<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SimpleLoginUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a parent user with numeric password
        User::updateOrCreate(
            ['email' => 'parent@test.com'],
            [
                'name' => 'Parent Test',
                'email' => 'parent@test.com',
                'password' => Hash::make('password'),
                'numeric_password' => '123456',
                'type' => 'parent',
                'email_verified_at' => now(),
            ]
        );

        // Create a child user with numeric password
        User::updateOrCreate(
            ['email' => 'kid@test.com'],
            [
                'name' => 'Kid Test',
                'email' => 'kid@test.com',
                'password' => Hash::make('password'),
                'numeric_password' => '654321',
                'type' => 'child',
                'email_verified_at' => now(),
            ]
        );

        // Create another parent user
        User::updateOrCreate(
            ['email' => 'parent2@test.com'],
            [
                'name' => 'Parent 2',
                'email' => 'parent2@test.com',
                'password' => Hash::make('password'),
                'numeric_password' => '111111',
                'type' => 'parent',
                'email_verified_at' => now(),
            ]
        );

        // Create another child user
        User::updateOrCreate(
            ['email' => 'kid2@test.com'],
            [
                'name' => 'Kid 2',
                'email' => 'kid2@test.com',
                'password' => Hash::make('password'),
                'numeric_password' => '222222',
                'type' => 'child',
                'email_verified_at' => now(),
            ]
        );
    }
}
