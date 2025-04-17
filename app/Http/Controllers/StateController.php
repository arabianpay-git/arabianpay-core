<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Country;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = State::with('translations', 'country')->select('states.*');
            return DataTables::of($data)
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('states.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('states.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.states.index');
    }

    public function create()
    {
        $countries = Country::all();
        return view('admin.states.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        $state = State::create([
            'country_id' => $request->country_id,
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($state, $request);

        return redirect()->route('states.index')->with('success', 'State created successfully.');
    }

    public function edit(State $state)
    {
        $countries = Country::all();
        return view('admin.states.edit', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
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
