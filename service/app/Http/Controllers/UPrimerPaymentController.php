<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Traits\Processor;
use App\Models\PaymentRequest;

class UPrimerPaymentController extends Controller
{
    use Processor;

    private $config_values;
    private $base_url;
    private $token;
    private PaymentRequest $payment;

    // API URI 常量
    const URI_TOKEN_AUTH = "/authorize";
    const URI_PAYMENT_CREATE = "/api/acquire/payment/create";
    const URI_PAYMENT_REFUND = "/api/acquire/payment/{originalId}/refund";
    const URI_TRANSACTION_QUERY = "/api/acquire/payment/{originalId}/get";

    public function __construct(PaymentRequest $payment)
    {
        Log::info('UPrimerPaymentController: Constructor called');
        
        $config = $this->payment_config('uprimer', 'payment_config');
        Log::info('UPrimerPaymentController: Payment config retrieved', ['config_exists' => !is_null($config)]);
        
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
            $this->base_url = "https://acquire.uprimer.com";
            Log::info('UPrimerPaymentController: Live mode configured', ['base_url' => $this->base_url]);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
            $this->base_url = "https://uatacquire.cloudpnr.com";
            Log::info('UPrimerPaymentController: Test mode configured', ['base_url' => $this->base_url]);
        } else {
            Log::warning('UPrimerPaymentController: No valid configuration found');
        }

        $this->payment = $payment;
        
        // 获取或刷新 token
        if ($this->config_values) {
            Log::info('UPrimerPaymentController: Attempting to get token');
            $this->getToken();
        } else {
            Log::error('UPrimerPaymentController: No config values available for token acquisition');
        }
        
        Log::info('UPrimerPaymentController: Constructor completed', ['token_available' => !empty($this->token)]);
    }

    /**
     * 获取访问token
     */
    private function getToken()
    {
        Log::info('getToken: Starting token acquisition process');
        
        $cache_key = 'uprimer_token_' . ($this->config_values->mode ?? 'test');
        Log::info('getToken: Cache key generated', ['cache_key' => $cache_key]);
        
        // 尝试从缓存获取token
        $cached_token = Cache::get($cache_key);
        Log::info('getToken: Cache lookup result', ['has_cached_token' => !empty($cached_token)]);
        
        if ($cached_token && isset($cached_token['token']) && isset($cached_token['expire_time'])) {
            Log::info('getToken: Cached token found, checking expiration', [
                'expire_time' => $cached_token['expire_time'],
                'current_time' => time(),
                'is_expired' => $cached_token['expire_time'] <= time()
            ]);
            
            if ($cached_token['expire_time'] > time()) {
                $this->token = $cached_token['token'];
                Log::info('getToken: Using cached token', ['token_length' => strlen($this->token)]);
                return $this->token;
            } else {
                Log::info('getToken: Cached token expired, will fetch new one');
            }
        } else {
            Log::info('getToken: No valid cached token found');
        }

        try {
            $url = $this->base_url . self::URI_TOKEN_AUTH;
            Log::info('getToken: Making token request', ['url' => $url]);
            
            $response = $this->httpGet($url, true); // true 表示获取token请求
            Log::info('getToken: Token request response received', ['response_length' => strlen($response)]);
            
            $response_data = json_decode($response, true);
            Log::info('getToken: Token response parsed', ['response_data' => $response_data]);

            if (isset($response_data['code']) && $response_data['code'] == "00000000") {
                $this->token = $response_data['data']['token'];
                $expire_in = $response_data['data']['expireIn'] ?? 300000;
                
                Log::info('getToken: Token obtained successfully', [
                    'token_length' => strlen($this->token),
                    'expire_in' => $expire_in
                ]);
                
                // 缓存token，提前30秒过期以避免边界情况
                $cache_expire_time = time() + $expire_in - 30;
                Cache::put($cache_key, [
                    'token' => $this->token,
                    'expire_time' => $cache_expire_time
                ], $expire_in - 30);

                Log::info('getToken: Token cached successfully', [
                    'cache_key' => $cache_key,
                    'cache_expire_time' => $cache_expire_time
                ]);

                return $this->token;
            } else {
                Log::error('getToken: Token request failed', ['response_data' => $response_data]);
                throw new \Exception(translate('messages.failed_to_obtain_access_token'));
            }

        } catch (\Exception $e) {
            Log::error('getToken: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 显示支付表单页面
     */
    public function payment(Request $request)
    {
        
        Log::info('payment: Payment form requested', ['request_data' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            Log::error('payment: Validation failed', ['errors' => $validator->errors()]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        Log::info('payment: Validation passed, searching for payment', ['payment_id' => $request['payment_id']]);
        
        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            Log::warning('payment: Payment not found or already paid', ['payment_id' => $request['payment_id']]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        Log::info('payment: Payment found', [
            'payment_id' => $data->id,
            'amount' => $data->payment_amount,
            'is_paid' => $data->is_paid
        ]);

        if (!$this->config_values || !$this->token) {
            Log::error('payment: Configuration or token missing', [
                'has_config' => !empty($this->config_values),
                'has_token' => !empty($this->token)
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, [['error_code' => 'config_error', 'message' => translate('messages.payment_gateway_not_configured')]]), 400);
        }

        // 准备视图数据
        $view_data = [
            'payment_data' => $data,
            'payment_amount' => number_format($data->payment_amount, 2),
            'currency' => 'USD',
            'is_wallet_payment' => isset($data->attribute) && $data->attribute === 'wallet_payments',
            'submit_url' => route('uprimer.process', ['payment_id' => $data->id]),
            'cancel_url' => route('uprimer.cancel', ['payment_id' => $data->id]),
            'config' => [
                'mode' => $this->config_values->mode ?? 'test',
                'app_id' => $this->config_values->app_id ?? 'fd001'
            ]
        ];

        Log::info('payment: Returning payment form view', [
            'payment_id' => $data->id,
            'amount' => $view_data['payment_amount'],
            'is_wallet_payment' => $view_data['is_wallet_payment']
        ]);

        return view('uprimer.payment_form', $view_data);
    }

    /**
     * 处理支付表单提交
     */
    public function processPayment(Request $request)
    {
        Log::info('processPayment: Processing payment request', [
            'payment_id' => $request->input('payment_id'),
            'card_number_length' => strlen($request->input('card_number', '')),
            'has_billing_info' => !empty($request->input('billing_email'))
        ]);
        
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
            // 卡片信息
            'card_number' => 'required|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:2',
            'cvv' => 'required|string|min:3|max:4',
            'card_holder_first_name' => 'required|string|max:50',
            'card_holder_last_name' => 'required|string|max:50',
            // 账单地址
            'billing_email' => 'required|email',
            'billing_phone' => 'required|string|max:20',
            'billing_country_code' => 'required|string|size:2',
            'billing_state' => 'required|string|max:255',
            'billing_city' => 'required|string|max:255',
            'billing_street' => 'required|string|max:255',
            'billing_post_code' => 'required|string|max:20',
            // 设备数据
            'accept_header' => 'nullable|string',
            'browser_java_enabled' => 'nullable|boolean',
            'browser_javascript_enabled' => 'nullable|boolean',
            'browser_user_agent' => 'nullable|string',
            'challenge_window' => 'nullable|string',
            'language' => 'nullable|string',
            'screen_color_depth' => 'nullable|string',
            'screen_height' => 'nullable|string',
            'screen_width' => 'nullable|string',
            'timezone' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::error('processPayment: Validation failed', ['errors' => $validator->errors()]);
            echo translate('messages.validation_failed');
            return back()->withErrors($validator)->withInput();
        }

        Log::info('processPayment: Validation passed, searching for payment');
        
        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            Log::warning('processPayment: Payment not found or already paid', ['payment_id' => $request['payment_id']]);
            return back()->with('error', translate('messages.payment_order_not_exist_or_paid'));
        }

        Log::info('processPayment: Payment found', [
            'payment_id' => $data->id,
            'amount' => $data->payment_amount
        ]);

        if (!$this->config_values || !$this->token) {
            Log::error('processPayment: Configuration or token missing', [
                'has_config' => !empty($this->config_values),
                'has_token' => !empty($this->token)
            ]);
            return back()->with('error', translate('messages.payment_gateway_configuration_error'));
        }

        try {
            Log::info('processPayment: Calling createPayment method');
            return $this->createPayment($data, $request);
        } catch (\Exception $e) {
            Log::error('processPayment: Exception occurred', [
                'payment_id' => $data->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', $e->getMessage() ?: translate('messages.payment_failed_please_try_again'));
        }
    }

    /**
     * 创建支付
     */
    private function createPayment($payment_data, Request $request)
    {
        Log::info('createPayment: Starting payment creation', ['payment_id' => $payment_data->id]);
        
        $order_time = date('Y-m-d\TH:i:sO');
        
        // 检查是否为wallet_payments
        $is_wallet_payment = isset($payment_data->attribute) && $payment_data->attribute === 'wallet_payments';
        $prefix = $is_wallet_payment ? 'WALLET' : 'ORDER';
        $merchant_order_id = $prefix . '-' . $payment_data->id . '-' . time();

        Log::info('createPayment: Payment details prepared', [
            'is_wallet_payment' => $is_wallet_payment,
            'merchant_order_id' => $merchant_order_id,
            'order_time' => $order_time,
            'amount' => $payment_data->payment_amount
        ]);

        $billing_first_name = $request->input('card_holder_first_name');
        $billing_last_name = $request->input('card_holder_last_name');

        $request_data = [
            "amount" => (int)($payment_data->payment_amount * 100),
            "appId" => $this->config_values->app_id ?? "fd001",
            "currency" => "USD",
            "descriptor" => translate('messages.payment_for_order') . " #" . $payment_data->id,
            "merchantOrderId" => $merchant_order_id,
            "requestId" => 'REQ-' . $payment_data->id . '-' . time(),
            "cancelUrl" => route('uprimer.cancel', ['payment_id' => $payment_data->id]),
            "successUrl" => route('uprimer.success', ['payment_id' => $payment_data->id]),
            "failureUrl" => route('uprimer.cancel', ['payment_id' => $payment_data->id]),
            "notificationUrl" => route('uprimer.notify', ['payment_id' => $payment_data->id]),
            "orderTime" => $order_time,
            "paymentMethod" => [
                "methodType" => "CARD",
                "card" => [
                    "cvv" => $request->input('cvv'),
                    "expiryMonth" => $request->input('expiry_month'),
                    "expiryYear" => $request->input('expiry_year'),
                    "firstName" => $request->input('card_holder_first_name'),
                    "lastName" => $request->input('card_holder_last_name'),
                    "number" => $request->input('card_number'),
                    "billing" => [
                        "firstName" => $billing_first_name,
                        "lastName" => $billing_last_name,
                        "phoneNumber" => $request->input('billing_phone'),
                        "email" => $request->input('billing_email'),
                        "countryCode" => $request->input('billing_country_code'),
                        "state" => $request->input('billing_state'),
                        "city" => $request->input('billing_city'),
                        "street" => $request->input('billing_street'),
                        "postCode" => $request->input('billing_post_code')
                    ]
                ]
            ],
            "products" => [
                [
                    "code" => "PROD-" . $payment_data->id,
                    "name" => translate('messages.order_payment') . " #" . $payment_data->id,
                    "quantity" => 1,
                    "sku" => "ORDER-SKU-" . $payment_data->id,
                    "unitPrice" => (int)($payment_data->payment_amount * 100),
                    "totalAmount" => (int)($payment_data->payment_amount * 100)
                ]
            ],
            "deviceData" => [
                "acceptHeader" => $request->input('accept_header', 'text/html'),
                "browserJavaEnabled" => $request->input('browser_java_enabled', true) ? "true" : "false",
                "browserJavascriptEnabled" => $request->input('browser_javascript_enabled', true) ? "true" : "false",
                "browserUserAgent" => $request->input('browser_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
                "challengeWindow" => $request->input('challenge_window', '5'),
                "language" => $request->input('language', 'en-US'),
                "screenColorDepth" => $request->input('screen_color_depth', '24'),
                "screenHeight" => $request->input('screen_height', '1080'),
                "screenWidth" => $request->input('screen_width', '1920'),
                "timezone" => $request->input('timezone', '0')
            ]
        ];

        // 对敏感信息进行脱敏记录
        $log_request_data = $request_data;
        if (isset($log_request_data['paymentMethod']['card']['number'])) {
            $log_request_data['paymentMethod']['card']['number'] = '**** **** **** ' . substr($request_data['paymentMethod']['card']['number'], -4);
        }
        if (isset($log_request_data['paymentMethod']['card']['cvv'])) {
            $log_request_data['paymentMethod']['card']['cvv'] = '***';
        }

        Log::info('createPayment: Request data prepared', [
            'payment_id' => $payment_data->id,
            'request_data' => $log_request_data
        ]);

        $url = $this->base_url . self::URI_PAYMENT_CREATE;
        Log::info('createPayment: Making payment request', ['url' => $url]);
        
        $response = $this->httpPost($url, $request_data);
        Log::info('createPayment: Payment response received', ['response_length' => strlen($response)]);
        
        $response_data = json_decode($response, true);

        Log::info('createPayment: Payment response parsed', [
            'payment_id' => $payment_data->id,
            'is_wallet_payment' => $is_wallet_payment,
            'response_code' => $response_data['code'] ?? 'unknown',
            'response' => $response_data
        ]);

        if (isset($response_data['code']) && $response_data['code'] == "00000000") {
            Log::info('createPayment: Payment request successful, updating payment record');
            
            // 更新支付记录
            $updated = $this->payment::where(['id' => $payment_data->id])->update([
                'payment_method' => 'uprimer',
                'is_paid' => 0,
                'transaction_id' => $merchant_order_id,
            ]);

            Log::info('createPayment: Payment record updated', [
                'payment_id' => $payment_data->id,
                'updated_rows' => $updated,
                'transaction_id' => $merchant_order_id
            ]);

            // 检查是否需要3DS认证
            if (isset($response_data['data']['redirectUrl'])) {
                Log::info('createPayment: 3DS authentication required, redirecting', [
                    'payment_id' => $payment_data->id,
                    'redirect_url' => $response_data['data']['redirectUrl']
                ]);
                return Redirect::away($response_data['data']['redirectUrl']);
            } elseif (
                isset($response_data['data']['nextAction']['actionType']) &&
                strtoupper($response_data['data']['nextAction']['actionType']) === 'REDIRECT' &&
                !empty($response_data['data']['nextAction']['url'])
            ) {
                $next_action = $response_data['data']['nextAction'];
                $next_method = strtoupper($next_action['method'] ?? 'GET');

                Log::info('createPayment: Handling nextAction redirect', [
                    'payment_id' => $payment_data->id,
                    'next_action' => $next_action
                ]);

                if ($next_method === 'GET') {
                    return Redirect::away($next_action['url']);
                }

                Log::warning('createPayment: Unsupported nextAction method', [
                    'payment_id' => $payment_data->id,
                    'method' => $next_method
                ]);
                throw new \Exception(translate('messages.unsupported_next_action_method'));
            } else {
                Log::info('createPayment: Direct payment success, no 3DS required');
                // 直接支付成功
                return $this->payment_response($payment_data, 'success');
            }
        } else {
            $error_message = $response_data['msg'] ?? translate('messages.payment_failed');
            Log::error('createPayment: Payment request failed', [
                'payment_id' => $payment_data->id,
                'error_code' => $response_data['code'] ?? 'unknown',
                'error_message' => $error_message
            ]);
            throw new \Exception($error_message);
        }
    }

    /**
     * 支付成功回调
     */
    public function success(Request $request)
    {
        Log::info('success: Success callback called', ['request_data' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            Log::error('success: Validation failed', ['errors' => $validator->errors()]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        Log::info('success: Searching for payment', ['payment_id' => $request['payment_id']]);
        
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

        if (!$payment_data) {
            Log::warning('success: Payment not found', ['payment_id' => $request['payment_id']]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_404), 404);
        }

        Log::info('success: Payment found, returning response', [
            'payment_id' => $payment_data->id,
            'is_paid' => $payment_data->is_paid,
            'status' => $payment_data->is_paid ? 'success' : 'pending'
        ]);

        return $this->payment_response($payment_data, $payment_data->is_paid ? 'success' : 'pending');
    }

    /**
     * 支付取消回调
     */
    public function cancel(Request $request)
    {
        Log::info('cancel: Cancel callback called', ['request_data' => $request->all()]);
        
        $data = $this->payment::where(['id' => $request['payment_id']])->first();
        
        if ($data) {
            Log::info('cancel: Payment found for cancellation', ['payment_id' => $data->id]);
        } else {
            Log::warning('cancel: Payment not found for cancellation', ['payment_id' => $request['payment_id']]);
        }
        
        return $this->payment_response($data, 'cancel');
    }

    /**
     * 支付通知回调
     */
    public function notify(Request $request)
    {
        Log::info('notify: Notification callback called', ['route_params' => $request->route()->parameters()]);
        
        try {
            $raw_body = file_get_contents("php://input");
            $signature = $request->header('X-Signature', '');
            $all_headers = $request->headers->all();

            Log::info('notify: Notification data received', [
                'body_length' => strlen($raw_body),
                'signature' => $signature,
                'headers' => $all_headers,
                'body' => $raw_body
            ]);

            // 验证签名
            Log::info('notify: Verifying notification signature');
            if (!$this->verifyNotificationSignature($raw_body, $signature)) {
                Log::warning('notify: Signature verification failed', [
                    'signature' => $signature,
                    'body_length' => strlen($raw_body)
                ]);
                return response(translate('messages.signature_verification_failed'), 400);
            }

            Log::info('notify: Signature verification passed');

            $notification_data = json_decode($raw_body, true);
            if (!$notification_data) {
                Log::error('notify: Failed to parse notification data', ['raw_body' => $raw_body]);
                return response(translate('messages.invalid_notification_data'), 400);
            }

            Log::info('notify: Notification data parsed', ['notification_data' => $notification_data]);

            $payment_id = $request->route('payment_id');
            if (!$payment_id) {
                Log::error('notify: Payment ID not found in route');
                return response(translate('messages.payment_id_not_found'), 400);
            }

            Log::info('notify: Payment ID extracted from route', ['payment_id' => $payment_id]);

            // 查找支付记录
            $payment_data = $this->payment::where(['id' => $payment_id])->first();
            if (!$payment_data) {
                Log::error('notify: Payment record not found', ['payment_id' => $payment_id]);
                return response(translate('messages.payment_record_not_found'), 404);
            }

            Log::info('notify: Payment record found', [
                'payment_id' => $payment_data->id,
                'is_paid' => $payment_data->is_paid,
                'amount' => $payment_data->payment_amount
            ]);

            // 使用数据库事务处理
            DB::beginTransaction();
            Log::info('notify: Database transaction started');
            
            try {
                $payment_record = $this->payment::where(['id' => $payment_id])
                    ->lockForUpdate()
                    ->first();

                Log::info('notify: Payment record locked for update', [
                    'payment_id' => $payment_id,
                    'current_is_paid' => $payment_record->is_paid
                ]);

                if ($payment_record->is_paid == 1) {
                    Log::info('notify: Payment already processed, returning success');
                    DB::commit();
                    return response('success');
                }

                // 检查支付状态
                $is_success = $this->isPaymentSuccess($notification_data);
                Log::info('notify: Payment status checked', [
                    'is_success' => $is_success,
                    'notification_status' => $notification_data['status'] ?? 'unknown'
                ]);

                if ($is_success) {
                    Log::info('notify: Updating payment record to paid status');
                    
                    $affected = $this->payment::where(['id' => $payment_id])
                        ->where('is_paid', 0)
                        ->update([
                            'payment_method' => 'uprimer',
                            'is_paid' => 1,
                            'transaction_id' => $notification_data['id'] ?? $payment_record->transaction_id,
                            'updated_at' => now()
                        ]);

                    Log::info('notify: Payment record update result', [
                        'affected_rows' => $affected,
                        'transaction_id' => $notification_data['id'] ?? $payment_record->transaction_id
                    ]);

                    if ($affected === 0) {
                        Log::warning('notify: No rows affected by update, payment may have been processed already');
                        DB::commit();
                        return response('success');
                    }

                    $updated_payment_data = $this->payment::where(['id' => $payment_id])->first();
                    DB::commit();
                    Log::info('notify: Database transaction committed successfully');

                    if (isset($updated_payment_data) && function_exists($updated_payment_data->success_hook)) {
                        Log::info('notify: Calling success hook');
                        call_user_func($updated_payment_data->success_hook, $updated_payment_data);
                    } else {
                        Log::info('notify: No success hook to call');
                    }

                    Log::info('notify: Payment notification processed successfully', [
                        'payment_id' => $payment_id,
                        'transaction_id' => $notification_data['id'] ?? ''
                    ]);

                    return response('success');
                } else {
                    Log::warning('notify: Payment failed according to notification status');
                    DB::rollBack();
                    
                    if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
                        Log::info('notify: Calling failure hook');
                        call_user_func($payment_data->failure_hook, $payment_data);
                    } else {
                        Log::info('notify: No failure hook to call');
                    }
                    return response(translate('messages.payment_failed'));
                }

            } catch (\Exception $e) {
                Log::error('notify: Exception in transaction block', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('notify: Exception in notification processing', [
                'error' => $e->getMessage(),
                'payment_id' => $payment_id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response(translate('messages.processing_failed'), 500);
        }
    }

    /**
     * 验证通知签名
     */
    private function verifyNotificationSignature($body, $signature)
    {
        Log::info('verifyNotificationSignature: Starting signature verification', [
            'body_length' => strlen($body),
            'signature' => $signature
        ]);
        
        if (!$this->config_values || !isset($this->config_values->secret_key)) {
            Log::error('verifyNotificationSignature: Secret key not configured');
            return false;
        }

        $expected_signature = md5($body . $this->config_values->secret_key);
        Log::info('verifyNotificationSignature: Signature comparison', [
            'expected_signature' => $expected_signature,
            'received_signature' => $signature,
            'matches' => hash_equals($signature, $expected_signature)
        ]);
        
        return hash_equals($signature, $expected_signature);
    }

    /**
     * 检查支付是否成功
     */
    private function isPaymentSuccess($notification_data)
    {
        $error_code = $notification_data['errorCode'] ?? '';
        $status = $notification_data['status'] ?? '';
        
        // 支付成功的条件：errorCode == "00000000" 且 status == "SUCCEED"
        $is_success = ($error_code === '00000000' && $status === 'SUCCEED');
        
        Log::info('isPaymentSuccess: Checking payment success status', [
            'error_code' => $error_code,
            'status' => $status,
            'is_success' => $is_success
        ]);
        
        return $is_success;
    }

    /**
     * 查询支付状态
     */
    public function queryPayment(Request $request)
    {
        Log::info('queryPayment: Payment query requested', ['request_data' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            Log::error('queryPayment: Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (!$payment_data) {
            Log::warning('queryPayment: Payment not found', ['payment_id' => $request['payment_id']]);
            return response()->json([
                'success' => false,
                'message' => translate('messages.payment_not_found')
            ], 404);
        }

        Log::info('queryPayment: Payment found, making query request', [
            'payment_id' => $payment_data->id,
            'transaction_id' => $payment_data->transaction_id
        ]);

        try {
            $url = $this->base_url . str_replace('{originalId}', $payment_data->transaction_id, self::URI_TRANSACTION_QUERY);
            Log::info('queryPayment: Making query request', ['url' => $url]);
            
            $response = $this->httpGet($url);
            Log::info('queryPayment: Query response received', ['response_length' => strlen($response)]);
            
            $response_data = json_decode($response, true);
            Log::info('queryPayment: Query response parsed', ['response_data' => $response_data]);

            return response()->json([
                'success' => true,
                'data' => $response_data
            ]);

        } catch (\Exception $e) {
            Log::error('queryPayment: Query failed', [
                'payment_id' => $request['payment_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => translate('messages.query_failed')
            ], 500);
        }
    }

    /**
     * HTTP POST 请求
     */
    private function httpPost($url, $data)
    {
        Log::info('httpPost: Starting POST request', [
            'url' => $url,
            'data_size' => strlen(json_encode($data))
        ]);
        
        $ch = curl_init();
        $headers = $this->getHttpPostHeaders($data);
        
        Log::info('httpPost: Request headers prepared', ['headers' => $headers]);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        Log::info('httpPost: Executing cURL request');
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        Log::info('httpPost: cURL request completed', [
            'http_code' => $http_code,
            'response_length' => strlen($response),
            'has_error' => !empty($error),
            'curl_info' => $curl_info
        ]);

        if ($error) {
            Log::error('httpPost: cURL error occurred', ['error' => $error]);
            throw new \Exception(translate('messages.curl_error') . ": " . $error);
        }

        Log::info('httpPost: Request successful', ['response_length' => strlen($response)]);
        return $response;
    }

    /**
     * HTTP GET 请求
     */
    private function httpGet($url, $is_token_request = false)
    {
        Log::info('httpGet: Starting GET request', [
            'url' => $url,
            'is_token_request' => $is_token_request
        ]);
        
        $ch = curl_init();
        $headers = $is_token_request ? $this->getHttpGetTokenHeaders() : $this->getHttpGetHeaders();
        
        Log::info('httpGet: Request headers prepared', ['headers' => $headers]);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        Log::info('httpGet: Executing cURL request');
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        Log::info('httpGet: cURL request completed', [
            'http_code' => $http_code,
            'response_length' => strlen($response),
            'has_error' => !empty($error),
            'curl_info' => $curl_info
        ]);

        if ($error) {
            Log::error('httpGet: cURL error occurred', ['error' => $error]);
            throw new \Exception(translate('messages.curl_error') . ": " . $error);
        }

        Log::info('httpGet: Request successful', ['response_length' => strlen($response)]);
        return $response;
    }

    /**
     * 获取POST请求头
     */
    private function getHttpPostHeaders($data)
    {
        Log::info('getHttpPostHeaders: Generating POST headers');
        
        $signature = $this->generateSignature($data);
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->token,
            "X-AccessCode: " . ($this->config_values->access_code ?? ''),
            "X-Signature: " . $signature,
        ];
        
        Log::info('getHttpPostHeaders: Headers generated', [
            'has_token' => !empty($this->token),
            'has_access_code' => !empty($this->config_values->access_code),
            'signature' => $signature
        ]);
        
        return $headers;
    }

    /**
     * 获取GET请求头（用于获取token）
     */
    private function getHttpGetTokenHeaders()
    {
        Log::info('getHttpGetTokenHeaders: Generating token request headers');
        
        $headers = [
            "Content-Type: application/json",
            "X-AccessCode: " . ($this->config_values->access_code ?? ''),
            "X-SecretKey: " . ($this->config_values->secret_key ?? '')
        ];
        
        Log::info('getHttpGetTokenHeaders: Headers generated', [
            'has_access_code' => !empty($this->config_values->access_code),
            'has_secret_key' => !empty($this->config_values->secret_key)
        ]);
        
        return $headers;
    }

    /**
     * 获取GET请求头
     */
    private function getHttpGetHeaders()
    {
        Log::info('getHttpGetHeaders: Generating GET headers');
        
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->token,
            "X-AccessCode: " . ($this->config_values->access_code ?? ''),
            "X-SecretKey: " . ($this->config_values->secret_key ?? '')
        ];
        
        Log::info('getHttpGetHeaders: Headers generated', [
            'has_token' => !empty($this->token),
            'has_access_code' => !empty($this->config_values->access_code),
            'has_secret_key' => !empty($this->config_values->secret_key)
        ]);
        
        return $headers;
    }

    /**
     * 生成签名
     */
    private function generateSignature($data)
    {
        Log::info('generateSignature: Generating signature for data');
        
        $secret_key = $this->config_values->secret_key ?? '';
        $json_data = json_encode($data);
        $signature = md5($json_data . $secret_key);
        
        Log::info('generateSignature: Signature generated', [
            'data_length' => strlen($json_data),
            'has_secret_key' => !empty($secret_key),
            'signature' => $signature
        ]);
        
        return $signature;
    }
}