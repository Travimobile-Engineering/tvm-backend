<?php

namespace App\Http\Controllers;

use App\Models\TransportRouteManagement;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class TransportRouteManagementController extends Controller
{
    use HttpResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $routeManagements = TransportRouteManagement::select('id', 'park_name', 'address', 'state', 'zone', 'originating_route', 'terminating_route', 'estimated_trip', 'key_man', 'estimated_distance', 'estimated_time', 'cost_of_transportation', 'road_safety_rating', 'field_officer', 'occasioned_by', 'lng', 'lat')
            ->paginate(25);

        return $this->withPagination($routeManagements, 'Transport Route Management');
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
            'lng' => $request->lng,
            'lat' => $request->lat,
        ]);

        return $this->success($routeManagement, 'Transport Route Management Created Successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransportRouteManagement $routeManagement)
    {
        return $this->success($routeManagement, 'Transport Route Management Retrieved Successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $routeManagement = TransportRouteManagement::find($id);

        if (! $routeManagement) {
            return $this->error(null, 'Transport Route Management Not Found', 404);
        }

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
            'lng' => $request->lng,
            'lat' => $request->lat,
        ]);

        return $this->success($routeManagement, 'Transport Route Management Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $routeManagement = TransportRouteManagement::find($id);

        if (! $routeManagement) {
            return $this->error(null, 'Transport Route Management Not Found', 404);
        }

        $routeManagement->delete();

        return $this->success(null, 'Transport Route Management Deleted Successfully');
    }
}
