<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        $users = User::with('department')->where('user_type', 'employee')->paginate(10);

        // Log view of user roles list
        $this->auditTrailService->log([
            'event_category' => 'user_management',
            'event_type' => 'user_roles_viewed',
            'entity_type' => 'User',
            'action_summary' => 'Viewed user roles management page',
            'properties' => [
                'user_type_filter' => 'employee',
                'viewed_by' => Auth::id(),
            ],
        ]);

        return view('admin.user_roles.index', compact('users'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        // Log view of user role edit form
        $this->auditTrailService->log([
            'event_category' => 'user_management',
            'event_type' => 'user_role_edit_viewed',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action_summary' => "Viewed role edit form for user {$user->email}",
            'properties' => [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'current_roles' => $user->getRoleNames()->toArray(),
                'available_roles_count' => $roles->count(),
                'viewed_by' => Auth::id(),
            ],
        ]);

        return view('admin.user_roles.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findById($request->role_id, 'web');

        // Get old roles before update
        $oldRoles = $user->getRoleNames()->toArray();

        // Get old role for PDPL justification
        $oldRoleName = ! empty($oldRoles) ? $oldRoles[0] : 'no_role';
        $newRoleName = $role->name;

        $user->syncRoles([$role->name]);

        // Prepare justification data
        $justificationData = $this->auditTrailService->withJustification(
            "User role updated from {$oldRoleName} to {$newRoleName} based on job requirements",
            'role_assignment',
            ['email', 'role'] // PII fields involved
        );

        // Log the role update with audit trail service
        $this->auditTrailService->log([
            'event_category' => 'user_management',
            'event_type' => 'user_role_updated',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'action_summary' => "Updated role for user {$user->email}",
            'before_state' => [
                'roles' => $oldRoles,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ],
            'after_state' => [
                'roles' => [$role->name],
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ],
            'properties' => [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'old_roles' => $oldRoles,
                'new_role' => $role->name,
                'role_id' => $role->id,
                'updated_by' => Auth::id(),
                'updated_by_email' => Auth::user()->email,
            ],
            'masking_state' => 'partial',
        ] + $justificationData);

        return redirect()->route('user-roles.index')->with('success', 'User role updated.');
    }
}
