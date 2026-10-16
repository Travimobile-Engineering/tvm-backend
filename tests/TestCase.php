<?php

namespace Tests;

use App\Contracts\SMS;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No SMS gateway is configured outside production, so resolving the
        // SMS contract throws. Swap in a no-op implementation so the suite
        // never depends on an external provider being configured.
        $this->app->bind(SMS::class, fn () => new class implements SMS
        {
            public function sendSms(string $to, string $message): array
            {
                return ['status' => 'success'];
            }
        });
    }
}
