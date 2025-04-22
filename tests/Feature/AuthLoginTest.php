<?php

namespace Tests\Feature;

use App\Enum\UserStatus;
use App\Enum\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;
    protected array $headers;

    public function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            config('security.header_key', 'X-SECURE-AUTH') => config('security.header_value'),
        ];
    }

    #[Test]
    public function it_logs_in_user_with_valid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => UserStatus::ACTIVE->value,
            'email_verified' => true,
            'user_category' => UserType::PASSENGER->value,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'email', 'user_category'],
                ]
            ]);
    }

    #[Test]
    public function it_blocks_user_after_five_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => Hash::make('correct_password'),
            'status' => UserStatus::ACTIVE->value,
            'email_verified' => true,
            'user_category' => UserType::PASSENGER->value,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'blocked@example.com',
                'password' => 'wrong_password',
            ], $this->headers);
        }

        $user->refresh();

        $this->assertEquals(UserStatus::BLOCKED->value, $user->status->value);
        $this->assertEquals(UserStatus::FAILED_LOGIN_ATTEMPTS->value, $user->reason);
    }

    #[Test]
    public function it_rejects_unverified_email()
    {
        User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('password'),
            'email_verified' => false,
            'status' => UserStatus::ACTIVE->value,
            'user_category' => UserType::PASSENGER->value,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'password',
        ], $this->headers);

        $response->assertStatus(400)
                 ->assertJsonFragment([
                    'message' => 'Email has not been verified!',
                 ]);
    }
}
