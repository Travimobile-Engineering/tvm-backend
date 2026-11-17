<?php

namespace Tests\Feature\Npis;

use App\Contracts\NpisCloudStorage;
use App\Models\Npis\AuditEvent;
use App\Models\Npis\Notification;
use App\Models\Npis\Registration;
use App\Models\Npis\TrainingSession;
use Database\Seeders\Npis\NpisDatabaseSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Npis\FakeNpisCloudStorage;
use Tests\TestCase;

class NpisAdminWorkflowTest extends TestCase
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
            'npis.oidc.dev_secret' => str_repeat('b', 32),
            'npis.oidc.issuer' => null,
            'npis.oidc.audience' => null,
        ]);
    }

    public function test_accommodation_allocation_and_self_arranged_switch_release_the_room(): void
    {
        [$token] = $this->verifyContact('+2348032000001');
        $this->withToken($token)->withHeader('Idempotency-Key', 'acc-reg-1')
            ->postJson('/api/v1/registrations', $this->hotelDelegatePayload())->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $admin = $this->adminToken('super_admin');
        $this->withToken($admin)->postJson("/api/v1/admin/registrations/{$registration->id}/review", ['action' => 'approve'])->assertOk();

        $this->withToken($admin)->putJson("/api/v1/admin/registrations/{$registration->id}/accommodation-allocation", [
            'allocationStatus' => 'allocated',
            'hotelName' => 'Jabi Lake Partner Hotel',
            'roomType' => 'Executive',
        ])->assertOk()
            ->assertJsonPath('data.accommodation.allocationStatus', 'allocated')
            ->assertJsonPath('data.accommodation.allocation.hotelName', 'Jabi Lake Partner Hotel');

        $registration = $registration->fresh();
        $roomRequestId = $registration->accommodation->id;
        $this->assertDatabaseHas('npis_accommodation_allocations', ['accommodation_request_id' => $roomRequestId]);

        $body = [
            'preference' => 'self_arranged',
            'checkInDate' => null,
            'checkOutDate' => null,
            'specialNeeds' => null,
            'allowanceRequested' => false,
            'payoutAccount' => null,
        ];

        $this->withToken($token)->withHeader('If-Match', (string) $registration->version)
            ->putJson('/api/v1/registrations/me/accommodation', $body)
            ->assertOk()
            ->assertJsonPath('data.accommodation.preference', 'self_arranged')
            ->assertJsonPath('data.accommodation.allocationStatus', 'not_requested')
            ->assertJsonPath('data.accommodation.allocation', null);

        $this->assertDatabaseMissing('npis_accommodation_allocations', ['accommodation_request_id' => $roomRequestId]);
    }

    public function test_review_actions_resolve_unlisted_units_and_merge_duplicates_without_losing_the_original_tag(): void
    {
        [$firstToken] = $this->verifyContact('+2348032000002');
        $payload = $this->policeDelegatePayload('Duplicate Officer');
        $payload['profile']['unitId'] = null;
        $payload['profile']['unitOtherName'] = 'Highway Patrol Team 4';
        $first = $this->withToken($firstToken)->withHeader('Idempotency-Key', 'dup-1')
            ->postJson('/api/v1/registrations', $payload)
            ->assertCreated()
            ->assertJsonPath('data.reviewFlags.0', 'unlisted_unit');

        $firstId = $first->json('data.registrationId');
        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->postJson("/api/v1/admin/registrations/{$firstId}/review", [
            'action' => 'accept_unlisted_unit',
            'unitId' => 'cmd_kn_hq',
        ])->assertOk()->assertJsonPath('data.reviewFlags', []);

        [$secondToken] = $this->verifyContact('+2348032000003');
        $second = $this->withToken($secondToken)->withHeader('Idempotency-Key', 'dup-2')
            ->postJson('/api/v1/registrations', $this->policeDelegatePayload('Duplicate Officer'))
            ->assertCreated()
            ->assertJsonPath('data.reviewFlags.0', 'possible_duplicate');

        $secondId = $second->json('data.registrationId');
        $this->assertSame($firstId, $second->json('data.duplicateOfRegistrationId'));

        $this->withToken($admin)->getJson('/api/v1/admin/registrations?reviewFlag=possible_duplicate')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 1);

        $this->withToken($admin)->getJson('/api/v1/admin/registrations?track=both&subsector=road')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 2);

        $this->withToken($admin)->postJson("/api/v1/admin/registrations/{$firstId}/review", ['action' => 'approve'])
            ->assertOk()->assertJsonPath('data.status', 'approved');

        $merged = $this->withToken($admin)->postJson("/api/v1/admin/registrations/{$secondId}/review", [
            'action' => 'merge_duplicate',
            'mergeIntoRegistrationId' => $firstId,
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame($second->json('data.summitTagNumber'), $merged->json('data.summitTagNumber'));
        $this->assertNotSame($first->json('data.summitTagNumber'), $merged->json('data.summitTagNumber'));
        $this->assertDatabaseHas('npis_registrations', [
            'id' => $firstId,
            'summit_tag_number' => $first->json('data.summitTagNumber'),
        ]);
        $this->assertDatabaseHas('npis_registrations', [
            'id' => $secondId,
            'status' => 'cancelled',
            'merged_into_registration_id' => $firstId,
        ]);
        $this->assertDatabaseHas('npis_registrations', ['id' => $firstId, 'status' => 'approved']);
    }

    public function test_reports_reconcile_with_filtered_lists_and_enforce_role_data_restrictions(): void
    {
        [$policeToken] = $this->verifyContact('+2348032000004');
        $this->withToken($policeToken)->withHeader('Idempotency-Key', 'rep-1')
            ->postJson('/api/v1/registrations', $this->policeDelegatePayload('Report Officer'))->assertCreated();

        [$hotelToken] = $this->verifyContact('+2348032000005');
        $this->withToken($hotelToken)->withHeader('Idempotency-Key', 'rep-2')
            ->postJson('/api/v1/registrations', $this->hotelDelegatePayload())->assertCreated();

        $admin = $this->adminToken('super_admin');

        $report = $this->withToken($admin)->getJson('/api/v1/admin/reports/registration?status=pending_approval')
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->assertSame(2, $report->json('data.byStatus.1.count'));

        $this->withToken($admin)->getJson('/api/v1/admin/registrations?status=pending_approval')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 2);

        $this->withToken($admin)->getJson('/api/v1/admin/reports/travel')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.byMode.0.key', 'air');

        $checkin = $this->adminToken('checkin');
        $this->withToken($checkin)->getJson('/api/v1/admin/registrations')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 2);
        $this->withToken($checkin)->getJson('/api/v1/admin/reports/travel')
            ->assertStatus(403)
            ->assertJsonPath('data.code', 'FORBIDDEN');
        $this->withToken($checkin)->getJson('/api/v1/admin/registrations?travelMode=air')
            ->assertStatus(403)
            ->assertJsonPath('data.details.filter', 'travelMode');

        $firstRegistration = Registration::query()->orderBy('submitted_at')->firstOrFail();
        $this->withToken($checkin)->getJson('/api/v1/admin/registrations/'.$firstRegistration->id)
            ->assertOk()
            ->assertJsonPath('data.contact', null)
            ->assertJsonPath('data.itinerary', null)
            ->assertJsonPath('data.accommodation', null)
            ->assertJsonPath('data.interests', null);

        $registrationDesk = $this->adminToken('registration');
        $this->withToken($registrationDesk)->getJson('/api/v1/admin/reports/travel')->assertOk();
        $this->withToken($registrationDesk)->getJson('/api/v1/admin/reports/registration')
            ->assertOk()
            ->assertJsonPath('data.photosMissing', 0);
    }

    public function test_training_assignments_enforce_capacity_and_prevent_duplicates(): void
    {
        [$token] = $this->verifyContact('+2348032000006');
        $this->withToken($token)->withHeader('Idempotency-Key', 'train-reg')
            ->postJson('/api/v1/registrations', $this->policeDelegatePayload('Training Officer'))->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $admin = $this->adminToken('super_admin');
        $this->withToken($admin)->postJson("/api/v1/admin/registrations/{$registration->id}/review", ['action' => 'approve'])->assertOk();

        $assignment = $this->withToken($admin)->postJson('/api/v1/admin/training/sessions/ses_road_1/assignments', [
            'registrationIds' => [$registration->id],
        ])->assertOk()
            ->assertJsonPath('data.results.0.outcome', 'assigned')
            ->assertJsonPath('data.session.assignedCount', 1);

        $assignmentId = $assignment->json('data.results.0.assignment.assignmentId');

        $this->withToken($admin)->postJson('/api/v1/admin/training/sessions/ses_road_1/assignments', [
            'registrationIds' => [$registration->id],
        ])->assertOk()->assertJsonPath('data.results.0.outcome', 'already_assigned');

        $this->withToken($token)->getJson('/api/v1/registrations/me/schedule')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sessionId', 'ses_road_1');

        $this->withToken($admin)->deleteJson("/api/v1/admin/training/assignments/{$assignmentId}")->assertNoContent();

        TrainingSession::query()->whereKey('ses_road_2')->update(['capacity' => 0]);
        $this->withToken($admin)->postJson('/api/v1/admin/training/sessions/ses_road_2/assignments', [
            'registrationIds' => [$registration->id],
        ])->assertOk()->assertJsonPath('data.results.0.outcome', 'session_full');

        $created = $this->withToken($admin)->postJson('/api/v1/admin/training/sessions', [
            'title' => 'New Intelligence Workshop',
            'track' => 'hospitality',
            'subsector' => null,
            'date' => '2026-12-09',
            'startTime' => '09:00',
            'endTime' => '11:00',
            'room' => 'Hall D',
            'capacity' => 30,
        ])->assertCreated()->assertJsonPath('data.assignedCount', 0);

        $newSessionId = $created->json('data.sessionId');

        $this->withToken($admin)->putJson('/api/v1/admin/training/sessions/'.$newSessionId, [
            'title' => 'New Intelligence Workshop',
            'track' => 'hospitality',
            'subsector' => null,
            'date' => '2026-12-09',
            'startTime' => '09:00',
            'endTime' => '11:00',
            'room' => 'Hall D',
            'capacity' => 40,
        ])->assertOk()->assertJsonPath('data.capacity', 40);

        $this->withToken($admin)->deleteJson('/api/v1/admin/training/sessions/'.$newSessionId)->assertNoContent();
        $this->assertSoftDeleted('npis_training_sessions', ['id' => $newSessionId]);
    }

    public function test_event_and_faq_partial_updates_are_merged_and_audited_and_access_code_rotation_revokes_sessions(): void
    {
        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->putJson('/api/v1/admin/event', [
            'deadlines' => ['travelEditDeadline' => '2026-12-06T23:59:00+01:00'],
            'policies' => ['verification' => ['codeLength' => 6, 'codeTtlSeconds' => 600]],
            'welcomeMessage' => ['title' => 'Updated welcome'],
        ])->assertOk()
            ->assertJsonPath('data.welcomeMessage.title', 'Updated welcome')
            ->assertJsonPath('data.deadlines.registrationClosesAt', '2026-12-07T23:59:00+01:00')
            ->assertJsonPath('data.deadlines.travelEditDeadline', '2026-12-06T23:59:00+01:00')
            ->assertJsonPath('data.policies.approvalRequired', true)
            ->assertJsonPath('data.policies.verification.codeLength', 6)
            ->assertJsonPath('data.policies.verification.maxResends', 3);

        $this->getJson('/api/v1/event')->assertOk()->assertJsonPath('data.welcomeMessage.title', 'Updated welcome');

        $this->withToken($admin)->putJson('/api/v1/admin/faqs', [
            'items' => [
                ['faqId' => 'faq_01', 'question' => 'Updated registration question?', 'answer' => 'Updated answer for delegates.', 'category' => 'registration', 'published' => false],
                ['question' => 'New travel question?', 'answer' => 'New travel answer for delegates.', 'category' => 'travel', 'published' => true],
            ],
        ])->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.order', 2);

        $this->getJson('/api/v1/event/faqs')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.category', 'travel');

        $this->withToken($admin)->getJson('/api/v1/admin/audit-events?action=event.updated')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 1)
            ->assertJsonPath('data.data.0.actor.role', 'super_admin');

        $this->withToken($admin)->putJson('/api/v1/admin/access-code', ['code' => 'NEW-SECURE-CODE-2026'])
            ->assertNoContent();

        $this->withToken($admin)->getJson('/api/v1/admin/registrations')->assertStatus(401);

        $this->assertDatabaseHas('npis_audit_events', ['action' => 'access_code.rotated']);
        $metadata = AuditEvent::query()->where('action', 'access_code.rotated')->firstOrFail()->metadata;
        $this->assertArrayNotHasKey('code', $metadata);
    }

    public function test_badge_override_and_photo_upload_and_removal(): void
    {
        [$token] = $this->verifyContact('+2348032000007');
        $this->withToken($token)->withHeader('Idempotency-Key', 'photo-reg')
            ->postJson('/api/v1/registrations', $this->policeDelegatePayload('Photo Officer'))->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $image = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($image, null, 80);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        $photo = 'data:image/jpeg;base64,'.base64_encode($jpeg);

        $this->withToken($token)->putJson('/api/v1/registrations/me/photo', ['photo' => $photo])
            ->assertOk()
            ->assertJsonPath('data.badge.photo.source', 'registrant');

        $badge = $registration->fresh()->badge;
        $photoPublicId = $badge->photo_public_id;
        $this->assertNotNull($badge->photo_url);
        $this->assertNotNull($this->cloud->fetch($badge->photo_url));

        $admin = $this->adminToken('super_admin');
        $this->withToken($admin)->putJson("/api/v1/admin/registrations/{$registration->id}/badge", [
            'category' => 'VP',
            'note' => 'Protocol list',
        ])->assertOk()
            ->assertJsonPath('data.badge.category.code', 'VP')
            ->assertJsonPath('data.badge.source', 'assigned');

        $this->withToken($admin)->putJson("/api/v1/admin/registrations/{$registration->id}/photo", ['photo' => null])
            ->assertOk()
            ->assertJsonPath('data.badge.photo', null);

        $this->assertNull($registration->fresh()->badge->photo_url);
        $this->assertNull($this->cloud->contentsForPublicId($photoPublicId));
    }

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
            'sub' => 'oidc-workflow-'.$role,
            'email' => 'workflow.'.$role.'@npis.ng',
            'name' => 'Workflow '.$role,
            'role' => $role,
            'exp' => now()->addHour()->timestamp,
            'iat' => now()->timestamp,
        ], str_repeat('b', 32), 'HS256');

        return $this->withToken($grant->json('data.accessToken'))
            ->postJson('/api/v1/admin/session', ['idToken' => $jwt])
            ->assertCreated()
            ->json('data.token');
    }

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
