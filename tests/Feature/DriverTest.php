<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Enum\PremiumUpgradeStatus;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Laravel\Prompts\table;
use Illuminate\Support\Facades\Auth;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverTest extends TestCase
{
    use RefreshDatabase;

    protected $header;
    public function setUp():void{
        parent::setUp();
        $this->header = [
            'Accept' => 'application/json',
            config('security.header_key') => config('security.header_value'),
        ];
    }

    // public $header = [
    //     'Accept' => 'application/json',
    //     config('security.header_key') => config('security.header_value'),
    // ];

    //Simulate/create a user and login without crendetials
    private function createLoginUser(){
        $user = User::factory()->create();
        Auth::loginUsingId($user->id);
        return $user;
    }
    //create token that represent authenticated user/set headers
    private function setTokenHeader(User $user){
        $token = JWTAuth::fromUser($user);
        $this->header = array_merge($this->header, [
            'Authorization' => 'Bearer ' . $token
        ]);
        return $this->header;
    }

    //create a transit company union & return its ID
    private function createTransitCompanyUnion(){
        return DB::table('transit_company_unions')
        ->insertGetId([
            'name' => 'NURTW',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    //create astate & return its ID
    private function createState(){
        return DB::table('states')
        ->insertGetId([
            'name' => 'Lagos',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function createVehicleBrands(){
        return DB::table('vehicle_brands')
        ->insertGetId([
            'name' => 'Toyota',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    
    private function createTransitCompany(User $user, $transit_company_union_id, $state_id){
        return DB::table('transit_companies')
        ->insertGetId([
            'user_id' => $user->id,
            'name' => 'First Transit Company',
            'country_code' => 'US',
            'union_states_chapter' => $state_id,
            'type' => 'cooperate',
            'union_id' => $transit_company_union_id,
        ]);
    }
    private function createVehicle(User $user, $vehicleBrand_id, $transitCompany_id){
        return DB::table('vehicles')
        ->insertGetId( [
            'user_id' => $user->id,
            'description' => 'First Body',
            'company_id' => $transitCompany_id,
            'brand_id' => $vehicleBrand_id,
            'plate_no' => 'BDG757FB',
            'color' => 'Red',
            'model' => 'Toyota',
            'seats' => json_encode(['A1', 'A2', 'A3','B1', 'B2', 'B3']),
            'air_conditioned' => 1,
            'status' => 1,
            
        ]);
    }

    private function createPremiumUpgrade(User $user, $vehicle_id){
        return DB::table('premium_upgrades')
        ->insertGetId([
            'user_id' => $user->id,
            'vehicle_id ' => $vehicle_id,
            'management_type' => 'travi_hire',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
       
    //payload for driver's onboarding
    private function driverOnboardingPayload(User $user, $transit_company_union_id, $state_id){
        return [
            'user_id' => $user->id,
            'transit_company_union_id' => $transit_company_union_id,
            'vehicle_year' => 2020,
            'vehicle_model' => 'Toyota',
            'vehicle_color' => 'Red',
            'plate_number' => 'BDG757FB',
            'vehicle_type' => 'Mini Bus',
            'vehicle_capacity' => 6,
            'profile_photo' => null,
            'nin' => '1234567890',
            'nin_photo' => null,
            'license_photo' => null,
            'license_number' => 'L1234567890',
            'license_expiration_date' => '2025-12-31',
            'vehicle_insurance_photo' => null,
            'vehicle_insurance_expiration_date' => '2025-12-31',
            'seats' => json_encode(['A1', 'A2', 'A3','B1', 'B2', 'B3']),
            'seat_row' => 3,
            'seat_column' => 2,
            'union_states_chapter' => $state_id
        ];
    }

    private function driverPremiumUpgradePayload(User $user, $vehicle_id){
        return [
            'user_id' => $user->id,
            'vehicle_id' => $vehicle_id,
            'management_type' => 'travi_hire',
            'is_ac_available' => 1,
            'vehicle_interior_images' => ['interior_image1.jpg', 'interior_image2.jpg'],
            'vehicle_exterior_images' => ['exterior_image1.jpg', 'exterior_image2.jpg'],
        ];
    }
    private function assertDriverOnboardingData(User $user){
        $this->assertDatabaseHas('transit_companies', [
            'user_id' => $user->id,
            'name' => $user->first_name,
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'plate_no' => 'BDG757FB',
        ]);
        
        $this->assertDatabaseHas('users',[
            'id' => $user->id,
            'user_category' => 'driver',
        ]);
    }

    public function assertDriverPremiumUpgradeData(User $user, $vehicle_id){

        $this->assertDatabaseHas('premium_upgrades', [
            'user_id' => $user->id,
            'vehicle_id' => $vehicle_id,
            'management_type' => 'travi_hire',
            'status' => PremiumUpgradeStatus::PENDING,
        ]);
    }
    public function test_driver_onboarding_passed_test(){
        $user = $this->createLoginUser();
        $headers = $this->setTokenHeader($user);
        $transit_company_union_id = $this->createTransitCompanyUnion();
        $state_id = $this->createState();
        $payload = $this->driverOnboardingPayload($user, $transit_company_union_id, $state_id);
        //send the post request
        $response = $this->post('/api/driver/onboarding', $payload, $headers);
        //assert the response
        //dump(DB::table('vehicles')->get()->toArray());
        $this->assertDriverOnboardingData($user);
        $response->assertStatus(201);
    }

    public function test_drivers_vehicle_setup_passed_test(){
        $user = $this->createLoginUser();
        $header = $this->setTokenHeader($user);
        $vehicleBrand_id = $this->createVehicleBrands();
        $transit_company_union_id = $this->createTransitCompanyUnion();
        $state_id = $this->createState();
        $transitCompany_id = $this->createTransitCompany($user, $transit_company_union_id, $state_id);
        $vehicle_id = $this->createVehicle($user,$vehicleBrand_id, $transitCompany_id);
        $payload = [
            'user_id' => $user->id,
            'description' => 'Second Body'
        ];
        $response = $this->post('/api/driver/setup-vehicle', $payload, $header);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle_id,
            'description' => 'Second Body',
        ]);
        $response->assertStatus(200);
    }

    // public function test_driver_vehicle_requirement(){
    //     $user = $this->createLoginUser();
    //     $header = $this->setTokenHeader($user);
    //     $vehicleBrand_id = $this->createVehicleBrands();
    //     $transit_company_union_id = $this->createTransitCompanyUnion();
    //     $state_id = $this->createState();
    //     $transitCompany_id = $this->createTransitCompany($user, $transit_company_union_id, $state_id);
    //     $vehicle_id = $this->createVehicle($user,$vehicleBrand_id, $transitCompany_id);
    //     $payload = $this->driverPremiumUpgradePayload($user, $vehicle_id);
    //     $response = $this->postJson('/api/driver/vehicle-requirements', $payload, $header);
    //     $this->assertDriverPremiumUpgradeData($user, $vehicle_id);
    //     $response->dump();
    //     $response->assertStatus(200);
    // }
}