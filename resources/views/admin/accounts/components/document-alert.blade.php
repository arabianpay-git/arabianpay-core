@php
    // Determine alert type based on risk score
    $overallScore = $riskScore['omrs'] ?? 0;
    $hasCriticalFlags = !empty($riskScore['flags']) && count($riskScore['flags']) > 0;
    $missingDataFlags = array_intersect(
        ['no_bureau_data', 'no_banking_data', 'no_credit_limit_info'],
        $riskScore['flags'] ?? [],
    );

    // Decide which alert to show
    if ($overallScore < 60) {
        $alert = [
            'type' => 'high_risk',
            'title' => translate('High Risk Alert'),
            'message' =>
                translate('Supplier has high risk score') .
                ' (' .
                $overallScore .
                '). ' .
                translate('Additional documentation required.'),
            'color' => 'bg-rose-50 border-rose-200 text-rose-900',
            'icon' => 'shield-cross',
            'action' => translate('Review Documents'),
            'action_color' => 'bg-rose-600 hover:bg-rose-700',
        ];
    } elseif ($missingDataFlags) {
        $translatedFlags = array_map(function ($flag) {
            return translate(ucwords(str_replace('_', ' ', $flag)));
        }, $missingDataFlags);

        $alert = [
            'type' => 'missing_data',
            'title' => translate('Missing Information'),
            'message' => translate('Required data missing:') . ' ' . implode(', ', $translatedFlags),
            'color' => 'bg-amber-50 border-amber-200 text-amber-900',
            'icon' => 'information-5',
            'action_color' => 'bg-amber-600 hover:bg-amber-700',
        ];
    } else {
        $alert = [
            'type' => 'document_expiry',
            'title' => translate('Document Alert'),
            'message' => translate('Insurance Policy expires in 30 days.'),
            'color' => 'bg-orange-50 border-orange-200 text-orange-900',
            'icon' => 'information-5',
            'action' => translate('Upload Now'),
            'action_color' => 'bg-orange-600 hover:bg-orange-700',
        ];
    }
@endphp

<div class="{{ $alert['color'] }} p-4 rounded-xl border">
    <div class="flex items-start gap-3">
        <i class="ki-outline ki-{{ $alert['icon'] }} text-xl"></i>
        <div class="flex-1">
            <p class="text-sm font-bold mb-1">{{ $alert['title'] }}</p>
            <p class="text-xs leading-relaxed">{{ $alert['message'] }}</p>

            @if (!empty($alert['action']))
                <div class="mt-3 flex items-center gap-2">
                    <button
                        class="{{ $alert['action_color'] }} text-white px-3 py-1.5 rounded-lg text-[10px] font-bold transition">
                        {{ $alert['action'] }}
                    </button>

                    @if ($alert['type'] === 'high_risk')
                        <button type="button"
                            class="text-slate-600 text-[10px] font-medium hover:text-slate-800 transition"
                            data-modal-toggle="#risk-details-modal" onclick="showRiskDetails()">
                            {{ translate('View Details') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal hidden" data-modal="true" id="risk-details-modal">
    <div class="modal-content max-w-2xl">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Risk Score Details') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <div class="space-y-6">
                <div class="bg-slate-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="font-bold text-slate-800">{{ translate('Overall Risk Score') }}</h6>
                        @php
                            $scoreColor = match (true) {
                                $overallScore >= 80 => 'text-emerald-600 bg-emerald-100',
                                $overallScore >= 60 => 'text-amber-600 bg-amber-100',
                                default => 'text-rose-600 bg-rose-100',
                            };
                        @endphp
                        <span class="text-lg font-bold {{ $scoreColor }} px-3 py-1 rounded-full">
                            {{ $overallScore }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2 mb-1">
                        <div class="h-2 rounded-full {{ $scoreColor }}" style="width: {{ $overallScore }}%"></div>
                    </div>
                    <p class="text-xs text-slate-600">{{ translate('Updated:') }} {{ now()->format('d M Y H:i') }}</p>
                </div>

                <div>
                    <h6 class="font-bold text-slate-800 mb-3">{{ translate('Component Breakdown') }}</h6>
                    <div class="space-y-3">
                        @foreach ($riskScore['components'] ?? [] as $componentKey => $component)
                            @php
                                $componentScore = $riskScore[$componentKey] ?? 0;
                                $componentColor = match (true) {
                                    $componentScore >= 80 => 'border-emerald-200 bg-emerald-50',
                                    $componentScore >= 60 => 'border-amber-200 bg-amber-50',
                                    default => 'border-rose-200 bg-rose-50',
                                };

                                $componentName = match ($componentKey) {
                                    'lps' => translate('Legal Profile Score'),
                                    'chs' => translate('Credit History Score'),
                                    'bcs' => translate('Business Capacity Score'),
                                    'bps' => translate('Business Profile Score'),
                                    'bes' => translate('Behavioral Score'),
                                    default => translate(ucfirst($componentKey)),
                                };
                            @endphp

                            <div class="border rounded-lg p-3 {{ $componentColor }}">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-sm">{{ $componentName }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold">{{ $componentScore }}</span>
                                        <span class="text-xs text-slate-500">
                                            ({{ $riskScore['weights_used'][$componentKey . '_weight'] ?? 0 }}%
                                            {{ translate('weight') }})
                                        </span>
                                    </div>
                                </div>

                                <div class="w-full bg-white rounded-full h-1.5 mb-2">
                                    <div class="h-1.5 rounded-full {{ str_replace('bg-', 'bg-opacity-80 ', $componentColor) }}"
                                        style="width: {{ $componentScore }}%"></div>
                                </div>

                                @if (!empty($riskScore['notes'][$componentKey]))
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        {{ Str::limit($riskScore['notes'][$componentKey], 120) }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if (!empty($riskScore['flags']))
                    <div>
                        <h6 class="font-bold text-slate-800 mb-3">{{ translate('Risk Flags') }}</h6>
                        <div class="space-y-2">
                            @foreach ($riskScore['flags'] as $flag)
                                <div class="flex items-start gap-2 p-2 bg-slate-50 rounded">
                                    <i class="ki-outline ki-warning text-amber-600 mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium">
                                            {{ translate(ucwords(str_replace('_', ' ', $flag))) }}
                                        </p>
                                        <p class="text-[10px] text-slate-500">
                                            @php
                                                $flagDescription = match ($flag) {
                                                    'no_bureau_data' => translate(
                                                        'Credit bureau data is not available for this supplier.',
                                                    ),
                                                    'no_banking_data' => translate(
                                                        'Banking transaction data is not available.',
                                                    ),
                                                    'no_credit_limit_info' => translate(
                                                        'Credit limit information is missing.',
                                                    ),
                                                    'no_behavior_history' => translate(
                                                        'No behavioral history available.',
                                                    ),
                                                    default => translate(
                                                        'This may affect the accuracy of the risk assessment.',
                                                    ),
                                                };
                                            @endphp
                                            {{ $flagDescription }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <h6 class="font-bold text-slate-800 mb-3">{{ translate('Weight Distribution') }}</h6>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @php
                            $mainComponents = [
                                'lps' => translate('Legal'),
                                'chs' => translate('Credit'),
                                'bcs' => translate('Business Capacity'),
                                'bps' => translate('Business Profile'),
                                'bes' => translate('Behavioral'),
                            ];
                        @endphp

                        @foreach ($mainComponents as $key => $label)
                            @if (isset($riskScore['weights_used'][$key . '_weight']))
                                <div class="text-center p-2 bg-slate-50 rounded">
                                    <div class="text-lg font-bold text-slate-800">
                                        {{ $riskScore['weights_used'][$key . '_weight'] }}%
                                    </div>
                                    <div class="text-[10px] text-slate-600">{{ $label }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer border-t border-slate-200 px-5 py-4">
            <button type="button" class="btn btn-light btn-sm" data-modal-dismiss="true">
                {{ translate('Close') }}
            </button>
        </div>
    </div>
</div>
