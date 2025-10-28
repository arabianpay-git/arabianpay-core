<?php

namespace App\Http\Controllers;

use App\Models\PartialPayment;
use App\Models\SchedulePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartialPaymentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'employee_id' => 'nullable|exists:users,id',
            'schedule_payment_id' => 'required|exists:schedule_payments,id',
            'partial_amount' => 'required|numeric|min:1',
            'partial_due_date' => 'required|date|after_or_equal:today',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $schedule = SchedulePayment::findOrFail($request->schedule_payment_id);

        // Total already paid in previous partial payments
        $totalPartialPaid = $schedule->partialPayments()->sum('partial_amount');

        // Calculate remaining amount including deducted_amount
        $remaining = $schedule->instalment_amount - ($schedule->deducted_amount + $totalPartialPaid);

        if ($request->partial_amount > $remaining) {
            return response()->json([
                'success' => false,
                'error' => "Partial amount cannot exceed remaining amount. Remaining limit: SAR " . number_format($remaining, 2)
            ]);
        }

        $partial = PartialPayment::create([
            'user_id' => $request->user_id,
            'employee_id' => $request->employee_id,
            'schedule_payment_id' => $request->schedule_payment_id,
            'partial_amount' => $request->partial_amount,
            'partial_due_date' => $request->partial_due_date,
            'details' => $request->details ? ['text' => $request->details] : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Partial payment created successfully. Remaining limit now: SAR ' . number_format($remaining - $request->partial_amount, 2)
        ]);
    }


    public function edit($id)
    {
        $partial = PartialPayment::findOrFail($id);
        return response()->json(['success' => true, 'data' => $partial]);
    }

    public function update(Request $request, $id)
    {
        $partial = PartialPayment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'partial_due_date' => 'required|date|after_or_equal:today',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()]);
        }

        $partial->update([
            'partial_due_date' => $request->partial_due_date,
            'details' => $request->details ? ['text' => $request->details] : null,
        ]);

        return response()->json(['success' => true, 'message' => 'Partial payment updated successfully.']);
    }

    public function destroy($id)
    {
        $partial = PartialPayment::findOrFail($id);
        $partial->delete();

        return response()->json(['success' => true, 'message' => 'Partial payment deleted successfully.']);
    }
}
