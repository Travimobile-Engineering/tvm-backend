<?php

use App\Providers\AppServiceProvider;
use App\Providers\FallbackMailServiceProvider;
use App\Providers\RouteServiceProvider;
use Unicodeveloper\Paystack\PaystackServiceProvider;

return [
    AppServiceProvider::class,
    RouteServiceProvider::class,
    PaystackServiceProvider::class,
    FallbackMailServiceProvider::class,
];
