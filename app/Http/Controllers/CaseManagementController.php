<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CaseManagement;
use App\Models\User;
use Illuminate\Http\Request;

class CaseManagementController extends Controller
{
    public function index()
    {
        $cases = CaseManagement::with('user')->latest()->paginate(10);
        return view('admin.case-management.index', compact('cases'));
    }

    public function create()
    {
        $users = User::select('id', 'first_name', 'is_manager')->where('user_type', 'employee')->get();
        return view('admin.case-management.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'documents' => 'nullable',
        ]);

        CaseManagement::create($validated);

        return redirect()->route('case-management.index')->with('success', 'Case created successfully.');
    }

    public function edit($id)
    {
        $case = CaseManagement::findOrFail($id);
        $users = User::select('id', 'first_name', 'is_manager')->where('user_type', 'employee')->get();
        return view('admin.case-management.edit', compact('case', 'users'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'documents' => 'nullable',
        ]);

        $case = CaseManagement::findOrFail($id);
        $case->update($validated);

        return redirect()->route('case-management.index')->with('success', 'Case updated successfully.');
    }

    public function destroy($id)
    {
        CaseManagement::findOrFail($id)->delete();
        return redirect()->route('case-management.index')->with('success', 'Case deleted successfully.');
    }
}
