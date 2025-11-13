@extends('emails.layouts.app')

@section('content')
    <h2 style="color:#0d6efd;">Upcoming Payment — Due in {{ $daysLeft }} day(s)</h2>

    <p>Dear {{ $user->first_name ?? 'Customer' }},</p>

    <p>This is a reminder that your scheduled payment <strong>#{{ $schedule->uuid }}</strong> for order
        <strong>#{{ $schedule->order_id }}</strong> is due in <strong>{{ $daysLeft }} day(s)</strong> (due date:
        {{ $schedule->due_date->toFormattedDateString() }}).</p>

    <table width="100%" cellpadding="8" cellspacing="0"
        style="background:#f8f9fa; border-radius:6px; margin:20px 0; border:1px solid #ddd;">
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ $schedule->instalment_amount }} {{ config('services.clickpay.currency') }}</td>
        </tr>
        <tr>
            <td><strong>Due Date</strong></td>
            <td>{{ $schedule->due_date->toFormattedDateString() }}</td>
        </tr>
    </table>

    <p>Please ensure your saved card is valid or update your payment method to avoid interruption.</p>

    <p style="color:#6c757d; font-size:13px; margin-top:20px;">
        This email is sent automatically {{ $daysLeft == 1 ? '1 day' : $daysLeft . ' days' }} before the payment due date.
    </p>
@endsection
