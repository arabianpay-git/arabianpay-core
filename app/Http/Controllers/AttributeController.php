<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Attribute::with('translations')->select('attributes.*');
            return DataTables::of($data)
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('attributes.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('attributes.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.attributes.index');
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
        ]);

        $attribute = Attribute::create([
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($attribute, $request);

        return redirect()->route('attributes.index')->with('success', 'Attribute created successfully.');
    }

    public function edit(Attribute $attribute)
    {
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
        ]);

        $attribute->update([
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($attribute, $request);

        return redirect()->route('attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()->route('attributes.index')->with('success', 'Attribute deleted successfully.');
    }

    private function storeOrUpdateTranslations(Attribute $attribute, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $attribute->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
