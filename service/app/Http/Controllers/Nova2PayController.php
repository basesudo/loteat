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

class Nova2PayController extends Controller
{
    use Processor;

    private $config_values;
    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('nova2pay', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
    }

    /**
     * 创建支付令牌并返回支付页面
     */
    public function payment(Request $request)
    {
        try {
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

            if ($request->isMethod('post')) {
                return $this->processPayment($request, $payment_data);
            }

            return $this->renderPaymentForm($payment_data);

        } catch (\Exception $e) {
            Log::error('Nova2Pay processing failed', [
                'error' => $e->getMessage(),
                'payment_id' => $request['payment_id'] ?? 'unknown',
            ]);
            
            return $this->renderErrorPage(translate('Payment processing failed. Please try again later or contact support.'));
        }
    }

    /**
     * 渲染支付表单页面
     */
    private function renderPaymentForm($payment_data)
    {
        return view('nova2pay.payment-form', [
            'type' => 'payment-form',
            'paymentData' => $payment_data,
            'orderId' => $this->generateMerOrderId($payment_data), // 新增
            'title' => translate('Complete Payment')
        ]);
    }

    /**
     * 处理支付请求
     */
    private function processPayment(Request $request, $payment_data)
    {
        $validator = Validator::make($request->all(), [
            'cardNo' => 'required|string|min:13|max:19',
            'expDate' => 'required|string|size:4|regex:/^\d{4}$/',
            'cvv' => 'required|string|size:3|regex:/^\d{3}$/',
            'firstName' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'countryCode' => 'required|string',
            'phoneNo' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'stateOrProvince' => 'required|string|max:50',
            'city' => 'required|string|max:50',
            'houseNumberOrName' => 'required|string|max:100',
            'street' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            $errors = implode('<br>', $validator->errors()->all());
            return $this->renderErrorPage(translate('Please correct the following errors:') . '<br>' . $errors);
        }

        $card_info = [
            'cardNo' => $request->input('cardNo'),
            'expDate' => $request->input('expDate'),
            'cvv' => $request->input('cvv'),
            'firstName' => $request->input('firstName'),
            'lastName' => $request->input('lastName'),
            'email' => $request->input('email'),
            'countryCode' => $request->input('countryCode'),
            'phoneNo' => $request->input('phoneNo'),
            'country' => $request->input('country'),
            'stateOrProvince' => $request->input('stateOrProvince'),
            'city' => $request->input('city'),
            'houseNumberOrName' => $request->input('houseNumberOrName'),
            'street' => $request->input('street'),
            'zip' => $request->input('zip'),
        ];

        $token_result = $this->createPaymentToken($payment_data, $card_info);
        
        if (!$token_result['success']) {
            return $this->renderErrorPage(translate('Unable to initialize payment. Please try again or contact support.') . ' (' . $token_result['message'] . ')');
        }

        return $this->renderPaymentPage($token_result['data'], $card_info, $payment_data);
    }

    /**
     * 渲染错误页面
     */
    private function renderErrorPage($message, $showRetry = true)
    {
        return view('nova2pay.payment-form', [
            'type' => 'error',
            'message' => $message,
            'showRetry' => $showRetry,
            'title' => translate('Payment Error')
        ]);
    }

    /**
     * 生成订单ID前缀
     */
    private function generateMerOrderId($payment_data)
    {
        if ($payment_data->attribute === 'order') {
            return 'ORDER-' . $payment_data->attribute_id;
        } elseif ($payment_data->attribute === 'wallet_payments') {
            return 'WALLET-' . $payment_data->attribute_id;
        }
        return 'PAY-' . $payment_data->attribute_id;
    }

    /**
     * 创建支付令牌
     */
    private function createPaymentToken($payment_data, $card_info)
    {
        try {
            $merOrderId = $this->generateMerOrderId($payment_data);
            $merTradeId = 'TRADE-' . uniqid();

            $requestData = [
                "accountId" => $this->config_values->accountId,
                "merOrderId" => $merOrderId,
                "merTradeId" => $merTradeId,
                "shopperUrl" => route('nova2pay.return', ['payment_id' => $payment_data->id]),
                "notifyUrl" => route('nova2pay.notify', ['payment_id' => $payment_data->id]),
                "amount" => [
                    "currency" => "USD",
                    "value" => number_format($payment_data->payment_amount, 2, '.', '')
                ],
                "version" => "2.2",
                "billingAddress" => [
                    "country" => strtoupper($card_info['country']),
                    "firstName" => $card_info['firstName'],
                    "lastName" => $card_info['lastName'],
                    "stateOrProvince" => $card_info['stateOrProvince'],
                    "city" => $card_info['city'],
                    "phone" => $card_info['countryCode'] . $card_info['phoneNo'],
                    "houseNumberOrName" => $card_info['houseNumberOrName'],
                    "street" => $card_info['street'],
                    "postalCode" => $card_info['zip'],
                    "email" => $card_info['email']
                ],
                "deliveryAddress" => [
                    "country" => strtoupper($card_info['country']),
                    "firstName" => $card_info['firstName'],
                    "lastName" => $card_info['lastName'],
                    "stateOrProvince" => $card_info['stateOrProvince'],
                    "city" => $card_info['city'],
                    "phone" => $card_info['countryCode'] . $card_info['phoneNo'],
                    "houseNumberOrName" => $card_info['houseNumberOrName'],
                    "street" => $card_info['street'],
                    "postalCode" => $card_info['zip'],
                    "email" => $card_info['email']
                ],
                "mddData" => [
                    "merchantDefinedData_mddField_19" => "admin.loteat.com",
                ]
            ];

            $requestData['tf_sign'] = $this->generateMd5Sign($requestData);

            $response = $this->makeApiRequest('/payment-order/api/transaction/session/pay', $requestData);

            Log::info('Nova2Pay create token', ['request' => $requestData, 'response' => $response]);

            if (!$response || !isset($response['resultCode']) || $response['resultCode'] !== '10000') {
                $error_message = isset($response['resultMessage']) ? $response['resultMessage'] : 'Create token failed';
                return ['success' => false, 'message' => $error_message];
            }

            return ['success' => true, 'data' => $response];

        } catch (\Exception $e) {
            Log::error('Nova2Pay create token failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment_data->id
            ]);
            
            return ['success' => false, 'message' => 'Create token processing failed'];
        }
    }

    /**
     * 渲染支付页面
     */
    private function renderPaymentPage($tokenData, $cardInfo, $paymentData)
    {
        $expDate = $cardInfo['expDate'];
        $expiryYear = '20' . substr($expDate, 2, 2);
        $expiryMonth = substr($expDate, 0, 2);
        
        $paymentConfigBase = [
            'aToken' => $tokenData['aToken'],
            'oId' => $tokenData['oId'],
            'firstName' => $cardInfo['firstName'],
            'lastName' => $cardInfo['lastName'],
            'cvc' => $cardInfo['cvv'],
            'number' => $cardInfo['cardNo'],
            'expiryMonth' => $expiryMonth,
            'expiryYear' => $expiryYear,
            'jsUrl' => $this->getApiBaseUrl()
        ];

        if (isset($tokenData['jsTranType']) && !empty($tokenData['jsTranType'])) {
            $paymentConfigBase['tranType'] = $tokenData['jsTranType'];
        }

        return view('nova2pay.payment-form', [
            'type' => 'processing',
            'paymentData' => $paymentData,
            'paymentConfig' => $paymentConfigBase,
            'jsUrl' => $this->getJsSecureUrl(),
            'title' => translate('Processing Payment')
        ]);
    }

    /**
     * 支付成功回调（同步通知）
     */
    public function success(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|uuid'
            ]);

            if ($validator->fails()) {
                return $this->renderErrorPage(translate('Invalid payment verification request'));
            }

            $responseData = $request->all();
            $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();

            if (!$payment_data) {
                return $this->renderErrorPage(translate('Payment record not found'));
            }

            $isSuccess = false;

            if (!empty($responseData) && isset($responseData['tf_sign'])) {
                $signString = $this->getSignString($responseData);
                $isValid = $this->checkSign($responseData['tf_sign'], $signString);
                
                if ($isValid && isset($responseData['tradeStatus']) && $responseData['tradeStatus'] === 'Approved') {
                    $isSuccess = true;
                }
            }

            return $this->payment_response($payment_data, $isSuccess ? 'success' : 'fail');

        } catch (\Exception $e) {
            Log::error('Nova2Pay success callback failed', [
                'error' => $e->getMessage(),
                'payment_id' => $request['payment_id'] ?? 'unknown'
            ]);
            
            return $this->renderErrorPage(translate('Payment verification failed. Please contact support.'));
        }
    }

    /**
     * 从 merOrderId 中提取支付ID
     */
    private function extractPaymentIdFromMerOrderId($merOrderId)
    {
        if (strpos($merOrderId, 'ORDER-') === 0) {
            return str_replace('ORDER-', '', $merOrderId);
        } elseif (strpos($merOrderId, 'WALLET-') === 0) {
            return str_replace('WALLET-', '', $merOrderId);
        } elseif (strpos($merOrderId, 'PAY-') === 0) {
            return str_replace('PAY-', '', $merOrderId);
        }
        return $merOrderId;
    }

    /**
     * 支付通知回调（异步通知）
     */
    public function notify(Request $request)
    {
        try {
            $responseData = $request->all();
            
            Log::info('Nova2Pay async notify received', $responseData);

            if (!empty($responseData) && isset($responseData['tf_sign'])) {
                $signString = $this->getSignString($responseData);
                $isValid = $this->checkSign($responseData['tf_sign'], $signString);
                
                if ($isValid) {
                    if (isset($responseData['tradeStatus']) && $responseData['tradeStatus'] === 'Approved') {
                        $attributeId = $this->extractPaymentIdFromMerOrderId($responseData['merOrderId'] ?? '');
                        
                        if ($attributeId) {
                            DB::beginTransaction();
                            try {
                                $affected = $this->payment::where(['attribute_id' => $attributeId])
                                    ->where('is_paid', 0)
                                    ->update([
                                        'payment_method' => 'nova2pay',
                                        'is_paid' => 1,
                                        'transaction_id' => $responseData['tradeId'] ?? $attributeId,
                                        'updated_at' => now()
                                    ]);

                                if ($affected > 0) {
                                    $updated_payment_data = $this->payment::where(['attribute_id' => $attributeId])->first();
                                    if ($updated_payment_data && function_exists($updated_payment_data->success_hook)) {
                                        call_user_func($updated_payment_data->success_hook, $updated_payment_data);
                                    }
                                }

                                DB::commit();
                                
                                Log::info('Nova2Pay notification processed successfully', [
                                    'merOrderId' => $responseData['merOrderId'] ?? '',
                                    'attributeId' => $attributeId,
                                    'tradeId' => $responseData['tradeId'] ?? ''
                                ]);

                            } catch (\Exception $e) {
                                DB::rollBack();
                                throw $e;
                            }
                        }
                    }

                    return response('success');
                } else {
                    Log::error('Nova2Pay async notify: RSA signature failed');
                    return response('Signature verification failed', 400);
                }
            } else {
                Log::error('Nova2Pay async notify: Invalid request');
                return response('Invalid request', 400);
            }

        } catch (\Exception $e) {
            Log::error('Nova2Pay notification processing failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response('Processing failed', 500);
        }
    }

    /**
     * 递归排序数组
     */
    private function recursiveKsort(array &$array)
    {
        ksort($array, SORT_STRING);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKsort($value);
            }
        }
    }

    /**
     * 构建排序查询字符串
     */
    private function buildSortedQueryString(array $params): string
    {
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null && $value !== [];
        });

        $this->recursiveKsort($params);
        
        $pairs = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $pairs[] = $key . '=' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $pairs[] = $key . '=' . $value;
            }
        }
        return implode('&', $pairs);
    }

    /**
     * 生成MD5签名
     */
    private function generateMd5Sign(array $params): string
    {
        $params['md5Key'] = $this->config_values->md5Key;
        $stringA = $this->buildSortedQueryString($params);
        
        Log::info("Nova2Pay MD5 sign string", ['string' => $stringA]);
        
        return strtoupper(md5($stringA));
    }

    /**
     * 十六进制转字符串
     */
    private function hex2str($hex)
    {
        $string = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $string;
    }

    /**
     * RSA签名验证
     */
    private function checkSign($sign, $toSign, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $publicKeyId = openssl_pkey_get_public($this->config_values->rsaPublicKey);
        if ($publicKeyId === false) {
            return false;
        }
        
        $result = openssl_verify($toSign, base64_decode($this->hex2str($sign)), $publicKeyId, $signature_alg);
        
        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($publicKeyId);
        }
        
        return $result === 1;
    }

    /**
     * 获取签名字符串
     */
    private function getSignString($params)
    {
        unset($params['tf_sign']);
        unset($params['action']);
        unset($params['payment_id']);
        
        ksort($params);
        reset($params);

        $pairs = array();
        foreach ($params as $k => $v) {
            if (!empty($v)) {
                $pairs[] = "$k=$v";
            }
        }

        return implode('&', $pairs);
    }

    /**
     * 发送API请求
     */
    private function makeApiRequest(string $endpoint, array $data): array
    {
        $url = $this->getApiBaseUrl() . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('Nova2Pay API request failed', ['error' => $error, 'url' => $url]);
            return ['resultCode' => 'CURL_ERROR', 'resultMessage' => $error];
        }

        $decoded = json_decode($response, true);
        return $decoded ?? ['resultCode' => 'INVALID_JSON', 'resultMessage' => 'Failed to decode JSON response.'];
    }

    /**
     * 获取API基础URL
     */
    private function getApiBaseUrl(): string
    {
        return $this->config_values->mode === 'test' 
            ? 'https://n2p-api.nova2pay.com' 
            : 'https://n2p-api.test.nova2pay.com';
    }

    /**
     * 获取JS安全URL
     */
    private function getJsSecureUrl(): string
    {
        return $this->config_values->mode === 'live' 
            ? 'https://n2p-secure.nova2pay.com' 
            : 'https://n2p-secure.test.nova2pay.com';
    }

    /**
     * 支付取消
     */
    public function cancel(Request $request)
    {
        $data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($data, 'cancel');
    }
}