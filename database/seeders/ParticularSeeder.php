<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Particular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParticularSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('particulars')->truncate();
        Schema::enableForeignKeyConstraints();

        $particulars = [
            // Your system records moved from the migration
            [
                'name' => 'Surcharge',
                'code' => 'surcharge',
                'description' => 'Calculated dynamically based on penalty rules',
                'amount' => 0, 
                'group' => 'penalty',
                'is_system' => true,
            ],
            [
                'name' => 'Interest',
                'code' => 'interest',
                'description' => 'Calculated dynamically based on penalty rules',
                'amount' => 0, 
                'group' => 'penalty',
                'is_system' => true,
            ],
            // The dummy records for the presentation
            [
                'name' => 'Fee Matrix',
                'code' => 'FM-001',
                'description' => 'Base fee matrix for standard processing',
                'amount' => 500.00,
                'group' => null, 
                'is_system' => true,
            ],
            [
                'name' => 'Standard Fee',
                'code' => 'STD-001',
                'description' => 'Standard operational processing fee',
                'amount' => 150.00,
                'group' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Service Fee',
                'code' => 'SRV-001',
                'description' => 'General maintenance and service fee',
                'amount' => 250.00,
                'group' => null,
                'is_system' => true,
            ]
        ];

        foreach ($particulars as $particular) {
            Particular::create($particular);
        }
    }
}