<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WalletSetupTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        $this->headers = [
            'Accept' => 'application/json',
            config('security.header_key') => config('security.header_value'),
            'Authorization' => 'Bearer '.$this->token,
        ];

        (new BankSeeder)->run();
    }

    public function test_wallet_setup(): void
    {
        $payload = [
            'user_id' => $this->user->id,
            'bank_name' => 'Access Bank',
            'account_number' => '0123456789',
            'account_name' => 'Test Account',
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ];
        $response = $this->postJson('api/user/wallet/setup', $payload, $this->headers);

        $this->assertDatabaseHas('user_banks', [
            'bank_name' => $payload['bank_name'],
            'account_number' => $payload['account_number'],
            'account_name' => $payload['account_name'],
        ]);

        $this->assertDatabaseCount('user_pins', 1);
        $response->assertStatus(201);
    }
}
