<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = City::with('translations', 'state', 'country')->select('cities.*');
            return DataTables::of($data)
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('cities.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('cities.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.cities.index');
    }

    public function create()
    {
        $countries = Country::all();
        $states = State::all();
        return view('admin.cities.create', compact('countries', 'states'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
        ]);

        $city = City::create([
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($city, $request);

        return redirect()->route('cities.index')->with('success', 'City created successfully.');
    }

    public function edit(City $city)
    {
        $countries = Country::all();
        $states = State::all();
        return view('admin.cities.edit', compact('city', 'countries', 'states'));
    }

    public function update(Request $request, City $city)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
        ]);

        $city->update([
            'country_id' => $request->country_id,
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
