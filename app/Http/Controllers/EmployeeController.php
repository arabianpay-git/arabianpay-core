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
        $departments = Department::orderBy('name')->get();
        return view('admin.employees.create', compact('departments'));
    }

    /**
     * Store a new employee.
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);

        $user = $this->createUser($request);

        $role = Role::findOrFail($request->role_id);
        $user->syncRoles([$role->name]);

        $finalPermissions = $this->getValidPermissions(
            $request->department_id,
            $role,
            $request->permission_ids ?? []
        );
        $user->syncPermissions($finalPermissions);

        $this->logAction('create', $user, $request);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(User $employee)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.employees.edit', compact('employee', 'departments'));
    }

    /**
     * Update an existing employee.
     */
    public function update(Request $request, User $employee)
    {
        $this->validateRequest($request, $employee->id);

        // Validate manual permissions against department
        if ($request->filled('permission_ids') && $request->department_id) {
            if (!$this->validatePermissionsBelongToDepartment($request->permission_ids, $request->department_id)) {
                return back()->withErrors([
                    'permission_ids' => 'Some selected permissions are not valid for the chosen department.'
                ])->withInput();
            }
        }

        $employee->update($this->getUserDataFromRequest($request));

        $role = Role::findOrFail($request->role_id);
        $employee->syncRoles([$role->name]);

        $finalPermissions = $this->getValidPermissions(
            $request->department_id,
            $role,
            $request->permission_ids ?? []
        );
        $employee->syncPermissions($finalPermissions);

        $this->logAction('update', $employee, $request);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Delete employee.
     */
    public function destroy(User $employee)
    {
        $this->logAction('delete', $employee, request());

        $employee->delete();

        return back()->with('success', 'Employee deleted successfully.');
    }

    /**
     * Return roles and permissions for a department.
     */
    public function getDepartmentAccess(Department $department)
    {
        // Load roles with their permissions (only id and name)
        $roles = $department->roles()
            ->select('roles.id', 'roles.name')
            ->with(['permissions:id,name'])
            ->get();

        // Load department's own permissions
        $permissions = $department->permissions()
            ->select('permissions.id', 'permissions.name')
            ->get();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }


    /**
     * Validate incoming request for store/update.
     */
    private function validateRequest(Request $request, ?int $userId = null): void
    {
        $rules = [
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email' . ($userId ? ',' . $userId : ''),
            'phone_number'    => 'required|string|max:20|unique:users,phone_number' . ($userId ? ',' . $userId : ''),
            'department_id'   => 'nullable|exists:departments,id',
            'is_manager'      => 'nullable|boolean',
            'password'        => $userId ? 'nullable|string|min:6|max:18|confirmed' : 'required|string|min:6|max:18|confirmed',
            'role_id'         => 'required|exists:roles,id',
            'permission_ids'  => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ];

        $request->validate($rules);
    }

    /**
     * Create user from request data.
     */
    private function createUser(Request $request): User
    {
        return User::create(array_merge(
            $this->getUserDataFromRequest($request),
            ['password' => Hash::make($request->password), 'user_type' => 'employee']
        ));
    }

    /**
     * Extract user data fields from request.
     */
    private function getUserDataFromRequest(Request $request): array
    {
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
            'sensitive_permissions' => $request->has('sensitive_permissions') ? $request->sensitive_permissions : false
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $data['user_type'] = 'employee';

        return $data;
    }

    /**
     * Validate manual permissions belong to the department.
     */
    private function validatePermissionsBelongToDepartment(array $permissionIds, int $departmentId): bool
    {
        $departmentPermissionIds = DB::table('department_has_permissions')
            ->where('department_id', $departmentId)
            ->pluck('permission_id')
            ->toArray();

        return empty(array_diff($permissionIds, $departmentPermissionIds));
    }

    /**
     * Get valid permissions intersection of department and role, merged with manual valid permissions.
     *
     * @param int|null $departmentId
     * @param Role $role
     * @param array $manualPermissionIds
     * @return array
     */
    private function getValidPermissions(?int $departmentId, Role $role, array $manualPermissionIds): array
    {
        if (!$departmentId) {
            return [];
        }

        $departmentPermissionIds = DB::table('department_has_permissions')
            ->where('department_id', $departmentId)
            ->pluck('permission_id')
            ->toArray();

        $rolePermissionIds = $role->permissions()->pluck('id')->toArray();

        // Intersection of role's permissions and department's permissions
        $validRolePermissions = array_intersect($departmentPermissionIds, $rolePermissionIds);

        // Filter manual permissions to valid ones
        $validManualPermissions = array_intersect($manualPermissionIds, $validRolePermissions);

        // Combine unique permission ids
        $finalPermissionIds = array_unique(array_merge($validRolePermissions, $validManualPermissions));

        return Permission::whereIn('id', $finalPermissionIds)->pluck('name')->toArray();
    }

    /**
     * Log employee actions.
     *
     * @param string $event
     * @param User $employee
     * @param Request $request
     */
    private function logAction(string $event, User $employee, Request $request): void
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $desc = match ($event) {
            'create' => "{$authUser->first_name} {$authUser->last_name} created a new employee: {$employee->first_name} {$employee->last_name}",
            'update' => "{$authUser->first_name} {$authUser->last_name} updated employee: {$employee->first_name} {$employee->last_name}",
            'delete' => "{$authUser->first_name} {$authUser->last_name} deleted employee: {$employee->first_name} {$employee->last_name}",
            default => '',
        };

        $authUser->logModelAction(
            event: $event,
            description: $desc,
            properties: [
                'ip' => $request->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );
    }
}
