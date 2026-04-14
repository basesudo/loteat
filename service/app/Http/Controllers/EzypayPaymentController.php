<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\Processor;
use App\Models\PaymentRequest;

class EzypayPaymentController extends Controller
{
    use Processor;

    private $config_values;
    private $nonce_str;
    private $millisecond;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('ezypay', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->nonce_str = $this->getNonceStr();
        $this->millisecond = $this->getMillisecond();
        $this->payment = $payment;
    }

    /**
     *
     * 产生随机字符串，不长于30位
     * @param int $length
     * @return $str 产生的随机字符串
     */
    public static function getNonceStr($length = 30)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode(" ", microtime());
        $millisecond = "000".($time[0] * 1000);
        $millisecond2 = explode(".", $millisecond);
        $millisecond = substr($millisecond2[0],-3);
        $time = $time[1] . $millisecond;
        return $time;
    }

    public function toSignParams()
    {
        $buff = "";
        $buff .= $this->config_values->partner_code . '&' . $this->millisecond . '&' . $this->nonce_str . "&" . $this->config_values->credential_code;
        return $buff;
    }

    public function makeSign()
    {
        //签名步骤一：构造签名参数
        $string = $this->toSignParams();
        //签名步骤三：SHA256加密
        $string = hash('sha256', utf8_encode($string));
        //签名步骤四：所有字符转为小写
        $result = strtolower($string);
        return $result;
    }

    /**
     * Responds with a welcome message with instructions
     *
     */
    public function payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $create_payment_order = 'https://usd.mobilebank.co.nz/api/v1.0/gateway/partners/'.$this->config_values->partner_code.'/pre_card_orders/'.$data->id;
        $create_payment_order .= '?nonce_str=' . $this->nonce_str;
        $create_payment_order .= '&time=' . $this->millisecond;
        $create_payment_order .= '&sign=' . $this->makeSign();

        $post_data = [
            'description' => "Order #{$data->id}",
            'currency' => 'USD',
            'price' => (int)($data->payment_amount * 100),
            'expire'=> '30m',
            'notify_url' => route('ezypay.notify',['payment_id' => $data->id]),
            'redirect' => route('ezypay.success',['payment_id' => $data->id]),
            'extra' => [],
            'customer' => []
        ];

        var_dump($post_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $create_payment_order);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $response = json_decode($response, true); // 改为数组格式便于访问

        // 验证响应是否成功
        if (!isset($response['return_code']) || $response['return_code'] !== 'SUCCESS') {
            $error_message = isset($response['error']) ? $response['error'] : 'Payment request failed';
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'payment_error', 'message' => $error_message]]), 400);
        }

        // 验证是否有支付URL
        if (!isset($response['pay_url'])) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'payment_error', 'message' => 'Payment URL not found']]), 400);
        }

        // 更新支付记录
        $this->payment::where(['id' => $data->id])->update([
            'payment_method' => 'ezypay',
            'is_paid' => 0,
            'transaction_id' => $response['order_id'], // 使用 order_id 作为交易ID
        ]);

        return Redirect::away($response['pay_url']);

        return 0;

    }

    /**
     * Responds with a welcome message with instructions
     */
    public function cancel(Request $request)
    {
        $data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($data,'cancel');
    }

    /**
     * 支付成功页面处理
     * 只处理页面展示，不执行业务逻辑（业务逻辑在异步通知中处理）
     */
    public function success(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        // 查询订单状态的 API 调用
        $order_status_url = 'https://usd.mobilebank.co.nz/api/v1.0/gateway/partners/'.$this->config_values->partner_code.'/orders/'.$request['payment_id'];
        $order_status_url .= '?nonce_str=' . $this->nonce_str;
        $order_status_url .= '&time=' . $this->millisecond;
        $order_status_url .= '&sign=' . $this->makeSign();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $order_status_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'curl_error', 'message' => $error]]), 400);
        }
        curl_close($ch);

        $response = json_decode($result, true);

        // 获取支付记录
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

        // 检查支付是否成功
        if (isset($response['result_code']) && $response['result_code'] === 'PAY_SUCCESS') {
            // 只更新支付状态，不执行业务逻辑
            // 业务逻辑将在异步通知中统一处理
            $this->payment::where(['id' => $request['payment_id']])
                ->where('is_paid', 0) // 只有未支付的才更新
                ->update([
                    'payment_method' => 'ezypay',
                    'is_paid' => 1,
                    'transaction_id' => $response['order_id'] ?? $request['payment_id'],
                ]);

            // 重新获取更新后的数据
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

            // 注意：这里不调用 success_hook，避免重复处理
            // success_hook 只在异步通知中调用

            return $this->payment_response($payment_data, 'success');
        }

        // 支付失败的情况
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    /**
     * 支付通知接口
     * EzyPay 支付成功后的回调通知
     * 这里执行业务逻辑，确保只执行一次
     */
    public function notify(Request $request)
    {
        // 获取原始请求体
        $raw_body = $request->getContent();
        $notification_data = json_decode($raw_body, true);

        // 验证必要字段
        if (!$notification_data || 
            !isset($notification_data['time']) || 
            !isset($notification_data['nonce_str']) || 
            !isset($notification_data['sign']) ||
            !isset($notification_data['partner_order_id'])) {
            return response()->json(['return_code' => 'FAIL', 'message' => 'Invalid notification data'], 400);
        }

        // 验证签名
        if (!$this->verifyNotificationSign($notification_data)) {
            return response()->json(['return_code' => 'FAIL', 'message' => 'Invalid signature'], 400);
        }

        // 查找对应的支付记录
        $payment_data = $this->payment::where(['id' => $notification_data['partner_order_id']])->first();
        
        if (!$payment_data) {
            return response()->json(['return_code' => 'FAIL', 'message' => 'Payment record not found'], 404);
        }

        // 使用数据库事务和锁，确保原子性操作
        try {
            DB::beginTransaction();

            // 使用 lockForUpdate 防止并发处理
            $payment_record = $this->payment::where(['id' => $notification_data['partner_order_id']])
                ->lockForUpdate()
                ->first();

            // 如果已经处理过，直接返回成功（防止重复处理）
            if ($payment_record->is_paid == 1) {
                DB::commit();
                return response()->json(['return_code' => 'SUCCESS']);
            }

            // 更新支付记录
            $affected = $this->payment::where(['id' => $notification_data['partner_order_id']])
                ->where('is_paid', 0) // 确保只有未支付的记录才被更新
                ->update([
                    'payment_method' => 'ezypay',
                    'is_paid' => 1,
                    'transaction_id' => $notification_data['order_id'],
                    'updated_at' => now()
                ]);

            // 如果没有记录被更新，说明已经处理过了
            if ($affected === 0) {
                DB::commit();
                return response()->json(['return_code' => 'SUCCESS']);
            }

            // 重新获取更新后的支付数据
            $updated_payment_data = $this->payment::where(['id' => $notification_data['partner_order_id']])->first();

            // 提交事务
            DB::commit();

            // 只有成功更新了支付状态，才调用业务逻辑
            if (isset($updated_payment_data) && function_exists($updated_payment_data->success_hook)) {
                call_user_func($updated_payment_data->success_hook, $updated_payment_data);
            }

            // 记录通知日志（可选）
            \Log::info('Ezypay payment notification processed successfully', [
                'partner_order_id' => $notification_data['partner_order_id'],
                'order_id' => $notification_data['order_id'],
                'total_fee' => $notification_data['total_fee'] ?? 0,
                'real_fee' => $notification_data['real_fee'] ?? 0,
                'channel' => $notification_data['channel'] ?? '',
                'pay_time' => $notification_data['pay_time'] ?? ''
            ]);

            return response()->json(['return_code' => 'SUCCESS']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Ezypay notification processing failed', [
                'error' => $e->getMessage(),
                'partner_order_id' => $notification_data['partner_order_id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['return_code' => 'FAIL', 'message' => 'Processing failed'], 500);
        }
    }

    /**
     * 验证通知签名
     */
    private function verifyNotificationSign($notification_data)
    {
        // 构造签名参数（根据EzyPay的签名规则）
        $sign_params = $this->config_values->partner_code . '&' . 
                      $notification_data['time'] . '&' . 
                      $notification_data['nonce_str'] . '&' . 
                      $this->config_values->credential_code;

        // 生成签名
        $calculated_sign = strtolower(hash('sha256', utf8_encode($sign_params)));
        
        // 比较签名
        return $calculated_sign === $notification_data['sign'];
    }
}