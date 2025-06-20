<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin Volunteer',
            'email' => 'admin@volunteer.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '081234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create test regular users
        $users = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567891',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sari Dewi',
                'email' => 'sari@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567892',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Ahmad Rahman',
                'email' => 'ahmad@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567893',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Admin and test users created successfully!');
    }
}