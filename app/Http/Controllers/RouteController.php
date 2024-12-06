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
                'from_region' => DB::table('route_regions')->where('id', $value->from_region_id)->pluck('name')->first(),
                'from_subregion' => DB::table('route_subregions')->where('id', $value->from_subregion_id)->pluck('name')->first(),
                'to_region' => DB::table('route_regions')->where('id', $value->to_region_id)->pluck('name')->first(),
                'to_subregion' => DB::table('route_subregions')->where('id', $value->to_subregion_id)->pluck('name')->first(),
            ]);

            $data->add($route);
        }
        return response()->json(compact('data'), 200);
    }

    public function getRegions(){
        $regions = collect();
        $subregions = DB::table('route_subregions')->get(['id', 'region_id', 'name',]);
        foreach($subregions as $subregion){
            $region = DB::table('route_regions')->where('id', $subregion->region_id)->get('name')->first();
            $regions->add(['id' => $subregion->id, 'name' => $region->name.' - '.$subregion->name]);
        }
        return response()->json(['data' => $regions], 200);
    }
}
