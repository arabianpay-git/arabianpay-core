@php
    $partialPayments = App\Models\PartialPayment::where('user_id', $order->user_id)
        ->with('schedulePayment')
        ->orderBy('schedule_payment_id', 'asc')
        ->orderBy('partial_due_date', 'asc')
        ->get();

    $scheduleNumbers = App\Models\SchedulePayment::where('user_id', $order->user_id)
        ->orderBy('due_date', 'asc')
        ->pluck('id')
        ->flip();
@endphp

@foreach ($partialPayments as $partial)
    @php
        $installmentNumber = $scheduleNumbers[$partial->schedule_payment_id] + 1 ?? 1;
        $detailsText = optional($partial->details)['text'] ?? null;
    @endphp

    <div class="sp-card promise" id="partial-{{ $partial->id }}">
        <div class="sp-ribbon">
            <span class="sp-ribbon-text">{{ translate('PROMISE') }}</span>
        </div>

        <div class="sp-card-content">
            <div class="sp-card-top">
                <div class="sp-left">
                    <div class="installment-number-circle">{{ $partial->schedule_payment_id }}</div>
                    <div class="sp-title">
                        <div class="inst-title">
                            {{ translate('Partial Payment for Installment') }} #{{ $partial->schedule_payment_id }}
                        </div>
                        <div class="inst-amount">
                            <span class="icon-saudi_riyal"></span>
                            {{ number_format($partial->partial_amount, 2) }}
                        </div>
                    </div>
                </div>

                <div class="sp-uuid" title="{{ $partial->id }}">
                    {{ 'INV-' . str_pad($partial->id, 6, '0', STR_PAD_LEFT) }}
                </div>
            </div>

            <div class="sp-body">
                <div class="sp-row">
                    <div class="meta">
                        @php
                            $statusColors = [
                                'pending' => 'yellow',
                                'paid' => 'green',
                                'cancelled' => 'red',
                                'rescheduled' => 'blue',
                                'failed' => 'gray',
                                'over_due' => 'orange',
                            ];

                            $approvalColors = [
                                'pending' => 'yellow',
                                'approved' => 'green',
                                'rejected' => 'red',
                                'review' => 'blue',
                            ];
                        @endphp

                        <span class="meta-badge {{ $statusColors[$partial->status] ?? 'gray' }}">
                            {{ translate('Status:') }} {{ ucfirst($partial->status) }}
                        </span>

                        <span class="meta-badge {{ $approvalColors[$partial->approval_status] ?? 'gray' }}">
                            {{ translate('Approval:') }} {{ ucfirst($partial->approval_status) }}
                        </span>

                        <span class="meta-badge gray">
                            {{ translate('Remaining') }}:
                            {{ number_format(
                                optional($partial->schedulePayment)->instalment_amount - optional($partial->schedulePayment)->deducted_amount,
                                2,
                            ) }}
                        </span>
                    </div>

                </div>

                <div class="sp-row mt-2">
                    <div class="due text-xs text-gray-600">
                        {{ \Carbon\Carbon::parse($partial->partial_due_date)->format('d M Y') }}
                    </div>

                    @if ($partial->status !== 'paid')
                        <div class="sp-actions flex gap-2">
                            {{-- Edit button --}}
                            <button class="btn btn-sm btn-outline btn-warning edit-partial-btn"
                                data-modal-toggle="#edit_partial_modal_{{ $partial->id }}"
                                data-partial-id="{{ $partial->id }}"
                                data-due="{{ \Carbon\Carbon::parse($partial->partial_due_date)->format('Y-m-d') }}"
                                data-details="{{ e(optional($partial->details)['text'] ?? '') }}"
                                data-amount="{{ $partial->partial_amount }}">
                                {{ translate('Edit') }}
                            </button>

                            {{-- Delete button --}}
                            <button class="btn btn-sm btn-outline btn-danger delete-partial-btn"
                                data-id="{{ $partial->id }}">
                                {{ translate('Delete') }}
                            </button>

                            {{-- Send reminder --}}
                            <a href="#" class="send-reminder btn btn-sm btn-secondary"
                                data-id="{{ $partial->id }}">
                                {{ translate('Send Reminder') }}
                            </a>
                        </div>
                    @else
                        <div class="sp-actions flex items-center gap-2">
                            <span class="badge badge-sm badge-outline badge-success">{{ translate('Paid') }}</span>
                            @if ($partial->paid_at)
                                <span class="due text-xs text-gray-500">
                                    {{ translate('Paid At:') }}
                                    {{ \Carbon\Carbon::parse($partial->paid_at)->format('d M Y') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($detailsText)
                    <div class="sp-row mt-2">
                        <div class="inst-sub text-xs text-gray-500">
                            {{ $detailsText }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Edit Modal (per-partial, kept inside loop as you had it) --}}
    <div class="modal" id="edit_partial_modal_{{ $partial->id }}" data-modal="true" aria-hidden="true">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">{{ translate('Edit Partial Payment') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                    data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-5">
                <form class="edit-partial-form" data-id="{{ $partial->id }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Partial Amount') }}</label>
                        <input type="number" name="partial_amount" class="input w-full"
                            value="{{ $partial->partial_amount }}" min="1" step="0.01" disabled required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Due Date') }}</label>
                        <input type="text" name="partial_due_date" class="input w-full flatpickr-date"
                            value="{{ $partial->partial_due_date }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Details / Remarks') }}</label>
                        <textarea name="details" class="textarea w-full" rows="2">{{ $detailsText }}</textarea>
                    </div>
                    <div class="mb-2">
                        <span class="edit-partial-error text-red-500 text-sm"></span>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Update Partial Payment') }}</button>
                </form>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
    {{-- Flatpickr + SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- simple modal toggle implementation ---
            // triggers: elements with data-modal-toggle="#modalId"
            // close: elements with data-modal-dismiss="true" inside modal
            document.querySelectorAll('[data-modal-toggle]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const selector = trigger.getAttribute('data-modal-toggle');
                    if (!selector) return;
                    const modal = document.querySelector(selector);
                    if (!modal) return;

                    // populate modal inputs if trigger has data attributes (we pass them)
                    const partialId = trigger.dataset.partialId;
                    const due = trigger.dataset.due;
                    const details = trigger.dataset.details;
                    const amount = trigger.dataset.amount;

                    // find form inside modal
                    const form = modal.querySelector('.edit-partial-form');
                    if (form) {
                        // set dataset id on form (so submit handler knows it)
                        form.dataset.id = partialId ?? form.dataset.id;

                        // set input values if present
                        const dueInput = form.querySelector('[name="partial_due_date"]');
                        const detailsInput = form.querySelector('[name="details"]');
                        const amountInput = form.querySelector('[name="partial_amount"]');

                        if (amountInput && (amount !== undefined)) amountInput.value = amount;
                        if (detailsInput && (details !== undefined)) detailsInput.value = details;
                        if (dueInput && (due !== undefined)) {
                            // If flatpickr already initialized on this input, set date; else set value and init below
                            if (dueInput._flatpickr) {
                                try {
                                    dueInput._flatpickr.setDate(due, true);
                                } catch (err) {
                                    dueInput.value = due;
                                }
                            } else {
                                dueInput.value = due;
                            }
                        }
                    }

                    // show modal (by setting data attribute; your CSS/modal system should react to this)
                    modal.setAttribute('data-modal-open', 'true');
                    modal.setAttribute('aria-hidden', 'false');

                    // initialize flatpickr for any .flatpickr-date inside this modal if not already
                    if (window.flatpickr) {
                        modal.querySelectorAll('.flatpickr-date').forEach(input => {
                            if (!input._flatpickr) {
                                flatpickr(input, {
                                    dateFormat: "Y-m-d",
                                    minDate: "today",
                                    allowInput: true,
                                });
                                // if trigger provided date, set it
                                if (trigger.dataset.due) {
                                    try {
                                        input._flatpickr.setDate(trigger.dataset.due, true);
                                    } catch (err) {}
                                }
                            }
                        });
                    }
                });
            });

            // close buttons inside modals
            document.querySelectorAll('[data-modal-dismiss="true"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    // find nearest modal ancestor
                    const modal = btn.closest('.modal');
                    if (!modal) return;
                    modal.removeAttribute('data-modal-open');
                    modal.setAttribute('aria-hidden', 'true');
                });
            });

            // Initialize flatpickr for any visible flatpickr-date inputs (outside modals)
            if (window.flatpickr) {
                document.querySelectorAll('.flatpickr-date').forEach(input => {
                    if (!input._flatpickr) {
                        flatpickr(input, {
                            dateFormat: "Y-m-d",
                            minDate: "today",
                            allowInput: true,
                        });
                    }
                });
            }

            // --- Edit Partial submit handlers (one per form as you had) ---
            document.querySelectorAll('.edit-partial-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const partialId = this.dataset.id;
                    const updateUrl = "{{ route('partial-payments.update', ['id' => ':id']) }}"
                        .replace(':id', partialId);

                    const payload = {
                        partial_due_date: this.querySelector('[name="partial_due_date"]').value,
                        details: this.querySelector('[name="details"]').value
                    };

                    // clear previous inline error
                    this.querySelector('.edit-partial-error').textContent = '';

                    fetch(updateUrl, {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(async res => {
                            const contentType = res.headers.get('content-type') || '';
                            let data = {};
                            if (contentType.includes('application/json')) {
                                data = await res.json();
                            } else {
                                const text = await res.text();
                                try {
                                    data = JSON.parse(text);
                                } catch {
                                    data = {
                                        success: false,
                                        message: text
                                    };
                                }
                            }

                            if (!res.ok) {
                                if (res.status === 422 && data.errors) {
                                    // show first validation message (inline + SweetAlert)
                                    const msgs = Object.keys(data.errors).map(k => data
                                        .errors[k][0]);
                                    const firstMsg = msgs.join('\n');
                                    this.querySelector('.edit-partial-error').textContent =
                                        firstMsg;
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Validation Error',
                                        text: firstMsg
                                    });
                                    return Promise.reject(firstMsg);
                                }
                                const msg = data.message || 'Server error';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg
                                });
                                return Promise.reject(msg);
                            }

                            return data;
                        })
                        .then(data => {
                            if (data && data.success) {
                                // close the modal that contains this form
                                const modal = this.closest('.modal');
                                modal?.querySelector('[data-modal-dismiss="true"]')?.click();

                                Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: data.message || 'Updated'
                                    })
                                    .then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Update failed'
                                });
                            }
                        })
                        .catch(err => {
                            console.error('edit-partial error', err);
                        });
                });
            });

            // --- Delete Partial Payment via AJAX (single handler) ---
            document.querySelectorAll('.delete-partial-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    Swal.fire({
                        title: '{{ translate('Are you sure you want to delete this partial payment?') }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '{{ translate('Yes, delete it!') }}'
                    }).then((result) => {
                        if (!result.isConfirmed) return;

                        const deleteUrl =
                            "{{ route('partial-payments.destroy', ['id' => ':id']) }}"
                            .replace(':id', id);

                        fetch(deleteUrl, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                            })
                            .then(async res => {
                                const contentType = res.headers.get(
                                    'content-type') || '';
                                let data = {};
                                if (contentType.includes('application/json')) {
                                    data = await res.json();
                                } else {
                                    const text = await res.text();
                                    try {
                                        data = JSON.parse(text);
                                    } catch {
                                        data = {
                                            success: false,
                                            message: text
                                        };
                                    }
                                }

                                if (!res.ok) {
                                    const msg = data.message ||
                                        'Server error while deleting';
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: msg
                                    });
                                    return Promise.reject(msg);
                                }

                                return data;
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: data.message
                                        })
                                        .then(() => {
                                            document.getElementById(`partial-${id}`)
                                                ?.remove();
                                        });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'Delete failed'
                                    });
                                }
                            })
                            .catch(err => console.error('delete error', err));
                    });
                });
            });

        });
    </script>
@endpush
