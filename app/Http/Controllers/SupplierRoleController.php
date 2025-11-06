<?php

namespace App\Http\Controllers;

use App\Models\SupplierRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = SupplierRole::latest()->paginate(10); // paginate 10 per page
        return view('admin.supplier-roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.supplier-roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:supplier_roles,name|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        SupplierRole::create($validated);

        return redirect()->route('supplier_roles.index')
            ->with('success', 'Supplier Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SupplierRole $supplierRole)
    {
        return view('admin.supplier-roles.show', compact('supplierRole'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SupplierRole $supplierRole)
    {
        return view('admin.supplier-roles.edit', compact('supplierRole'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupplierRole $supplierRole)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('supplier_roles')->ignore($supplierRole->id),
            ],
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        $supplierRole->update($validated);

        return redirect()->route('supplier_roles.index')
            ->with('success', 'Supplier Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupplierRole $supplierRole)
    {
        $supplierRole->delete();

        return redirect()->route('supplier_roles.index')
            ->with('success', 'Supplier Role deleted successfully.');
    }
}
