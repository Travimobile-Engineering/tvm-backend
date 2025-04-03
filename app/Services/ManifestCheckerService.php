<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Manifest;
use App\Models\WatchList;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;

class ManifestCheckerService
{
    use HttpResponse;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {}

    public function getManifestData($request)
    {
        $manifest = Manifest::with([
                'trip.vehicle',
                'trip.tripBookings.user',
                'trip.transitCompany.parks'
            ])
            ->whereHas('trip.vehicle', function($query) use ($request) {
                $query->where('plate_no', $request->plate_no);
            })
            ->latest()
            ->first();

        if (!$manifest) {
            return $this->error(null, "No manifest data found");
        }

        return $this->success($manifest, null);
    }

    public function addIncident($request)
    {
        if($request->hasFile('media')){
            $file = uploadFile($request, 'media', 'incidents');
        }

        $incident = Incident::create([
            'user_id' => authUser()->id,
            'category' => $request->category,
            'type' => $request->type,
            'date' => $request->date,
            'time' => $request->time,
            'location' => $request->location,
            'description' => $request->description,
            'media_url' => $file['url'] ?? null,
            'severity_level' => $request->severity_level,
            'persons_of_interest' => $request->persons_of_interest
        ]);

        return $this->success($incident, "Incident created successfully");
    }

    public function getIncidentCategories()
    {
        $categories = DB::table('incident_categories')->pluck('name')->toArray();

        if (!empty($categories)) {
            return $this->success($categories, "Incident categories retrieved successfully");
        }

        $defaultCategories = [
            'General Security Incident',
            'Safety Incidents',
            'Transportation Specific Incidents',
            'Emergency Situations'
        ];

        return $this->success($defaultCategories, "Incident categories retrieved successfully");
    }

    public function getIncidentTypes()
    {
        $types = DB::table('incident_types')->pluck('name');

        if (!empty($types)) {
            return $this->success($types, "Incident types retrieved successfully");
        }

        $types = [
            'Trespassing',
            'Vandalism',
            'Accidents',
            'Injury',
            'Medical Emergency',
            'Traffic Accident',
            'Vehicle Breakdown',
            'Kidnapping',
            'Bomb Threat',
            'Natural Disaster'
        ];

        return $this->success($types, "Incident types retrieved successfully");
    }

    public function getIncidentSeverityLevels(){
        $severities = DB::table('incident_categories')->pluck('name');

        if (!empty($severities)) {
            return $this->success($severities, "Incident severities retrieved successfully");
        }

        $severities = [
            'Informational',
            'Low Priority',
            'Medium Priority',
            'High Prority',
            'Critical Priority',
            'Catastrophic'
        ];

        return $this->success($severities, "Incident severities retrieved successfully");
    }

    public function getIncidents(){
        $incidents = Incident::all();
        return $this->success($incidents, 'Incidents retrieved successfully');
    }

    public function addUpdateWatchList($request, $action = 'create'){
        
        $photo_url = "";
        $document_links = [];

        if($request->hasFile('photo')){
            $response = $request->file('photo')->storeOnCloudinary('watch_list');
            $photo_url = $response->getSecurePath();
        }

        if($request->hasFile('documents')){
            
            $documents = $request->documents;
            if(is_array($documents)){
                foreach($documents as $doc){
                    $response = $request->file($doc)->storeOnCloudinary('watch_list');
                    $document_links[] = $response->getSecurePath();
                }
            }
        }

        $data = [
            "full_name" => $request->full_name,
            "phone" => $request->phone,
            "email" => $request->email,
            "dob" => $request->dob,
            "state_of_origin" => $request->state_of_origin,
            "nin" => $request->nin,
            "investigation_officer" => $request->investigation_officer,
            "io_contact_number" => $request->io_contact_number,
            "alert_location" => $request->alert_location,
            "photo_url" => $photo_url,
            "documents" => json_encode($document_links),
        ];

        if($action == 'update') {
            $record = WatchList::find($request->id)->update($data);
            if($record) return $this->success(null, "Record updated successfully");
            return $this->error(null, "Failed to update record");
        }

        $record = WatchList::create($data);
        if($record) return $this->success(null, "Record successfully added to watch list");
        return $this->error(null, "Failed to add record to watch list");
    }

    public function getWatchListRecord($request){
        $record = WatchList::find($request->id);
        if($record){
            return $this->success($record, null);
        }

        return $this->error(null, "Invalid watch list ID");
    }

    public function searchWatchList($request){
        $records = WatchList::where('full_name', 'LIKE', "%".$request->name."%")->get();
        if($records){
            return $this->success($records);
        }
        return $this->error(null, "No records found for '".$request->name."'");
    }
}
