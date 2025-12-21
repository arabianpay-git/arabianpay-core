{{-- resources/views/admin/approvals/modal.blade.php --}}
@php
    $selected = collect(old('sensitive_permissions', []))->map(fn($v) => (string) $v)->toArray();
@endphp

<div class="modal fade" id="sensitive_approval_modal" tabindex="-1" aria-hidden="true" data-modal="true">
    <div class="modal-content max-w-[600px] top-[10%] shadow-lg border-0">
        <div class="modal-header py-4 px-5 border-b border-gray-200">
            <h5 class="modal-title font-semibold text-gray-800">{{ translate('Request Sensitive Data Access') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross text-xl"></i>
            </button>
        </div>

        <div class="modal-body p-6">
            <form id="sensitive-approval-form" method="POST" action="{{ route('approvals.store') }}">
                @csrf
                <input type="hidden" name="employee_id" id="approval_employee_id" value="{{ $employee->id ?? '' }}">

                <div class="grid gap-5">
                    <div class="flex flex-col gap-2">
                        <label class="form-label font-medium text-gray-700">
                            {{ translate('Sensitive Permissions') }} <span class="text-danger">*</span>
                        </label>
                        <select id="sensitive_permissions" name="sensitive_permissions[]" class="select w-full" multiple
                            data-placeholder="{{ translate('Select permissions...') }}">
                            @php
                                $options = [
                                    'business_identity' => 'Business Identity (CR/VAT/Activity)',
                                    'authorized_person_name' => 'Authorized Person Name',
                                    'national_id_iqama' => 'National ID / Iqama',
                                    'phone_number' => 'Phone Number',
                                    'email_address' => 'Email Address',
                                    'full_address' => 'Address (Full)',
                                    'iban_bank_account' => 'IBAN / Bank Account',
                                    'documents_id_cr_contracts' => 'Documents (ID/CR/Contracts)',
                                    'credit_data_simah_bureau' => 'Credit Data (SIMAH / Bureau)',
                                    'credit_decision_output' => 'Credit Decision Output (Limit/Grade)',
                                    'risk_drivers_aggregated' => 'Risk Drivers (Aggregated)',
                                    'transaction_references' => 'Transaction References (Gateway/Bank)',
                                    'risk_compliance_notes' => 'Notes (Risk/Compliance/Collections)',
                                    'export_dataset_row_level' => 'Export Dataset (Row-level)',
                                    'api_keys_secrets' => 'API Keys / Secrets',
                                ];
                            @endphp
                            @foreach ($options as $value => $label)
                                <option value="{{ $value }}" @selected(in_array($value, $selected))>
                                    {{ translate($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="form-label font-medium text-gray-700">{{ translate('Reason for Access') }}</label>
                        <textarea id="request_reason" name="request_reason" rows="3" class="textarea w-full"
                            placeholder="{{ translate('Please explain why you need access to this data...') }}">{{ old('request_reason') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-3 mt-8 pt-4 border-t border-gray-100">
                    <button type="button" class="btn btn-sm btn-light" data-modal-dismiss="true">
                        {{ translate('Cancel') }}
                    </button>
                    <button id="submit-approval-btn" type="submit"
                        class="btn btn-sm btn-primary flex items-center gap-2">
                        <span class="indicator-label">{{ translate('Request Access') }}</span>
                        <span class="indicator-progress hidden">
                            {{ translate('Please wait...') }} <span
                                class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Modern Select2 Styling */
        .select2-container--default .select2-selection--multiple {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            padding: 4px 10px !important;
            min-height: 44px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
            padding: 3px 10px !important;
            font-size: 0.8125rem !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            padding-left: 14px !important;
        }

        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            z-index: 9999;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function() {
            "use strict";

            const elements = {
                form: document.getElementById('sensitive-approval-form'),
                submitBtn: document.getElementById('submit-approval-btn'),
                permissionSelect: $('#sensitive_permissions')
            };

            const toggleLoading = (isLoading) => {
                elements.submitBtn.disabled = isLoading;
                elements.submitBtn.textContent = isLoading ?
                    '{{ translate('Submitting...') }}' :
                    '{{ translate('Request Access') }}';
            };

            // Show validation errors via SweetAlert
            const showValidationAlert = (message, conflictingPermissions = []) => {
                let errorHtml = `<p>${message}</p>`;
                if (conflictingPermissions.length > 0) {
                    const list = conflictingPermissions.map(p => `<li>${p}</li>`).join('');
                    errorHtml += `<ul class="text-left list-disc pl-5 mt-2 text-sm text-red-600">${list}</ul>`;

                    // Highlight conflicting permissions in Select2
                    const currentValues = elements.permissionSelect.val() || [];
                    const newValues = currentValues.filter(v => !conflictingPermissions.includes(v));
                    elements.permissionSelect.val(newValues).trigger('change');
                }

                Swal.fire({
                    icon: 'warning',
                    title: '{{ translate('Validation Failed') }}',
                    html: errorHtml,
                    confirmButtonText: '{{ translate('Ok') }}',
                    customClass: {
                        confirmButton: 'btn btn-warning'
                    }
                });
            };

            // Initialize Select2
            $(document).on('shown.bs.modal', '#sensitive_approval_modal', function() {
                elements.permissionSelect.select2({
                    dropdownParent: $('#sensitive_approval_modal'),
                    width: '100%',
                    placeholder: '{{ translate('Select Sensitive Permissions') }}'
                });
            });

            // Form submit
            elements.form.addEventListener('submit', async function(e) {
                e.preventDefault();
                toggleLoading(true);

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        },
                        body: new FormData(this)
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        if (response.status === 422) {
                            // If server sends conflicting_permissions, highlight them
                            const conflicting = result.conflicting_permissions || [];
                            showValidationAlert(result.message || '{{ translate('Validation failed') }}',
                                conflicting);
                        } else {
                            throw new Error(result.message || '{{ translate('Request failed') }}');
                        }
                        return;
                    }

                    // Success
                    Swal.fire({
                        icon: 'success',
                        title: '{{ translate('Submitted') }}',
                        text: result.message ||
                            '{{ translate('Your request has been sent for approval.') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    }).then(() => {
                        $('[data-modal-dismiss="true"]').first().click(); // Close modal
                        this.reset();
                        elements.permissionSelect.val(null).trigger('change');
                    });

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('Error') }}',
                        text: error.message,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                } finally {
                    toggleLoading(false);
                }
            });
        })();
    </script>
@endpush
