@extends('emails.layouts.app')

@section('content')
    <h2 style="color:#f39c12;">⚠️ Payment Due Reminder (Attempt {{ $attemptNumber }})</h2>

    <p>Dear {{ $user->first_name ?? 'Customer' }},</p>

    <p>Your scheduled payment <strong>#{{ $schedule->uuid }}</strong> for order <strong>#{{ $schedule->order_id }}</strong>
        is now due.</p>

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
        <tr>
            <td><strong>Payment Status</strong></td>
            <td>{{ ucfirst($schedule->payment_status) }}</td>
        </tr>
    </table>

    <p>Please make sure your payment method is active or update it to avoid late fees.</p>

    <p style="color:#6c757d; font-size:13px; margin-top:40px;">
        You will receive this reminder daily until the payment is processed.
    </p>
@endsection
