{{-- resources/views/admin/risk-management/partials/weight-modal.blade.php --}}
<div class="modal" data-modal="true" id="weight_modal">
    <div class="modal-content max-w-[700px] top-[5%]">
        <div class="modal-header py-4 px-5 border-b">
            <h5 class="modal-title">{{ translate('Set Risk Weights') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-0 pb-5">
            <form id="weight_form" action="{{ route('riskWeights.store') }}" method="POST" class="px-5 pt-3 space-y-5">
                @csrf
                <input type="hidden" name="weight_id" id="weight_id" value="">

                {{-- ================= MAIN WEIGHTS ================= --}}
                <div class="border-b pb-3 mb-2">
                    <h6 class="font-semibold mb-3">{{ translate('Main Weights (Sum must be 100)') }}</h6>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">{{ translate('CR/ID Weight') }}</label>
                            <input type="number" step="0.01" min="0" name="cr_id" id="cr_id"
                                class="input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ translate('POS Weight') }}</label>
                            <input type="number" step="0.01" min="0" name="pos" id="pos"
                                class="input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Repayment Weight') }}</label>
                            <input type="number" step="0.01" min="0" name="repayment" id="repayment"
                                class="input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Industry Weight') }}</label>
                            <input type="number" step="0.01" min="0" name="industry" id="industry"
                                class="input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Location Weight') }}</label>
                            <input type="number" step="0.01" min="0" name="location" id="location"
                                class="input" required>
                        </div>
                    </div>
                </div>

                {{-- ================= CR/ID SUB-WEIGHTS ================= --}}
                <div class="border-b pb-3 mb-2">
                    <h6 class="font-semibold mb-3">{{ translate('CR/ID Sub-Weights (Total 100)') }}</h6>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">{{ translate('ID Match') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_id_match" id="cr_id_sub_id_match"
                                class="input" value="30">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('ID Expiry') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_id_expiry" id="cr_id_sub_id_expiry"
                                class="input" value="20">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('CR Expiry') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_cr_expiry" id="cr_id_sub_cr_expiry"
                                class="input" value="20">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Industry (Within CR)') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_industry" id="cr_id_sub_industry"
                                class="input" value="15">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Activity Presence') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_activity" id="cr_id_sub_activity"
                                class="input" value="15">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Total') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_total" id="cr_id_sub_total"
                                class="input" value="100" readonly>
                        </div>
                    </div>
                </div>

                {{-- ================= POS SECTION ================= --}}
                <div class="border-b pb-3 mb-2">
                    <h6 class="font-semibold mb-3">{{ translate('POS Thresholds') }}</h6>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">{{ translate('Monthly POS Threshold') }}</label>
                            <input type="number" step="0.01" min="0" name="pos_threshold"
                                id="pos_threshold" class="input" value="50000">
                        </div>
                    </div>
                </div>

                {{-- ================= REPAYMENT SECTION ================= --}}
                <div class="border-b pb-3 mb-2">
                    <h6 class="font-semibold mb-3">{{ translate('Repayment Sub-Weights') }}</h6>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">{{ translate('Few Delays Threshold') }}</label>
                            <input type="number" step="1" min="0" name="repayment_few_threshold"
                                id="repayment_few_threshold" class="input" value="2">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('No Delays Score') }}</label>
                            <input type="number" step="0.01" min="0" name="repayment_score_no_delays"
                                id="repayment_score_no_delays" class="input" value="20">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Few Delays Score') }}</label>
                            <input type="number" step="0.01" min="0" name="repayment_score_few_delays"
                                id="repayment_score_few_delays" class="input" value="15">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Many Delays Score') }}</label>
                            <input type="number" step="0.01" min="0" name="repayment_score_many_delays"
                                id="repayment_score_many_delays" class="input" value="5">
                        </div>
                    </div>
                </div>

                {{-- ================= LOCATION SECTION ================= --}}
                <div>
                    <h6 class="font-semibold mb-3">{{ translate('Location Sub-Weights') }}</h6>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">{{ translate('Activity Max') }}</label>
                            <input type="number" step="0.01" min="0" name="location_activity_max"
                                id="location_activity_max" class="input" value="4.5">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Default Rate Max') }}</label>
                            <input type="number" step="0.01" min="0" name="location_default_rate_max"
                                id="location_default_rate_max" class="input" value="4.5">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Sub Total Max') }}</label>
                            <input type="number" step="0.01" min="0" name="location_sub_total_max"
                                id="location_sub_total_max" class="input" value="15">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" id="weight_submit_btn" class="btn btn-primary">
                        {{ translate('Save Weights') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weightForm = document.getElementById('weight_form');
            const weightModal = document.getElementById('weight_modal');
            const openWeightBtn = document.querySelector('[data-modal-toggle="#weight_modal"]');
            const submitBtn = document.getElementById('weight_submit_btn');

            const storeUrl = "{{ route('riskWeights.store') }}";
            const updateBaseUrl = "{{ route('riskWeights.update', ':id') }}";
            const lastUrl = "{{ route('riskWeights.last') }}";

            function openModal() {
                weightModal.dataset.modal = true;
            }

            function closeModal() {
                weightModal.dataset.modal = false;
            }

            function disableEl(el) {
                if (el) {
                    el.setAttribute('disabled', 'disabled');
                    el.classList.add('opacity-60', 'cursor-not-allowed');
                }
            }

            function enableEl(el) {
                if (el) {
                    el.removeAttribute('disabled');
                    el.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            }

            // Open modal and fetch last weights
            if (openWeightBtn) {
                openWeightBtn.addEventListener('click', function() {
                    disableEl(openWeightBtn);
                    fetch(lastUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.ok ? res.json() : {
                            success: false
                        })
                        .then(data => {
                            const w = data.success && data.weights ? data.weights : {};
                            const setVal = (id, val) => document.getElementById(id).value = w[id] ??
                                val;

                            // Main
                            setVal('cr_id', 25);
                            setVal('pos', 25);
                            setVal('repayment', 20);
                            setVal('industry', 15);
                            setVal('location', 10);

                            // CR/ID
                            setVal('cr_id_sub_id_match', 30);
                            setVal('cr_id_sub_id_expiry', 20);
                            setVal('cr_id_sub_cr_expiry', 20);
                            setVal('cr_id_sub_industry', 15);
                            setVal('cr_id_sub_activity', 15);
                            setVal('cr_id_sub_total', 100);

                            // POS
                            setVal('pos_threshold', 50000);

                            // Repayment
                            setVal('repayment_few_threshold', 2);
                            setVal('repayment_score_no_delays', 20);
                            setVal('repayment_score_few_delays', 15);
                            setVal('repayment_score_many_delays', 5);

                            // Location
                            setVal('location_activity_max', 4.5);
                            setVal('location_default_rate_max', 4.5);
                            setVal('location_sub_total_max', 15);

                            document.getElementById('weight_id').value = w.id ?? '';
                            openModal();
                        })
                        .finally(() => enableEl(openWeightBtn));
                });
            }

            // Submit form
            weightForm.addEventListener('submit', function(e) {
                e.preventDefault();
                disableEl(submitBtn);

                const formData = new FormData(weightForm);
                const id = formData.get('weight_id');
                let url = storeUrl;
                if (id) {
                    url = updateBaseUrl.replace(':id', id);
                    formData.set('_method', 'PUT');
                }

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));
                        if (res.ok && json.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: json.message ??
                                    '{{ translate('Weights saved successfully') }}'
                            }).then(() => {
                                // ✅ Reload the page after user clicks "OK"
                                window.location.reload();
                            });

                            if (json.data?.id) document.getElementById('weight_id').value = json
                                .data.id;
                        } else {
                            const msg = json.message ?? '{{ translate('Validation failed') }}';
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: msg
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('Server error') }}'
                        });
                    })
                    .finally(() => enableEl(submitBtn));
            });
        });
    </script>
@endpush
