<?php

namespace App\Http\Controllers;

use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Models\TransportRouteManagement;

class TransportRouteManagementController extends Controller
{
    use HttpResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $routeManagements = TransportRouteManagement::select('id', 'park_name', 'address', 'state', 'zone', 'originating_route', 'terminating_route', 'estimated_trip', 'key_man', 'estimated_distance', 'estimated_time', 'cost_of_transportation', 'road_safety_rating', 'field_officer', 'occasioned_by')
            ->paginate(25);

        return $this->withPagination($routeManagements, "Transport Route Management");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $routeManagement = TransportRouteManagement::query()->create([
            'park_name' => $request->park_name,
            'address' => $request->address,
            'state' => $request->state,
            'zone' => $request->zone,
            'originating_route' => $request->originating_route,
            'terminating_route' => $request->terminating_route,
            'estimated_trip' => $request->estimated_trip,
            'key_man' => $request->key_man,
            'estimated_distance' => $request->estimated_distance,
            'estimated_time' => $request->estimated_time,
            'cost_of_transportation' => $request->cost_of_transportation,
            'road_safety_rating' => $request->road_safety_rating,
            'field_officer' => $request->field_officer,
            'occasioned_by' => $request->occasioned_by,
        ]);

        return $this->success($routeManagement, "Transport Route Management Created Successfully", 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransportRouteManagement $routeManagement)
    {
        return $this->success($routeManagement, "Transport Route Management Retrieved Successfully");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransportRouteManagement $routeManagement)
    {
        $routeManagement->update([
            'park_name' => $request->park_name,
            'address' => $request->address,
            'state' => $request->state,
            'zone' => $request->zone,
            'originating_route' => $request->originating_route,
            'terminating_route' => $request->terminating_route,
            'estimated_trip' => $request->estimated_trip,
            'key_man' => $request->key_man,
            'estimated_distance' => $request->estimated_distance,
            'estimated_time' => $request->estimated_time,
            'cost_of_transportation' => $request->cost_of_transportation,
            'road_safety_rating' => $request->road_safety_rating,
            'field_officer' => $request->field_officer,
            'occasioned_by' => $request->occasioned_by,
        ]);

        return $this->success($routeManagement, "Transport Route Management Updated Successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransportRouteManagement $routeManagement)
    {
        $routeManagement->delete();
        return $this->success(null, "Transport Route Management Deleted Successfully");
    }
}
