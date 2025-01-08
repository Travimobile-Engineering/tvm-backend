<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\Auth\ForgotPasswordEmailController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendTestMailController;
use App\Http\Middleware\CheckExpectsJson;
use App\Http\Middleware\JWTAuthenticator;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\OtherController;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\TripBookingController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WalletController;


Route::get('/', function () {
    // return view('welcome');
    return 'welcome to tvm console! nothing spoil 😇👍';
});

Route::controller(OtherController::class)
    ->group(function () {
        Route::get('/states', 'getStates');
        Route::get('/bank', 'getBank');
        Route::post('/account/lookup', 'accountLookUp');
    });

Route::post('/payment/webhook', [PaymentController::class, 'webhook']);

Route::prefix('auth')
->group(function(){
    Route::post('/signup', [RegisterController::class, 'signup']);
    Route::post('/login', [AuthenticateController::class, 'login']);
    Route::post('/forgot-password-email', [ForgotPasswordEmailController::class, 'send_password_reset_link']);
    Route::get('/reset-password', fn()=> "Oops! Please bear with us. We are currently working on this page")->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);
    Route::post('/verify', [RegisterController::class, 'verify_account']);
    Route::post('/resend-verification-code', [RegisterController::class, 'send_verification_code']);
});

Route::middleware(JWTAuthenticator::class)
->group(function(){

    Route::prefix('profile')
        ->controller(ProfileController::class)
        ->group(function (){
            Route::get('/', 'index');
            Route::post('/edit/{id}', 'edit');
            Route::get('/driver', 'getDriverProfile');
        });

    Route::get('/auth/logout', [AuthenticateController::class, 'logout']);

    Route::prefix('transit-company')
    ->controller(TransitCompanyController::class)
    ->group(function(){
        Route::post('/create', 'store');
        Route::get('/get-unions', 'getUnions');
        Route::post('/edit/{transitCompany}', 'update');
        Route::get('/{transitCompany}', 'show');
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
        ->controller(TripController::class)
        ->group(function () {
            Route::post('/create', 'store');
            Route::get('/popular', 'getPopularTrips');
            Route::post('/edit/{trip}', 'update');
            Route::get('/get-trips', 'getTrips');
            Route::get('/{trip}', 'getTrip');

            // Get Bus Stops
            Route::get('/bus-stops/{state_id}', 'getBusStops');

            Route::prefix('/driver')
                ->group(function () {

                    // One Time
                    Route::post('/one-time', 'createOneTime');
                    Route::get('/get-one-time/{id}', 'getOneTime');
                    Route::get('/user/one-time/{user_id}', 'getUserOneTimes');
                    Route::put('/edit-one-time/{id}', 'editOneTime');

                    // Recurring
                    Route::post('/recurring', 'createRecurring');
                    Route::get('/recurring/get-one/{id}', 'getRecurring');
                    Route::get('/user/recurring/{user_id}', 'getUserRecurrings');
                    Route::put('/recurring/edit/{id}', 'editRecurring');

                    // Trips
                    Route::get('/upcoming/{user_id}', 'getUpcomingTrips');
                    Route::get('/completed/{user_id}', 'getCompletedTrips');
                    Route::get('/cancelled/{user_id}', 'getCancelledTrips');
                    Route::get('/{user_id}', 'getAllTrips');
                    Route::get('/passenger-info/{trip_id}/{user_id}', 'getManifestInfo');
                    Route::post('/start-trip', 'startTrip');

                    // Trip update
                    Route::put('/cancel/{id}', 'cancelTrip');
                    Route::put('/complete/{id}', 'completeTrip');
                });

            Route::prefix('/passenger')
                ->group(function () {
                    Route::get('/get-trips', 'getAll');
                });
        });

    Route::prefix('driver')
        ->controller(DriverController::class)
        ->group(function () {
            Route::post('/onboarding', 'addDriverInfo');
            Route::post('/bus-stop', 'addBusStop');
            Route::get('/bus-stop/{user_id}', 'getAllBusStops');
            Route::get('{user_id}/stops/{state_id}', 'getStop');

            // Documents
            Route::post('/edit-document', 'updateDriverDocuments');
            Route::delete('/remove-document/{id}', 'removeDocument');
            Route::put('/edit-union', 'updateUnion');

            Route::prefix('wallet')
                ->controller(WalletController::class)
                ->group(function () {
                    Route::post('/setup', 'driverWalletSetup');
                    Route::post('/verify-pin', 'verifyPin');
                    Route::post('/withdraw', 'withdraw')
                        ->middleware('transaction.pin');
                    Route::post('/topup', 'walletTopUp')
                        ->middleware('transaction.pin');

                    // Transaction
                    Route::get('/recent-transaction/{user_id}', 'recentTransaction');
                });
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
});

Route::prefix('agent')->controller(AgentController::class)
->group(function(){
    Route::get('/{agent_id}', 'show');
});

Route::get('/send-test-mail', [SendTestMailController::class, 'sendTestMail']);
Route::fallback(function(){
    return response()->json(['error' => 'page not found'], 404);
});

