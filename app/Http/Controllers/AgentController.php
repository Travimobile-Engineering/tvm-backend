<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgentController extends Controller
{
    public function show(Request $request){

        $agent = User::where('agent_id', $request->agent_id);
        if(!$agent->exists()) return response()->json(['error' => 'Invalid agent ID']);

        return response()->json(['data' => $agent->get(['first_name', 'last_name', 'email', 'agent_id', 'profile_photo_url'])->first()]);
    }
}
