<?php

namespace App\Facades;

use App\Services\Mail\FallbackMailer;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(string|array $to, \Illuminate\Mail\Mailable $mailable)
 * @method static bool queue(string|array $to, \Illuminate\Mail\Mailable $mailable)
 * @method static array getMailers()
 *
 * @see FallbackMailer
 */
class FallbackMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'fallback.mailer';
    }
}
