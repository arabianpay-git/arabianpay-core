<?php

namespace App\Http\Controllers;

use App\Models\PartialPayment;
use App\Models\SchedulePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SchedulePaymentController extends Controller
{
    public function filterByPaymentStatus($status)
    {
        $user = currentUser();
        $schedulePayments = SchedulePayment::with('assigned')->where('payment_status', $status)
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })->orderByRaw('assigned_to IS NULL DESC')
            ->select('uuid', 'instalment_number', 'due_date', 'instalment_amount', 'payment_status')
            ->paginate(10);

        $type = ucfirst($status);
        return view('admin.schedule-payment.index', compact('schedulePayments', 'type'));
    }

    public function index()
    {
        $user = currentUser();

        $schedulePayments = SchedulePayment::with('assigned')->when($user->user_type !== 'admin', function ($query) use ($user) {
            $query->where('assigned_to', $user->id);
        })
            ->orderByRaw('assigned_to IS NULL DESC')
            ->select('uuid', 'instalment_number', 'due_date', 'instalment_amount', 'payment_status')
            ->paginate(10);

        $type = "All";
        return view('admin.schedule-payment.index', compact('schedulePayments', 'type'));
    }

    public function update(Request $request, $id)
    {
        $payment = SchedulePayment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'due_date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()]);
        }

        $payment->update([
            'due_date' => $request->due_date,
            'payment_status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Schedule payment updated successfully.']);
    }

    public function payNow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id'    => 'required|exists:schedule_payments,id',
            'payment_method' => 'required|string',
            'receipt'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $payment = SchedulePayment::findOrFail($request->schedule_id);

        // Calculate remaining amount
        $remainingAmount = $payment->installment_amount - $payment->deducted_amount;

        // Prevent double payment
        if ($remainingAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This schedule payment is already fully paid.',
            ], 400);
        }

        try {
            $payment->payment_method = $request->payment_method;
            $payment->deducted_amount += $remainingAmount;
            $payment->payment_status = 'paid';
            $payment->paid_at = now();

            // Handle receipt upload
            if ($request->hasFile('receipt')) {
                $disk = 'public';
                $folder = 'receipts';
                $file = $request->file('receipt');

                $extension = strtolower($file->getClientOriginalExtension());
                $filename = Str::random(40) . '.' . $extension;
                $receiptPath = "$folder/$filename";

                $file->storeAs($folder, $filename, $disk);
                $payment->receipt = $receiptPath;
            }

            $payment->save();

            // Update related partial payments if needed
            $this->markPartialPaymentsAsPaid($request->schedule_id, $request->payment_method, $payment->receipt ?? null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment submitted successfully. Amount: ' . $remainingAmount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Private helper to mark all partial payments of a schedule as paid
     */
    private function markPartialPaymentsAsPaid(int $scheduleId, string $paymentMethod, ?string $receiptPath = null)
    {
        PartialPayment::where('schedule_payment_id', $scheduleId)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->update([
                'status' => 'paid',
                'approval_status' => 'approved',
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
                'receipt' => $receiptPath,
            ]);
    }
}
