<?php

namespace App\Http\Controllers;

use App\Models\SchedulePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchedulePaymentController extends Controller
{
    public function filterByPaymentStatus($status)
    {
        $schedulePayments = SchedulePayment::where('payment_status', $status)
            ->where('user_id', Auth::id())
            ->select('uuid', 'instalment_number', 'due_date', 'instalment_amount', 'payment_status')
            ->paginate(10);

        $type = ucfirst($status);
        return view('merchant.schedule-payment.index', compact('schedulePayments', 'type'));
    }

    public function index()
    {
        $schedulePayments = SchedulePayment::where('user_id', Auth::id())
            ->select('uuid', 'instalment_number', 'due_date', 'instalment_amount', 'payment_status')
            ->paginate(10);
        $type = "All";
        return view('merchant.schedule-payment.index', compact('schedulePayments', 'type'));
    }
}
