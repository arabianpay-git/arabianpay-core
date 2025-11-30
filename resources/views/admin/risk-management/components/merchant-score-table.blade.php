<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th data-tooltip-key="user-id" class="w-[60px] text-center">{{ translate('User ID') }}</th>
                <th data-tooltip-key="user-name" class="text-center">{{ translate('User Name') }}</th>
                <th data-tooltip-key="lps-score" class="w-[120px] text-center">{{ translate('LPS') }}</th>
                <th data-tooltip-key="chs-score" class="w-[120px] text-center">{{ translate('CHS') }}</th>
                <th data-tooltip-key="bcs-score" class="w-[120px] text-center">{{ translate('BCS') }}</th>
                <th data-tooltip-key="bps-score" class="w-[120px] text-center">{{ translate('BPS') }}</th>
                <th data-tooltip-key="bes-score" class="w-[120px] text-center">{{ translate('BES') }}</th>
                <th data-tooltip-key="caf-factor" class="w-[100px] text-center">{{ translate('CAF Factor') }}</th>
                <th data-tooltip-key="omrs-score" class="w-[120px] text-center">{{ translate('OMRS') }}</th>
                <th data-tooltip-key="risk-flags" class="w-[150px] text-center">{{ translate('Risk Flags') }}</th>
                <th data-tooltip-key="action" class="w-[100px] text-center">{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $userIds = $risks->pluck('user.id')->toArray();
                $riskWeights = App\Models\RiskWeight::whereIn('user_id', $userIds)->get()->keyBy('user_id');
            @endphp

            @foreach ($risks as $item)
                @php
                    $user = $item['user'];
                    $risk = $item['risk'];
                    $hasError = isset($risk['error']);

                    // Get weights for this user from database or use defaults
                    $userWeights = $riskWeights[$user->id] ?? null;
                    $weights = [
                        'lps' => $userWeights->lps_weight ?? 15,
                        'chs' => $userWeights->chs_weight ?? 25,
                        'bcs' => $userWeights->bcs_weight ?? 20,
                        'bps' => $userWeights->bps_weight ?? 10,
                        'bes' => $userWeights->bes_weight ?? 30,
                        'caf' => $userWeights->caf_weight ?? 0,
                        'omrs' => 100,
                    ];

                    if (!$hasError) {
                        $components = $risk['components'] ?? [];
                        $notes = $risk['notes'] ?? [];
                        $flags = $risk['flags'] ?? [];

                        // Calculate weighted contributions using dynamic weights
                        $weightedContributions = [];
                        foreach ($weights as $scoreType => $weight) {
                            if (isset($risk[$scoreType]) && $weight > 0) {
                                $weightedContributions[$scoreType] = ($risk[$scoreType] / 100) * $weight;
                            }
                        }

                        // Determine risk level and badge color
                        $score = $risk['omrs'] ?? 0;
                        if ($score >= 80) {
                            $riskLevel = translate('Low Risk');
                            $badgeColor = 'badge badge-sm badge-outline badge-success';
                        } elseif ($score >= 60) {
                            $riskLevel = translate('Medium Risk');
                            $badgeColor = 'badge badge-sm badge-outline badge-warning';
                        } else {
                            $riskLevel = translate('High Risk');
                            $badgeColor = 'badge badge-sm badge-outline badge-danger';
                        }
                    }
                @endphp
                <tr>
                    <!-- User ID -->
                    <td class="text-center">{{ $user->id }}</td>

                    <!-- User Name -->
                    <td>
                        @if (!$hasError && count($flags) > 0)
                            <span class="menu-icon items-start w-[20px]">
                                <i class="ki-filled ki-flag text-lg text-danger"></i>
                            </span>
                        @endif

                        <div class="whitespace-nowrap">
                            @php
                                $type = request('type');
                                $profileRoute =
                                    $type === 'merchant'
                                        ? route('supplierProfile', ['id' => $user->id])
                                        : route('customerProfile', ['id' => $user->id]);
                            @endphp


                            <a href="{{ $profileRoute }}" class="underline">
                                {{ $user->first_name . ' ' . $user->last_name ?? '-' }}
                            </a>
                            <br>
                            <small class="text-gray-500">
                                @php
                                    $businessName = $user->business_name ?? '-';
                                @endphp
                                — {{ $businessName }}
                            </small>

                            @if ($hasError)
                                <br>
                                <small class="text-danger">
                                    {{ translate('Error:') }} {{ $risk['error'] }}
                                </small>
                            @endif
                        </div>
                    </td>

                    @if ($hasError)
                        <!-- Error State - Show dashes for all score columns -->
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">
                            <span class="text-danger">{{ translate('No Data') }}</span>
                        </td>
                    @else
                        <!-- LPS Score -->
                        <td class="text-center score-cell-tooltip" data-score-type="lps"
                            data-score-value="{{ round($risk['lps'], 1) }}"
                            data-components="{{ json_encode($components['lps'] ?? []) }}"
                            data-notes="{{ $notes['lps'] ?? '' }}" data-flags="{{ json_encode($flags) }}"
                            data-weight="{{ $weights['lps'] }}"
                            data-weighted-contribution="{{ round($weightedContributions['lps'], 1) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="font-semibold {{ $risk['lps'] >= 70 ? 'text-success' : ($risk['lps'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                    {{ round($risk['lps'], 1) }}
                                </span>
                                <br>

                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                    {{ round($weightedContributions['lps'], 1) }} of {{ $weights['lps'] }}%
                                </div>
                                @if (isset($components['lps']))
                                    <div class="text-xs text-gray-400 mt-1">
                                        Age:{{ round($components['lps']['age_score'] ?? 0) }} |
                                        CR:{{ round($components['lps']['cr_score'] ?? 0) }} |
                                        Doc:{{ round($components['lps']['doc_score'] ?? 0) }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- CHS Score -->
                        <td class="text-center score-cell-tooltip" data-score-type="chs"
                            data-score-value="{{ round($risk['chs'], 1) }}"
                            data-components="{{ json_encode($components['chs'] ?? []) }}"
                            data-notes="{{ $notes['chs'] ?? '' }}" data-flags="{{ json_encode($flags) }}"
                            data-weight="{{ $weights['chs'] }}"
                            data-weighted-contribution="{{ round($weightedContributions['chs'], 1) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="font-semibold {{ $risk['chs'] >= 70 ? 'text-success' : ($risk['chs'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                    {{ round($risk['chs'], 1) }}
                                </span>
                                <br>
                                <small class="text-gray-500">
                                    {{ translate('Credit History') }}
                                </small>
                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                    {{ round($weightedContributions['chs'], 1) }} of {{ $weights['chs'] }}%
                                </div>
                                @if (in_array('no_bureau_data', $flags))
                                    <div class="text-xs text-warning mt-1">
                                        {{ translate('No Bureau') }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- BCS Score -->
                        <td class="text-center score-cell-tooltip" data-score-type="bcs"
                            data-score-value="{{ round($risk['bcs'], 1) }}"
                            data-components="{{ json_encode($components['bcs'] ?? []) }}"
                            data-notes="{{ $notes['bcs'] ?? '' }}" data-flags="{{ json_encode($flags) }}"
                            data-weight="{{ $weights['bcs'] }}"
                            data-weighted-contribution="{{ round($weightedContributions['bcs'], 1) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="font-semibold {{ $risk['bcs'] >= 70 ? 'text-success' : ($risk['bcs'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                    {{ round($risk['bcs'], 1) }}
                                </span>
                                <br>
                                <small class="text-gray-500">
                                    {{ translate('Banking & Cashflow') }}
                                </small>
                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                    {{ round($weightedContributions['bcs'], 1) }} of {{ $weights['bcs'] }}%
                                </div>
                                @if (isset($components['bcs']))
                                    <div class="text-xs text-gray-400 mt-1">
                                        Turnover:{{ round($components['bcs']['turnover_score'] ?? 0) }} |
                                        Volatility:{{ round($components['bcs']['volatility_score'] ?? 0) }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- BPS Score -->
                        <td class="text-center score-cell-tooltip" data-score-type="bps"
                            data-score-value="{{ round($risk['bps'], 1) }}"
                            data-components="{{ json_encode($components['bps'] ?? []) }}"
                            data-notes="{{ $notes['bps'] ?? '' }}" data-flags="{{ json_encode($flags) }}"
                            data-weight="{{ $weights['bps'] }}"
                            data-weighted-contribution="{{ round($weightedContributions['bps'], 1) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="font-semibold {{ $risk['bps'] >= 70 ? 'text-success' : ($risk['bps'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                    {{ round($risk['bps'], 1) }}
                                </span>
                                <br>
                                <small class="text-gray-500">
                                    {{ translate('Business Profile') }}
                                </small>
                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                    {{ round($weightedContributions['bps'], 1) }} of {{ $weights['bps'] }}%
                                </div>
                                @if (isset($components['bps']))
                                    <div class="text-xs text-gray-400 mt-1">
                                        Sector:{{ round($components['bps']['sector_score'] ?? 0) }} |
                                        Region:{{ round($components['bps']['region_score'] ?? 0) }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- BES Score -->
                        <td class="text-center score-cell-tooltip" data-score-type="bes"
                            data-score-value="{{ round($risk['bes'], 1) }}"
                            data-components="{{ json_encode($components['bes'] ?? []) }}"
                            data-notes="{{ $notes['bes'] ?? '' }}" data-flags="{{ json_encode($flags) }}"
                            data-weight="{{ $weights['bes'] }}"
                            data-weighted-contribution="{{ round($weightedContributions['bes'], 1) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="font-semibold {{ $risk['bes'] >= 70 ? 'text-success' : ($risk['bes'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                    {{ round($risk['bes'], 1) }}
                                </span>
                                <br>
                                <small class="text-gray-500">
                                    {{ translate('Behavioral') }}
                                </small>
                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                    {{ round($weightedContributions['bes'], 1) }} of {{ $weights['bes'] }}%
                                </div>
                                @if (isset($components['bes']))
                                    <div class="text-xs text-gray-400 mt-1">
                                        DPD:{{ round($components['bes']['ap_dpd_score'] ?? 0) }} |
                                        Utilization:{{ round($components['bes']['utilization_score'] ?? 0) }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- CAF Factor -->
                        <td class="text-center score-cell-tooltip" data-score-type="caf"
                            data-score-value="{{ round($risk['caf'], 2) }}"
                            data-components="{{ json_encode($components['caf'] ?? []) }}"
                            data-notes="{{ $notes['caf'] ?? '' }}" data-flags="{{ json_encode($flags) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="font-semibold {{ $risk['caf'] >= 1.0 ? 'text-success' : ($risk['caf'] >= 0.8 ? 'text-warning' : 'text-danger') }}">
                                    {{ round($risk['caf'], 2) }}
                                </span>
                                <br>
                                <small class="text-gray-500">
                                    {{ translate('Compliance') }}
                                </small>
                                <div class="text-xs text-gray-400 mt-1">
                                    Multiplicative Factor
                                </div>
                            </div>
                        </td>

                        <!-- OMRS Score -->
                        <td class="text-center score-cell-tooltip" data-score-type="omrs"
                            data-score-value="{{ round($risk['omrs'], 1) }}"
                            data-components="{{ json_encode($components) }}" data-notes="{{ json_encode($notes) }}"
                            data-flags="{{ json_encode($flags) }}">
                            <div class="whitespace-nowrap">
                                <span
                                    class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                    {{ round($risk['omrs'], 1) }}/100<br>
                                    - {{ $riskLevel }}
                                </span>
                                <br>
                                <small class="text-gray-500 mt-1 block">
                                    {{ translate('Overall Risk') }}
                                </small>
                                @php
                                    $totalWeighted = array_sum($weightedContributions);
                                    $cafAdjusted = $totalWeighted * $risk['caf'];
                                @endphp
                                <div class="text-xs text-green-600 mt-1 font-medium">
                                    Base: {{ round($totalWeighted, 1) }} × CAF: {{ $risk['caf'] }}
                                </div>
                            </div>
                        </td>

                        <!-- Risk Flags -->
                        <td class="text-center score-cell-tooltip" data-score-type="flags"
                            data-flags="{{ json_encode($flags) }}" data-count="{{ count($flags) }}">
                            <div class="whitespace-nowrap">
                                @if (count($flags) > 0)
                                    @foreach (array_slice($flags, 0, 3) as $flag)
                                        <span class="badge badge-sm badge-outline badge-danger inline-block mb-1"
                                            style="width: 7rem;">
                                            {{ $flag }}
                                        </span>
                                        @if (!$loop->last)
                                            <br>
                                        @endif
                                    @endforeach
                                    @if (count($flags) > 3)
                                        <span class="badge badge-sm badge-outline badge-warning inline-block">
                                            +{{ count($flags) - 3 }} more
                                        </span>
                                    @endif
                                @else
                                    <span class="text-success">-</span>
                                @endif
                            </div>
                        </td>
                    @endif

                    <!-- Action -->
                    <td class="text-center">
                        @if (Auth::user()->user_type == 'admin')
                            <div class="flex gap-1 justify-center">
                                @if ($hasError)
                                    <!-- Disabled buttons for error state -->
                                    <button class="btn btn-sm btn-icon btn-clear btn-secondary" disabled
                                        title="{{ translate('No risk data available') }}">
                                        <i class="ki-filled ki-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon btn-clear btn-secondary" disabled
                                        title="{{ translate('No risk data available') }}">
                                        <i class="ki-filled ki-notepad-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon btn-clear btn-secondary" disabled
                                        title="{{ translate('No risk data available') }}">
                                        <i class="ki-filled ki-setting-4"></i>
                                    </button>
                                @else
                                    <!-- Risk Details Button -->
                                    <a href="{{ route('risk.analysis.details', ['user' => $user->id, 'type' => $type]) }}"
                                        class="btn btn-sm btn-icon btn-clear btn-primary"
                                        title="{{ translate('View Detailed Analysis') }}">
                                        <i class="ki-filled ki-eye"></i>
                                    </a>

                                    <!-- Manual Score Adjustment Button -->
                                    @php
                                        $score = App\Models\RiskScore::where('user_id', $user->id)->first();
                                    @endphp

                                    <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                        data-modal-toggle="#score_modal" data-id="{{ $user->id }}"
                                        data-action="{{ route('risk.scoreUpdate') }}"
                                        data-score="{{ optional($score)->risk_score }}"
                                        data-reason="{{ optional($score)->reason }}"
                                        title="{{ translate('Manual Score Adjustment') }}">

                                        <i class="ki-filled ki-notepad-edit"></i>
                                    </a>

                                    <a class="btn btn-sm btn-icon btn-clear btn-info"
                                        title="{{ translate('Set Risk Weights') }}"
                                        data-modal-toggle="#risk_weight_modal" data-user-id="{{ $user->id }}">
                                        <i class="ki-filled ki-setting-4"></i>
                                    </a>
                                @endif
                            </div>
                        @else
                            <span class="text-muted">{{ translate('Unauthorized') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="modal" data-modal="true" id="score_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('User Risk Management') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <form action="{{ route('risk.scoreUpdate') }}" method="POST" class="px-5 pt-3">
                @csrf
                <input type="hidden" id="user_id" name="user_id">

                <div class="mb-4">
                    <label class="form-label" for="risk_score">{{ translate('Score') }}</label>
                    <input type="text" id="risk_score" name="risk_score" class="input"
                        value="{{ old('risk_score') }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="reason">{{ translate('Reason') }}</label>
                    <textarea id="reason" name="reason" class="textarea" required>{{ old('reason') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">{{ translate('Upgrade') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('layouts.includes.table-pagination', ['paginator' => $risks])
@include('admin.risk-management.components.risk-weight-modal')


@push('styles')
    <style>
        /* Header tooltip (light) */
        .table-tooltip {
            position: absolute;
            z-index: 99999;
            max-width: 420px;
            background: #fff;
            color: #111827;
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.45;
            display: none;
            pointer-events: none;
        }

        /* Cell tooltip (dark) */
        .table-tooltip.dark {
            background: #1f2937;
            color: #f9fafb;
            border: 1px solid #374151;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        .table-tooltip h4 {
            margin: 0 0 6px 0;
            font-size: 13px;
            font-weight: 700;
        }

        .table-tooltip p {
            margin: 6px 0;
        }

        .table-tooltip ul {
            margin: 6px 0;
            padding-left: 18px;
        }

        .table-tooltip ul li {
            margin-bottom: 4px;
        }

        .table-tooltip .muted {
            color: #9ca3af;
            font-size: 12px;
        }

        .table-tooltip .formula {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px;
            border-radius: 4px;
            font-family: monospace;
            margin: 8px 0;
            border-left: 3px solid #3b82f6;
        }

        .table-tooltip .component-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 8px 0;
        }

        .table-tooltip .component-item {
            padding: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            font-size: 12px;
        }

        .table-tooltip .risk-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin: 1px;
        }

        .table-tooltip .risk-badge.low {
            background: #10b981;
            color: white;
        }

        .table-tooltip .risk-badge.medium {
            background: #f59e0b;
            color: black;
        }

        .table-tooltip .risk-badge.high {
            background: #ef4444;
            color: white;
        }

        th[data-tooltip-key] {
            position: relative;
            cursor: help;
        }

        .score-cell-tooltip {
            cursor: help;
            position: relative;
        }

        .risk-component-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .risk-component-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            background: #f9fafb;
        }

        .risk-component-card h5 {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            color: #374151;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tooltip content for headers (light theme)
            const tooltipContent = {
                'user-id': `<h4>User ID</h4>
                <p>A unique database identifier for the user. Used to link all risk assessment records and perform system lookups.</p>`,

                'user-name': `<h4>User Name & Business</h4>
                <p>Displays the customer's full name and their registered business name. Click to open the detailed profile page for comprehensive risk analysis.</p>
                <p class="muted">Note: Some users may show error messages if customer records are missing or risk calculation failed.</p>`,

                'lps-score': `<h4>LPS - Legal & Profile Score (15% Weight)</h4>
                <p>Evaluates the legal standing and business profile completeness:</p>
                <ul>
                    <li><strong>Age Score (40%)</strong> - Business tenure based on CR issue date</li>
                    <li><strong>CR Score (30%)</strong> - Commercial Registration validity and status</li>
                    <li><strong>Document Score (30%)</strong> - Completeness of CR and Nafath verification</li>
                </ul>
                <div class="formula">LPS = 0.4 × Age + 0.3 × CR + 0.3 × Document</div>
                <p class="muted">Scoring: 0-39 (High Risk), 40-69 (Medium Risk), 70-100 (Low Risk)</p>`,

                'chs-score': `<h4>CHS - Credit History Score (25% Weight)</h4>
                <p>Assesses creditworthiness through bureau data:</p>
                <ul>
                    <li><strong>Bureau Rating Score</strong> - Credit rating from SIMAH bureau</li>
                    <li><strong>DPD Score</strong> - Days Past Due performance</li>
                    <li><strong>Max DPD 12M</strong> - Worst delinquency in last 12 months</li>
                </ul>
                <p class="muted">Note: Currently using default scores pending bureau integration. Flagged when no bureau data available.</p>`,

                'bcs-score': `<h4>BCS - Banking & Cashflow Score (20% Weight)</h4>
                <p>Analyzes financial health and transaction patterns:</p>
                <ul>
                    <li><strong>Turnover Score (35%)</strong> - Average monthly revenue</li>
                    <li><strong>Volatility Score (25%)</strong> - Revenue stability (std deviation/mean)</li>
                    <li><strong>Returned Score (25%)</strong> - Refund/order ratio</li>
                    <li><strong>Min Balance Score (15%)</strong> - Account balance adequacy</li>
                </ul>
                <div class="formula">BCS = 0.35 × Turnover + 0.25 × Volatility + 0.25 × Returned + 0.15 × Min Balance</div>
                <p class="muted">Thresholds: Low (&lt;5K), Medium (5K-50K), High (50K-200K), Premium (&gt;200K)</p>`,

                'bps-score': `<h4>BPS - Business Profile Score (10% Weight)</h4>
                <p>Evaluates business sector and geographic risks:</p>
                <ul>
                    <li><strong>Sector Score (70%)</strong> - Industry risk classification (1-5 scale)</li>
                    <li><strong>Region Score (30%)</strong> - Geographic risk based on city tier</li>
                </ul>
                <div class="formula">BPS = 0.7 × Sector + 0.3 × Region</div>
                <p class="muted">Sector Risk: 1=Low, 2=Medium-Low, 3=Medium, 4=High, 5=Very High</p>`,

                'bes-score': `<h4>BES - Behavioral & Experience Score (30% Weight)</h4>
                <p>Measures payment behavior and platform experience:</p>
                <ul>
                    <li><strong>AP DPD Score (40%)</strong> - Maximum late days in payments</li>
                    <li><strong>Utilization Score (25%)</strong> - Credit limit usage ratio</li>
                    <li><strong>Dispute Score (25%)</strong> - Refund request frequency</li>
                    <li><strong>Trend Score (10%)</strong> - Revenue growth trend</li>
                </ul>
                <div class="formula">BES = 0.4 × DPD + 0.25 × Utilization + 0.25 × Dispute + 0.10 × Trend</div>`,

                'caf-factor': `<h4>CAF - Compliance Adjustment Factor</h4>
                <p>Multiplicative factor for compliance and regulatory considerations:</p>
                <ul>
                    <li><strong>Default</strong>: 1.0 (neutral impact)</li>
                    <li><strong>Penalties</strong>: &lt;1.0 for compliance issues</li>
                    <li><strong>Bonuses</strong>: &gt;1.0 for exceptional compliance</li>
                </ul>
                <p class="muted">Currently set to default 1.0 pending compliance module integration.</p>`,

                'omrs-score': `<h4>OMRS - Overall Merchant Risk Score</h4>
                <p>Composite risk score combining all components:</p>
                <div class="formula">
                    Base = 0.15×LPS + 0.25×CHS + 0.20×BCS + 0.10×BPS + 0.30×BES<br>
                    OMRS = Base × CAF × 100
                </div>
                <p><strong>Risk Bands:</strong></p>
                <ul>
                    <li>80-100: Low Risk (Green)</li>
                    <li>60-79: Medium Risk (Yellow)</li>
                    <li>0-59: High Risk (Red)</li>
                </ul>`,

                'risk-flags': `<h4>Risk Flags & Warnings</h4>
                <p>Indicates data quality issues or risk concerns:</p>
                <ul>
                    <li><strong>no_bureau_data</strong> - Missing credit bureau information</li>
                    <li><strong>no_banking_data</strong> - Insufficient transaction history</li>
                    <li><strong>no_cr_issue_date</strong> - Missing business registration date</li>
                    <li><strong>no_credit_limit_info</strong> - Credit limit data unavailable</li>
                    <li><strong>no_behavior_history</strong> - No payment history available</li>
                </ul>
                <p class="muted">Flags help identify areas needing manual review or additional data collection.</p>`,

                'action': `<h4>Action Buttons</h4>
                <p>Admin-only risk management actions:</p>
                <ul>
                    <li><strong>View Details</strong> - Comprehensive risk breakdown and components</li>
                    <li><strong>Manual Adjustment</strong> - Override automated scores with expert judgment</li>
                    <li><strong>Set Weights</strong> - Customize scoring weights for specific risk models</li>
                </ul>`
            };

            // Create and manage tooltip element
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'table-tooltip';
            tooltipEl.setAttribute('role', 'tooltip');
            document.body.appendChild(tooltipEl);

            let activeTarget = null;
            let hideTimeout = null;

            function showTooltip(target, html, isDark = false) {
                if (!html) return;
                tooltipEl.innerHTML = html;
                tooltipEl.style.display = 'block';

                if (isDark) {
                    tooltipEl.classList.add('dark');
                } else {
                    tooltipEl.classList.remove('dark');
                }

                const rect = target.getBoundingClientRect();
                const ttRect = tooltipEl.getBoundingClientRect();
                const margin = 8;
                let top = rect.top - ttRect.height - margin;
                let left = rect.left + (rect.width / 2) - (ttRect.width / 2);

                if (top < 6) {
                    top = rect.bottom + margin;
                }

                left = Math.max(6, Math.min(left, window.innerWidth - ttRect.width - 6));
                tooltipEl.style.top = `${top + window.scrollY}px`;
                tooltipEl.style.left = `${left + window.scrollX}px`;
            }

            function hideTooltip() {
                tooltipEl.style.display = 'none';
                tooltipEl.classList.remove('dark');
                activeTarget = null;
            }

            // Generate cell tooltip content based on score type
            function generateCellTooltipContent(cell) {
                const scoreType = cell.dataset.scoreType;
                const scoreValue = cell.dataset.scoreValue;
                const components = JSON.parse(cell.dataset.components || '{}');
                const notes = cell.dataset.notes || '';
                const flags = JSON.parse(cell.dataset.flags || '[]');

                let content = '';

                switch (scoreType) {
                    case 'lps':
                        const businessYears = components.t_business_months ? (components.t_business_months / 12)
                            .toFixed(1) : 'N/A';
                        content = `
                            <h4>LPS - Legal & Profile Score</h4>
                            <p><strong>Overall Score:</strong> ${scoreValue}</p>
                            <div class="component-grid">
                                <div class="component-item">
                                    <strong>Age Score:</strong> ${components.age_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>CR Score:</strong> ${components.cr_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Document Score:</strong> ${components.doc_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Business Months:</strong> ${components.t_business_months ? components.t_business_months.toFixed(2) : 'N/A'}
                                </div>
                                <div class="component-item">
                                    <strong>Business Duration:</strong> ${businessYears !== 'N/A' ? businessYears + ' years' : 'N/A'}
                                </div>
                            </div>
                            <p class="muted">${notes}</p>
                        `;
                        break;

                    case 'chs':
                        content = `
                            <h4>CHS - Credit History Score</h4>
                            <p><strong>Overall Score:</strong> ${scoreValue}</p>
                            <div class="component-grid">
                                <div class="component-item">
                                    <strong>Bureau Rating:</strong> ${components.bureau_rating_score || 'N/A'}
                                </div>
                                <div class="component-item">
                                    <strong>DPD Score:</strong> ${components.dpd_score || 'N/A'}
                                </div>
                                <div class="component-item">
                                    <strong>Max DPD 12M:</strong> ${components.max_dpd_12m || 'N/A'}
                                </div>
                            </div>
                            <p class="muted">${notes}</p>
                            ${flags.includes('no_bureau_data') ? '<span class="risk-badge high">No Bureau Data</span>' : ''}
                        `;
                        break;

                    case 'bcs':
                        content = `
                            <h4>BCS - Banking & Cashflow Score</h4>
                            <p><strong>Overall Score:</strong> ${scoreValue}</p>
                            <div class="component-grid">
                                <div class="component-item">
                                    <strong>Turnover Score:</strong> ${components.turnover_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Volatility Score:</strong> ${components.volatility_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Returned Score:</strong> ${components.returned_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Min Balance Score:</strong> ${components.min_balance_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Avg Monthly Turnover:</strong> ${components.avg_monthly_turnover || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Volatility:</strong> ${components.volatility || 0}
                                </div>
                            </div>
                            <p class="muted">${notes}</p>
                        `;
                        break;

                    case 'bps':
                        content = `
                            <h4>BPS - Business Profile Score</h4>
                            <p><strong>Overall Score:</strong> ${scoreValue}</p>
                            <div class="component-grid">
                                <div class="component-item">
                                    <strong>Sector Score:</strong> ${components.sector_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Region Score:</strong> ${components.region_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Sector Risk Class:</strong> ${components.sector_risk_class || 'N/A'}
                                </div>
                                <div class="component-item">
                                    <strong>Region Risk Class:</strong> ${components.region_risk_class || 'N/A'}
                                </div>
                            </div>
                            <p class="muted">${notes}</p>
                        `;
                        break;

                    case 'bes':
                        content = `
                            <h4>BES - Behavioral & Experience Score</h4>
                            <p><strong>Overall Score:</strong> ${scoreValue}</p>
                            <div class="component-grid">
                                <div class="component-item">
                                    <strong>AP DPD Score:</strong> ${components.ap_dpd_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Utilization Score:</strong> ${components.utilization_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Dispute Score:</strong> ${components.dispute_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Trend Score:</strong> ${components.trend_score || 0}
                                </div>
                                <div class="component-item">
                                    <strong>DPD AP Max:</strong> ${components.dpd_ap_max || 0}
                                </div>
                                <div class="component-item">
                                    <strong>Utilization Ratio:</strong> ${components.utilization_ratio || 0}
                                </div>
                            </div>
                            <p class="muted">${notes}</p>
                        `;
                        break;

                    case 'caf':
                        content = `
                            <h4>CAF - Compliance Adjustment Factor</h4>
                            <p><strong>Factor:</strong> ${scoreValue}</p>
                            <p class="muted">${notes}</p>
                        `;
                        break;

                    case 'omrs':
                        content = `
                            <h4>OMRS - Overall Merchant Risk Score</h4>
                            <p><strong>Overall Score:</strong> ${scoreValue}</p>
                            <p><strong>Risk Level:</strong> ${scoreValue >= 80 ? 'Low Risk' : scoreValue >= 60 ? 'Medium Risk' : 'High Risk'}</p>
                            <div class="formula">
                                Base = 0.15×LPS + 0.25×CHS + 0.20×BCS + 0.10×BPS + 0.30×BES<br>
                                OMRS = Base × CAF × 100
                            </div>
                        `;
                        break;

                    case 'flags':
                        const flagCount = parseInt(cell.dataset.count) || 0;
                        content = `
                            <h4>Risk Flags</h4>
                            <p><strong>Total Flags:</strong> ${flagCount}</p>
                            ${flagCount > 0 ? `
                                                                                                                                                                                                                                                                    <div class="component-grid">
                                                                                                                                                                                                                                                                        ${flags.map(flag => `
                                        <div class="component-item">
                                            <span class="risk-badge high">${flag}</span>
                                        </div>
                                    `).join('')}
                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                ` : '<p>No risk flags detected</p>'}
                        `;
                        break;
                }

                return content;
            }

            // Attach tooltip events to table headers
            const ths = document.querySelectorAll('th[data-tooltip-key]');
            ths.forEach(th => {
                const key = th.getAttribute('data-tooltip-key');
                const content = tooltipContent[key] || '';

                th.addEventListener('mouseenter', (e) => {
                    clearTimeout(hideTimeout);
                    activeTarget = th;
                    showTooltip(th, content, false);
                });

                th.addEventListener('mouseleave', (e) => {
                    hideTimeout = setTimeout(hideTooltip, 150);
                });

                th.setAttribute('tabindex', '0');
                th.addEventListener('focus', (e) => {
                    clearTimeout(hideTimeout);
                    activeTarget = th;
                    showTooltip(th, content, false);
                });

                th.addEventListener('blur', (e) => {
                    hideTimeout = setTimeout(hideTooltip, 150);
                });
            });

            // Attach tooltip events to score cells
            const scoreCells = document.querySelectorAll('.score-cell-tooltip');
            scoreCells.forEach(cell => {
                cell.addEventListener('mouseenter', (e) => {
                    clearTimeout(hideTimeout);
                    activeTarget = cell;
                    const content = generateCellTooltipContent(cell);
                    showTooltip(cell, content, true);
                });

                cell.addEventListener('mouseleave', (e) => {
                    hideTimeout = setTimeout(hideTooltip, 150);
                });

                cell.setAttribute('tabindex', '0');
                cell.addEventListener('focus', (e) => {
                    clearTimeout(hideTimeout);
                    activeTarget = cell;
                    const content = generateCellTooltipContent(cell);
                    showTooltip(cell, content, true);
                });

                cell.addEventListener('blur', (e) => {
                    hideTimeout = setTimeout(hideTooltip, 150);
                });
            });

            // Risk Details Modal Handler
            document.querySelectorAll('.risk-details-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const riskData = JSON.parse(this.getAttribute('data-risk-data'));
                    const userId = this.getAttribute('data-user-id');

                    let detailsHtml = `
                    <div class="risk-details">
                        <h4 class="font-semibold mb-4">Risk Breakdown for User #${userId}</h4>
                        <div class="risk-component-grid">
                `;

                    // LPS Details
                    if (riskData.components && riskData.components.lps) {
                        detailsHtml += `
                        <div class="risk-component-card">
                            <h5>LPS Components</h5>
                            <p>Age Score: ${riskData.components.lps.age_score}</p>
                            <p>CR Score: ${riskData.components.lps.cr_score}</p>
                            <p>Document Score: ${riskData.components.lps.doc_score}</p>
                            <p>Business Months: ${riskData.components.lps.t_business_months || 'N/A'}</p>
                        </div>
                    `;
                    }

                    // BCS Details
                    if (riskData.components && riskData.components.bcs) {
                        detailsHtml += `
                        <div class="risk-component-card">
                            <h5>BCS Components</h5>
                            <p>Avg Monthly Turnover: ${riskData.components.bcs.avg_monthly_turnover}</p>
                            <p>Turnover Score: ${riskData.components.bcs.turnover_score}</p>
                            <p>Volatility Score: ${riskData.components.bcs.volatility_score}</p>
                            <p>Returned Score: ${riskData.components.bcs.returned_score}</p>
                        </div>
                    `;
                    }

                    // BPS Details
                    if (riskData.components && riskData.components.bps) {
                        detailsHtml += `
                        <div class="risk-component-card">
                            <h5>BPS Components</h5>
                            <p>Sector Risk Class: ${riskData.components.bps.sector_risk_class}</p>
                            <p>Sector Score: ${riskData.components.bps.sector_score}</p>
                            <p>Region Risk Class: ${riskData.components.bps.region_risk_class}</p>
                            <p>Region Score: ${riskData.components.bps.region_score}</p>
                        </div>
                    `;
                    }

                    // BES Details
                    if (riskData.components && riskData.components.bes) {
                        detailsHtml += `
                        <div class="risk-component-card">
                            <h5>BES Components</h5>
                            <p>DPD AP Max: ${riskData.components.bes.dpd_ap_max}</p>
                            <p>AP DPD Score: ${riskData.components.bes.ap_dpd_score}</p>
                            <p>Utilization Ratio: ${riskData.components.bes.utilization_ratio}</p>
                            <p>Dispute Rate: ${riskData.components.bes.dispute_rate}</p>
                        </div>
                    `;
                    }

                    detailsHtml += `
                        </div>
                        <div class="mt-4">
                            <h5 class="font-semibold">Risk Flags</h5>
                            <p>${riskData.flags ? riskData.flags.join(', ') : 'No flags'}</p>
                        </div>
                        <div class="mt-4">
                            <h5 class="font-semibold">Calculation Notes</h5>
                            <pre class="text-sm bg-gray-100 p-2 rounded">${JSON.stringify(riskData.notes, null, 2)}</pre>
                        </div>
                    </div>
                `;

                    document.getElementById('riskDetailsContent').innerHTML = detailsHtml;
                    document.getElementById('riskDetailsModal').style.display = 'block';
                });
            });

            // Manual Adjustment Handler
            const scoreButtons = document.querySelectorAll('[data-modal-toggle="#score_modal"]');
            scoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('score_modal');
                    const form = modal.querySelector('form');
                    form.setAttribute('action', this.getAttribute('data-action'));
                    form.querySelector('#user_id').value = this.getAttribute('data-id');
                    form.querySelector('#risk_score').value = this.getAttribute('data-score') || '';
                    form.querySelector('#reason').value = this.getAttribute('data-reason') || '';
                });
            });

            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('riskDetailsModal');
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });

            // Hide tooltip on scroll/resize
            window.addEventListener('scroll', () => {
                if (activeTarget) {
                    if (activeTarget.classList.contains('score-cell-tooltip')) {
                        const content = generateCellTooltipContent(activeTarget);
                        showTooltip(activeTarget, content, true);
                    } else {
                        const key = activeTarget.getAttribute('data-tooltip-key');
                        const content = tooltipContent[key] || '';
                        showTooltip(activeTarget, content, false);
                    }
                }
            }, {
                passive: true
            });

            window.addEventListener('resize', hideTooltip);
        });
    </script>
@endpush
