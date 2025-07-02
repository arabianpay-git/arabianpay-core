<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->paginate(10);

        // Key departments collection by id for quick access in view
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
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $validPermissionIds = $this->getDepartmentPermissionIds($request->department_id);

        $selectedPermissionIds = array_intersect($request->permissions ?? [], $validPermissionIds);
        $permissionNames = $this->getPermissionNamesByIds($selectedPermissionIds);

        $role->syncPermissions($permissionNames);

        $this->logAction('assign_permissions', $role, $request->department_id);

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
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::findOrFail($roleId);
        $validPermissionIds = $this->getDepartmentPermissionIds($departmentId);

        $selectedPermissionIds = array_intersect($request->permissions ?? [], $validPermissionIds);
        $permissionNames = $this->getPermissionNamesByIds($selectedPermissionIds);

        // Remove old department's permissions
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

    // AJAX method to load permissions grouped by prefix for given department and role
    public function getPermissionsByDepartment($departmentId, $roleId)
    {
        $departmentPermissionIds = $this->getDepartmentPermissionIds($departmentId);
        $permissions = Permission::whereIn('id', $departmentPermissionIds)->get()
            ->groupBy(fn($permission) => explode('.', $permission->name)[0]);

        $role = Role::findOrFail($roleId);

        return view('admin.role_permissions._permissions', compact('permissions', 'role'))->render();
    }

    // Helper: Get permission IDs linked to a department
    private function getDepartmentPermissionIds(int $departmentId): array
    {
        return DB::table('department_has_permissions')
            ->where('department_id', $departmentId)
            ->pluck('permission_id')
            ->toArray();
    }

    // Helper: Get permission names by IDs
    private function getPermissionNamesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Permission::whereIn('id', $ids)->pluck('name')->toArray();
    }

    // Helper: Log model action with user info and ip/batch uuid
    private function logAction(string $event, Role $role, int $departmentId): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->logModelAction(
            event: $event,
            description: "{$user->first_name} {$user->last_name} {$this->getEventDescription($event)} role: {$role->name} from department ID: {$departmentId}",
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
}
