<?php

use App\Http\Controllers\Auth\ForgotPasswordEmailController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendTestMailController;
use App\Http\Middleware\JWTAuthenticator;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\TripBookingController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WalletController;

Route::get('/', function () {
    // return view('welcome');
    return 'welcome to tvm console! nothing spoil 😇👍';
});

Route::prefix('auth')
->group(function(){
    Route::post('/signup', [RegisterController::class, 'signup']);
    Route::post('/login', [AuthenticateController::class, 'login']);
    Route::post('/forgot-password-email', [ForgotPasswordEmailController::class, 'send_password_reset_link']);
    Route::get('/reset-password', fn()=> "Oops! Please bear with us. We are currently working on this page")->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);
    Route::post('/verify', [VerifyController::class, 'index']);
    Route::post('/resend-verification-code', [RegisterController::class, 'send_verification_code']);
});

Route::middleware(JWTAuthenticator::class)
->group(function(){

    Route::prefix('profile')
    ->group(function (){
        Route::get('/', [ProfileController::class, 'index']);
        Route::post('/edit/{id}', [ProfileController::class, 'edit']);
    });

    Route::get('/auth/logout', [AuthenticateController::class, 'logout']);

    Route::prefix('transit-company')
    ->group(function(){
        Route::post('/create', [TransitCompanyController::class, 'store']);
        Route::post('/edit/{transitCompany}', [TransitCompanyController::class, 'update']);
        Route::get('/{transitCompany}', [TransitCompanyController::class, 'show']);
    });

    Route::prefix('route')
    ->group(function(){
        Route::get('/get-covered-routes', [RouteController::class, 'getCoveredRoutes']);
        Route::get('/get-regions', [RouteController::class, 'getRegions']);
    });

    Route::prefix('vehicle')
    ->group(function(){
        Route::get('/get-types', [VehicleController::class, 'getVehicleTypes']);
        Route::get('/get-brands', [VehicleController::class, 'getVehicleBrands']);
        Route::post('/create', [VehicleController::class, 'store']);
        Route::post('/edit/{vehicle}', [VehicleController::class, 'update']);
        Route::get('/{vehicle}', [VehicleController::class, 'show']);

    });

    Route::prefix('trip')
    ->group(function(){
        Route::post('/create', [TripController::class, 'store']);
        Route::post('/edit/{trip}', [TripController::class, 'update']);
        Route::get('/get-trips', [TripController::class, 'getTrips']);
        Route::get('/{trip}', [TripController::class, 'show']);
    });

    Route::prefix('trip-booking')
    ->group(function(){
        Route::post('/create', [TripBookingController::class, 'store']);
        Route::post('/edit/{tripBooking}', [TripBookingController::class, 'update']);
        Route::get('/cancel/{booking_id}', [TripBookingController::class, 'cancelTripBooking']);
        Route::get('/history/{user}', [TripBookingController::class, 'getUserTripBookingHistory']);
        Route::get('/{tripBooking}', [TripBookingController::class, 'show']);
    });

    Route::prefix('payment')
    ->group(function(){
        Route::post('/initialize-paystack-transaction', [PaystackPaymentController::class, 'intializeTransaction']);
    });

    Route::prefix('wallet')
    ->group(function(){
        Route::get('/get-balance', [WalletController::class, 'getBalance']);
        Route::post('/fund-wallet', [WalletController::class, 'fundWallet']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
        Route::get('/transactions', [WalletController::class, 'getTransactions']);
        Route::post('/set-transaction-pin', [WalletController::class, 'setTransactionPin']);
    });

    Route::prefix('transport')
        ->controller(TransportController::class)
        ->group(function () {
            // One Time
            Route::post('/one-time', 'createOneTime');
            Route::get('/one-time/{id}', 'getOneTime');
            Route::get('/user/one-time/{user_id}', 'getUserOneTimes');
            Route::put('/one-time/{id}', 'editOneTime');

            // Recurring
            Route::post('/recurring', 'createRecurring');
            Route::get('/recurring/{id}', 'getRecurring');
            Route::get('/user/recurring/{user_id}', 'getUserRecurrings');
            Route::put('/recurring/{id}', 'editRecurring');

            // Trips
            Route::get('/upcoming/{user_id}', 'getUpcomingTrips');
            Route::get('/completed/{user_id}', 'getCompletedTrips');
            Route::get('/cancelled/{user_id}', 'getCancelledTrips');

            // Trip update
            Route::put('/cancel/{id}', 'cancelTrip');
            Route::put('/complete/{id}', 'completeTrip');
        });
});

Route::get('/send-test-mail', [SendTestMailController::class, 'sendTestMail']);
Route::fallback(function(){
    return response()->json(['error' => 'page not found'], 404);
});
