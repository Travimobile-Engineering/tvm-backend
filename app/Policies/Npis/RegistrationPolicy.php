<?php

namespace App\Policies\Npis;

use App\Models\Npis\AdminUser;
use App\Models\Npis\Registration;

class RegistrationPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->hasPermission('registrations:read');
    }

    public function view(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('registrations:read');
    }

    public function review(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('registrations:review');
    }

    public function merge(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('registrations:review');
    }

    public function allocate(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('accommodation:allocate');
    }

    public function viewContact(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('contact:read');
    }

    public function viewTravel(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('travel:read');
    }

    public function viewAccommodation(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('accommodation:read');
    }

    public function viewTraining(AdminUser $admin, Registration $registration): bool
    {
        return $admin->hasPermission('training:read');
    }
}
