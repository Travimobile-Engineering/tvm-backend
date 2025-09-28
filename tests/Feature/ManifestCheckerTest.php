<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ManifestCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $user = Auth::loginUsingId($this->user->id);
        $this->token = JWTAuth::fromUser($user);

        $this->headers = [
            config('security.header_key') => config('security.header_value'),
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->token,
        ];
    }

    public function test_add_incident(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'category' => 'General Security Incident',
            'type' => 'Trespassing',
            'date' => now()->format('Y-m-d'),
            'time' => now(),
            'location' => 'Rivers, Port Harcourt',
            'description' => 'A slight incident',
            'media_url' => 'https://example.com',
            'severity_level' => 'Informational',
            'persons_of_interest' => 'person 1',
        ];
        $response = $this->post('api/manifest-checker/incident/add', $data, $this->headers);

        $this->assertDatabaseHas('incidents', [
            'user_id' => $this->user->id,
            'description' => 'A slight incident',
        ]);

        $response->assertStatus(200);
    }

    public function test_add_watchlist()
    {

        $payload = [
            'full_name' => 'User Example',
            'phone' => '09012345678',
            'email' => 'email@example.com',
            'dob' => '1997-8-01',
            'state_of_origin' => 'Rivers',
            'nin' => '12345678909',
            'investigation_officer' => 'Isham Agat',
            'io_contact_number' => '09012345678',
            'state_id' => '33',
            'city' => 'Port Harcourt',
            'photo' => '',
            'documents' => '',
            'status' => 'active',
        ];

        $response = $this->post('api/manifest-checker/watch-list/add', $payload, $this->headers);
        $this->assertDatabaseHas('watch_lists', [
            'full_name' => 'User Example',
            'phone' => '09012345678',
            'email' => 'email@example.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_watchlist()
    {

        $payload = [
            'full_name' => 'User Example',
            'phone' => '09012345678',
            'email' => 'email@example.com',
            'dob' => '1997-8-01',
            'state_of_origin' => 'Rivers',
            'nin' => '12345678909',
            'investigation_officer' => 'Isham Agat',
            'io_contact_number' => '09012345678',
            'state_id' => '33',
            'city' => 'Port Harcourt',
            'photo' => '',
            'documents' => '',
            'status' => 'active',
        ];

        $response = $this->post('api/manifest-checker/watch-list/add', $payload, $this->headers);

        $payload = [
            'full_name' => 'User Example2',
        ];
        $response = $this->post('api/manifest-checker/watch-list/update/'.json_decode($response->getContent())->data->id, $payload, $this->headers);
        $this->assertDatabaseHas('watch_lists', [
            'full_name' => 'User Example2',
            'phone' => '09012345678',
            'email' => 'email@example.com',
        ]);
        $response->assertStatus(200);
    }
}
