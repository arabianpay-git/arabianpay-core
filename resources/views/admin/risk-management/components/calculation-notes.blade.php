@php
    $riskAnalysis = $riskAnalysis ?? [];
    $notes = $riskAnalysis['notes'] ?? [];
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="ki-filled ki-notepad-edit text-blue-600 mr-2"></i>
        {{ translate('Calculation Notes & Methodology') }}
    </h3>

    @if (!empty($notes))
        <div class="space-y-4">
            @foreach ($notes as $scoreType => $note)
                @php
                    $scoreTypes = [
                        'lps' => [
                            'title' => translate('Legal & Profile Score'),
                            'icon' => 'ki-document',
                            'color' => 'blue',
                        ],
                        'chs' => [
                            'title' => translate('Credit History Score'),
                            'icon' => 'ki-chart-line',
                            'color' => 'green',
                        ],
                        'bcs' => [
                            'title' => translate('Banking & Cashflow Score'),
                            'icon' => 'ki-bank',
                            'color' => 'purple',
                        ],
                        'bps' => [
                            'title' => translate('Business Profile Score'),
                            'icon' => 'ki-briefcase',
                            'color' => 'orange',
                        ],
                        'bes' => [
                            'title' => translate('Behavioral & Experience Score'),
                            'icon' => 'ki-chart-simple',
                            'color' => 'red',
                        ],
                        'caf' => [
                            'title' => translate('Compliance Adjustment Factor'),
                            'icon' => 'ki-shield-tick',
                            'color' => 'gray',
                        ],
                    ];

                    $currentType = $scoreTypes[$scoreType] ?? [
                        'title' => ucfirst($scoreType),
                        'icon' => 'ki-information',
                        'color' => 'gray',
                    ];
                    $colorClass = "text-{$currentType['color']}-600";
                @endphp

                <details class="group">
                    <summary
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <i class="ki-filled {{ $currentType['icon'] }} {{ $colorClass }}"></i>
                            <span class="font-medium text-gray-900">{{ $currentType['title'] }}</span>
                        </div>
                        <i class="ki-filled ki-arrow-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                    </summary>

                    <div class="mt-2 p-3 bg-white border border-gray-200 rounded-lg">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $note }}</p>
                    </div>
                </details>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <i class="ki-filled ki-notepad text-3xl text-gray-400 mb-3"></i>
            <p>{{ translate('No calculation notes available') }}</p>
        </div>
    @endif

    <!-- Methodology Info -->
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h4 class="font-semibold text-blue-900 mb-2 flex items-center">
            <i class="ki-filled ki-information text-blue-600 mr-2"></i>
            {{ translate('Scoring Methodology') }}
        </h4>
        <p class="text-sm text-blue-800">
            {{ translate('OMRS = (LPS × 15%) + (CHS × 25%) + (BCS × 20%) + (BPS × 10%) + (BES × 30%) × CAF') }}
        </p>
        <p class="text-xs text-blue-700 mt-2">
            {{ translate('Weights are customizable per user. Scores are normalized to 0-100 scale.') }}
        </p>
    </div>
</div>
