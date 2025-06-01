<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

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

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        // Log the creation of the role
        auth()->user()->logModelAction(
            event: 'create',
            description: auth()->user()->first_name . " " . auth()->user()->last_name . " created a new role: {$request->name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) \Str::uuid(), // Generate a new UUID for the batch
            ],
        );

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $role->update([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        // Log the update of the role
        auth()->user()->logModelAction(
            event: 'update',
            description: auth()->user()->first_name . " " . auth()->user()->last_name . " updated the role: {$role->name} [{$role->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) \Str::uuid(), // Generate a new UUID for the batch
            ],
        );

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // log the deletion of the role
        auth()->user()->logModelAction(
            event: 'delete',
            description: auth()->user()->first_name . " " . auth()->user()->last_name . " deleted the role: {$role->name} [{$role->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) \Str::uuid(), // Generate a new UUID for the batch
            ],
        );
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
