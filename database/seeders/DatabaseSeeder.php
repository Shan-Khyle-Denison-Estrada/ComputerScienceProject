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
        // updateOrCreate ensures we don't get a "Duplicate Entry" SQL error on future deployments
        User::updateOrCreate(
            ['email' => 'admin@tricycle.com'], // The unique identifier to check
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make('password'), // You can change this later in the UI
                'role' => UserRole::ADMIN,
                'user_photo' => null,
                'contact_number' => '09451830519',
                'street_address' => 'Estrada Drive',
                'barangay' => 'San Roque',
                'city' => 'Zamboanga City',
                'status' => 'active',
            ]
        );
    }
}