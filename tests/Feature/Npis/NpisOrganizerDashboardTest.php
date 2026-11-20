<?php

namespace Tests\Feature\Npis;

use App\Contracts\NpisCloudStorage;
use App\Models\Npis\Notification;
use App\Models\Npis\Registration;
use Database\Seeders\Npis\NpisDatabaseSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Npis\FakeNpisCloudStorage;
use Tests\TestCase;

class NpisOrganizerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private FakeNpisCloudStorage $cloud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(NpisDatabaseSeeder::class);
        Queue::fake();
        $this->cloud = new FakeNpisCloudStorage;
        $this->app->instance(NpisCloudStorage::class, $this->cloud);
        config([
            'npis.oidc.dev_secret' => str_repeat('c', 32),
            'npis.oidc.issuer' => null,
            'npis.oidc.audience' => null,
        ]);
    }

    public function test_organizer_session_and_dashboard_are_scoped_to_the_role(): void
    {
        [$policeToken] = $this->verifyContact('+2348033000001');
        $this->withToken($policeToken)->withHeader('Idempotency-Key', 'dash-reg-1')
            ->postJson('/api/v1/registrations', $this->policeDelegatePayload('Dashboard Officer'))->assertCreated();

        [$hotelToken] = $this->verifyContact('+2348033000002');
        $this->withToken($hotelToken)->withHeader('Idempotency-Key', 'dash-reg-2')
            ->postJson('/api/v1/registrations', $this->hotelDelegatePayload())->assertCreated();

        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->getJson('/api/v1/admin/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'super_admin')
            ->assertJsonPath('data.roleLabel', 'Super admin')
            ->assertJsonFragment(['permissions' => config('npis.organizer.roles.super_admin.permissions')])
            ->assertJsonStructure(['data' => ['userId', 'name', 'email', 'sessionExpiresAt']]);

        $this->withToken($admin)->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.event.eventId', 'npis-2026')
            ->assertJsonPath('data.registrations.total', 2)
            ->assertJsonPath('data.registrations.vendors', 0)
            ->assertJsonPath('data.registrations.oldestPendingApprovals.0.fullName', 'Dashboard Officer')
            ->assertJsonPath('data.travel.total', 2)
            ->assertJsonPath('data.accommodation.nextToAllocate.0.fullName', 'Hotel Delegate Officer')
            ->assertJsonPath('data.attendance.checkInsToday', 0)
            ->assertJsonPath('data.certificates.total', 0)
            ->assertJsonStructure([
                'data' => [
                    'registrations' => ['byStatus', 'byParticipantType', 'reviewQueue'],
                    'travel' => ['byMode', 'arrivalsByDate'],
                    'accommodation' => ['byAllocationStatus', 'roomNightsByNight'],
                    'training' => ['sessions', 'capacity', 'assigned', 'capacityUtilisation'],
                    'attendance' => ['checkInsByDate', 'today'],
                    'certificates' => ['byDeliveryStatus'],
                ],
            ]);

        $checkin = $this->adminToken('checkin');

        $this->withToken($checkin)->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.registrations.total', 2)
            ->assertJsonPath('data.attendance.total', 2)
            ->assertJsonMissingPath('data.travel')
            ->assertJsonMissingPath('data.accommodation')
            ->assertJsonMissingPath('data.training')
            ->assertJsonMissingPath('data.certificates');
    }

    public function test_event_settings_hotels_and_training_assignments_are_permission_scoped(): void
    {
        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->getJson('/api/v1/admin/event')
            ->assertOk()
            ->assertJsonPath('data.eventId', 'npis-2026')
            ->assertJsonPath('data.organizerAccess.updatedBy', 'System')
            ->assertJsonStructure(['data' => ['deadlines', 'policies', 'brochure', 'organizerAccess' => ['updatedAt', 'updatedBy']]]);

        $this->withToken($admin)->getJson('/api/v1/admin/hotels')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                'hotelId' => 'hotel_jabi',
                'name' => 'Jabi Lake Partner Hotel',
                'roomTypes' => ['Standard single', 'Executive'],
                'isActive' => true,
            ]);

        [$token] = $this->verifyContact('+2348033000003');
        $this->withToken($token)->withHeader('Idempotency-Key', 'assign-reg-1')
            ->postJson('/api/v1/registrations', $this->policeDelegatePayload('Assigned Officer'))->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $this->withToken($admin)->postJson("/api/v1/admin/registrations/{$registration->id}/review", ['action' => 'approve'])->assertOk();
        $this->withToken($admin)->postJson('/api/v1/admin/training/sessions/ses_road_1/assignments', [
            'registrationIds' => [$registration->id],
        ])->assertOk();

        $this->withToken($admin)->getJson('/api/v1/admin/training/sessions/ses_road_1/assignments')
            ->assertOk()
            ->assertJsonPath('data.session.sessionId', 'ses_road_1')
            ->assertJsonPath('data.session.assignedCount', 1)
            ->assertJsonPath('data.assignments.0.registrationId', $registration->id)
            ->assertJsonPath('data.assignments.0.fullName', 'Assigned Officer')
            ->assertJsonPath('data.assignments.0.assignedBy', 'Workflow super_admin');

        $this->withToken($admin)->getJson('/api/v1/admin/training/sessions/missing/assignments')
            ->assertStatus(404)
            ->assertJsonPath('data.code', 'NOT_FOUND');

        $checkin = $this->adminToken('checkin');

        $this->withToken($checkin)->getJson('/api/v1/admin/event')
            ->assertStatus(403)
            ->assertJsonPath('data.code', 'FORBIDDEN');

        $this->withToken($checkin)->getJson('/api/v1/admin/hotels')
            ->assertStatus(403)
            ->assertJsonPath('data.code', 'FORBIDDEN');

        $this->withToken($checkin)->getJson('/api/v1/admin/training/sessions/ses_road_1/assignments')
            ->assertStatus(403)
            ->assertJsonPath('data.code', 'FORBIDDEN');
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function verifyContact(string $destination): array
    {
        $verification = $this->postJson('/api/v1/verifications', ['channel' => 'sms', 'destination' => $destination])->assertCreated();
        $notification = Notification::query()->where('related_id', $verification->json('data.verificationId'))->firstOrFail();
        preg_match('/\b(\d{5})\b/', (string) $notification->body, $matches);

        $confirm = $this->postJson('/api/v1/verifications/'.$verification->json('data.verificationId').'/confirm', [
            'code' => $matches[1],
        ])->assertOk();

        return [$confirm->json('data.sessionToken'), $confirm->json('data.contact')];
    }

    private function adminToken(string $role): string
    {
        $grant = $this->postJson('/api/v1/admin/access', ['code' => 'NPIS-ORG-2026'])->assertCreated();

        $jwt = JWT::encode([
            'sub' => 'oidc-dashboard-'.$role,
            'email' => 'dashboard.'.$role.'@npis.ng',
            'name' => 'Workflow '.$role,
            'role' => $role,
            'exp' => now()->addHour()->timestamp,
            'iat' => now()->timestamp,
        ], str_repeat('c', 32), 'HS256');

        return $this->withToken($grant->json('data.accessToken'))
            ->postJson('/api/v1/admin/session', ['idToken' => $jwt])
            ->assertCreated()
            ->json('data.token');
    }

    /**
     * @return array<string, mixed>
     */
    private function policeDelegatePayload(string $fullName): array
    {
        return [
            'participantType' => 'delegate',
            'profile' => [
                'fullName' => $fullName,
                'sector' => 'police',
                'rankCode' => 'CSP',
                'stateCode' => 'KN',
                'commandId' => 'cmd_kn',
                'unitId' => 'cmd_kn_sid',
                'unitOtherName' => null,
                'organization' => null,
                'designation' => null,
                'vendorService' => null,
                'vendorServiceOther' => null,
            ],
            'itinerary' => [
                'travelMode' => 'air',
                'arrival' => ['date' => '2026-12-07', 'time' => null, 'timeWindow' => 'afternoon', 'planStatus' => 'preferred'],
                'departure' => ['date' => '2026-12-10', 'time' => null, 'timeWindow' => 'evening', 'planStatus' => 'preferred'],
                'postSummitPlan' => 'leave_immediately',
                'flight' => ['departureAirportCode' => 'KAN', 'flightTime' => null, 'flightTimeWindow' => 'morning', 'flightNumber' => null],
            ],
            'accommodation' => null,
            'interests' => ['track' => 'both', 'transportSubsectors' => ['road', 'air']],
            'photo' => null,
            'consent' => ['dataProcessing' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hotelDelegatePayload(): array
    {
        return [
            'participantType' => 'delegate',
            'profile' => [
                'fullName' => 'Hotel Delegate Officer',
                'sector' => 'hospitality',
                'rankCode' => null,
                'stateCode' => null,
                'commandId' => null,
                'unitId' => null,
                'unitOtherName' => null,
                'organization' => 'Hotel Owners Forum Abuja',
                'designation' => 'Chairperson',
                'vendorService' => null,
                'vendorServiceOther' => null,
            ],
            'itinerary' => [
                'travelMode' => 'road',
                'arrival' => ['date' => '2026-12-07', 'time' => null, 'timeWindow' => 'evening', 'planStatus' => 'confirmed'],
                'departure' => ['date' => '2026-12-11', 'time' => null, 'timeWindow' => 'morning', 'planStatus' => 'preferred'],
                'postSummitPlan' => 'leave_immediately',
                'flight' => null,
            ],
            'accommodation' => [
                'preference' => 'organizer_arranged',
                'checkInDate' => '2026-12-07',
                'checkOutDate' => '2026-12-11',
                'specialNeeds' => null,
                'allowanceRequested' => false,
                'payoutAccount' => null,
            ],
            'interests' => ['track' => 'hospitality', 'transportSubsectors' => []],
            'photo' => null,
            'consent' => ['dataProcessing' => true],
        ];
    }
}
