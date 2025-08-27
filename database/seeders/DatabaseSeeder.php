<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (!User::where('email', 'parent@example.com')->exists()) {
            User::create([
                'name' => 'Sample Parent',
                'email' => 'parent@example.com',
                'password' => Hash::make('password'),
                'type' => 'parent',
            ]);
        }
        if (!User::where('email', 'kid@example.com')->exists()) {
            User::create([
                'name' => 'Sample Kid',
                'email' => 'kid@example.com',
                'password' => Hash::make('password'),
                'type' => 'child',
            ]);
        }
    }
}
