<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed the Barangays first
        $this->call([
            BarangaySeeder::class,
        ]);

        // 2. Safely create or update the Super Admin
        User::updateOrCreate(
            ['email' => 'admin@tricycle.com'], 
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make('password'), 
                'role' => UserRole::ADMIN,
                'user_photo' => null,
                'contact_number' => '12345678901',
                'street_address' => 'Admin Address',
                'province' => 'Zamboanga del Sur',
                'barangay' => 'San Roque',
                'city' => 'City of Zamboanga',
                'status' => 'active',
            ]
        );

        // 3. Run the Presentation Seeders in specific dependency order
        $this->call([
            ZoneSeeder::class,
            UnitMakeSeeder::class,
            ParticularSeeder::class,
            GraduationSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}