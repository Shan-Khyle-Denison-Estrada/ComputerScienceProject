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
            'status' => 'active',
            'user_photo' => null,
        ]);

        // 2. Franchise Owner
        User::create([
            'first_name' => 'Juan',
            'middle_name' => 'D.',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@franchise.com',
            'password' => Hash::make('password'),
            'role' => UserRole::FRANCHISE_OWNER,
            'status' => 'active',
            'user_photo' => null,
        ]);
    }
}