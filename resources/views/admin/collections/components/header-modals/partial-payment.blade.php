@php
    // Fetch unpaid schedule payments for the user (replace $user with actual user variable)
    $unpaidPayments = App\Models\SchedulePayment::where('user_id', $order->user_id)
        ->where('payment_status', '!=', 'paid')
        ->orderBy('due_date', 'asc')
        ->get();
@endphp

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

<!-- Partial Payment Modal -->
<div class="modal" data-modal="true" id="partial_payment_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Partial Payment') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-5">
            <form id="partialForm" action="{{ route('partial-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $order->user_id }}">
                <input type="hidden" name="employee_id" value="{{ auth()->id() }}">

                {{-- Select unpaid schedule payments --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Select Installment') }}</label>
                    <select name="schedule_payment_id" id="partial_schedule_payment" class="select" required>
                        <option value="">{{ translate('Select an unpaid installment') }}</option>
                        @foreach ($unpaidPayments as $payment)
                            @php
                                $remaining = $payment->instalment_amount - $payment->deducted_amount;
                            @endphp
                            <option value="{{ $payment->id }}" data-remaining="{{ $remaining }}">
                                SAR {{ number_format($payment->instalment_amount, 2) }}
                                — Remaining: SAR {{ number_format($remaining, 2) }}
                                — Due: {{ $payment->due_date->format('Y-m-d') }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger text-sm" id="error-schedule_payment_id"></span>
                </div>

                {{-- Input partial payment amount --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Partial Payment Amount') }}</label>
                    <input type="number" name="partial_amount" id="partial_amount" class="input"
                        placeholder="{{ translate('Enter amount to pay') }}" min="1" step="0.01" required>
                    <small id="remaining_info" class="text-muted block mt-1"></small>
                    <span class="text-danger text-sm" id="error-partial_amount"></span>
                </div>

                {{-- Select due date --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Due Date') }}</label>
                    <input type="text" name="partial_due_date" id="partial_due_date" class="input"
                        placeholder="{{ translate('Select Date') }}" required>
                    <span class="text-danger text-sm" id="error-partial_due_date"></span>
                </div>

                {{-- Optional remarks/details --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Details / Remarks') }}</label>
                    <textarea name="details" class="textarea" rows="2" placeholder="{{ translate('Optional remarks...') }}"></textarea>
                    <span class="text-danger text-sm" id="error-details"></span>
                </div>

                <button type="submit" class="btn btn-success">{{ translate('Create Partial Payment') }}</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('partialForm');
            const scheduleSelect = document.getElementById('partial_schedule_payment');
            const amountInput = document.getElementById('partial_amount');
            const remainingInfo = document.getElementById('remaining_info');
            const submitBtn = form.querySelector('button[type="submit"]');
            let remainingAmount = 0;

            flatpickr('#partial_due_date', {
                dateFormat: 'Y-m-d',
                minDate: 'today'
            });

            // Show remaining amount info when installment is selected
            scheduleSelect.addEventListener('change', () => {
                const selected = scheduleSelect.options[scheduleSelect.selectedIndex];
                remainingAmount = parseFloat(selected?.getAttribute('data-remaining') || 0);

                if (remainingAmount > 0) {
                    remainingInfo.textContent =
                        `{{ translate('Remaining amount:') }} SAR ${remainingAmount.toFixed(2)}`;
                    amountInput.setAttribute('max', remainingAmount);
                } else {
                    remainingInfo.textContent = '';
                    amountInput.removeAttribute('max');
                }
                amountInput.value = '';
            });

            // Validation: prevent entering more than remaining
            amountInput.addEventListener('input', () => {
                const entered = parseFloat(amountInput.value || 0);
                if (entered > remainingAmount) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ translate('Invalid Amount') }}',
                        text: '{{ translate('You cannot enter more than the remaining amount.') }}',
                    });
                    amountInput.value = remainingAmount.toFixed(2);
                }
            });

            // Ajax submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa fa-spinner fa-spin"></i> {{ translate('Processing...') }}';

                // Clear old errors
                document.querySelectorAll('[id^="error-"]').forEach(el => el.textContent = '');

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
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '{{ translate('Create Partial Payment') }}';

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                // Close modal and reload list
                                document.querySelector('[data-modal-dismiss="true"]').click();
                                location.reload();
                            });
                        } else if (data.errors) {
                            // Show Laravel validation errors
                            for (const field in data.errors) {
                                const errEl = document.getElementById(`error-${field}`);
                                if (errEl) errEl.textContent = data.errors[field][0];
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: data.error || '{{ translate('Something went wrong.') }}',
                            });
                        }
                    })
                    .catch(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '{{ translate('Create Partial Payment') }}';
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Server Error') }}',
                            text: '{{ translate('Unable to submit partial payment. Try again later.') }}',
                        });
                    });
            });
        });
    </script>
@endpush
