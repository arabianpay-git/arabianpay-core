<div class="container-fixed mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Overall Risk Score -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">{{ translate('Overall Risk Score') }}</p>
                    <div class="flex items-baseline mt-1">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $riskScores['overall_risk'] }}/100</h3>
                        <span
                            class="ml-2 text-sm {{ $riskScores['overall_risk'] > 70 ? 'text-danger' : ($riskScores['overall_risk'] > 40 ? 'text-warning' : 'text-success') }}">
                            {{ $riskScores['overall_risk'] > 70 ? translate('High') : ($riskScores['overall_risk'] > 40 ? translate('Medium') : translate('Low')) }}
                        </span>
                    </div>
                </div>
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none" />
                        <circle cx="50" cy="50" r="40"
                            stroke="{{ $riskScores['overall_risk'] > 70 ? '#ef4444' : ($riskScores['overall_risk'] > 40 ? '#f59e0b' : '#10b981') }}"
                            stroke-width="8" fill="none" stroke-dasharray="{{ 2 * 3.14159 * 40 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 40 * (1 - $riskScores['overall_risk'] / 100) }}" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold">{{ $riskScores['overall_risk'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portfolio Risk -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">{{ translate('Portfolio Risk') }}</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ $riskScores['portfolio_risk'] }}/100</h3>
                </div>
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none" />
                        <circle cx="50" cy="50" r="40" stroke="#dc2626" stroke-width="8" fill="none"
                            stroke-dasharray="{{ 2 * 3.14159 * 40 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 40 * (1 - $riskScores['portfolio_risk'] / 100) }}" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold">{{ $riskScores['portfolio_risk'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pipeline Risk -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">{{ translate('Pipeline Risk') }}</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ $riskScores['pipeline_risk'] }}/100</h3>
                </div>
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none" />
                        <circle cx="50" cy="50" r="40" stroke="#f59e0b" stroke-width="8" fill="none"
                            stroke-dasharray="{{ 2 * 3.14159 * 40 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 40 * (1 - $riskScores['pipeline_risk'] / 100) }}" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold">{{ $riskScores['pipeline_risk'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- EWS Risk -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">{{ translate('EWS Risk') }}</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ $riskScores['ews_risk'] }}/100</h3>
                </div>
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none" />
                        <circle cx="50" cy="50" r="40" stroke="#ef4444" stroke-width="8" fill="none"
                            stroke-dasharray="{{ 2 * 3.14159 * 40 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 40 * (1 - $riskScores['ews_risk'] / 100) }}" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold">{{ $riskScores['ews_risk'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
