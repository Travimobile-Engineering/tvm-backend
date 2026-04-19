<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Fallback order — list of mailer keys to try in priority order
    |--------------------------------------------------------------------------
    | Set MAIL_FALLBACK_ORDER in .env as a comma-separated list, e.g.:
    |   MAIL_FALLBACK_ORDER=ses,mailgun,smtp_backup
    */
    'fallback_order' => array_filter(
        array_map('trim', explode(',', env('MAIL_FALLBACK_ORDER', 'smtp,mailtrap,zoho')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT', 20),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

        'mailersend' => [
            'transport' => 'mailersend',
        ],

        'mailtrap' => [
            'transport' => 'smtp',
            'host' => env('MAILTRAP_HOST', 'sandbox.smtp.mailtrap.io'),
            'port' => env('MAILTRAP_PORT', 2525),
            'encryption' => env('MAILTRAP_ENCRYPTION', 'tls'),
            'username' => env('MAILTRAP_USERNAME'),
            'password' => env('MAILTRAP_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT', 10),
        ],

        'zoho' => [
            'transport' => 'smtp',
            'host' => env('ZOHO_MAIL_HOST', 'smtp.zoho.com'),
            'port' => env('ZOHO_MAIL_PORT', 587),
            'encryption' => env('ZOHO_MAIL_ENCRYPTION', 'tls'),
            'username' => env('ZOHO_MAIL_USERNAME'),  // your full Zoho email
            'password' => env('ZOHO_MAIL_PASSWORD'),  // app-specific password
            'timeout' => env('MAIL_TIMEOUT', 10),
            'encryption' => 'ssl',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
