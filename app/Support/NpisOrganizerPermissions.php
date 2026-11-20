<?php

namespace App\Support;

/**
 * Catalogue of organizer permissions used by the Users & Roles screen.
 *
 * Role defaults live in config/npis.php; this class only supplies the human
 * readable labels, descriptions and grouping for the permission picker.
 */
final class NpisOrganizerPermissions
{
    /**
     * @var array<string, array{label: string, description: string, group: string}>
     */
    public const array CATALOG = [
        'registrations:read' => [
            'label' => 'View participant records',
            'description' => 'Open participant records and search the registration list.',
            'group' => 'Registrations',
        ],
        'registrations:review' => [
            'label' => 'Approve registrations and manage tags/photos',
            'description' => 'Approve or reject registrations and manage badge categories and tag photos.',
            'group' => 'Registrations',
        ],
        'contact:read' => [
            'label' => 'View contact details',
            'description' => 'See verified phone numbers and email addresses.',
            'group' => 'Registrations',
        ],
        'travel:read' => [
            'label' => 'View travel plans',
            'description' => 'See itineraries, arrival and departure dates and windows.',
            'group' => 'Travel',
        ],
        'accommodation:read' => [
            'label' => 'View accommodation and allowance requests',
            'description' => 'See stay dates, allocation status and allowance requests.',
            'group' => 'Accommodation',
        ],
        'accommodation:allocate' => [
            'label' => 'Allocate accommodation',
            'description' => 'Assign hotels and room types to delegates.',
            'group' => 'Accommodation',
        ],
        'training:read' => [
            'label' => 'View training interests and sessions',
            'description' => 'See programme tracks, session capacity and rosters.',
            'group' => 'Training',
        ],
        'training:assign' => [
            'label' => 'Assign participants to training',
            'description' => 'Create sessions and assign or remove delegates.',
            'group' => 'Training',
        ],
        'attendance:read' => [
            'label' => 'View attendance',
            'description' => 'See check-in records by summit day.',
            'group' => 'Attendance and certificates',
        ],
        'attendance:record' => [
            'label' => 'Record check-ins',
            'description' => 'Record on-site check-ins by tag number or QR.',
            'group' => 'Attendance and certificates',
        ],
        'certificates:manage' => [
            'label' => 'Issue certificates and retry delivery',
            'description' => 'Issue certificates and retry failed deliveries.',
            'group' => 'Attendance and certificates',
        ],
        'exports:create' => [
            'label' => 'Export participant records',
            'description' => 'Create CSV exports of registrations.',
            'group' => 'Reporting',
        ],
        'audit:read' => [
            'label' => 'View audit trail',
            'description' => 'Read organizer activity logs.',
            'group' => 'Governance',
        ],
        'event:manage' => [
            'label' => 'Manage event settings and FAQs',
            'description' => 'Update event settings, deadlines, policies and FAQs.',
            'group' => 'Governance',
        ],
        'organizers:manage' => [
            'label' => 'Manage organizers',
            'description' => 'Create organizer accounts and assign roles and permissions.',
            'group' => 'Governance',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::CATALOG);
    }

    /**
     * @return list<array{key: string, label: string, description: string, group: string}>
     */
    public static function catalog(): array
    {
        return array_map(
            fn (string $key, array $permission): array => ['key' => $key, ...$permission],
            array_keys(self::CATALOG),
            array_values(self::CATALOG),
        );
    }

    /**
     * @return list<array{value: string, label: string, permissions: list<string>}>
     */
    public static function roles(): array
    {
        $roles = config('npis.organizer.roles', []);

        return array_map(
            fn (string $value, array $role): array => [
                'value' => $value,
                'label' => (string) ($role['label'] ?? $value),
                'permissions' => array_values((array) ($role['permissions'] ?? [])),
            ],
            array_keys((array) $roles),
            array_values((array) $roles),
        );
    }
}
