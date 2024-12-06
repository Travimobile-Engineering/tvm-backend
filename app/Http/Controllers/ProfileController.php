<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    //method to get the authenticated user
    public function index(){
        
        $user = JWTAuth::user();
        return response()->json($user);
    }

    //Method to update user data
    public function edit(Request $request){

        $id = $request->route('id');

        if(JWTAuth::user()->id == $id){

            $updates = collect($request->all());
            
            if($updates->has('full_name')){
                $names = explode(' ', $updates['full_name'], 2);
                $updates['first_name'] = $names[0];
                $updates['last_name'] = $names[1] ?? '';
            }
            
            $updates = $updates->filter(function($value, $key){
                return !empty($value) && $key != 'email' && Schema::hasColumn('users', $key);
            });

            if($updates->has('password')){

                try{
                    $request->validate([
                        'password' => 'min:8',
                    ]);
                }catch(ValidationException $e){
                    return response()->json(['error' => $e->errors()]);
                }

                $updates['password'] = Hash::make($updates['password']);
            }

            
            $user = User::where('id', $id)
                ->update($updates->toArray());

            return response()->json([
                'message' => 'User data updated successfully',
                'user' => User::find($id),
            ]);
        }

        else return response()->json(['error' => 'Invalid user id']);
    }
}
