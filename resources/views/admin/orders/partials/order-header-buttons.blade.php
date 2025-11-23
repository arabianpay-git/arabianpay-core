@php
    $isDisabled = in_array($order->general_status, ['completed', 'accepted', 'rejected']);
    // try to find local Sanad model for this order
    $orderSanad = \App\Models\Sanad::where('order_id', $order->id)->first();
@endphp

<div class="flex justify-end flex-wrap gap-2">

    {{-- Download Supplier Invoice --}}
    @if (!empty($order->invoice_file))
        <a href="{{ getMediaUrl('storage/' . $order->invoice_file) }}" target="_blank"
            class="btn btn-sm btn-light bg-gray-100 text-gray-800 hover:bg-gray-200">
            <i class="ki-filled ki-download"></i> {{ translate('Download Supplier Invoice') }}
        </a>
    @endif

    {{-- show Get SANAD Detail if local Sanad exists --}}
    @if ($orderSanad && !empty($orderSanad->order_id) && !empty($orderSanad->user_id))
        <button type="button" class="btn btn-sm btn-primary" data-modal-toggle="#sanad_detail_modal"
            onclick="openSanadModal('{{ $order->id }}')">
            <i class="ki-filled ki-eye"></i> {{ translate('Get SANAD Detail') }}
        </button>
    @endif

    {{-- Reject Order Button --}}
    <button class="btn btn-sm btn-outline btn-danger" @if ($isDisabled) disabled @endif
        @if ($order->general_status === 'rejected') id="rejectedBtn"
            @else
                data-modal-toggle="#reject_order" @endif>
        <i class="ki-filled ki-shield-cross"></i>
        @if ($order->general_status === 'rejected')
            {{ translate('Order Already Rejected') }}
        @elseif($order->general_status === 'accepted')
            {{ translate('Cannot Reject Accepted Order') }}
        @else
            {{ translate('Reject Order') }}
        @endif
    </button>

    @if ($order->general_status === 'rejected')
        <script>
            document.getElementById('rejectedBtn')?.addEventListener('click', function() {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ translate('Warning') }}',
                    text: '{{ translate('This order has already been rejected.') }}',
                });
            });
        </script>
    @endif

    @includeWhen(
        !$isDisabled && $order->general_status !== 'rejected',
        'admin.orders.partials.order-reject-modal')

    {{-- Accept Order Button --}}
    <button class="btn btn-sm btn-primary" @if ($isDisabled) disabled @endif
        @if (!$isDisabled) data-modal-toggle="#accept_order" @endif>
        <i class="ki-filled ki-copy-success"></i>
        @if ($order->general_status === 'rejected')
            {{ translate('Cannot Accept Rejected Order') }}
        @elseif($order->general_status === 'accepted')
            {{ translate('Order Already Accepted') }}
        @else
            {{ translate('Accept Order') }}
        @endif
    </button>

    @includeWhen(
        !$isDisabled && $order->general_status !== 'accepted',
        'admin.orders.partials.order-accept-modal')

    {{-- Transfer Request --}}
    <button class="btn btn-sm btn-light" @if ($isDisabled) disabled @endif
        @if (!$isDisabled) data-modal-toggle="#transfer_request" @endif>
        <i class="ki-filled ki-disconnect"></i>
        @if ($order->general_status === 'rejected')
            {{ translate('Cannot Transfer Rejected Order') }}
        @elseif($order->general_status === 'accepted')
            {{ translate('Cannot Transfer Accepted Order') }}
        @else
            {{ translate('Transfer Request') }}
        @endif
    </button>

    @includeWhen(
        !$isDisabled && !in_array($order->general_status, ['accepted', 'rejected']),
        'admin.components.transfer-request',
        [
            'employees' => getEmployees(),
            'model_type' => 'App\Models\Order',
            'model_id' => $order->id,
        ]
    )
</div>

{{-- Include SANAD detail modal partial (it will be invoked by JS openSanadModal(id)) --}}
@includeWhen(true, 'admin.orders.partials.sanad-detail-modal', ['sanad' => $orderSanad])
