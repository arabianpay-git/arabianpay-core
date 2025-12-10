@extends('layouts.base')

@push('styles')
    <style>
        .mt1 {
            margin-top: 1rem;
        }

        .total-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            font-weight: 600;
        }

        .total-display.valid {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }

        .total-display.invalid {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .weight-section {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: border-color 0.3s ease;
        }

        .weight-section.valid {
            border-color: #10b981;
        }

        .weight-section.invalid {
            border-color: #ef4444;
        }
    </style>
@endpush

@section('content')
    <main class="grow content pt-5">
        <div class="container-fixed">

            <div class="card pb-2.5">
                <div class="card-header">
                    <h3 class="card-title">{{ translate('Risk Weight Settings') }}</h3>
                </div>

                <form id="riskWeightsPageForm" action="{{ route('settings.risk-weight.store') }}" method="POST">
                    @csrf

                    <div class="card-body grid gap-5">

                        <div class="weight-section mb-6" id="main_weights_section">
                            <h6 class="font-semibold text-lg mb-2">{{ translate('Main Risk Weights') }}</h6>
                            <p class="text-sm text-gray-600 mb-3">{{ translate('Total must equal 100%') }}</p>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                                <div class="form-group">
                                    <label for="lps_weight" class="form-label">{{ translate('LPS Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" name="lps_weight"
                                        id="lps_weight" class="input main-weight" required
                                        value="{{ old('lps_weight', $values['lps_weight'] ?? $defaults['lps_weight']) }}">
                                    <small class="text-gray-500">{{ translate('Legal & Profile Score') }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="chs_weight" class="form-label">{{ translate('CHS Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" name="chs_weight"
                                        id="chs_weight" class="input main-weight" required
                                        value="{{ old('chs_weight', $values['chs_weight'] ?? $defaults['chs_weight']) }}">
                                    <small class="text-gray-500">{{ translate('Credit History Score') }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="bcs_weight" class="form-label">{{ translate('BCS Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" name="bcs_weight"
                                        id="bcs_weight" class="input main-weight" required
                                        value="{{ old('bcs_weight', $values['bcs_weight'] ?? $defaults['bcs_weight']) }}">
                                    <small class="text-gray-500">{{ translate('Banking & Cashflow Score') }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="bps_weight" class="form-label">{{ translate('BPS Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" name="bps_weight"
                                        id="bps_weight" class="input main-weight" required
                                        value="{{ old('bps_weight', $values['bps_weight'] ?? $defaults['bps_weight']) }}">
                                    <small class="text-gray-500">{{ translate('Business Profile Score') }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="bes_weight" class="form-label">{{ translate('BES Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" name="bes_weight"
                                        id="bes_weight" class="input main-weight" required
                                        value="{{ old('bes_weight', $values['bes_weight'] ?? $defaults['bes_weight']) }}">
                                    <small class="text-gray-500">{{ translate('Behavioral & Experience Score') }}</small>
                                </div>

                                <div class="form-group">
                                    <label for="caf_weight" class="form-label">{{ translate('CAF Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100" name="caf_weight"
                                        id="caf_weight" class="input main-weight" required
                                        value="{{ old('caf_weight', $values['caf_weight'] ?? $defaults['caf_weight']) }}">
                                    <small class="text-gray-500">{{ translate('Compliance Adjustment Factor') }}</small>
                                </div>
                            </div>

                            <div class="total-display" id="main_weights_total_display">
                                <div class="flex justify-between items-center">
                                    <span>{{ translate('Main Weights Total:') }}</span>
                                    <span id="main_weights_total">0.00%</span>
                                </div>
                                <div id="main_weights_status" class="text-sm mt-1"></div>
                            </div>
                        </div>

                        <div class="weight-section" id="lps_sub_section">
                            <h6 class="font-semibold mb-2">{{ translate('LPS Sub-weights') }}</h6>
                            <p class="text-sm text-gray-600 mb-3">{{ translate('Total must equal 100%') }}</p>

                            <div class="grid grid-cols-3 md:grid-cols-3 gap-4 mb-4">
                                <div class="form-group">
                                    <label for="lps_age_weight"
                                        class="form-label">{{ translate('Age Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="lps_age_weight" id="lps_age_weight" class="input lps-sub-weight" required
                                        value="{{ old('lps_age_weight', $values['lps_age_weight'] ?? $defaults['lps_age_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="lps_cr_weight"
                                        class="form-label">{{ translate('CR Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="lps_cr_weight" id="lps_cr_weight" class="input lps-sub-weight" required
                                        value="{{ old('lps_cr_weight', $values['lps_cr_weight'] ?? $defaults['lps_cr_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="lps_doc_weight"
                                        class="form-label">{{ translate('Document Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="lps_doc_weight" id="lps_doc_weight" class="input lps-sub-weight" required
                                        value="{{ old('lps_doc_weight', $values['lps_doc_weight'] ?? $defaults['lps_doc_weight']) }}">
                                </div>
                            </div>

                            <div class="total-display" id="lps_sub_total_display">
                                <div class="flex justify-between items-center">
                                    <span>{{ translate('LPS Sub-weights Total:') }}</span>
                                    <span id="lps_sub_total">0.00%</span>
                                </div>
                                <div id="lps_sub_status" class="text-sm mt-1"></div>
                            </div>
                        </div>

                        <div class="weight-section mt1" id="bcs_sub_section">
                            <h6 class="font-semibold mb-2">{{ translate('BCS Sub-weights') }}</h6>
                            <p class="text-sm text-gray-600 mb-3">{{ translate('Total must equal 100%') }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                <div class="form-group">
                                    <label for="bcs_turnover_weight"
                                        class="form-label">{{ translate('Turnover Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bcs_turnover_weight" id="bcs_turnover_weight" class="input bcs-sub-weight"
                                        required
                                        value="{{ old('bcs_turnover_weight', $values['bcs_turnover_weight'] ?? $defaults['bcs_turnover_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bcs_volatility_weight"
                                        class="form-label">{{ translate('Volatility Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bcs_volatility_weight" id="bcs_volatility_weight"
                                        class="input bcs-sub-weight" required
                                        value="{{ old('bcs_volatility_weight', $values['bcs_volatility_weight'] ?? $defaults['bcs_volatility_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bcs_returned_weight"
                                        class="form-label">{{ translate('Returned Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bcs_returned_weight" id="bcs_returned_weight" class="input bcs-sub-weight"
                                        required
                                        value="{{ old('bcs_returned_weight', $values['bcs_returned_weight'] ?? $defaults['bcs_returned_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bcs_balance_weight"
                                        class="form-label">{{ translate('Balance Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bcs_balance_weight" id="bcs_balance_weight" class="input bcs-sub-weight"
                                        required
                                        value="{{ old('bcs_balance_weight', $values['bcs_balance_weight'] ?? $defaults['bcs_balance_weight']) }}">
                                </div>
                            </div>

                            <div class="total-display" id="bcs_sub_total_display">
                                <div class="flex justify-between items-center">
                                    <span>{{ translate('BCS Sub-weights Total:') }}</span>
                                    <span id="bcs_sub_total">0.00%</span>
                                </div>
                                <div id="bcs_sub_status" class="text-sm mt-1"></div>
                            </div>
                        </div>

                        <div class="weight-section mt1" id="bps_sub_section">
                            <h6 class="font-semibold mb-2">{{ translate('BPS Sub-weights') }}</h6>
                            <p class="text-sm text-gray-600 mb-3">{{ translate('Total must equal 100%') }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label for="bps_sector_weight"
                                        class="form-label">{{ translate('Sector Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bps_sector_weight" id="bps_sector_weight" class="input bps-sub-weight"
                                        required
                                        value="{{ old('bps_sector_weight', $values['bps_sector_weight'] ?? $defaults['bps_sector_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bps_region_weight"
                                        class="form-label">{{ translate('Region Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bps_region_weight" id="bps_region_weight" class="input bps-sub-weight"
                                        required
                                        value="{{ old('bps_region_weight', $values['bps_region_weight'] ?? $defaults['bps_region_weight']) }}">
                                </div>
                            </div>

                            <div class="total-display" id="bps_sub_total_display">
                                <div class="flex justify-between items-center">
                                    <span>{{ translate('BPS Sub-weights Total:') }}</span>
                                    <span id="bps_sub_total">0.00%</span>
                                </div>
                                <div id="bps_sub_status" class="text-sm mt-1"></div>
                            </div>
                        </div>

                        <div class="weight-section mt1" id="bes_sub_section">
                            <h6 class="font-semibold mb-2">{{ translate('BES Sub-weights') }}</h6>
                            <p class="text-sm text-gray-600 mb-3">{{ translate('Total must equal 100%') }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                <div class="form-group">
                                    <label for="bes_dpd_weight"
                                        class="form-label">{{ translate('DPD Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bes_dpd_weight" id="bes_dpd_weight" class="input bes-sub-weight" required
                                        value="{{ old('bes_dpd_weight', $values['bes_dpd_weight'] ?? $defaults['bes_dpd_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bes_utilization_weight"
                                        class="form-label">{{ translate('Utilization Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bes_utilization_weight" id="bes_utilization_weight"
                                        class="input bes-sub-weight" required
                                        value="{{ old('bes_utilization_weight', $values['bes_utilization_weight'] ?? $defaults['bes_utilization_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bes_dispute_weight"
                                        class="form-label">{{ translate('Dispute Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bes_dispute_weight" id="bes_dispute_weight" class="input bes-sub-weight"
                                        required
                                        value="{{ old('bes_dispute_weight', $values['bes_dispute_weight'] ?? $defaults['bes_dispute_weight']) }}">
                                </div>
                                <div class="form-group">
                                    <label for="bes_trend_weight"
                                        class="form-label">{{ translate('Trend Weight (%)') }}</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="bes_trend_weight" id="bes_trend_weight" class="input bes-sub-weight"
                                        required
                                        value="{{ old('bes_trend_weight', $values['bes_trend_weight'] ?? $defaults['bes_trend_weight']) }}">
                                </div>
                            </div>

                            <div class="total-display" id="bes_sub_total_display">
                                <div class="flex justify-between items-center">
                                    <span>{{ translate('BES Sub-weights Total:') }}</span>
                                    <span id="bes_sub_total">0.00%</span>
                                </div>
                                <div id="bes_sub_status" class="text-sm mt-1"></div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 border rounded-lg bg-blue-50" id="validation_summary">
                            <h6 class="font-semibold mb-2">{{ translate('Validation Summary') }}</h6>
                            <div id="validation_status" class="text-sm">
                                {{ translate('Please adjust weights to meet all requirements') }}
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-4">
                            <div>
                                <button type="button" class="btn btn-light"
                                    id="resetWeightsBtn">{{ translate('Reset to Default') }}
                                </button>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" class="btn btn-light"
                                    id="cancelBtn">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary" id="saveWeightsBtn"
                                    disabled>{{ translate('Save Weights') }}
                                </button>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // server-provided values and defaults injected from controller
            const serverValues = @json($values ?? []);
            const defaultWeights = @json($defaults ?? []);

            // --------- DOM ----------
            const form = document.getElementById('riskWeightsPageForm');
            const saveBtn = document.getElementById('saveWeightsBtn');
            const resetBtn = document.getElementById('resetWeightsBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            // helper: load either saved values (serverValues) or defaults into DOM
            function loadDefaults(useServer = true) {
                const source = (useServer && Object.keys(serverValues).length) ? serverValues : defaultWeights;
                Object.keys(source).forEach(k => {
                    const el = document.querySelector(`[name="${k}"]`);
                    if (el) el.value = source[k];
                });
                calculateTotals();
            }

            // helper: calculate totals & update UI
            function updateValidationStatus() {
                const mainTotal = parseFloat(document.getElementById('main_weights_total').textContent) || 0;
                const lpsTotal = parseFloat(document.getElementById('lps_sub_total').textContent) || 0;
                const bcsTotal = parseFloat(document.getElementById('bcs_sub_total').textContent) || 0;
                const bpsTotal = parseFloat(document.getElementById('bps_sub_total').textContent) || 0;
                const besTotal = parseFloat(document.getElementById('bes_sub_total').textContent) || 0;

                const isMainValid = Math.abs(mainTotal - 100) <= 0.01;
                const isLpsValid = Math.abs(lpsTotal - 100) <= 0.01;
                const isBcsValid = Math.abs(bcsTotal - 100) <= 0.01;
                const isBpsValid = Math.abs(bpsTotal - 100) <= 0.01;
                const isBesValid = Math.abs(besTotal - 100) <= 0.01;

                const allValid = isMainValid && isLpsValid && isBcsValid && isBpsValid && isBesValid;

                saveBtn.disabled = !allValid;

                const validationSummary = document.getElementById('validation_summary');
                const validationStatus = document.getElementById('validation_status');

                if (allValid) {
                    validationSummary.className = 'mt-6 p-4 border rounded-lg bg-green-50 border-green-200';
                    validationStatus.innerHTML =
                        '<span class="text-green-700">✓ {{ translate('All weights are properly configured and ready to save.') }}</span>';
                } else {
                    validationSummary.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 border-yellow-200';
                    let issues = [];
                    if (!isMainValid) issues.push('{{ translate('Main weights must total 100%') }}');
                    if (!isLpsValid) issues.push('{{ translate('LPS sub-weights must total 100%') }}');
                    if (!isBcsValid) issues.push('{{ translate('BCS sub-weights must total 100%') }}');
                    if (!isBpsValid) issues.push('{{ translate('BPS sub-weights must total 100%') }}');
                    if (!isBesValid) issues.push('{{ translate('BES sub-weights must total 100%') }}');

                    validationStatus.innerHTML =
                        '<span class="text-yellow-700">{{ translate('Please fix the following issues:') }}</span><ul class="list-disc list-inside mt-1">' +
                        issues.map(i => `<li class="text-sm">${i}</li>`).join('') + '</ul>';
                }

                return allValid;
            }

            function calculateTotals() {
                const sections = [{
                        selector: '.main-weight',
                        totalId: 'main_weights_total',
                        displayId: 'main_weights_total_display',
                        statusId: 'main_weights_status',
                        sectionId: 'main_weights_section'
                    },
                    {
                        selector: '.lps-sub-weight',
                        totalId: 'lps_sub_total',
                        displayId: 'lps_sub_total_display',
                        statusId: 'lps_sub_status',
                        sectionId: 'lps_sub_section'
                    },
                    {
                        selector: '.bcs-sub-weight',
                        totalId: 'bcs_sub_total',
                        displayId: 'bcs_sub_total_display',
                        statusId: 'bcs_sub_status',
                        sectionId: 'bcs_sub_section'
                    },
                    {
                        selector: '.bps-sub-weight',
                        totalId: 'bps_sub_total',
                        displayId: 'bps_sub_total_display',
                        statusId: 'bps_sub_status',
                        sectionId: 'bps_sub_section'
                    },
                    {
                        selector: '.bes-sub-weight',
                        totalId: 'bes_sub_total',
                        displayId: 'bes_sub_total_display',
                        statusId: 'bes_sub_status',
                        sectionId: 'bes_sub_section'
                    }
                ];

                sections.forEach(sec => {
                    const weights = document.querySelectorAll(sec.selector);
                    let total = 0;
                    weights.forEach(input => total += parseFloat(input.value) || 0);

                    document.getElementById(sec.totalId).textContent = total.toFixed(2) + '%';
                    const displayEl = document.getElementById(sec.displayId);
                    const statusEl = document.getElementById(sec.statusId);
                    const sectionEl = document.getElementById(sec.sectionId);

                    if (Math.abs(total - 100) <= 0.01) {
                        displayEl.className = 'total-display valid';
                        statusEl.innerHTML =
                            '<span class="text-green-600">✓ {{ translate('Perfect! Total is 100%') }}</span>';
                        sectionEl.className = 'weight-section valid';
                    } else {
                        const difference = (100 - total).toFixed(2);
                        const statusText = total < 100 ? '{{ translate('short') }}' :
                            '{{ translate('over') }}';
                        displayEl.className = 'total-display invalid';
                        statusEl.innerHTML =
                            `<span class="text-red-600">✗ {{ translate('Needs adjustment') }} (${difference}% ${statusText})</span>`;
                        sectionEl.className = 'weight-section invalid';
                    }
                });

                updateValidationStatus();
            }

            // attach listeners for all number inputs
            document.querySelectorAll('#riskWeightsPageForm input[type="number"]').forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // reset to defaults (from server injected defaultWeights)
            // reset to defaults (from server injected defaultWeights) and save immediately
            resetBtn.addEventListener('click', function() {
                Swal.fire({
                    title: '{{ translate('Reset all weights?') }}',
                    text: '{{ translate('This will reset all weights to default values and save them automatically.') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ translate('Yes, reset') }}'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        loadDefaults(false); // load defaultWeights into form

                        // prepare payload from form values
                        const fd = new FormData(form);
                        const payload = Object.fromEntries(fd.entries());
                        Object.keys(payload).forEach(k => {
                            payload[k] = parseFloat(payload[k]) || 0;
                            payload[k] = Number(payload[k].toFixed(2));
                        });

                        saveBtn.disabled = true;
                        saveBtn.textContent = '{{ translate('Saving...') }}';

                        try {
                            const res = await fetch(
                                "{{ route('settings.risk-weight.store') }}", {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]')?.getAttribute(
                                            'content') || '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify(payload)
                                });

                            saveBtn.disabled = false;
                            saveBtn.textContent = '{{ translate('Save Weights') }}';

                            if (res.ok) {
                                const data = await res.json();
                                Swal.fire({
                                    title: '{{ translate('Reset & Saved') }}',
                                    text: data.message ||
                                        '{{ translate('All weights have been reset to default and saved successfully.') }}',
                                    icon: 'success',
                                });
                                Object.assign(serverValues, payload); // update serverValues
                            } else {
                                const text = await res.text();
                                Swal.fire({
                                    title: '{{ translate('Error') }}',
                                    html: text ||
                                        '{{ translate('An unexpected error occurred while saving.') }}',
                                    icon: 'error'
                                });
                            }

                        } catch (err) {
                            saveBtn.disabled = false;
                            saveBtn.textContent = '{{ translate('Save Weights') }}';
                            Swal.fire({
                                title: '{{ translate('Network Error') }}',
                                text: err.message ||
                                    '{{ translate('Could not reach the server.') }}',
                                icon: 'error'
                            });
                        }
                    }
                });
            });

            // cancel (just reload page)
            cancelBtn.addEventListener('click', function() {
                Swal.fire({
                    title: '{{ translate('Discard changes?') }}',
                    text: '{{ translate('Are you sure you want to discard changes and reload?') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ translate('Yes, reload') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            });

            // form submit (AJAX)
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!updateValidationStatus()) {
                    Swal.fire({
                        title: '{{ translate('Validation Error') }}',
                        html: '{{ translate('Please fix validation issues before saving.') }}',
                        icon: 'error',
                    });
                    return;
                }

                const fd = new FormData(form);
                const payload = Object.fromEntries(fd.entries());

                // Convert values to floats with two decimals
                Object.keys(payload).forEach(k => {
                    payload[k] = parseFloat(payload[k]) || 0;
                    payload[k] = Number(payload[k].toFixed(2));
                });

                saveBtn.disabled = true;
                const originalSaveText = saveBtn.textContent;
                saveBtn.textContent = '{{ translate('Saving...') }}';

                fetch("{{ route('settings.risk-weight.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).then(async res => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalSaveText ||
                        '{{ translate('Save Weights') }}';

                    // examine content-type BEFORE calling res.json()
                    const contentType = (res.headers.get('content-type') || '').toLowerCase();

                    // if server returned JSON
                    if (contentType.includes('application/json')) {
                        let data;
                        try {
                            data = await res.json();
                        } catch (err) {
                            // parsing error - fallback to text
                            const txt = await res.text().catch(() => '');
                            Swal.fire({
                                title: '{{ translate('Error') }}',
                                html: txt ||
                                    '{{ translate('Invalid JSON response from server.') }}',
                                icon: 'error'
                            });
                            return;
                        }

                        if (res.ok) {
                            Swal.fire({
                                title: '{{ translate('Saved') }}',
                                text: data.message ||
                                    '{{ translate('Weights saved successfully') }}',
                                icon: 'success',
                            });
                            Object.assign(serverValues, payload);
                            return;
                        }

                        // status not ok (e.g., 422) with JSON payload
                        const message = data.message ||
                            '{{ translate('Validation failed') }}';
                        let details = '';
                        if (data.errors) {
                            if (typeof data.errors === 'object') {
                                const arr = [];
                                Object.keys(data.errors).forEach(k => {
                                    const v = data.errors[k];
                                    if (Array.isArray(v)) arr.push(...v);
                                    else arr.push(v);
                                });
                                details = arr.join('<br/>');
                            } else {
                                details = String(data.errors);
                            }
                        }
                        Swal.fire({
                            title: message,
                            html: details ||
                                '{{ translate('Please fix the issues and try again.') }}',
                            icon: 'error'
                        });
                        return;
                    }

                    // If not JSON (likely HTML), parse as text and show helpful message.
                    const text = await res.text().catch(() => '');
                    // If the server redirected to login page, show a clear message
                    if (res.redirected || text.toLowerCase().includes('<!doctype') || text
                        .toLowerCase().includes('<html')) {
                        Swal.fire({
                            title: '{{ translate('Session or Redirect') }}',
                            html: '{{ translate('Server returned HTML (possible session timeout or redirect). Please reload the page and try again.') }}',
                            icon: 'warning'
                        });
                        return;
                    }

                    // final fallback
                    Swal.fire({
                        title: '{{ translate('Error') }}',
                        html: text ||
                            '{{ translate('An unexpected error occurred.') }}',
                        icon: 'error'
                    });

                }).catch(err => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalSaveText || '{{ translate('Save Weights') }}';
                    Swal.fire({
                        title: '{{ translate('Network Error') }}',
                        text: err.message ||
                            '{{ translate('Could not reach the server.') }}',
                        icon: 'error'
                    });
                });
            });

            // initialize page with server values (if saved) otherwise defaults
            loadDefaults(true);
        });
    </script>
@endpush
