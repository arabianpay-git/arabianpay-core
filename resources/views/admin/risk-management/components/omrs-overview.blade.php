@php
    $riskAnalysis = $riskAnalysis ?? [];
    $omrs = $riskAnalysis['omrs'] ?? 0;
    $caf = $riskAnalysis['caf'] ?? 1.0;

    // Determine risk level and colors
    if ($omrs >= 80) {
        $riskLevel = translate('Low Risk');
        $bgColor = 'bg-green-50';
        $borderColor = 'border-green-200';
        $textColor = 'text-green-800';
        $ringColor = 'ring-green-500';
        $riskBadge = 'bg-green-100 text-green-800';
    } elseif ($omrs >= 60) {
        $riskLevel = translate('Medium Risk');
        $bgColor = 'bg-yellow-50';
        $borderColor = 'border-yellow-200';
        $textColor = 'text-yellow-800';
        $ringColor = 'ring-yellow-500';
        $riskBadge = 'bg-yellow-100 text-yellow-800';
    } else {
        $riskLevel = translate('High Risk');
        $bgColor = 'bg-red-50';
        $borderColor = 'border-red-200';
        $textColor = 'text-red-800';
        $ringColor = 'ring-red-500';
        $riskBadge = 'bg-red-100 text-red-800';
    }

    $components = $riskAnalysis['components']['omrs'] ?? [];
    $baseOmrs = $components['base_omrs'] ?? $omrs;
@endphp

<div class="bg-white rounded-lg shadow-sm border {{ $borderColor }} p-6">
    <div class="text-center">
        <!-- Risk Level Badge -->
        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $riskBadge }} mb-4">
            <i class="ki-filled ki-shield mr-1"></i>
            {{ $riskLevel }}
        </div>

        <!-- OMRS Score Circle -->
        <div class="relative inline-flex items-center justify-center mb-4">
            <div class="relative">
                <!-- Background Circle -->
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-gray-200" d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                    <path class="{{ $textColor }}" d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3"
                        stroke-dasharray="{{ $omrs }}, 100" />
                </svg>

                <!-- Score Text -->
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold {{ $textColor }}">{{ round($omrs, 1) }}</span>
                    <span class="text-sm text-gray-500">/100</span>
                </div>
            </div>
        </div>

        <!-- Score Details -->
        <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">{{ translate('Base Score:') }}</span>
                <span class="font-semibold">{{ round($baseOmrs, 1) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">{{ translate('CAF Factor:') }}</span>
                <span
                    class="font-semibold {{ $caf >= 1.0 ? 'text-green-600' : ($caf >= 0.8 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ round($caf, 2) }}
                </span>
            </div>
            <div class="border-t border-gray-200 pt-2 mt-2">
                <div class="flex justify-between items-center font-semibold">
                    <span>{{ translate('Final OMRS:') }}</span>
                    <span class="{{ $textColor }}">{{ round($omrs, 1) }}</span>
                </div>
            </div>
        </div>

        <!-- Risk Meter -->
        <div class="mt-6">
            <div class="flex justify-between text-xs text-gray-600 mb-1">
                <span>{{ translate('High Risk') }}</span>
                <span>{{ translate('Medium') }}</span>
                <span>{{ translate('Low Risk') }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 h-2 rounded-full"></div>
                <div class="relative -mt-2 -mb-1" style="left: calc({{ $omrs }}% - 8px);">
                    <div class="w-4 h-4 bg-white border-2 {{ $ringColor }} rounded-full shadow-sm"></div>
                </div>
            </div>
            <div class="flex justify-between text-xs text-gray-600 mt-1">
                <span>0</span>
                <span>50</span>
                <span>100</span>
            </div>
        </div>
    </div>
</div>
