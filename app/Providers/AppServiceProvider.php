<?php

namespace App\Providers;

use App\Auth\BlindIndexUserProvider;
use App\Contracts\SMS;
use App\Models\TripBooking;
use App\Models\User;
use App\Observers\TripBookingObserver;
use App\Observers\UserObserver;
use App\Services\DataProtection\DataProtector;
use App\Services\SMS\SmsServiceFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SMS::class, function ($app) {
            $provider = config('services.sms.default');

            return SmsServiceFactory::make($provider);
        });

        $this->app->singleton(DataProtector::class, function (): DataProtector {
            $previousKeys = array_values(array_map(
                'strval',
                (array) config('data-protection.previous_keys', [])
            ));

            return new DataProtector(
                (string) config('data-protection.key'),
                $previousKeys,
                (string) config('data-protection.index_key', ''),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Encrypted email/phone columns are matched via their blind index so
        // Auth::attempt() (e.g. the JWT "agent" guard) keeps working.
        Auth::provider('blind-index', function ($app, array $config) {
            return new BlindIndexUserProvider($app['hash'], $config['model']);
        });

        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        RateLimiter::for('apis', function (Request $request) {
            return $request->user() ?
                Limit::perMinute(60)->by($request->ip())
                : Limit::perMinute(20)->by($request->ip());
        });

        $this->configureModels();
        $this->configureUrl();

        // Register observers
        TripBooking::observe(TripBookingObserver::class);
        User::observe(UserObserver::class);
    }

    /**
     * Configure the application's models.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict();
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();
    }

    /**
     * Configure the application's URL.
     */
    private function configureUrl(): void
    {
        if ($this->app->environment('production')) {
            URL::formatScheme('https');
        }
    }
}
