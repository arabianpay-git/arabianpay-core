<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Invoice #{{ $transaction->uuid }}</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --light: #f8f9fa;
            --dark: #2d3436;
            --border: #e0e0e0;
            --font-sans: 'Helvetica Neue', Arial, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-sans);
        }

        body {
            color: var(--dark);
            background: #fff;
            padding: 15mm;
            font-size: 12px;
            line-height: 1.4;
            position: relative;
        }

        /* Logo Fix */
        .logo-container {
            text-align: center;
            margin-bottom: 1.2rem;
            page-break-before: avoid;
        }

        .logo {
            height: 50px;
            max-width: 200px;
            object-fit: contain;
        }

        /* Header Fix */
        .header-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 1rem 0;
            border-bottom: 2px solid var(--border);
            page-break-inside: avoid;
        }

        .company-info {
            font-size: 0.9em;
        }

        .company-name {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.4rem;
            font-size: 1.2em;
        }

        .invoice-meta {
            text-align: right;
        }

        /* Parties Fix */
        .contacts {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .party-block {
            padding: 12px;
            background: var(--light);
            border-radius: 4px;
            min-height: 150px;
        }

        /* Table Fix */
        .section-title {
            font-size: 14px;
            color: var(--primary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 4px;
            margin: 20px 0 15px;
            page-break-after: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            page-break-inside: auto;
        }

        th, td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        thead th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        /* Footer Fix */
        .invoice-footer {
            position: absolute;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
            text-align: center;
            padding-top: 10px;
            font-size: 11px;
            color: #7f8c8d;
            border-top: 1px solid var(--border);
        }

        @media print {
            body {
                padding: 0;
                padding-bottom: 30mm;
            }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Header Section -->
        <div class="logo-container">
            <img src="{{ public_path('assets/media/images/logo.png') }}" alt="Company Logo" class="logo" />
        </div>

        <div class="header-row">
            <div class="company-info">
                <div class="company-name">{{ config('app.name') }}</div>
                <div class="company-address">{{ config('company.address') }}</div>
                <div class="company-contact">
                    <span>Phone: {{ config('company.phone') }}</span><br>
                    <span>Email: {{ config('company.email') }}</span>
                </div>
                <div class="company-vat">VAT No: {{ config('company.vat') }}</div>
            </div>

            <div class="invoice-meta">
                <div class="invoice-number">Invoice #{{ $transaction->uuid }}</div>
                <div class="invoice-date">
                    <span class="meta-label">Issued:</span>
                    <span class="meta-value">{{ $transaction->created_at->format('d M Y') }}</span>
                </div>
                <div class="invoice-due-date">
                    <span class="meta-label">Due:</span>
                    <span class="meta-value">{{ optional($transaction->schedulePayments->last())->due_date->format('d M Y') ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Parties Section -->
        <div class="section-title">Parties</div>
        <div class="contacts">
            <div class="party-block">
                <h3>Seller</h3>
                <div>{{ $transaction->seller->first_name ?? '—' }} {{ $transaction->seller->last_name ?? '—' }}</div>
                <div>{{ $transaction->seller->business_name ?? '—' }}</div>
                <div>{{ $transaction->seller->email ?? '—' }}</div>
                <div>{{ $transaction->seller->city->name ?? '—' }}, {{ $transaction->seller->country->name ?? '—' }}</div>
            </div>

            <div class="party-block">
                <h3>Buyer</h3>
                <div>{{ $transaction->user->first_name ?? '—' }} {{ $transaction->user->last_name ?? '—' }}</div>
                <div>{{ $transaction->user->business_name ?? '—' }}</div>
                <div>{{ $transaction->user->email ?? '—' }}</div>
                <div>{{ $transaction->user->city->name ?? '—' }}, {{ $transaction->user->country->name ?? '—' }}</div>
            </div>
        </div>

        <!-- Transaction Summary -->
        <div class="section-title">Transaction Summary</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 25%">Reference</th>
                    <th style="width: 20%">Loan Amount</th>
                    <th style="width: 20%">Collected</th>
                    <th style="width: 20%">Remaining</th>
                    <th style="width: 15%">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $transaction->refrence_payment }}</td>
                    <td>{{ number_format($transaction->loan_amount,2) }} SAR</td>
                    <td>{{ number_format($transaction->collected,2) }} SAR</td>
                    <td>{{ number_format($transaction->remaining_credit_limit,2) }} SAR</td>
                    <td><span class="badge {{ $transaction->payment_status }}">{{ ucfirst($transaction->payment_status) }}</span></td>
                </tr>
            </tbody>
        </table>

        <!-- Scheduled Payments -->
        <div style="page-break-inside: avoid;">
            <div class="section-title">Scheduled Payments</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%">#</th>
                        <th style="width: 25%">Due Date</th>
                        <th style="width: 30%">Amount</th>
                        <th style="width: 30%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->schedulePayments as $sp)
                    <tr>
                        <td>{{ $sp->instalment_number }}</td>
                        <td>{{ $sp->due_date->format('d M Y') }}</td>
                        <td>{{ number_format($sp->instalment_amount,2) }} SAR</td>
                        <td><span class="badge {{ $sp->payment_status }}">{{ ucfirst($sp->payment_status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="label">Total Paid:</div>
            <div class="value">{{ number_format($transaction->schedulePayments->sum('instalment_amount'),2) }} SAR</div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            Thank you for your business. For support, email {{ config('company.email') }}
        </div>
    </div>
</body>
</html>