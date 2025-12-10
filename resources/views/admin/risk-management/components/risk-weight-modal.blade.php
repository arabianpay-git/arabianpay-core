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

        .custom-alert {
            border-left: 4px solid #f59e0b;
            background-color: #fffbeb;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-alert i {
            font-size: 1.25rem;
        }

        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 10px;
        }

        /* Ensure d-none class works */
        .d-none {
            display: none !important;
        }
    </style>
@endpush

<!-- Risk Weights Modal -->
<div class="modal" data-modal="true" id="risk_weight_modal">
    <div class="modal-content max-w-[900px] top-[2%] max-h-[95vh] overflow-hidden">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Set Risk Weights') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 overflow-auto">
            <form id="riskWeightsForm" class="p-5">
                <input type="hidden" name="user_id" id="risk_weight_user_id">

                <!-- Weights Source Info -->
                <div id="weights_source_info" class="info-badge mb-4 d-none">
                    <i class="ki-duotone ki-information-2 fs-3 text-primary"></i>
                    <span id="weights_source_text">Loading weights source...</span>
                </div>

                <!-- Warning Alert for Existing User Weights -->
                <div id="existing_weights_warning" class="custom-alert d-none">
                    <i class="ki-duotone ki-information fs-3 text-warning"></i>
                    <div>
                        <strong>Note:</strong> This user already has custom risk weights. The values below are specific
                        to this user.
                        To use system defaults instead, click "Reset to Default".
                    </div>
                </div>

                <!-- Main Weights Section -->
                <div class="weight-section mb-6" id="main_weights_section">
                    <h6 class="font-semibold text-lg mb-4 border-b pb-2">Main Risk Weights</h6>
                    <p class="text-sm text-gray-600 mb-4">Total must equal 100%</p>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                        <!-- LPS Weight -->
                        <div class="form-group">
                            <label for="lps_weight" class="form-label">LPS Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="lps_weight"
                                id="lps_weight" class="input main-weight" required>
                            <small class="text-gray-500">Legal & Profile Score</small>
                        </div>

                        <!-- CHS Weight -->
                        <div class="form-group">
                            <label for="chs_weight" class="form-label">CHS Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="chs_weight"
                                id="chs_weight" class="input main-weight" required>
                            <small class="text-gray-500">Credit History Score</small>
                        </div>

                        <!-- BCS Weight -->
                        <div class="form-group">
                            <label for="bcs_weight" class="form-label">BCS Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="bcs_weight"
                                id="bcs_weight" class="input main-weight" required>
                            <small class="text-gray-500">Banking & Cashflow Score</small>
                        </div>

                        <!-- BPS Weight -->
                        <div class="form-group">
                            <label for="bps_weight" class="form-label">BPS Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="bps_weight"
                                id="bps_weight" class="input main-weight" required>
                            <small class="text-gray-500">Business Profile Score</small>
                        </div>

                        <!-- BES Weight -->
                        <div class="form-group">
                            <label for="bes_weight" class="form-label">BES Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="bes_weight"
                                id="bes_weight" class="input main-weight" required>
                            <small class="text-gray-500">Behavioral & Experience Score</small>
                        </div>

                        <!-- CAF Weight -->
                        <div class="form-group">
                            <label for="caf_weight" class="form-label">CAF Weight (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="caf_weight"
                                id="caf_weight" class="input main-weight" required>
                            <small class="text-gray-500">Compliance Adjustment Factor</small>
                        </div>
                    </div>

                    <!-- Main Weights Total Display -->
                    <div class="total-display" id="main_weights_total_display">
                        <div class="flex justify-between items-center">
                            <span>Main Weights Total:</span>
                            <span id="main_weights_total">0.00%</span>
                        </div>
                        <div id="main_weights_status" class="text-sm mt-1"></div>
                    </div>
                </div>

                <!-- Sub-weights Sections -->
                <div class="space-y-6">
                    <!-- LPS Sub-weights -->
                    <div class="weight-section border rounded-lg p-4" id="lps_sub_section">
                        <h6 class="font-semibold mb-3">LPS Sub-weights</h6>
                        <p class="text-sm text-gray-600 mb-3">Total must equal 100%</p>

                        <div class="grid grid-cols-3 md:grid-cols-3 gap-4 mb-4">
                            <div class="form-group">
                                <label for="lps_age_weight" class="form-label">Age Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="lps_age_weight" id="lps_age_weight" class="input lps-sub-weight" required>
                            </div>
                            <div class="form-group">
                                <label for="lps_cr_weight" class="form-label">CR Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="lps_cr_weight" id="lps_cr_weight" class="input lps-sub-weight" required>
                            </div>
                            <div class="form-group">
                                <label for="lps_doc_weight" class="form-label">Document Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="lps_doc_weight" id="lps_doc_weight" class="input lps-sub-weight" required>
                            </div>
                        </div>

                        <!-- LPS Sub-weights Total Display -->
                        <div class="total-display" id="lps_sub_total_display">
                            <div class="flex justify-between items-center">
                                <span>LPS Sub-weights Total:</span>
                                <span id="lps_sub_total">0.00%</span>
                            </div>
                            <div id="lps_sub_status" class="text-sm mt-1"></div>
                        </div>
                    </div>

                    <!-- BCS Sub-weights -->
                    <div class="weight-section border rounded-lg p-4 mt1" id="bcs_sub_section">
                        <h6 class="font-semibold mb-3">BCS Sub-weights</h6>
                        <p class="text-sm text-gray-600 mb-3">Total must equal 100%</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div class="form-group">
                                <label for="bcs_turnover_weight" class="form-label">Turnover Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bcs_turnover_weight" id="bcs_turnover_weight" class="input bcs-sub-weight"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="bcs_volatility_weight" class="form-label">Volatility Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bcs_volatility_weight" id="bcs_volatility_weight"
                                    class="input bcs-sub-weight" required>
                            </div>
                            <div class="form-group">
                                <label for="bcs_returned_weight" class="form-label">Returned Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bcs_returned_weight" id="bcs_returned_weight" class="input bcs-sub-weight"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="bcs_balance_weight" class="form-label">Balance Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bcs_balance_weight" id="bcs_balance_weight" class="input bcs-sub-weight"
                                    required>
                            </div>
                        </div>

                        <!-- BCS Sub-weights Total Display -->
                        <div class="total-display" id="bcs_sub_total_display">
                            <div class="flex justify-between items-center">
                                <span>BCS Sub-weights Total:</span>
                                <span id="bcs_sub_total">0.00%</span>
                            </div>
                            <div id="bcs_sub_status" class="text-sm mt-1"></div>
                        </div>
                    </div>

                    <!-- BPS Sub-weights -->
                    <div class="weight-section border rounded-lg p-4 mt1" id="bps_sub_section">
                        <h6 class="font-semibold mb-3">BPS Sub-weights</h6>
                        <p class="text-sm text-gray-600 mb-3">Total must equal 100%</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label for="bps_sector_weight" class="form-label">Sector Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bps_sector_weight" id="bps_sector_weight" class="input bps-sub-weight"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="bps_region_weight" class="form-label">Region Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bps_region_weight" id="bps_region_weight" class="input bps-sub-weight"
                                    required>
                            </div>
                        </div>

                        <!-- BPS Sub-weights Total Display -->
                        <div class="total-display" id="bps_sub_total_display">
                            <div class="flex justify-between items-center">
                                <span>BPS Sub-weights Total:</span>
                                <span id="bps_sub_total">0.00%</span>
                            </div>
                            <div id="bps_sub_status" class="text-sm mt-1"></div>
                        </div>
                    </div>

                    <!-- BES Sub-weights -->
                    <div class="weight-section border rounded-lg p-4 mt1" id="bes_sub_section">
                        <h6 class="font-semibold mb-3">BES Sub-weights</h6>
                        <p class="text-sm text-gray-600 mb-3">Total must equal 100%</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div class="form-group">
                                <label for="bes_dpd_weight" class="form-label">DPD Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bes_dpd_weight" id="bes_dpd_weight" class="input bes-sub-weight" required>
                            </div>
                            <div class="form-group">
                                <label for="bes_utilization_weight" class="form-label">Utilization Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bes_utilization_weight" id="bes_utilization_weight"
                                    class="input bes-sub-weight" required>
                            </div>
                            <div class="form-group">
                                <label for="bes_dispute_weight" class="form-label">Dispute Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bes_dispute_weight" id="bes_dispute_weight" class="input bes-sub-weight"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="bes_trend_weight" class="form-label">Trend Weight (%)</label>
                                <input type="number" step="0.01" min="0" max="100"
                                    name="bes_trend_weight" id="bes_trend_weight" class="input bes-sub-weight"
                                    required>
                            </div>
                        </div>

                        <!-- BES Sub-weights Total Display -->
                        <div class="total-display" id="bes_sub_total_display">
                            <div class="flex justify-between items-center">
                                <span>BES Sub-weights Total:</span>
                                <span id="bes_sub_total">0.00%</span>
                            </div>
                            <div id="bes_sub_status" class="text-sm mt-1"></div>
                        </div>
                    </div>
                </div>

                <!-- Overall Validation Summary -->
                <div class="mt-6 p-4 border rounded-lg bg-blue-50" id="validation_summary">
                    <h6 class="font-semibold mb-2">Validation Summary</h6>
                    <div id="validation_status" class="text-sm">
                        Please adjust weights to meet all requirements
                    </div>
                </div>

                <div class="modal-footer mt-6 flex justify-between items-center mt1">
                    <div>
                        <button type="button" class="btn btn-light" id="resetWeightsBtn">
                            Reset to Default
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveWeightsBtn" disabled>
                            Save Weights
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $defaultWeights = \App\Models\Setting::getByKey('risk_weights', [
        'bcs_weight' => 20,
        'bes_weight' => 30,
        'bps_weight' => 10,
        'caf_weight' => 0,
        'chs_weight' => 25,
        'lps_weight' => 15,
        'lps_cr_weight' => 30,
        'bes_dpd_weight' => 40,
        'lps_age_weight' => 40,
        'lps_doc_weight' => 30,
        'bes_trend_weight' => 10,
        'bps_region_weight' => 30,
        'bps_sector_weight' => 70,
        'bcs_balance_weight' => 15,
        'bes_dispute_weight' => 25,
        'bcs_returned_weight' => 25,
        'bcs_turnover_weight' => 35,
        'bcs_volatility_weight' => 25,
        'bes_utilization_weight' => 25,
    ]);
@endphp

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weightModal = document.getElementById('risk_weight_modal');
            const riskWeightsForm = document.getElementById('riskWeightsForm');
            const resetWeightsBtn = document.getElementById('resetWeightsBtn');
            const saveWeightsBtn = document.getElementById('saveWeightsBtn');
            const validationSummary = document.getElementById('validation_summary');
            const validationStatus = document.getElementById('validation_status');
            const weightsSourceInfo = document.getElementById('weights_source_info');
            const weightsSourceText = document.getElementById('weights_source_text');
            const existingWeightsWarning = document.getElementById('existing_weights_warning');

            // PHP variables for defaults from settings
            const defaultWeights = @json($defaultWeights);

            function showAlert(icon, title, text) {
                return Swal.fire({
                    icon: icon,
                    title: title,
                    text: text,
                    confirmButtonColor: '#3085d6',
                });
            }

            function updateValidationStatus() {
                const mainTotal = parseFloat(document.getElementById('main_weights_total').textContent);
                const lpsTotal = parseFloat(document.getElementById('lps_sub_total').textContent);
                const bcsTotal = parseFloat(document.getElementById('bcs_sub_total').textContent);
                const bpsTotal = parseFloat(document.getElementById('bps_sub_total').textContent);
                const besTotal = parseFloat(document.getElementById('bes_sub_total').textContent);

                const isMainValid = Math.abs(mainTotal - 100) <= 0.01;
                const isLpsValid = Math.abs(lpsTotal - 100) <= 0.01;
                const isBcsValid = Math.abs(bcsTotal - 100) <= 0.01;
                const isBpsValid = Math.abs(bpsTotal - 100) <= 0.01;
                const isBesValid = Math.abs(besTotal - 100) <= 0.01;

                const allValid = isMainValid && isLpsValid && isBcsValid && isBpsValid && isBesValid;

                // Update save button
                saveWeightsBtn.disabled = !allValid;

                // Update validation summary
                if (allValid) {
                    validationSummary.className = 'mt-6 p-4 border rounded-lg bg-green-50 border-green-200';
                    validationStatus.innerHTML =
                        '<span class="text-green-700">✓ All weights are properly configured and ready to save.</span>';
                } else {
                    validationSummary.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 border-yellow-200';
                    let issues = [];
                    if (!isMainValid) issues.push('Main weights must total 100%');
                    if (!isLpsValid) issues.push('LPS sub-weights must total 100%');
                    if (!isBcsValid) issues.push('BCS sub-weights must total 100%');
                    if (!isBpsValid) issues.push('BPS sub-weights must total 100%');
                    if (!isBesValid) issues.push('BES sub-weights must total 100%');

                    validationStatus.innerHTML =
                        '<span class="text-yellow-700">Please fix the following issues:</span><ul class="list-disc list-inside mt-1">' +
                        issues.map(issue => `<li class="text-sm">${issue}</li>`).join('') + '</ul>';
                }

                return allValid;
            }

            function calculateTotals() {
                // Main weights
                const mainWeights = document.querySelectorAll('.main-weight');
                let mainTotal = 0;
                mainWeights.forEach(input => {
                    mainTotal += parseFloat(input.value) || 0;
                });

                const mainTotalElement = document.getElementById('main_weights_total');
                const mainTotalDisplay = document.getElementById('main_weights_total_display');
                const mainStatusElement = document.getElementById('main_weights_status');
                const mainSection = document.getElementById('main_weights_section');

                mainTotalElement.textContent = mainTotal.toFixed(2) + '%';

                if (Math.abs(mainTotal - 100) <= 0.01) {
                    mainTotalDisplay.className = 'total-display valid';
                    mainStatusElement.innerHTML = '<span class="text-green-600">✓ Perfect! Total is 100%</span>';
                    mainSection.className = 'weight-section mb-6 valid';
                } else {
                    mainTotalDisplay.className = 'total-display invalid';
                    mainStatusElement.innerHTML =
                        `<span class="text-red-600">✗ Needs adjustment (${(100 - mainTotal).toFixed(2)}% ${mainTotal < 100 ? 'short' : 'over'})</span>`;
                    mainSection.className = 'weight-section mb-6 invalid';
                }

                // LPS sub-weights
                const lpsWeights = document.querySelectorAll('.lps-sub-weight');
                let lpsTotal = 0;
                lpsWeights.forEach(input => {
                    lpsTotal += parseFloat(input.value) || 0;
                });

                const lpsTotalElement = document.getElementById('lps_sub_total');
                const lpsTotalDisplay = document.getElementById('lps_sub_total_display');
                const lpsStatusElement = document.getElementById('lps_sub_status');
                const lpsSection = document.getElementById('lps_sub_section');

                lpsTotalElement.textContent = lpsTotal.toFixed(2) + '%';

                if (Math.abs(lpsTotal - 100) <= 0.01) {
                    lpsTotalDisplay.className = 'total-display valid';
                    lpsStatusElement.innerHTML = '<span class="text-green-600">✓ Perfect! Total is 100%</span>';
                    lpsSection.className = 'weight-section border rounded-lg p-4 valid';
                } else {
                    lpsTotalDisplay.className = 'total-display invalid';
                    lpsStatusElement.innerHTML =
                        `<span class="text-red-600">✗ Needs adjustment (${(100 - lpsTotal).toFixed(2)}% ${lpsTotal < 100 ? 'short' : 'over'})</span>`;
                    lpsSection.className = 'weight-section border rounded-lg p-4 invalid';
                }

                // BCS sub-weights
                const bcsWeights = document.querySelectorAll('.bcs-sub-weight');
                let bcsTotal = 0;
                bcsWeights.forEach(input => {
                    bcsTotal += parseFloat(input.value) || 0;
                });

                const bcsTotalElement = document.getElementById('bcs_sub_total');
                const bcsTotalDisplay = document.getElementById('bcs_sub_total_display');
                const bcsStatusElement = document.getElementById('bcs_sub_status');
                const bcsSection = document.getElementById('bcs_sub_section');

                bcsTotalElement.textContent = bcsTotal.toFixed(2) + '%';

                if (Math.abs(bcsTotal - 100) <= 0.01) {
                    bcsTotalDisplay.className = 'total-display valid';
                    bcsStatusElement.innerHTML = '<span class="text-green-600">✓ Perfect! Total is 100%</span>';
                    bcsSection.className = 'weight-section border rounded-lg p-4 mt1 valid';
                } else {
                    bcsTotalDisplay.className = 'total-display invalid';
                    bcsStatusElement.innerHTML =
                        `<span class="text-red-600">✗ Needs adjustment (${(100 - bcsTotal).toFixed(2)}% ${bcsTotal < 100 ? 'short' : 'over'})</span>`;
                    bcsSection.className = 'weight-section border rounded-lg p-4 mt1 invalid';
                }

                // BPS sub-weights
                const bpsWeights = document.querySelectorAll('.bps-sub-weight');
                let bpsTotal = 0;
                bpsWeights.forEach(input => {
                    bpsTotal += parseFloat(input.value) || 0;
                });

                const bpsTotalElement = document.getElementById('bps_sub_total');
                const bpsTotalDisplay = document.getElementById('bps_sub_total_display');
                const bpsStatusElement = document.getElementById('bps_sub_status');
                const bpsSection = document.getElementById('bps_sub_section');

                bpsTotalElement.textContent = bpsTotal.toFixed(2) + '%';

                if (Math.abs(bpsTotal - 100) <= 0.01) {
                    bpsTotalDisplay.className = 'total-display valid';
                    bpsStatusElement.innerHTML = '<span class="text-green-600">✓ Perfect! Total is 100%</span>';
                    bpsSection.className = 'weight-section border rounded-lg p-4 mt1 valid';
                } else {
                    bpsTotalDisplay.className = 'total-display invalid';
                    bpsStatusElement.innerHTML =
                        `<span class="text-red-600">✗ Needs adjustment (${(100 - bpsTotal).toFixed(2)}% ${bpsTotal < 100 ? 'short' : 'over'})</span>`;
                    bpsSection.className = 'weight-section border rounded-lg p-4 mt1 invalid';
                }

                // BES sub-weights
                const besWeights = document.querySelectorAll('.bes-sub-weight');
                let besTotal = 0;
                besWeights.forEach(input => {
                    besTotal += parseFloat(input.value) || 0;
                });

                const besTotalElement = document.getElementById('bes_sub_total');
                const besTotalDisplay = document.getElementById('bes_sub_total_display');
                const besStatusElement = document.getElementById('bes_sub_status');
                const besSection = document.getElementById('bes_sub_section');

                besTotalElement.textContent = besTotal.toFixed(2) + '%';

                if (Math.abs(besTotal - 100) <= 0.01) {
                    besTotalDisplay.className = 'total-display valid';
                    besStatusElement.innerHTML = '<span class="text-green-600">✓ Perfect! Total is 100%</span>';
                    besSection.className = 'weight-section border rounded-lg p-4 mt1 valid';
                } else {
                    besTotalDisplay.className = 'total-display invalid';
                    besStatusElement.innerHTML =
                        `<span class="text-red-600">✗ Needs adjustment (${(100 - besTotal).toFixed(2)}% ${besTotal < 100 ? 'short' : 'over'})</span>`;
                    besSection.className = 'weight-section border rounded-lg p-4 mt1 invalid';
                }

                // Update overall validation
                updateValidationStatus();
            }

            // Function to show user has custom weights
            function showCustomWeightsWarning() {
                console.log('Showing custom weights warning');
                weightsSourceInfo.classList.remove('d-none');
                weightsSourceText.textContent = 'This user has custom risk weights';
                weightsSourceInfo.className = 'info-badge mb-4 bg-amber-50 text-amber-700';
                existingWeightsWarning.classList.remove('d-none');
            }

            // Function to show user is using defaults from settings
            function showSettingsDefaultsWarning() {
                console.log('Showing settings defaults warning');
                weightsSourceInfo.classList.remove('d-none');
                weightsSourceText.textContent = 'Using system default weights from settings';
                weightsSourceInfo.className = 'info-badge mb-4 bg-blue-50 text-blue-700';
                existingWeightsWarning.classList.add('d-none');
            }

            // Function to show user is using fallback defaults
            function showFallbackDefaultsWarning() {
                console.log('Showing fallback defaults warning');
                weightsSourceInfo.classList.remove('d-none');
                weightsSourceText.textContent = 'Using fallback default weights';
                weightsSourceInfo.className = 'info-badge mb-4 bg-gray-50 text-gray-700';
                existingWeightsWarning.classList.add('d-none');
            }

            // Attach event listeners to all weight inputs
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            document.querySelectorAll('[data-modal-toggle="#risk_weight_modal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    if (!userId) {
                        showAlert('error', 'Error', 'User ID not found');
                        return;
                    }

                    document.getElementById('risk_weight_user_id').value = userId;

                    const saveBtn = document.getElementById('saveWeightsBtn');
                    const originalText = saveBtn.innerHTML;
                    saveBtn.innerHTML = 'Loading...';
                    saveBtn.disabled = true;

                    const url = "{{ route('risk-weights.get') }}" + `?user_id=${userId}`;

                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Weights data received:', data);
                            if (data.success) {
                                const weights = data.weights;
                                const source = data.source || 'settings';

                                // Fill form with weights
                                Object.keys(weights).forEach(key => {
                                    const input = document.querySelector(
                                        `[name="${key}"]`);
                                    if (input && weights[key] !== null && weights[
                                            key] !== undefined) {
                                        input.value = weights[key];
                                    }
                                });

                                // Update source info and warnings based on source
                                switch (source) {
                                    case 'database':
                                        showCustomWeightsWarning();
                                        break;
                                    case 'settings':
                                        showSettingsDefaultsWarning();
                                        break;
                                    case 'fallback':
                                        showFallbackDefaultsWarning();
                                        break;
                                    default:
                                        showSettingsDefaultsWarning();
                                }

                                calculateTotals();
                                weightModal.style.display = 'block';
                            } else {
                                // If API fails, show error but still open modal with defaults
                                showSettingsDefaultsWarning();
                                showAlert('warning', 'Notice', data.message ||
                                    'Failed to load weights, using defaults');
                                weightModal.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching weights:', error);
                            // On error, load default weights
                            Object.keys(defaultWeights).forEach(key => {
                                const input = document.querySelector(`[name="${key}"]`);
                                if (input) {
                                    input.value = defaultWeights[key];
                                }
                            });
                            showSettingsDefaultsWarning();
                            calculateTotals();
                            weightModal.style.display = 'block';
                            showAlert('error', 'Error',
                                'Failed to load user weights. Using system defaults.');
                        })
                        .finally(() => {
                            saveBtn.innerHTML = originalText;
                        });
                });
            });

            // RESET WEIGHTS
            resetWeightsBtn.addEventListener('click', function() {
                const userId = document.getElementById('risk_weight_user_id').value;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to reset all weights to system default values?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, reset it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const resetBtn = resetWeightsBtn;
                        const originalText = resetBtn.innerHTML;
                        resetBtn.innerHTML = 'Resetting...';
                        resetBtn.disabled = true;

                        fetch("{{ route('risk-weights.reset') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    user_id: userId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Reset response:', data);
                                if (data.success) {
                                    showAlert('success', 'Success!',
                                        'Weights reset to system defaults successfully');

                                    // Apply the returned weights
                                    Object.keys(data.weights).forEach(key => {
                                        const input = document.querySelector(
                                            `[name="${key}"]`);
                                        if (input && data.weights[key] !== null) {
                                            input.value = data.weights[key];
                                        }
                                    });

                                    // Update source info based on the source
                                    if (data.source === 'settings') {
                                        showSettingsDefaultsWarning();
                                    } else {
                                        showFallbackDefaultsWarning();
                                    }
                                    calculateTotals();
                                } else {
                                    showAlert('error', 'Error', data.message ||
                                        'Failed to reset weights');
                                }
                            })
                            .finally(() => {
                                resetBtn.innerHTML = originalText;
                                resetBtn.disabled = false;
                            });
                    }
                });
            });

            // FORM SUBMIT (SAVE)
            riskWeightsForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate before submission
                if (!updateValidationStatus()) {
                    showAlert('error', 'Validation Error',
                        'Please fix all weight validation issues before saving.');
                    return;
                }

                const submitBtn = document.getElementById('saveWeightsBtn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = 'Saving...';
                submitBtn.disabled = true;

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                fetch("{{ route('risk-weights.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('success', 'Success!', 'Weights saved successfully!')
                                .then(() => {
                                    // Update source info to show custom weights
                                    showCustomWeightsWarning();
                                });
                        } else {
                            showAlert('error', 'Error', data.message || 'Failed to save weights');
                        }
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });

            weightModal.addEventListener('click', function(e) {
                if (e.target === weightModal || e.target.closest('[data-modal-dismiss="true"]')) {
                    weightModal.style.display = 'none';
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && weightModal.style.display === 'block') {
                    weightModal.style.display = 'none';
                }
            });
        });
    </script>
@endpush
