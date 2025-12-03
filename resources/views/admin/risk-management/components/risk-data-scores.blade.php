@php
    $riskScores = $riskScores ?? [];
    $overallRisk = $riskScores['overall_risk'] ?? 0;
    $riskLevel = $riskScores['risk_level'] ?? 'Medium';

    // Determine color based on risk level
    $riskColor = match ($riskLevel) {
        'Critical' => 'red',
        'High' => 'orange',
        'Medium' => 'yellow',
        'Low' => 'blue',
        'Very Low' => 'green',
        default => 'gray',
    };

    $scoreColors = [
        'portfolio_risk' => 'bg-blue-100 text-blue-800',
        'pipeline_risk' => 'bg-purple-100 text-purple-800',
        'ews_risk' => 'bg-red-100 text-red-800',
        'overall_risk' => "bg-{$riskColor}-100 text-{$riskColor}-800",
    ];

    $scoreIcons = [
        'portfolio_risk' => 'ki-chart-line',
        'pipeline_risk' => 'ki-setting-4',
        'ews_risk' => 'ki-shield',
        'overall_risk' => 'ki-shield-cross',
    ];

    $scoreTitles = [
        'portfolio_risk' => translate('Portfolio Risk'),
        'pipeline_risk' => translate('Pipeline Risk'),
        'ews_risk' => translate('Early Warning System'),
        'overall_risk' => translate('Overall Risk Score'),
    ];
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="ki-filled ki-shield-cross text-red-600 mr-2"></i>
        {{ translate('Risk Scores Overview') }}
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach (['portfolio_risk', 'pipeline_risk', 'ews_risk', 'overall_risk'] as $scoreType)
            @php
                $score = $riskScores[$scoreType] ?? 0;
                $colorClass = $scoreColors[$scoreType] ?? 'bg-gray-100 text-gray-800';
                $icon = $scoreIcons[$scoreType] ?? 'ki-information';
                $title = $scoreTitles[$scoreType] ?? ucfirst(str_replace('_', ' ', $scoreType));
            @endphp

            <div class="flex flex-col p-4 border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <div class="p-2 {{ $colorClass }} rounded-lg">
                            <i class="ki-filled {{ $icon }}"></i>
                        </div>
                        <div class="font-medium text-gray-900">{{ $title }}</div>
                    </div>
                </div>

                <div class="flex items-end justify-between mt-auto">
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900">{{ round($score) }}/100</div>
                        @if ($scoreType === 'overall_risk')
                            <div class="text-sm font-medium {{ $riskColor }}-600 mt-1">
                                {{ $riskLevel }}
                            </div>
                        @else
                            <div class="text-sm text-gray-500 mt-1">Score</div>
                        @endif
                    </div>

                    <!-- Progress bar -->
                    <div class="w-24">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full {{ $riskColor }}-500 rounded-full"
                                style="width: {{ min($score, 100) }}%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 text-right">{{ round($score) }}%</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Risk Level Indicator -->
    <div class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 rounded-full bg-{{ $riskColor }}-500 flex items-center justify-center">
                    <i class="ki-filled ki-check text-white text-xs"></i>
                </div>
                <span class="font-medium text-gray-900">
                    {{ translate('Risk Level') }}:
                    <span class="text-{{ $riskColor }}-600">{{ $riskLevel }}</span>
                </span>
            </div>

            <div class="text-sm text-gray-600">
                {{ translate('Last Updated') }}: {{ now()->format('M d, Y H:i') }}
            </div>
        </div>
    </div>
</div>
