@php
    $riskAnalysis = $riskAnalysis ?? [];
    $weightsUsed = $riskAnalysis['weights_used'] ?? [];
    $mainWeights = $weightsUsed['main_weights'] ?? [];
    $source = $weightsUsed['source'] ?? 'default';
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <i class="ki-filled ki-setting-4 text-purple-600 mr-2"></i>
        {{ translate('Scoring Weights Configuration') }}
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach ($mainWeights as $scoreType => $weight)
            @php
                $scoreInfo = [
                    'lps' => ['title' => translate('Legal & Profile'), 'color' => 'blue', 'icon' => 'ki-document'],
                    'chs' => ['title' => translate('Credit History'), 'color' => 'green', 'icon' => 'ki-chart-line'],
                    'bcs' => ['title' => translate('Banking & Cashflow'), 'color' => 'purple', 'icon' => 'ki-bank'],
                    'bps' => ['title' => translate('Business Profile'), 'color' => 'orange', 'icon' => 'ki-briefcase'],
                    'bes' => [
                        'title' => translate('Behavioral & Experience'),
                        'color' => 'red',
                        'icon' => 'ki-chart-simple',
                    ],
                    'caf' => ['title' => translate('Compliance Factor'), 'color' => 'gray', 'icon' => 'ki-shield-tick'],
                ];

                $currentInfo = $scoreInfo[$scoreType] ?? [
                    'title' => ucfirst($scoreType),
                    'color' => 'gray',
                    'icon' => 'ki-information',
                ];
                $colorClass = "bg-{$currentInfo['color']}-100 text-{$currentInfo['color']}-800";
            @endphp

            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="p-2 {{ $colorClass }} rounded-lg">
                        <i class="ki-filled {{ $currentInfo['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ $currentInfo['title'] }}</div>
                        <div class="text-sm text-gray-500">{{ translate('Weight') }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold text-gray-900">{{ $weight }}%</div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Source Information -->
    <div class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 text-sm text-gray-600">
                <i class="ki-filled ki-database"></i>
                <span>{{ translate('Weights Source') }}:
                    <span
                        class="font-medium capitalize {{ $source === 'database' ? 'text-green-600' : 'text-blue-600' }}">
                        {{ $source }}
                    </span>
                </span>
            </div>

            @if (Auth::user()->user_type == 'admin')
                <button class="btn btn-sm btn-outline btn-primary" data-modal-toggle="#risk_weight_modal"
                    data-user-id="{{ $riskAnalysis['user_id'] ?? '' }}">
                    <i class="ki-filled ki-setting-4 mr-1"></i>
                    {{ translate('Customize Weights') }}
                </button>
            @endif
        </div>
    </div>
</div>
