<?php

namespace App\Enum;

enum UserType: string
{
    const SUPERADMIN = "super-admin";
    const DRIVER = "driver";
    const AGENT = "agent";
    const PASSENGER = "passenger";
}

