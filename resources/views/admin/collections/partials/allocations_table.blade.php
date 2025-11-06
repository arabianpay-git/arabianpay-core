<tbody>
    @php $counter = 1; @endphp
    @foreach ($groupedPayments as $orderId => $payments)
        @php
            $rowspan = count($payments);
            $firstPayment = $payments->first();
            $customerName =
                $firstPayment->order->customer->first_name . ' ' . $firstPayment->order->customer->last_name;
            $customerBusiness = $firstPayment->order->customer->business_name ?? '-';
            $customerId = $firstPayment->order->customer->id ?? '-';
            $tracking = $firstPayment->order->tracking ?? '- No Tracking Code -';
            $orderId = $firstPayment->order->id ?? '-';
        @endphp

        @foreach ($payments as $index => $payment)
            <tr>
                @if ($index === 0)
                    <td class="text-center" rowspan="{{ $rowspan }}">
                        {{ $counter++ }}
                    </td>
                    <td rowspan="{{ $rowspan }}">
                        <div class="whitespace-nowrap">
                            <a href="{{ route('customerProfile', ['id' => $customerId]) }}"
                                class="underline">{{ $customerName }}</a>
                            <br>
                            <small class="text-gray-500">— {{ $customerBusiness }}</small>
                        </div>
                    </td>
                    <td rowspan="{{ $rowspan }}">
                        <a href="{{ route('orders.details', ['id' => $orderId]) }}"
                            class="underline">{{ $tracking }}</a>
                    </td>
                @endif

                <td>
                    @php
                        $received = $payment->deducted_amount ?? 0;
                        $allocated = $payment->instalment_amount ?? 0;
                        $receivedRatio = $allocated > 0 ? $received / $allocated : 0;
                        $textClass = match (true) {
                            $receivedRatio >= 0.9 => 'text-success',
                            $receivedRatio >= 0.5 => 'text-warning',
                            default => 'text-danger',
                        };
                    @endphp
                    <span class="{{ $textClass }}">
                        <span class="icon-saudi_riyal"></span>{{ number_format($received, 2) }}
                    </span>
                </td>
                <td>
                    <span
                        class="badge badge-sm badge-outline badge-primary">{{ $payment->payment_method ?? 'N/A' }}</span>
                </td>
                <td><span class="icon-saudi_riyal"></span>{{ number_format($payment->instalment_amount ?? 0, 2) }}</td>
                <td><span class="icon-saudi_riyal"></span>{{ number_format($payment->late_fee ?? 0, 2) }}</td>
                <td>
                    <div class="flex gap-1">
                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                            href="{{ route('collections.installmentDetails', ['id' => $payment->order->id]) }}">
                            <i class="ki-filled ki-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
    @endforeach
</tbody>
