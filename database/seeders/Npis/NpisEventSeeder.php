<?php

namespace Database\Seeders\Npis;

use App\Models\Npis\Hotel;
use App\Models\Npis\NotificationTemplate;
use App\Models\Npis\OrganizerAccess;
use App\Models\Npis\SummitEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NpisEventSeeder extends Seeder
{
    public function run(): void
    {
        SummitEvent::updateOrCreate(['id' => config('npis.event_id', 'npis-2026')], [
            'name' => 'Nigeria Police Intelligence Summit 2026',
            'start_date' => '2026-12-08',
            'end_date' => '2026-12-10',
            'timezone' => 'Africa/Lagos',
            'venue_name' => 'Police Resource Centre',
            'venue_address' => 'Jabi, FCT – Abuja',
            'theme' => 'Driving intelligence through technology',
            'welcome_message_title' => 'Welcome to the Nigeria Police Intelligence Summit',
            'welcome_message_author' => 'IGP Kayode Adeolu Egbetokun, Ph.D, NPM',
            'welcome_message_author_title' => 'Inspector-General of Police',
            'welcome_message_paragraphs' => [
                'On behalf of the Nigeria Police Force, I welcome you to this summit on intelligence-led policing. Your participation strengthens the partnership between the Force, the transport and hospitality sectors, regulators and the media.',
                'Over three days we will chart practical ways to turn data into safer roads, airports, waterways, rail corridors and hospitality spaces across our nation. I look forward to your contributions.',
            ],
            'welcome_message_approved_at' => '2026-11-01T09:00:00+01:00',
            'brochure_title' => 'NPIS 2026 Official e-Brochure',
            'brochure_file_name' => 'NPIS-2026-e-Brochure.pdf',
            'brochure_url' => null,
            'brochure_size_bytes' => 2480000,
            'brochure_version' => '1.2',
            'brochure_published_at' => '2026-11-05T10:00:00+01:00',
            'deadlines' => [
                'registrationClosesAt' => '2026-12-07T23:59:00+01:00',
                'travelEditDeadline' => '2026-12-07T23:59:00+01:00',
                'accommodationEditDeadline' => '2026-12-07T23:59:00+01:00',
            ],
            'policies' => [
                'approvalRequired' => true,
                'certificateMinDaysAttended' => 2,
                'travelDatesFrom' => '2026-11-01',
                'accommodationCheckInFrom' => '2026-11-01',
                'accommodationCheckOutBy' => '2026-12-13',
                'verification' => [
                    'codeLength' => 5,
                    'codeTtlSeconds' => 600,
                    'resendCooldownSeconds' => 60,
                    'maxResends' => 3,
                    'maxAttempts' => 5,
                ],
            ],
        ]);

        $access = OrganizerAccess::current();
        if (! Hash::check(config('npis.organizer.default_access_code'), $access->code_hash)) {
            $access->update(['code_hash' => Hash::make(config('npis.organizer.default_access_code'))]);
        }

        foreach ([
            ['hotel_jabi', 'Jabi Lake Partner Hotel', ['Standard single', 'Executive']],
            ['hotel_utako', 'Utako Partner Suites', ['Standard single', 'Executive']],
            ['hotel_wuse', 'Wuse II Partner Lodge', ['Standard single', 'Executive']],
        ] as [$id, $name, $rooms]) {
            Hotel::updateOrCreate(['id' => $id], ['name' => $name, 'room_types' => $rooms, 'is_active' => true]);
        }

        $templates = [
            ['verification_code', null, null, 'NPIS: Your verification code is {{code}}. It expires in {{minutes}} minutes. Police staff will never ask you for this code.'],
            ['registration_confirmation', null, null, 'NPIS: Registration received. Your Summit Tag Number is {{tag}}. Status: {{status}}. Keep this number for check-in.'],
            ['registration_approved', null, null, 'NPIS: Your registration {{tag}} has been approved. See you at the Police Resource Centre, Jabi.'],
            ['registration_rejected', null, null, 'NPIS: Your registration {{tag}} was not approved. Reason: {{reason}}. Contact the organizing committee for help.'],
            ['travel_updated', null, null, 'NPIS: Your travel itinerary for {{tag}} has been updated.'],
            ['accommodation_updated', null, null, 'NPIS: Your accommodation details for {{tag}} have been updated.'],
            ['certificate_issued', null, 'Your NPIS certificate of participation', 'NPIS: Your certificate of participation ({{certificateNumber}}) is ready. Download it from “My registration” on the summit website.'],
            ['certificate_delivery', null, 'Your NPIS certificate of participation', 'NPIS: Your certificate of participation ({{certificateNumber}}) is ready. Download it from “My registration” on the summit website.'],
        ];

        foreach ($templates as [$key, $channel, $subject, $body]) {
            NotificationTemplate::updateOrCreate(['key' => $key], [
                'channel' => $channel,
                'subject' => $subject,
                'body' => $body,
                'is_active' => true,
            ]);
        }
    }
}
