<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DepartmentController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        // Log view operation for department list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Department',
            'Viewed departments list',
            [
                'page' => request()->get('page', 1),
                'per_page' => 10,
            ]
        );

        $departments = Department::with(['roles', 'users'])->latest()->paginate(10);
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        // Log view create form
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'Department',
            'Viewed department creation form'
        );

        $roles = Role::with('permissions')->orderBy('name', 'asc')->get();

        // Group permissions by their subject (first part before dot)
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
            'role.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $name = $request->name;

        $exists = Department::whereEncrypted('name', $name)->exists();

        if ($exists) {
            // Log duplicate creation attempt
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'duplicate_department_attempt',
                'entity_type' => 'Department',
                'action_summary' => 'Attempted to create duplicate department',
                'properties' => [
                    'department_name' => $name,
                    'reason' => 'Department name must be unique',
                ],
            ]);

            return back()->withErrors(['name' => 'The department name must be unique.'])->withInput();
        }

        DB::beginTransaction();

        try {
            $department = Department::create(['name' => $name]);

            // Attach roles in department_has_roles
            $department->roles()->attach($request->role);

            // Attach permissions in department_has_permissions
            if ($request->filled('permissions')) {
                $department->permissions()->attach($request->permissions);
            }

            // Get the attached roles and permissions for logging
            $attachedRoles = $department->roles()->pluck('name')->toArray();
            $attachedPermissions = $department->permissions()->pluck('name')->toArray();

            // Log department creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New department created for organizational structure',
                'business_operation',
                ['name']
            );

            $this->auditTrailService->logCreated(
                $department,
                "Created new department: '{$name}'",
                array_merge([
                    'roles_assigned' => $attachedRoles,
                    'permissions_assigned' => $attachedPermissions,
                    'role_count' => count($request->role),
                    'permission_count' => count($request->permissions ?? []),
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('departments.index')->with('success', 'Department created with roles and permissions.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'department_creation_failed',
                'entity_type' => 'Department',
                'action_summary' => 'Failed to create department',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token']),
                    'department_name' => $name,
                ],
            ]);

            Log::error('Department Store Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Department $department)
    {
        // Log department view with entity_id
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_details',
            'entity_type' => 'Department',
            'entity_id' => $department->id,
            'action_summary' => "Viewed department details: '{$department->name}'",
            'properties' => [
                'user_count' => $department->users()->count(),
                'role_count' => $department->roles()->count(),
                'permission_count' => $department->permissions()->count(),
            ],
        ]);

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        // Log view edit form with entity_id
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'Department',
            'entity_id' => $department->id,
            'action_summary' => "Viewed edit form for department: '{$department->name}'",
        ]);

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

        // Unique check except current department
        $exists = Department::whereEncrypted('name', $name)
            ->where('id', '!=', $department->id)
            ->exists();

        if ($exists) {
            // Log duplicate update attempt
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'duplicate_department_update_attempt',
                'entity_type' => 'Department',
                'entity_id' => $department->id,
                'action_summary' => "Attempted to update department to duplicate name: '{$name}'",
                'properties' => [
                    'current_department_name' => $department->name,
                    'new_department_name' => $name,
                ],
            ]);

            return back()->with(['error' => 'The department name must be unique.']);
        }

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $department->toArray();

            // Get old roles and permissions
            $oldRoles = $department->roles()->pluck('roles.id', 'roles.name')->toArray();
            $oldPermissions = $department->permissions()->pluck('permissions.id', 'permissions.name')->toArray();

            $department->update(['name' => $name]);

            $department->roles()->sync($request->role);
            $department->permissions()->sync($request->permissions ?? []);

            // Get new roles and permissions
            $newRoles = $department->roles()->pluck('roles.id', 'roles.name')->toArray();
            $newPermissions = $department->permissions()->pluck('permissions.id', 'permissions.name')->toArray();

            // Calculate changes
            $rolesAdded = array_diff_key($newRoles, $oldRoles);
            $rolesRemoved = array_diff_key($oldRoles, $newRoles);
            $permissionsAdded = array_diff_key($newPermissions, $oldPermissions);
            $permissionsRemoved = array_diff_key($oldPermissions, $newPermissions);

            // Log department update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Department updated to reflect organizational changes',
                'business_operation',
                ['name']
            );

            $this->auditTrailService->logUpdated(
                $department,
                $oldData,
                "Updated department: '{$department->name}'",
                array_merge([
                    'old_roles' => array_keys($oldRoles),
                    'new_roles' => array_keys($newRoles),
                    'old_permissions' => array_keys($oldPermissions),
                    'new_permissions' => array_keys($newPermissions),
                    'roles_added' => array_keys($rolesAdded),
                    'roles_removed' => array_keys($rolesRemoved),
                    'permissions_added' => array_keys($permissionsAdded),
                    'permissions_removed' => array_keys($permissionsRemoved),
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'department_update_failed',
                'entity_type' => 'Department',
                'entity_id' => $department->id,
                'action_summary' => "Failed to update department: '{$department->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method']),
                ],
            ]);

            Log::error('Department Update Error: ' . $e->getMessage());
            return back()->with(['error' => 'Department Update Error: ' . $e->getMessage()]);
        }
    }

    public function destroy(Department $department)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $departmentData = $department->toArray();

            // Get associated data for logging
            $userCount = $department->users()->count();
            $roleCount = $department->roles()->count();
            $permissionCount = $department->permissions()->count();

            // Log department deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Department removed due to organizational restructuring',
                'data_cleanup',
                ['name']
            );

            $this->auditTrailService->logDeleted(
                $department,
                "Deleted department: '{$department->name}'",
                array_merge([
                    'user_count' => $userCount,
                    'role_count' => $roleCount,
                    'permission_count' => $permissionCount,
                    'affected_users' => $userCount > 0,
                ], $justificationData)
            );

            $department->delete();

            DB::commit();

            return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'department_deletion_failed',
                'entity_type' => 'Department',
                'entity_id' => $department->id,
                'action_summary' => "Failed to delete department: '{$department->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                ],
            ]);

            Log::error('Department Delete Error: ' . $e->getMessage());
            return back()->with(['error' => 'Department Delete Error: ' . $e->getMessage()]);
        }
    }
}
