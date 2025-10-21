<div class="card grow mt-4 bg-white shadow-sm rounded-xl">
    <div class="card-header px-6 pt-5 pb-3 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">{{ translate('Schedule Payments') }}</h3>
    </div>

    <div class="card-body p-4 sm:p-6">
        {{-- Assuming $schedulePayments exists and is iterable --}}
        <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-1">
            @foreach ($schedulePayments as $index => $payment)
                @php
                    $status = $payment->payment_status ?? 'pending';
                    $dueDateCarbon = \Carbon\Carbon::parse($payment->due_date);

                    // --- Status Mapping Logic ---
                    $statusClass = match ($status) {
                        'paid' => 'paid',
                        'due' => 'pending', // Use 'pending' class for 'due' status (blue/upcoming style)
                        'pending' => 'upcoming', // Use 'upcoming' class for 'pending' status (blue/upcoming style)
                        'late' => 'late', // Separate class for 'late' status (red style)
                        'failed' => 'failed', // Separate class for 'failed' status (gray/dark style)
                        default => 'upcoming',
                    };

                    // Ribbon text logic based on the status
                    $ribbonText = match ($status) {
                        'paid' => translate('PAID'),
                        'due' => translate('DUE'),
                        'late' => translate('OVERDUE'),
                        'failed' => translate('FAILED'),
                        default => translate('UPCOMING'), // Covers 'pending'
                    };

                    $dueDate = $dueDateCarbon->format('d M Y');
                    $amount = number_format($payment->instalment_amount ?? 0, 2);
                    $installmentNumber = $payment->instalment_number ?? $index + 1;
                    $referenceId = $payment->uuid ?? 'INV-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                @endphp

                <div class="sp-card {{ $statusClass }}">
                    {{-- START: Diagonal Ribbon --}}
                    <div class="sp-ribbon">
                        <span class="sp-ribbon-text">{{ $ribbonText }}</span>
                    </div>
                    {{-- END: Diagonal Ribbon --}}

                    <div class="sp-card-content">
                        <div class="sp-card-top">
                            <div class="sp-left">
                                <div class="installment-number-circle">{{ $installmentNumber }}</div>
                                <div class="sp-title">
                                    <div class="inst-title">{{ translate('Installment') }} {{ $installmentNumber }}
                                    </div>
                                    <div class="inst-amount"><span class="icon-saudi_riyal"></span> {{ $amount }}
                                    </div>
                                </div>
                            </div>

                            <div class="sp-uuid" title="{{ $referenceId }}">{{ $referenceId }}</div>
                        </div>

                        <div class="sp-body">
                            <div class="sp-row">
                                <div class="meta">
                                    @if ($payment->principle_amount)
                                        <span class="meta-badge blue">P:
                                            {{ number_format($payment->principle_amount, 2) }}</span>
                                    @endif
                                    @if ($payment->late_fee && $payment->late_fee > 0)
                                        <span class="meta-badge red">Late:
                                            {{ number_format($payment->late_fee, 2) }}</span>
                                    @endif
                                    @if ($payment->shipping_amount && $payment->shipping_amount > 0)
                                        <span class="meta-badge green">Ship:
                                            {{ number_format($payment->shipping_amount, 2) }}</span>
                                    @endif
                                    @if ($payment->additional_amount && $payment->additional_amount > 0)
                                        <span class="meta-badge gray">Add:
                                            {{ number_format($payment->additional_amount, 2) }}</span>
                                    @endif
                                </div>

                                <div class="method text-xs text-gray-600">
                                    {{ $payment->payment_method ?? 'Bank Transfer' }}
                                </div>
                            </div>

                            <div class="sp-row mt-2">
                                {{-- Use due-late for 'late' status, otherwise standard text-gray-600 --}}
                                <div class="due text-xs {{ $status === 'late' ? 'due-late' : 'text-gray-600' }}">
                                    {{ $dueDate }}</div>

                                <div class="sp-actions">
                                    @if ($status === 'paid')
                                        <span
                                            class="badge badge-sm badge-outline badge-success">{{ translate('Paid') }}</span>
                                    @elseif ($status === 'late' || $status === 'due' || $status === 'failed')
                                        <button
                                            class="btn btn-sm btn-outline btn-warning">{{ translate('Pay Now') }}</button>
                                    @else
                                        {{-- pending --}}
                                        <span
                                            class="badge badge-sm badge-outline badge-primary">{{ translate('Upcoming') }}</span>
                                    @endif
                                </div>
                            </div>

                            @if ($status === 'pending')
                                <div class="sp-row mt-2 items-center">
                                    <div class="inst-sub text-xs text-gray-500">{{ translate('Auto reminder sent') }}
                                    </div>
                                    <a href="#" class="send-reminder ml-3"
                                        data-payment-id="{{ $payment->id }}">{{ translate('Send Reminder') }}</a>
                                </div>
                            @elseif($status === 'paid')
                                <div class="sp-row mt-2 items-center">
                                    <div class="inst-sub text-xs text-gray-500">{{ translate('Paid on') }}
                                        {{ $dueDate }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    /* Card grid & card */
    .sp-card {
        background: #fff;
        border: 1px solid #e6e9ee;
        border-radius: 0.75rem;
        padding: 1.25rem 0.8rem 0.8rem 0.8rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        transition: transform .12s ease, box-shadow .12s ease;
        min-height: 120px;
        position: relative;
        overflow: hidden;
    }

    /* Inner content wrapper to allow for absolute ribbon */
    .sp-card-content {
        position: relative;
        z-index: 20;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .sp-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    /* --- Status-based Styles (Left Accent & Circle) --- */
    .sp-card.paid {
        border-left: 4px solid #10b981;
        /* Green */
    }

    /* 'pending' and 'upcoming' (blue) */
    .sp-card.upcoming,
    .sp-card.pending {
        border-left: 4px solid #3b82f6;
        /* Blue */
    }

    /* 'late' (red) */
    .sp-card.late {
        border-left: 4px solid #dc2626;
        /* Red */
    }

    /* New: 'failed' (dark/gray) */
    .sp-card.failed {
        border-left: 4px solid #4b5563;
        /* Gray/Dark */
    }


    /* --- Diagonal Ribbon (Watermark) Styles --- */
    .sp-ribbon {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 10;
        width: 120px;
        height: 120px;
        overflow: hidden;
    }

    .sp-ribbon-text {
        display: block;
        width: 150px;
        padding: 0.25rem 0;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        position: absolute;
        top: 22px;
        right: -45px;
        transform: rotate(45deg);
        text-align: center;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #fff;
        line-height: 1;
    }

    /* Ribbon Color variations */
    .sp-card.paid .sp-ribbon-text {
        background-color: #10b981;
        /* Success Green */
    }

    .sp-card.pending .sp-ribbon-text,
    .sp-card.upcoming .sp-ribbon-text {
        background-color: #3b82f6;
        /* Primary Blue */
    }

    .sp-card.late .sp-ribbon-text {
        background-color: #dc2626;
        /* Danger Red */
    }

    /* New: 'failed' Ribbon Color */
    .sp-card.failed .sp-ribbon-text {
        background-color: #4b5563;
        /* Dark Gray */
    }

    /* --- Installment Circle Styles --- */
    .installment-number-circle {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 0.95rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .sp-card.paid .installment-number-circle {
        background: #10b981;
    }

    .sp-card.pending .installment-number-circle,
    .sp-card.upcoming .installment-number-circle {
        background: #3b82f6;
    }

    .sp-card.late .installment-number-circle {
        background: #dc2626;
    }

    /* New: 'failed' Circle Color */
    .sp-card.failed .installment-number-circle {
        background: #4b5563;
    }

    /* --- General Layout Styles (Copied from previous) --- */
    .sp-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .sp-left {
        display: flex;
        gap: 0.625rem;
        align-items: center;
        min-width: 0;
    }

    .sp-title {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .inst-title {
        font-weight: 700;
        color: #111827;
        font-size: 0.95rem;
        line-height: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .inst-amount {
        font-weight: 700;
        color: #111827;
        font-size: 0.95rem;
    }

    .sp-uuid {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, "Roboto Mono", "Courier New", monospace;
        font-size: 0.75rem;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 42%;
        text-align: right;
    }

    .sp-body {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }

    .sp-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }

    .meta {
        display: flex;
        gap: 0.375rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .meta-badge {
        font-size: 0.72rem;
        padding: 0.18rem 0.36rem;
        border-radius: 6px;
        font-weight: 700;
        color: #0f172a;
    }

    .meta-badge.blue {
        background: #e0f2fe;
        color: #0369a1;
    }

    .meta-badge.red {
        background: #fee2e2;
        color: #b91c1c;
    }

    .meta-badge.green {
        background: #ecfdf5;
        color: #065f46;
    }

    .meta-badge.gray {
        background: #f3f4f6;
        color: #374151;
    }

    .method {
        min-width: 0;
        color: #6b7280;
        font-size: 0.82rem;
        text-align: right;
    }

    .due {
        font-weight: 600;
        color: #374151;
        font-size: 0.86rem;
    }

    .due-late {
        color: #b91c1c;
        font-weight: 700;
    }

    .sp-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .send-reminder {
        color: #2563eb;
        text-decoration-line: underline;
        text-decoration-style: dashed;
        text-decoration-color: #2563eb;
        font-size: 0.78rem;
        cursor: pointer;
        margin-left: 0.5rem;
    }

    .send-reminder:hover {
        color: #1d4ed8;
    }

    /* small screens: 1 column */
    @media (max-width: 640px) {
        .sp-uuid {
            max-width: 38%;
            font-size: 0.7rem;
        }

        .sp-card {
            min-height: 122px;
            padding: 1rem 0.7rem 0.7rem 0.7rem;
        }
    }

    /* medium screens: 2 columns */
    @media (min-width: 641px) and (max-width: 1024px) {
        .sp-uuid {
            max-width: 40%;
        }
    }
</style>
