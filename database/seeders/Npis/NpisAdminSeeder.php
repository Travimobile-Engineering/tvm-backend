<?php

namespace Database\Seeders\Npis;

use App\Enum\Npis\OrganizerRole;
use App\Models\Npis\AdminUser;
use Illuminate\Database\Seeder;

class NpisAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['super_admin', 'ACP Grace Adeleke', 'grace.adeleke@npis.ng'],
            ['registration', 'Insp. Tolu Bakare', 'tolu.bakare@npis.ng'],
            ['logistics', 'DSP Yakubu Garba', 'yakubu.garba@npis.ng'],
            ['training', 'SP Ngozi Eze', 'ngozi.eze@npis.ng'],
            ['checkin', 'Sgt. Ahmed Lawal', 'ahmed.lawal@npis.ng'],
        ];

        $password = config('npis.organizer.seed_password')
            ?: (fn (): ?string => app()->isProduction() ? null : 'Npis@2026#Dev')();

        foreach ($admins as [$role, $name, $email]) {
            $attributes = [
                'name' => $name,
                'role' => OrganizerRole::from($role),
                'is_active' => true,
            ];

            // Optional password for email/password sign-in. Production keeps
            // existing passwords unless NPIS_SEED_PASSWORD is explicitly set.
            if (is_string($password) && $password !== '') {
                $attributes['password'] = $password;
            }

            AdminUser::updateOrCreate(['email' => $email], $attributes);
        }
    }
}
