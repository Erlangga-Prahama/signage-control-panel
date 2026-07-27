<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates one admin user for the dashboard. Override via .env so you
     * don't have to hardcode real credentials in source control:
     *
     *   ADMIN_NAME="Erlangga"
     *   ADMIN_EMAIL=admin[at]yourdomain.com
     *   ADMIN_PASSWORD=supersecret123
     *
     * Run with: php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        $defaultEmail = 'admin' . '@' . 'yourdomain.com';
        $email = env('ADMIN_EMAIL', $defaultEmail);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password123')),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin user siap: {$user->email}");
    }
}