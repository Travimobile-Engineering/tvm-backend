<?php

namespace App\Services;

use Exception;
use App\Models\JobOpening;
use App\Trait\HttpResponse;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Schema;
use App\Http\Resources\JobOpeningResource;

class JobService
{
    use HttpResponse;

    public function getJobs(){
        $jobs =  JobOpening::all();
        return $this->success($jobs);
    }
    
    public function getJob($request){
        $job = JobOpening::findOrFail($request->id);
        return $this->success(new JobOpeningResource($job));

    }
    
    public function apply($request){

        $alreadyApplied = JobApplication::where('job_opening_id', $request->job_id)
            ->where('email', $request->email)
            ->exists();

        if($alreadyApplied){
            return $this->error(null, 'You have already applied for this job', 400);
        }

        if($request->hasFile('resume')){
            $resume_url= uploadFile($request, 'resume', 'JobApplications')['url'];
        }

        if($request->hasFile('cover_letter')){
            $cover_letter_url= uploadFile($request, 'cover_letter', 'JobApplications')['url'];

        }
        
        
        try{
            $application = JobApplication::create([
                'job_opening_id' => $request->job_id,
                'full_name' => $request->full_name,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'state_of_origin' => $request->state_of_origin,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'state_applying_for' => $request->state_applying_for,
                'highest_level_of_education' => $request->highest_level_of_education,
                'field_of_study' => $request->field_of_study,
                'resume_url' => $resume_url ?? null,
                'cover_letter_url' => $cover_letter_url ?? null
            ]);

            return $this->success($application, 'Job application was successfully', 201);
        }
        catch(Exception $e){
            return $this->error(null, 'Failed to process job application. '.$e->getMessage());
        }

    }

    public function addJob($request){
        $job = JobOpening::create([
            'title' => $request->title,
            'type' => $request->type,
            'deadline' => $request->deadline,
            'summary' => $request->summary,
            'responsibilities' => $request->responsibilities,
            'requirement' => $request->requirement,
            'offer' => $request->offer,
        ]);

        if(!$job){
            return $this->error(null, 'Failed to create job. Please try again or contact support');
        }

        return $this->success($job, 'Job was created successfully', 201);
    }

    public function updateJob($request){
        
        $columns = Schema::getColumnListing('job_openings');
        $data = collect($request->all())->filter(function($value, $key) use($columns) {
            return in_array($key, $columns);
        })->all();
        
        try{
           JobOpening::where('id', $request->id)->update($data); 
           return $this->success(null, 'Job was updated successfully');
        }
        catch(Exception $e){
            return $this->error(null, "Failed to update job. ".$e->getMessage());
        }

    }
}
