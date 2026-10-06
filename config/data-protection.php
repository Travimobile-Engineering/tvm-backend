<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Protection Key
    |--------------------------------------------------------------------------
    |
    | A dedicated 32 byte key (base64 encoded) used by the application's own
    | encryption layer to protect sensitive data at rest. This is separate from
    | APP_KEY so that data protection can be rotated independently.
    |
    | Generate one with: php artisan data-protection:key
    |
    */

    'key' => env('DATA_PROTECTION_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Previous Keys
    |--------------------------------------------------------------------------
    |
    | Comma separated list of retired keys. They are only used to decrypt data
    | that was encrypted before a key rotation and are never used to encrypt.
    |
    */

    'previous_keys' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DATA_PROTECTION_PREVIOUS_KEYS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Blind Index Key
    |--------------------------------------------------------------------------
    |
    | Separate key used to build deterministic blind indexes (HMAC-SHA256) for
    | searchable sensitive values such as emails and phone numbers. Falls back to
    | the data protection key, then APP_KEY. Rotating it requires re-indexing.
    |
    */

    'index_key' => env('DATA_PROTECTION_INDEX_KEY', env('DATA_PROTECTION_KEY', env('APP_KEY'))),

    /*
    |--------------------------------------------------------------------------
    | Log Redaction
    |--------------------------------------------------------------------------
    |
    | Keys whose values must never be written to the logs (matched as a
    | case-insensitive whole segment of the context key), plus the placeholder
    | used in their place. Email addresses found inside log messages are masked.
    |
    */

    'redaction' => [
        'replacement' => '[REDACTED]',
        'keys' => [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key',
            'apikey',
            'authorization',
            'nin',
            'bvn',
            'email',
            'first_name',
            'last_name',
            'full_name',
            'phone',
            'phone_number',
            'next_of_kin',
            'next_of_kin_full_name',
            'next_of_kin_phone_number',
            'address',
            'residential_address',
            'security_answer',
            'pin',
            'txn_pin',
            'otp',
            'verification_code',
            'referral_code',
            'lng',
            'lat',
            'date_of_birth',
            'dob',
            'account_number',
            'account_name',
            'card_number',
            'cvv',
            'cvc',
            'recipient_code',
            'bank_name',
            'serial_number',
        ],
    ],

];
