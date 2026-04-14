<?php
namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Processor;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Models\WalletPayment;

class PricPayController extends Controller
{
    use Processor;

    private $gateway_url = 'https://gateway.pricpay.com/payment/gateway/payapi/cache-merchant-info';
    private $cashier_url = 'https://cashier.pricpay.com';
    private $config_values;
    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('pricpay', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
    }

    /**
     * 直接处理支付
     */
    public function payment(Request $request)
    {
        try {
            // 验证请求参数
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|uuid',
            ]);

            if ($validator->fails()) {
                return $this->renderErrorPage(translate('Invalid payment request parameters'));
            }

            $payment_data = $this->payment::where(['id' => $request['payment_id']])
                ->where(['is_paid' => 0])
                ->first();

            if (!isset($payment_data)) {
                return $this->renderErrorPage(translate('Payment request not found or already processed'));
            }

            if (empty($payment_data->attribute_id)) {
                return $this->renderErrorPage(translate('Order information is missing from payment request'));
            }

            // 直接创建支付订单
            return $this->createPayment($payment_data);

        } catch (\Exception $e) {
            Log::error('PricPay payment processing failed', [
                'error' => $e->getMessage(),
                'payment_id' => $request['payment_id'] ?? 'unknown',
            ]);
            
            return $this->renderErrorPage(translate('Payment processing failed. Please try again later or contact support.'));
        }
    }

    /**
     * 创建支付请求
     */
    private function createPayment($payment_data)
    {
        try {
            // 根据订单类型获取订单信息
            $order_info = $this->getOrderInfo($payment_data);
            if (!$order_info) {
                return $this->renderErrorPage(translate('Order not found'));
            }
            
            // 生成商户订单号
            $merchant_order_no = $this->generateOrderNumber($payment_data);
            
            // 构建支付参数
            $pay_args = $this->buildPaymentParams($payment_data, $merchant_order_no, $order_info);
            
            // 发送请求到PricPay网关
            $response = $this->sendGatewayRequest($pay_args);
            
            if ($response['success']) {
                // 更新支付请求状态
                $this->payment::where('id', $payment_data->id)->update([
                    'payment_method' => 'pricpay',
                    'transaction_id' => $response['body'],
                    'updated_at' => now()
                ]);

                // 构建收银台URL
                $checkout_url = $this->cashier_url . '?cashier=' . $response['body'];
                
                // 直接跳转到收银台
                return redirect($checkout_url);

            } else {
                // 记录错误
                Log::error('PricPay payment creation failed', [
                    'payment_id' => $payment_data->id,
                    'error' => $response['message']
                ]);

                return $this->renderErrorPage(translate('Payment creation failed: ') . $response['message']);
            }

        } catch (\Exception $e) {
            Log::error('PricPay payment creation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->renderErrorPage(translate('Payment creation failed'));
        }
    }

    /**
     * 根据订单类型获取订单信息
     */
    private function getOrderInfo($payment_data)
    {
        if ($payment_data->attribute === 'order') {
            return Order::find($payment_data->attribute_id);
        } elseif ($payment_data->attribute === 'wallet_payments') {
            return WalletPayment::find($payment_data->attribute_id);
        }
        
        return null;
    }

    /**
     * 构建支付参数
     */
    private function buildPaymentParams($payment_data, $merchant_order_no, $order_info)
    {
        // 根据订单类型构建商品信息
        $product_info = $this->buildProductInfo($payment_data, $order_info);

        // 构建商品信息JSON
        $product_information = json_encode($product_info);

        $params = [
            'websiteVersionNumber' => 'V1.0',
            'signType' => 'MD5',
            'merchantNo' => $this->config_values->merchant_no,
            'orderNumber' => $merchant_order_no,
            'merchantWebsite' => "https://loteat.com",
            'transactionAmount' => number_format($payment_data->payment_amount, 2, '.', ''),
            'transactionCurrency' => 'USD',
            'productInformation' => $product_information,
            'notifyUrl' => route('pricpay.notify', ['payment_id' => $payment_data->id]),
            'returnUrl' => route('pricpay.return', ['payment_id' => $payment_data->id]),
            'merchantOrderDate' => date('Ymd'),
            'remark' => 'Payment for order ' . $merchant_order_no,
        ];

        // 生成签名
        $params['signature'] = $this->generateSignature($params);

        return $params;
    }

    /**
     * 根据订单类型构建商品信息
     */
    private function buildProductInfo($payment_data, $order_info)
    {
        if ($payment_data->attribute === 'order') {
            return [[
                'name' => $order_info->product_name ?? 'Product',
                'quantity' => 1,
                'price' => $payment_data->payment_amount
            ]];
        } elseif ($payment_data->attribute === 'wallet_payments') {
            return [[
                'name' => 'Wallet Top Up',
                'quantity' => 1,
                'price' => $payment_data->payment_amount
            ]];
        }
        
        return [[
            'name' => 'Product',
            'quantity' => 1,
            'price' => $payment_data->payment_amount
        ]];
    }

    /**
     * 渲染错误页面
     */
    private function renderErrorPage($message, $showRetry = true)
    {
        return view('pricpay.payment-form', [
            'type' => 'error',
            'message' => $message,
            'showRetry' => $showRetry,
            'title' => translate('Payment Error')
        ]);
    }

    /**
     * 生成签名
     */
    private function generateSignature($params)
    {
        // 移除signature字段
        unset($params['signature']);
        
        // 移除空值参数
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }
        
        // 按键名排序
        ksort($params);
        
        // 构建签名字符串
        $sign_string = '';
        foreach ($params as $key => $value) {
            $sign_string .= $key . '=' . $value . '&';
        }
        
        // 添加密钥，格式为 secret=密钥值
        $sign_string .= 'secret=' . $this->config_values->secret_key;
        
        // 生成MD5签名并转换为大写
        return strtoupper(md5($sign_string));
    }

    /**
     * 发送网关请求
     */
    private function sendGatewayRequest($params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gateway_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($params))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error
            ];
        }

        if ($http_code === 200) {
            return [
                'success' => true,
                'body' => $response
            ];
        } else {
            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $http_code
            ];
        }
    }

    /**
     * 处理异步通知
     */
    public function handleNotify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|uuid'
            ]);

            if ($validator->fails()) {
                Log::error('PricPay notify: Invalid payment_id');
                return response('FAIL', 400);
            }

            $responseData = $request->all();
            Log::info('PricPay notify received', $responseData);

            // 验证签名
            if (!$this->verifyNotifySignature($responseData)) {
                Log::error('PricPay notify signature verification failed');
                return response('FAIL', 400);
            }

            // 查找支付请求
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

            if (!$payment_data) {
                Log::error('PricPay payment request not found', ['payment_id' => $request['payment_id']]);
                return response('FAIL', 404);
            }

            // 更新支付状态
            $order_status = $request->input('orderStatus');
            if ($order_status === '100') { // 交易成功
                DB::beginTransaction();
                try {
                    $this->payment::where('id', $payment_data->id)->update([
                        'payment_method' => 'pricpay',
                        'is_paid' => 1,
                        'transaction_id' => $request->input('platformOrderNo', $payment_data->transaction_id),
                        'updated_at' => now()
                    ]);

                    // 根据订单类型更新相关订单状态
                    $this->updateOrderStatus($payment_data, $request->input('platformOrderNo'));

                    // 调用成功回调
                    if (function_exists($payment_data->success_hook)) {
                        call_user_func($payment_data->success_hook, $payment_data);
                    }

                    DB::commit();
                    
                    Log::info('PricPay payment completed successfully', [
                        'payment_id' => $payment_data->id,
                        'platform_order_no' => $request->input('platformOrderNo')
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } elseif ($order_status === '120') { // 交易失败
                Log::info('PricPay payment failed', [
                    'payment_id' => $payment_data->id,
                    'platform_order_no' => $request->input('platformOrderNo')
                ]);
            } elseif ($order_status === '103') { // 交易处理中
                Log::info('PricPay payment processing', [
                    'payment_id' => $payment_data->id,
                    'platform_order_no' => $request->input('platformOrderNo')
                ]);
            }

            return response('SUCCESS', 200);

        } catch (\Exception $e) {
            Log::error('PricPay notify handling exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('FAIL', 500);
        }
    }

    /**
     * 根据订单类型更新订单状态
     */
    private function updateOrderStatus($payment_data, $platform_order_no)
    {
        if ($payment_data->attribute === 'wallet_payments') {
            // 更新充值订单状态
            WalletPayment::where('id', $payment_data->attribute_id)->update([
                'payment_status' => 'completed',
                'transaction_ref' => $platform_order_no,
                'updated_at' => now()
            ]);
        } elseif ($payment_data->attribute === 'order') {
            // 更新普通订单状态（如果需要的话）
            // Order::where('id', $payment_data->attribute_id)->update([
            //     'payment_status' => 'completed',
            //     'updated_at' => now()
            // ]);
        }
    }

    /**
     * 处理同步回调
     */
    public function handleReturn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|uuid',
                'originOrderId' => 'required|string',
                'orderStatus' => 'required|string'
            ]);

            if ($validator->fails()) {
                Log::error('PricPay return: Invalid parameters', $validator->errors()->all());
                return $this->payment_response(null, 'fail');
            }

            $payment_id = $request->input('payment_id');
            $origin_order_id = $request->input('originOrderId');
            $order_status = $request->input('orderStatus');

            Log::info('PricPay return received', [
                'payment_id' => $payment_id,
                'origin_order_id' => $origin_order_id,
                'order_status' => $order_status
            ]);

            // 查找支付请求
            $payment_data = $this->payment::where(['id' => $payment_id])->first();

            if (!$payment_data) {
                Log::error('PricPay return: Payment request not found', ['payment_id' => $payment_id]);
                return $this->payment_response(null, 'fail');
            }

            // 验证订单号是否匹配
            $expected_order_id = $this->generateOrderNumber($payment_data);
            if ($origin_order_id !== $expected_order_id) {
                Log::error('PricPay return: Order ID mismatch', [
                    'expected' => $expected_order_id,
                    'received' => $origin_order_id
                ]);
                return $this->payment_response(null, 'fail');
            }

            // 根据支付状态处理
            $status = 'fail';
            if ($order_status === 'SUCCESS') {
                $status = 'success';
                Log::info('PricPay return: Payment successful', [
                    'payment_id' => $payment_id,
                    'origin_order_id' => $origin_order_id
                ]);
            } elseif ($order_status === 'FAILED') {
                $status = 'fail';
                Log::info('PricPay return: Payment failed', [
                    'payment_id' => $payment_id,
                    'origin_order_id' => $origin_order_id
                ]);
            } elseif ($order_status === 'PROCESSING') {
                $status = 'processing';
                Log::info('PricPay return: Payment processing', [
                    'payment_id' => $payment_id,
                    'origin_order_id' => $origin_order_id
                ]);
            }
            
            return $this->payment_response($payment_data, $status);

        } catch (\Exception $e) {
            Log::error('PricPay return handling exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->payment_response(null, 'fail');
        }
    }

    /**
     * 验证通知签名
     */
    private function verifyNotifySignature($params)
    {
        $signature = $params['signature'] ?? '';
        
        // 移除不参与签名的字段
        $sign_params = $params;
        unset($sign_params['signature']);
        unset($sign_params['payment_id']);
        
        $generated_signature = $this->generateSignature($sign_params);
        
        return hash_equals($signature, $generated_signature);
    }

    /**
     * 验证返回签名
     */
    private function verifyReturnSignature($params)
    {
        return $this->verifyNotifySignature($params);
    }

    /**
     * 生成商户订单号
     */
    private function generateOrderNumber($payment_data)
    {
        if ($payment_data->attribute === 'order') {
            return 'PRIC-ORDER-' . $payment_data->attribute_id;
        } elseif ($payment_data->attribute === 'wallet_payments') {
            return 'PRIC-WALLET-' . $payment_data->attribute_id;
        }
        return 'PRIC-PAY-' . $payment_data->attribute_id;
    }

    /**
     * 查询支付状态
     */
    public function queryPaymentStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|uuid'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

            if (!$payment_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment_data->id,
                    'attribute_id' => $payment_data->attribute_id,
                    'transaction_id' => $payment_data->transaction_id,
                    'status' => $payment_data->is_paid ? 'completed' : 'pending',
                    'amount' => $payment_data->payment_amount,
                    'created_at' => $payment_data->created_at,
                    'updated_at' => $payment_data->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('PricPay query payment status exception', [
                'error' => $e->getMessage(),
                'payment_id' => $request['payment_id'] ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Query failed'
            ], 500);
        }
    }

    /**
     * 支付取消
     */
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return $this->payment_response(null, 'cancel');
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($payment_data, 'cancel');
    }
}