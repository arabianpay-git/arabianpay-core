<?php

namespace App\Http\Controllers;

use App\Models\Risk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskController extends Controller
{
    public function index()
    {
        $risks = Risk::with('user')->latest()->paginate(10);

        return view('admin.register.index', compact('risks'));
    }

    public function create()
    {
        return view('admin.register.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:700',
            'type' => 'nullable|string|max:255',
            'entity' => 'nullable|string|max:255',
            'score' => 'required|numeric',
            'status' => 'required|in:low,medium,high,critical',
            'action' => 'nullable|string|max:255',
        ]);

        $validated['owner'] = Auth::id();
        Risk::create($validated);

        return redirect()->route('risk-register.index')->with('success', 'Risk created successfully.');
    }

    public function edit($id)
    {
        $risk = Risk::findorFail($id);

        return view('admin.register.edit', compact('risk'));
    }

    public function update(Request $request, $id)
    {
        $risk = Risk::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string|max:700',
            'type' => 'nullable|string|max:255',
            'entity' => 'nullable|string|max:255',
            'score' => 'required|numeric',
            'status' => 'required|in:low,medium,high,critical',
            'action' => 'nullable|string|max:255',
        ]);

        $validated['owner'] = Auth::id();
        $risk->update($validated);

        return redirect()->route('risk-register.index')->with('success', 'Risk updated successfully.');
    }

    public function destroy($risk_register)
    {
        $risk = Risk::findOrFail($risk_register);
        $risk->delete();

        return redirect()->route('risk-register.index')->with('success', 'Risk deleted successfully.');
    }
}
