<div class="modal" data-modal="true" id="risk-score-modal">
    <div class="modal-content max-w-3xl">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Risk Score Details') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <div class="space-y-6">
                <!-- Overall Score Card -->
                <div class="bg-slate-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h6 class="font-bold text-slate-800">{{ translate('Overall Risk Score') }}</h6>
                            <p class="text-xs text-slate-600">{{ translate('Updated') }}:
                                {{ now()->format('d M Y H:i') }}</p>
                        </div>
                        @php
                            $scoreColor = match (true) {
                                $overallScore >= 80 => 'text-emerald-600 bg-emerald-100',
                                $overallScore >= 60 => 'text-amber-600 bg-amber-100',
                                $overallScore >= 40 => 'text-orange-600 bg-orange-100',
                                default => 'text-rose-600 bg-rose-100',
                            };
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold {{ $scoreColor }} px-4 py-2 rounded-full">
                                {{ $overallScore }}
                            </span>
                            <span class="text-sm font-medium {{ str_replace('text-', 'text-', $scoreColor) }}">
                                {{ $riskLevel['label'] }}
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ str_replace('text-', 'bg-', $scoreColor) }}"
                            style="width: {{ $overallScore }}%"></div>
                    </div>
                </div>

                <!-- Component Scores -->
                <div>
                    <h6 class="font-bold text-slate-800 mb-3">{{ translate('Component Scores') }}</h6>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        @foreach (['lps', 'chs', 'bcs', 'bps', 'bes'] as $component)
                            @php
                                $score = $riskScore[$component] ?? 0;
                                $componentColor = match (true) {
                                    $score >= 80 => 'bg-emerald-100 border-emerald-300',
                                    $score >= 60 => 'bg-amber-100 border-amber-300',
                                    $score >= 40 => 'bg-orange-100 border-orange-300',
                                    default => 'bg-rose-100 border-rose-300',
                                };

                                $componentLabels = [
                                    'lps' => 'Legal Profile',
                                    'chs' => 'Credit History',
                                    'bcs' => 'Business Capacity',
                                    'bps' => 'Business Profile',
                                    'bes' => 'Behavioral',
                                ];
                            @endphp
                            <div class="border rounded-lg p-3 text-center {{ $componentColor }}">
                                <div class="text-lg font-bold">{{ $score }}</div>
                                <div class="text-xs text-slate-600">
                                    {{ translate($componentLabels[$component] ?? strtoupper($component)) }}
                                </div>
                                <div class="text-[10px] text-slate-500 mt-1">
                                    {{ $riskScore['weights_used'][$component . '_weight'] ?? 0 }}%
                                    {{ translate('weight') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Flags -->
                @if (!empty($riskScore['flags']))
                    <div>
                        <h6 class="font-bold text-slate-800 mb-3">{{ translate('Risk Flags') }}</h6>
                        <div class="space-y-2">
                            @foreach ($riskScore['flags'] as $flag)
                                <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg">
                                    <i class="ki-outline ki-warning text-amber-600"></i>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">{{ ucwords(str_replace('_', ' ', $flag)) }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            @php
                                                $flagDescription = match ($flag) {
                                                    'no_bureau_data' => translate('Credit bureau data not available'),
                                                    'no_banking_data' => translate('Banking data not available'),
                                                    'no_credit_limit_info' => translate(
                                                        'Credit limit information missing',
                                                    ),
                                                    'no_behavior_history' => translate(
                                                        'Behavioral history not available',
                                                    ),
                                                    default => translate('Risk factor identified'),
                                                };
                                            @endphp
                                            {{ $flagDescription }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Notes Summary -->
                @if (!empty($riskScore['notes']))
                    <div>
                        <h6 class="font-bold text-slate-800 mb-3">{{ translate('Assessment Notes') }}</h6>
                        <div class="space-y-2">
                            @foreach ($riskScore['notes'] as $component => $note)
                                @if (
                                    $component !== 'lps' &&
                                        $component !== 'chs' &&
                                        $component !== 'bcs' &&
                                        $component !== 'bps' &&
                                        $component !== 'bes')
                                    @continue
                                @endif
                                @php
                                    $componentName = match ($component) {
                                        'lps' => 'Legal Profile',
                                        'chs' => 'Credit History',
                                        'bcs' => 'Business Capacity',
                                        'bps' => 'Business Profile',
                                        'bes' => 'Behavioral',
                                        default => ucfirst($component),
                                    };
                                @endphp
                                <div class="text-sm">
                                    <span class="font-medium text-slate-700">{{ $componentName }}:</span>
                                    <span class="text-slate-600">{{ Str::limit($note, 120) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="modal-footer border-t border-slate-200 px-5 py-4">
            <button type="button" class="btn btn-light btn-sm" data-modal-dismiss="true">
                {{ translate('Close') }}
            </button>
        </div>
    </div>
</div>
