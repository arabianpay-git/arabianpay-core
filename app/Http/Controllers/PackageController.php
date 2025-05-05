<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::latest()->paginate(10);
        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:packages,name'],
            'min_score'  => 'required|integer',
            'max_score'  => 'required|integer|gte:min_score',
        ]);

        $data = $request->only(['name', 'min_score', 'max_score', 'logo']);

        Package::create($data);

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('packages', 'name')->ignore($package->id),
            ],
            'min_score'  => 'required|integer',
            'max_score'  => 'required|integer|gte:min_score',
        ]);

        $data = [
            'name' => $request->name['en'],
            'min_score' => $request->min_score,
            'max_score' => $request->max_score,
            'logo' => $request->logo,
        ];

        $package->update($data);


        $package->update($data);

        $this->storeOrUpdateTranslations($package, $request);

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('packages.index')->with('success', 'Package deleted successfully.');
    }

    private function storeOrUpdateTranslations(Package $package, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $package->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
