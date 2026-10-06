<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as Monolog;

/**
 * Monolog "tap" registered on the application's log channels. It attaches the
 * SensitiveDataProcessor to every logger so that sensitive values are redacted
 * before a log line is written.
 */
class RedactSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof Monolog) {
            $monolog->pushProcessor(new SensitiveDataProcessor);
        }
    }
}
