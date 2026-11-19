<?php

namespace Database\Seeders\Emf;

use App\Models\Emf\Alert;
use App\Models\Emf\AlertAction;
use App\Models\Emf\CaseEvent;
use App\Models\Emf\CaseEvidence;
use App\Models\Emf\CaseFile;
use App\Models\Emf\Incident;
use App\Models\Emf\IncidentEvent;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestCheck;
use App\Models\Emf\ManifestPassenger;
use App\Models\Emf\WatchlistEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmfOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $watchlist = [
            ['wl_001', 'WL-26-0001', 'person', 'critical', 'Armed robbery suspect', 'NPF CID', 'Adaemu Y. Bello', '12345678901', '+2348031110001', null, '1991-04-12', '14 Adeola Odeku Street, Victoria Island, Lagos', 30],
            ['wl_002', 'WL-26-0002', 'vehicle', 'high', 'Reported stolen vehicle', 'Lagos State VIO', null, null, null, 'LSD-482-XY', null, null, 45],
            ['wl_003', 'WL-26-0003', 'person', 'high', 'Person of interest — impersonation', 'NPF Interpol Desk', 'Musa Danjuma', '22345678902', '+2348031110003', null, '1987-09-22', 'Sabon Gari, Kano', 60],
            ['wl_004', 'WL-26-0004', 'phone', 'medium', 'Phone linked to kidnap ransom calls', 'NPF Cybercrime', null, null, '+2348092220044', null, null, null, 20],
            ['wl_005', 'WL-26-0005', 'person', 'critical', 'Escaped suspect — armed', 'NPF Custody', 'Chinedu Okoro', '32345678903', '+2348031110005', null, '1994-01-04', 'Awka South, Anambra', 15],
            ['wl_006', 'WL-26-0006', 'person', 'medium', 'Immigration watch — document fraud', 'Nigeria Immigration Service', 'Amina Suleiman', '42345678904', '+2348031110006', null, '1998-06-18', 'Wuse 2, Abuja', 90],
            ['wl_007', 'WL-26-0007', 'organization', 'high', 'Unlicensed transport operator', 'Lagos State Ministry of Transport', null, null, null, null, null, null, 120],
            ['wl_008', 'WL-26-0008', 'vehicle', 'medium', 'Vehicle linked to fuel diversion', 'NPF Marine Command', null, null, null, 'KJA-211-AA', null, null, 30],
            ['wl_009', 'WL-26-0009', 'person', 'low', 'Material witness — travel monitoring', 'NPF Legal', 'Bisi Ogunleye', '52345678905', '+2348031110009', null, '1985-12-02', 'Ikeja GRA, Lagos', 180],
            ['wl_010', 'WL-26-0010', 'phone', 'high', 'Phone associated with trafficking network', 'NAPTIP', null, null, '+2348092220100', null, null, null, 10],
        ];

        foreach ($watchlist as [$id, $number, $type, $risk, $reason, $source, $name, $nin, $phone, $plate, $dob, $address, $days]) {
            WatchlistEntry::query()->updateOrCreate(['id' => $id], [
                'entry_number' => $number,
                'type' => $type,
                'risk' => $risk,
                'reason' => $reason,
                'source' => $source,
                'full_name' => $name,
                'nin' => $nin,
                'phone' => $phone,
                'plate_number' => $plate,
                'dob' => $dob,
                'nationality' => 'Nigerian',
                'address' => $address,
                'expires_at' => now()->addDays($days),
                'status' => $id === 'wl_009' ? 'cleared' : 'active',
                'notes' => 'Seeded demo record for the Manifest Checker operations room.',
                'created_by' => 'off_ada',
            ]);
        }

        $names = [
            'John Adewale', 'Ngozi Okafor', 'Emeka Obi', 'Fatima Bello', 'Tunde Balogun',
            'Amina Musa', 'Chidi Nwosu', 'Blessing Eze', 'Ibrahim Sani', 'Grace Adeyemi',
            'Samuel Ogun', 'Halima Yakubu', 'Peter Ekong', 'Joy Aluko', 'Abdulahi Garba',
            'Rita Umeh', 'Kunle Ajayi', 'Zainab Lawal', 'Daniel Etim', 'Mercy Chukwu',
        ];
        $operators = [
            'road' => ['ABC Transport', 'Chisco Transport', 'God Is Good Motors', 'Young Shall Grow'],
            'sea' => ['LagFerry', 'City Ferry Services', 'NSWC Marine'],
            'air' => ['Air Peace', 'Arik Air', 'Ibom Air', 'United Nigeria'],
            'rail' => ['Nigeria Railway Corporation', 'Lagos Rail Mass Transit'],
            'hospitality' => ['Eko Hotels', 'Radisson Blu', 'Lagos Continental'],
        ];
        $routes = [
            'road' => [['Lagos', 'Ibadan'], ['Lagos', 'Abuja'], ['Ikeja', 'Lekki'], ['Ojota', 'Benin']],
            'sea' => [['Apapa', 'Tarkwa Bay'], ['Lagos', 'Badagry'], ['Marina', 'Ikorodu']],
            'air' => [['Lagos', 'Abuja'], ['Lagos', 'Port Harcourt'], ['Lagos', 'Kano']],
            'rail' => [['Ibadan', 'Lagos'], ['Lagos', 'Ibadan']],
            'hospitality' => [['Lekki', 'Victoria Island'], ['Ikeja', 'Lekki']],
        ];
        $modes = ['road', 'sea', 'air', 'rail', 'hospitality'];
        $points = ['dp_lekki', 'dp_mile2', 'dp_ojota', 'dp_airport', 'dp_apapa'];
        $officers = ['off_ada', 'off_grace', 'off_tunde', 'off_ibrahim', 'off_ngozi'];

        for ($i = 1; $i <= 30; $i++) {
            $mode = $modes[($i - 1) % count($modes)];
            $route = $routes[$mode][($i - 1) % count($routes[$mode])];
            $operator = $operators[$mode][($i - 1) % count($operators[$mode])];
            $id = 'mf_'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $checkedAt = now()->subMinutes(($i * 37) + 8);
            $passengerCount = 5 + ($i % 4);
            $manifest = Manifest::query()->updateOrCreate(['id' => $id], [
                'manifest_number' => 'MF-26-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'mode' => $mode,
                'operator' => $operator,
                'vehicle_registration' => $mode === 'road' ? 'LSD-'.(400 + $i).'-XY' : null,
                'driver_name' => $names[($i + 3) % count($names)],
                'route_origin' => $route[0],
                'route_destination' => $route[1],
                'seats' => $mode === 'air' ? 120 : 18,
                'passenger_count' => $passengerCount,
                'checked_count' => $passengerCount,
                'duty_point_id' => $points[($i - 1) % count($points)],
                'state_code' => 'LA',
                'lga_id' => $i % 2 === 0 ? 'lga_la_2' : 'lga_la_1',
                'verdict' => 'clear',
                'departed_at' => $checkedAt->copy()->subMinutes(20),
                'expected_arrival_at' => $checkedAt->copy()->addHours(2),
                'checked_by_officer_id' => $officers[($i - 1) % count($officers)],
                'checked_at' => $checkedAt,
                'source' => 'manual',
                'notes' => null,
            ]);

            $manifest->passengers()->delete();
            $manifest->checks()->delete();
            $highest = 'clear';

            for ($p = 0; $p < $passengerCount; $p++) {
                $isMatch = $i % 9 === 0 && $p === 1;
                $missingNin = ! $isMatch && ($i + $p) % 7 === 0;
                $name = $isMatch ? 'Adaemu Y. Bello' : $names[($i + $p) % count($names)];
                $nin = $isMatch ? '12345678901' : ($missingNin ? null : (string) (10000000000 + ($i * 100) + $p));
                $verdict = $isMatch ? 'flagged' : ($missingNin ? 'unverified' : 'clear');

                if ($verdict !== 'clear') {
                    $highest = $verdict === 'flagged' ? 'flagged' : ($highest === 'flagged' ? 'flagged' : 'unverified');
                }

                ManifestPassenger::create([
                    'id' => 'pax_'.$i.'_'.$p,
                    'manifest_id' => $manifest->id,
                    'full_name' => $name,
                    'nin' => $nin,
                    'seat' => (string) ($p + 1),
                    'verdict' => $verdict,
                    'watchlist_entry_id' => $isMatch ? 'wl_001' : null,
                    'checked_at' => $checkedAt,
                    'identity_confidence' => $isMatch ? 98 : ($missingNin ? null : 100),
                ]);
            }

            foreach (['nin_verification', 'watchlist_screening', 'plate_lookup', 'headcount'] as $type) {
                ManifestCheck::create([
                    'id' => 'chk_'.$i.'_'.Str::slug($type),
                    'manifest_id' => $manifest->id,
                    'type' => $type,
                    'status' => ($type === 'watchlist_screening' && $highest === 'flagged') ? 'flagged' : ($type === 'nin_verification' && $highest === 'unverified' ? 'mismatch' : 'clear'),
                    'result' => $type === 'watchlist_screening' && $highest === 'flagged' ? 'Watchlist match confirmed' : 'Verified',
                    'performed_by' => $manifest->checked_by_officer_id,
                    'performed_at' => $checkedAt,
                ]);
            }

            $manifest->forceFill(['verdict' => $highest])->save();
        }

        $alertTypes = ['critical', 'flagged', 'mismatch', 'unverified'];
        $alertStatuses = ['open', 'open', 'acknowledged', 'assigned', 'open', 'escalated', 'acknowledged', 'dismissed', 'open', 'assigned', 'open', 'open', 'open', 'acknowledged', 'open', 'open', 'closed'];
        $titles = [
            'NIN match 98% — Adaemu Y. Bello', 'Plate mismatch at Ojota', 'Watchlist hit — Musa Danjuma',
            'Passenger identity unverified', 'NIN match 98% — Chinedu Okoro', 'Headcount discrepancy',
            'Vehicle on watchlist — LSD-482-XY', 'Phone match — trafficking network', 'NIN match 94% — Amina Suleiman',
            'Manifest seat duplication', 'Watchlist hit — Bisi Ogunleye', 'Unverified passenger at Lekki',
            'NIN mismatch at Mile 2', 'Watchlist hit — phone 08092220044', 'Plate lookup failed at Apapa',
            'NIN match 98% — Adaemu Y. Bello', 'Passenger count mismatch',
        ];

        for ($i = 1; $i <= 17; $i++) {
            $id = 'al_'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $manifest = Manifest::query()->find('mf_'.str_pad((string) ((($i - 1) % 30) + 1), 3, '0', STR_PAD_LEFT));
            $status = $alertStatuses[$i - 1];
            $detected = now()->subMinutes(($i * 19) + 4);
            $acknowledged = in_array($status, ['acknowledged', 'assigned', 'escalated', 'dismissed', 'closed'], true) ? $detected->copy()->addMinutes(3) : null;
            $watchlistId = $i % 3 === 0 ? 'wl_001' : ($i % 5 === 0 ? 'wl_002' : null);
            $type = $i <= 4 ? 'critical' : $alertTypes[($i - 1) % count($alertTypes)];

            Alert::query()->updateOrCreate(['id' => $id], [
                'alert_number' => 'AL-26-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'type' => $type,
                'title' => $titles[$i - 1],
                'summary' => 'Alert generated by manifest screening at the command operations room.',
                'subject_type' => 'passenger',
                'subject_id' => 'pax_'.(($i % 30) + 1).'_1',
                'manifest_id' => $manifest?->id,
                'watchlist_entry_id' => $watchlistId,
                'confidence' => $type === 'unverified' ? null : (88 + ($i % 12)),
                'mode' => $manifest?->mode,
                'route' => $manifest ? $manifest->route_origin.' → '.$manifest->route_destination : null,
                'duty_point_id' => $manifest?->duty_point_id,
                'state_code' => 'LA',
                'lga_id' => $manifest?->lga_id,
                'status' => $status,
                'sla_seconds' => 120,
                'detected_at' => $detected,
                'verified_at' => $detected->copy()->addSeconds(12),
                'acknowledged_at' => $acknowledged,
                'assigned_officer_id' => in_array($status, ['assigned', 'escalated', 'closed'], true) ? 'off_grace' : null,
                'responder_name' => in_array($status, ['assigned', 'escalated', 'closed'], true) ? 'Grace Nwosu' : null,
                'resolution' => $status === 'dismissed' ? 'False positive confirmed after document review.' : null,
                'metadata' => ['seat' => '1', 'source' => 'demo'],
            ]);

            AlertAction::query()->where('alert_id', $id)->delete();
            AlertAction::create([
                'alert_id' => $id,
                'action' => 'detected',
                'note' => 'Automated screening raised this alert.',
                'actor_id' => null,
                'metadata' => ['source' => 'watchlist-screening'],
                'created_at' => $detected,
                'updated_at' => $detected,
            ]);
            if ($status !== 'open') {
                AlertAction::create([
                    'alert_id' => $id,
                    'action' => $status,
                    'note' => $status === 'dismissed' ? 'Dismissed as false positive.' : 'Action recorded by duty officer.',
                    'actor_id' => 'off_grace',
                    'created_at' => $acknowledged,
                    'updated_at' => $acknowledged,
                ]);
            }
        }

        $incidentStatuses = ['unsig', 'assigned', 'acknowledged', 'in_progress', 'resolved', 'assigned', 'in_progress', 'unsig', 'resolved', 'acknowledged', 'in_progress', 'unsig'];
        $severities = ['critical', 'high', 'medium', 'high', 'low', 'critical', 'medium', 'high', 'low', 'critical', 'medium', 'high'];
        for ($i = 1; $i <= 12; $i++) {
            $id = 'inc_'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $manifest = Manifest::query()->find('mf_'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
            $alert = Alert::query()->find('al_'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
            $reported = now()->subHours(20 - $i);
            $incident = Incident::query()->updateOrCreate(['id' => $id], [
                'incident_number' => 'INC-26-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'title' => $titles[$i - 1],
                'type' => 'manifest_verification',
                'severity' => $severities[$i - 1],
                'status' => $incidentStatuses[$i - 1],
                'summary' => 'Incident opened from the manifest verification queue for follow-up.',
                'manifest_id' => $manifest?->id,
                'alert_id' => $alert?->id,
                'watchlist_entry_id' => $i % 4 === 0 ? 'wl_001' : null,
                'location' => $manifest ? $manifest->dutyPoint->name ?? 'Lagos Command' : 'Lagos Command',
                'duty_point_id' => $manifest?->duty_point_id,
                'state_code' => 'LA',
                'lga_id' => $manifest?->lga_id,
                'reporter_name' => 'Emeka Okafor',
                'reported_by' => 'off_emeka',
                'responder_officer_id' => in_array($incidentStatuses[$i - 1], ['assigned', 'acknowledged', 'in_progress', 'resolved'], true) ? 'off_grace' : null,
                'reported_at' => $reported,
                'acknowledged_at' => in_array($incidentStatuses[$i - 1], ['acknowledged', 'in_progress', 'resolved'], true) ? $reported->copy()->addMinutes(8) : null,
                'resolved_at' => $incidentStatuses[$i - 1] === 'resolved' ? $reported->copy()->addHours(2) : null,
                'metadata' => ['source' => 'demo'],
            ]);

            IncidentEvent::query()->where('incident_id', $id)->delete();
            IncidentEvent::create([
                'incident_id' => $id,
                'event_type' => 'created',
                'description' => 'Incident reported by Emeka Okafor.',
                'actor_name' => 'Emeka Okafor',
                'created_at' => $reported,
                'updated_at' => $reported,
            ]);
            if ($incidentStatuses[$i - 1] !== 'unsig') {
                IncidentEvent::create([
                    'incident_id' => $id,
                    'event_type' => 'assigned',
                    'description' => 'Assigned to Grace Nwosu for field response.',
                    'actor_name' => 'Emeka Okafor',
                    'created_at' => $reported->copy()->addMinutes(8),
                    'updated_at' => $reported->copy()->addMinutes(8),
                ]);
            }
            if ($incidentStatuses[$i - 1] === 'resolved') {
                IncidentEvent::create([
                    'incident_id' => $id,
                    'event_type' => 'resolved',
                    'description' => 'Incident resolved after verification and field report.',
                    'actor_name' => 'Grace Nwosu',
                    'created_at' => $reported->copy()->addHours(2),
                    'updated_at' => $reported->copy()->addHours(2),
                ]);
            }

            if ($i <= 8) {
                $caseStatuses = ['open', 'in_review', 'awaiting_approval', 'closed'];
                $case = CaseFile::query()->updateOrCreate(['id' => 'case_'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)], [
                    'case_number' => 'CS-26-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'title' => 'Investigation — '.$incident->title,
                    'incident_id' => $incident->id,
                    'status' => $caseStatuses[($i - 1) % count($caseStatuses)],
                    'severity' => $severities[$i - 1],
                    'owner_officer_id' => 'off_emeka',
                    'opened_at' => $reported->copy()->addHours(1),
                    'closed_at' => ($i - 1) % 4 === 3 ? $reported->copy()->addDays(2) : null,
                    'due_date' => now()->addDays(7 + $i)->toDateString(),
                    'summary' => 'Case workspace for the '.$incident->incident_number.' follow-up. Evidence and approvals are tracked here.',
                    'approval_status' => ($i - 1) % 4 === 2 ? 'requested' : (($i - 1) % 4 === 3 ? 'approved' : 'none'),
                    'approved_by' => ($i - 1) % 4 === 3 ? 'off_ada' : null,
                    'approved_at' => ($i - 1) % 4 === 3 ? $reported->copy()->addDays(2) : null,
                    'share_status' => $i === 3 ? 'pending' : 'none',
                    'linked_records' => [
                        ['type' => 'incident', 'id' => $incident->id, 'number' => $incident->incident_number],
                        ['type' => 'manifest', 'id' => $manifest?->id],
                        ['type' => 'watchlistEntry', 'id' => $incident->watchlist_entry_id],
                    ],
                    'metadata' => ['createdFrom' => 'incident', 'convertedBy' => 'Emeka Okafor'],
                ]);

                CaseEvent::query()->where('case_id', $case->id)->delete();
                CaseEvent::create([
                    'case_id' => $case->id,
                    'action' => 'created',
                    'note' => 'Case opened from incident '.$incident->incident_number,
                    'actor_name' => 'Emeka Okafor',
                    'created_at' => $reported->copy()->addHours(1),
                    'updated_at' => $reported->copy()->addHours(1),
                ]);
                CaseEvent::create([
                    'case_id' => $case->id,
                    'action' => 'note',
                    'note' => 'Witness statements collected; CCTV footage requested from the operator.',
                    'actor_name' => 'Grace Nwosu',
                    'created_at' => $reported->copy()->addHours(3),
                    'updated_at' => $reported->copy()->addHours(3),
                ]);

                CaseEvidence::query()->where('case_id', $case->id)->delete();
                CaseEvidence::create([
                    'id' => 'evd_'.$i.'_1',
                    'case_id' => $case->id,
                    'evidence_type' => 'document',
                    'label' => 'Passenger manifest extract',
                    'file_url' => 'https://res.cloudinary.com/demo/raw/upload/v1/emf/cases/manifest-'.$i.'.pdf',
                    'file_size' => 48213,
                    'uploaded_by' => 'off_emeka',
                    'uploaded_at' => $reported->copy()->addHours(2),
                ]);
                if ($i % 2 === 0) {
                    CaseEvidence::create([
                        'id' => 'evd_'.$i.'_2',
                        'case_id' => $case->id,
                        'evidence_type' => 'photo',
                        'label' => 'Checkpoint photo',
                        'file_url' => 'https://res.cloudinary.com/demo/image/upload/v1/emf/cases/checkpoint-'.$i.'.jpg',
                        'file_size' => 182647,
                        'uploaded_by' => 'off_grace',
                        'uploaded_at' => $reported->copy()->addHours(2)->addMinutes(15),
                    ]);
                }
            }
        }
    }
}
