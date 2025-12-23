<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\State;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        // Log view operation for employee list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Employee',
            'Viewed employees list',
            [
                'page' => request()->get('page', 1),
                'per_page' => 10,
            ]
        );

        $employees = User::where('user_type', 'employee')->latest()->paginate(10);
        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        // Log view create form
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'Employee',
            'Viewed employee creation form'
        );

        $departments = Department::orderBy('name')->get();
        return view('admin.employees.create', compact('departments'));
    }

    /**
     * Store a new employee.
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);

        DB::beginTransaction();

        try {
            $user = $this->createUser($request);

            $role = Role::findOrFail($request->role_id);
            $user->syncRoles([$role->name]);

            $finalPermissions = $this->getValidPermissions(
                $request->department_id,
                $role,
                $request->permission_ids ?? []
            );
            $user->syncPermissions($finalPermissions);

            // Log employee creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New employee created for organizational needs',
                'business_operation',
                ['email', 'phone_number']
            );

            $this->auditTrailService->logCreated(
                $user,
                "Created new employee: {$user->first_name} {$user->last_name} with role: {$role->name}",
                array_merge([
                    'permissions_assigned' => $finalPermissions,
                    'role_assigned' => $role->name,
                    'department_id' => $request->department_id,
                    'is_manager' => $request->boolean('is_manager'),
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'employee_creation_failed',
                'entity_type' => 'Employee',
                'action_summary' => 'Failed to create employee',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', 'password', 'password_confirmation']),
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(User $employee)
    {
        // Log view edit form with entity_id
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'Employee',
            'entity_id' => $employee->id,
            'action_summary' => "Viewed edit form for employee: {$employee->first_name} {$employee->last_name}",
        ]);

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

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $employee->toArray();

            // Get old permissions and role
            $oldPermissions = $employee->getPermissionNames()->toArray();
            $oldRole = $employee->roles->first()?->name;

            $employee->update($this->getUserDataFromRequest($request));

            $role = Role::findOrFail($request->role_id);
            $employee->syncRoles([$role->name]);

            $finalPermissions = $this->getValidPermissions(
                $request->department_id,
                $role,
                $request->permission_ids ?? []
            );
            $employee->syncPermissions($finalPermissions);

            // Log employee update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Employee information updated for operational requirements',
                'data_correction',
                ['email', 'phone_number']
            );

            $this->auditTrailService->logUpdated(
                $employee,
                $oldData,
                "Updated employee: {$employee->first_name} {$employee->last_name}",
                array_merge([
                    'old_permissions' => $oldPermissions,
                    'new_permissions' => $finalPermissions,
                    'old_role' => $oldRole,
                    'new_role' => $role->name,
                    'department_id' => $request->department_id,
                    'is_manager' => $request->boolean('is_manager'),
                    'password_changed' => $request->filled('password'),
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'employee_update_failed',
                'entity_type' => 'Employee',
                'entity_id' => $employee->id,
                'action_summary' => "Failed to update employee: {$employee->first_name} {$employee->last_name}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method', 'password', 'password_confirmation']),
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Delete employee.
     */
    public function destroy(User $employee)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $employeeData = $employee->toArray();
            $employeePermissions = $employee->getPermissionNames()->toArray();
            $employeeRole = $employee->roles->first()?->name;

            // Log employee deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Employee removed due to organizational changes',
                'data_cleanup',
                ['email', 'phone_number']
            );

            $this->auditTrailService->logDeleted(
                $employee,
                "Deleted employee: {$employee->first_name} {$employee->last_name}",
                array_merge([
                    'permissions_at_deletion' => $employeePermissions,
                    'role_at_deletion' => $employeeRole,
                    'department_id' => $employee->department_id,
                ], $justificationData)
            );

            $employee->delete();

            DB::commit();

            return back()->with('success', 'Employee deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'employee_deletion_failed',
                'entity_type' => 'Employee',
                'entity_id' => $employee->id,
                'action_summary' => "Failed to delete employee: {$employee->first_name} {$employee->last_name}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Return roles and permissions for a department.
     */
    public function getDepartmentAccess(Department $department)
    {
        // Log department access view
        $this->auditTrailService->log([
            'event_category' => 'access_management',
            'event_type' => 'view_department_access',
            'entity_type' => 'Department',
            'entity_id' => $department->id,
            'action_summary' => "Viewed access permissions for department: {$department->name}",
        ]);

        // Load roles with their permissions and sensitive_permissions
        $roles = $department->roles()
            ->select('roles.id', 'roles.name', 'roles.sensitive_permissions')
            ->with(['permissions:id,name'])
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                    'sensitive_permissions' => is_array($role->sensitive_permissions)
                        ? $role->sensitive_permissions
                        : (is_string($role->sensitive_permissions)
                            ? json_decode($role->sensitive_permissions, true)
                            : []),
                ];
            });

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
}
