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

// Antom SDK classes
use Request\pay\AlipayPayRequest;
use Request\pay\AlipayPaymentSessionRequest;
use Request\pay\AlipayPayConsultRequest;
use Client\DefaultAlipayClient;
use Client\SignatureTool;
use Model\Amount;
use Model\Buyer;    
use Model\Env;
use Model\Merchant;
use Model\Order;
use Model\OsType;
use Model\PaymentFactor;
use Model\PaymentMethod;
use Model\PresentmentMode;
use Model\ProductCodeType;
use Model\SettlementStrategy;
use Model\Store;
use Model\TerminalType;
use Model\WalletPaymentMethodType;

class AntomPaymentController extends Controller
{
    use Processor;

    private $config_values;
    private $alipayClient;
    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('antom', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }

        // 初始化 Antom 客户端
        if ($this->config_values) {
            $this->alipayClient = new DefaultAlipayClient(
                $this->config_values->gateway_url ?? "https://open-sea-global.alipay.com",
                $this->config_values->merchant_private_key,
                $this->config_values->antom_public_key
            );
        }

        $this->payment = $payment;
    }

    /**
     * 根据货币类型转换金额到Antom所需的最小单位
     * 
     * @param float $amount 原始金额
     * @param string $currency 货币代码
     * @return string 转换后的金额字符串
     */
    private function convertAmountForCurrency($amount, $currency)
    {
        // 需要乘以100的货币（2位小数）
        $decimalCurrencies = [
            'AUD', 'BDT', 'BRL', 'CAD', 'CNY', 'EUR', 'GBP', 'HKD',
            'IDR', 'MXN', 'MYR', 'NZD', 'PEN', 'PHP', 'PKR', 'PLN',
            'SGD', 'THB', 'TWD', 'USD'
        ];
        
        // 不需要乘以100的货币（0位小数）
        $wholeCurrencies = [
            'CLP', 'JPY', 'KRW', 'VND'
        ];
        
        $currency = strtoupper($currency);
        
        if (in_array($currency, $decimalCurrencies)) {
            // 2位小数货币，需要乘以100转换为最小单位
            return (string)round($amount * 100);
        } elseif (in_array($currency, $wholeCurrencies)) {
            // 0位小数货币，直接使用整数值
            return (string)round($amount);
        } else {
            // 默认处理：假设为2位小数货币
            return (string)round($amount * 100);
        }
    }

    /**
     * 获取货币的小数位数
     * 
     * @param string $currency 货币代码
     * @return int 小数位数
     */
    private function getCurrencyDecimalPlaces($currency)
    {
        $currency = strtoupper($currency);
        
        // 0位小数的货币
        $noDecimalCurrencies = ['CLP', 'JPY', 'KRW', 'VND'];
        
        return in_array($currency, $noDecimalCurrencies) ? 0 : 2;
    }

    public function paymentConsult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        if (!$this->config_values || !$this->alipayClient) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'config_error', 'message' => 'Payment gateway not configured']]), 400);
        }

        try {
            // 创建支付咨询请求
            $consultRequest = new AlipayPayConsultRequest();

            // 设置必填项：环境信息
            $env = new Env();
            $env->setTerminalType(TerminalType::WEB);
            $consultRequest->setEnv($env);

            // 设置必填项：支付金额 - 使用新的转换方法
            $paymentAmount = new Amount();
            $currency = strtoupper($request->input('currency', 'USD'));
            $paymentAmount->setCurrency($currency);
            $paymentAmount->setValue($this->convertAmountForCurrency($request->input('amount'), $currency));
            $consultRequest->setPaymentAmount($paymentAmount);

            // 设置必填项：产品代码
            $consultRequest->setProductCode(ProductCodeType::CASHIER_PAYMENT);

            // 可选：设置客户端ID
            if (isset($this->config_values->client_id)) {
                $consultRequest->setClientId($this->config_values->client_id);
            }

            // 可选：设置商户信息
            $merchant = new Merchant();
            $merchant->setReferenceMerchantId($this->config_values->merchant_id ?? 'default_merchant');
            $consultRequest->setMerchant($merchant);

            // 执行咨询请求
            $consultResponse = $this->alipayClient->execute($consultRequest);

            return response()->json($consultResponse);
            // 处理响应
            if ($consultResponse && isset($consultResponse->result)) {
                // 记录咨询日志
                Log::info('Antom payment consult successful', [
                    'payment_id' => $request->input('payment_id'),
                    'amount' => $request->input('amount'),
                    'currency' => $currency,
                    'converted_amount' => $this->convertAmountForCurrency($request->input('amount'), $currency),
                    'available_methods' => $consultResponse->paymentMethods ?? []
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'available_payment_methods' => $consultResponse->paymentMethods ?? [],
                        'consult_id' => $consultResponse->consultId ?? null,
                        'currency_info' => $consultResponse->currencyInfo ?? null,
                        'payment_amount' => [
                            'currency' => $currency,
                            'value' => $request->input('amount'),
                            'converted_value' => $this->convertAmountForCurrency($request->input('amount'), $currency)
                        ]
                    ]
                ]);
            } else {
                Log::error('Antom payment consult failed', [
                    'payment_id' => $request->input('payment_id'),
                    'response' => $consultResponse
                ]);

                return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'consult_error', 'message' => 'Payment consult failed']]), 400);
            }

        } catch (\Exception $e) {
            Log::error('Antom payment consult error: ' . $e->getMessage(), [
                'payment_id' => $request->input('payment_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'consult_error', 'message' => 'Payment consult processing failed']]), 400);
        }
    }
    
    /**
     * 创建支付订单
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

        if (!$this->config_values || !$this->alipayClient) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'config_error', 'message' => 'Payment gateway not configured']]), 400);
        }

        try {
            // 创建支付会话请求
            $antomRequest = new AlipayPaymentSessionRequest();
            $paymentRequestId = 'PR_' . $data->id;

            // 设置订单信息
            $order = new Order();
            $order->setOrderDescription("Order #{$data->id}");
            $order->setReferenceOrderId($data->id);
            $currency = 'USD';
            // 设置订单金额
            $orderAmount = new Amount();
            $orderAmount->setCurrency($currency);
            $orderAmount->setValue($this->convertAmountForCurrency($data->payment_amount, $currency));
            $order->setOrderAmount($orderAmount);

            // 设置买家信息
            $buyer = new Buyer();
            $buyer->setReferenceBuyerId("buyer_" . $data->id);
            $order->setBuyer($buyer);

            // 设置环境信息
            $env = new Env();
            $env->setTerminalType(TerminalType::WEB);
            $order->setEnv($env);

            $antomRequest->setOrder($order);

            // 设置支付金额
            $paymentAmount = new Amount();
            $paymentAmount->setCurrency($currency);
            $paymentAmount->setValue($this->convertAmountForCurrency($data->payment_amount, $currency));
            $antomRequest->setPaymentAmount($paymentAmount);

            // 设置可用支付方式
            $availablePaymentMethod = new \stdClass();
            $availablePaymentMethod->paymentMethodTypeList = [
                [
                    'paymentMethodType' => 'RABBIT_LINE_PAY',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '0'
                ],
                [
                    'paymentMethodType' => 'ALIPAY_CN',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '1'
                ],
                [
                    'paymentMethodType' => 'KREDIVO_ID',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '2'
                ],
                [
                    'paymentMethodType' => 'BOOST',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '3'
                ],
                [
                    'paymentMethodType' => 'ALIPAY_HK',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '4'
                ],
                [
                    'paymentMethodType' => 'GCASH',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '5'
                ],
                
                [
                    'paymentMethodType' => 'KAKAOPAY',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '6'
                ],
                [
                    'paymentMethodType' => 'TNG',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '7'
                ],
                [
                    'paymentMethodType' => 'BANCOMATPAY',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '8'
                ],
                [
                    'paymentMethodType' => 'BLIK',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '9'
                ],
                [
                    'paymentMethodType' => 'BANCONTACT',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '10'
                ],
                [
                    'paymentMethodType' => 'EPS',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '11'
                ],
                [
                    'paymentMethodType' => 'PAYU',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '12'
                ],
                [
                    'paymentMethodType' => 'PIX',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '13'
                ],
                [
                    'paymentMethodType' => 'P24',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '14'
                ],
                [
                    'paymentMethodType' => 'TRUEMONEY',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '15'
                ],
                [
                    'paymentMethodType' => 'IDEAL',
                    'expressCheckout' => false,
                    'paymentMethodOrder' => '16'
                ]
            ];
            $antomRequest->setAvailablePaymentMethod($availablePaymentMethod);

            // 设置回调URL
            $antomRequest->setPaymentNotifyUrl(route('antom.notify', ['payment_id' => $data->id]));
            $antomRequest->setPaymentRedirectUrl(route('antom.success', ['payment_id' => $data->id]));

            // 设置产品代码和场景
            $antomRequest->setProductCode(ProductCodeType::CASHIER_PAYMENT);
            $antomRequest->setProductScene('CHECKOUT_PAYMENT');
            $antomRequest->setClientId($this->config_values->client_id);
            $antomRequest->setPaymentRequestId($paymentRequestId);

            // 设置结算策略
            $settlementStrategy = new SettlementStrategy();
            $settlementStrategy->setSettlementCurrency("HKD");
            $antomRequest->setSettlementStrategy($settlementStrategy);

            // 创建客户端并执行请求
            $alipayClient = new DefaultAlipayClient(
                $this->config_values->gateway_url ?? "https://open-sea-global.alipay.com",
                $this->config_values->merchant_private_key,
                $this->config_values->antom_public_key
            );
            
            $alipayResponse = $alipayClient->execute($antomRequest);

            // 更新支付记录
            $this->payment::where(['id' => $data->id])->update([
                'payment_method' => 'antom',
                'is_paid' => 0,
                'transaction_id' => $paymentRequestId,
            ]);

            // 返回支付URL
            if (isset($alipayResponse->normalUrl)) {
                return Redirect::away($alipayResponse->normalUrl);
            } else {
                Log::error('Antom payment error: No payment URL returned', [
                    'payment_id' => $data->id,
                    'response' => $alipayResponse
                ]);
                return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'payment_error', 'message' => 'Payment URL not found']]), 400);
            }

        } catch (\Exception $e) {
            Log::error('Antom payment error: ' . $e->getMessage(), [
                'payment_id' => $data->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'payment_error', 'message' => 'Payment processing failed']]), 400);
        }
    }

    /**
     * 支付取消处理
     */
    public function cancel(Request $request)
    {
        $data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($data, 'cancel');
    }

    /**
     * 支付成功页面处理
     */
    public function success(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        // 获取支付记录
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

        if (!$payment_data) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_404), 404);
        }

        // 检查支付状态（可以选择性地调用Antom查询接口验证）
        // 这里简化处理，主要依赖异步通知更新状态
        
        return $this->payment_response($payment_data, $payment_data->is_paid ? 'success' : 'pending');
    }

    /**
     * 支付通知接口
     * Antom 支付成功后的回调通知
     */
    public function notify(Request $request)
    {
        // 生成唯一的通知处理ID，用于追踪整个处理流程
        $notify_id = 'NOTIFY_' . time() . '_' . Str::random(8);
        
        Log::info('Antom notification received', [
            'notify_id' => $notify_id,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query_params' => $request->query(),
            'user_agent' => $request->header('User-Agent'),
            'ip' => $request->ip(),
            'payment_id_from_route' => $request->route('payment_id')
        ]);

        try {
            // Step 1: 获取原始请求体
            Log::info('Step 1: Getting raw request body', ['notify_id' => $notify_id]);
            $raw_body = $request->getContent();
            
            Log::info('Step 1 completed: Raw body retrieved', [
                'notify_id' => $notify_id,
                'body_length' => strlen($raw_body),
                'body_preview' => substr($raw_body, 0, 200) // 只记录前200字符
            ]);

            // Step 2: 验证签名
            Log::info('Step 2: Starting signature verification', [
                'notify_id' => $notify_id,
                'signature_header' => $request->header('signature'),
                'client_id_header' => $request->header('client-id'),
                'request_time_header' => $request->header('request-time')
            ]);
            
            if (!$this->verifyNotificationSign($request)) {
                Log::warning('Step 2 failed: Signature verification failed', [
                    'notify_id' => $notify_id,
                    'all_headers' => $request->headers->all()
                ]);
                
                return response()->json([
                    'result' => [
                        'resultCode' => 'SIGNATURE_INVALID',
                        'resultStatus' => 'F',
                        'resultMessage' => 'Invalid signature'
                    ]
                ], 400);
            }
            
            Log::info('Step 2 completed: Signature verification passed', ['notify_id' => $notify_id]);

            // Step 3: 解析通知数据
            Log::info('Step 3: Parsing notification data', ['notify_id' => $notify_id]);
            $notification_data = json_decode($raw_body, true);
            
            if (!$notification_data) {
                Log::error('Step 3 failed: Invalid JSON data', [
                    'notify_id' => $notify_id,
                    'raw_body' => $raw_body,
                    'json_last_error' => json_last_error_msg()
                ]);
                
                return response()->json([
                    'result' => [
                        'resultCode' => 'INVALID_DATA',
                        'resultStatus' => 'F',
                        'resultMessage' => 'Invalid notification data'
                    ]
                ], 400);
            }
            
            Log::info('Step 3 completed: Data parsed successfully', [
                'notify_id' => $notify_id,
                'parsed_keys' => array_keys($notification_data),
                'payment_request_id' => $notification_data['paymentRequestId'] ?? 'not_found',
                'reference_order_id' => $notification_data['referenceOrderId'] ?? 'not_found'
            ]);

            // Step 4: 从通知数据中获取 paymentRequestId
            Log::info('Step 4: Extracting paymentRequestId', ['notify_id' => $notify_id]);
            $paymentRequestId = $notification_data['paymentRequestId'] ?? null;
            
            if (!$paymentRequestId) {
                Log::error('Step 4 failed: paymentRequestId not found in notification', [
                    'notify_id' => $notify_id,
                    'notification_data_structure' => array_keys($notification_data)
                ]);
                
                return response()->json([
                    'result' => [
                        'resultCode' => 'PAYMENT_REQUEST_ID_NOT_FOUND',
                        'resultStatus' => 'F',
                        'resultMessage' => 'Payment request ID not found'
                    ]
                ], 400);
            }
            
            Log::info('Step 4 completed: paymentRequestId extracted', [
                'notify_id' => $notify_id,
                'payment_request_id' => $paymentRequestId
            ]);

            // Step 5: 使用 paymentRequestId 查找支付记录
            Log::info('Step 5: Looking up payment record by transaction_id', [
                'notify_id' => $notify_id,
                'payment_request_id' => $paymentRequestId
            ]);
            
            $payment_data = $this->payment::where(['transaction_id' => $paymentRequestId])->first();
            
            if (!$payment_data) {
                Log::error('Step 5 failed: Payment record not found', [
                    'notify_id' => $notify_id,
                    'payment_request_id' => $paymentRequestId
                ]);
                
                return response()->json([
                    'result' => [
                        'resultCode' => 'PAYMENT_NOT_FOUND',
                        'resultStatus' => 'F',
                        'resultMessage' => 'Payment record not found'
                    ]
                ], 404);
            }
            
            Log::info('Step 5 completed: Payment record found', [
                'notify_id' => $notify_id,
                'payment_id' => $payment_data->id,
                'payment_request_id' => $paymentRequestId,
                'current_is_paid' => $payment_data->is_paid,
                'payment_amount' => $payment_data->payment_amount,
                'current_transaction_id' => $payment_data->transaction_id
            ]);

            // Step 6: 开始数据库事务
            Log::info('Step 6: Starting database transaction', ['notify_id' => $notify_id]);
            DB::beginTransaction();

            try {
                // Step 7: 获取锁定的支付记录
                Log::info('Step 7: Acquiring payment record lock', [
                    'notify_id' => $notify_id,
                    'payment_request_id' => $paymentRequestId
                ]);
                
                $payment_record = $this->payment::where(['transaction_id' => $paymentRequestId])
                    ->lockForUpdate()
                    ->first();

                Log::info('Step 7 completed: Payment record locked', [
                    'notify_id' => $notify_id,
                    'payment_id' => $payment_record->id,
                    'locked_is_paid' => $payment_record->is_paid
                ]);

                // Step 8: 检查是否已处理
                if ($payment_record->is_paid == 1) {
                    Log::info('Step 8: Payment already processed, skipping', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id,
                        'current_status' => 'already_paid'
                    ]);
                    
                    DB::commit();
                    return response()->json([
                        'result' => [
                            'resultCode' => 'SUCCESS',
                            'resultStatus' => 'S',
                            'resultMessage' => 'success'
                        ]
                    ]);
                }
                
                Log::info('Step 8 completed: Payment not yet processed, continuing', [
                    'notify_id' => $notify_id,
                    'payment_id' => $payment_record->id
                ]);

                // Step 9: 检查支付状态
                Log::info('Step 9: Checking payment success status', [
                    'notify_id' => $notify_id,
                    'payment_id' => $payment_record->id,
                    'notification_result' => $notification_data['result'] ?? 'not_found'
                ]);
                
                $is_success = $this->isPaymentSuccess($notification_data);
                
                Log::info('Step 9 completed: Payment status determined', [
                    'notify_id' => $notify_id,
                    'payment_id' => $payment_record->id,
                    'is_success' => $is_success,
                    'result_status' => $notification_data['result']['resultStatus'] ?? 'unknown',
                    'result_code' => $notification_data['result']['resultCode'] ?? 'unknown',
                    'result_message' => $notification_data['result']['resultMessage'] ?? 'unknown'
                ]);
                
                if ($is_success) {
                    // Step 10: 更新支付记录
                    Log::info('Step 10: Updating payment record for success', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id,
                        'new_transaction_id' => $notification_data['paymentId'] ?? $payment_record->transaction_id
                    ]);
                    
                    $affected = $this->payment::where(['transaction_id' => $paymentRequestId])
                        ->where('is_paid', 0)
                        ->update([
                            'payment_method' => 'antom',
                            'is_paid' => 1,
                            'transaction_id' => $notification_data['paymentId'] ?? $payment_record->transaction_id,
                            'updated_at' => now()
                        ]);

                    Log::info('Step 10 completed: Payment record update result', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id,
                        'affected_rows' => $affected
                    ]);

                    // Step 11: 检查更新结果
                    if ($affected === 0) {
                        Log::warning('Step 11: No rows affected, payment may have been processed concurrently', [
                            'notify_id' => $notify_id,
                            'payment_id' => $payment_record->id
                        ]);
                        
                        DB::commit();
                        return response()->json([
                            'result' => [
                                'resultCode' => 'SUCCESS',
                                'resultStatus' => 'S',
                                'resultMessage' => 'success'
                            ]
                        ]);
                    }

                    // Step 12: 重新获取更新后的数据
                    Log::info('Step 12: Retrieving updated payment data', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id
                    ]);
                    
                    $updated_payment_data = $this->payment::where(['id' => $payment_record->id])->first();
                    
                    Log::info('Step 12 completed: Updated payment data retrieved', [
                        'notify_id' => $notify_id,
                        'payment_id' => $updated_payment_data->id,
                        'final_is_paid' => $updated_payment_data->is_paid,
                        'final_transaction_id' => $updated_payment_data->transaction_id
                    ]);

                    // Step 13: 提交事务
                    Log::info('Step 13: Committing database transaction', ['notify_id' => $notify_id]);
                    DB::commit();
                    Log::info('Step 13 completed: Transaction committed successfully', ['notify_id' => $notify_id]);

                    // Step 14: 调用成功回调
                    Log::info('Step 14: Calling success hook', [
                        'notify_id' => $notify_id,
                        'payment_id' => $updated_payment_data->id,
                        'has_success_hook' => isset($updated_payment_data) && function_exists($updated_payment_data->success_hook)
                    ]);
                    
                    if (isset($updated_payment_data) && function_exists($updated_payment_data->success_hook)) {
                        try {
                            call_user_func($updated_payment_data->success_hook, $updated_payment_data);
                            Log::info('Step 14 completed: Success hook called successfully', ['notify_id' => $notify_id]);
                        } catch (\Exception $hookException) {
                            Log::error('Step 14 warning: Success hook execution failed', [
                                'notify_id' => $notify_id,
                                'hook_error' => $hookException->getMessage()
                            ]);
                        }
                    } else {
                        Log::info('Step 14 skipped: No success hook to call', ['notify_id' => $notify_id]);
                    }

                    // Step 15: 记录最终成功日志
                    Log::info('Step 15: Payment notification processed successfully', [
                        'notify_id' => $notify_id,
                        'payment_id' => $updated_payment_data->id,
                        'transaction_id' => $notification_data['paymentId'] ?? '',
                        'amount' => $notification_data['paymentAmount']['value'] ?? 0,
                        'currency' => $notification_data['paymentAmount']['currency'] ?? '',
                        'result_status' => $notification_data['result']['resultStatus'] ?? '',
                        'processing_time' => microtime(true)
                    ]);

                    return response()->json([
                        'result' => [
                            'resultCode' => 'SUCCESS',
                            'resultStatus' => 'S',
                            'resultMessage' => 'success'
                        ]
                    ]);
                    
                } else {
                    // Step 16: 处理支付失败
                    Log::warning('Step 16: Processing payment failure', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id,
                        'result_status' => $notification_data['result']['resultStatus'] ?? 'unknown',
                        'result_code' => $notification_data['result']['resultCode'] ?? 'unknown',
                        'result_message' => $notification_data['result']['resultMessage'] ?? 'unknown'
                    ]);
                    
                    DB::rollBack();
                    Log::info('Step 16: Transaction rolled back for failed payment', ['notify_id' => $notify_id]);
                    
                    // Step 17: 调用失败回调
                    Log::info('Step 17: Calling failure hook', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id,
                        'has_failure_hook' => isset($payment_data) && function_exists($payment_data->failure_hook)
                    ]);
                    
                    if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                        try {
                            call_user_func($payment_data->failure_hook, $payment_data);
                            Log::info('Step 17 completed: Failure hook called successfully', ['notify_id' => $notify_id]);
                        } catch (\Exception $hookException) {
                            Log::error('Step 17 warning: Failure hook execution failed', [
                                'notify_id' => $notify_id,
                                'hook_error' => $hookException->getMessage()
                            ]);
                        }
                    } else {
                        Log::info('Step 17 skipped: No failure hook to call', ['notify_id' => $notify_id]);
                    }

                    Log::warning('Step 18: Payment notification processed as failure', [
                        'notify_id' => $notify_id,
                        'payment_id' => $payment_record->id,
                        'final_status' => 'failed'
                    ]);

                    return response()->json([
                        'result' => [
                            'resultCode' => 'SUCCESS',
                            'resultStatus' => 'S',
                            'resultMessage' => 'success'
                        ]
                    ]);
                }

            } catch (\Exception $dbException) {
                Log::error('Database transaction error in notify', [
                    'notify_id' => $notify_id,
                    'payment_request_id' => $paymentRequestId ?? 'unknown',
                    'db_error' => $dbException->getMessage(),
                    'db_trace' => $dbException->getTraceAsString()
                ]);
                
                DB::rollBack();
                throw $dbException;
            }

        } catch (\Exception $e) {
            Log::error('Critical error in Antom notification processing', [
                'notify_id' => $notify_id,
                'payment_request_id' => $paymentRequestId ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'result' => [
                    'resultCode' => 'SYSTEM_ERROR',
                    'resultStatus' => 'F',
                    'resultMessage' => 'Processing failed'
                ]
            ], 500);
        }
    }

    /**
     * 验证通知签名
     */
    private function verifyNotificationSign(Request $request)
    {
        try {
            Log::info('Starting signature verification', [
                'method' => 'verifyNotificationSign'
            ]);
            
            // 获取签名相关头信息
            $signature = $request->header('signature');
            $clientId = $request->header('client-id') ?? $this->config_values->client_id;
            $responseTime = $request->header('request-time');
            
            Log::info('Signature verification headers extracted', [
                'signature_present' => $signature ? true : false,
                'client_id_present' => $clientId ? true : false,
                'response_time_present' => $responseTime ? true : false,
                'signature_preview' => $signature ? substr($signature, 0, 50) . '...' : null
            ]);
            
            if (!$signature || !$clientId || !$responseTime) {
                Log::warning('Signature verification failed: missing required headers', [
                    'signature' => $signature ? 'present' : 'missing',
                    'client_id' => $clientId ? 'present' : 'missing',
                    'response_time' => $responseTime ? 'present' : 'missing'
                ]);
                return false;
            }

            // 获取请求体
            $body = $request->getContent();
            
            // 获取请求信息
            $httpMethod = $request->getMethod();
            $path = $request->getPathInfo();
            
            Log::info('Signature verification parameters prepared', [
                'http_method' => $httpMethod,
                'path' => $path,
                'body_length' => strlen($body),
                'client_id' => $clientId,
                'response_time' => $responseTime
            ]);
            
            // 使用 Antom SDK 的签名工具验证
            $publicKey = $this->config_values->antom_public_key ?? '';

            $signature = $this->getResponseSignature($signature);
            
            Log::info('Calling SignatureTool::verify', [
                'public_key_present' => !empty($publicKey),
                'extracted_signature_preview' => $signature ? substr($signature, 0, 50) . '...' : null
            ]);

            $verifyResult = SignatureTool::verify(
                $httpMethod,
                $path,
                $clientId,
                $responseTime,
                $body,
                $signature,
                $publicKey
            );

            Log::info('Signature verification completed', [
                'verify_result' => $verifyResult,
                'is_valid' => $verifyResult === 1
            ]);
            
            return $verifyResult === 1;
            
        } catch (\Exception $e) {
            Log::error('Signature verification exception', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 从通知数据中提取支付ID
     */
    private function extractPaymentId($notification_data)
    {
        Log::info('Extracting payment ID from notification data', [
            'method' => 'extractPaymentId',
            'available_keys' => array_keys($notification_data)
        ]);
        
        $extracted_id = null;
        
        // 根据实际Antom通知结构提取支付ID
        if (isset($notification_data['paymentRequestId'])) {
            // 从paymentRequestId中提取原始支付ID
            $parts = explode('_', $notification_data['paymentRequestId']);
            $extracted_id = $parts[1] ?? null;
            
            Log::info('Payment ID extracted from paymentRequestId', [
                'payment_request_id' => $notification_data['paymentRequestId'],
                'parts' => $parts,
                'extracted_id' => $extracted_id
            ]);
        } elseif (isset($notification_data['referenceOrderId'])) {
            $extracted_id = $notification_data['referenceOrderId'];
            
            Log::info('Payment ID extracted from referenceOrderId', [
                'reference_order_id' => $notification_data['referenceOrderId'],
                'extracted_id' => $extracted_id
            ]);
        }
        
        if (!$extracted_id) {
            Log::warning('Failed to extract payment ID from notification data', [
                'notification_data_keys' => array_keys($notification_data),
                'payment_request_id' => $notification_data['paymentRequestId'] ?? 'not_found',
                'reference_order_id' => $notification_data['referenceOrderId'] ?? 'not_found'
            ]);
        }
        
        return $extracted_id;
    }

    /**
     * 判断支付是否成功
     * 根据新的通知数据结构修改验证逻辑
     */
    private function isPaymentSuccess($notification_data)
    {
        Log::info('Checking if payment is successful', [
            'method' => 'isPaymentSuccess',
            'result_data' => $notification_data['result'] ?? 'not_found'
        ]);
        
        // 检查 result 对象中的 resultStatus 字段
        if (isset($notification_data['result']) && is_array($notification_data['result'])) {
            $resultStatus = $notification_data['result']['resultStatus'] ?? null;
            
            Log::info('Payment success check result', [
                'result_status' => $resultStatus,
                'is_success' => $resultStatus === 'S',
                'result_code' => $notification_data['result']['resultCode'] ?? 'not_found',
                'result_message' => $notification_data['result']['resultMessage'] ?? 'not_found'
            ]);
            
            // S 表示成功，F 表示失败
            return $resultStatus === 'S';
        }
        
        Log::warning('Payment success check failed: invalid result structure', [
            'result_exists' => isset($notification_data['result']),
            'result_is_array' => isset($notification_data['result']) ? is_array($notification_data['result']) : 'not_set'
        ]);
        
        return false;
    }

    private function getResponseSignature($headerItem)
    {
        Log::info('Extracting signature from header', [
            'method' => 'getResponseSignature',
            'header_item_preview' => substr($headerItem, 0, 100) . '...'
        ]);
        
        if (strstr($headerItem, "signature")) {
            $startIndex = strrpos($headerItem, "=") + 1;
            $signatureValue = substr($headerItem, $startIndex);
            
            Log::info('Signature extracted successfully', [
                'start_index' => $startIndex,
                'signature_preview' => substr($signatureValue, 0, 50) . '...'
            ]);
            
            return $signatureValue;
        }
        
        Log::warning('Failed to extract signature from header', [
            'header_item' => $headerItem
        ]);
        
        return null;
    }
}