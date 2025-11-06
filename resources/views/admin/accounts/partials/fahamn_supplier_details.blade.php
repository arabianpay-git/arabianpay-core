<div class="flex items-start gap-4">
    <!-- Content of evaluation -->
    <div class="flex-1">
        <div class="mt-4 p-3">
            <!-- Close button -->
            <div class="flex items-center gap-2.5 mt-3" style="float: right;">
                <a class="btn btn-sm btn-light" data-modal-dismiss="true" href="#">
                    Close
                </a>
            </div>

            <!-- 📊 Risk Score Breakdown -->
            <h5 class="text-lg font-semibold text-gray-800 mb-3 mt-5">📊 Risk Score Breakdown</h5>
            @php
                $breakdown = [
                    'CR & ID Match Score' => $riskScore->cr_id_match_score,
                    'ID Expiry Score' => $riskScore->id_expiry_score,
                    'CR Expiry Score' => $riskScore->cr_expiry_score,
                    'Business Type Score' => $riskScore->business_type_score,
                    'Activity Score' => $riskScore->activity_score,
                    'Repayment Score' => $riskScore->repayment_score,
                    'POS Score' => $riskScore->pos_score,
                    'Industry Score' => $riskScore->industry_score,
                    'Location Score' => $riskScore->location_score,
                ];

                $maxScores = [
                    'CR & ID Match Score' => 20,
                    'ID Expiry Score' => 10,
                    'CR Expiry Score' => 20,
                    'Business Type Score' => 15,
                    'Activity Score' => 15,
                    'Repayment Score' => 20,
                    'POS Score' => 20,
                    'Industry Score' => 15,
                    'Location Score' => 10,
                ];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-1">
                @foreach ($breakdown as $label => $value)
                    @php
                        $max = $maxScores[$label] ?? 25;
                        $avg = ($value / $max) * 100;
                        $message = match (true) {
                            $avg >= 90 => 'Excellent — this boosts Fahman’s confidence.',
                            $avg >= 75 => 'Good performance, but could still be improved.',
                            $avg >= 60 => 'Some instability detected — better to monitor closely.',
                            $avg >= 50 => 'Moderate risk — caution is advised.',
                            default => 'Very weak — this raises serious concern.',
                        };
                        $color = match (true) {
                            $avg >= 90 => 'success',
                            $avg >= 75 => 'primary',
                            $avg >= 60 => 'warning',
                            $avg >= 50 => 'danger',
                            default => 'dark',
                        };
                    @endphp

                    <div class="card p-1">
                        <div class="flex flex-wrap justify-between items-center gap-2">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex justify-center items-center size-8 shrink-0 rounded-full ring-1 ring-gray-300 bg-gray-100">
                                    <i class="ki-filled ki-information-2 text-{{ $color }}"></i>
                                </div>
                                <div class="flex justify-center items-center size-8 shrink-0 rounded-full bg-gray-100">
                                    <span class="text-primary font-semibold">{{ $value }}</span>
                                    /
                                    <span class="text-grey-700 font-semibold text-2sm">{{ $max }}</span>
                                </div>
                                <div class="grid grid-col gap-1">
                                    <a class="text-base font-medium text-gray-900 hover:text-primary-active mb-px"
                                        href="#">
                                        {{ $label }}
                                    </a>
                                    <span class="text-2sm text-gray-700">
                                        {{ $message }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>


            <!-- 📍 Location -->
            <h5 class="text-lg font-semibold text-gray-800 mt-5 mb-3">📍 Location Analysis</h5>
            @php
                $location = $riskScore->location ?? [];
                $locationData = [
                    'City' => $location['city'] ?? 'N/A',
                    'Tier Score' => $location['tier_score'] ?? '-',
                    'Activity Score' => $location['activity_score'] ?? '-',
                    'Default Rate Score' => $location['default_rate_score'] ?? '-',
                ];
            @endphp
            <!--
            <ul class="list-group list-group-flush">
                @foreach ($locationData as $label => $value)
<li class="list-group-item p-1 border rounded mb-1 d-flex justify-between align-items-center">
                        <div class="text-base text-gray-700 font-semibold">{{ $label }}</div>
                        <div class="text-gray-900">{{ $value }}</div>
                    </li>
@endforeach
            </ul>
        -->
            <!-- 🧮 Total Score -->
            <div class="border-t pt-3 mt-3">
                <h5 class="text-lg font-semibold text-gray-800">🧮 Total Score</h5>
                @php
                    $total = $riskScore->total_score;
                    $scoreColor =
                        $total >= 90
                            ? 'success'
                            : ($total >= 75
                                ? 'primary'
                                : ($total >= 60
                                    ? 'warning'
                                    : ($total >= 40
                                        ? 'danger'
                                        : 'dark')));
                @endphp
                <span class="text-3xl font-bold text-{{ $scoreColor }}">
                    {{ $total }}
                </span>/100
            </div>


        </div>
    </div>
</div>
