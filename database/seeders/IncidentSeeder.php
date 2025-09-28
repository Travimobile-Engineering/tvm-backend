<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $incidentTypes = [
            'Trespassing',
            'Vandalism',
            'Accidents',
            'Injury',
            'Medical Emergency',
            'Traffic Accident',
            'Vehicle Breakdown',
            'Kidnapping',
            'Bomb Threat',
            'Natural Disaster',
        ];

        $data = [];
        foreach ($incidentTypes as $type) {
            $data[] = [
                'name' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('incident_types')->insert($data);

        $severities = [
            'Informational',
            'Low Priority',
            'Medium Priority',
            'High Prority',
            'Critical Priority',
            'Catastrophic',
        ];

        $data = [];
        foreach ($severities as $severity) {
            $data[] = [
                'name' => $severity,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('incident_severity_levels')->insert($data);

        $incidentCategories = [
            'General Security Incident',
            'Safety Incidents',
            'Transportation Specific Incidents',
            'Emergency Situations',
        ];

        $data = [];
        foreach ($incidentCategories as $categories) {
            $data[] = [
                'name' => $categories,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('incident_categories')->insert($data);
    }
}
