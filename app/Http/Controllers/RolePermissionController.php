<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->paginate(10);
        $departments = Department::all()->keyBy('id');

        return view('admin.role_permissions.index', compact('roles', 'departments'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();

        return view('admin.role_permissions.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::findOrFail($data['role_id']);
        $validPermissionIds = $this->getDepartmentPermissionIds($data['department_id']);

        // Filter permissions: only allow those permitted by department
        $selectedPermissionIds = array_intersect($data['permissions'] ?? [], $validPermissionIds);
        $permissionNames = $this->getPermissionNamesByIds($selectedPermissionIds);

        // Sync permissions on role (stored in role_has_permissions)
        $role->syncPermissions($permissionNames);

        $this->logAction('assign_permissions', $role, $data['department_id']);

        return redirect()->route('role-permissions.index')->with('success', 'Permissions assigned successfully.');
    }

    public function edit($roleId, $departmentId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $department = Department::findOrFail($departmentId);
        $departments = Department::all();

        return view('admin.role_permissions.edit', compact('role', 'department', 'departments'));
    }

    public function update(Request $request, $roleId, $departmentId)
    {
        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::findOrFail($roleId);
        $validPermissionIds = $this->getDepartmentPermissionIds($departmentId);

        $selectedPermissionIds = array_intersect($data['permissions'] ?? [], $validPermissionIds);
        $permissionNames = $this->getPermissionNamesByIds($selectedPermissionIds);

        // Remove old department permissions from role
        $currentPermissionNames = $this->getPermissionNamesByIds($validPermissionIds);
        $role->revokePermissionTo($currentPermissionNames);

        // Assign new permissions
        $role->givePermissionTo($permissionNames);

        $this->logAction('update_permissions', $role, $departmentId);

        return redirect()->route('role-permissions.index')->with('success', 'Permissions updated successfully.');
    }

    public function destroy($roleId, $departmentId)
    {
        $role = Role::findOrFail($roleId);
        $permissionIds = $this->getDepartmentPermissionIds($departmentId);
        $permissionNames = $this->getPermissionNamesByIds($permissionIds);

        $role->revokePermissionTo($permissionNames);

        $this->logAction('remove_permissions', $role, $departmentId);

        return redirect()->back()->with('success', 'Permissions removed for department.');
    }

    // AJAX: Get department-scoped permissions grouped by prefix
    public function getPermissionsByDepartment($departmentId, $roleId)
    {
        $departmentPermissionIds = $this->getDepartmentPermissionIds($departmentId);
        $permissions = Permission::whereIn('id', $departmentPermissionIds)
            ->get()
            ->groupBy(fn ($permission) => explode('.', $permission->name)[0]);

        $role = Role::findOrFail($roleId);

        return view('admin.role_permissions._permissions', compact('permissions', 'role'))->render();
    }

    // --- Helpers ---

    /**
     * Get permission IDs allowed for department.
     */
    private function getDepartmentPermissionIds(int $departmentId): array
    {
        return DB::table('department_has_permissions')
            ->where('department_id', $departmentId)
            ->pluck('permission_id')
            ->toArray();
    }

    /**
     * Get permission names from IDs.
     */
    private function getPermissionNamesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Permission::whereIn('id', $ids)->pluck('name')->toArray();
    }

    /**
     * Log actions with user info.
     */
    private function logAction(string $event, Role $role, int $departmentId): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user) {
            return; // no logging if no user context
        }

        $user->logModelAction(
            event: $event,
            description: "{$user->first_name} {$user->last_name} {$this->getEventDescription($event)} role: {$role->name} for department ID: {$departmentId}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );
    }

    private function getEventDescription(string $event): string
    {
        return match ($event) {
            'assign_permissions' => 'assigned permissions to',
            'update_permissions' => 'updated permissions for',
            'remove_permissions' => 'removed permissions for',
            default => $event,
        };
    }

    public function getRolesByDepartment($departmentId)
    {
        $department = Department::with('roles')->findOrFail($departmentId);

        return response()->json(
            $department->roles()->select('roles.id', 'roles.name')->get()
        );
    }
}
