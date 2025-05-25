<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index()
    {
        $users = User::with('roles.permissions')->where('user_type', 'employee')->paginate(10);
        return view('admin.user_roles.index', compact('users'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.user_roles.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findById($request->role_id, 'web');

        $user->syncRoles([$role->name]);

        return redirect()->route('user-roles.index')->with('success', 'User role updated.');
    }
}
