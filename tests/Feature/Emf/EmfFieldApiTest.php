<?php

namespace Tests\Feature\Emf;

use App\Models\Emf\Alert;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestPassenger;

class EmfFieldApiTest extends EmfApiTestCase
{
    public function test_field_home_search_upcoming_and_recent(): void
    {
        $manifest = $this->makeManifest('mf_field_test', 'AKW 412 UY', [
            ['name' => 'Emeka Nwosu', 'seat' => '01'],
            ['name' => 'Ngozi Eze', 'seat' => '02'],
        ], 'in_transit');

        $this->getJson('/api/v1/manifest-checker/field/home')
            ->assertOk()
            ->assertJsonPath('data.officer.serviceNumber', 'NPF-1001')
            ->assertJsonPath('data.duty.dutyPoint', 'Lekki Test Checkpoint')
            ->assertJsonCount(1, 'data.upcomingManifests')
            ->assertJsonStructure(['data' => ['quickActions', 'alerts', 'recentlyChecked']]);

        $this->getJson('/api/v1/manifest-checker/field/search?q=AKW 412 UY')
            ->assertOk()
            ->assertJsonPath('data.found', true)
            ->assertJsonPath('data.manifest.manifestNumber', $manifest->manifest_number);

        $this->getJson('/api/v1/manifest-checker/field/search?q=RIV 221 KD')
            ->assertOk()
            ->assertJsonPath('data.found', false)
            ->assertJsonPath('data.message', 'No NTEM manifest matches this plate today. No commercial bus carrying passengers without one is an offence.');

        $this->getJson('/api/v1/manifest-checker/field/manifests/upcoming')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->postJson('/api/v1/manifest-checker/field/manifests/'.$manifest->id.'/checks')
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonCount(2, 'data.seats');

        $this->getJson('/api/v1/manifest-checker/field/manifests/recent')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_field_check_confirm_flag_resolve_and_clear_vehicle(): void
    {
        $manifest = $this->makeManifest('mf_clear_test', 'PHC 482 XA', [
            ['name' => 'Chiamaka Okafor', 'seat' => '03', 'verdict' => 'clear'],
            ['name' => 'Abubakar S. Bello', 'seat' => '07', 'verdict' => 'clear'],
        ]);

        $workspace = $this->postJson('/api/v1/manifest-checker/field/manifests/'.$manifest->id.'/checks')
            ->assertCreated()->json('data');
        $checkId = $workspace['checkId'];
        /** @var list<array<string, mixed>> $seatRows */
        $seatRows = (array) $workspace['seats'];
        $seats = collect($seatRows);
        $first = $seats->firstWhere('seat', '03');
        $second = $seats->firstWhere('seat', '07');

        $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/clear-vehicle')
            ->assertStatus(409)
            ->assertJsonPath('data.code', 'UNRESOLVED_SEATS');

        $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/seats/'.$first['passengerId'].'/confirm')
            ->assertOk()
            ->assertJsonPath('data.seat.status', 'present');

        $flag = $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/seats/'.$second['passengerId'].'/flag', [
            'reason' => 'photo_mismatch',
            'note' => 'Photo does not match the NIN record.',
        ])->assertOk()
            ->assertJsonPath('data.seat.status', 'flagged');
        $this->assertNotEmpty($flag->json('data.alertId'));

        $this->getJson('/api/v1/manifest-checker/field/alerts/'.$flag->json('data.alertId'))
            ->assertOk()
            ->assertJsonPath('data.passenger.fullName', 'Abubakar S. Bello');

        $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/seats/'.$second['passengerId'].'/resolve', [
            'note' => 'Photo verified against the NIN record.',
        ])->assertOk()
            ->assertJsonPath('data.seat.status', 'cleared_with_note');

        $cleared = $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/clear-vehicle')
            ->assertOk()
            ->assertJsonPath('data.record.vehicleCleared', true)
            ->json('data');

        $this->assertStringStartsWith('CLR-', $cleared['clearanceReference']);
        $this->assertNotNull($manifest->fresh()->checked_at);
    }

    public function test_field_check_escalation_hold_release_and_identity_reveal(): void
    {
        $manifest = $this->makeManifest('mf_escalate_test', 'PHC 482 XA', [
            ['name' => 'Ibrahim Danjuma', 'seat' => '04', 'verdict' => 'flagged', 'nin' => '12345678901'],
        ]);

        $workspace = $this->postJson('/api/v1/manifest-checker/field/manifests/'.$manifest->id.'/checks')
            ->assertCreated()->json('data');
        $checkId = $workspace['checkId'];
        $passengerId = $workspace['seats'][0]['passengerId'];
        $this->assertStringContainsString('•', $workspace['seats'][0]['identity']['ninMasked']);

        $reveal = $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/seats/'.$passengerId.'/reveal', [
            'fields' => ['nin', 'phone'],
            'reason' => 'Confirming the watchlist match.',
        ])->assertOk()->json('data');

        $this->assertSame('12345678901', $reveal['fields']['nin']);

        $escalated = $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/seats/'.$passengerId.'/escalate', [
            'category' => 'watchlist_hit',
            'severity' => 'critical',
            'actionTaken' => 'vehicle_detained',
            'note' => 'Watchlist match confirmed at the checkpoint.',
        ])->assertOk()
            ->assertJsonPath('data.check.status', 'on_hold')
            ->json('data');

        $this->assertStringStartsWith('ESC-', $escalated['escalationReference']);
        $this->assertNotEmpty($escalated['instructions']);

        $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/hold/acknowledge')
            ->assertOk();

        $this->postJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/release', [
            'incidentId' => $escalated['incidentId'],
            'note' => 'Ops room took over the passenger.',
        ])->assertOk()
            ->assertJsonPath('data.check.status', 'ops_takeover');

        $this->getJson('/api/v1/manifest-checker/field/checks/'.$checkId.'/record')
            ->assertOk()
            ->assertJsonPath('data.locked', true)
            ->assertJsonStructure(['data' => ['timeline', 'counts', 'incidentReport']]);
    }

    public function test_field_alerts_and_reports_workflows(): void
    {
        $manifest = $this->makeManifest('mf_report_test', 'PHC 117 BD', [
            ['name' => 'Ngozi Eze', 'seat' => '02', 'verdict' => 'mismatch'],
        ]);

        Alert::query()->create([
            'id' => 'al_field_test',
            'alert_number' => 'AL-26-99001',
            'type' => 'critical',
            'title' => 'NIN watchlist match on inbound bus',
            'summary' => 'Seat 04 matched an NIN watchlist entry.',
            'subject_type' => 'passenger',
            'subject_id' => null,
            'manifest_id' => $manifest->id,
            'confidence' => 98,
            'mode' => 'road',
            'route' => 'Lagos → Abuja',
            'duty_point_id' => 'dp_test',
            'state_code' => 'LA',
            'lga_id' => 'lga_test',
            'status' => 'open',
            'sla_seconds' => 120,
            'detected_at' => now()->subMinute(),
            'verified_at' => now(),
            'match_rule' => 'Exact NIN + name · High risk',
            'boarding_point' => 'Mile 3 Park, Lagos',
        ]);

        $this->getJson('/api/v1/manifest-checker/field/alerts?tab=active')
            ->assertOk()
            ->assertJsonPath('data.counts.active', 1)
            ->assertJsonPath('data.data.0.matchRule', 'Exact NIN + name · High risk');

        $this->getJson('/api/v1/manifest-checker/field/alerts/al_field_test')
            ->assertOk()
            ->assertJsonStructure(['data' => ['timeline', 'actions', 'manifest']]);

        $this->postJson('/api/v1/manifest-checker/field/alerts/al_field_test/acknowledge', [
            'notifyPost' => true,
        ])->assertOk()
            ->assertJsonPath('data.status', 'acknowledged')
            ->assertJsonPath('data.alertPost', 'Lekki Test Checkpoint');

        $this->postJson('/api/v1/manifest-checker/field/alerts/al_field_test/clear', [
            'outcome' => 'cleared',
            'note' => 'Identity confirmed and passenger cleared.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.fieldOutcome', 'cleared');

        $report = $this->postJson('/api/v1/manifest-checker/field/reports', [
            'category' => 'watchlist_hit',
            'severity' => 'high',
            'title' => 'Watchlist passenger held at checkpoint',
            'note' => 'Seat 04 confirmed on-site against NIN photo. Bus held; ops room notified.',
            'manifestId' => $manifest->id,
            'linkedVehicle' => 'PHC 117 BD',
            'seat' => '04',
            'escalateToOps' => true,
            'gpsLatitude' => 6.4478,
            'gpsLongitude' => 3.4723,
            'evidence' => [['type' => 'photo', 'fileUrl' => 'https://cdn.example.test/evidence.jpg']],
        ])->assertCreated()
            ->assertJsonPath('data.reportState', 'escalated')
            ->assertJsonPath('data.statusLabel', 'Escalated to Ops')
            ->json('data');

        $reportId = $report['reportId'];
        $this->assertStringStartsWith('INC-', $report['reportNumber']);

        $this->getJson('/api/v1/manifest-checker/field/reports?tab=open')
            ->assertOk()
            ->assertJsonPath('data.counts.open', 1);

        $this->getJson('/api/v1/manifest-checker/field/reports/'.$reportId)
            ->assertOk()
            ->assertJsonStructure(['data' => ['evidence', 'timeline', 'whatHappened']]);

        $this->postJson('/api/v1/manifest-checker/field/reports/'.$reportId.'/notes', [
            'note' => 'Ops room has taken over the subject.',
        ])->assertOk();

        $this->postJson('/api/v1/manifest-checker/field/reports/'.$reportId.'/attachments', [
            'type' => 'voice_note',
            'fileUrl' => 'https://cdn.example.test/voice.m4a',
            'durationSeconds' => 34,
        ])->assertCreated();

        $this->postJson('/api/v1/manifest-checker/field/reports/sync', [
            'reports' => [[
                'localId' => 'L-0013',
                'payload' => [
                    'category' => 'document_nin_fraud',
                    'severity' => 'high',
                    'note' => 'Saved while offline and synced later.',
                    'linkedVehicle' => 'RV 904 KD',
                    'clientReportId' => 'L-0013',
                ],
            ]],
        ])->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.synced.0.localId', 'L-0013');
    }

    public function test_field_profile_security_activity_and_support(): void
    {
        $this->getJson('/api/v1/manifest-checker/field/profile')
            ->assertOk()
            ->assertJsonPath('data.officer.serviceNumber', 'NPF-1001')
            ->assertJsonStructure(['data' => ['security', 'preferences', 'appearance', 'recentActivity']]);

        $security = $this->putJson('/api/v1/manifest-checker/field/profile/security', [
            'twoFactorEnabled' => true,
            'biometricEnabled' => true,
            'generateBackupCodes' => true,
        ])->assertOk()->json('data');

        $this->assertCount(8, $security['backupCodes']);
        $this->assertNotNull($security['twoFactorSetup']);

        $this->putJson('/api/v1/manifest-checker/field/profile/appearance', ['theme' => 'dark'])
            ->assertOk()
            ->assertJsonPath('data.theme', 'dark');

        $this->putJson('/api/v1/manifest-checker/field/profile/notifications', [
            'preferences' => ['busApproachingMyPost' => false],
        ])->assertOk()
            ->assertJsonPath('data.preferences.busApproachingMyPost', false);

        $this->postJson('/api/v1/manifest-checker/field/profile/password', [
            'currentPassword' => 'Password@123',
            'newPassword' => 'NewPassword@456',
            'newPassword_confirmation' => 'NewPassword@456',
        ])->assertOk();

        $this->postJson('/api/v1/manifest-checker/auth/login', [
            'serviceNumber' => 'NPF-1001',
            'password' => 'NewPassword@456',
        ])->assertOk();

        $this->getJson('/api/v1/manifest-checker/field/profile/sessions')
            ->assertOk()
            ->assertJsonFragment(['current' => true]);

        $this->getJson('/api/v1/manifest-checker/field/profile/activity?filter=all')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'pagination']]);

        $this->getJson('/api/v1/manifest-checker/field/support')
            ->assertOk()
            ->assertJsonStructure(['data' => ['opsRoom', 'topics', 'appVersion']]);

        $this->postJson('/api/v1/manifest-checker/field/support', [
            'type' => 'app_problem',
            'subject' => 'Offline sync delayed',
            'message' => 'Reports took 10 minutes to sync.',
        ])->assertCreated();

        $this->postJson('/api/v1/manifest-checker/field/profile/correction-request', [
            'field' => 'division',
            'message' => 'Please correct my division.',
        ])->assertCreated();

        $this->postJson('/api/v1/manifest-checker/field/profile/logout-all')
            ->assertOk()
            ->assertJsonPath('data.signedOut', fn ($count): bool => is_int($count) && $count >= 1);
    }

    /**
     * @param  list<array<string, mixed>>  $passengers
     */
    private function makeManifest(string $id, string $plate, array $passengers, string $arrivalStatus = 'arrived'): Manifest
    {
        $manifest = Manifest::query()->create([
            'id' => $id,
            'manifest_number' => 'MF-26-'.strtoupper(substr(md5($id), 0, 5)),
            'mode' => 'road',
            'operator' => 'Toyota Hiace',
            'vehicle_registration' => $plate,
            'driver_name' => 'Sunday Pepple',
            'route_origin' => 'Mile 3 Park, Lagos',
            'route_destination' => 'Utako, Abuja',
            'seats' => 14,
            'passenger_count' => count($passengers),
            'checked_count' => 0,
            'duty_point_id' => 'dp_test',
            'state_code' => 'LA',
            'lga_id' => 'lga_test',
            'verdict' => 'unverified',
            'arrival_status' => $arrivalStatus,
            'departed_at' => now()->subHours(2),
            'expected_arrival_at' => now()->addHour(),
            'source' => 'operator_api',
        ]);

        foreach ($passengers as $index => $passenger) {
            ManifestPassenger::create([
                'id' => 'pax_field_'.$id.'_'.$index,
                'manifest_id' => $manifest->id,
                'full_name' => $passenger['name'],
                'nin' => $passenger['nin'] ?? (string) (22345678900 + $index),
                'seat' => $passenger['seat'],
                'verdict' => $passenger['verdict'] ?? 'clear',
                'sex' => 'F',
                'dob' => '1992-05-14',
                'phone' => '+234803111'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'photo_url' => 'https://i.pravatar.cc/96?img='.(20 + $index),
                'nin_record' => [
                    'name' => $passenger['name'],
                    'dob' => '1992-05-14',
                    'sex' => 'F',
                    'phone' => '+234803111'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                ],
                'boarding_point' => 'Mile 3 Park, Lagos',
            ]);
        }

        return $manifest;
    }
}
