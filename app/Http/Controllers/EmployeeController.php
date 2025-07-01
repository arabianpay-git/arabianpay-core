<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'phone_number'  => 'required|string|max:20|unique:users,phone_number',
            'department_id'    => 'nullable',
            'is_manager'    => 'nullable|boolean',
            'password'      => 'required|string|min:6|max:18|confirmed',
        ]);

        User::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'business_name' => $request->first_name . $request->email,
            'country_id'    => Country::first()?->id,
            'state_id'      => State::first()?->id,
            'city_id'       => City::first()?->id,
            'department_id'    => $request->department_id,
            'is_manager'    => $request->boolean('is_manager'),
            'password'      => Hash::make($request->password),
            'user_type'     => 'employee',
        ]);

        // Log the creation of the employee
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'create',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " created a new employee: {$request->first_name} {$request->last_name}",
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
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $employee->id,
            'phone_number'  => 'required|string|max:20|unique:users,phone_number,' . $employee->id,
            'department_id'    => 'nullable|',
            'is_manager'    => 'nullable|boolean',
            'password'      => 'nullable|string|min:6|max:18|confirmed',
        ]);

        $data = [
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'business_name' => $request->first_name . $request->email,
            'country_id'    => Country::first()?->id,
            'state_id'      => State::first()?->id,
            'city_id'       => City::first()?->id,
            'department_id'    => $request->department_id,
            'is_manager'    => $request->boolean('is_manager'),
            'user_type'     => 'employee',
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        // Log the update of the employee
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated employee: {$request->first_name} {$request->last_name}",
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
}
