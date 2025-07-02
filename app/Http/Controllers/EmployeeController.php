<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::where('user_type', 'employee')->latest()->paginate(10);
        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        $departments = Department::orderBy('name', 'asc')->get();
        return view('admin.employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'phone_number'    => 'required|string|max:20|unique:users,phone_number',
            'department_id'   => 'nullable|exists:departments,id',
            'is_manager'      => 'nullable|boolean',
            'password'        => 'required|string|min:6|max:18|confirmed',
            'role_id'         => 'required|exists:roles,id',
            'permission_ids'  => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $user = User::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'business_name' => $request->first_name . $request->email,
            'country_id'    => Country::first()?->id,
            'state_id'      => State::first()?->id,
            'city_id'       => City::first()?->id,
            'department_id' => $request->department_id,
            'is_manager'    => $request->boolean('is_manager'),
            'password'      => Hash::make($request->password),
            'user_type'     => 'employee',
        ]);

        // Assign single role
        $role = Role::findOrFail($request->role_id);
        $user->syncRoles([$role->name]);

        // Assign direct permissions if provided
        if ($request->filled('permission_ids')) {
            $permissions = Permission::whereIn('id', $request->permission_ids)->pluck('name')->toArray();
            $user->syncPermissions($permissions);
        }

        // Log the creation of the employee
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $authUser->logModelAction(
            event: 'create',
            description: "{$authUser->first_name} {$authUser->last_name} created a new employee: {$request->first_name} {$request->last_name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    // public function show(User $employee)
    // {
    //     return view('admin.employees.show', compact('employee'));
    // }

    public function edit(User $employee)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, User $employee)
    {
        $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $employee->id,
            'phone_number'    => 'required|string|max:20|unique:users,phone_number,' . $employee->id,
            'department_id'   => 'nullable|exists:departments,id',
            'is_manager'      => 'nullable|boolean',
            'password'        => 'nullable|string|min:6|max:18|confirmed',
            'role_id'         => 'required|exists:roles,id',
            'permission_ids'  => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Check permissions belong to the department
        if ($request->filled('permission_ids') && $request->department_id) {
            $departmentPermissionIds = DB::table('department_has_permissions')
                ->where('department_id', $request->department_id)
                ->pluck('permission_id')
                ->toArray();

            $invalidPermissions = array_diff($request->permission_ids, $departmentPermissionIds);
            if (!empty($invalidPermissions)) {
                return back()->withErrors([
                    'permission_ids' => 'Some selected permissions are not valid for the chosen department.'
                ])->withInput();
            }
        }

        $data = [
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'business_name' => $request->first_name . $request->email,
            'country_id'    => Country::first()?->id,
            'state_id'      => State::first()?->id,
            'city_id'       => City::first()?->id,
            'department_id' => $request->department_id,
            'is_manager'    => $request->boolean('is_manager'),
            'user_type'     => 'employee',
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        // Sync role (single role)
        $role = Role::find($request->role_id);
        $employee->syncRoles($role ? [$role->name] : []);

        // Sync permissions
        $permissions = Permission::whereIn('id', $request->permission_ids ?? [])->pluck('name')->toArray();
        $employee->syncPermissions($permissions);

        // Log the update of the employee
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $authUser->logModelAction(
            event: 'update',
            description: "{$authUser->first_name} {$authUser->last_name} updated employee: {$request->first_name} {$request->last_name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }



    public function destroy(User $employee)
    {

        // log the deletion of the employee
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'delete',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " deleted employee: {$employee->first_name} {$employee->last_name}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );
        $employee->delete();
        return back()->with('success', 'Employee deleted successfully.');
    }

    public function getDepartmentAccess(Department $department)
    {
        $roles = $department->roles()->with('permissions:id,name')->select('id', 'name')->get();
        $permissions = $department->permissions()->select('id', 'name')->get();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
