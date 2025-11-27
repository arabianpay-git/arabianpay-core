@php
    $riskAnalysis = $riskAnalysis ?? [];
    $components = $riskAnalysis['components'] ?? [];
    $weightsUsed = $riskAnalysis['weights_used'] ?? [];

    // Define contribution colors, icons, and titles
    $scoreColors = [
        'lps' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'light' => 'bg-blue-50'],
        'chs' => ['bg' => 'bg-green-500', 'text' => 'text-green-600', 'light' => 'bg-green-50'],
        'bcs' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'light' => 'bg-purple-50'],
        'bps' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-600', 'light' => 'bg-orange-50'],
        'bes' => ['bg' => 'bg-red-500', 'text' => 'text-red-600', 'light' => 'bg-red-50'],
    ];

    $scoreIcons = [
        'lps' => 'ki-document',
        'chs' => 'ki-chart-line',
        'bcs' => 'ki-bank',
        'bps' => 'ki-briefcase',
        'bes' => 'ki-chart-simple',
    ];

    $scoreTitles = [
        'lps' => translate('Legal & Profile'),
        'chs' => translate('Credit History'),
        'bcs' => translate('Banking & Cashflow'),
        'bps' => translate('Business Profile'),
        'bes' => translate('Behavioral & Experience'),
    ];

    // Compute contributions from weights and component scores
    $contributions = [];
    foreach (['lps', 'chs', 'bcs', 'bps', 'bes'] as $type) {
        $compScore = $riskAnalysis[$type] ?? 0;
        $weight = floatval($weightsUsed["{$type}_weight"] ?? 0);
        $contributions[$type] = ($compScore * $weight) / 100; // contribution in points
    }

@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="ki-filled ki-chart-pie-2 text-blue-600 mr-2"></i>
        {{ translate('Risk Score Distribution') }}
    </h3>

    <!-- Contribution Bars -->
    <div class="space-y-4 mb-6">
        @foreach ($contributions as $scoreType => $contribution)
            @php
                $weight = floatval($weightsUsed["{$scoreType}_weight"] ?? 0);
                $score = floatval($riskAnalysis[$scoreType] ?? 0);
                $color = $scoreColors[$scoreType];
                $percentage = $weight > 0 ? ($contribution / $weight) * 100 : 0;
            @endphp

            <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                    <div class="flex items-center space-x-2">
                        <i class="ki-filled {{ $scoreIcons[$scoreType] }} {{ $color['text'] }}"></i>
                        <span class="font-medium text-gray-700">{{ $scoreTitles[$scoreType] }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold {{ $color['text'] }}">{{ round($score, 1) }}</span>
                        <span class="text-gray-500">/100</span>
                    </div>
                </div>

                <div class="flex space-x-2 items-center">
                    <!-- Contribution Bar -->
                    <div class="flex-1 bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="h-full {{ $color['bg'] }} rounded-full transition-all duration-500"
                            style="width: {{ $percentage }}%">
                        </div>
                    </div>

                    <!-- Contribution Label -->
                    <div class="text-xs font-medium text-gray-600 w-16 text-right">
                        {{ round($contribution, 1) }}/{{ $weight }}
                    </div>
                </div>

                <!-- Weight Info -->
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ translate('Weight:') }} {{ $weight }}%</span>
                    <span>{{ translate('Contribution:') }} {{ round($contribution, 1) }}
                        {{ translate('points') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Summary -->
    <div class="border-t border-gray-200 pt-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center p-3 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">
                    {{ round(array_sum($contributions), 1) }}
                </div>
                <div class="text-blue-800 font-medium">{{ translate('Base Score') }}</div>
            </div>
            <div class="text-center p-3 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">
                    {{ round($riskAnalysis['omrs'] ?? 0, 1) }}
                </div>
                <div class="text-green-800 font-medium">{{ translate('Final OMRS') }}</div>
            </div>
        </div>
    </div>
</div>
