<?php

namespace App\Http\Controllers;

use App\Models\DunningTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DunningTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DunningTemplate::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('dpd_bucket')) {
            $query->where('dpd_bucket', $request->dpd_bucket);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->latest()->paginate(10);

        return view('admin.collections.dunning', compact('items'));
    }

    public function show($id)
    {
        $dunning = DunningTemplate::find($id);

        if (!$dunning) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $dunning]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'dpd_bucket' => 'required|string|max:20',
            'type' => 'required|string|max:50',
            'language' => 'required|string|max:10',
            'throttling' => 'nullable|string|max:50',
            'message' => 'required|min:3|max:700|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = DunningTemplate::create($request->all());
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function update(Request $request, $id)
    {
        $template = DunningTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'dpd_bucket' => 'required|string|max:20',
            'type' => 'required|string|max:50',
            'language' => 'required|string|max:10',
            'throttling' => 'nullable|string|max:50',
            'message' => 'required|min:3|max:700|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template->update($request->all());
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function destroy($id)
    {
        DunningTemplate::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
