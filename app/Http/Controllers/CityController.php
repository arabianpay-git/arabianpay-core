<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $cities = City::with('translations', 'state')->select('cities.*')->paginate(10);

        return view('admin.locations.cities.index', compact('cities'));
    }

    public function create()
    {
        $countries = Country::all();
        $states = State::all();
        return view('admin.locations.cities.create', compact('countries', 'states'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('cities')->where(function ($query) use ($request) {
                    return $query->where('state_id', $request->state_id);
                }),
            ],

            'state_id' => 'required|exists:states,id',
        ]);

        $city = City::create([
            'state_id' => $request->state_id,
            'name' => $request->name,
        ]);

        $this->storeOrUpdateTranslations($city, $request);

        return redirect()->route('cities.index')->with('success', 'City created successfully.');
    }

    public function edit(City $city)
    {
        $states = State::all();
        return view('admin.locations.cities.edit', compact('city', 'states'));
    }

    public function update(Request $request, City $city)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('cities', 'name')->ignore($city->id),
            ],
            'state_id' => 'required|exists:states,id',
        ]);

        $city->update([
            'state_id' => $request->state_id,
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($city, $request);

        return redirect()->route('cities.index')->with('success', 'City updated successfully.');
    }

    public function destroy(City $city)
    {
        $city->delete();

        return redirect()->route('cities.index')->with('success', 'City deleted successfully.');
    }

    private function storeOrUpdateTranslations(City $city, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $city->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
