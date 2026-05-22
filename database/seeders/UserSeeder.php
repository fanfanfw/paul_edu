<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\MentorProfile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed demo identity users.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
            ]
        );
        $admin->syncRoles(['admin']);

        $mentor = User::updateOrCreate(
            ['email' => 'mentor@example.com'],
            [
                'name' => 'Mentor Demo',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
            ]
        );
        $mentor->syncRoles(['mentor']);

        MentorProfile::updateOrCreate(
            ['user_id' => $mentor->id],
            [
                'display_name' => 'Mentor Demo',
                'slug' => 'mentor-demo',
                'status' => 'active',
            ]
        );

        Wallet::updateOrCreate(
            ['owner_type' => 'user', 'owner_id' => $mentor->id],
            ['balance' => 0, 'currency' => 'IDR', 'status' => 'active']
        );

        $user = User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User Demo',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
            ]
        );
        $user->syncRoles(['user']);

        Wallet::updateOrCreate(
            ['owner_type' => 'user', 'owner_id' => $user->id],
            ['balance' => 500000, 'currency' => 'IDR', 'status' => 'active']
        );
    }
}
