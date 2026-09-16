<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public const RACKS = ['A', 'B', 'C', 'D', 'E'];

    public function run(): void
    {
        // 1. Seed admin user
        User::updateOrCreate(
            ['email' => 'admin@thi2-warehouse.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 2. Seed HR user
        User::updateOrCreate(
            ['email' => 'hr@thi2-warehouse.com'],
            [
                'name' => 'HRD',
                'password' => Hash::make('password'),
                'role' => 'hr',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 3. Seed Gudang user
        User::updateOrCreate(
            ['email' => 'gudang@thi2-warehouse.com'],
            [
                'name' => 'Gudang Utama',
                'password' => Hash::make('password'),
                'role' => 'gudang',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 4. Seed Director user
        User::updateOrCreate(
            ['email' => 'director@thi2-warehouse.com'],
            [
                'name' => 'Mr. Yang',
                'password' => Hash::make('password'),
                'role' => 'director',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 5. Seed default settings
        Setting::set('dev_mode', '0');
    }
}
