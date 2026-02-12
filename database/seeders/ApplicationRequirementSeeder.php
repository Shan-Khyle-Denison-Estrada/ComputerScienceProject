<?php

namespace Database\Seeders;

use App\Models\ApplicationRequirement;
use Illuminate\Database\Seeder;

class ApplicationRequirementSeeder extends Seeder
{
    public function run()
    {
        $requirements = [
            'new_operator' => ['Valid ID', 'TIN Registration', 'Cedula', 'Barangay Clearance'],
            'new_franchise' => ['Unit OR/CR', 'Insurance Policy', 'Voters ID'],
            'renewal' => ['Previous Franchise Permit', 'Emission Test Result', 'Insurance Policy'],
            'transfer_ownership' => ['Deed of Sale', 'Valid ID of New Owner', 'Valid ID of Previous Owner'],
            'change_unit' => ['Surrender of Old Plate', 'New Unit OR/CR', 'Picture of New Unit'],
        ];

        foreach ($requirements as $type => $reqs) {
            foreach ($reqs as $req) {
                ApplicationRequirement::create([
                    'application_type' => $type,
                    'name' => $req
                ]);
            }
        }
    }
}