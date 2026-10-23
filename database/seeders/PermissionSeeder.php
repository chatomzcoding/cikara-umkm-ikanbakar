<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionNames = [];

        foreach (config('permissions.groups', []) as $group) {
            foreach ($group['permissions'] as $name => $displayName) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'description' => $group['label'],
                    ],
                );

                $permissionNames[] = $name;
            }
        }

        $superadmin = Role::where('name', 'superadmin')->first();

        if ($superadmin) {
            $superadmin->syncPermissions(
                Permission::whereIn('name', $permissionNames)->get(),
            );
        }

        foreach (config('permissions.role_defaults', []) as $name => $default) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                ['display_name' => $default['display_name'], 'description' => $default['description']],
            );

            $role->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', $default['permissions'])->pluck('id'),
            );
        }
    }
}
