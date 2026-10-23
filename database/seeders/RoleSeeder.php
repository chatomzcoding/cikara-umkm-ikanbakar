<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat role Super Admin
        $superAdminRole = Role::updateOrCreate(
            [
                'name' => 'superadmin',
            ],
            [
                'display_name' => 'Super Admin',
                'description' => 'Memiliki akses penuh ke seluruh sistem.',
            ]
        );

        // Ambil user Super Admin
        $superAdmin = User::where('email', 'superadmin@gmail.com')->first();

        // Assign role jika user ditemukan
        if ($superAdmin && ! $superAdmin->hasRole('superadmin')) {
            $superAdmin->addRole($superAdminRole);
        }
    }
}
