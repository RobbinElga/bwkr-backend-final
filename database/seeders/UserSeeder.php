<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@bwkr.id'],
            [
                'name'      => 'Super Admin BWKR',
                'phone'     => '081200000001',
                'role'      => UserRole::SuperAdmin->value,
                'password'  => 'Superadmin#2026',   // otomatis di-hash oleh cast
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@bwkr.id'],
            [
                'name'      => 'Admin BWKR',
                'phone'     => '081200000002',
                'role'      => UserRole::Admin->value,
                'password'  => 'Admin#2026',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@bwkr2.id'],
            [
                'name'      => 'Admin BWKR',
                'phone'     => '081200000003',
                'role'      => UserRole::Admin->value,
                'password'  => 'Admin#2026',
                'is_active' => true,
            ]
        );
    }
}
