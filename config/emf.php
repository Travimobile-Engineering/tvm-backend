<?php

return [
    'session_ttl' => 28800, // eight hours

    'roles' => [
        'state_commander' => [
            'label' => 'State Commander',
            'permissions' => [
                'dashboard:read', 'alerts:read', 'alerts:respond', 'manifests:read', 'manifests:check',
                'watchlist:read', 'watchlist:manage', 'incidents:read', 'incidents:manage',
                'cases:read', 'cases:manage', 'officers:read', 'officers:manage', 'grants:manage',
                'duty:read', 'duty:manage', 'analytics:read', 'audit:read', 'settings:manage',
                'exports:create',
            ],
        ],
        'ops_officer' => [
            'label' => 'Ops Officer',
            'permissions' => [
                'dashboard:read', 'alerts:read', 'alerts:respond', 'manifests:read', 'manifests:check',
                'watchlist:read', 'incidents:read', 'incidents:manage', 'cases:read', 'cases:manage',
                'officers:read', 'duty:read', 'analytics:read', 'exports:create',
            ],
        ],
        'duty_officer' => [
            'label' => 'Duty Officer',
            'permissions' => [
                'dashboard:read', 'alerts:read', 'alerts:respond', 'manifests:read', 'manifests:check',
                'watchlist:read', 'incidents:read', 'incidents:manage', 'cases:read', 'duty:read',
            ],
        ],
        'analyst' => [
            'label' => 'Analyst',
            'permissions' => [
                'dashboard:read', 'manifests:read', 'watchlist:read', 'incidents:read',
                'cases:read', 'analytics:read', 'audit:read', 'exports:create',
            ],
        ],
        'admin' => [
            'label' => 'System Admin',
            'permissions' => [
                'dashboard:read', 'alerts:read', 'alerts:respond', 'manifests:read', 'manifests:check',
                'watchlist:read', 'watchlist:manage', 'incidents:read', 'incidents:manage',
                'cases:read', 'cases:manage', 'officers:read', 'officers:manage', 'grants:manage',
                'duty:read', 'duty:manage', 'analytics:read', 'audit:read', 'settings:manage',
                'exports:create',
            ],
        ],
    ],

    'modes' => [
        'road' => 'Road',
        'sea' => 'Sea',
        'air' => 'Air',
        'rail' => 'Rail',
        'hospitality' => 'Hospitality',
    ],

    'verdicts' => ['clear', 'flagged', 'mismatch', 'unverified'],
    'alert_types' => ['critical', 'flagged', 'mismatch', 'unverified'],
    'risk_levels' => ['critical', 'high', 'medium', 'low'],
    'severities' => ['critical', 'high', 'medium', 'low'],
    'case_statuses' => ['open', 'in_review', 'awaiting_approval', 'closed'],
    'duty_point_types' => ['motor_park', 'jetty', 'airport', 'rail', 'hotel', 'other'],

    'provisioning' => [
        'invitation_ttl' => 172800,
        'temporary_password_ttl' => 86400,
    ],

    'field' => [
        'ops_room_phone' => env('EMF_OPS_ROOM_PHONE', '+234 803 000 0000'),
        'flag_reasons' => [
            'photo_mismatch' => 'Photo mismatch',
            'name_mismatch' => 'Name differs from NIN record',
            'watchlist_hit' => 'Watchlist match',
            'unverified_id' => 'Unverified ID',
            'suspected_impersonation' => 'Suspected impersonation',
            'other' => 'Other',
        ],
        'escalation_categories' => [
            'watchlist_hit' => 'Watchlist / wanted hit',
            'suspected_impersonation' => 'Suspected impersonation / fake ID',
            'trafficking' => 'Trafficking / minor without guardian',
            'refusal' => 'Refusal to cooperate / hostile',
            'kidnapping' => 'Suspected kidnapping / duress',
            'contraband' => 'Contraband / security threat',
        ],
        'escalation_actions' => [
            'vehicle_detained' => 'Vehicle detained at checkpoint',
            'subject_isolated' => 'Subject isolated for questioning',
            'vehicle_fled' => 'Vehicle fled / evaded stop',
        ],
    ],
];
