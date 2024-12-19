<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmationEmail;
use App\Models\TransitCompany;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class TransitCompanyController extends Controller
{

    protected $user;

    public function __construct(){
        $this->user->JWTAuth::user();
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
    public function store(Request $request)
    {
        try{
            $validation = $request->validate([
                'email' => 'required|unique:transit_companies|email',
                'phone' => 'required|unique:transit_companies|max_digits:14',
                'url' => 'nullable|url',
            ]);

        }catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

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
            return response()->json(['message' => 'Account created successfully', 'data' => $company], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TransitCompany $transitCompany)
    {
        if($transitCompany){

            return response()->json(['data' => $transitCompany], 200);
        }

        else return response()->json(['error' => 'not found'], 400);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransitCompany $transitCompany)
    {
        try{
            $validation = $request->validate([
                'phone' => 'required|max_digits:14',
                'url' => 'nullable|url',
            ]);

        }catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

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
            return response()->json(['message' => 'Account updated successfully', 'data' => $transitCompany], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransitCompany $transitCompany)
    {
        //
    }
}
