<?php

namespace Tests\Feature\Emf;

use App\Contracts\NpisCloudStorage;
use App\Models\Emf\DutyPoint;
use App\Models\Emf\Lga;
use App\Models\Emf\Officer;
use App\Models\Emf\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Npis\FakeNpisCloudStorage;
use Tests\TestCase;

abstract class EmfApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Officer $officer;

    protected Officer $responder;

    protected string $token;

    protected FakeNpisCloudStorage $cloud;

    protected function setUp(): void
    {
        parent::setUp();

        State::query()->create(['code' => 'LA', 'name' => 'Lagos', 'sort_order' => 1]);
        Lga::query()->create(['id' => 'lga_test', 'state_code' => 'LA', 'name' => 'Eti-Osa']);
        DutyPoint::query()->create([
            'id' => 'dp_test',
            'state_code' => 'LA',
            'lga_id' => 'lga_test',
            'name' => 'Lekki Test Checkpoint',
            'type' => 'motor_park',
            'address' => 'Lekki, Lagos',
            'latitude' => 6.4478,
            'longitude' => 3.4723,
            'shift_start' => '06:00',
            'shift_end' => '18:00',
            'officers_count' => 4,
            'grants_count' => 1,
            'status' => 'active',
        ]);

        $this->officer = $this->makeOfficer('off_test', 'NPF-1001', 'Adaemu Test Officer', 'ada@test.local', 'state_commander', 'dp_test');
        $this->responder = $this->makeOfficer('off_responder', 'NPF-1002', 'Grace Test Responder', 'grace@test.local', 'duty_officer', 'dp_test');

        $this->cloud = new FakeNpisCloudStorage;
        $this->app->instance(NpisCloudStorage::class, $this->cloud);

        $response = $this->postJson('/api/v1/manifest-checker/auth/login', [
            'serviceNumber' => 'NPF-1001',
            'password' => 'Password@123',
        ])->assertOk();

        $this->token = (string) $response->json('data.token');
        $this->withHeader('Authorization', 'Bearer '.$this->token);
    }

    protected function makeOfficer(string $id, string $service, string $name, string $email, string $role, ?string $point): Officer
    {
        return Officer::query()->create([
            'id' => $id,
            'service_number' => $service,
            'rank' => 'Superintendent',
            'full_name' => $name,
            'email' => $email,
            'phone' => '+2348000000000',
            'unit' => 'Manifest Enforcement',
            'state_code' => 'LA',
            'lga_id' => 'lga_test',
            'duty_point_id' => $point,
            'role' => $role,
            'status' => 'on_duty',
            'language' => 'en',
            'password_hash' => Hash::make('Password@123'),
            'watchlist_edit_code_hash' => Hash::make('123456'),
            'last_active_at' => now(),
            'checks_today' => 0,
        ]);
    }
}
