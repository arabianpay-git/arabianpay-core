@php
    $riskAnalysis = $riskAnalysis ?? [];
    $flags = $riskAnalysis['flags'] ?? [];

    $flagDescriptions = [
        'no_bureau_data' => translate('SIMAH credit data not integrated'),
        'no_banking_data' => translate('Insufficient banking transaction history'),
        'no_cr_issue_date' => translate('Business registration date missing'),
        'no_credit_limit_info' => translate('Credit limit information unavailable'),
        'no_behavior_history' => translate('No payment behavior history available'),
    ];

    $flagIcons = [
        'no_bureau_data' => 'ki-chart-line',
        'no_banking_data' => 'ki-bank',
        'no_cr_issue_date' => 'ki-document',
        'no_credit_limit_info' => 'ki-credit-cart',
        'no_behavior_history' => 'ki-chart-simple',
    ];
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="ki-filled ki-flag text-red-600 mr-2"></i>
        {{ translate('Risk Flags & Warnings') }}
        <span class="ml-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
            {{ count($flags) }}
        </span>
    </h3>

    @if (count($flags) > 0)
        <div class="space-y-3">
            @foreach ($flags as $flag)
                @php
                    $description = $flagDescriptions[$flag] ?? translate('Unknown flag');
                    $icon = $flagIcons[$flag] ?? 'ki-information';
                @endphp

                <div class="flex items-start space-x-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <i class="ki-filled {{ $icon }} text-red-600 mt-0.5"></i>
                    <div class="flex-1">
                        <div class="font-medium text-red-800 capitalize">
                            {{ translate(str_replace('_', ' ', $flag)) }}
                        </div>
                        <div class="text-sm text-red-600 mt-1">
                            {{ $description }}
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs font-bold bg-red-600 text-white rounded-full">
                        !
                    </span>
                </div>
            @endforeach

            <!-- Flag Summary -->
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center space-x-2 text-yellow-800">
                    <i class="ki-filled ki-information text-yellow-600"></i>
                    <span class="text-sm font-medium">{{ translate('Impact Summary') }}</span>
                </div>
                <p class="text-sm text-yellow-700 mt-1">
                    {{ translate('These flags indicate data quality issues that may affect scoring accuracy. Consider manual review and data collection to resolve these issues.') }}
                </p>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ki-filled ki-check text-green-600 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-1">{{ translate('No Risk Flags') }}</h4>
            <p class="text-gray-600 text-sm">
                {{ translate('All required data is available and scoring components are complete.') }}
            </p>
        </div>
    @endif
</div>
