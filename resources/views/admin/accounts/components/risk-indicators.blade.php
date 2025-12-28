<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-800">{{ translate('Risk Indicators') }}</h3>
        <div class="flex items-center gap-2">
            @php
                $overallScore = $riskScore['omrs'] ?? 0;
                $scoreColor = match (true) {
                    $overallScore >= 80 => 'text-emerald-600',
                    $overallScore >= 60 => 'text-amber-600',
                    default => 'text-rose-600',
                };

                $scoreBg = match (true) {
                    $overallScore >= 80 => 'bg-emerald-50 border-emerald-200',
                    $overallScore >= 60 => 'bg-amber-50 border-amber-200',
                    default => 'bg-rose-50 border-rose-200',
                };
            @endphp
            <div class="{{ $scoreBg }} border rounded-lg px-3 py-1">
                <span class="text-xs font-bold {{ $scoreColor }}">{{ $overallScore }}</span>
                <span class="text-[10px] text-slate-500 ml-1">{{ translate('Score') }}</span>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3 mb-4">
            @foreach (['lps', 'chs', 'bcs', 'bps', 'bes'] as $component)
                @php
                    $score = $riskScore[$component] ?? 0;
                    $componentColor = match (true) {
                        $score >= 80 => 'bg-emerald-100 text-emerald-700',
                        $score >= 60 => 'bg-amber-100 text-amber-700',
                        default => 'bg-rose-100 text-rose-700',
                    };

                    $componentLabels = [
                        'lps' => translate('Legal Profile'),
                        'chs' => translate('Credit History'),
                        'bcs' => translate('Business Capacity'),
                        'bps' => translate('Business Profile'),
                        'bes' => translate('Behavioral'),
                    ];
                @endphp
                <div class="text-center p-2 rounded-lg {{ $componentColor }}">
                    <div class="text-xs font-bold">{{ $score }}</div>
                    <div class="text-[10px] opacity-80">{{ $componentLabels[$component] ?? strtoupper($component) }}
                    </div>
                </div>
            @endforeach
        </div>

        @if (!empty($riskScore['flags']))
            <div class="border-t border-slate-100 pt-4">
                <h4 class="text-xs font-bold text-slate-700 mb-2">{{ translate('Risk Flags') }}</h4>
                <div class="space-y-2">
                    @foreach ($riskScore['flags'] as $flag)
                        @php
                            $flagInfo = match ($flag) {
                                'no_bureau_data' => [
                                    'color' => 'bg-amber-100 text-amber-800',
                                    'label' => translate('No Bureau Data'),
                                    'message' => translate('Credit bureau data not available'),
                                ],
                                'no_banking_data' => [
                                    'color' => 'bg-amber-100 text-amber-800',
                                    'label' => translate('No Banking Data'),
                                    'message' => translate('Banking data not available'),
                                ],
                                'no_credit_limit_info' => [
                                    'color' => 'bg-amber-100 text-amber-800',
                                    'label' => translate('No Credit Limit'),
                                    'message' => translate('Credit limit information missing'),
                                ],
                                'no_behavior_history' => [
                                    'color' => 'bg-amber-100 text-amber-800',
                                    'label' => translate('No Behavior History'),
                                    'message' => translate('Behavioral history not available'),
                                ],
                                default => [
                                    'color' => 'bg-slate-100 text-slate-800',
                                    'label' => translate(ucwords(str_replace('_', ' ', $flag))),
                                    'message' => translate('Flag detected'),
                                ],
                            };
                        @endphp
                        <div class="flex items-center gap-2 text-xs p-2 {{ $flagInfo['color'] }} rounded">
                            <i class="ki-outline ki-warning text-sm"></i>
                            <span class="font-medium">{{ $flagInfo['label'] }}</span>
                            <span class="text-[10px] ml-auto">{{ $flagInfo['message'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="border-t border-slate-100 pt-4">
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <i class="ki-outline ki-shield-tick text-blue-600"></i>
                    <span class="font-medium">{{ translate('Compliance Score') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-16 bg-slate-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($riskScore['caf'] ?? 1) * 100 }}%">
                        </div>
                    </div>
                    <span class="font-bold">{{ $riskScore['caf'] ?? 1.0 }}</span>
                </div>
            </div>
        </div>

        @php
            $riskLevel = match (true) {
                $overallScore >= 80 => [
                    'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                    'label' => translate('Low Risk'),
                    'icon' => 'shield-tick',
                ],
                $overallScore >= 60 => [
                    'color' => 'bg-amber-100 text-amber-800 border-amber-300',
                    'label' => translate('Medium Risk'),
                    'icon' => 'warning',
                ],
                default => [
                    'color' => 'bg-rose-100 text-rose-800 border-rose-300',
                    'label' => translate('High Risk'),
                    'icon' => 'shield-cross',
                ],
            };
        @endphp
        <div class="border {{ $riskLevel['color'] }} rounded-lg p-3 mt-4">
            <div class="flex items-center gap-3">
                <i class="ki-outline ki-{{ $riskLevel['icon'] }} text-lg"></i>
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold">{{ $riskLevel['label'] }}</span>
                        <span class="text-xs opacity-75">{{ translate('Overall Risk') }}</span>
                    </div>
                    <div class="text-[10px] mt-1 opacity-75">
                        {{ translate('Based on') }} {{ count($riskScore['components'] ?? []) }}
                        {{ translate('risk factors') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
