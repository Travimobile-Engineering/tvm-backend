<?php

namespace App\Contracts;

interface SMS
{
    public function sendSms(string $to, string $message): array;
}
