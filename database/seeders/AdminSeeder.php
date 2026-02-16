<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'driver']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
            ]
        );

        $admin->assignRole($adminRole);
    }
}
