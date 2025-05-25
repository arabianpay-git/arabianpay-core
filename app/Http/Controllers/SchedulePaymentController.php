<?php

namespace App\Http\Controllers;

use App\Models\SchedulePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
