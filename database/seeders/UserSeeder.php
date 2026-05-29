<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'building' => 15,
                'room_number' => 101,
                'balance' => 0.00,
            ],
            [
                'name' => 'Supplier User',
                'email' => 'supplier@example.com',
                'password' => bcrypt('password123'),
                'role' => 'supplier',
                'building' => 14,
                'room_number' => 202,
                'balance' => 0.00,
            ]
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
