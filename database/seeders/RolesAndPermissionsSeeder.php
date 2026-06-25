<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Define roles and their permissions
        $data = [
            'admin' => ['create user', 'edit user', 'delete user', 'view reports'],
            'editor' => ['edit post', 'publish post', 'unpublish post'],
            'viewer' => ['view post'],
        ];

        foreach ($data as $roleName => $perms) {
            // Create or retrieve role
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Loop through each permission
            foreach ($perms as $permName) {
                $permission = Permission::firstOrCreate(['name' => $permName]);
                $role->givePermissionTo($permission);
            }
        }
    }
}
