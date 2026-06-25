<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::latest()->paginate(10);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function show(Role $role)
    {
        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'sensitive_permissions' => $role->sensitive_permissions ?? [],
        ]);
    }

    public function store(StoreRoleRequest $request)
    {

        Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'sensitive_permissions' => $request->sensitive_permissions,
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'create',
            description: Auth::user()->first_name.' '.Auth::user()->last_name." created a new role: {$request->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        return view('admin.roles.edit', compact('role'));
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $role = Role::findOrFail($id);

        $role->update([
            'name' => $request->name,
            'guard_name' => 'web',
            'sensitive_permissions' => $request->sensitive_permissions,
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'update',
            description: Auth::user()->first_name.' '.Auth::user()->last_name." updated the role: {$role->name} [{$role->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'delete',
            description: Auth::user()->first_name.' '.Auth::user()->last_name." deleted the role: {$role->name} [{$role->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
