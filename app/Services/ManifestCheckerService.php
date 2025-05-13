<?php

namespace App\Services;

use App\Models\User;
use App\Models\Incident;
use App\Models\Manifest;
use App\Models\WatchList;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Http\Resources\SecurityAgentProfileResource;

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
            'user_id' => Auth::user()->id,
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
        return $this->success($categories, "Incident categories retrieved successfully");
    }

    public function getIncidentTypes()
    {
        $types = DB::table('incident_types')->pluck('name')->toArray();
        return $this->success($types, "Incident types retrieved successfully");
    }

    public function getIncidentSeverityLevels(){
        $severities = DB::table('incident_severity_levels')->pluck('name')->toArray();
        return $this->success($severities, "Incident severities retrieved successfully");
    }

    public function getIncidents(){
        $incidents = Incident::all();
        return $this->success($incidents, 'Incidents retrieved successfully');
    }

    public function getIncident($request){
        $incident = Incident::where('id', $request->id)->get();
        return $this->success($incident, 'incident retrieved successfully');
    }

    public function addUpdateWatchList($request, bool $update = false){
        
        try{
            $document_links = [];
    
            if($request->hasFile('photo')){
                $photo_url = uploadFile($request, 'photo', 'watch_list')['url'];
            }
    
            if($request->hasFile('documents')){
    
                $documents = $request->documents;
                if(is_array($documents)){
                    foreach($documents as $doc){
                        $response = $request->file($doc)->storeOnCloudinary('watch_list');
                        $document_links[] = $response->getSecurePath();
                    }
                }
    
                else {
                    $document_links[] = uploadFile($request, 'documents', 'watch_list')['url'];
                }
            }
    
            $data = [
                "full_name" => $request->full_name,
                "category" => $request->category,
                "phone" => $request->phone,
                "email" => $request->email,
                "dob" => $request->dob,
                "state_of_origin" => $request->state_of_origin,
                "nin" => $request->nin,
                "investigation_officer" => $request->investigation_officer,
                "io_contact_number" => $request->io_contact_number,
                "alert_location" => $request->alert_location,
                "recent_location" => $request->recent_location,
                "observation" => $request->observation,
                "photo_url" => $photo_url ?? '',
                "documents" => json_encode($document_links),
            ];

            $record = WatchList::create($data);
            return $this->success($record, "Record successfully added to watch list");
        }
        catch(\Exception $e){
            return $this->error(null, $e->getMessage());
        }
    }

    public function updateWatchList($request){
        
        try{
            $columns = Schema::getColumnListing('watch_lists');
            $data = array_filter($request->all(), function($key) use($columns){
                return in_array($key, $columns);
            }, ARRAY_FILTER_USE_KEY);

            WatchList::find($request->id)->update($data);
            return $this->success(null, "Record updated successfully");
        }
        catch(\Exception $e){
            return $this->error(null, $e->getMessage());
        }
    }

    public function getWatchListRecords(){
        $record = WatchList::all();
        return $this->success($record, null);
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

    public function getProfile(){
        return new SecurityAgentProfileResource(Auth::user());
    }

    public function editProfile($request){
        $columns = Schema::getColumnListing('users');
        $data = array_filter($request->all(), function($key) use($columns){
            return in_array($key, $columns);
        }, ARRAY_FILTER_USE_KEY);

        if(isset($request->full_name)){
            $names = explode(' ', $request->full_name, 2);
            $data['first_name'] = trim($names[0]);
            $data['last_name'] = trim($names[1] ??= null);
        }
        try{
            User::find(Auth::user()->id)->update($data);
            return $this->success(null, 'User account updated successfully');
        }
        catch(\Exception $e){
            return $this->error(null, $e->getMessage());
        }
    }
}
