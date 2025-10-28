<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    public function index($userId)
    {
        $notes = Note::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        return view('notes.index', compact('notes', 'userId'));
    }

    // AJAX store
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'note' => 'required|string',
        ]);

        $note = Note::create([
            'user_id' => $validated['user_id'],
            'employee_id' => Auth::id(),
            'note' => $validated['note'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully',
            'note' => $note->load('employee')
        ]);
    }

    // AJAX update
    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $note->update(['note' => $validated['note']]);

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully',
            'note' => $note
        ]);
    }

    // Optional delete (also via AJAX)
    public function destroy(Note $note)
    {
        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    }
}
