<style>
    .btn-info {
        background-color: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
    }

    .btn-info:hover {
        background-color: #2563eb;
        border-color: #2563eb;
    }

    .btn-warning {
        background-color: #facc15;
        color: #111827;
        border-color: #facc15;
    }

    .btn-warning:hover {
        background-color: #eab308;
        border-color: #eab308;
    }
</style>
<div class="flex justify-end flex-wrap gap-2">
    {{-- Add Note --}}
    <button class="btn btn-sm btn-info" data-modal-toggle="#add_note_modal">
        <i class="ki-filled ki-pencil"></i> {{ translate('Add Note') }}
    </button>

    {{-- Promise to Pay --}}
    <button class="btn btn-sm btn-warning" data-modal-toggle="#promise_to_pay_modal">
        <i class="ki-filled ki-watch"></i> {{ translate('Promise to Pay') }}
    </button>

    <button class="btn btn-sm btn-success" data-modal-toggle="#partial_payment_modal">
        <i class="ki-filled ki-chart-pie-too"></i> {{ translate('Partial Payment') }}
    </button>

    {{-- Transfer to Legal Department --}}
    <button class="btn btn-sm btn-danger" data-modal-toggle="#transfer_legal_modal">
        <i class="ki-filled ki-shield-cross"></i> {{ translate('Transfer to Legal Department') }}
    </button>

    {{-- Send Reminder --}}
    <button class="btn btn-sm btn-success" data-modal-toggle="#send_reminder_modal">
        <i class="ki-filled ki-notification-on"></i> {{ translate('Send Reminder') }}
    </button>
</div>


{{-- Add Note Modal --}}
@include('admin.collections.components.header-modals.note')

{{-- Promise to Pay Modal --}}
@include('admin.collections.components.header-modals.promise')

@include('admin.collections.components.header-modals.partial-payment')

{{-- Transfer to Legal Modal --}}
@include('admin.collections.components.header-modals.transfer-to-legal')

{{-- Send Reminder Modal --}}
@include('admin.collections.components.header-modals.send-reminder')
