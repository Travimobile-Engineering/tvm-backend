<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManifestCheckerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */

    //  public function setUp(): void{
    //     parent::setUp();
    //     $this->refreshApplication();
    //  }

    public function test_add_incident(): void
    {
        $headers = [
            'X-SECURE-AUTH' => 'eLxPtkXlJdCbxo2LRdfSXB',
            'Accept' => 'application/json',
        ];

        $user = User::factory()->create();

        $user = Auth::loginUsingId($user->id);
        $token = JWTAuth::fromUser($user);
        $headers = array_merge($headers, [
            'Authorization' => 'Bearer '.$token
        ]);
        
        $data = [
            'user_id' => $user->id,
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
        $response = $this->post('api/manifest-checker/incident/add', $data, $headers);

        $this->assertDatabaseHas('incidents', [
            'user_id' => $user->id,
            'description' => 'A slight incident'
        ]);

        $response->assertStatus(200);
    }

    public function test_add_watchlist(){

        $headers = [
            'X-SECURE-AUTH' => 'eLxPtkXlJdCbxo2LRdfSXB',
            'Accept' => 'application/json',
        ];

        $user = User::factory()->create();

        $user = Auth::loginUsingId($user->id);
        $token = JWTAuth::fromUser($user);
        $headers = array_merge($headers, [
            'Authorization' => 'Bearer '.$token
        ]);

        $payload = [
            "full_name" => 'User Example',
            "phone" => '09012345678',
            "email" => 'email@example.com',
            "dob" => '1997-8-01',
            "state_of_origin" => 'Rivers',
            "nin" => '12345678909',
            "investigation_officer" => 'Isham Agat',
            "io_contact_number" => '09012345678',
            "alert_location" => 'Port Harcourt',
            "photo" => '',
            "documents" => '',
            "status" => 'active'
        ];

        $response = $this->post('api/manifest-checker/watch-list/add', $payload, $headers);
        $this->assertDatabaseHas('watch_lists', [
            'full_name' => 'User Example',
            'phone' => '09012345678',
            'email' => 'email@example.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_watchlist(){
        $headers = [
            'X-SECURE-AUTH' => 'eLxPtkXlJdCbxo2LRdfSXB',
            'Accept' => 'application/json',
        ];

        $user = User::factory()->create();

        $user = Auth::loginUsingId($user->id);
        $token = JWTAuth::fromUser($user);
        $headers = array_merge($headers, [
            'Authorization' => 'Bearer '.$token
        ]);

        $payload = [
            "full_name" => 'User Example',
            "phone" => '09012345678',
            "email" => 'email@example.com',
            "dob" => '1997-8-01',
            "state_of_origin" => 'Rivers',
            "nin" => '12345678909',
            "investigation_officer" => 'Isham Agat',
            "io_contact_number" => '09012345678',
            "alert_location" => 'Port Harcourt',
            "photo" => '',
            "documents" => '',
            "status" => 'active'
        ];

        $response = $this->post('api/manifest-checker/watch-list/add', $payload, $headers);

        $payload = [
            "full_name" => 'User Example2',
        ];
        $response = $this->post("api/manifest-checker/watch-list/update/" .json_decode($response->getContent())->data->id, $payload, $headers);
        $this->assertDatabaseHas('watch_lists', [
            'full_name' => 'User Example2',
            'phone' => '09012345678',
            'email' => 'email@example.com',
        ]);
        $response->assertStatus(200);
    }
}
