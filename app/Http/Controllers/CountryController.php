<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Country::with('translations')->select('countries.*');
            return DataTables::of($data)
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('countries.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('countries.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.countries.index');
    }

    public function create()
    {
        return view('admin.countries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
            'code' => 'required|string|max:3',
        ]);

        $country = Country::create([
            'code' => $request->code,
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($country, $request);

        return redirect()->route('countries.index')->with('success', 'Country created successfully.');
    }

    public function edit(Country $country)
    {
        return view('admin.countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
            'code' => 'required|string|max:3',
        ]);

        $country->update([
            'code' => $request->code,
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($country, $request);

        return redirect()->route('countries.index')->with('success', 'Country updated successfully.');
    }

    public function destroy(Country $country)
    {
        $country->delete();

        return redirect()->route('countries.index')->with('success', 'Country deleted successfully.');
    }

    private function storeOrUpdateTranslations(Country $country, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $country->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
