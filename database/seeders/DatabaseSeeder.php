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
        // 1. Super Admin
        User::create([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@tricycle.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'user_photo' => null,
            'contact_number' => '09451830519',
            'street_address' => 'Estrada Drive',
            'barangay' => 'San Roque',
            'city' => 'Zamboanga City',
            'status' => 'active',
        ]);

        // 2. Franchise Owner
        User::create([
            'first_name' => 'Tricy',
            'last_name' => 'Owner',
            'email' => 'owner@tricycle.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'user_photo' => null,
            'contact_number' => '09451830519',
            'street_address' => 'Mormons Drive',
            'barangay' => 'Tetuan',
            'city' => 'Zamboanga City',
            'status' => 'active',
        ]);
    }
}