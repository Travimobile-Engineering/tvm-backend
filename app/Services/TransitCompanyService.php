<?php

namespace App\Services;

use App\Models\TransitCompany;
use Illuminate\Support\Carbon;
use App\Mail\ConfirmationEmail;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;

class TransitCompanyService
{
    use HttpResponse;

    protected $user;
    public function __construct(){
        $this->user = JWTAuth::user();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {

        $v_code = str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
        
        $company = TransitCompany::create([
            'name' => $request->name,
            'user_id' => $this->user->id,
            'short_name' => $request->short_name,
            'reg_no' => $request->reg_no,
            'url' => $request->url,
            'email' => $request->email,
            'state' => $request->state,
            'lga' => $request->lga,
            'phone' => $request->phone,
            'address' => $request->address,
            'about_details' => $request->about_details,
            'ver_code' => $v_code,
            'ver_code_expires_at' => Carbon::now()->addMinutes(10)
        ]);

        if($company){
            Mail::to($request->email)->send(new ConfirmationEmail($request->name, $v_code));
            return ['message' => 'Account created successfully', 'data' => $company];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TransitCompany $transitCompany)
    {
        if($transitCompany){
            return ['data' => $transitCompany];
        }
        else return ['message' => 'not found', 'code' => '400'];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $transitCompany)
    {

        if($transitCompany->user_id != $this->user->id) return response()->json(['error' => 'Invalid user detected'], 400);

        $company = $transitCompany->update([
            'name' => $request->name,
            'short_name' => $request->short_name,
            'reg_no' => $request->reg_no,
            'url' => $request->url,
            'state' => $request->state,
            'lga' => $request->lga,
            'phone' => $request->phone,
            'address' => $request->address,
            'about_details' => $request->about_details
        ]);

        if($company){
            return ['message' => 'Account updated successfully', 'data' => $transitCompany];
        }
    }

    public function getUnions()
    {
        $data = DB::table('transit_company_unions')
            ->select('id', 'name')
            ->get();
            
        return $this->success($data, "Transit company unions");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransitCompany $transitCompany)
    {
        //
    }


}
