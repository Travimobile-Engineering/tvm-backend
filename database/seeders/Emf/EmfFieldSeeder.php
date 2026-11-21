<?php

namespace Database\Seeders\Emf;

use App\Models\Emf\Alert;
use App\Models\Emf\FieldCheck;
use App\Models\Emf\FieldCheckSeat;
use App\Models\Emf\FieldManifestEvent;
use App\Models\Emf\Incident;
use App\Models\Emf\IncidentAttachment;
use App\Models\Emf\IncidentEvent;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestPassenger;
use App\Models\Emf\Officer;
use App\Models\Emf\SupportRequest;
use Illuminate\Database\Seeder;

class EmfFieldSeeder extends Seeder
{
    public function run(): void
    {
        $officer = Officer::query()->find('off_ada');

        if ($officer) {
            $officer->forceFill([
                'nin' => '98765432109',
                'division' => 'Manifest Enforcement',
                'reports_to' => 'CSP Ada Nwosu',
                'ops_room' => '+234 803 000 0099',
                'photo_url' => 'https://i.pravatar.cc/160?img=47',
                'password_changed_at' => now()->subMonths(3),
                'two_factor_enabled' => true,
                'biometric_enabled' => true,
                'theme' => 'system',
                'field_notification_preferences' => [
                    'watchlistHitsOnRoute' => true,
                    'busApproachingMyPost' => true,
                    'corridorAPBs' => true,
                    'reportStatusChanges' => true,
                    'approvals' => true,
                    'shiftEndingReminder' => true,
                    'appUpdates' => false,
                ],
            ])->save();
        }

        Manifest::query()->whereNotNull('checked_at')->update(['arrival_status' => 'checked']);

        $upcoming = [
            [
                'id' => 'mf_field_001', 'number' => 'MF-26-91001', 'plate' => 'AKW 412 UY', 'operator' => 'Toyota Hiace',
                'origin' => 'Mile 3 Park, Lagos', 'destination' => 'Utako, Abuja', 'driver' => 'Sunday Pepple',
                'status' => 'in_transit', 'departed' => now()->subHours(3), 'eta' => now()->addHour(),
            ],
            [
                'id' => 'mf_field_002', 'number' => 'MF-26-91002', 'plate' => 'PHC 482 XA', 'operator' => 'Toyota Hiace',
                'origin' => 'Mile 3, Utako', 'destination' => 'Eket Bus Park, Lagos', 'driver' => 'Sunday Pepple',
                'status' => 'scheduled', 'departed' => now()->subMinutes(40), 'eta' => now()->addHours(2),
            ],
            [
                'id' => 'mf_field_003', 'number' => 'MF-26-91003', 'plate' => 'RIV 221 KD', 'operator' => 'White Stenna',
                'origin' => 'Eket Park', 'destination' => 'Port Harcourt', 'driver' => 'Emeka Obi',
                'status' => 'arrived', 'departed' => now()->subHours(1), 'eta' => now(),
            ],
        ];

        $names = ['Emeka Nwosu', 'Ngozi Eze', 'Chiamaka Okafor', 'Abubakar S. Bello', 'Hauwa Lawal', 'Musa Ibrahim', 'Blessing Amadi', 'Ifeanyi Obi', 'Funmi Adeyemi', 'Kelechi Opara', 'Daniel Ekpo', 'Precious Tamuno', 'Bleire Ibiere', 'Ngozi Okafor'];

        foreach ($upcoming as $index => $trip) {
            $manifest = Manifest::query()->updateOrCreate(['id' => $trip['id']], [
                'manifest_number' => $trip['number'],
                'mode' => 'road',
                'operator' => $trip['operator'],
                'vehicle_registration' => $trip['plate'],
                'driver_name' => $trip['driver'],
                'route_origin' => $trip['origin'],
                'route_destination' => $trip['destination'],
                'seats' => 14,
                'passenger_count' => 14,
                'checked_count' => 0,
                'duty_point_id' => 'dp_lekki',
                'state_code' => 'LA',
                'lga_id' => 'lga_la_2',
                'verdict' => 'unverified',
                'arrival_status' => $trip['status'],
                'departed_at' => $trip['departed'],
                'expected_arrival_at' => $trip['eta'],
                'source' => 'operator_api',
            ]);

            $manifest->passengers()->delete();
            foreach (array_slice($names, 0, 14) as $seat => $name) {
                $flagged = $index === 0 && $seat === 3;
                ManifestPassenger::create([
                    'id' => 'fpax_'.$trip['id'].'_'.$seat,
                    'manifest_id' => $manifest->id,
                    'full_name' => $flagged ? 'Ibrahim Danjuma' : $name,
                    'nin' => (string) (22345678900 + ($seat + 1)),
                    'seat' => str_pad((string) ($seat + 1), 2, '0', STR_PAD_LEFT),
                    'verdict' => $flagged ? 'flagged' : 'clear',
                    'sex' => $seat % 3 === 0 ? 'F' : 'M',
                    'dob' => now()->subYears(25 + $seat)->toDateString(),
                    'phone' => '+234803111'.str_pad((string) $seat, 4, '0', STR_PAD_LEFT),
                    'photo_url' => 'https://i.pravatar.cc/96?img='.(10 + $seat),
                    'nin_record' => [
                        'name' => $flagged ? 'Ibrahim Danjuma' : $name,
                        'dob' => now()->subYears(25 + $seat)->toDateString(),
                        'sex' => $seat % 3 === 0 ? 'F' : 'M',
                        'phone' => '+234803111'.str_pad((string) $seat, 4, '0', STR_PAD_LEFT),
                    ],
                    'boarding_point' => $trip['origin'],
                    'checked_at' => null,
                ]);
            }

            FieldManifestEvent::query()->where('manifest_id', $manifest->id)->delete();
            FieldManifestEvent::create([
                'manifest_id' => $manifest->id,
                'event_type' => 'departure',
                'location' => $trip['origin'],
                'logged_at' => $trip['departed'],
                'source' => 'manual',
                'note' => 'Left the park with 14 passengers listed.',
            ]);
            if ($index === 0) {
                FieldManifestEvent::create([
                    'manifest_id' => $manifest->id,
                    'event_type' => 'checkpoint',
                    'location' => 'Eket Junction post',
                    'logged_at' => now()->subMinutes(50),
                    'source' => 'manual',
                    'note' => 'Last logged at Eket Junction. No GPS; arrival depends on the next checkpoint log.',
                ]);
            }
        }

        $checks = [
            ['fchk_001', 'CHK-LA-1119-0412', 'mf_001', 'cleared', 'CLR-1119-0412', now()->subHours(4), now()->subHours(3), null],
            ['fchk_002', 'CHK-LA-1119-0520', 'mf_002', 'ops_takeover', null, now()->subHours(3), now()->subHours(2), 'inc_002'],
            ['fchk_003', 'CHK-LA-1119-0602', 'mf_003', 'cleared', 'CLR-1119-0602', now()->subHours(2), now()->subHour(), null],
        ];

        foreach ($checks as [$id, $reference, $manifestId, $status, $clearance, $started, $completed, $incidentId]) {
            $manifest = Manifest::query()->with('passengers')->find($manifestId);
            if (! $manifest) {
                continue;
            }

            $check = FieldCheck::query()->updateOrCreate(['id' => $id], [
                'check_reference' => $reference,
                'manifest_id' => $manifest->id,
                'officer_id' => 'off_ada',
                'duty_point_id' => $manifest->duty_point_id,
                'status' => $status,
                'started_at' => $started,
                'completed_at' => $completed,
                'clearance_reference' => $clearance,
                'incident_id' => $incidentId,
                'released_at' => (string) $status === 'ops_takeover' ? $completed : null,
                'vehicle_cleared' => (string) $status === 'cleared',
                'listed_count' => $manifest->passengers->count(),
                'metadata' => ['source' => 'seed'],
            ]);

            FieldCheckSeat::query()->where('field_check_id', $check->id)->delete();
            foreach ($manifest->passengers as $index => $passenger) {
                $seatStatus = 'present';
                if ((string) $status === 'ops_takeover' && $index === 0) {
                    $seatStatus = 'escalated';
                } elseif ((string) $status === 'cleared' && $index === 1) {
                    $seatStatus = 'cleared_with_note';
                }

                FieldCheckSeat::create([
                    'id' => 'fs_'.$id.'_'.$index,
                    'field_check_id' => $check->id,
                    'manifest_passenger_id' => $passenger->id,
                    'seat' => $passenger->seat,
                    'status' => $seatStatus,
                    'note' => $seatStatus === 'cleared_with_note' ? 'Photo verified by field officer.' : null,
                    'identity_snapshot' => ['name' => $passenger->full_name],
                    'confirmed_at' => $completed,
                    'flagged_at' => $seatStatus === 'escalated' ? $started->copy()->addMinutes(10) : null,
                    'resolved_at' => $seatStatus === 'cleared_with_note' ? $completed : null,
                    'resolved_by' => $seatStatus === 'cleared_with_note' ? 'off_ada' : null,
                ]);
            }

            $check->forceFill([
                'confirmed_count' => (string) $status === 'cleared' ? $manifest->passengers->count() : $manifest->passengers->count() - 1,
                'flagged_count' => (string) $status === 'ops_takeover' ? 1 : 0,
                'cleared_with_note_count' => (string) $status === 'cleared' ? 1 : 0,
            ])->save();
        }

        Alert::query()->whereIn('id', ['al_001', 'al_002', 'al_003', 'al_004'])->update([
            'match_rule' => 'Exact NIN + name · High risk',
            'boarding_point' => 'Mile 3 Park, Lagos',
        ]);
        Alert::query()->where('id', 'al_002')->update(['field_outcome' => 'interrupted']);
        Alert::query()->where('id', 'al_003')->update(['field_outcome' => 'cleared']);

        $reports = [
            ['inc_field_001', 'INC-2026-0842', 'Watchlist passenger held at checkpoint', 'watchlist_hit', 'high', 'escalated', 'AKW 412 UY', 'mf_field_001', 3, true],
            ['inc_field_002', 'INC-2026-0839', "Passenger didn't match NIN photo", 'document_nin_fraud', 'high', 'under_investigation', 'PHC 482 XA', 'mf_002', 2, true],
            ['inc_field_003', 'INC-2026-0837', 'Name mismatch cleared with note', 'document_nin_fraud', 'medium', 'signing', 'PHC 117 BD', 'mf_003', 1, false],
            ['inc_field_004', 'INC-2026-0836', 'Existing vehicle intercepted at Bori', 'vehicle_evaded_stop', 'low', 'closed', 'RIV 551 AA', 'mf_004', 4, true],
        ];

        foreach ($reports as [$id, $number, $title, $category, $severity, $state, $vehicle, $manifestId, $seat, $signed]) {
            $report = Incident::query()->updateOrCreate(['id' => $id], [
                'incident_number' => $number,
                'title' => $title,
                'type' => $category,
                'category' => $category,
                'severity' => $severity,
                'status' => $state === 'closed' ? 'resolved' : ($state === 'escalated' ? 'in_progress' : 'acknowledged'),
                'report_state' => $state,
                'summary' => 'Report filed from the NTEM Field app at Ogoni Checkpoint.',
                'manifest_id' => $manifestId,
                'location' => 'Ogoni Checkpoint · East-West Road',
                'duty_point_id' => 'dp_lekki',
                'state_code' => 'LA',
                'lga_id' => 'lga_la_2',
                'reporter_name' => 'Adaemu Y. Bello',
                'reported_by' => 'off_ada',
                'reported_at' => now()->subHours(6),
                'occurred_at' => now()->subHours(6),
                'signed_at' => $signed ? now()->subHours(5) : null,
                'signed_by' => $signed ? 'off_ada' : null,
                'linked_vehicle' => $vehicle,
                'client_report_id' => 'local-'.$id,
                'gps_latitude' => 4.8156,
                'gps_longitude' => 7.0498,
                'metadata' => ['seat' => $seat, 'offline' => false],
                'resolved_at' => $state === 'closed' ? now()->subHours(2) : null,
            ]);

            IncidentEvent::query()->where('incident_id', $report->id)->delete();
            IncidentEvent::create([
                'incident_id' => $report->id,
                'event_type' => 'created',
                'description' => 'Report filed by Adaemu Y. Bello.',
                'actor_name' => 'Adaemu Y. Bello',
            ]);
            IncidentEvent::create([
                'incident_id' => $report->id,
                'event_type' => $state === 'escalated' ? 'escalated' : 'note',
                'description' => $state === 'escalated' ? 'Escalated to the Rivers State Command Ops Room.' : 'Reviewed by the duty supervisor.',
                'actor_name' => 'Adaemu Y. Bello',
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ]);

            IncidentAttachment::query()->where('incident_id', $report->id)->delete();
            IncidentAttachment::create([
                'id' => 'fatt_'.$id.'_1',
                'incident_id' => $report->id,
                'attachment_type' => 'photo',
                'file_url' => 'https://res.cloudinary.com/demo/image/upload/v1/emf/reports/evidence-'.$id.'.jpg',
                'file_size' => 182647,
                'uploaded_by' => 'off_ada',
            ]);
            if ($id === 'inc_field_001') {
                IncidentAttachment::create([
                    'id' => 'fatt_'.$id.'_2',
                    'incident_id' => $report->id,
                    'attachment_type' => 'voice_note',
                    'file_url' => 'https://res.cloudinary.com/demo/video/upload/v1/emf/reports/voice-'.$id.'.m4a',
                    'file_size' => 96112,
                    'duration_seconds' => 34,
                    'uploaded_by' => 'off_ada',
                ]);
            }
        }

        SupportRequest::query()->updateOrCreate(['id' => 'sup_001'], [
            'officer_id' => 'off_ada',
            'request_type' => 'app_problem',
            'subject' => 'Offline sync delayed',
            'message' => 'Two reports queued while I was offline and synced after 10 minutes.',
            'app_version' => '1.0',
            'device_info' => ['device' => 'Tecno Spark 10', 'os' => 'Android 14'],
            'status' => 'open',
        ]);
    }
}
