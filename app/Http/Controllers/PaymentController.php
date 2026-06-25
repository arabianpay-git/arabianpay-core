<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class PaymentController extends Controller
{
    // Function to get all payments with 'pending' status
    public function pending()
    {
        $payments = Payment::where('payment_status', 'pending')->paginate(10);
        $type = 'Pending';

        return view('payments.index', compact('payments', 'type'));
    }

    // Function to get all payments with 'due' status
    public function due()
    {
        $payments = Payment::where('payment_status', 'due')->paginate(10);
        $type = 'Due';

        return view('payments.index', compact('payments', 'type'));
    }

    // Function to get all payments with 'late' status
    public function late()
    {
        $payments = Payment::where('payment_status', 'late')->paginate(10);
        $type = 'Late';

        return view('payments.index', compact('payments', 'type'));
    }

    // Function to get all payments with 'paid' status
    public function paid()
    {
        $payments = Payment::where('payment_status', 'paid')->paginate(10);
        $type = 'Paid';

        return view('payments.index', compact('payments', 'type'));
    }

    // Function to get all payments with 'failed' status
    public function failed()
    {
        $payments = Payment::where('payment_status', 'failed')->paginate(10);
        $type = 'Failed';

        return view('payments.index', compact('payments', 'type'));
    }

    public function transactionHistory()
    {
        $payments = Payment::paginate(10);
        $type = 'All Payments';

        return view('payments.index', compact('payments', 'type'));
    }
}
