<?php

namespace Database\Seeders;

use App\Models\RouteSubregion;
use App\Models\Trip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stateZoneMap = [
            'Adamawa' => 1, 'Bauchi' => 1, 'Borno' => 1, 'Gombe' => 1, 'Taraba' => 1, 'Yobe' => 1,
            'Jigawa' => 2, 'Kaduna' => 2, 'Kano' => 2, 'Katsina' => 2, 'Kebbi' => 2, 'Sokoto' => 2, 'Zamfara' => 2,
            'Benue' => 3, 'Kogi' => 3, 'Kwara' => 3, 'Nasarawa' => 3, 'Niger' => 3, 'Plateau' => 3, 'FCT' => 3,
            'Ekiti' => 4, 'Lagos' => 4, 'Ogun' => 4, 'Ondo' => 4, 'Osun' => 4, 'Oyo' => 4,
            'Abia' => 5, 'Anambra' => 5, 'Ebonyi' => 5, 'Enugu' => 5, 'Imo' => 5,
            'Akwa Ibom' => 6, 'Bayelsa' => 6, 'Cross River' => 6, 'Delta' => 6, 'Edo' => 6, 'Rivers' => 6,
        ];

        foreach ($stateZoneMap as $stateName => $zoneId) {
            DB::table('states')
                ->where('name', $stateName)
                ->update(['zone_id' => $zoneId]);
        }

        Trip::chunk(100, function ($trips) {
            foreach ($trips as $trip) {
                if (! $trip->destination) {
                    continue;
                }

                $route = RouteSubregion::with('state')->find($trip->destination);

                if ($route && $route->state && $route->state->zone_id) {
                    $trip->update(['zone_id' => $route->state->zone_id]);
                }
            }
        });
    }
}
