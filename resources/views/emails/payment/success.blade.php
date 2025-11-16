@extends('emails.layouts.app')

@section('content')
    <h2 style="color:#333;">Payment Successful</h2>

    <p>Dear {{ $user->first_name ?? 'Customer' }},</p>

    <p>Your scheduled payment <strong>#{{ $schedule->uuid }}</strong> for order <strong>#{{ $schedule->order_id }}</strong>
        has been processed successfully.</p>

    <table width="100%" cellpadding="8" cellspacing="0"
        style="background:#f8f9fa; border-radius:6px; margin:20px 0; border:1px solid #ddd;">
        <tr>
            <td><strong>Amount</strong></td>
            <td>{{ $payment->amount ?? $schedule->instalment_amount }} {{ config('services.clickpay.currency') }}</td>
        </tr>
        <tr>
            <td><strong>Transaction Ref</strong></td>
            <td>{{ data_get($payment, 'txn_code') ?? data_get($payment, 'payment_details.raw.transactionReference') }}</td>
        </tr>
        <tr>
            <td><strong>Date</strong></td>
            <td>{{ now()->toDayDateTimeString() }}</td>
        </tr>
    </table>

    <p>Thank you.</p>
@endsection
