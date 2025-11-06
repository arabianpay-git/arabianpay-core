<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles')->latest()->paginate(10);
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        // Log the creation of the permission
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'create',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " created a new permission: {$request->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        // Log the update of the permission
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated the permission: {$request->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        // log the deletion of the permission
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'delete',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " deleted the permission: {$permission->name} [{$permission->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );
        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');
    }
}
