<?php

namespace Database\Seeders\Emf;

use App\Models\Emf\AccessGrant;
use App\Models\Emf\Device;
use App\Models\Emf\DutyPoint;
use App\Models\Emf\DutyRoster;
use App\Models\Emf\GrantTemplate;
use App\Models\Emf\Officer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class EmfAccessSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            ['dp_lekki', 'Lekki Phase 1 Checkpoint', 'other', 'LA', 'lga_la_2', 6.4478, 3.4723, 12, 4],
            ['dp_mile2', 'Mile 2 Motor Park', 'motor_park', 'LA', 'lga_la_3', 6.4655, 3.3342, 9, 3],
            ['dp_ojota', 'Ojota Interchange', 'motor_park', 'LA', 'lga_la_1', 6.5778, 3.3819, 7, 2],
            ['dp_airport', 'Murtala Muhammed Airport T1', 'airport', 'LA', 'lga_la_1', 6.5774, 3.3212, 8, 3],
            ['dp_apapa', 'Apapa Jetty', 'jetty', 'LA', 'lga_la_3', 6.4478, 3.3594, 5, 1],
            ['dp_ibadan', 'Ibadan Rail Terminus', 'rail', 'OY', 'lga_oy_1', 7.3775, 3.9470, 4, 1],
            ['dp_abuja', 'Nnamdi Azikiwe Airport', 'airport', 'FC', 'lga_fc_1', 9.0068, 7.2632, 6, 2],
            ['dp_kano', 'Kano Line Motor Park', 'motor_park', 'KN', 'lga_kn_1', 12.0022, 8.5919, 3, 1],
            ['dp_ph', 'Port Harcourt Jetty', 'jetty', 'RI', 'lga_ri_1', 4.8156, 7.0498, 2, 0],
            ['dp_hotel', 'Eko Hotel Hospitality Desk', 'hotel', 'LA', 'lga_la_2', 6.4281, 3.4219, 0, 0],
        ];

        foreach ($points as [$id, $name, $type, $state, $lga, $lat, $lng, $officers, $grants]) {
            DutyPoint::query()->updateOrCreate(['id' => $id], [
                'name' => $name,
                'type' => $type,
                'state_code' => $state,
                'lga_id' => $lga,
                'address' => $name.', Nigeria',
                'latitude' => $lat,
                'longitude' => $lng,
                'shift_start' => '06:00',
                'shift_end' => '18:00',
                'officers_count' => $officers,
                'grants_count' => $grants,
                'status' => 'active',
            ]);
        }

        $officers = [
            ['off_ada', 'NPF-88214', 'Deputy Superintendent', 'Adaemu Y. Bello', 'ada.bello@npf.gov.ng', '+2348030000001', 'Manifest Enforcement', 'LA', 'lga_la_2', 'dp_lekki', 'state_commander', 'on_duty', 702, 1],
            ['off_emeka', 'NPF-77310', 'Superintendent', 'Emeka Okafor', 'emeka.okafor@npf.gov.ng', '+2348030000002', 'State Operations Room', 'LA', 'lga_la_1', 'dp_ojota', 'ops_officer', 'on_duty', 431, 2],
            ['off_fatima', 'NPF-66102', 'Assistant Superintendent', 'Fatima Sani', 'fatima.sani@npf.gov.ng', '+2348030000003', 'Intelligence Analysis', 'LA', 'lga_la_1', null, 'analyst', 'on_duty', 188, 3],
            ['off_tunde', 'NPF-55110', 'Inspector', 'Tunde Adebayo', 'tunde.adebayo@npf.gov.ng', '+2348030000004', 'Duty Operations', 'LA', 'lga_la_3', 'dp_mile2', 'duty_officer', 'on_duty', 301, 4],
            ['off_grace', 'NPF-44310', 'Inspector', 'Grace Nwosu', 'grace.nwosu@npf.gov.ng', '+2348030000005', 'Duty Operations', 'LA', 'lga_la_2', 'dp_lekki', 'duty_officer', 'on_duty', 274, 5],
            ['off_ibrahim', 'NPF-33421', 'Sergeant', 'Ibrahim Musa', 'ibrahim.musa@npf.gov.ng', '+2348030000006', 'Duty Operations', 'LA', 'lga_la_1', 'dp_airport', 'duty_officer', 'on_duty', 198, 6],
            ['off_chidi', 'NPF-22510', 'Inspector', 'Chidi Eze', 'chidi.eze@npf.gov.ng', '+2348030000007', 'Duty Operations', 'LA', 'lga_la_3', 'dp_apapa', 'duty_officer', 'suspended', 61, 7],
            ['off_aisha', 'NPF-11922', 'Deputy Superintendent', 'Aisha Bello', 'aisha.bello@npf.gov.ng', '+2348030000008', 'State Operations Room', 'LA', 'lga_la_1', 'dp_ojota', 'ops_officer', 'on_duty', 389, 8],
            ['off_sola', 'NPF-99213', 'Inspector', 'Sola Ajayi', 'sola.ajayi@npf.gov.ng', '+2348030000009', 'Duty Operations', 'LA', 'lga_la_4', 'dp_hotel', 'duty_officer', 'off_duty', 0, 9],
            ['off_ngozi', 'NPF-81820', 'Sergeant', 'Ngozi Ugwu', 'ngozi.ugwu@npf.gov.ng', '+2348030000010', 'Duty Operations', 'LA', 'lga_la_2', 'dp_lekki', 'duty_officer', 'on_duty', 142, 10],
        ];

        foreach ($officers as [$id, $service, $rank, $name, $email, $phone, $unit, $state, $lga, $point, $role, $status, $checks, $suffix]) {
            Officer::query()->updateOrCreate(['id' => $id], [
                'service_number' => $service,
                'rank' => $rank,
                'full_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'unit' => $unit,
                'state_code' => $state,
                'lga_id' => $lga,
                'duty_point_id' => $point,
                'role' => $role,
                'status' => $status,
                'language' => 'en',
                'password_hash' => Hash::make('Password@123'),
                'watchlist_edit_code_hash' => Hash::make('123456'),
                'last_active_at' => now()->subMinutes($suffix * 3),
                'checks_today' => $checks,
            ]);
        }

        $templates = [
            ['grt_checkpoint', '24-hour Checkpoint', 'Standard field grant for motor parks and checkpoints.', 'Checkpoint', ['road'], 24, 6],
            ['grt_airport', 'Airport Check', 'Airside and arrivals screening grant.', 'Airport', ['air'], 12, 4],
            ['grt_maritime', 'Maritime Escort', 'Jetty and waterway manifest verification.', 'Jetty', ['sea'], 36, 3],
            ['grt_watchlist', 'Watchlist Read', 'Read-only watchlist lookup grant.', 'Statewide', ['road', 'sea', 'air', 'rail', 'hospitality'], 8, 2],
            ['grt_supervisor', 'Supervisor Review', 'Approval and audit review grant.', 'Statewide', ['road', 'sea', 'air', 'rail'], 72, 1],
        ];

        foreach ($templates as [$id, $name, $description, $scope, $modes, $hours, $count]) {
            GrantTemplate::query()->updateOrCreate(['id' => $id], [
                'name' => $name,
                'description' => $description,
                'scope' => $scope,
                'modes' => $modes,
                'duration_hours' => $hours,
                'access_count' => $count,
                'status' => 'active',
                'created_by' => 'off_ada',
            ]);
        }

        $grants = [
            ['gr_001', 'GR-26-0001', 'off_ada', 'grt_supervisor', 'Statewide', ['road', 'sea', 'air', 'rail'], 72],
            ['gr_002', 'GR-26-0002', 'off_emeka', 'grt_checkpoint', 'Ojota', ['road'], 24],
            ['gr_003', 'GR-26-0003', 'off_tunde', 'grt_checkpoint', 'Mile 2', ['road'], 24],
            ['gr_004', 'GR-26-0004', 'off_grace', 'grt_checkpoint', 'Lekki', ['road'], 12],
            ['gr_005', 'GR-26-0005', 'off_ibrahim', 'grt_airport', 'Airport T1', ['air'], 12],
            ['gr_006', 'GR-26-0006', 'off_aisha', 'grt_watchlist', 'Statewide', ['road', 'sea', 'air'], 8],
            ['gr_007', 'GR-26-0007', 'off_ngozi', 'grt_checkpoint', 'Lekki', ['road'], 24],
            ['gr_008', 'GR-26-0008', 'off_chidi', 'grt_maritime', 'Apapa', ['sea'], 36],
        ];

        foreach ($grants as [$id, $number, $officer, $template, $scope, $modes, $hours]) {
            AccessGrant::query()->updateOrCreate(['id' => $id], [
                'grant_number' => $number,
                'officer_id' => $officer,
                'template_id' => $template,
                'scope' => $scope,
                'modes' => $modes,
                'starts_at' => now()->subHours(2),
                'ends_at' => now()->addHours($hours),
                'status' => $id === 'gr_008' ? 'revoked' : 'active',
                'created_by' => 'off_ada',
                'revoked_at' => $id === 'gr_008' ? now()->subHour() : null,
                'revoke_reason' => $id === 'gr_008' ? 'Account suspended pending review' : null,
            ]);
        }

        Device::query()->updateOrCreate(['id' => 'dev_001'], [
            'officer_id' => 'off_ada',
            'device_name' => 'iPad Pro · Ops Room',
            'platform' => 'iPadOS 18',
            'status' => 'pending',
            'requested_at' => now()->subMinutes(18),
            'last_seen_ip' => '102.89.34.12',
        ]);
        Device::query()->updateOrCreate(['id' => 'dev_002'], [
            'officer_id' => 'off_grace',
            'device_name' => 'Samsung Galaxy A55',
            'platform' => 'Android 15',
            'status' => 'pending',
            'requested_at' => now()->subHours(2),
            'last_seen_ip' => '102.89.34.44',
        ]);
        Device::query()->updateOrCreate(['id' => 'dev_003'], [
            'officer_id' => 'off_tunde',
            'device_name' => 'Tecno Camon 30',
            'platform' => 'Android 14',
            'status' => 'approved',
            'requested_at' => now()->subDays(2),
            'approved_by' => 'off_ada',
            'approved_at' => now()->subDays(2)->addMinutes(20),
            'last_seen_ip' => '102.89.34.55',
        ]);

        $weekStart = Carbon::now()->startOfWeek();
        $shifts = ['morning', 'afternoon'];
        $assignments = [
            'dp_lekki' => ['off_grace', 'off_ngozi'],
            'dp_mile2' => ['off_tunde', null],
            'dp_ojota' => ['off_tunde', 'off_aisha'],
            'dp_airport' => ['off_ibrahim', null],
            'dp_apapa' => [null, null],
            'dp_ibadan' => [null, null],
            'dp_abuja' => [null, null],
            'dp_kano' => [null, null],
            'dp_ph' => [null, null],
            'dp_hotel' => [null, null],
        ];

        foreach ($assignments as $point => $officerPair) {
            foreach (range(0, 6) as $dayOffset) {
                $date = $weekStart->copy()->addDays($dayOffset);
                foreach ($shifts as $index => $shift) {
                    $officerId = $dayOffset === 0 ? $officerPair[$index] : null;
                    $status = $officerId ? 'assigned' : 'vacant';
                    if ($officerId === 'off_tunde' && $point === 'dp_mile2') {
                        $status = 'conflict';
                    }

                    DutyRoster::query()->updateOrCreate([
                        'duty_point_id' => $point,
                        'roster_date' => $date->toDateString(),
                        'shift' => $shift,
                    ], [
                        'id' => 'ros_'.$point.'_'.$date->format('Ymd').'_'.$shift,
                        'officer_id' => $officerId,
                        'status' => $status,
                        'published' => $dayOffset < 1,
                        'created_by' => 'off_ada',
                    ]);
                }
            }
        }
    }
}
