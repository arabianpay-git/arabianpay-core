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

            // تحويل الدرجة (0–100) إلى نسبة عمولة بين 1% و10%
            $commissionPercentage = 10 - round($score / 10); // كلما زادت المخاطرة، زادت العمولة
            $commissionPercentage = max(1, min($commissionPercentage, 10)); // تأمين بين 1 و10

            // مثال: إن أردت ضربها في قيمة طلب (مثلاً 100,000 ريال)
            $sampleOrderAmount = 100000;
            $calculatedCommission = ($commissionPercentage / 100) * $sampleOrderAmount;
        @endphp
            <!-- Commission Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">

                <!-- Commission Value Card -->
                <div class="card p-4 shadow-sm border-l-4 border-primary">
                    <div class="flex items-center gap-4">
                        <div>
                            <h6 class="text-sm text-muted mb-1">Settlement Date (Payment)</h6>
                            <div class="text-lg font-bold text-gray-800">
                                3 Days
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
                                {{ $commissionPercentage }}%
                            </div>
                        </div>
                    </div>
                </div>

            </div>
    </div>
</div>
