<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['merchant', 'user'])->latest()->paginate(10);

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $merchants = Merchant::all();
        $users = User::all();

        return view('branches.create', compact('merchants', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'merchant_id' => 'nullable|exists:merchants,id',
            'user_id' => 'nullable|exists:users,id',
            'is_main' => 'required|boolean',
            'name' => 'required|string|max:255',
            'activity' => 'required|string|max:255',
            'status' => 'required|in:active,pending,suspended,closed',
        ]);

        try {
            Branch::create($data);

            return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
        } catch (\Exception $e) {
            Log::error('Branch creation failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Failed to create branch.');
        }
    }

    public function edit(Branch $branch)
    {
        $merchants = Merchant::all();
        $users = User::all();

        return view('branches.edit', compact('branch', 'merchants', 'users'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'merchant_id' => 'nullable|exists:merchants,id',
            'user_id' => 'nullable|exists:users,id',
            'is_main' => 'required|boolean',
            'name' => 'required|string|max:255',
            'activity' => 'required|string|max:255',
            'status' => 'required|in:active,pending,suspended,closed',
        ]);

        try {
            $branch->update($data);

            return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
        } catch (\Exception $e) {
            Log::error('Branch update failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Failed to update branch.');
        }
    }

    public function destroy(Branch $branch)
    {
        try {
            $branch->delete();

            return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Branch deletion failed: '.$e->getMessage());

            return redirect()->route('branches.index')->with('error', 'Failed to delete branch.');
        }
    }
}
