<div class="card grow shadow-lg rounded-lg bg-white mt-5">
    <div class="card-header flex justify-between items-center p-4 border-b">
        <h3 class="card-title font-semibold text-xl text-gray-800">
            {{ translate('Payment Information') }}
        </h3>
    </div>
    <div class="card-body pt-4 pb-3 px-4 sm:px-6 space-y-3 text-sm text-gray-700">
        @php
            $transaction = $order->transactions->first();
        @endphp

        @if ($transaction)
            <div class="flex flex-col gap-3">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Payment Method') }}</span>
                    <span class="text-gray-800">{{ $transaction->resource ?? translate('N/A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Payment Reference') }}</span>
                    <span class="text-gray-800">{{ $transaction->refrence_payment ?? translate('N/A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Transaction ID') }}</span>
                    <span class="text-gray-800">{{ $transaction->uuid ?? translate('N/A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Order Date') }}</span>
                    <span class="text-gray-800">{{ $order->created_at->format('d M, Y') }}</span>
                </div>

                <div class="flex justify-between flex-wrap items-center gap-2 mb-2">
                    <span class="font-medium text-gray-600">{{ translate('Payment Status') }}</span>
                    <div class="flex flex-wrap gap-1">
                        @forelse ($transaction->schedulePayments as $payment)
                            @php
                                $statusValue = $payment->payment_status instanceof \BackedEnum
                                    ? $payment->payment_status->value
                                    : (string) ($payment->payment_status ?? 'pending');
                                $paymentStatusClasses = [
                                    'pending' => 'badge badge-sm badge-outline badge-info',
                                    'due' => 'badge badge-sm badge-outline badge-warning',
                                    'late' => 'badge badge-sm badge-outline badge-error',
                                    'paid' => 'badge badge-sm badge-outline badge-success',
                                    'failed' => 'badge badge-sm badge-outline badge-danger',
                                    'unpaid' => 'badge badge-sm badge-outline badge-secondary',
                                    'current' => 'badge badge-sm badge-outline badge-primary',
                                    'partially_paid' => 'badge badge-sm badge-outline badge-warning',
                                    'cancelled' => 'badge badge-sm badge-outline badge-secondary',
                                    'canceled' => 'badge badge-sm badge-outline badge-secondary',
                                ];
                                $paymentStatusClass =
                                    $paymentStatusClasses[$statusValue] ??
                                    'badge badge-sm badge-outline';
                                $statusLabel = ucfirst(str_replace('_', ' ', $statusValue));
                            @endphp
                            <span class="{{ $paymentStatusClass }}">
                                {{ translate($statusLabel) }}
                            </span>
                        @empty
                            <span class="badge badge-sm badge-outline badge-secondary">No Payments</span>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium text-gray-600">{{ translate('Settlement Status') }}</span>
                    @php
                        $settlementStatusClasses = [
                            'pending' => 'badge badge-sm badge-outline badge-info',
                            'settled' => 'badge badge-sm badge-outline badge-success',
                            'failed' => 'badge badge-sm badge-outline badge-danger',
                        ];
                        $settlementStatusClass =
                            $settlementStatusClasses[$transaction->settlement_status ?? 'pending'] ??
                            'badge badge-sm badge-outline';
                    @endphp
                    <span class="{{ $settlementStatusClass }}">
                        {{ ucfirst($transaction->settlement_status ?? 'N/A') }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Collected') }}</span>
                    <span class="text-gray-800">{{ number_format($transaction->collected ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Retrieved') }}</span>
                    <span class="text-gray-800">{{ number_format($transaction->retrieved ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Canceled') }}</span>
                    <span class="text-gray-800">{{ number_format($transaction->canceled ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Loan Amount') }}</span>
                    <span class="text-gray-800">{{ number_format($transaction->loan_amount ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Loan Term') }}</span>
                    <span class="text-gray-800">{{ $transaction->loan_term ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Loan Start') }}</span>
                    <span class="text-gray-800">
                        {{ $transaction->loan_start_date ? \Carbon\Carbon::parse($transaction->loan_start_date)->format('d M, Y') : '-' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">{{ translate('Loan End') }}</span>
                    <span class="text-gray-800">
                        {{ $transaction->loan_end_date ? \Carbon\Carbon::parse($transaction->loan_end_date)->format('d M, Y') : '-' }}
                    </span>
                </div>
            </div>
        @else
            <div class="text-gray-500 text-sm">{{ translate('No transaction information available.') }}</div>
        @endif
    </div>
</div>
