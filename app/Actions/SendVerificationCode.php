<?php

namespace App\Actions;

use App\Mail\VerifyPinMail;

class SendVerificationCode
{
    public function execute($user, $code)
    {
        if (!empty($user->email) && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            sendMail($user->email, new VerifyPinMail($user->first_name, $code));
        }

        if ($user->phone_number !== null && $user->phone_number !== '') {
            sendSmS(
                formatPhoneNumber($user->phone_number),
                "Your Travi Verification Code is {$code}. Valid for 10 mins. Do not share with anyone. Powered By Travi"
            );
        }
    }
}
