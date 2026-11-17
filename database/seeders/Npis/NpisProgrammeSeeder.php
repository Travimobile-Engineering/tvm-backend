<?php

namespace Database\Seeders\Npis;

use App\Models\Npis\Faq;
use App\Models\Npis\TrainingSession;
use Illuminate\Database\Seeder;

class NpisProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = [
            ['ses_road_1', 'Road Transport Intelligence & the E-Manifest System', 'transportation', 'road', '2026-12-08', '14:00', '16:00', 'Hall A', 60],
            ['ses_road_2', 'Highway Patrol Data-Sharing Workshop', 'transportation', 'road', '2026-12-09', '10:00', '12:00', 'Hall B', 40],
            ['ses_air_1', 'Aviation Security & Passenger Manifests', 'transportation', 'air', '2026-12-09', '10:00', '12:00', 'Hall C', 35],
            ['ses_sea_1', 'Maritime & Inland Waterways Intelligence', 'transportation', 'sea', '2026-12-09', '14:00', '16:00', 'Hall C', 25],
            ['ses_rail_1', 'Rail Corridor Security', 'transportation', 'rail', '2026-12-10', '10:00', '12:00', 'Hall B', 25],
            ['ses_hosp_1', 'Guest Registration, NIN & Digital Footprints', 'hospitality', null, '2026-12-08', '14:00', '16:00', 'Hall B', 50],
            ['ses_hosp_2', 'Hotel & Short-let Intelligence Partnerships', 'hospitality', null, '2026-12-10', '10:00', '12:00', 'Hall A', 45],
        ];

        foreach ($sessions as [$id, $title, $track, $subsector, $date, $start, $end, $room, $capacity]) {
            TrainingSession::updateOrCreate(['id' => $id], [
                'event_id' => config('npis.event_id', 'npis-2026'),
                'title' => $title,
                'track' => $track,
                'subsector' => $subsector,
                'session_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'room' => $room,
                'capacity' => $capacity,
            ]);
        }

        $faqs = [
            ['registration', 'Who should register for the summit?', "Delegates: officers nominated by their command or formation, and invited stakeholders from security agencies, government, the transport and hospitality sectors and the media.\n\nVendors: anyone providing a service to delegates during the summit, such as ushers, caterers, cleaners, security guards, photographers, videographers, journalists, drivers and decorators."],
            ['registration', 'When does registration close?', 'Registration closes at 23:59 West Africa Time on Monday, 7 December 2026 — the day before the summit begins.'],
            ['registration', "I didn't receive my verification code. What should I do?", "Codes usually arrive within a minute. Check that the number or email shown on screen is correct, then wait for the resend timer and tap “Resend code”.\n\nIf SMS is slow on your network, choose “Change” and verify by email instead."],
            ['registration', 'I registered on the old form. Do I need to register again?', "No. Verify the phone number or email you used before and we'll find your earlier registration. Complete the remaining details — your Summit Tag Number stays the same."],
            ['registration', 'What is a Summit Tag Number?', 'A unique number like NPIS26-K7Q4M that identifies your registration. Vendor tags include a V, like NPIS26-V-K7Q4M. Keep it safe — you will need it for check-in.'],
            ['travel', "My travel plans aren't confirmed yet. Can I still register?", 'Yes. Mark your arrival and departure as undecided or a preference. You can update them later until the travel edit deadline.'],
            ['accommodation', 'Will the organizers provide accommodation?', 'Organizer-arranged accommodation is available to delegates outside the Nigeria Police Force. Police officers attend under command arrangements; vendors are not provided accommodation.'],
            ['programme', 'How are training sessions assigned?', 'Your programme interests help the training desk plan sessions. A confirmed assignment appears in “My registration” and on your schedule.'],
            ['check_in', 'How do I check in at the venue?', 'Show the QR code from “My registration” (or a saved image of it) at the check-in desk, or tell the desk your Summit Tag Number. Bring your police ID card or another valid photo ID. Check in on each day you attend.'],
            ['general', 'How do I get my certificate of participation?', 'Certificates are issued after the summit to approved delegates who checked in on at least two of the three days. You will receive a message, and you can download it from “My registration”.'],
            ['registration', "I'm providing a service at the summit. How do I register?", 'Choose “Vendor” after verifying your phone or email, then tell us the service you will provide. You will receive a vendor tag (it includes a V, like NPIS26-V-K7Q4M) to show at check-in. Accommodation is not provided for vendors.'],
        ];

        foreach ($faqs as $index => [$category, $question, $answer]) {
            $faqId = 'faq_'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

            Faq::withTrashed()->updateOrCreate(
                ['id' => $faqId],
                [
                    'event_id' => config('npis.event_id', 'npis-2026'),
                    'question' => $question,
                    'answer' => $answer,
                    'category' => $category,
                    'sort_order' => $index + 1,
                    'published' => true,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
