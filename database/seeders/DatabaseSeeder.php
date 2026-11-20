<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Operator Admin User
        User::firstOrCreate(
            ['email' => 'admin@promptedit.com'],
            [
                'name' => 'Operator Admin',
                'password' => Hash::make('password'),
                'balance' => '50.000000000',
                'is_admin' => true,
            ]
        );

        // Standard Demo User
        User::firstOrCreate(
            ['email' => 'user@promptedit.com'],
            [
                'name' => 'Alex Developer',
                'password' => Hash::make('password'),
                'balance' => '15.000000000',
                'is_admin' => false,
            ]
        );
    }
}
