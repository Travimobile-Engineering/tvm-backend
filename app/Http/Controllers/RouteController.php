<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function getCoveredRoutes(){
        $coveredRoutes = DB::table('covered_routes')->get();

        $data = collect();

        foreach($coveredRoutes as $key => $value){

            $route = array([
                'from_region' => DB::table('states')->where('id', $value->from_region_id)->pluck('name')->first(),
                'from_subregion' => DB::table('route_subregions')->where('id', $value->from_subregion_id)->pluck('name')->first(),
                'to_region' => DB::table('states')->where('id', $value->to_region_id)->pluck('name')->first(),
                'to_subregion' => DB::table('route_subregions')->where('id', $value->to_subregion_id)->pluck('name')->first(),
            ]);

            $data->add($route);
        }
        return response()->json(compact('data'), 200);
    }

    public function getRegions()
    {
        $subregions = DB::table('route_subregions')
            ->join('states', 'route_subregions.state_id', '=', 'states.id')
            ->select(
                'route_subregions.id',
                'route_subregions.name as subregion_name',
                'route_subregions.state_id',
                'states.name as state_name'
            )
            ->get();

        $regions = $subregions->map(function ($subregion) {
            return [
                'id' => $subregion->id,
                'name' => $subregion->state_name . ' > ' . $subregion->subregion_name,
                'state_id' => $subregion->state_id
            ];
        });

        return response()->json(['data' => $regions], 200);
    }
}
