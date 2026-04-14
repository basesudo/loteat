<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytmController;
use App\Http\Controllers\LiqPayController;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\PaytabsController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\RazorPayController;
use App\Http\Controllers\SenangPayController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\FlutterwaveV3Controller;
use App\Http\Controllers\PaypalPaymentController;
use App\Http\Controllers\EzypayPaymentController;
use App\Http\Controllers\AntomPaymentController;
use App\Http\Controllers\Nova2PayController;
use App\Http\Controllers\PricPayController;
use App\Http\Controllers\UPrimerPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SslCommerzPaymentController;
use App\Http\Controllers\FirebaseController;
use App\CentralLogics\Helpers;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::post('/subscribeToTopic', [FirebaseController::class, 'subscribeToTopic']);
Route::get('/', 'HomeController@index')->name('home');
Route::get('lang/{locale}', 'HomeController@lang')->name('lang');
Route::get('terms-and-conditions', 'HomeController@terms_and_conditions')->name('terms-and-conditions');
Route::get('agency-payment-share', 'HomeController@agency_payment_share')->name('agency-payment-share');
Route::get('agency-payment-thank-you', 'HomeController@agency_payment_thank_you')->name('agency-payment-thank-you');

Route::get('agency-payment-add-fund', 'HomeController@agency_payment_add_fund')->name('agency-payment-add-fund');
Route::get('agency-payment-success', 'HomeController@agency_payment_success')->name('agency-payment-success');

Route::get('agency-payment-info', 'HomeController@agency_payment_info')->name('agency_payment_info');
Route::get('about-us', 'HomeController@about_us')->name('about-us');
Route::get('contact-us', 'HomeController@contact_us')->name('contact-us');
Route::post('send-message', 'HomeController@send_message')->name('send-message');
Route::get('privacy-policy', 'HomeController@privacy_policy')->name('privacy-policy');
Route::get('cancelation', 'HomeController@cancelation')->name('cancelation');
Route::get('refund', 'HomeController@refund_policy')->name('refund');
Route::get('shipping-policy', 'HomeController@shipping_policy')->name('shipping-policy');
Route::post('newsletter/subscribe', 'NewsletterController@newsLetterSubscribe')->name('newsletter.subscribe');
Route::get('subscription-invoice/{id}', 'HomeController@subscription_invoice')->name('subscription_invoice');

Route::get('login/{tab}', 'LoginController@login')->name('login');
Route::post('external-login-from-drivemond', 'LoginController@externalLoginFromDrivemond');
Route::post('login_submit', 'LoginController@submit')->name('login_post')->middleware('actch');
Route::get('logout', 'LoginController@logout')->name('logout');
Route::get('/reload-captcha', 'LoginController@reloadCaptcha')->name('reload-captcha');
Route::get('/reset-password', 'LoginController@reset_password_request')->name('reset-password');
Route::post('/vendor-reset-password', 'LoginController@vendor_reset_password_request')->name('vendor-reset-password');
Route::get('/password-reset', 'LoginController@reset_password')->name('change-password');
Route::post('verify-otp', 'LoginController@verify_token')->name('verify-otp');
Route::post('reset-password-submit', 'LoginController@reset_password_submit')->name('reset-password-submit');
Route::get('otp-resent', 'LoginController@otp_resent')->name('otp_resent');

Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthenticated.']);
    return response()->json([
        'errors' => $errors,
    ], 401);
})->name('authentication-failed');

Route::group(['prefix' => 'payment-mobile'], function () {
    Route::get('/', 'PaymentController@payment')->name('payment-mobile');
    Route::get('set-payment-method/{name}', 'PaymentController@set_payment_method')->name('set-payment-method');
});

Route::get('payment-success', 'PaymentController@success')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');
Route::get('payment-cancel', 'PaymentController@cancel')->name('payment-cancel');

$is_published = 0;
try {
$full_data = include('Modules/Gateways/Addon/info.php');
$is_published = $full_data['is_published'] == 1 ? 1 : 0;
} catch (\Exception $exception) {}

if (!$is_published) {
    Route::group(['prefix' => 'payment'], function () {

        //SSLCOMMERZ
        Route::group(['prefix' => 'sslcommerz', 'as' => 'sslcommerz.'], function () {
            Route::get('pay', [SslCommerzPaymentController::class, 'index'])->name('pay');
            Route::post('success', [SslCommerzPaymentController::class, 'success'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('failed', [SslCommerzPaymentController::class, 'failed'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('canceled', [SslCommerzPaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //STRIPE
        Route::group(['prefix' => 'stripe', 'as' => 'stripe.'], function () {
            Route::get('pay', [StripePaymentController::class, 'index'])->name('pay');
            Route::get('token', [StripePaymentController::class, 'payment_process_3d'])->name('token');
            Route::get('success', [StripePaymentController::class, 'success'])->name('success');
            Route::get('canceled', [StripePaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //RAZOR-PAY
        Route::group(['prefix' => 'razor-pay', 'as' => 'razor-pay.'], function () {
            Route::get('pay', [RazorPayController::class, 'index']);
            Route::post('payment', [RazorPayController::class, 'payment'])->name('payment')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //PAYPAL
        Route::group(['prefix' => 'paypal', 'as' => 'paypal.'], function () {
            Route::get('pay', [PaypalPaymentController::class, 'payment']);
            Route::any('success', [PaypalPaymentController::class, 'success'])->name('success')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('cancel', [PaypalPaymentController::class, 'cancel'])->name('cancel')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //EZYPAY
        Route::group(['prefix' => 'ezypay', 'as' => 'ezypay.'], function () {
            Route::get('pay', [EzypayPaymentController::class, 'payment']);
            Route::any('success', [EzypayPaymentController::class, 'success'])->name('success')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('cancel', [EzypayPaymentController::class, 'cancel'])->name('cancel')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('notify', [EzypayPaymentController::class, 'notify'])->name('notify')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //ANTOM
        Route::group(['prefix' => 'antom', 'as' => 'antom.'], function () {
            Route::get('pay', [AntomPaymentController::class, 'payment']);
            Route::get('consult', [AntomPaymentController::class, 'paymentConsult']);
            // Route::get('pay2', [AntomPaymentController::class, 'pay2']);
            Route::any('success', [AntomPaymentController::class, 'success'])->name('success')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('cancel', [AntomPaymentController::class, 'cancel'])->name('cancel')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('notify', [AntomPaymentController::class, 'notify'])->name('notify')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //nova2pay
        Route::prefix('nova2pay')->name('nova2pay.')->group(function () {
    
            // 主要支付处理路由 (支持 GET 和 POST)
            Route::match(['GET', 'POST'], '/pay', [Nova2PayController::class, 'payment'])
                ->name('payment');
            
            // 支付成功回调 (同步通知)
            Route::get('/success', [Nova2PayController::class, 'success'])
                ->name('return');
            
            // 支付异步通知回调
            Route::post('/notify', [Nova2PayController::class, 'notify'])
                ->name('notify');
            
            // 支付取消
            Route::get('/cancel', [Nova2PayController::class, 'cancel'])
                ->name('cancel');
        });

        Route::group(['prefix' => 'pricpay', 'as' => 'pricpay.'], function () {
            // 主要支付处理路由 (支持 GET 和 POST)
            Route::match(['GET', 'POST'], 'pay', [PricPayController::class, 'payment'])
                ->name('payment');
            
            // 支付成功回调 (同步通知)
            Route::any('return', [PricPayController::class, 'handleReturn'])
                ->name('return')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            
            // 支付异步通知回调
            Route::any('notify', [PricPayController::class, 'handleNotify'])
                ->name('notify')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            
            // 支付取消
            Route::any('cancel', [PricPayController::class, 'cancel'])
                ->name('cancel')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            
            // 支付状态查询
            Route::get('status', [PricPayController::class, 'queryPaymentStatus'])
                ->name('status');
        });

        //UPRIMER
        Route::group(['prefix' => 'uprimer', 'as' => 'uprimer.'], function () {
            Route::get('pay', [UPrimerPaymentController::class, 'payment'])->name('pay');
            Route::post('process', [UPrimerPaymentController::class, 'processPayment'])->name('process');
            Route::get('success', [UPrimerPaymentController::class, 'success'])->name('success');
            Route::get('cancel', [UPrimerPaymentController::class, 'cancel'])->name('cancel');
            Route::post('notify/{payment_id}', [UPrimerPaymentController::class, 'notify'])->name('notify')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::get('query', [UPrimerPaymentController::class, 'queryPayment'])->name('query');
            Route::post('refund', [UPrimerPaymentController::class, 'refund'])->name('refund');
        });

        //SENANG-PAY
        Route::group(['prefix' => 'senang-pay', 'as' => 'senang-pay.'], function () {
            Route::get('pay', [SenangPayController::class, 'index']);
            Route::any('callback', [SenangPayController::class, 'return_senang_pay']);
        });

        //PAYTM
        Route::group(['prefix' => 'paytm', 'as' => 'paytm.'], function () {
            Route::get('pay', [PaytmController::class, 'payment']);
            Route::any('response', [PaytmController::class, 'callback'])->name('response')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //FLUTTERWAVE
        Route::group(['prefix' => 'flutterwave-v3', 'as' => 'flutterwave-v3.'], function () {
            Route::get('pay', [FlutterwaveV3Controller::class, 'initialize'])->name('pay');
            Route::get('callback', [FlutterwaveV3Controller::class, 'callback'])->name('callback');
        });

        //PAYSTACK
        Route::group(['prefix' => 'paystack', 'as' => 'paystack.'], function () {
            Route::get('pay', [PaystackController::class, 'index'])->name('pay');
            Route::post('payment', [PaystackController::class, 'redirectToGateway'])->name('payment');
            Route::get('callback', [PaystackController::class, 'handleGatewayCallback'])->name('callback');
        });

        //BKASH

        Route::group(['prefix' => 'bkash', 'as' => 'bkash.'], function () {
            // Payment Routes for bKash
            Route::get('make-payment', [BkashPaymentController::class, 'make_tokenize_payment'])->name('make-payment');
            Route::any('callback', [BkashPaymentController::class, 'callback'])->name('callback');

            // Refund Routes for bKash
            // Route::get('refund', 'BkashRefundController@index')->name('bkash-refund');
            // Route::post('refund', 'BkashRefundController@refund')->name('bkash-refund');
        });

        //Liqpay
        Route::group(['prefix' => 'liqpay', 'as' => 'liqpay.'], function () {
            Route::get('payment', [LiqPayController::class, 'payment'])->name('payment');
            Route::any('callback', [LiqPayController::class, 'callback'])->name('callback');
        });

        //MERCADOPAGO

        Route::group(['prefix' => 'mercadopago', 'as' => 'mercadopago.'], function () {
            Route::get('pay', [MercadoPagoController::class, 'index'])->name('index');
            Route::any('make-payment', [MercadoPagoController::class, 'make_payment'])->name('make_payment')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::get('success', [MercadoPagoController::class, 'success'])->name('success');
            Route::get('failed', [MercadoPagoController::class, 'failed'])->name('failed');
        });

        //PAYMOB
        Route::group(['prefix' => 'paymob', 'as' => 'paymob.'], function () {
            Route::any('pay', [PaymobController::class, 'credit'])->name('pay');
            Route::any('callback', [PaymobController::class, 'callback'])->name('callback');
        });

        //PAYTABS
        Route::group(['prefix' => 'paytabs', 'as' => 'paytabs.'], function () {
            Route::any('pay', [PaytabsController::class, 'payment'])->name('pay');
            Route::any('callback', [PaytabsController::class, 'callback'])->name('callback');
            Route::any('response', [PaytabsController::class, 'response'])->name('response');
        });
    });
}


Route::get('/test', function () {
    dd('Hello tester');
});

Route::get('module-test', function () {
});

//Restaurant Registration
Route::group(['prefix' => 'store', 'as' => 'restaurant.'], function () {
    Route::get('apply', 'VendorController@create')->name('create');
    Route::post('apply', 'VendorController@store')->name('store');
    Route::get('get-all-modules', 'VendorController@get_all_modules')->name('get-all-modules');

    Route::get('back', 'VendorController@back')->name('back');
    Route::post('business-plan', 'VendorController@business_plan')->name('business_plan');
    Route::post('payment', 'VendorController@payment')->name('payment');
    Route::get('final-step', 'VendorController@final_step')->name('final_step');
});

//Deliveryman Registration
Route::group(['prefix' => 'deliveryman', 'as' => 'deliveryman.'], function () {
    Route::get('apply', 'DeliveryManController@create')->name('create');
    Route::post('apply', 'DeliveryManController@store')->name('store');
});
