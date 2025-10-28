<?php

namespace App\Http\Controllers;

use App\Models\Promise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromiseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedule_payment_id' => 'required|exists:schedule_payments,id',
            'method' => 'required|string|in:call,email',
            'promise_date' => 'required|date|after_or_equal:today'
        ]);

        // Check if a promise already exists for this schedule_payment_id
        $existing = Promise::where('schedule_payment_id', $request->schedule_payment_id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A promise already exists for this installment.'
            ], 422);
        }

        // Create the promise
        Promise::create([
            'user_id' => $request->user_id,
            'employee_id' => Auth::id(),
            'schedule_payment_id' => $request->schedule_payment_id,
            'promise_date' => $request->promise_date,
            'method' => $request->method,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Promise recorded successfully."
            ]);
        }

        return back()->with('success', "Promise recorded successfully.");
    }

    public function update(Request $request, Promise $promise)
    {
        $request->validate([
            'promise_date' => 'required|date|after_or_equal:today',
        ]);

        // Update the promise
        $promise->promise_date = $request->promise_date;
        $promise->save();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Promise updated successfully.']);
        }

        return back()->with('success', 'Promise updated successfully.');
    }

    public function destroy(Promise $promise)
    {
        $promise->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Promise deleted successfully.']);
        }

        return back()->with('success', 'Promise deleted successfully.');
    }
}
