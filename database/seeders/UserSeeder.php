<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts for every role. All of them use the password "password"
     * (hashed by the User model's "hashed" cast).
     */
    public function run(): void
    {
        $users = [
            ['Admin', 'admin@lazyroom.com', 'admin', 0, '1', '100'],
            ['Campus Bites', 'supplier@lazyroom.com', 'supplier', 0, '2', '010'],
            ['Fresh Corner', 'fresh@lazyroom.com', 'supplier', 0, '3', '005'],
            ['Omar Haddad', 'customer@lazyroom.com', 'customer', 100, '14', '202'],
            ['Sara Khalil', 'sara@lazyroom.com', 'customer', 50, '12', '118'],
        ];

        foreach ($users as [$name, $email, $role, $balance, $building, $room]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'balance' => $balance,
                'building' => $building,
                'room_number' => $room,
            ]);
        }
    }
}
