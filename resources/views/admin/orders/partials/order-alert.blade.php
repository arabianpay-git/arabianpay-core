@if ($order->general_status === 'completed')
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-2 rounded mt-4 mb-4">
        <p class="font-medium">{{ translate('Success!') }}</p>
        <p class="text-sm">
            {{ translate('This order has been successfully completed. No further action is required.') }}
        </p>
    </div>
@endif


@if ($order->general_status === 'cancelled')
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-2 rounded mt-4 mb-4">
        <p class="font-medium">{{ translate('Cancelled!') }}</p>
        <p class="text-sm">
            {{ translate('This order has been cancelled. No further action can be taken.') }}
        </p>
    </div>
@endif

@if ($order->delivery_status === 'returned')
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-2 rounded mt-4 mb-4">
        <p class="font-medium">{{ translate('Returned!') }}</p>
        <p class="text-sm">
            {{ translate('This order has been returned.') }}
        </p>
    </div>
@endif

@if ($order->general_status === 'rejected')
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-2 rounded mt-4 mb-4">
        <p class="font-medium">{{ translate('Rejected!') }}</p>
        <p class="text-sm">
            {{ translate('This order has been rejected.') }}
        </p>
    </div>
@endif
