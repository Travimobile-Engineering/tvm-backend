<?php

namespace Tests\Feature\Emf;

use App\Models\Emf\Alert;
use App\Models\Emf\Device;
use App\Models\Emf\Manifest;
use App\Models\Emf\Notification;
use App\Models\Emf\WatchlistEntry;
use Illuminate\Support\Carbon;

class EmfApiTest extends EmfApiTestCase
{
    public function test_authentication_meta_and_dashboard_are_versioned_and_enveloped(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/manifest-checker/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('status', false)
            ->assertJsonPath('data.code', 'UNAUTHENTICATED');

        $this->withHeader('Authorization', 'Bearer '.$this->token);

        $this->getJson('/api/v1/manifest-checker/auth/me')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.serviceNumber', 'NPF-1001');

        $this->getJson('/api/v1/manifest-checker/meta')
            ->assertOk()
            ->assertJsonPath('data.modes.0.key', 'road')
            ->assertJsonPath('data.states.0.code', 'LA');

        $this->getJson('/api/v1/manifest-checker/meta/system-states')
            ->assertOk()
            ->assertJsonPath('data.no_permission.key', 'no_permission');

        $manifest = $this->checkManifest([
            ['fullName' => 'John Adewale', 'nin' => '10000000001'],
        ]);

        $this->getJson('/api/v1/manifest-checker/dashboard')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.metrics.activeManifests', 1)
            ->assertJsonStructure(['data' => ['metrics', 'liveAlerts', 'manifestsByMode', 'incidentsBySeverity', 'coverageGaps']]);

        $this->getJson('/api/v1/manifest-checker/dashboard/search?q='.$manifest['manifestNumber'])
            ->assertOk()
            ->assertJsonPath('data.manifests.0.title', $manifest['manifestNumber']);
    }

    public function test_role_permissions_are_enforced(): void
    {
        $this->makeOfficer('off_analyst', 'NPF-2001', 'Test Analyst', 'analyst@test.local', 'analyst', null);

        $token = (string) $this->postJson('/api/v1/manifest-checker/auth/login', [
            'serviceNumber' => 'NPF-2001',
            'password' => 'Password@123',
        ])->assertOk()->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/manifest-checker/dashboard')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/manifest-checker/watchlist', [
                'type' => 'person',
                'risk' => 'high',
                'reason' => 'Denied',
                'source' => 'Test',
                'fullName' => 'Denied Person',
            ])->assertForbidden()
            ->assertJsonPath('data.code', 'FORBIDDEN');
    }

    public function test_alert_queue_supports_acknowledge_escalate_assign_and_dismiss(): void
    {
        $manifest = $this->makeManifest('MF-26-90001');
        $alert = $this->makeAlert('al_test_1', 'AL-26-90001', 'open', $manifest);
        $second = $this->makeAlert('al_test_2', 'AL-26-90002', 'open', $manifest);
        $third = $this->makeAlert('al_test_3', 'AL-26-90003', 'open', $manifest);

        $this->getJson('/api/v1/manifest-checker/alerts')
            ->assertOk()
            ->assertJsonCount(3, 'data.data');

        $this->getJson('/api/v1/manifest-checker/alerts/'.$alert->id)
            ->assertOk()
            ->assertJsonPath('data.alertNumber', 'AL-26-90001')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonStructure(['data' => ['enforcementActions', 'actions', 'slaRemainingSeconds']]);

        $this->postJson('/api/v1/manifest-checker/alerts/'.$alert->id.'/acknowledge')
            ->assertOk()
            ->assertJsonPath('data.status', 'acknowledged');

        $this->postJson('/api/v1/manifest-checker/alerts/'.$second->id.'/escalate', [
            'severity' => 'high',
            'notes' => 'Escalating after NIN confirmation.',
            'assigneeId' => $this->responder->id,
        ])->assertOk()
            ->assertJsonPath('data.status', 'escalated')
            ->assertJsonPath('data.incident.status', 'assigned');

        $this->postJson('/api/v1/manifest-checker/alerts/'.$second->id.'/assign', [
            'officerId' => $this->responder->id,
            'actionType' => 'radio',
            'note' => 'Responder dispatched.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'assigned');

        $this->postJson('/api/v1/manifest-checker/alerts/'.$third->id.'/dismiss', [
            'reason' => 'false_positive',
            'note' => 'Documents confirmed the identity.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'dismissed');
    }

    public function test_manifest_checks_flag_watchlist_matches_and_support_notes_and_exports(): void
    {
        WatchlistEntry::query()->create([
            'id' => 'wl_test',
            'entry_number' => 'WL-26-9001',
            'type' => 'person',
            'risk' => 'critical',
            'reason' => 'Armed robbery suspect',
            'source' => 'NPF CID',
            'full_name' => 'Adaemu Y. Bello',
            'nin' => '12345678901',
            'nationality' => 'Nigerian',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'created_by' => $this->officer->id,
        ]);

        $manifest = $this->checkManifest([
            ['fullName' => 'Adaemu Y. Bello', 'nin' => '12345678901', 'seat' => '1'],
            ['fullName' => 'No NIN Passenger', 'seat' => '2'],
        ]);

        $this->assertSame('flagged', $manifest['verdict']);
        $this->assertDatabaseHas('emf_alerts', ['manifest_id' => $manifest['manifestId'], 'type' => 'critical']);
        $this->assertDatabaseHas('emf_alerts', ['manifest_id' => $manifest['manifestId'], 'type' => 'unverified']);

        $this->getJson('/api/v1/manifest-checker/manifests/'.$manifest['manifestId'])
            ->assertOk()
            ->assertJsonPath('data.verdict', 'flagged')
            ->assertJsonCount(2, 'data.passengers')
            ->assertJsonCount(4, 'data.checks')
            ->assertJsonStructure(['data' => ['journey', 'route']]);

        $this->getJson('/api/v1/manifest-checker/manifests/'.$manifest['manifestId'].'/passengers?page=1&pageSize=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.pagination.totalItems', 2);

        $this->postJson('/api/v1/manifest-checker/manifests/'.$manifest['manifestId'].'/notes', [
            'note' => 'Supervisor reviewed the flagged passenger.',
        ])->assertOk();

        $this->assertStringContainsString(
            'Supervisor reviewed the flagged passenger.',
            (string) $this->getJson('/api/v1/manifest-checker/manifests/'.$manifest['manifestId'])->json('data.notes'),
        );

        $export = $this->postJson('/api/v1/manifest-checker/manifests/'.$manifest['manifestId'].'/export')
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->json('data');

        $this->assertNotEmpty($export['downloadUrl']);
        $this->assertStringContainsString('"Manifest"', (string) $this->cloud->latestContents());

        $download = $this->get($export['downloadUrl'])->assertOk();
        $this->assertStringContainsString('"Manifest"', (string) $download->getContent());
    }

    public function test_watchlist_crud_import_subscriptions_and_documents(): void
    {
        $entry = $this->postJson('/api/v1/manifest-checker/watchlist', [
            'type' => 'person',
            'risk' => 'high',
            'reason' => 'Person of interest',
            'source' => 'NPF CID',
            'fullName' => 'Musa Danjuma',
            'nin' => '22345678902',
            'phone' => '+2348031110003',
            'expiresAt' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $id = $entry['watchlistEntryId'];

        $this->putJson('/api/v1/manifest-checker/watchlist/'.$id, [
            'verificationCode' => '123456',
            'risk' => 'critical',
            'notes' => 'Escalated by intelligence.',
        ])->assertOk()
            ->assertJsonPath('data.risk', 'critical');

        $this->getJson('/api/v1/manifest-checker/watchlist/'.$id)
            ->assertOk()
            ->assertJsonPath('data.fullName', 'Musa Danjuma')
            ->assertJsonStructure(['data' => ['travelHistory', 'linkedRecords', 'documents', 'validity']]);

        $this->postJson('/api/v1/manifest-checker/watchlist/import', [
            'entries' => [
                ['type' => 'person', 'risk' => 'medium', 'reason' => 'Witness', 'source' => 'NPF Legal', 'fullName' => 'Bisi Ogunleye'],
                ['type' => 'vehicle', 'risk' => 'low', 'reason' => 'Vehicle check', 'source' => 'VIO', 'plateNumber' => 'KJA-211-AA'],
            ],
        ])->assertOk()
            ->assertJsonPath('data.created', 2);

        $this->getJson('/api/v1/manifest-checker/watchlist/subscriptions')
            ->assertOk();

        $this->postJson('/api/v1/manifest-checker/watchlist/subscriptions', [
            'subscriberName' => 'Ops Room',
            'agency' => 'NPF',
            'scope' => 'Statewide',
            'modes' => ['road', 'sea'],
        ])->assertCreated();

        $this->postJson('/api/v1/manifest-checker/watchlist/'.$id.'/documents', [
            'name' => 'Court order.pdf',
            'fileUrl' => 'https://cdn.example.test/court-order.pdf',
        ])->assertCreated();

        $this->getJson('/api/v1/manifest-checker/watchlist/documents')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 1);

        $this->postJson('/api/v1/manifest-checker/watchlist/'.$id.'/escalate', [
            'severity' => 'high',
            'location' => 'Lekki Test Checkpoint',
            'notes' => 'Intelligence confirmed the match.',
        ])->assertCreated();

        $this->postJson('/api/v1/manifest-checker/watchlist/'.$id.'/open-case', [
            'title' => 'Watchlist investigation',
            'severity' => 'high',
        ])->assertCreated();

        $this->deleteJson('/api/v1/manifest-checker/watchlist/'.$id, [
            'verificationCode' => '123456',
            'reason' => 'Record cleared.',
        ])->assertOk();
    }

    public function test_incident_conversion_and_case_approval_workflow(): void
    {
        $incident = $this->postJson('/api/v1/manifest-checker/incidents', [
            'title' => 'Passenger identity discrepancy',
            'type' => 'manifest_verification',
            'severity' => 'high',
            'summary' => 'Passenger could not be verified at the checkpoint.',
            'location' => 'Lekki Test Checkpoint',
        ])->assertCreated()->json('data');

        $incidentId = $incident['incidentId'];

        $this->postJson('/api/v1/manifest-checker/incidents/'.$incidentId.'/assign', [
            'officerId' => $this->responder->id,
            'note' => 'Field response required.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'assigned');

        $case = $this->postJson('/api/v1/manifest-checker/incidents/'.$incidentId.'/convert-to-case', [
            'caseTitle' => 'Identity discrepancy investigation',
            'ownerId' => $this->responder->id,
        ])->assertCreated()->json('data');

        $caseId = $case['caseId'];

        $this->getJson('/api/v1/manifest-checker/cases')
            ->assertOk()
            ->assertJsonPath('data.board.open', 1);

        $this->postJson('/api/v1/manifest-checker/cases/'.$caseId.'/notes', [
            'note' => 'Witness statement collected.',
        ])->assertOk();

        $this->postJson('/api/v1/manifest-checker/cases/'.$caseId.'/evidence', [
            'type' => 'document',
            'label' => 'Manifest extract',
            'fileUrl' => 'https://cdn.example.test/manifest.pdf',
        ])->assertCreated();

        $this->getJson('/api/v1/manifest-checker/cases/'.$caseId.'/activity')
            ->assertOk()
            ->assertJsonCount(1, 'data.evidence');

        $this->postJson('/api/v1/manifest-checker/cases/'.$caseId.'/submit-closure', [
            'note' => 'All evidence reviewed.',
            'shareWithDss' => true,
        ])->assertOk()
            ->assertJsonPath('data.status', 'awaiting_approval');

        $this->postJson('/api/v1/manifest-checker/cases/'.$caseId.'/approve-closure', [
            'note' => 'Approved by supervisor.',
            'shareWithAgency' => true,
        ])->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.approvalStatus', 'approved');

        $this->postJson('/api/v1/manifest-checker/cases/'.$caseId.'/share', [
            'agency' => 'DSS',
            'expiresAt' => now()->addWeek()->toIso8601String(),
            'code' => '123456',
        ])->assertOk()
            ->assertJsonPath('data.shareStatus', 'active');
    }

    public function test_officers_access_grants_devices_and_duty_roster(): void
    {
        $this->getJson('/api/v1/manifest-checker/officers')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 2);

        $this->getJson('/api/v1/manifest-checker/officers/'.$this->responder->id.'/performance')
            ->assertOk()
            ->assertJsonStructure(['data' => ['metrics', 'checksPerDay', 'accessGrants', 'hitsHandled']]);

        $this->postJson('/api/v1/manifest-checker/officers/'.$this->responder->id.'/suspend', [
            'reason' => 'Pending conduct review.',
        ])->assertOk()
            ->assertJsonPath('data.officer.status', 'suspended');

        $this->postJson('/api/v1/manifest-checker/officers/'.$this->responder->id.'/reactivate')
            ->assertOk()
            ->assertJsonPath('data.officer.status', 'off_duty');

        $template = $this->postJson('/api/v1/manifest-checker/grant-templates', [
            'name' => 'Checkpoint 24h',
            'scope' => 'Lekki',
            'modes' => ['road'],
            'durationHours' => 24,
            'accessCount' => 2,
        ])->assertCreated()->json('data.0.templateId');

        $grant = $this->postJson('/api/v1/manifest-checker/access-grants', [
            'officerId' => $this->responder->id,
            'templateId' => $template,
            'scope' => 'Lekki',
            'modes' => ['road'],
            'startsAt' => now()->toIso8601String(),
            'endsAt' => now()->addDay()->toIso8601String(),
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/manifest-checker/access-grants/conflicts', [
            'officerIds' => [$this->responder->id],
            'startsAt' => now()->addHour()->toIso8601String(),
            'endsAt' => now()->addHours(2)->toIso8601String(),
            'modes' => ['road'],
        ])->assertOk()
            ->assertJsonPath('data.hasConflicts', true);

        $this->postJson('/api/v1/manifest-checker/access-grants/'.$grant['grantId'].'/revoke', [
            'code' => '123456',
            'reason' => 'Rotation complete.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        Device::query()->create([
            'id' => 'dev_test',
            'officer_id' => $this->responder->id,
            'device_name' => 'Test Phone',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->postJson('/api/v1/manifest-checker/devices/dev_test/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson('/api/v1/manifest-checker/duty-roster/assign', [
            'dutyPointId' => 'dp_test',
            'officerId' => $this->responder->id,
            'date' => now()->toDateString(),
            'shift' => 'morning',
        ])->assertOk()
            ->assertJsonPath('data.status', 'assigned');

        $this->postJson('/api/v1/manifest-checker/duty-roster/publish', [
            'weekStart' => now()->startOfWeek()->toDateString(),
        ])->assertOk()
            ->assertJsonPath('data.publishedSlots', 1);

        $this->postJson('/api/v1/manifest-checker/duty-roster/auto-fill', [
            'weekStart' => now()->startOfWeek()->toDateString(),
        ])->assertOk()
            ->assertJsonStructure(['data' => ['assigned']]);
    }

    public function test_analytics_audit_notifications_settings_and_exports(): void
    {
        $this->checkManifest([['fullName' => 'John Adewale', 'nin' => '10000000001']]);

        $this->getJson('/api/v1/manifest-checker/analytics')
            ->assertOk()
            ->assertJsonStructure(['data' => ['metrics', 'checksPerDay', 'coverage', 'leaderboard']]);

        $this->getJson('/api/v1/manifest-checker/analytics/units/dp_test')
            ->assertOk()
            ->assertJsonPath('data.dutyPoint.dutyPointId', 'dp_test');

        $this->postJson('/api/v1/manifest-checker/analytics/export', [
            'code' => '123456',
            'watermark' => true,
            'period' => '7d',
        ])->assertOk()
            ->assertJsonPath('data.status', 'ready');

        $this->getJson('/api/v1/manifest-checker/analytics/schedules')
            ->assertOk();

        $this->postJson('/api/v1/manifest-checker/analytics/schedules', [
            'name' => 'Weekly operations report',
            'reportType' => 'analytics',
            'frequency' => 'weekly',
            'recipients' => ['ops@test.local'],
            'nextRunAt' => now()->addWeek()->toIso8601String(),
        ])->assertCreated();

        $this->getJson('/api/v1/manifest-checker/audit-logs?period=30d')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'pagination']]);

        $this->postJson('/api/v1/manifest-checker/audit-logs/export', [
            'reportType' => 'audit',
            'code' => '123456',
            'watermark' => true,
            'requires2fa' => true,
        ])->assertOk()
            ->assertJsonPath('data.reportType', 'audit');

        Notification::query()->create([
            'id' => 'not_test',
            'officer_id' => $this->officer->id,
            'type' => 'critical',
            'title' => 'Test alert',
            'body' => 'Test notification body',
            'needs_action' => true,
            'status' => 'unread',
        ]);

        $this->getJson('/api/v1/manifest-checker/notifications?filter=needs_action')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->postJson('/api/v1/manifest-checker/notifications/not_test/read')
            ->assertOk()
            ->assertJsonPath('data.status', 'read');

        $this->getJson('/api/v1/manifest-checker/settings')
            ->assertOk()
            ->assertJsonStructure(['data' => ['profile', 'notifications', 'sessions', 'languages']]);

        $this->putJson('/api/v1/manifest-checker/settings/profile', [
            'phone' => '+2348099999999',
            'language' => 'en',
        ])->assertOk()
            ->assertJsonPath('data.profile.phone', '+2348099999999');

        $this->putJson('/api/v1/manifest-checker/settings/notifications', [
            'preferences' => ['criticalAlert' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => true]],
        ])->assertOk()
            ->assertJsonPath('data.notifications.criticalAlert.sms', false);

        $this->getJson('/api/v1/manifest-checker/settings/help')
            ->assertOk()
            ->assertJsonStructure(['data' => ['topics', 'support']]);
    }

    /**
     * @param  list<array<string, mixed>>  $passengers
     * @return array<string, mixed>
     */
    private function checkManifest(array $passengers): array
    {
        return $this->postJson('/api/v1/manifest-checker/manifests/checks', [
            'mode' => 'road',
            'operator' => 'ABC Transport',
            'routeOrigin' => 'Lagos',
            'routeDestination' => 'Ibadan',
            'vehicleRegistration' => 'LSD-401-XY',
            'driverName' => 'Tunde Balogun',
            'seats' => 18,
            'passengers' => $passengers,
        ])->assertCreated()->json('data');
    }

    private function makeManifest(string $number): Manifest
    {
        return Manifest::query()->create([
            'id' => 'mf_'.strtolower($number),
            'manifest_number' => $number,
            'mode' => 'road',
            'operator' => 'ABC Transport',
            'route_origin' => 'Lagos',
            'route_destination' => 'Ibadan',
            'seats' => 18,
            'passenger_count' => 1,
            'checked_count' => 1,
            'duty_point_id' => 'dp_test',
            'state_code' => 'LA',
            'lga_id' => 'lga_test',
            'verdict' => 'flagged',
            'checked_by_officer_id' => $this->officer->id,
            'checked_at' => now(),
            'departed_at' => now()->subHour(),
        ]);
    }

    private function makeAlert(string $id, string $number, string $status, Manifest $manifest): Alert
    {
        return Alert::query()->create([
            'id' => $id,
            'alert_number' => $number,
            'type' => 'critical',
            'title' => 'NIN match 98%',
            'summary' => 'Watchlist match confirmed.',
            'subject_type' => 'passenger',
            'subject_id' => 'pax_1',
            'manifest_id' => $manifest->id,
            'confidence' => 98,
            'mode' => 'road',
            'route' => 'Lagos → Ibadan',
            'duty_point_id' => 'dp_test',
            'state_code' => 'LA',
            'lga_id' => 'lga_test',
            'status' => $status,
            'sla_seconds' => 120,
            'detected_at' => Carbon::now()->subMinutes(2),
            'verified_at' => Carbon::now()->subMinute(),
        ]);
    }
}
