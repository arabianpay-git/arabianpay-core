<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['roles', 'users'])->latest()->paginate(10);
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $roles = Role::orderBy('name', 'asc')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });
        return view('admin.departments.create', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|array|min:1',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $name = $request->name;

        $exists = Department::whereEncrypted('name', $name)->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'The department name must be unique.'])->withInput();
        }

        try {
            $department = Department::create(['name' => $name]);
            $department->roles()->attach($request->role);

            if ($request->filled('permissions')) {
                $department->permissions()->attach($request->permissions);
            }

            return redirect()->route('departments.index')->with('success', 'Department created with roles and permissions.');
        } catch (\Exception $e) {
            Log::error('Department Store Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Department $department)
    {
        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $department->load('roles', 'permissions');
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('admin.departments.edit', compact('department', 'roles', 'permissions'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|array|min:1',
            'role.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $name = $request->name;

        $exists = Department::whereEncrypted('name', $name)
            ->where('id', '!=', $department->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'The department name must be unique.'])->withInput();
        }

        try {
            $department->update(['name' => $name]);
            $department->roles()->sync($request->role);
            $department->permissions()->sync($request->permissions ?? []);

            return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
        } catch (\Exception $e) {
            Log::error('Department Update Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Department Update Error: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Department $department)
    {
        try {
            $department->delete();
            return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Department Delete Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Department Delete Error: ' . $e->getMessage()]);
        }
    }
}
