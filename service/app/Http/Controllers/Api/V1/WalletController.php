<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\ExternalConfiguration;
use App\Models\User;
use App\Models\WalletBonus;
use App\Models\WalletPayment;
use App\Models\WalletTransaction;
use App\Models\AgencyAddFundInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use App\Library\Payment as PaymentInfo;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function transactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $paginator = WalletTransaction::where('user_id', $request->user()->id)
            ->when($request['type'] && $request['type'] == 'order', function ($query) {
                $query->whereIn('transaction_type', ['order_place', 'order_refund', 'partial_payment']);
            })
            ->when($request['type'] && $request['type'] == 'loyalty_point', function ($query) {
                $query->whereIn('transaction_type', ['loyalty_point']);
            })
            ->when($request['type'] && $request['type'] == 'add_fund', function ($query) {
                $query->whereIn('transaction_type', ['add_fund']);
            })
            ->when($request['type'] && $request['type'] == 'referrer', function ($query) {
                $query->whereIn('transaction_type', ['referrer']);
            })
            ->when($request['type'] && $request['type'] == 'CashBack', function ($query) {
                $query->whereIn('transaction_type', ['CashBack']);
            })
            ->latest()->paginate($request->limit, ['*'], 'page', $request->offset);

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request->limit,
            'offset' => $request->offset,
            'data' => $paginator->items()
        ];
        return response()->json($data, 200);
    }

    public function add_fund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $digital_payment = Helpers::get_business_settings('digital_payment');
        if ($digital_payment['status'] == 0) {
            return response()->json(['errors' => ['message' => 'digital_payment_is_disable']], 403);
        }

        $customer = User::find($request->user()->id);

        if (!isset($customer)) {
            return response()->json(['errors' => ['message' => 'Customer not found']], 403);
        }

        // 创建新的钱包支付记录
        $wallet = new WalletPayment();
        $wallet->user_id = $customer->id;
        $wallet->amount = $request->amount;
        $wallet->payment_status = 'pending';
        $wallet->payment_method = $request->payment_method;
        
        // 如果是 agency_payment，添加 agency_id 并立即返回
        if ($request->payment_method == 'agency_payment') {
            $agency_id = (string) Str::uuid();
            $wallet->agency_id = $agency_id;
            $wallet->save();
            
            return response()->json([
                'agency_id' => $agency_id,
                'status' => 'success'
            ], 200);
        }
        
        $wallet->save();
        $wallet_amount = $request->amount;

        $payer = new Payer(
            $customer->f_name . ' ' . $customer->l_name,
            $customer->email,
            $customer->phone,
            ''
        );

        $currency = BusinessSetting::where(['key' => 'currency'])->first()->value;
        $store_logo = BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
            'business_logo' => \App\CentralLogics\Helpers::get_full_url('business', $store_logo?->value, $store_logo?->storage[0]?->value ?? 'public')
        ];
        $payment_info = new PaymentInfo(
            success_hook: 'wallet_success',
            failure_hook: 'wallet_failed',
            currency_code: $currency,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer->id,
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $wallet_amount,
            external_redirect_link: $request->has('callback') ? $request['callback'] : session('callback'),
            attribute: 'wallet_payments',
            attribute_id: $wallet->id
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        $data = [
            'redirect_link' => $redirect_link,
        ];
        return response()->json($data, 200);
    }

    /**
     * 处理代理支付请求
     * 不需要用户登录，通过 agency_id 获取支付信息
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process_agency_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agency_id' => 'required|string',
            'payment_method' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $digital_payment = Helpers::get_business_settings('digital_payment');
        if ($digital_payment['status'] == 0) {
            return response()->json(['errors' => ['message' => 'digital_payment_is_disable']], 403);
        }

        // 根据 agency_id 获取钱包支付记录
        $wallet = WalletPayment::where('agency_id', $request->agency_id)->first();
        
        if (!$wallet) {
            return response()->json(['errors' => ['message' => 'Wallet payment with this agency_id not found']], 404);
        }
        
        // 更新支付方式以请求为准
        $wallet->payment_method = $request->payment_method;
        $wallet->save();
        
        // 获取用户信息
        $customer = User::find($wallet->user_id);
        
        if (!isset($customer)) {
            return response()->json(['errors' => ['message' => 'Customer not found']], 403);
        }

        // 使用数据库中存储的金额
        $wallet_amount = $wallet->amount;

        $payer = new Payer(
            $customer->f_name . ' ' . $customer->l_name,
            $customer->email,
            $customer->phone,
            ''
        );

        $currency = BusinessSetting::where(['key' => 'currency'])->first()->value;
        $store_logo = BusinessSetting::where(['key' => 'logo'])->first();
        $additional_data = [
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
            'business_logo' => \App\CentralLogics\Helpers::get_full_url('business', $store_logo?->value, $store_logo?->storage[0]?->value ?? 'public')
        ];
        $payment_info = new PaymentInfo(
            success_hook: 'wallet_success',
            failure_hook: 'wallet_failed',
            currency_code: $currency,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer->id,
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $wallet_amount,
            external_redirect_link: $request->has('callback') ? $request['callback'] : session('callback'),
            attribute: 'wallet_payments',
            attribute_id: $wallet->id
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        $data = [
            'redirect_link' => $redirect_link,
        ];
        return response()->json($data, 200);
    }

    /**
     * 钱包充值代付信息接口
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function wallet_agency_payment_info(Request $request)
    {
        // 验证是否传入了agency_id
        if(!$request->has('agency_id')) {
            return response()->json([
                'errors' => [
                    ['code' => 'agency_id', 'message' => translate('messages.agency_id_required')]
                ]
            ], 403);
        }
        
        $agency_id = $request->query('agency_id');
        $walletPayment = \App\Models\WalletPayment::where('agency_id', $agency_id)->first();
        
        // 验证钱包支付记录是否存在
        if(!$walletPayment) {
            return response()->json([
                'errors' => [
                    ['code' => 'wallet_payment', 'message' => translate('messages.wallet_payment_not_found')]
                ]
            ], 404);
        }
        
        // 获取用户信息
        $user = \App\Models\User::find($walletPayment->user_id);
        if(!$user) {
            return response()->json([
                'errors' => [
                    ['code' => 'user', 'message' => translate('messages.user_not_found')]
                ]
            ], 404);
        }
        
        // 处理创建时间格式
        $created_at = $walletPayment->created_at;
        if (is_object($created_at) && method_exists($created_at, 'format')) {
            $formatted_date = $created_at->format('Y-m-d H:i:s');
        } else {
            // 如果不是日期对象，则直接使用或转换为合适的格式
            $formatted_date = is_string($created_at) ? $created_at : date('Y-m-d H:i:s');
        }
        
        // 检查当前请求用户或传入的user_id是否为创建者
        $is_creator = false;
        if(($request->user() && $request->user()->id == $walletPayment->user_id) || 
        ($request->has('user_id') && $request->user_id == $walletPayment->user_id)) {
            $is_creator = true;
        }
        
        // 获取最新的代付内容信息
        $agencyContent = \App\Models\AgencyAddFundInfo::where('agency_id', $agency_id)
                            ->latest()
                            ->first();
        
        // 处理图片数据
        $images = [];
        if ($agencyContent && !empty($agencyContent->images)) {
            $imagesData = json_decode($agencyContent->images, true);
            if (is_array($imagesData)) {
                foreach ($imagesData as $image) {
                    // 添加第三个参数 'public' 作为存储类型
                    $images[] = Helpers::get_full_url('agency_payment', $image, 'public');
                }
            }
        }
        
        // 准备代付内容数据
        $contentData = null;
        if ($agencyContent) {
            $contentData = [
                'title' => $agencyContent->title,
                'content' => $agencyContent->content,
                'images' => $images,
                'created_at' => $agencyContent->created_at ? $agencyContent->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $agencyContent->updated_at ? $agencyContent->updated_at->format('Y-m-d H:i:s') : null
            ];
        }
        
        // 准备响应数据
        $response = [
            'success' => true,
            'wallet_payment' => [
                'id' => $walletPayment->id,
                'agency_id' => $walletPayment->agency_id,
                'amount' => $walletPayment->amount,
                'payment_status' => $walletPayment->payment_status,
                'payment_method' => $walletPayment->payment_method,
                'is_paid' => $walletPayment->payment_status == 'success',
                'created_at' => $formatted_date,
                'is_creator' => $is_creator,  // 添加此字段表示当前请求用户或传入用户是否为创建者
                'customer' => [
                    'id' => $user->id,
                    'name' => $user->f_name.' '.$user->l_name,
                    'phone' => $user->phone,
                    'email' => $user->email
                ],
                'agency_content' => $contentData // 添加代付内容信息
            ]
        ];
        
        return response()->json($response);
    }

    public function update_agency_payment_content(Request $request)
    {
        // 验证请求数据
        $validator = Validator::make($request->all(), [
            'agency_id' => 'required|string',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'new_images' => 'nullable|array',
            'new_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // 检查 agency_id 是否有效
        $walletPayment = WalletPayment::where('agency_id', $request->agency_id)->first();
        if (!$walletPayment) {
            return response()->json([
                'errors' => [
                    ['code' => 'agency_id', 'message' => translate('messages.wallet_payment_not_found')]
                ]
            ], 404);
        }

        // 权限验证：仅允许创建者更新内容
        if (!$request->user() || $walletPayment->user_id != $request->user()->id) {
            return response()->json([
                'errors' => [
                    ['code' => 'permission', 'message' => translate('messages.permission_denied_not_your_wallet_payment')]
                ]
            ], 403);
        }

        // 处理图像上传
        $imageNames = [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $imageName = Helpers::upload('agency_payment/', 'png', $image);
                $imageNames[] = $imageName;
            }
        }

        // 始终创建一个新记录，而不是更新旧记录
        // 这样可以保留每次修改的历史版本
        $agencyInfo = new \App\Models\AgencyAddFundInfo();
        $agencyInfo->agency_id = $request->agency_id;
        $agencyInfo->title = $request->title;
        $agencyInfo->content = $request->content;
        
        if (count($imageNames) > 0) {
            $agencyInfo->images = json_encode($imageNames);
        } else {
            // 如果没有新图片，可以检查是否需要从最新版本复制图片
            $latestVersion = \App\Models\AgencyAddFundInfo::where('agency_id', $request->agency_id)
                ->latest()
                ->first();
            
            if ($latestVersion && !empty($latestVersion->images)) {
                $agencyInfo->images = $latestVersion->images;
            }
        }
        
        $agencyInfo->save();

        return response()->json([
            'status' => 'success',
            'message' => translate('messages.agency_payment_info_version_created_successfully'),
            'version_id' => $agencyInfo->id,
            'created_at' => $agencyInfo->created_at
        ], 200);
    }

    public function get_bonus()
    {
        $bonuses = WalletBonus::Active()->Running()->latest()->get();
        return response()->json($bonuses ?? [], 200);
    }

    #handshake

    public function transferMartToDrivemondWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $customer = Auth::user();
        if ($customer->wallet_balance < $request->amount) {
            $errors = [];
            array_push($errors, ['code' => 'insufficient_fund_403', 'message' => translate('messages.You have insufficient balance on wallet')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        if (Helpers::checkSelfExternalConfiguration()) {
            $currencyCode = Helpers::currency_code();
            $driveMondBaseUrl = ExternalConfiguration::where('key', 'drivemond_base_url')->first()?->value;
            $driveMondToken = ExternalConfiguration::where('key', 'drivemond_token')->first()?->value;
            $systemSelfToken = ExternalConfiguration::where('key', 'system_self_token')->first()?->value;
            $response = Http::post($driveMondBaseUrl . '/api/customer/wallet/transfer-drivemond-from-mart',
                [
                    'bearer_token' => $request->bearerToken(),
                    'currency' => $currencyCode,
                    'amount' => $request->amount,
                    'token' => $driveMondToken,
                    'external_base_url' => url('/'),
                    'external_token' => $systemSelfToken,
                ]);
            if ($response->successful()) {
                $drivemondCustomerResponse = $response->json();
                if (array_key_exists('status',$drivemondCustomerResponse) && $drivemondCustomerResponse['status']) {
                    $drivemondCustomer = $drivemondCustomerResponse['data'];
                    $user = User::where(['phone' => $drivemondCustomer['phone']])->first();
                    if ($user) {
                        $user = User::find($request->user()->id);
                        $user->wallet_balance -= $request->amount;
                        $user->save();
                        $wallet_transaction = new WalletTransaction();
                        $wallet_transaction->user_id = $user->id;
                        $wallet_transaction->transaction_id = Str::uuid();
                        $wallet_transaction->transaction_type = 'wallet_transfer_mart_to_drivemond';
                        $wallet_transaction->debit = $request->amount;
                        $wallet_transaction->balance = $user->wallet_balance;
                        $wallet_transaction->created_at = now();
                        $wallet_transaction->updated_at = now();
                        $wallet_transaction->save();
                        $data = [
                            'status' => true,
                            'data' => $user
                        ];
                        return response()->json($data);
                    }
                }
                $drivemondCustomer = $drivemondCustomerResponse['data'];
                if (array_key_exists('error_code',$drivemondCustomer) && $drivemondCustomer['error_code'] == 405) {
                    $errors = [];
                    array_push($errors, ['code' => 'currency_not_match_403', 'message' => translate('messages.Currency not matched, Please contact support')]);
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }
            } else {
                $errors = [];
                array_push($errors, ['code' => 'account_not_found_403', 'message' => translate('messages.drivemond account not found')]);
                return response()->json([
                    'errors' => $errors
                ], 403);
            }


        }
        $errors = [];
        array_push($errors, ['code' => 'account_not_found_403', 'message' => translate('messages.drivemond account not found')]);
        return response()->json([
            'errors' => $errors
        ], 403);

    }

    public function transferMartFromDrivemondWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required',
            'amount' => 'required',
            'bearer_token' => 'required',
            'token' => 'required',
            'external_base_url' => 'required',
            'external_token' => 'required',
        ]);
        if ($validator->fails()) {
            $data = [
                'status' => false,
                'data' =>Helpers::error_processor($validator),
            ];
            return response()->json($data);
        }
        if (strcasecmp(str_replace('"', '', $request->currency), str_replace('"', '', Helpers::currency_code())) !== 0) {
            $data = [
                'status' => false,
                'data' => ['error_code' => 405, 'message' => "Currency not matched, Please contact support"],
            ];
            return response()->json($data);
        }
        if (Helpers::checkSelfExternalConfiguration() && Helpers::checkExternalConfiguration($request->external_base_url, $request->external_token, $request->token)) {
            $driveMondBaseUrl = ExternalConfiguration::where('key', 'drivemond_base_url')->first()?->value;
            $driveMondToken = ExternalConfiguration::where('key', 'drivemond_token')->first()?->value;
            $systemSelfToken = ExternalConfiguration::where('key', 'system_self_token')->first()?->value;
            $response = Http::withToken($request->bearer_token)->post($driveMondBaseUrl . '/api/customer/get-data',
                [
                    'token' => $driveMondToken,
                    'external_base_url' => url('/'),
                    'external_token' => $systemSelfToken,
                ]);
            if ($response->successful()) {
                $drivemondCustomerResponse = $response->json();
                if ($drivemondCustomerResponse['status']) {
                    $drivemondCustomer = $drivemondCustomerResponse['data'];
                    $user = User::where(['phone' => $drivemondCustomer['phone']])->first();
                    if ($user) {
                        $user->wallet_balance += $request->amount;
                        $user->save();
                        $wallet_transaction = new WalletTransaction();
                        $wallet_transaction->user_id = $user->id;
                        $wallet_transaction->transaction_id = Str::uuid();
                        $wallet_transaction->transaction_type = 'wallet_transfer_mart_from_drivemond';
                        $wallet_transaction->credit = $request->amount;
                        $wallet_transaction->balance = $user->wallet_balance;
                        $wallet_transaction->created_at = now();
                        $wallet_transaction->updated_at = now();
                        $wallet_transaction->save();

                        $notificationData = [
                            'title' => translate('wallet_transfer_mart_from_drivemond'),
                            'description' => translate('you_transfer_your_wallet_balance_mart_from_drivemond'),
                            'order_id' => '',
                            'image' => '',
                            'type'=> 'wallet_transfer'
                        ];
                        Helpers::send_push_notif_to_device($user->cm_firebase_token, $notificationData);

                        $data = [
                            'status' => true,
                            'data' => $user
                        ];
                        return response()->json($data);
                    }
                }
            }
            $drivemondCustomer = $drivemondCustomerResponse['data'];
            if ($drivemondCustomer['error_code'] == 402) {
                $data = [
                    'status' => false,
                    'data' => ['error_code' => 402, 'message' => "Drivemond user not found"]
                ];
                return response()->json($data);
            }

        }
        $data = [
            'status' => false,
            'data' => ['error_code' => 402, 'message' => "Invalid token"]
        ];
        return response()->json($data);


    }
}
