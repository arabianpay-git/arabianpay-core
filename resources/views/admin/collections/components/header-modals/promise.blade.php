@php
    // Fetch unpaid schedule payments for the user (replace $user with actual user variable)
    $unpaidPayments = App\Models\SchedulePayment::where('user_id', $order->user_id)
        ->where('payment_status', '!=', 'paid')
        ->orderBy('due_date', 'asc')
        ->get();
@endphp

@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

<div class="modal" data-modal="true" id="promise_to_pay_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Promise to Pay') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-5">
            <form id="promiseForm" action="{{ route('promises.store') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $order->user_id }}">

                {{-- Select unpaid schedule payments --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Select Installment') }}</label>
                    <select name="schedule_payment_id" class="select" required>
                        <option value="">{{ translate('Select an unpaid installment') }}</option>
                        @foreach ($unpaidPayments as $payment)
                            <option value="{{ $payment->id }}">
                                SAR {{ number_format($payment->instalment_amount, 2) }} — Due:
                                {{ $payment->due_date->format(dateFormat()) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ translate('Contact Method') }}</label>
                    <select name="method" class="select" required>
                        <option value="call">{{ translate('Call') }}</option>
                        <option value="email">{{ translate('Email') }}</option>
                    </select>
                </div>

                {{-- Promise Date --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Promise Date') }}</label>
                    <input type="text" name="promise_date" id="promise_date" class="input"
                        placeholder="{{ translate('Select Date') }}" required>
                </div>

                <button type="submit" class="btn btn-warning">{{ translate('Mark as Promise') }}</button>
            </form>
        </div>
    </div>
</div>
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Flatpickr init
            flatpickr('#promise_date', {
                dateFormat: 'Y-m-d',
                minDate: 'today'
            });

            // AJAX submit
            const form = document.getElementById('promiseForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Client-side validation
                const payment = form.querySelector('[name="schedule_payment_id"]').value;
                const date = form.querySelector('[name="promise_date"]').value;

                if (!payment || !date) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ translate('Validation Error') }}',
                        text: '{{ translate('Please select installment and promise date.') }}',
                    });
                    return;
                }

                const formData = new FormData(form);

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: data.message,
                                confirmButtonText: '{{ translate('OK') }}'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            // Handle failure with proper message
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: data.message || data.error ||
                                    '{{ translate('Something went wrong.') }}',
                                confirmButtonText: '{{ translate('OK') }}'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Server Error') }}',
                            text: '{{ translate('Unable to submit promise. Try again later.') }}',
                            confirmButtonText: '{{ translate('OK') }}'
                        });
                    });
            });
        });
    </script>
@endpush
