@php
    use App\Models\Promise;
    use App\Models\SchedulePayment;

    // Fetch all promises with relationships
    $promises = Promise::with(['employee', 'schedulePayment'])
        ->where('user_id', $order->user_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Fetch unpaid installments for dropdown
    $unpaidPayments = SchedulePayment::where('user_id', $order->user_id)
        ->where('payment_status', '!=', 'paid')
        ->orderBy('due_date', 'asc')
        ->get();
@endphp

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .badge-info {
            background-color: #3b82f6;
            color: #fff;
        }

        .badge-warning {
            background-color: #facc15;
            color: #111827;
        }

        .badge-danger {
            background-color: #ef4444;
            color: #fff;
        }
    </style>
@endpush

<div class="card grow shadow-lg rounded-lg bg-white mt-5">
    <div class="card-header flex justify-between items-center p-4 border-b">
        <h3 class="card-title font-semibold text-xl text-gray-800">{{ translate('Promise to Pay') }}</h3>
        <button class="btn btn-warning btn-sm" data-modal-toggle="#add_promise_modal">
            {{ translate('Add Promise') }}
        </button>
    </div>

    <div class="card-body pt-4 pb-3 px-4 sm:px-6 space-y-3 text-sm text-gray-700">
        @forelse($promises as $promise)
            <div class="flex justify-between items-center p-3 border rounded gap-2">
                <div>
                    <p>
                        <strong>{{ translate('Installment:') }}</strong>
                        SAR {{ number_format($promise->schedulePayment->instalment_amount ?? 0, 2) }}
                    </p>
                    <p><strong>{{ translate('Promise Date:') }}</strong> {{ $promise->promise_date }}</p>
                    <p><strong>{{ translate('Contact Method:') }}</strong> {{ ucfirst($promise->method) }}</p>
                    <small class="text-gray-500">
                        {{ $promise->created_at->format('d M, Y H:i') }} by
                        {{ $promise->employee ? $promise->employee->first_name . ' ' . $promise->employee->last_name : '-' }}
                    </small>
                </div>

                <div class="flex gap-2">
                    {{-- Edit --}}
                    <button class="badge badge-info" data-modal-toggle="#edit_promise_{{ $promise->id }}">
                        <i class="ki-filled ki-notepad-edit"></i>
                    </button>

                    {{-- Delete --}}
                    <form action="{{ route('promises.destroy', $promise->id) }}" method="POST"
                        class="delete-promise-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="badge badge-danger">
                            <i class="ki-filled ki-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Edit Modal --}}
            <div class="modal" data-modal="true" id="edit_promise_{{ $promise->id }}">
                <div class="modal-content max-w-[600px] top-[5%]">
                    <div class="modal-header py-4 px-5">
                        <h5 class="modal-title">{{ translate('Edit Promise') }}</h5>
                        <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                            data-modal-dismiss="true">
                            <i class="ki-filled ki-cross"></i>
                        </button>
                    </div>
                    <div class="modal-body p-5">
                        <form action="{{ route('promises.update', $promise->id) }}" method="POST"
                            class="edit-promise-form">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">{{ translate('Installment') }}</label>
                                <select name="schedule_payment_id" class="select" required disabled>
                                    @foreach ($unpaidPayments as $payment)
                                        <option value="{{ $payment->id }}"
                                            {{ $payment->id == $promise->schedule_payment_id ? 'selected' : '' }}>
                                            SAR {{ number_format($payment->instalment_amount, 2) }} - Due:
                                            {{ $payment->due_date->format(dateFormat()) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ translate('Contact Method') }}</label>
                                <select name="method" class="select" required disabled>
                                    <option value="call" {{ $promise->method == 'call' ? 'selected' : '' }}>
                                        {{ translate('Call') }}
                                    </option>
                                    <option value="email" {{ $promise->method == 'email' ? 'selected' : '' }}>
                                        {{ translate('Email') }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ translate('Promise Date') }}</label>
                                <input type="text" name="promise_date" id="promise_date_edit_{{ $promise->id }}"
                                    class="input" value="{{ $promise->promise_date }}" required>
                            </div>

                            <button type="submit" class="btn btn-warning">{{ translate('Update Promise') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-gray-500 text-sm">{{ translate('No promises available.') }}</div>
        @endforelse
    </div>
</div>

{{-- Add Modal --}}
<div class="modal" data-modal="true" id="add_promise_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Add Promise') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <form action="{{ route('promises.store') }}" method="POST" id="add-promise-form">
                @csrf
                <input type="hidden" name="user_id" value="{{ $order->user_id }}">

                <div class="mb-3">
                    <label class="form-label">{{ translate('Installment') }}</label>
                    <select name="schedule_payment_id" class="select" required>
                        <option value="">{{ translate('Select Installment') }}</option>
                        @foreach ($unpaidPayments as $payment)
                            <option value="{{ $payment->id }}">
                                SAR {{ number_format($payment->instalment_amount, 2) }} - Due:
                                {{ $payment->due_date->format(dateFormat()) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ translate('Contact method') }}</label>
                    <select name="method" class="select" required>
                        <option value="call">{{ translate('Call') }}</option>
                        <option value="email">{{ translate('Email') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ translate('Promise Date') }}</label>
                    <input type="text" name="promise_date" id="promise_date" class="input"
                        placeholder="{{ translate('Select Date') }}" required>
                </div>

                <button type="submit" class="btn btn-warning">{{ translate('Save Promise') }}</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Initialize Flatpickr
            flatpickr('#promise_date', {
                dateFormat: 'Y-m-d',
                minDate: 'today'
            });
            document.querySelectorAll('[id^="promise_date_edit_"]').forEach(el => {
                flatpickr(el, {
                    dateFormat: 'Y-m-d',
                    minDate: 'today'
                });
            });

            // ADD PROMISE AJAX
            document.querySelector('#add-promise-form')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error || 'Something went wrong', 'error');
                        }
                    });
            });

            // EDIT PROMISE AJAX
            document.querySelectorAll('.edit-promise-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Updated', data.message, 'success').then(() =>
                                    location.reload());
                            } else {
                                Swal.fire('Error', data.error || 'Update failed', 'error');
                            }
                        });
                });
            });

            // DELETE PROMISE AJAX
            document.querySelectorAll('.delete-promise-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '{{ translate('Are you sure?') }}',
                        text: '{{ translate('This action cannot be undone!') }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '{{ translate('Yes, delete it!') }}'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch(form.action, {
                                    method: 'POST',
                                    body: new FormData(form),
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Deleted', data.message, 'success')
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Error', data.error ||
                                            'Delete failed', 'error');
                                    }
                                });
                        }
                    });
                });
            });
        });
    </script>
@endpush
