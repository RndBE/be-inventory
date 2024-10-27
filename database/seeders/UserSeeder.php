<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = User::create([
            'name' => 'Administrator',
            'email' => 'administrator@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        $administrator->assignRole('administrator');

        $purchasing = User::create([
            'name' => 'Purchasing',
            'email' => 'purchasing@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        $purchasing->assignRole('purchasing');
    }
}
