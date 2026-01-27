<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminExists = DB::table('users')->where('email', 'admin@admin.com')->exists();
        if (!$adminExists) {
            DB::table('users')->insert([
                'uid' => $this->generateUuid(),
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                'email_verified_at' => now(),
                'password' => Hash::make('Admin@1234'),
                'role' => 'admin',
                'status' => 'active',
                'kyc_status' => 'approved',
                'last_login_at' => now(),
                'avatar' => null,
                'is_admin' => 1,
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@admin.com');
            $this->command->info('Password: Admin@1234');
        } else {
            $this->command->info('Admin user already exists, skipping...');
        }

        $userExists = DB::table('users')->where('email', 'user@user.com')->exists();
        if (!$userExists) {
            DB::table('users')->insert([
                'uid' => $this->generateUuid(),
                'name' => 'Test User',
                'email' => 'user@user.com',
                'email_verified_at' => now(),
                'password' => Hash::make('User@1234'),
                'role' => 'user',
                'status' => 'active',
                'kyc_status' => 'pending',
                'last_login_at' => null,
                'avatar' => null,
                'is_admin' => 0,
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Regular user created successfully!');
            $this->command->info('Email: user@user.com');
            $this->command->info('Password: User@1234');
        } else {
            $this->command->info('Regular user already exists, skipping...');
        }

        $this->command->warn('Please change the default passwords after first login!');
    }

    /**
     * Generate a UUID v4
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
