<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->headers = [
            'Accept' => 'application/json',
            config('security.header_key') => config('security.header_value'),
        ];
    }

    public function test_account_signup(): void
    {

        $data = [
            'full_name' => 'Test User',
            'email' => 'testuser@example.com',
            'phone_number' => '08123456789',
            'user_category' => 'passenger',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $response = $this->postJson('/api/auth/signup', $data, $this->headers);

        // Email is encrypted at rest, so match via the blind index lookup and
        // confirm it round-trips back to plaintext on read.
        $user = User::byEmail('testuser@example.com')->where('first_name', 'Test')->first();

        $this->assertNotNull($user);
        $this->assertSame('testuser@example.com', $user->email);

        $response->assertStatus(201);
    }

    public function test_account_verification(): void
    {
        User::factory()->create(['email' => 'testuser@example.com']);
        $user = User::byEmail('testuser@example.com')
            ->where('verification_code_expires_at', '>=', now())
            ->first();

        $response = $this->postJson('/api/auth/verify/account', ['code' => $user->verification_code], $this->headers);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified' => 1,
        ]);

        $this->assertSame('testuser@example.com', $user->fresh()->email);

        $response->assertStatus(200);
    }
}
