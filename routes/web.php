<?php

use App\Http\Controllers\auth\ForgotPasswordEmailController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendTestMailController;
use App\Http\Middleware\JWTAuthenticator;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticateController;

Route::get('/', function () {
    // return view('welcome');
    return 'welcome to tvm console! nothing spoil 😇👍';
});


Route::withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
->group(function(){

    Route::prefix('auth')
    ->group(function(){
        Route::post('/signup', [RegisterController::class, 'signup']);
        Route::post('/login', [AuthenticateController::class, 'login']);
        Route::post('/forgot-password-email', [ForgotPasswordEmailController::class, 'send_password_reset_link']);
        Route::get('/reset-password', [])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);
        Route::post('/verify', [VerifyController::class, 'index']);
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

});




Route::get('/send-test-mail', [SendTestMailController::class, 'sendTestMail']);
