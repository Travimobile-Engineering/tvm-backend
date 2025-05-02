<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    
    protected $headers;

    public function setUp(): void{
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
        $response->dump();
        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'email' => 'testuser@example.com',
        ]);
        
        $response->assertStatus(201);
    }

    public function test_account_verification(): void
    {
        User::factory()->create(['email' => 'testuser@example.com']);
        $user = User::where('email', 'testuser@example.com')
            ->where('verification_code_expires_at', '>=', now())
            ->first();
        
        $response = $this->postJson('/api/auth/verify/account', ['code' => $user->verification_code], $this->headers);
        
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
            'email_verified' => 1,
        ]);
        
        $response->assertStatus(200);
    }
}
