<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Country;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $states = State::with('translations', 'country')->select('states.*')->paginate(10);

        return view('admin.locations.states.index', compact('states'));
    }

    public function create()
    {
        $countries = Country::all();
        return view('admin.locations.states.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('states')->where(function ($query) use ($request) {
                    return $query->where('country_id', $request->country_id);
                }),
            ],
            'country_id' => 'required|exists:countries,id',
        ]);

        $state = State::create([
            'country_id' => $request->country_id,
            'name' => $request->name,
        ]);

        $this->storeOrUpdateTranslations($state, $request);

        return redirect()->route('states.index')->with('success', 'State created successfully.');
    }


    public function edit(State $state)
    {
        $countries = Country::all();
        return view('admin.locations.states.edit', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('states', 'name')->ignore($state->id),
            ],
            'country_id' => 'required|exists:countries,id',
        ]);

        $state->update([
            'country_id' => $request->country_id,
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($state, $request);

        return redirect()->route('states.index')->with('success', 'State updated successfully.');
    }


    public function destroy(State $state)
    {
        $state->delete();

        return redirect()->route('states.index')->with('success', 'State deleted successfully.');
    }

    private function storeOrUpdateTranslations(State $state, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $state->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
