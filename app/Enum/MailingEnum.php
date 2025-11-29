<?php

namespace App\Enum;

enum MailingEnum: string
{
    // Type
    const SIGN_UP_OTP = 'sign_up_otp';

    const LOGIN_OTP = 'login_otp';

    const ORDER_EMAIL = 'order_email';

    const RESEND_CODE = 'resend_code';

    const EMAIL_VERIFICATION = 'email_verification';

    const VERIFY_OTP = 'verify_otp';

    // Status
    const PENDING = 'pending';

    const SENT = 'sent';

    const FAILED = 'failed';
}
