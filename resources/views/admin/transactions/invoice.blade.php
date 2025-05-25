<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Invoice #{{ $transaction->order->invoice_number }}</title>
</head>

<body
    style="color:#2d3436; background:#fff; font-family: 'Helvetica Neue', Arial, sans-serif; font-size:12px; line-height:1.4; padding:5mm; margin:0;">

    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:1.2rem; page-break-before:avoid;">
        <tr>
            <td align="center">
                <img src="{{ public_path('assets/media/images/logo.png') }}" alt="Company Logo"
                    style="height:50px; max-width:200px; object-fit:contain;" />
            </td>
        </tr>
    </table>

    <!-- Header Row -->
    <table width="100%" cellspacing="0" cellpadding="0"
        style="border-bottom: 2px solid #e0e0e0; padding: 1rem 0; page-break-inside: avoid; margin-bottom:1rem;">
        <tr>
            <td style="font-size:0.9em; vertical-align:top; width:50%;">
                <div style="font-weight:700; color:#2c3e50; font-size:1.2em; margin-bottom:0.4rem;">
                    {{ config('app.name') }}</div>
                <div>{{ config('company.address') }}</div>
                <div style="margin-top:0.3rem;">
                    <span>Phone: {{ config('company.phone') }}</span><br />
                    <span>Email: {{ config('company.email') }}</span>
                </div>
                <div style="margin-top:0.3rem;">VAT No: {{ config('company.vat') }}</div>
            </td>
            <td style="text-align:right; vertical-align:top; width:50%;">
                <div style="margin-bottom:0.4rem; font-weight:600;">Invoice #{{ $transaction->order->invoice_number }}
                </div>
                <div style="margin-bottom:0.3rem;">
                    <span style="font-weight:600;">Issued:</span>
                    <span>{{ $transaction->created_at->format('d M Y') }}</span>
                </div>
                <div>
                    <span style="font-weight:600;">Due:</span>
                    <span>{{ optional($transaction->schedulePayments->last())->due_date->format('d M Y') ?? '-' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Parties Section Title -->
    <table width="100%" cellspacing="0" cellpadding="0"
        style="margin-top:20px; margin-bottom:15px; border-bottom: 2px solid #3498db;">
        <tr>
            <td style="font-size:14px; font-weight:600; color:#2c3e50; padding-bottom:4px;">Parties</td>
        </tr>
    </table>

    <!-- Parties Section -->
    <table width="100%" cellspacing="0" cellpadding="12"
        style="background:#f8f9fa; border-radius:4px; margin-bottom:20px; page-break-inside: avoid;">
        <tr>
            <td style="width:49%; vertical-align:top; background:#f8f9fa;">
                <h3 style="margin:0 0 8px 0; font-weight:700; color:#2c3e50;">Seller</h3>
                <div>{{ $transaction->seller->first_name ?? '—' }} {{ $transaction->seller->last_name ?? '—' }}</div>
                <div>{{ $transaction->seller->business_name ?? '—' }}</div>
                <div>{{ $transaction->seller->email ?? '—' }}</div>
                <div>{{ $transaction->seller->city->name ?? '—' }}, {{ $transaction->seller->country->name ?? '—' }}
                </div>
            </td>

            <!-- Spacer with white background -->
            <td style="width:2%; background:#fff;"></td>

            <td style="width:49%; vertical-align:top; background:#f8f9fa;">
                <h3 style="margin:0 0 8px 0; font-weight:700; color:#2c3e50;">Buyer</h3>
                <div>{{ $transaction->user->first_name ?? '—' }} {{ $transaction->user->last_name ?? '—' }}</div>
                <div>{{ $transaction->user->business_name ?? '—' }}</div>
                <div>{{ $transaction->user->email ?? '—' }}</div>
                <div>{{ $transaction->user->city->name ?? '—' }}, {{ $transaction->user->country->name ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <!-- Transaction Summary Title -->
    <table width="100%" cellspacing="0" cellpadding="0"
        style="margin-top:20px; margin-bottom:15px; border-bottom: 2px solid #3498db;">
        <tr>
            <td style="font-size:14px; font-weight:600; color:#2c3e50; padding-bottom:4px;">Transaction Summary</td>
        </tr>
    </table>

    <!-- Transaction Summary Table -->
    <table width="100%" cellspacing="0" cellpadding="10"
        style="border-collapse: collapse; margin-bottom: 20px; page-break-inside:auto;">
        <thead>
            <tr style="background:#2c3e50; color:#fff; font-weight:600;">
                <th style="width: 25%; border-bottom:1px solid #e0e0e0;">Reference</th>
                <th style="width: 20%; border-bottom:1px solid #e0e0e0;">Loan Amount</th>
                <th style="width: 20%; border-bottom:1px solid #e0e0e0;">Collected</th>
                <th style="width: 20%; border-bottom:1px solid #e0e0e0;">Remaining</th>
                <th style="width: 15%; border-bottom:1px solid #e0e0e0;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border-bottom:1px solid #e0e0e0;">{{ $transaction->refrence_payment }}</td>
                <td style="border-bottom:1px solid #e0e0e0;">{{ number_format($transaction->loan_amount, 2) }} SAR</td>
                <td style="border-bottom:1px solid #e0e0e0;">{{ number_format($transaction->collected, 2) }} SAR</td>
                <td style="border-bottom:1px solid #e0e0e0;">
                    {{ number_format($transaction->remaining_credit_limit, 2) }} SAR</td>
                <td style="border-bottom:1px solid #e0e0e0;"><span class="badge {{ $transaction->payment_status }}"
                        style="padding:3px 8px; border-radius:4px; background:#3498db; color:#fff; text-transform:capitalize;">{{ ucfirst($transaction->payment_status) }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Scheduled Payments Title -->
    <table width="100%" cellspacing="0" cellpadding="0"
        style="margin-top:20px; margin-bottom:15px; border-bottom: 2px solid #3498db; page-break-inside: avoid;">
        <tr>
            <td style="font-size:14px; font-weight:600; color:#2c3e50; padding-bottom:4px;">Scheduled Payments</td>
        </tr>
    </table>

    <!-- Scheduled Payments Table -->
    <table width="100%" cellspacing="0" cellpadding="10" style="border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background:#2c3e50; color:#fff; font-weight:600;">
                <th style="width: 15%; border-bottom:1px solid #e0e0e0;">#</th>
                <th style="width: 25%; border-bottom:1px solid #e0e0e0;">Due Date</th>
                <th style="width: 30%; border-bottom:1px solid #e0e0e0;">Amount</th>
                <th style="width: 30%; border-bottom:1px solid #e0e0e0;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaction->schedulePayments as $sp)
                <tr>
                    <td style="border-bottom:1px solid #e0e0e0;">{{ $sp->instalment_number }}</td>
                    <td style="border-bottom:1px solid #e0e0e0;">{{ $sp->due_date->format('d M Y') }}</td>
                    <td style="border-bottom:1px solid #e0e0e0;">{{ number_format($sp->instalment_amount, 2) }} SAR</td>
                    <td style="border-bottom:1px solid #e0e0e0;"><span class="badge {{ $sp->payment_status }}"
                            style="padding:3px 8px; border-radius:4px; background:#3498db; color:#fff; text-transform:capitalize;">{{ ucfirst($sp->payment_status) }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table width="100%" cellspacing="0" cellpadding="10" style="margin-top: 20px; page-break-inside: avoid;">
        <tr>
            <td style="font-weight:600; width: 80%;">Total Paid:</td>
            <td style="text-align:right;">
                {{ number_format($transaction->schedulePayments->sum('instalment_amount'), 2) }} SAR</td>
        </tr>
    </table>

    <!-- Footer -->
    <table width="100%" cellspacing="0" cellpadding="10"
        style="border-top:1px solid #e0e0e0; 
            position:absolute; 
            bottom:15mm; 
            left:15mm; 
            right:15mm; 
            text-align:center; 
            font-size:11px; 
            color:#7f8c8d; 
            font-family: Arial, sans-serif;">
        <tr>
            <td>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </td>
        </tr>
    </table>
