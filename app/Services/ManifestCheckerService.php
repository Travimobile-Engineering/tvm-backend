<?php

namespace App\Services;

use App\Models\Manifest;

class ManifestCheckerService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        
    }

    public function check($request){
        $request->validate(['plate_no' => 'required']);
        return Manifest::with('trip.vehicle', 'trip.tripBookings.user', 'trip.transitCompany.parks')->whereHas('trip', function($query) use($request){
            $query->whereHas('vehicle', function($query) use($request){
                $query->where('plate_no', $request->plate_no);
            });
        })->latest()->limit(1)->first();
    }
}
