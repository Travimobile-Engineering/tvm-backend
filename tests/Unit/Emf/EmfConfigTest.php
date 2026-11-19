<?php

namespace Tests\Unit\Emf;

use Tests\TestCase;

class EmfConfigTest extends TestCase
{
    public function test_every_manifest_checker_role_has_a_permission_set(): void
    {
        $roles = config('emf.roles', []);

        $this->assertNotEmpty($roles);

        foreach ($roles as $role) {
            $this->assertNotEmpty($role['label']);
            $this->assertNotEmpty($role['permissions']);
        }
    }

    public function test_supported_modes_verdicts_and_risks_are_stable(): void
    {
        $this->assertSame(['road', 'sea', 'air', 'rail', 'hospitality'], array_keys((array) config('emf.modes')));
        $this->assertSame(['clear', 'flagged', 'mismatch', 'unverified'], config('emf.verdicts'));
        $this->assertSame(['critical', 'high', 'medium', 'low'], config('emf.risk_levels'));
    }
}
