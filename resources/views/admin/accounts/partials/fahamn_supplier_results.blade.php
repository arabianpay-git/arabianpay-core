<div class="flex items-start gap-4 mt-5">
    <!-- Fahman Illustration -->
    <div class="shrink-0">
        <img src="{{ asset('assets/media/fahman/' . $fahmanAdvice['level'] . '.png') }}" alt="Fahman" class="rounded-full w-24 h-80">
    </div>

    <!-- Advice Content -->
    <div class="flex-1">
        <!-- Risk Score Display -->
        <div class="flex items-center justify-between mb-3">
            <div class="text-2xl font-bold text-gray-800">
                Total Risk Score: <span class="text-{{ $fahmanAdvice['level'] }} font-bold">{{ $riskScore->total_score }}</span><span class="text-2sm">/100</span>
            </div>
        </div>

        <!-- Advice Message -->
        <div class="alert alert-{{ $fahmanAdvice['level'] }} text-left">
            <strong>{{ $fahmanAdvice['title'] }}</strong><br>
            <span class="text-sm text-muted">{{ $fahmanAdvice['message'] }}</span>
        </div>

        <!-- View Details Button (optional) -->
        <button type="button" class="btn btn-primary btn-sm mt-2 float-right"
            onclick="showSupplierRiskDetails()">
            Why? View full analysis
        </button>
        @php
            $score = $riskScore->total_score;

            // Calculate Commission Percentage
            $rawCommission = (100 - $score) * 0.2 * $sectorAvg + 1;

            // Ensure result is between 1 and 10
            $commissionPercentage = min(10, max(1, $rawCommission));

            
            
        @endphp
            <!-- Commission Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">

                <!-- Commission Value Card -->
                <div class="card p-4 shadow-sm border-l-4 border-primary">
                    <div class="flex items-center gap-4">
                        <div>
                            <h6 class="text-sm text-muted mb-1">Settlement Date (Payment)</h6>
                            <div class="text-lg font-bold text-gray-800">
                                {{ round($PayDate) }} Days
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commission Percentage Card -->
                <div class="card p-4 shadow-sm border-l-4 border-warning">
                    <div class="flex items-center gap-4">
                        <div>
                            <h6 class="text-sm text-muted mb-1">Commission Percentage</h6>
                            <div class="text-2xl font-bold text-gray-800">
                                {{ number_format($commissionPercentage,2) }}%
                            </div>
                        </div>
                    </div>
                </div>

            </div>
    </div>
</div>
