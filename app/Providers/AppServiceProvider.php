<?php

namespace App\Providers;

use App\Contracts\SMS;
use App\Models\TripBooking;
use App\Observers\TripBookingObserver;
use App\Services\SMS\SmsServiceFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        TripBooking::observe(TripBookingObserver::class);
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
        URL::formatScheme('https');
    }
}
