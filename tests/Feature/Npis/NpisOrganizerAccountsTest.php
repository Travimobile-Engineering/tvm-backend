<?php

namespace Tests\Feature\Npis;

use App\Mail\NpisOrganizerCredentialsMail;
use App\Models\Mailing;
use App\Models\Npis\AdminUser;
use Database\Seeders\Npis\NpisDatabaseSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NpisOrganizerAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(NpisDatabaseSeeder::class);
        Queue::fake();
        config([
            'npis.oidc.dev_secret' => str_repeat('d', 32),
            'npis.oidc.issuer' => null,
            'npis.oidc.audience' => null,
        ]);
    }

    public function test_super_admin_can_create_an_organizer_and_credentials_are_emailed(): void
    {
        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->getJson('/api/v1/admin/organizer-options')
            ->assertOk()
            ->assertJsonCount(5, 'data.roles')
            ->assertJsonFragment(['key' => 'organizers:manage', 'label' => 'Manage organizers'])
            ->assertJsonFragment(['value' => 'registration', 'label' => 'Registration desk']);

        $this->withToken($admin)->postJson('/api/v1/admin/organizers', [
            'name' => 'Tolu Adeyemi',
            'email' => 'New.Organizer@NPIS.ng',
            'role' => 'registration',
            'additionalPermissions' => ['accommodation:allocate', 'audit:read', 'registrations:read'],
            'password' => 'Str0ngPassw0rd',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'new.organizer@npis.ng')
            ->assertJsonPath('data.role', 'registration')
            ->assertJsonPath('data.roleLabel', 'Registration desk')
            ->assertJsonPath('data.additionalPermissions', ['accommodation:allocate', 'audit:read'])
            ->assertJsonPath('data.credentialsEmailed', true);

        $organizer = AdminUser::query()->where('email', 'new.organizer@npis.ng')->firstOrFail();

        $this->assertNotSame('Str0ngPassw0rd', $organizer->password);
        $this->assertTrue(Hash::check('Str0ngPassw0rd', (string) $organizer->password));
        $this->assertSame(['accommodation:allocate', 'audit:read'], $organizer->extra_permissions);
        $this->assertContains('accommodation:allocate', $organizer->permissions());
        $this->assertContains('registrations:read', $organizer->permissions());

        $mailing = Mailing::query()
            ->where('mailable', NpisOrganizerCredentialsMail::class)
            ->firstOrFail();

        $this->assertSame('new.organizer@npis.ng', $mailing->email);
        $this->assertSame('pending', $mailing->status);
        $this->assertSame(
            [
                'Tolu Adeyemi',
                'new.organizer@npis.ng',
                'Str0ngPassw0rd',
                'Registration desk',
                (string) config('npis.organizer.login_url'),
            ],
            array_values((array) $mailing->payload)
        );

        $this->assertDatabaseHas('npis_audit_events', ['action' => 'organizer.created']);
    }

    public function test_organizers_can_be_listed(): void
    {
        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->postJson('/api/v1/admin/organizers', $this->organizerPayload())->assertCreated();

        $this->withToken($admin)->getJson('/api/v1/admin/organizers?q=desk.organizer')
            ->assertOk()
            ->assertJsonPath('data.pagination.totalItems', 1)
            ->assertJsonPath('data.pagination.page', 1)
            ->assertJsonPath('data.data.0.email', 'desk.organizer@npis.ng')
            ->assertJsonPath('data.data.0.role', 'registration')
            ->assertJsonPath('data.data.0.roleLabel', 'Registration desk')
            ->assertJsonPath('data.data.0.isActive', true);

        $this->withToken($this->adminToken('registration'))
            ->getJson('/api/v1/admin/organizers')
            ->assertStatus(403)
            ->assertJsonPath('data.code', 'FORBIDDEN');
    }

    public function test_organizer_creation_validates_input_and_requires_the_manage_permission(): void
    {
        $admin = $this->adminToken('super_admin');

        $payload = $this->organizerPayload();

        $this->withToken($admin)->postJson('/api/v1/admin/organizers', $payload)->assertCreated();
        $this->withToken($admin)->postJson('/api/v1/admin/organizers', $payload)
            ->assertStatus(422)
            ->assertJsonPath('data.code', 'VALIDATION_FAILED');

        $this->withToken($admin)->postJson('/api/v1/admin/organizers', [
            ...$payload,
            'email' => 'weak.password@npis.ng',
            'password' => 'password',
        ])->assertStatus(422);

        $this->withToken($admin)->postJson('/api/v1/admin/organizers', [
            ...$payload,
            'email' => 'bad.role@npis.ng',
            'role' => 'owner',
        ])->assertStatus(422);

        $desk = $this->adminToken('registration');
        $this->withToken($desk)->postJson('/api/v1/admin/organizers', [
            ...$payload,
            'email' => 'not.allowed@npis.ng',
        ])->assertStatus(403)->assertJsonPath('data.code', 'FORBIDDEN');
    }

    public function test_organizer_can_sign_in_with_email_and_password(): void
    {
        $admin = $this->adminToken('super_admin');

        $this->withToken($admin)->postJson('/api/v1/admin/organizers', $this->organizerPayload())->assertCreated();

        $login = $this->postJson('/api/v1/admin/login', [
            'email' => 'desk.organizer@npis.ng',
            'password' => 'Str0ngPassw0rd',
        ])->assertCreated()
            ->assertJsonPath('data.user.role', 'registration')
            ->assertJsonStructure(['data' => ['token', 'user' => ['userId', 'name', 'role'], 'permissions', 'expiresAt']]);

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertContains('accommodation:allocate', $login->json('data.permissions'));

        $this->withToken($token)->getJson('/api/v1/admin/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'desk.organizer@npis.ng')
            ->assertJsonPath('data.role', 'registration');

        $this->postJson('/api/v1/admin/login', [
            'email' => 'desk.organizer@npis.ng',
            'password' => 'wrong-password',
        ])->assertStatus(401)->assertJsonPath('data.code', 'UNAUTHENTICATED');

        $this->postJson('/api/v1/admin/login', [
            'email' => 'missing.organizer@npis.ng',
            'password' => 'Str0ngPassw0rd',
        ])->assertStatus(401);

        AdminUser::query()->where('email', 'desk.organizer@npis.ng')->update(['is_active' => false]);

        $this->postJson('/api/v1/admin/login', [
            'email' => 'desk.organizer@npis.ng',
            'password' => 'Str0ngPassw0rd',
        ])->assertStatus(403)->assertJsonPath('data.code', 'FORBIDDEN');
    }

    /**
     * @return array<string, mixed>
     */
    private function organizerPayload(): array
    {
        return [
            'name' => 'Desk Organizer',
            'email' => 'desk.organizer@npis.ng',
            'role' => 'registration',
            'additionalPermissions' => ['accommodation:allocate', 'audit:read'],
            'password' => 'Str0ngPassw0rd',
        ];
    }

    private function adminToken(string $role): string
    {
        $grant = $this->postJson('/api/v1/admin/access', ['code' => 'NPIS-ORG-2026'])->assertCreated();

        $jwt = JWT::encode([
            'sub' => 'oidc-accounts-'.$role,
            'email' => 'accounts.'.$role.'@npis.ng',
            'name' => 'Accounts '.$role,
            'role' => $role,
            'exp' => now()->addHour()->timestamp,
            'iat' => now()->timestamp,
        ], str_repeat('d', 32), 'HS256');

        return $this->withToken($grant->json('data.accessToken'))
            ->postJson('/api/v1/admin/session', ['idToken' => $jwt])
            ->assertCreated()
            ->json('data.token');
    }
}
