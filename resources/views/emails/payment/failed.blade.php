@extends('emails.layouts.app')

@section('content')
    <h2 style="color:#d9534f;">Payment Failed</h2>

    <p>Dear {{ $user->first_name ?? 'Customer' }},</p>

    <p>We attempted to collect your scheduled payment <strong>#{{ $schedule->uuid }}</strong> for order
        <strong>#{{ $schedule->order_id }}</strong> but the transaction failed.</p>

    <table width="100%" cellpadding="8" cellspacing="0"
        style="background:#f8f9fa; border-radius:6px; margin:20px 0; border:1px solid #ddd;">
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ $schedule->instalment_amount }} {{ config('services.clickpay.currency') }}</td>
        </tr>
        <tr>
            <td><strong>Reason</strong></td>
            <td>{{ $failureReason ?? 'Unknown' }}</td>
        </tr>
        <tr>
            <td><strong>Date</strong></td>
            <td>{{ now()->toDayDateTimeString() }}</td>
        </tr>
    </table>

    <p>Please update your payment method or contact support to avoid further action.</p>
@endsection
