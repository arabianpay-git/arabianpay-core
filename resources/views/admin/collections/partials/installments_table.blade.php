<div class="scrollable-x-auto">
    <table class="table table-auto table-border">
        <thead>
            <tr>
                <th class="text-center w-[60px]">{{ translate('No') }}</th>
                <th>{{ translate('Merchant') }}</th>
                <th>{{ translate('Order') }}</th>
                <th>{{ translate('Due Date') }}</th>
                <th>{{ translate('Amount') }}</th>
                <th>{{ translate('Status') }}</th>
                <th>{{ translate('DPD') }}</th>
                <th>{{ translate('Channel') }}</th>
                <th>{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $orderIndex => $order)
                @foreach ($order->schedulePayments as $index => $payment)
                    <tr>
                        @if ($index === 0)
                            <td class="text-center" rowspan="{{ $order->schedulePayments->count() }}">
                                {{ $orderIndex + 1 }}
                            </td>
                            <td rowspan="{{ $order->schedulePayments->count() }}">
                                <div class="whitespace-nowrap">
                                    <a href="{{ route('customerProfile', ['id' => $order->user_id]) }}"
                                        class="underline">{{ $order->user?->business_name ?? $order->user?->first_name }}</a>
                                    <br>
                                    <small class="text-gray-500">—
                                        {{ $order->user?->business_name ?? '—' }}</small>
                                </div>
                            </td>
                            <td rowspan="{{ $order->schedulePayments->count() }}">
                                <a href="{{ route('orders.details', ['id' => $order->id]) }}"
                                    class="underline">{{ $order->tracking ?? '- No Tracking Code -' }}</a>
                            </td>
                        @endif

                        <td>{{ \Carbon\Carbon::parse($payment->due_date)->format('d M Y') }}</td>
                        <td>
                            <span class="icon-saudi_riyal"></span>
                            {{ number_format($payment->instalment_amount, 2) }}
                        </td>
                        @php
                            $late_days = 0;
                            if ($payment->payment_status !== 'paid' && $payment->due_date->lt(now())) {
                                $difference = now()
                                    ->startOfDay()
                                    ->diffInDays($payment->due_date->startOfDay());
                                $late_days = abs($difference);
                            }

                            $badge_class = match ($payment->payment_status) {
                                'paid' => 'badge-success',
                                'pending' => 'badge-info',
                                'due' => 'badge-primary',
                                'late' => 'badge-danger',
                                'failed' => 'badge-warning',
                                default => 'badge-secondary',
                            };
                        @endphp

                        <td>
                            <span class="badge badge-sm badge-outline {{ $badge_class }}">
                                {{ ucfirst($payment->payment_status) }}
                            </span>
                        </td>

                        <td>{{ $late_days }} {{ $late_days == 1 ? 'Day' : 'Days' }}</td>
                        <td>
                            <span class="badge badge-sm badge-outline badge-primary">
                                {{ ucfirst($order->payment_type ?? 'N/A') }}
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                    href="{{ route('collections.installmentDetails', ['id' => $order->id]) }}">
                                    <i class="ki-filled ki-eye"> </i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-4">
        @include('layouts.includes.table-pagination', ['paginator' => $orders])
    </div>
</div>
