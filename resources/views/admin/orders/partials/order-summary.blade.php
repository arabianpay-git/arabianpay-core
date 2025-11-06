<div
    class="card grow shadow-lg rounded-lg bg-white {{ request()->routeIs('collections.installmentDetails') ? '' : 'mt-5' }}">
    <div class="card-header flex justify-between items-center p-4 border-b">
        <h3 class="card-title font-semibold text-xl text-gray-800">
            {{ translate('Order Summary') }}
        </h3>
        @if (!request()->routeIs('collections.installmentDetails'))
            <button class="btn btn-light btn-sm bg-gray-100 text-gray-800 hover:bg-gray-200">
                <a href="{{ route('order.downloadInvoice', $order->id) }}">
                    <i class="ki-filled ki-exit-down"> </i> {{ translate('Download Invoice') }}
                </a>
            </button>
        @endif
    </div>
    <div class="card-body pt-4 pb-3">
        <table class="table-auto w-full text-sm text-gray-700">
            <tbody>
                <tr>
                    <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">{{ translate('Quantity') }}</td>
                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">{{ number_format($totalQuantity) }}</td>
                </tr>
                <tr>
                    <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">{{ translate('Sub Amount') }}</td>
                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                        <span class="icon-saudi_riyal"></span> {{ number_format($subTotal, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">{{ translate('Discount') }}</td>
                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                        <span class="icon-saudi_riyal"></span>
                        {{ number_format($order->coupon_discount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">{{ translate('Shipping Fee') }}</td>
                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                        @if ($totalShippingFee)
                            <span class="icon-saudi_riyal"></span>
                            {{ number_format($totalShippingFee, 2) }}
                        @else
                            {{ translate('Free delivery') }}
                        @endif
                    </td>
                </tr>
                <!-- Service Fees 1% of subTotal -->
                @php
                    $serviceFee = $subTotal * 0.01;
                @endphp
                <tr>
                    <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">{{ translate('Service Fees') }} (1%)</td>
                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                        <span class="icon-saudi_riyal"></span> {{ number_format($serviceFee, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">{{ translate('Total Amount') }}</td>
                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                        <span class="icon-saudi_riyal"></span>
                        {{ number_format($subTotal + $serviceFee + ($totalShippingFee ?? 0) - $order->coupon_discount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
