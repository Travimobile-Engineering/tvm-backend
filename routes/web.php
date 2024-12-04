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
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\TripBookingController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\VehicleController;

Route::get('/', function () {
    // return view('welcome');
    return 'welcome to tvm console! nothing spoil 😇👍';
});


Route::withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
// ->middleware(AllowCORS::class)
->group(function(){

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
    });

    Route::prefix('company')
    ->group(function(){
        Route::get('/{transitCompany}', [TransitCompanyController::class, 'show']);
        Route::post('/create', [TransitCompanyController::class, 'store']);
        Route::post('/edit/{transitCompany}', [TransitCompanyController::class, 'update']);
    });

    Route::prefix('vehicle')
    ->group(function(){
        Route::get('/get-types', [VehicleController::class, 'getVehicleTypes']);
        Route::get('/get-brands', [VehicleController::class, 'getVehicleBrands']);
        Route::get('/{vehicle}', [VehicleController::class, 'show']);
        Route::post('/create', [VehicleController::class, 'store']);
        Route::post('/edit/{vehicle}', [VehicleController::class, 'update']);

    });

    Route::prefix('trip')
    ->group(function(){
        Route::post('/create', [TripController::class, 'store']);
        Route::post('/edit/{trip}', [TripController::class, 'update']);
        Route::get('/{trip}', [TripController::class, 'show']);
    });

    Route::prefix('trip-booking')
    ->group(function(){
        Route::post('/create', [TripBookingController::class, 'store']);
        Route::post('/edit/{tripBooking}', [TripBookingController::class, 'update']);
        Route::get('/{tripBooking}', [TripBookingController::class, 'show']);
    });

});

Route::get('/send-test-mail', [SendTestMailController::class, 'sendTestMail']);
Route::fallback(function(){
    return response()->json(['error', 'page not found'], 404);
});




