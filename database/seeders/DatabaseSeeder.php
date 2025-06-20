<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            BantuanSosialSeeder::class,
            NewsSeeder::class,
        ]);

        $this->command->info('All seeders completed successfully!');
        $this->command->line('');
        $this->command->info('Default Login Credentials:');
        $this->command->line('Admin: admin@volunteer.com / password123');
        $this->command->line('User 1: budi@example.com / password123');
        $this->command->line('User 2: sari@example.com / password123');
        $this->command->line('User 3: ahmad@example.com / password123');
    }
}