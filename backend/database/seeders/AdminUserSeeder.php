<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Hanya ada 1 akun admin, dibuat manual lewat seeder (§1.4 kontrak) —
     * tidak ada endpoint register admin.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@hakostar.test',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
            ]
        );

        UserProfile::firstOrCreate(
            ['user_id' => $admin->id],
            ['full_name' => 'HakoStar Admin']
        );
    }
}
