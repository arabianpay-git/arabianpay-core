@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Sensitive Data Approvals') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ translate('Pending Approvals') }}</h3>
                    </div>
                    <div class="card-body">
                        <div id="approvals_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border w-full">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('ID') }}</th>
                                            <th>{{ translate('Employee') }}</th>
                                            <th>{{ translate('Permissions') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                            <th>{{ translate('Requested At') }}</th>
                                            <th class="text-center">{{ translate('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($approvals as $approval)
                                            <tr>
                                                <td class="text-center text-gray-600">{{ $approval->id }}</td>
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-900">
                                                            {{ $approval->requester?->first_name }}
                                                            {{ $approval->requester?->last_name }}
                                                        </span>
                                                        <span
                                                            class="text-2xs text-gray-500">{{ $approval->requester?->email }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if (empty($approval->sensitive_permissions))
                                                        <span class="text-sm text-gray-400">-</span>
                                                    @else
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach ($approval->sensitive_permissions as $perm)
                                                                <span class="badge badge-sm badge-outline badge-primary">
                                                                    {{ \Illuminate\Support\Str::headline($perm) }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusClasses = [
                                                            'pending' => 'badge-warning',
                                                            'approved' => 'badge-success',
                                                            'rejected' => 'badge-danger',
                                                            'revoked' => 'badge-secondary',
                                                        ];
                                                        $class = $statusClasses[$approval->status] ?? 'badge-light';
                                                    @endphp
                                                    <span
                                                        class="badge badge-sm badge-outline {{ $class }} font-semibold">
                                                        {{ ucfirst($approval->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-gray-600 text-sm">
                                                    {{ $approval->created_at->format(dateFormat()) }}
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary manage-approval-btn"
                                                        data-id="{{ $approval->id }}">
                                                        {{ translate('Manage') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-10 text-gray-500">
                                                    {{ translate('No pending requests found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @include('layouts.includes.table-pagination', ['paginator' => $approvals])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Hidden trigger for modal system compatibility --}}
    <button id="modal_trigger_hidden" class="hidden" data-modal-toggle="#approval_manage_modal"></button>

    {{-- Admin decision modal --}}
    <div class="modal" data-modal="true" id="approval_manage_modal">
        <div class="modal-content max-w-[600px] top-[10%] shadow-xl border-0">
            <div class="modal-header py-4 px-5 border-b border-gray-100">
                <h5 class="modal-title font-bold text-gray-800">{{ translate('Manage Access Request') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross text-xl"></i>
                </button>
            </div>
            <div class="modal-body p-6">
                <form id="approval-decision-form">
                    <input type="hidden" id="modal_approval_id" value="">

                    <div class="flex flex-col gap-5">
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                            <label
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 block">{{ translate('Employee') }}</label>
                            <div id="modal_employee" class="text-gray-900 font-medium"></div>
                        </div>

                        <div>
                            <label
                                class="form-label font-semibold text-gray-700 mb-2">{{ translate('Requested Permissions') }}</label>
                            <div id="modal_permissions" class="flex flex-wrap gap-1"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="form-label">{{ translate('Access Start') }}</label>
                                <input id="modal_start_at" type="text" class="input" placeholder="Select date">
                                <span class="text-danger text-xs mt-1 hidden" id="error_start_at"></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="form-label">{{ translate('Access End') }}</label>
                                <input id="modal_end_at" type="text" class="input" placeholder="Select date">
                                <span class="text-danger text-xs mt-1 hidden" id="error_end_at"></span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="form-label">{{ translate('Set Status') }}</label>
                            <select id="modal_status" class="select">
                                <option value="approved">{{ translate('Approve') }}</option>
                                <option value="rejected">{{ translate('Reject') }}</option>
                                <option value="revoked">{{ translate('Revoke') }}</option>
                            </select>
                            <span class="text-danger text-xs mt-1 hidden" id="error_status"></span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="form-label">{{ translate('Notes for Employee') }}</label>
                            <textarea id="modal_decision_notes" rows="3" class="textarea"
                                placeholder="{{ translate('Provide a reason for this decision...') }}"></textarea>
                            <span class="text-danger text-xs mt-1 hidden" id="error_decision_notes"></span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                        <button type="button" class="btn btn-sm btn-light"
                            data-modal-dismiss="true">{{ translate('Cancel') }}</button>
                        <button id="modal_submit_btn" type="submit" class="btn btn-sm btn-primary">
                            <span class="indicator-label">{{ translate('Save Decision') }}</span>
                            <span class="indicator-progress hidden">
                                {{ translate('Processing...') }} <span
                                    class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .badge-outline {
            background-color: transparent !important;
            border: 1px solid currentColor;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            "use strict";

            const el = {
                form: document.getElementById('approval-decision-form'),
                modalApprovalId: document.getElementById('modal_approval_id'),
                modalEmployee: document.getElementById('modal_employee'),
                modalPermissions: document.getElementById('modal_permissions'),
                modalStart: document.getElementById('modal_start_at'),
                modalEnd: document.getElementById('modal_end_at'),
                modalStatus: document.getElementById('modal_status'),
                modalNotes: document.getElementById('modal_decision_notes'),
                submitBtn: document.getElementById('modal_submit_btn'),
                trigger: document.getElementById('modal_trigger_hidden')
            };

            let fpStart, fpEnd;

            // Initialize Flatpickr
            fpStart = flatpickr(el.modalStart, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        // Set the minDate of end picker to selected start date
                        fpEnd.set('minDate', selectedDates[0]);
                    } else {
                        fpEnd.set('minDate', "today");
                    }
                }
            });

            fpEnd = flatpickr(el.modalEnd, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: fpStart.selectedDates[0] || "today"
            });

            const clearErrors = () => {
                document.querySelectorAll('[id^="error_"]').forEach(e => e.classList.add('hidden'));
            };

            const toggleLoading = (isLoading) => {
                el.submitBtn.disabled = isLoading;
                el.submitBtn.querySelector('.indicator-label').classList.toggle('hidden', isLoading);
                el.submitBtn.querySelector('.indicator-progress').classList.toggle('hidden', !isLoading);
            };

            // Manage Button Click
            document.addEventListener('click', async function(e) {
                const btn = e.target.closest('.manage-approval-btn');
                if (!btn) return;

                const id = btn.dataset.id;
                clearErrors();

                try {
                    const res = await fetch(`/admin/approvals/${id}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const result = await res.json();

                    if (!res.ok || !result.success) throw new Error(result.message || 'Fetch failed');

                    const data = result.data;

                    // Populate Data
                    el.modalApprovalId.value = data.id;
                    el.modalEmployee.innerHTML = `
                        <div class="flex flex-col">
                            <span>${data.requester?.first_name || ''} ${data.requester?.last_name || ''}</span>
                            <span class="text-xs text-gray-500 font-normal">${data.requester?.email || ''}</span>
                        </div>
                    `;

                    el.modalPermissions.innerHTML = (data.sensitive_permissions || []).map(p =>
                        `<span class="badge badge-sm badge-outline badge-primary">${p.replace(/_/g, ' ')}</span>`
                    ).join('');

                    el.modalStatus.value = data.status;
                    el.modalNotes.value = data.decision_notes || '';

                    if (data.start_at) fpStart.setDate(data.start_at);
                    if (data.end_at) fpEnd.setDate(data.end_at);

                    // Open Modal
                    el.trigger.click();

                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message
                    });
                }
            });

            // Form Submit
            el.form.addEventListener('submit', async function(e) {
                e.preventDefault();
                clearErrors();
                toggleLoading(true);

                const id = el.modalApprovalId.value;
                const payload = {
                    status: el.modalStatus.value,
                    start_at: el.modalStart.value,
                    end_at: el.modalEnd.value,
                    decision_notes: el.modalNotes.value
                };

                try {
                    const res = await fetch(`/admin/approvals/${id}/decision`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json();

                    if (!res.ok) {
                        if (res.status === 422) {
                            Object.keys(json.errors).forEach(key => {
                                const errEl = document.getElementById('error_' + key);
                                if (errEl) {
                                    errEl.textContent = json.errors[key][0];
                                    errEl.classList.remove('hidden');
                                }
                            });
                        } else {
                            throw new Error(json.message || 'Update failed');
                        }
                        return;
                    }

                    Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: json.message
                        })
                        .then(() => location.reload());

                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message
                    });
                } finally {
                    toggleLoading(false);
                }
            });
        })();
    </script>
@endpush
