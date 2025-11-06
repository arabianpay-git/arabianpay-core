{{-- resources/views/admin/risk-management/partials/weight-modal.blade.php --}}
<div class="modal" data-modal="true" id="weight_modal">
    <div class="modal-content max-w-[700px] top-[5%]">
        <div class="modal-header py-4 px-5 border-b flex justify-between items-center">
            <div>
                <h5 class="modal-title">{{ translate('Set Risk Weights') }}</h5>
                <p class="text-sm text-gray-500 mt-1">
                    {{ translate('User ID:') }}
                    <span id="weight_user_id_display" class="font-semibold text-gray-800">—</span>
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-0 pb-5">
            <form id="weight_form" action="{{ route('riskWeights.store') }}" method="POST" class="px-5 pt-3 space-y-5">
                @csrf
                <input type="hidden" name="weight_id" id="weight_id" value="">
                <input type="hidden" name="user_id" id="weight_user_id" value=""> {{-- ✅ added user_id --}}

                {{-- ================= MAIN WEIGHTS ================= --}}
                <div class="border-b pb-3 mb-2">
                    <h6 class="font-semibold mb-3">{{ translate('Main Weights (Sum must be 95)') }}</h6>

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
                                class="input cr-sub" value="30">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('ID Expiry') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_id_expiry" id="cr_id_sub_id_expiry"
                                class="input cr-sub" value="20">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('CR Expiry') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_cr_expiry" id="cr_id_sub_cr_expiry"
                                class="input cr-sub" value="20">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Industry (Within CR)') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_industry" id="cr_id_sub_industry"
                                class="input cr-sub" value="15">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Activity Presence') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_activity" id="cr_id_sub_activity"
                                class="input cr-sub" value="15">
                        </div>
                        <div>
                            <label class="form-label">{{ translate('Total') }}</label>
                            <input type="number" step="0.01" name="cr_id_sub_total" id="cr_id_sub_total"
                                class="input bg-gray-100" value="100" readonly>
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
            const submitBtn = document.getElementById('weight_submit_btn');
            const storeUrl = "{{ route('riskWeights.store') }}";
            const updateBaseUrl = "{{ route('riskWeights.update', ':id') }}";
            const lastUrl = "{{ route('riskWeights.last') }}";

            // 🧮 Auto Calculate Total for CR/ID Sub-Weights
            function calculateCrTotal() {
                const fields = document.querySelectorAll('.cr-sub');
                let total = 0;
                fields.forEach(el => total += parseFloat(el.value || 0));
                document.getElementById('cr_id_sub_total').value = total.toFixed(2);
            }

            document.querySelectorAll('.cr-sub').forEach(input => {
                input.addEventListener('input', calculateCrTotal);
            });

            document.querySelectorAll('[data-modal-toggle="#weight_modal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');

                    // 🟢 Set both hidden field and header display
                    document.getElementById('weight_user_id').value = userId;
                    document.getElementById('weight_user_id_display').textContent = userId;

                    fetch(`${lastUrl}?user_id=${userId}`, {
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
                            const setVal = (id, val) => document.getElementById(id).value = w[
                                id] ?? val;

                            // Fill with existing or default values
                            setVal('cr_id', 25);
                            setVal('pos', 25);
                            setVal('repayment', 20);
                            setVal('industry', 15);
                            setVal('location', 10);

                            setVal('cr_id_sub_id_match', 30);
                            setVal('cr_id_sub_id_expiry', 20);
                            setVal('cr_id_sub_cr_expiry', 20);
                            setVal('cr_id_sub_industry', 15);
                            setVal('cr_id_sub_activity', 15);
                            calculateCrTotal();

                            setVal('pos_threshold', 50000);
                            setVal('repayment_few_threshold', 2);
                            setVal('repayment_score_no_delays', 20);
                            setVal('repayment_score_few_delays', 15);
                            setVal('repayment_score_many_delays', 5);

                            setVal('location_activity_max', 4.5);
                            setVal('location_default_rate_max', 4.5);
                            setVal('location_sub_total_max', 15);

                            document.getElementById('weight_id').value = w.id ?? '';
                        });
                });
            });


            // 🟢 Submit form
            weightForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitBtn.setAttribute('disabled', true);
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
                                })
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: json.message ??
                                    '{{ translate('Validation failed') }}'
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
                    .finally(() => submitBtn.removeAttribute('disabled'));
            });
        });
    </script>
@endpush
