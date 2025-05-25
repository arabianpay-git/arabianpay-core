<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::where('user_type', 'employee')->latest()->paginate(10);
        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('admin.employees.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'phone_number'  => 'required|string|max:20|unique:users,phone_number',
            'department'    => 'nullable|string|max:100',
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
            'department'    => $request->department,
            'is_manager'    => $request->boolean('is_manager'),
            'password'      => Hash::make($request->password),
            'user_type'     => 'employee',
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(User $employee)
    {
        abort_unless($employee->user_type === 'employee', 404);
        return view('admin.employees.show', compact('employee'));
    }

    public function edit(User $employee)
    {
        abort_unless($employee->user_type === 'employee', 404);
        return view('admin.employees.edit', compact('employee'));
    }

    public function update(Request $request, User $employee)
    {
        abort_unless($employee->user_type === 'employee', 404);

        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $employee->id,
            'phone_number'  => 'required|string|max:20|unique:users,phone_number,' . $employee->id,
            'department'    => 'nullable|string|max:100',
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
            'department'    => $request->department,
            'is_manager'    => $request->boolean('is_manager'),
            'user_type'     => 'employee',
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $employee)
    {
        abort_unless($employee->role === 'employee', 404);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
