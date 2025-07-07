<?php

namespace App\Services;

use App\Enum\MailingEnum;
use App\Models\TransitCompany;
use Illuminate\Support\Carbon;
use App\Mail\ConfirmationEmail;
use App\Models\State;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;

class TransitCompanyService
{
    use HttpResponse;

    protected $user;

    public function __construct()
    {
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
        $stateID = State::where('name', $request->state)->first();

        TransitCompany::create([
            'name' => $request->name,
            'user_id' => $this->user->id,
            'short_name' => $request->short_name,
            'reg_no' => $request->reg_no,
            'url' => $request->url,
            'email' => $request->email,
            'state' => $request->state,
            'union_states_chapter' => $stateID->id ?? 25,
            'lga' => $request->lga,
            'phone' => $request->phone,
            'address' => $request->address,
            'about_details' => $request->about_details,
            'ver_code' => $v_code,
            'ver_code_expires_at' => Carbon::now()->addMinutes(10),
            'type' => 'transit_company',
        ]);

        $type = MailingEnum::EMAIL_VERIFICATION;
        $subject = "Email Verification";
        $mail_class = ConfirmationEmail::class;
        $data = [
            'name' => $request->name,
            'verification_code' => $v_code
        ];

        mailSend($type, $this->user, $subject, $mail_class, $data);

        return $this->success(null, 'Account created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransitCompany $transitCompany)
    {
        if (! $transitCompany) {
            return $this->error(null, "Not found", 404);
        }

        return $this->success($transitCompany, "Transit company retrieved successfully");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $transitCompany)
    {

        if($transitCompany->user_id != $this->user->id) {
            return $this->error(null, "You are not authorized to update this account", 401);
        }

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

        return $this->success($company, "Transit company updated successfully");
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
