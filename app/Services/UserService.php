<?php

namespace App\Services;

use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Hash;

class UserService
{
    use HttpResponse;

    public function changePassword($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

             return $this->success(null, "Password changed successfully");

        }else {
            return $this->error(null, 'Old Password did not match', 422);
        }
    }
}


