<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $countries = Country::with('translations')->select('countries.*')->paginate(10);

        return view('admin.locations.countries.index', compact('countries'));
    }

    public function create()
    {
        return view('admin.locations.countries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:countries,name'],
            'code' => ['required', 'string', 'max:3', 'regex:/^[a-zA-Z]+$/', 'unique:countries,code'],
        ]);

        $country = Country::create([
            'code' => $request->code,
            'name' => $request->name,
        ]);

        return redirect()->route('countries.index')->with('success', 'Country created successfully.');
    }

    public function edit(Country $country)
    {
        return view('admin.locations.countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('countries', 'name')->ignore($country->id),
            ],
            'code' => [
                'required',
                'string',
                'max:3',
                'regex:/^[a-zA-Z]+$/',
                Rule::unique('countries', 'code')->ignore($country->id),
            ],
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
