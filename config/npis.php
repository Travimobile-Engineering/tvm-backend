<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event
    |--------------------------------------------------------------------------
    */
    'event_id' => env('NPIS_EVENT_ID', 'npis-2026'),

    /*
    |--------------------------------------------------------------------------
    | Public content caching
    |--------------------------------------------------------------------------
    | The specification caps public event, FAQ and protocol caching at five
    | minutes. Keep this value at or below 300 seconds.
    */
    'cache_ttl' => min(300, (int) env('NPIS_CACHE_TTL', 300)),

    /*
    |--------------------------------------------------------------------------
    | Verified participant sessions
    |--------------------------------------------------------------------------
    */
    'participant_session_ttl' => 7200, // two hours

    /*
    |--------------------------------------------------------------------------
    | OTP verification defaults and permitted ranges
    |--------------------------------------------------------------------------
    */
    'verification' => [
        'code_length' => 5,
        'code_ttl_seconds' => 600,
        'resend_cooldown_seconds' => 60,
        'max_resends' => 3,
        'max_attempts' => 5,
        'challenges_per_destination_per_hour' => 5,
        'challenges_per_ip_per_hour' => 20,
        'limits' => [
            'code_length' => ['min' => 4, 'max' => 8],
            'code_ttl_seconds' => ['min' => 300, 'max' => 900],
            'resend_cooldown_seconds' => ['min' => 30, 'max' => 300],
            'max_resends' => ['min' => 0, 'max' => 10],
            'max_attempts' => ['min' => 3, 'max' => 10],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Summit tag numbers
    |--------------------------------------------------------------------------
    | 32 character Crockford-like alphabet: no 0, 1, I, L, O or U. A tag is
    | four random characters followed by a positional check character.
    */
    'tag' => [
        'prefix' => 'NPIS26',
        'charset' => '23456789ABCDEFGHJKMNPQRSTVWXYZ',
        'random_length' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Organizer access
    |--------------------------------------------------------------------------
    */
    'organizer' => [
        'default_access_code' => env('NPIS_ORGANIZER_ACCESS_CODE', 'NPIS-ORG-2026'),
        'login_url' => env('NPIS_ORGANIZER_LOGIN_URL', 'https://npis.ng/#/organizer'),
        'seed_password' => env('NPIS_SEED_PASSWORD'),
        'access_grant_ttl' => 28800, // eight hours
        'admin_session_ttl' => 28800, // eight hours
        'max_failed_attempts' => 5,
        'lockout_seconds' => 900,
        'roles' => [
            'super_admin' => [
                'label' => 'Super admin',
                'permissions' => [
                    'registrations:read',
                    'registrations:review',
                    'contact:read',
                    'travel:read',
                    'accommodation:read',
                    'accommodation:allocate',
                    'training:read',
                    'training:assign',
                    'attendance:read',
                    'attendance:record',
                    'certificates:manage',
                    'exports:create',
                    'audit:read',
                    'event:manage',
                    'organizers:manage',
                ],
            ],
            'registration' => [
                'label' => 'Registration desk',
                'permissions' => [
                    'registrations:read',
                    'registrations:review',
                    'contact:read',
                    'travel:read',
                    'accommodation:read',
                    'training:read',
                    'attendance:read',
                    'certificates:manage',
                    'exports:create',
                ],
            ],
            'logistics' => [
                'label' => 'Logistics',
                'permissions' => [
                    'registrations:read',
                    'contact:read',
                    'travel:read',
                    'accommodation:read',
                    'accommodation:allocate',
                    'attendance:read',
                    'exports:create',
                ],
            ],
            'training' => [
                'label' => 'Training',
                'permissions' => [
                    'registrations:read',
                    'travel:read',
                    'training:read',
                    'training:assign',
                    'attendance:read',
                    'exports:create',
                ],
            ],
            'checkin' => [
                'label' => 'On-site check-in',
                'permissions' => [
                    'registrations:read',
                    'attendance:read',
                    'attendance:record',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OIDC / Force SSO
    |--------------------------------------------------------------------------
    | Organizer identities are validated against a trusted OIDC issuer. The
    | development secret is only used outside production for automated tests
    | and local integration. Never rely on it in production.
    */
    'oidc' => [
        'issuer' => env('NPIS_OIDC_ISSUER'),
        'audience' => env('NPIS_OIDC_AUDIENCE'),
        'jwks_url' => env('NPIS_OIDC_JWKS_URL'),
        'dev_secret' => env('NPIS_OIDC_DEV_SECRET'),
        'auto_provision' => (bool) env('NPIS_OIDC_AUTO_PROVISION', true),
        'allow_hs256' => (bool) env('NPIS_OIDC_ALLOW_HS256', false),
        'allowed_algorithms' => ['RS256', 'RS384', 'RS512', 'ES256', 'HS256'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud file storage
    |--------------------------------------------------------------------------
    | NPIS media is uploaded through the existing Cloudinary -> ImageKit
    | fallback used by the rest of the application. No local disk is used.
    */
    'storage' => [
        'folders' => [
            'photos' => 'npis/photos',
            'certificates' => 'npis/certificates',
            'exports' => 'npis/exports',
            'brochures' => 'npis/brochures',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificates
    |--------------------------------------------------------------------------
    */
    'certificate' => [
        'download_url_ttl' => 900, // fifteen minutes
        'signatures' => [
            ['name' => 'Inspector-General of Police', 'title' => 'Nigeria Police Force'],
            ['name' => 'Chairman, Organizing Committee', 'title' => 'NPIS 2026'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Private media
    |--------------------------------------------------------------------------
    */
    'media' => [
        'photo_max_bytes' => 400000,
        'photo_max_dimension' => 4000,
        'photo_ttl' => 900,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */
    'exports' => [
        'url_ttl' => 900,
        'max_rows' => 50000,
        'fields' => [
            ['key' => 'summitTagNumber', 'label' => 'Summit Tag Number'],
            ['key' => 'participantType', 'label' => 'Participant type'],
            ['key' => 'fullName', 'label' => 'Full name'],
            ['key' => 'sector', 'label' => 'Sector'],
            ['key' => 'organization', 'label' => 'Organization / company'],
            ['key' => 'designation', 'label' => 'Designation'],
            ['key' => 'vendorService', 'label' => 'Vendor service'],
            ['key' => 'protocolCategory', 'label' => 'Tag category'],
            ['key' => 'photo', 'label' => 'Tag photo'],
            ['key' => 'rank', 'label' => 'Rank'],
            ['key' => 'state', 'label' => 'State / FCT'],
            ['key' => 'command', 'label' => 'Command'],
            ['key' => 'unit', 'label' => 'Division / unit'],
            ['key' => 'status', 'label' => 'Registration status'],
            ['key' => 'phone', 'label' => 'Phone', 'permission' => 'contact:read'],
            ['key' => 'email', 'label' => 'Email', 'permission' => 'contact:read'],
            ['key' => 'travelMode', 'label' => 'Travel mode', 'permission' => 'travel:read'],
            ['key' => 'arrival', 'label' => 'Arrival (WAT)', 'permission' => 'travel:read'],
            ['key' => 'departure', 'label' => 'Departure (WAT)', 'permission' => 'travel:read'],
            ['key' => 'planStatus', 'label' => 'Plan status', 'permission' => 'travel:read'],
            ['key' => 'airport', 'label' => 'Departure airport', 'permission' => 'travel:read'],
            ['key' => 'accommodation', 'label' => 'Accommodation', 'permission' => 'accommodation:read'],
            ['key' => 'checkIn', 'label' => 'Hotel check-in', 'permission' => 'accommodation:read'],
            ['key' => 'checkOut', 'label' => 'Hotel check-out', 'permission' => 'accommodation:read'],
            ['key' => 'allowance', 'label' => 'Accommodation allowance', 'permission' => 'accommodation:read'],
            ['key' => 'payoutBank', 'label' => 'Allowance bank', 'permission' => 'accommodation:read'],
            ['key' => 'payoutAccountNumber', 'label' => 'Allowance account number', 'permission' => 'accommodation:read'],
            ['key' => 'payoutAccountName', 'label' => 'Allowance account name', 'permission' => 'accommodation:read'],
            ['key' => 'track', 'label' => 'Programme track', 'permission' => 'training:read'],
            ['key' => 'subsectors', 'label' => 'Transport subsectors', 'permission' => 'training:read'],
            ['key' => 'sessions', 'label' => 'Training sessions', 'permission' => 'training:read'],
            ['key' => 'attendance', 'label' => 'Days attended'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'sms_email_fallback' => (bool) env('NPIS_SMS_EMAIL_FALLBACK', true),
        'webhook_secret' => env('NPIS_NOTIFICATION_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protocol
    |--------------------------------------------------------------------------
    | The colour palette and zone matrix are proposals until the organizing
    | committee approves them. Values live in reference tables, never code.
    */
    'protocol' => [
        'status' => env('NPIS_PROTOCOL_STATUS', 'proposed'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit / idempotency
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'redact_keys' => [
            'password',
            'password_confirmation',
            'code',
            'otp',
            'token',
            'access_token',
            'accessToken',
            'sessionToken',
            'authorization',
            'idToken',
            'api_key',
            'secret',
            'accountNumber',
            'account_number',
            'payoutAccount',
            'payout_account_number',
            'payoutAccountNumber',
        ],
    ],

    'idempotency' => [
        'ttl' => 86400, // 24 hours
    ],
];
