<?php

namespace Tests\Feature\Npis;

use App\Contracts\NpisCloudStorage;
use App\Jobs\Npis\GenerateExportJob;
use App\Models\Npis\ExportJob;
use App\Models\Npis\Notification;
use App\Models\Npis\Registration;
use App\Models\Npis\VerifiedContact;
use App\Support\Npis\TagNumber;
use Database\Seeders\Npis\NpisDatabaseSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Npis\FakeNpisCloudStorage;
use Tests\TestCase;

class NpisApiTest extends TestCase
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
            'npis.oidc.dev_secret' => str_repeat('a', 32),
            'npis.oidc.issuer' => null,
            'npis.oidc.audience' => null,
            'npis.oidc.auto_provision' => true,
        ]);
    }

    public function test_public_event_reference_and_protocol_endpoints_match_the_contract(): void
    {
        $this->getJson('/api/v1/event')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Event retrieved successfully')
            ->assertJsonPath('data.eventId', 'npis-2026')
            ->assertJsonPath('data.policies.certificateMinDaysAttended', 2)
            ->assertJsonStructure(['data' => ['deadlines' => ['registrationClosesAt'], 'venue' => ['name', 'address']]]);

        $this->getJson('/api/v1/event/faqs')
            ->assertOk()
            ->assertJsonCount(11, 'data');

        $this->getJson('/api/v1/reference/states')
            ->assertOk()
            ->assertJsonCount(37, 'data')
            ->assertJsonFragment(['code' => 'FC', 'name' => 'Federal Capital Territory']);

        $this->getJson('/api/v1/reference/commands?stateCode=KN')
            ->assertOk()
            ->assertJsonFragment(['commandId' => 'cmd_kn']);

        $this->getJson('/api/v1/reference/units?commandId=cmd_kn')
            ->assertOk()
            ->assertJsonFragment(['unitId' => 'cmd_kn_sid']);

        $this->getJson('/api/v1/reference/protocol')
            ->assertOk()
            ->assertJsonPath('data.status', 'proposed')
            ->assertJsonCount(10, 'data.categories')
            ->assertJsonPath('data.categories.0.code', 'VP');

        $this->getJson('/api/v1/reference/commands')
            ->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('data.code', 'VALIDATION_FAILED');
    }

    public function test_phone_verification_issues_a_two_hour_session_without_disclosing_registration_details(): void
    {
        $response = $this->postJson('/api/v1/verifications', [
            'channel' => 'sms',
            'destination' => '+2348031234567',
        ])->assertCreated()
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.codeLength', 5)
            ->assertJsonMissingPath('data.destination');

        $verificationId = $response->json('data.verificationId');
        $this->assertStringContainsString('***', $response->json('data.maskedDestination'));

        $notification = Notification::query()->where('related_type', 'verification')->latest()->firstOrFail();
        preg_match('/\b(\d{5})\b/', (string) $notification->body, $matches);
        $this->assertNotEmpty($matches);

        $this->postJson("/api/v1/verifications/{$verificationId}/confirm", ['code' => '00000'])
            ->assertStatus(400)
            ->assertJsonPath('data.code', 'CODE_INCORRECT');

        $confirm = $this->postJson("/api/v1/verifications/{$verificationId}/confirm", ['code' => $matches[1]])
            ->assertOk()
            ->assertJsonPath('data.existingRegistration', null)
            ->assertJsonPath('data.contact.destination', '+2348031234567');

        $this->assertNotEmpty($confirm->json('data.sessionToken'));
        $this->assertDatabaseHas('npis_participant_sessions', ['contact_id' => VerifiedContact::query()->firstOrFail()->id]);
    }

    public function test_registration_creates_a_checked_summit_tag_and_blocks_a_second_active_registration(): void
    {
        [$token] = $this->verifyContact();

        $payload = $this->delegatePayload();
        $payload['profile']['unitId'] = null;
        $payload['profile']['unitOtherName'] = 'Anti-Kidnapping Squad';

        $response = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'reg-key-1')
            ->postJson('/api/v1/registrations', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_approval')
            ->assertJsonPath('data.badge.category.code', 'DL')
            ->assertJsonPath('data.reviewFlags.0', 'unlisted_unit');

        $tag = $response->json('data.summitTagNumber');
        $this->assertMatchesRegularExpression('/^NPIS26-[23456789ABCDEFGHJKMNPQRSTVWXYZ]{5}$/', $tag);
        $this->assertDatabaseHas('npis_registrations', ['summit_tag_number' => $tag]);

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'reg-key-1')
            ->postJson('/api/v1/registrations', $payload)
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true');

        $different = $payload;
        $different['profile']['fullName'] = 'Different Officer Name';

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'reg-key-1')
            ->postJson('/api/v1/registrations', $different)
            ->assertStatus(422)
            ->assertJsonPath('data.code', 'IDEMPOTENCY_KEY_REUSED');

        [$secondToken] = $this->verifyContact('+2348031234568');
        $this->withToken($secondToken)
            ->withHeader('Idempotency-Key', 'reg-key-2')
            ->postJson('/api/v1/registrations', $this->delegatePayload('Another Officer'))
            ->assertCreated();

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'reg-key-3')
            ->postJson('/api/v1/registrations', $payload)
            ->assertStatus(409)
            ->assertJsonPath('data.code', 'REGISTRATION_EXISTS');
    }

    public function test_vendor_registration_uses_vendor_tag_and_badge_and_rejects_travel_fields(): void
    {
        [$token] = $this->verifyContact('+2348031234569');

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'vendor-1')
            ->postJson('/api/v1/registrations', $this->vendorPayload())
            ->assertCreated()
            ->assertJsonPath('data.participantType', 'vendor')
            ->assertJsonPath('data.badge.category.code', 'MD')
            ->assertJsonPath('data.itinerary', null)
            ->assertJsonPath('data.accommodation', null)
            ->assertJsonPath('data.interests', null);

        $registration = Registration::query()->firstOrFail();
        $this->assertMatchesRegularExpression('/^NPIS26-V-[23456789ABCDEFGHJKMNPQRSTVWXYZ]{5}$/', $registration->summit_tag_number);
        $this->assertSame($registration->summit_tag_number, TagNumber::validate($registration->summit_tag_number));
    }

    public function test_itinerary_updates_require_if_match_and_increment_the_version(): void
    {
        [$token] = $this->verifyContact('+2348031234570');

        $this->withToken($token)->withHeader('Idempotency-Key', 'it-1')
            ->postJson('/api/v1/registrations', $this->delegatePayload())->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $itinerary = $this->delegatePayload()['itinerary'];
        $itinerary['arrival']['timeWindow'] = 'evening';
        $itinerary['arrival']['planStatus'] = 'preferred';

        $this->withToken($token)->putJson('/api/v1/registrations/me/itinerary', $itinerary)
            ->assertStatus(428)
            ->assertJsonPath('data.details.fields.If-Match', 'Required');

        $this->withToken($token)->withHeader('If-Match', '999')
            ->putJson('/api/v1/registrations/me/itinerary', $itinerary)
            ->assertStatus(412)
            ->assertJsonPath('data.code', 'VERSION_CONFLICT');

        $this->withToken($token)->withHeader('If-Match', (string) $registration->version)
            ->putJson('/api/v1/registrations/me/itinerary', $itinerary)
            ->assertOk()
            ->assertJsonPath('data.itinerary.arrival.timeWindow', 'evening')
            ->assertJsonPath('data.version', $registration->version + 1);
    }

    public function test_organizer_access_oidc_session_permissions_and_check_in_duplicate_handling(): void
    {
        [$participantToken] = $this->verifyContact('+2348031234571');
        $this->withToken($participantToken)->withHeader('Idempotency-Key', 'chk-reg')
            ->postJson('/api/v1/registrations', $this->delegatePayload('Check In Delegate'))
            ->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $adminToken = $this->adminToken('super_admin');

        $this->withToken($adminToken)->postJson("/api/v1/admin/registrations/{$registration->id}/review", [
            'action' => 'approve',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $payload = ['summitTagNumber' => $registration->summit_tag_number, 'eventDate' => '2026-12-08', 'method' => 'qr'];

        $first = $this->withToken($adminToken)->withHeader('Idempotency-Key', 'checkin-1')
            ->postJson('/api/v1/admin/check-ins', $payload)
            ->assertCreated()
            ->assertJsonPath('data.replayed', false)
            ->assertJsonPath('data.officer.badge.category.code', 'DL');

        $this->assertDatabaseCount('npis_idempotency_records', 2);
        $this->assertDatabaseHas('npis_idempotency_records', ['idempotency_key' => 'checkin-1']);

        $second = $this->withToken($adminToken)->withHeader('Idempotency-Key', 'checkin-1')
            ->postJson('/api/v1/admin/check-ins', $payload)
            ->assertStatus(201)
            ->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame($first->json('data.checkIn.checkInId'), $second->json('data.checkIn.checkInId'));

        $this->withToken($adminToken)->withHeader('Idempotency-Key', 'checkin-2')
            ->postJson('/api/v1/admin/check-ins', $payload)
            ->assertOk()
            ->assertJsonPath('data.replayed', true);

        $this->withToken($adminToken)->getJson('/api/v1/admin/check-ins?eventDate=2026-12-08')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_certificate_eligibility_issue_and_retry_reuses_the_same_certificate(): void
    {
        [$participantToken] = $this->verifyContact('+2348031234572');
        $this->withToken($participantToken)->withHeader('Idempotency-Key', 'cert-reg')
            ->postJson('/api/v1/registrations', $this->delegatePayload('Certificate Delegate'))->assertCreated();

        $registration = Registration::query()->firstOrFail();
        $adminToken = $this->adminToken('super_admin');
        $this->withToken($adminToken)->postJson("/api/v1/admin/registrations/{$registration->id}/review", ['action' => 'approve'])->assertOk();

        foreach (['2026-12-08', '2026-12-09'] as $index => $date) {
            $this->withToken($adminToken)->withHeader('Idempotency-Key', 'cert-checkin-'.$index)
                ->postJson('/api/v1/admin/check-ins', ['summitTagNumber' => $registration->summit_tag_number, 'eventDate' => $date])
                ->assertCreated();
        }

        $issue = $this->withToken($adminToken)->withHeader('Idempotency-Key', 'cert-issue-1')
            ->postJson('/api/v1/admin/certificates', ['registrationIds' => [$registration->id]])
            ->assertCreated()
            ->assertJsonPath('data.results.0.outcome', 'issued');

        $certificateId = $issue->json('data.results.0.certificate.certificateId');
        $certificateNumber = $issue->json('data.results.0.certificate.certificateNumber');

        $this->withToken($adminToken)->withHeader('Idempotency-Key', 'cert-issue-2')
            ->postJson('/api/v1/admin/certificates', ['registrationIds' => [$registration->id]])
            ->assertCreated()
            ->assertJsonPath('data.results.0.outcome', 'already_issued')
            ->assertJsonPath('data.results.0.certificate.certificateNumber', $certificateNumber);

        $this->withToken($participantToken)->getJson('/api/v1/registrations/me/certificate')
            ->assertOk()
            ->assertJsonPath('data.certificateNumber', $certificateNumber)
            ->assertJsonStructure(['data' => ['downloadUrl', 'expiresAt']]);

        $this->assertDatabaseCount('npis_certificates', 1);
        $this->assertDatabaseHas('npis_certificates', ['id' => $certificateId, 'delivery_status' => 'queued']);
    }

    public function test_export_row_count_reconciles_and_csv_escapes_formula_injection(): void
    {
        [$participantToken] = $this->verifyContact('+2348031234573');
        $payload = $this->nonPoliceDelegatePayload();
        $payload['profile']['organization'] = '=SUM(A1:A2)';
        $this->withToken($participantToken)->withHeader('Idempotency-Key', 'exp-reg')
            ->postJson('/api/v1/registrations', $payload)->assertCreated();

        $adminToken = $this->adminToken('super_admin');
        $registration = Registration::query()->firstOrFail();

        $this->withToken($adminToken)->getJson('/api/v1/admin/registrations?participantType=delegate')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 1);

        $export = $this->withToken($adminToken)->withHeader('Idempotency-Key', 'exp-1')
            ->postJson('/api/v1/admin/exports', [
                'filters' => ['participantType' => 'delegate'],
                'fields' => ['summitTagNumber', 'fullName', 'organization'],
                'format' => 'csv',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.rowCount', 1);

        $exportId = $export->json('data.exportId');
        $job = new GenerateExportJob($exportId);
        app()->call([$job, 'handle']);

        $this->withToken($adminToken)->getJson("/api/v1/admin/exports/{$exportId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.rowCount', 1)
            ->assertJsonPath('data.downloadUrl', fn ($value) => is_string($value));

        $exportModel = ExportJob::query()->findOrFail($exportId);
        $csv = $this->cloud->contentsForPublicId($exportModel->file_public_id);
        $this->assertNotNull($csv);
        $this->assertStringContainsString("'=SUM(A1:A2)", $csv);
        $this->assertSame(1, substr_count($csv, $registration->summit_tag_number));
    }

    private function verifyContact(string $destination = '+2348031234567'): array
    {
        $verification = $this->postJson('/api/v1/verifications', [
            'channel' => 'sms',
            'destination' => $destination,
        ])->assertCreated();

        $notification = Notification::query()
            ->where('related_type', 'verification')
            ->where('related_id', $verification->json('data.verificationId'))
            ->firstOrFail();

        preg_match('/\b(\d{5})\b/', (string) $notification->body, $matches);
        $this->assertNotEmpty($matches, 'The verification notification should contain the code.');

        $confirm = $this->postJson('/api/v1/verifications/'.$verification->json('data.verificationId').'/confirm', [
            'code' => $matches[1],
        ])->assertOk();

        return [$confirm->json('data.sessionToken'), $confirm->json('data.contact')];
    }

    private function adminToken(string $role = 'super_admin'): string
    {
        $grant = $this->postJson('/api/v1/admin/access', ['code' => 'NPIS-ORG-2026'])->assertCreated();

        $token = JWT::encode([
            'sub' => 'oidc-'.$role,
            'email' => $role.'@npis.ng',
            'name' => 'Test '.$role,
            'role' => $role,
            'exp' => now()->addHour()->timestamp,
            'iat' => now()->timestamp,
        ], str_repeat('a', 32), 'HS256');

        return $this->withToken($grant->json('data.accessToken'))
            ->postJson('/api/v1/admin/session', ['idToken' => $token])
            ->assertCreated()
            ->json('data.token');
    }

    private function delegatePayload(string $fullName = 'Musa Abdullahi Bello'): array
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

    private function vendorPayload(): array
    {
        return [
            'participantType' => 'vendor',
            'profile' => [
                'fullName' => 'Samuel Adewale Johnson',
                'sector' => null,
                'rankCode' => null,
                'stateCode' => null,
                'commandId' => null,
                'unitId' => null,
                'unitOtherName' => null,
                'organization' => 'Lens & Light Studios',
                'designation' => 'Lead Photographer',
                'vendorService' => 'photographer',
                'vendorServiceOther' => null,
            ],
            'itinerary' => null,
            'accommodation' => null,
            'interests' => null,
            'photo' => null,
            'consent' => ['dataProcessing' => true],
        ];
    }

    private function nonPoliceDelegatePayload(): array
    {
        return [
            'participantType' => 'delegate',
            'profile' => [
                'fullName' => 'Grace Ene Ochai',
                'sector' => 'transport',
                'rankCode' => null,
                'stateCode' => null,
                'commandId' => null,
                'unitId' => null,
                'unitOtherName' => null,
                'organization' => 'Road Transport Employers Association',
                'designation' => 'Zonal Chairman',
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
            'interests' => ['track' => 'transportation', 'transportSubsectors' => ['road']],
            'photo' => null,
            'consent' => ['dataProcessing' => true],
        ];
    }
}
