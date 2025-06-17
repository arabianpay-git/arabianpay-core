<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->paginate(10);
        return view('admin.role_permissions.index', compact('roles'));
    }

    public function create()
    {
        $roles = Role::all();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('admin.role_permissions.form', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($request->role_id);

        // Sync permissions (assign the selected permissions)
        $role->syncPermissions($request->permissions ?? []);

        // Log the assignment of permissions
        Auth::user()->logModelAction(
            event: 'assign_permissions',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " assigned permissions to role: {$role->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('role-permissions.index')
            ->with('success', 'Permissions assigned successfully.');
    }


    public function edit($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        // Group permissions by resource/module (text before the dot)
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('admin.role_permissions.form', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);

        // Sync permissions
        $role->syncPermissions($request->permissions ?? []);

        // Log the update of permissions
        Auth::user()->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated permissions for role: {$role->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('role-permissions.index')
            ->with('success', 'Permissions updated successfully.');
    }

    public function destroy($roleId)
    {
        $role = Role::findOrFail($roleId);
        // Log the removal of permissions
        Auth::user()->logModelAction(
            event: 'remove_permissions',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " removed permissions from role: {$role->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );
        $role->syncPermissions([]);

        return redirect()->back()->with('success', 'Permissions removed from role.');
    }
}
