<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AirlineController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\IpWhitelistController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ManifestCheckerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NpisController;
use App\Http\Controllers\OtherController;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PremiumHireController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SendTestMailController;
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\TransportRouteManagementController;
use App\Http\Controllers\TripBookingController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\JWTAuthenticator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::post('/seed/run', function () {
    $seederClass = Str::studly(request()->input('seeder_class'));

    if (! class_exists("Database\\Seeders\\{$seederClass}")) {
        return response()->json([
            'error' => "Seeder class '{$seederClass}' not found in Database\\Seeders namespace.",
        ], 404);
    }

    try {
        Artisan::call('db:seed', [
            '--class' => $seederClass,
            '--force' => true,
        ]);

        return response()->json([
            'message' => "{$seederClass} executed successfully.",
            'output' => Artisan::output(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Seeder failed to run.',
            'details' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/', fn () => response('Welcome to the API', 200));

Route::resource('transport-route-management', TransportRouteManagementController::class);
Route::prefix('npis')
    ->controller(NpisController::class)
    ->group(function () {
        Route::post('/event/create', 'createEvent');
        Route::get('/', 'getEvents');
        Route::get('/{id}', 'getEvent');
    });

Route::middleware(['validate.header'])
    ->group(function () {
        Route::controller(OtherController::class)
            ->group(function () {
                Route::get('/states', 'getStates');
                Route::get('/bank', 'getBank');
                Route::post('/account/lookup', 'accountLookUp');
            });

        Route::prefix('job')
            ->controller(JobController::class)
            ->group(function () {
                Route::get('/all', 'getJobs');
                Route::get('/{id}', 'getJob');
                Route::post('/apply', 'apply');
                Route::post('/add', 'addJob');
                Route::post('/update/{id}', 'updateJob');
            });

        Route::prefix('auth')
            ->group(function () {
                Route::post('/signup', [RegisterController::class, 'accountSignUp']);
                Route::post('/login', [AuthenticateController::class, 'authLogin'])
                    ->middleware('login.attempt');

                Route::post('/manifest-checker/login', [AuthenticateController::class, 'agencyLogin'])
                    ->middleware('login.attempt');

                Route::post('/forgot-password-email', [ForgotPasswordController::class, 'sendPasswordResetOtp']);
                Route::post('/resend-code', [RegisterController::class, 'resendCode']);
                Route::post('/verify-reset-password-otp', [ForgotPasswordController::class, 'verifyPasswordResetOtp']);
                Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
                Route::post('/verify/account', [RegisterController::class, 'verifyAcount']);
            });

        Route::prefix('payment')
            ->controller(PaymentController::class)
            ->group(function () {
                Route::post('/callback', 'callback');

                // Deprecating soon
                Route::post('/webhook', 'webhook')
                    ->withoutMiddleware(['validate.header']);
            });

        // Approval URL (Deprecating soon)
        Route::post('/payment/paystack/transfer/approve', [PaymentController::class, 'approveTransfer'])
            ->withoutMiddleware(['validate.header']);

        // Google Auth
        Route::controller(GoogleAuthController::class)
            ->group(function (): void {
                Route::get('auth/google', 'redirectToGoogle');
                Route::get('auth/google/callback', 'handleCallback');
            });

        Route::prefix('route')
            ->group(function () {
                Route::get('/get-covered-routes', [RouteController::class, 'getCoveredRoutes']);
                Route::get('/get-regions', [RouteController::class, 'getRegions']);
            });

        // User Set Security Answer
        Route::prefix('user')
            ->controller(UserSettingsController::class)
            ->group(function () {
                Route::get('/settings/get/questions', 'getQuestions');
                Route::post('/set-security-answer', 'setSecurityAnswer');
                Route::post('/create-password', 'createPassword');
                Route::get('/get-question', 'getUserQuestion');
                Route::post('/verify-security-answer', 'verifySecurityAnswer');
            });

        Route::middleware(JWTAuthenticator::class)
            ->group(function () {
                Route::prefix('profile')
                    ->controller(ProfileController::class)
                    ->group(function () {
                        Route::get('/', 'index');
                        Route::post('/edit/{id}', 'edit');
                        Route::get('/driver', 'getDriverProfile');
                        Route::get('/foo', 'getFoo');
                    });

                Route::get('/auth/logout', [AuthenticateController::class, 'logout']);

                Route::prefix('transit-company')
                    ->controller(TransitCompanyController::class)
                    ->group(function () {
                        Route::post('/create', 'store');
                        Route::get('/get-unions', 'getUnions');
                        Route::get('/{transitCompany}', 'show');
                        Route::post('/edit/{transitCompany}', 'update');
                    });

                Route::prefix('vehicle')
                    ->group(function () {
                        Route::get('/get-types', [VehicleController::class, 'getVehicleTypes']);
                        Route::get('/get-types/{vehicleType}', [VehicleController::class, 'getVehicleType']);
                        Route::get('/get-brands', [VehicleController::class, 'getVehicleBrands']);
                        Route::post('/create', [VehicleController::class, 'store']);
                        Route::post('/edit/{vehicle}', [VehicleController::class, 'update']);
                        Route::get('/{vehicle}', [VehicleController::class, 'show']);
                    });

                // All user routes
                Route::prefix('user')
                    ->controller(UserController::class)
                    ->group(function () {
                        Route::post('/change-password', 'changePassword');
                        Route::prefix('{user_id}')->group(function () {
                            Route::get('notifications', 'getNotifications');
                            Route::get('notification/{id}', 'getNotification');
                            Route::delete('notification/{id}', 'deleteNotification');
                            Route::post('notifications/mark-all-read', 'markAllNotificationsAsRead');
                        });
                        Route::patch('notification', 'updateNotification');
                        Route::post('/save-fcm-token', 'saveFCMToken');
                        Route::patch('remove-fcm-token', 'removeFCMToken');
                        Route::delete('/delete-account', 'deleteAccount');

                        // Settings
                        Route::prefix('settings')
                            ->controller(UserSettingsController::class)
                            ->group(function () {
                                Route::post('/change/security-answer', 'changeSecurityAnswer');
                            });

                        // Announcement
                        Route::prefix('announcement')
                            ->group(function () {
                                Route::get('/', 'getAnnouncements');
                                Route::post('/read', 'markAsRead');
                            });
                    });

                Route::prefix('user/wallet')
                    ->controller(WalletController::class)
                    ->group(function () {
                        Route::post('/setup', 'walletSetup');
                        Route::post('/verify-pin', 'verifyPin');
                        Route::post('/set-transaction-pin', 'setTransactionPin');
                        Route::post('/withdraw', 'withdraw')
                            ->middleware(['transaction.pin', 'burst.guard', 'tx.replay']);
                        Route::post('/balance/withdraw', 'balanceWithdraw')
                            ->middleware(['transaction.pin', 'burst.guard', 'tx.replay']);
                        Route::post('/topup', 'walletTopUp');
                        Route::post('/change-bank', 'changeBank')
                            ->middleware('transaction.pin');

                        // Change Pin
                        Route::post('/pin/send-otp', 'sendOtp');
                        Route::post('/pin/verify', 'verifyWalletPin');
                        Route::post('/pin/change', 'changePin')
                            ->middleware('verify.pin');

                        // Transaction
                        Route::get('/recent-transaction/{user_id}', 'recentTransaction');
                        Route::get('/recent-earning/{user_id}', 'recentEarning');
                        Route::get('/statistics/{user_id}', 'stats');
                    });

                Route::prefix('trip')
                    ->controller(TripController::class)
                    ->group(function () {
                        Route::post('/create', 'store');
                        Route::get('/popular', 'getPopularTrips');
                        Route::post('/edit/{trip}', 'update');
                        Route::get('/get-trips', 'getTrips');
                        Route::get('/{trip}', 'getTrip');
                        Route::post('/extend-time', 'tripExtendTime');

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

                                // Extend time
                                Route::put('/settings/extend-time', 'extendTime');

                                // Notify Passengers
                                Route::post('/notify', 'notifyPassengers');
                            });

                        Route::prefix('/passenger')
                            ->group(function () {
                                Route::get('/get-trips', 'getAll');
                                Route::get('/ticket/download/{booking_id}', 'downloadTicket');
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

                        Route::post('/setup-vehicle', 'setupVehicle');
                        Route::post('/vehicle-requirements', 'premiumUpgrade');
                        Route::put('/edit-description', 'editDescription');
                        Route::post('/set-availability', 'setAvailability');
                        Route::put('/vehicle/update-layout', 'updateLayout');

                        Route::match(['get', 'post'], '/scan-ticket/{booking_id?}/{seat_no?}', 'scanTicket');
                    });

                Route::prefix('premium')
                    ->controller(PremiumHireController::class)
                    ->group(function () {
                        Route::get('/vehicle-lookup', 'vehicleLookup');
                        Route::get('/vehicle/{vehicle_id}', 'vehicleDetail');
                        Route::post('/add/charter', 'addCharter');
                        Route::get('/charter/{user_id}', 'getCharter');
                        Route::delete('/remove/charter/{id}', 'removeCharter');
                        Route::post('/charter/payment', 'payCharter');
                        Route::get('/payment/{reference}', 'getPaymentRef');
                        Route::get('/user/booking/{user_id}', 'userBookings');
                        Route::prefix('booking')
                            ->group(function () {
                                Route::get('/{user_id}', 'getBookings');
                                Route::get('/detail/{id}', 'bookingDetails');
                            });

                        Route::prefix('user')->group(function () {
                            Route::post('/passenger', 'addPassenger');
                            Route::get('/passenger/{user_id}/{booking_id}', 'getPassengers');
                            Route::put('/passenger/{user_id}', 'editPassenger');
                            Route::delete('/passengers', 'deletePassenger');
                        });
                        Route::put('/cancel-booking', 'cancelBooking');
                        Route::post('/review', 'review');
                        Route::get('/review', 'getReviews');
                        Route::get('/review/{vehicle_id}', 'getSingleReview');

                        Route::prefix('trip')
                            ->group(function () {
                                Route::get('/{user_id}', 'driverBookings');
                                Route::get('/detail/{id}', 'driverTripDetails');
                                Route::put('/accept/{id}', 'acceptTrip');
                                Route::put('/cancel', 'cancelTrip');
                                Route::put('/start/{id}', 'startTrip');
                                Route::post('/finish', 'finishTrip');
                            });
                    });

                Route::prefix('trip-booking')
                    ->controller(TripBookingController::class)
                    ->group(function () {
                        Route::post('/create', 'booking');
                        Route::post('/edit/{tripBooking}', 'update');
                        Route::post('/cancel', 'cancelTripBooking');
                        Route::get('/history/{user}', 'getUserTripBookingHistory');
                        Route::get('/{tripBooking}', 'show');
                        Route::get('/payment/{reference}', 'getPaymentRef');
                    });

                Route::prefix('payment')
                    ->group(function () {
                        Route::post('/initialize-paystack-transaction', [PaystackPaymentController::class, 'intializeTransaction']);
                    });

                Route::prefix('wallet')
                    ->group(function () {
                        Route::get('/get-balance', [WalletController::class, 'getBalance']);
                        Route::post('/fund-wallet', [WalletController::class, 'fundWallet'])
                            ->middleware(['burst.guard', 'tx.replay']);
                        Route::post('/transfer', [WalletController::class, 'transfer'])
                            ->middleware(['transaction.pin', 'burst.guard', 'tx.replay']);
                        Route::get('/transactions', [WalletController::class, 'getTransactions']);
                    });

                Route::prefix('notification')
                    ->controller(NotificationController::class)
                    ->group(function () {
                        Route::get('/', 'all');
                        Route::get('/{notification}', 'show');
                        Route::delete('/{notification}', 'delete');
                    });

                Route::prefix('manifest-checker')
                    ->group(function () {
                        Route::post('/login', [AuthenticateController::class, 'securityAgentLogin'])
                            ->middleware(['login.attempt']);

                        Route::controller(ManifestCheckerController::class)
                            ->group(function () {
                                Route::get('/check/{plate_no}', 'getManifestData');

                                Route::prefix('profile')
                                    ->group(function () {
                                        Route::get('/', 'getProfile');
                                        Route::post('/edit', 'editProfile');
                                    });

                                Route::prefix('incident')
                                    ->group(function () {
                                        Route::get('/get-categories', 'getIncidentCategories');
                                        Route::get('/get-types', 'getIncidentTypes');
                                        Route::get('/get-severity-levels', 'getIncidentSeverityLevels');
                                        Route::get('/get-incidents', 'getIncidents');
                                        Route::get('/get-incident/{id}', 'getIncident');
                                        Route::post('/add', 'addIncident');
                                    });

                                Route::prefix('watch-list')
                                    ->group(function () {
                                        Route::post('/add', 'addRecordToWatchList');
                                        Route::post('/update/{id}', 'updateWatchListRecord');
                                        Route::get('/get-watchlists', 'getWatchListRecords');
                                        Route::get('/get/{id}', 'getWatchListRecord');
                                        Route::post('/search', 'searchWatchList');
                                    });
                            });
                    });
            });

        Route::prefix('agent')
            ->middleware('agent.auth')
            ->controller(AgentController::class)
            ->group(function () {
                // Profile & Account Management
                Route::get('/get-profile', 'profile');
                Route::post('/update-profile', 'updateProfile');
                Route::post('/change-password', 'changePassword');
                Route::delete('/delete-account', 'deleteProfile');
                Route::get('/{agent_id}', 'getAgent')->withoutMiddleware('agent.auth');

                // Ticket & Trip Management
                Route::post('/bus-search', 'busSearch');
                Route::post('/buy-ticket', 'buyTicket');
                Route::post('/ticket/search', 'ticketSearch');
                Route::match(['get', 'post'], '/scan-ticket/{booking_id?}/{seat_no?}', 'scanTicket');

                // Passenger Management
                Route::post('/search/passenger', 'searchPassenger')->withoutMiddleware('agent.auth');
                Route::get('/{user_id}/booking-history', 'bookingHistory');
                Route::get('/booking-detail/{booking_id}', 'bookingDetail');
                Route::put('/cancel-trip/{trip_id}', 'cancelTrip');

                // Agent Information
                Route::post('/info', 'agentInfo');
                Route::post('/add-user', 'addUser');

                // Reset Pin
                Route::post('/pin/send-otp', 'sendOtp');
                Route::post('/pin/verify', 'verifyPin');
                Route::post('/pin/change', 'changePin')
                    ->middleware('verify.pin');

                // Manage driver
                Route::post('/search-driver', 'searchDriver');
                Route::post('/impersonate-driver', 'impersonateDriver')
                    ->middleware('impersonation.throttle');
                Route::post('/driver/validate-pin', 'validateDriverPin');

                // Trip
                Route::prefix('trip')
                    ->group(function () {
                        Route::post('/one-time', 'createOneTimeTrip');
                        Route::post('/recurring', 'createRecurringTrip');
                        Route::get('/{user_id}', 'getTrips');
                        Route::get('/detail/{id}', 'tripDetails');
                        Route::post('start', 'startTrip')
                            ->middleware('transaction.pin');
                        Route::patch('complete/{id}', 'completeTrip');
                        Route::post('/notify', 'notifyPassengers');
                    });

                // Bus-stops
                Route::post('/bus-stop', 'addBusStop');
                Route::get('/bus-stop/{user_id}', 'getAllBusStops');
                Route::get('{user_id}/stops/{state_id}', 'getStop');

                // Notification
                Route::patch('/notification', 'updateNotification');

                // Create Driver
                Route::prefix('driver')
                    ->controller(RegisterController::class)
                    ->group(function () {
                        Route::post('/create', 'createDriver');
                        Route::post('/verify-account', 'verifyDriverAccount');
                    });
            });

        Route::get('/send-test-mail', [SendTestMailController::class, 'sendTestMail']);

        // Get charges
        Route::get('/charges', [WalletController::class, 'getCharges']);

        Route::prefix('airline')
            ->group(function () {

                // Profile
                Route::get('/me', [AuthenticateController::class, 'me']);

                Route::middleware(['ip.whitelist', 'ip.ratelimit:30,10'])
                    ->controller(ApiKeyController::class)
                    ->group(function () {
                        // Environment
                        Route::prefix('environment')->group(function () {
                            Route::get('/', 'showCurrentEnvironment');
                            Route::patch('/', 'toggleEnvironment');
                        });

                        // API Key management
                        Route::prefix('keys')->group(function () {
                            Route::get('/', 'index');
                            Route::post('/', 'generate');
                            Route::get('/{id}', 'show');
                            Route::post('/{id}/rotate', 'rotate');
                            Route::delete('/{id}', 'revoke');
                        });

                        // Audit logs
                        Route::get('/audit-logs', 'getAudits');
                    });

                // Ip Whitelist
                Route::prefix('ip-whitelist')
                    ->controller(IpWhitelistController::class)
                    ->group(function () {
                        Route::get('/', 'index');
                        Route::post('/', 'store');
                        Route::post('/check', 'check');
                        Route::get('/{id}', 'show');
                        Route::patch('/{id}', 'update');
                        Route::delete('/{id}', 'destroy');
                        Route::patch('/{id}/toggle', 'toggle');
                    });
            });

        Route::prefix('v1')
            ->controller(AirlineController::class)
            ->middleware(['validate.api.key', 'ip.whitelist', 'ip.ratelimit:30,10'])
            ->group(function () {

                Route::get('/flights', 'getFlights');
                Route::get('/flights/{id}', 'getFlight');

                Route::prefix('tickets')
                    ->middleware(['force.production.key'])
                    ->name('tickets.')
                    ->group(function () {
                        Route::post('/', 'issueTicket');
                        Route::get('/{id}', 'getTicket');
                    });
            });
    });

Route::fallback(function () {
    return response('page not found', 400);
});

// Test mail
