<?php

namespace App\Providers;

use App\Services\Mail\FallbackMailer;
use Illuminate\Support\ServiceProvider;

class FallbackMailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FallbackMailer::class, fn () => new FallbackMailer);

        // Optional alias so you can resolve it as 'fallback.mailer'
        $this->app->alias(FallbackMailer::class, 'fallback.mailer');
    }

    public function boot(): void
    {
        //
    }
}
