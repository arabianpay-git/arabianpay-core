{{-- Refund Request Card --}}
@if ($refundRequest)
    <div class="card grow shadow-lg rounded-lg bg-white mb-4">
        <div class="card-header flex justify-between items-center p-4 border-b">
            <h3 class="card-title font-semibold text-xl text-gray-800">
                {{ translate('Refund Request') }}
            </h3>
        </div>
        <div class="card-body pt-4 pb-3">
            @if (in_array($order->general_status, ['rejected', 'completed']))
                <div class="text-center py-6 text-red-600 font-medium">
                    @if ($order->general_status === 'rejected')
                        {{ translate('This order has been rejected.') }}
                    @else
                        {{ translate('This order has been completed.') }}
                    @endif
                </div>
            @else
                <form action="{{ route('refund-requests.update-status', $order->id) }}" method="POST"
                    id="statusForm-{{ $order->id }}">
                    @csrf
                    @method('PATCH')
                    <select name="refund_status" class="select"
                        onchange="document.getElementById('statusForm-{{ $order->id }}').submit()">
                        <option value="pending" {{ $order->refund_status == 'pending' ? 'selected' : '' }}>
                            {{ translate('Pending') }}</option>
                        <option value="approved" {{ $order->refund_status == 'approved' ? 'selected' : '' }}>
                            {{ translate('Approved') }}</option>
                        <option value="rejected" {{ $order->refund_status == 'rejected' ? 'selected' : '' }}>
                            {{ translate('Rejected') }}</option>
                    </select>
                </form>
            @endif
        </div>
    </div>
@endif

{{-- Order Status Card --}}
<div class="card grow shadow-lg rounded-lg bg-white">
    <div class="card-header flex justify-between items-center p-4 border-b">
        <h3 class="card-title font-semibold text-xl text-gray-800">
            {{ translate('Order Status') }}
        </h3>

        {{-- Show download shipping label button only if order not rejected or completed --}}
        @if (!in_array($order->general_status, ['rejected', 'completed']))
            <a href="{{ route('order.downloadShippingLabel', $order->id) }}"
                class="btn btn-light btn-sm bg-gray-100 text-gray-800 hover:bg-gray-200">
                <i class="ki-filled ki-exit-down"></i> {{ translate('Print Shipping Label') }}
            </a>
        @endif
    </div>

    <div class="card-body pt-4 pb-3">
        @if (in_array($order->general_status, ['rejected', 'completed']))
            <div class="text-center py-6 text-red-600 font-medium">
                @if ($order->general_status === 'rejected')
                    {{ translate('You have already rejected this order.') }}
                @else
                    {{ translate('This order has been completed.') }}
                @endif
            </div>
        @else
            <form action="{{ route('order.updateStatus', $order->id) }}" method="POST"
                class="space-y-4 update-status-form">
                @csrf
                @method('PUT')

                <!-- Delivery Status -->
                <div>
                    <label for="delivery_status"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Delivery Status') }}</label>
                    <select name="delivery_status" id="delivery_status" class="select">
                        <option value="">{{ translate('-- Select Status --') }}</option>
                        <option value="pending" {{ $order->delivery_status === 'pending' ? 'selected' : '' }}>
                            {{ translate('Pending') }}</option>
                        <option value="shipped" {{ $order->delivery_status === 'shipped' ? 'selected' : '' }}>
                            {{ translate('Shipped') }}</option>
                        <option value="delivered" {{ $order->delivery_status === 'delivered' ? 'selected' : '' }}>
                            {{ translate('Delivered') }}</option>
                        <option value="returned" {{ $order->delivery_status === 'returned' ? 'selected' : '' }}>
                            {{ translate('Returned') }}</option>
                    </select>
                </div>

                <!-- General Status -->
                <div class="w-full mt-4">
                    <label for="general_status"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ translate('General Status') }}</label>
                    <select name="general_status" id="general_status" class="select">
                        <option value="">{{ translate('-- Select Status --') }}</option>
                        <option value="processing" {{ $order->general_status === 'processing' ? 'selected' : '' }}>
                            {{ translate('Processing') }}</option>
                        <option value="cancelled" {{ $order->general_status === 'cancelled' ? 'selected' : '' }}>
                            {{ translate('Cancelled') }}</option>
                        <option value="failed" {{ $order->general_status === 'failed' ? 'selected' : '' }}>
                            {{ translate('Failed') }}</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-2.5">
                    <button type="submit" class="btn btn-sm btn-primary update-status-btn">
                        {{ translate('Update Status') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

{{-- JS to prevent double submit --}}
<script>
    document.querySelectorAll('.update-status-form').forEach(form => {
        form.addEventListener('submit', function() {
            const button = form.querySelector('.update-status-btn');
            if (button) {
                button.disabled = true;
                button.innerHTML =
                    `<span class="spinner-border spinner-border-sm mr-2"></span> {{ translate('Processing...') }}`;
            }
        });
    });

    window.addEventListener('pageshow', function() {
        document.querySelectorAll('.update-status-btn').forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = `{{ translate('Update Status') }}`;
        });
    });
</script>
