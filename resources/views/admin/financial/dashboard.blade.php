@extends('layouts.base')

@push('styles')
 
<style>
    /* Enhanced KPI Cards */
    .kpi-card {
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        border-top: 3px solid transparent;
    }

    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .kpi-card.trend-positive {
        border-top-color: #10B981;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
    }

    .kpi-card.trend-negative {
        border-top-color: #EF4444;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, transparent 100%);
    }

    .kpi-card.trend-neutral {
        border-top-color: #6B7280;
    }

    .trend-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 6px;
    }

    .trend-indicator.positive {
        background-color: rgba(16, 185, 129, 0.1);
        color: #10B981;
    }

    .trend-indicator.negative {
        background-color: rgba(239, 68, 68, 0.1);
        color: #EF4444;
    }

    .trend-indicator.neutral {
        background-color: rgba(107, 114, 128, 0.1);
        color: #6B7280;
    }

    .sparkline-container {
        height: 30px;
        margin-top: 8px;
        opacity: 0.7;
    }

    .kpi-icon-wrapper {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%);
        transition: all 0.3s ease;
    }

    .kpi-card:hover .kpi-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    /* Liquid Meter Gauge Styles */
    .liquid-gauge-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto;
    }

    .liquid-gauge-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        position: relative;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        box-shadow: 
            0 4px 20px rgba(0,0,0,0.08),
            inset 0 2px 8px rgba(0,0,0,0.05);
        border: 8px solid #e9ecef;
        overflow: hidden;
    }

    .liquid-wave-container {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 0%;
        transition: height 2s cubic-bezier(0.4, 0.0, 0.2, 1);
        overflow: hidden;
    }

    .liquid-wave {
        position: absolute;
        bottom: 0;
        left: -100%;
        width: 300%;
        height: 100%;
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        border-radius: 45%;
        animation: wave 8s linear infinite;
        opacity: 0.9;
    }

    .liquid-wave:nth-child(2) {
        animation: wave 6s linear infinite reverse;
        opacity: 0.7;
        background: linear-gradient(135deg, #60a5fa 0%, #93c5fd 100%);
    }

    .liquid-wave:nth-child(3) {
        animation: wave 10s linear infinite;
        opacity: 0.5;
        background: linear-gradient(135deg, #93c5fd 0%, #dbeafe 100%);
    }

    @keyframes wave {
        0% {
            transform: translateX(0) translateY(0);
        }
        50% {
            transform: translateX(-25%) translateY(-10px);
        }
        100% {
            transform: translateX(-50%) translateY(0);
        }
    }

    .liquid-percentage {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        text-shadow: 0 2px 4px rgba(255,255,255,0.8);
        display: flex;
        align-items: baseline;
        gap: 2px;
    }

    .liquid-percentage-symbol {
        font-size: 24px;
        font-weight: 600;
        color: #64748b;
    }

    .liquid-label {
        text-align: center;
        margin-top: 12px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Success color variant */
    .liquid-gauge-success .liquid-wave {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    }

    .liquid-gauge-success .liquid-wave:nth-child(2) {
        background: linear-gradient(135deg, #34d399 0%, #6ee7b7 100%);
    }

    .liquid-gauge-success .liquid-wave:nth-child(3) {
        background: linear-gradient(135deg, #6ee7b7 0%, #d1fae5 100%);
    }

    /* Warning color variant */
    .liquid-gauge-warning .liquid-wave {
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    }

    .liquid-gauge-warning .liquid-wave:nth-child(2) {
        background: linear-gradient(135deg, #fbbf24 0%, #fcd34d 100%);
    }

    .liquid-gauge-warning .liquid-wave:nth-child(3) {
        background: linear-gradient(135deg, #fcd34d 0%, #fef3c7 100%);
    }

    /* Danger color variant */
    .liquid-gauge-danger .liquid-wave {
        background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
    }

    .liquid-gauge-danger .liquid-wave:nth-child(2) {
        background: linear-gradient(135deg, #f87171 0%, #fca5a5 100%);
    }

    .liquid-gauge-danger .liquid-wave:nth-child(3) {
        background: linear-gradient(135deg, #fca5a5 0%, #fee2e2 100%);
    }

    /* Make the legend items flow horizontally and center them */
    #monthlyCashFlowChart .apexcharts-legend,
    #accountTypesChart .apexcharts-legend {
        display: grid !important;
        grid-auto-flow: column;
        grid-auto-columns: max-content;
        justify-content: center;
        align-items: center;
        gap: .15rem;
        padding-bottom: 0.5rem;
    }

    /* Prevent labels from wrapping */
    #monthlyCashFlowChart .apexcharts-legend-text,
    #accountTypesChart .apexcharts-legend-text {
        white-space: nowrap !important;
    }

    /* Bootstrap Timeline Styles */
    .pools-timeline {
        min-height: 200px;
        padding: 10px 0 35px 0;
    }
    
    .timeline-months-header {
        margin-bottom: 15px;
        font-weight: bold;
        border-bottom: 2px solid #e4e6ea;
        padding-bottom: 8px;
    }
    
    .timeline-month {
        text-align: center;
        font-size: 11px;
        color: #7e8299;
        padding: 8px 2px;
        border-right: 1px solid #f1f1f4;
        white-space: nowrap;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
    }
    
    .timeline-month:last-child {
        border-right: none;
    }
    
    .timeline-pool-row {
        margin-bottom: 8px;
        min-height: 35px;
        position: relative;
        padding: 2px 0;
    }
    
    .pool-badge {
        position: absolute;
        height: 30px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        min-width: 60px;
    }
    
    .pool-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10;
    }
    
    .pool-badge-high {
        background: linear-gradient(135deg, #50cd89, #3ac47d);
    }
    
    .pool-badge-medium {
        background: linear-gradient(135deg, #ffc700, #f1bc00);
    }
    
    .pool-badge-low {
        background: linear-gradient(135deg, #f1416c, #e02454);
    }
    
    .pool-badge-closed {
        background: linear-gradient(135deg, #7e8299, #6c727f);
    }
    
    .timeline-grid-lines {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 1;
    }
    
    .grid-line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 1px;
        background-color: #f1f1f4;
    }

    .grid-line-label {
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 11px;
        font-weight: 600;
        color: #7e8299;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .timeline-today-indicator {
        position: absolute;
        top: -10px;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, #3b82f6 0%, #60a5fa 100%);
        z-index: 5;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        animation: todayPulse 2s ease-in-out infinite;
    }

    .timeline-today-indicator::before {
        content: 'Today';
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: #3b82f6;
        color: white;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
    }

    .timeline-today-indicator::after {
        content: '';
        position: absolute;
        top: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 6px solid #3b82f6;
    }

    @keyframes todayPulse {
        0%, 100% {
            opacity: 1;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }
        50% {
            opacity: 0.8;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
        }
    }
</style>
@endpush

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>

    <!-- Container -->
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Financial Dashboard
                </h1>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>Overview of your financial position and recent activities</span>
                    <span class="badge badge-sm badge-outline">{{ date('F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-3 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-7.5 mb-5">
            
           

            <!-- Monthly Net Income -->
            @php
                $netIncomeTrend = $kpis['net_income_trend'] ?? 0;
                $netIncomeClass = $netIncomeTrend > 0 ? 'trend-positive' : ($netIncomeTrend < 0 ? 'trend-negative' : 'trend-neutral');
                $incomeTrendClass = $netIncomeTrend > 0 ? 'positive' : ($netIncomeTrend < 0 ? 'negative' : 'neutral');
            @endphp
            <div class="card kpi-card cursor-pointer {{ $netIncomeClass }}" onclick="window.location='{{ route('financial.accounts.ledger', 4000) }}'">
                <div class="card-body">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600 mb-2">Monthly Net Income</div>
                            <div class="text-2xl font-bold text-gray-900 mb-1"><span class="icon-saudi_riyal"></span>{{ number_format($kpis['net_income'], 2) }}</div>
                            <div class="trend-indicator {{ $incomeTrendClass }}">
                                <i class="ki-filled {{ $netIncomeTrend >= 0 ? 'ki-arrow-up' : 'ki-arrow-down' }} text-xs"></i>
                                <span>{{ number_format(abs($netIncomeTrend), 1) }}%</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrapper">
                            <i class="ki-filled ki-dollar text-2xl text-success"></i>
                        </div>
                    </div>
                    <div class="sparkline-container">
                        <div id="netIncomeSparkline" class="w-full h-full"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100">
                        <div class="flex justify-between text-success"><span>Revenue:</span><span class="font-medium"><span class="icon-saudi_riyal"></span>{{ number_format($kpis['monthly_revenue'], 0) }}</span></div>
                        <div class="flex justify-between mt-1 text-danger"><span>Expenses:</span><span class="font-medium"><span class="icon-saudi_riyal"></span>{{ number_format($kpis['monthly_expenses'], 0) }}</span></div>
                    </div>
                </div>
            </div>

            <!-- Accounts Receivable -->
            @php
                $arTrend = $kpis['accounts_receivable_trend'] ?? 0;
                $arClass = $arTrend > 0 ? 'trend-positive' : ($arTrend < 0 ? 'trend-negative' : 'trend-neutral');
                $arTrendClass = $arTrend > 0 ? 'positive' : ($arTrend < 0 ? 'negative' : 'neutral');
            @endphp
            <div class="card kpi-card cursor-pointer {{ $arClass }}" onclick="window.location='{{ route('financial.accounts.ledger', 1203) }}'">
                <div class="card-body">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600 mb-2">Accounts Receivable</div>
                            <div class="text-2xl font-bold text-gray-900 mb-1"><span class="icon-saudi_riyal"></span>{{ number_format($kpis['accounts_receivable'], 2) }} </div>
                            <div class="trend-indicator {{ $arTrendClass }}">
                                <i class="ki-filled {{ $arTrend >= 0 ? 'ki-arrow-up' : 'ki-arrow-down' }} text-xs"></i>
                                <span>{{ number_format(abs($arTrend), 1) }}%</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrapper">
                            <i class="ki-filled ki-arrow-left text-2xl text-success"></i>
                        </div>
                    </div>
                    <div class="sparkline-container">
                        <div id="accountsReceivableSparkline" class="w-full h-full"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100"><span>Outstanding customers loans</span></div>
                </div>
            </div>

            <!-- Accounts Payable -->
            @php
                $apTrend = $kpis['accounts_payable_trend'] ?? 0;
                // For payables, increase is negative (red), decrease is positive (green)   
                $apClass = $apTrend < 0 ? 'trend-positive' : ($apTrend > 0 ? 'trend-negative' : 'trend-neutral');
                $apTrendClass = $apTrend < 0 ? 'positive' : ($apTrend > 0 ? 'negative' : 'neutral');
            @endphp
            <div class="card kpi-card cursor-pointer {{ $apClass }}" onclick="window.location='{{ route('financial.accounts.ledger', 2400) }}'">
                <div class="card-body">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-600 mb-2">Accounts Payable</div>
                            <div class="text-2xl font-bold text-gray-900 mb-1"><span class="icon-saudi_riyal"></span>{{ number_format($kpis['accounts_payable'], 2) }}</div>
                            <div class="trend-indicator {{ $apTrendClass }}">
                                <i class="ki-filled {{ $apTrend >= 0 ? 'ki-arrow-up' : 'ki-arrow-down' }} text-xs"></i>
                                <span>{{ number_format(abs($apTrend), 1) }}%</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrapper">
                            <i class="ki-filled ki-arrow-right text-2xl text-danger"></i>
                        </div>
                    </div>
                    <div class="sparkline-container">
                        <div id="accountsPayableSparkline" class="w-full h-full"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100"><span>Outstanding suppliers loans</span></div>
                </div>
            </div>
        </div>

       

        <!-- Investment Pools Summary -->
        <div class="card mb-5">
            <div class="card-header">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="card-title">
                        <i class="ki-filled ki-chart-pie text-primary mr-2"></i>
                        Investment Pools Summary
                    </h3>
                   
                </div>
            </div>
            <div class="card-body">
                <!-- Summary Stats -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5 mb-5">
                        <div class="flex flex-col gap-4">       
                            <div class="flex items-center gap-3 p-3  rounded-lg">
                                 <div class="kpi-icon-wrapper">
                            <i class="ki-filled ki-arrow-right text-2xl text-danger"></i>
                        </div>
                                <div class="flex-1">
                                    <div class="text-xs font-medium text-gray-600 mb-1">Total Disbursed (Year)</div>
                                    <div class="text-xl font-bold text-gray-900">
                                    <span class="icon-saudi_riyal"></span>    {{ number_format((float) $investmentPools['summary']['total_disbursed']/1000, 2) }}k
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3  rounded-lg">
                                 <div class="kpi-icon-wrapper">
                            <i class="ki-filled ki-arrow-left text-2xl text-success"></i>
                        </div>
                                <div class="flex-1">
                                    <div class="text-xs font-medium text-gray-600 mb-1">Total Collected (Year)</div>
                                    <div class="text-xl font-bold text-gray-900">
                                      <span class="icon-saudi_riyal"></span>  {{ number_format((float) $investmentPools['summary']['total_collected']/1000, 2) }}k
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-center">
                            @php
                                $collectionRate = $investmentPools['summary']['avg_collection_rate'];
                                $gaugeVariant = $collectionRate >= 90 ? 'success' : ($collectionRate >= 70 ? 'warning' : 'danger');
                            @endphp
                            <div>
                                <div class="liquid-gauge-container">
                                    <div class="liquid-gauge-circle liquid-gauge-{{ $gaugeVariant }}">
                                        <div class="liquid-wave-container" data-percentage="{{ $collectionRate }}">
                                            <div class="liquid-wave"></div>
                                            <div class="liquid-wave"></div>
                                            <div class="liquid-wave"></div>
                                        </div>
                                        <div class="liquid-percentage">
                                            <span class="liquid-percentage-value">{{ number_format($collectionRate, 1) }}</span>
                                            <span class="liquid-percentage-symbol">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="liquid-label">Collection Rate</div>
                            </div>
                        </div>
                    </div>
              

                <!-- Timeline Section -->
                <div class="border-t pt-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <h5 class="text-lg font-semibold text-gray-800">Pools Timeline</h5>
                        <div class="flex items-center gap-2">
                            <button id="test-timeline-btn" class="btn btn-sm btn-light-info">
                                <i class="ki-filled ki-setting-2"></i>
                                Test
                            </button>
                            <button id="timeline-year-prev" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-left"></i>
                            </button>
                            <span id="timeline-year" class="fw-bold px-3">{{ date('Y') }}</span>
                            <button id="timeline-year-next" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="flex flex-wrap items-center gap-4 mb-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-success rounded-full badge"></div>
                            <span class="text-gray-700">(≥90%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-warning rounded-full badge"></div>
                            <span class="text-gray-700">(70-89%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-danger rounded-full badge"></div>
                            <span class="text-gray-700">(<70%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-gray-400 rounded-full badge"></div>
                            <span class="text-gray-700">Closed</span>
                        </div>
                    </div>
                    
                    <!-- Timeline Container -->
                    <div id="pools-timeline" class="pools-timeline">
                        <!-- Timeline will be generated by JavaScript -->
                    </div>
                </div>

                <div class="modal fade" id="poolDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Investment Pool Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="poolDetailsContent">
                                <!-- Pool details will be loaded here -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary">Edit Pool</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Trial Balance -->
        <div class="card mb-5">
            <div class="card-header">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="card-title">
                        Trial Balance - {{ \Carbon\Carbon::parse($trialBalance['balanceDate'])->format('F d, Y') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        @php
                            $isBalanced = abs($trialBalance['totalDebits'] - $trialBalance['totalCredits']) < 0.01;
                        @endphp
                        @if($isBalanced)
                            <span class="badge badge-success badge-sm">
                                <i class="ki-filled ki-check-circle"></i>
                                Balanced
                            </span>
                        @else
                            <span class="badge badge-danger badge-sm">
                                <i class="ki-filled ki-cross-circle"></i>
                                Unbalanced
                            </span>
                        @endif
                        <span class="text-sm text-gray-600">
                            {{ $trialBalance['trialBalance']->count() }} Accounts
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($trialBalance['trialBalance']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-rounded table-striped border gs-7">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                                    <th class="min-w-150px">Account Code</th>
                                    <th class="min-w-250px">Account Name</th>
                                    <th class="min-w-100px">Type</th>
                                    <th class="min-w-120px text-end">Debit</th>
                                    <th class="min-w-120px text-end">Credit</th>
                                    <th class="min-w-120px text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trialBalance['trialBalance'] as $account)
                                    <tr class="cursor-pointer hover:bg-gray-50" title="View ledger"
                                        data-ledger-url="{{ route('financial.accounts.ledger', $account->id) }}"
                                        onclick="window.location.href=this.dataset.ledgerUrl">
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $account->id }}</div>
                                        </td>
                                        <td>
                                            <div class="font-semibold text-gray-900">{{ $account->account_name }}</div>
                                            <div class="text-xs text-gray-500">
                                                @if($account->account_type1 == 1)
                                                    Budget Account
                                                @else
                                                    Non-Budget Account
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($account->account_type1 == 1 && $account->account_type2 == 1)
                                                <span class="badge badge-sm badge-primary">
                                                    Assets
                                                </span>
                                            @elseif($account->account_type1 == 1 && $account->account_type2 == 2)
                                                <span class="badge badge-sm badge-info">
                                                    Liabilities
                                                </span>
                                            @elseif($account->account_type1 == 2 && $account->account_type2 == 1)
                                                <span class="badge badge-sm badge-danger">
                                                    Expenses
                                                </span>
                                            @else
                                                <span class="badge badge-sm badge-success">
                                                    Revenue
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($account->total_debit > 0)
                                                <span class="font-semibold text-gray-900">
                                                   <span class="icon-saudi_riyal"></span> {{ number_format($account->total_debit, 2) }} 
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($account->total_credit > 0)
                                                <span class="font-semibold text-gray-900">
                                                    <span class="icon-saudi_riyal"></span> {{ number_format($account->total_credit, 2) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="font-bold {{ $account->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                <span class="icon-saudi_riyal"></span> {{ number_format(abs($account->balance), 2) }}
                                            </span>
                                            @if($account->balance < 0)
                                                <div class="text-xs text-danger">(Negative)</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                           
                            <tfoot>
                                <tr class="border-top-2 bg-gray-50">
                                    <td colspan="3" class="font-bold text-gray-900 py-4">
                                        TOTALS
                                    </td>
                                    <td class="text-end font-bold text-gray-900 py-4">
                                        <span class="icon-saudi_riyal"></span> {{ number_format($trialBalance['totalDebits'], 2) }}
                                    </td>
                                    <td class="text-end font-bold text-gray-900 py-4">
                                        <span class="icon-saudi_riyal"></span> {{ number_format($trialBalance['totalCredits'], 2) }}
                                    </td>
                                    <td class="text-end py-4">
                                        @php
                                            $difference = abs($trialBalance['totalDebits'] - $trialBalance['totalCredits']);
                                            $isBalanced = $difference < 0.01;
                                        @endphp
                                        <span class="badge badge-{{ $isBalanced ? 'success' : 'danger' }} badge-sm">
                                            @if($isBalanced)
                                                Balanced
                                            @else
                                               Difference: <span class="icon-saudi_riyal"></span>{{ number_format($difference, 2) }} 
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-20">
                        <i class="ki-filled ki-file-sheet text-5xl text-gray-300 mb-5"></i>
                        <h3 class="text-gray-600 text-lg font-medium mb-2">No Accounts Found</h3>
                        <p class="text-gray-500 mb-6">No accounts with transactions found for the selected date.</p>
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('financial.accounts.create') }}" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Create Account
                            </a>
                            <a href="{{ route('financial.transactions.create') }}" class="btn btn-sm btn-light">
                                <i class="ki-filled ki-note-2"></i>
                                Create Transaction
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
         <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5 mb-5">
            <!-- Monthly Cash Flow Chart -->
            <div class="lg:col-span-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Monthly Cash Flow Trend</h3>
                    </div>
                    <div class="card-body">
                        <div id="monthlyCashFlowChart"></div>
                    </div>
                </div>
            </div>

           
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="application/json" id="monthly-data-json">
{!! json_encode([
    'revenue' => collect($chartData['monthly_data'])->pluck('revenue'),
    'expenses' => collect($chartData['monthly_data'])->pluck('expenses'),
    'net' => collect($chartData['monthly_data'])->pluck('net'),
    'months' => collect($chartData['monthly_data'])->pluck('month'),
]) !!}
</script>
<script type="application/json" id="account-types-json">
{!! json_encode([
    'series' => collect($chartData['account_types'])->pluck('count'),
    'labels' => collect($chartData['account_types'])->pluck('account_type1'),
]) !!}
</script>
<script type="application/json" id="kpi-sparklines-json">
{!! json_encode([
    'netWorth' => $kpis['net_worth_sparkline'] ?? [],
    'netIncome' => $kpis['net_income_sparkline'] ?? [],
    'accountsReceivable' => $kpis['accounts_receivable_sparkline'] ?? [],
    'accountsPayable' => $kpis['accounts_payable_sparkline'] ?? []
]) !!}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Read JSON payloads embedded by Blade (keeps JS clean for static parsers)
    const monthlyPayload = JSON.parse(document.getElementById('monthly-data-json').textContent || '{}');
    const accountTypesPayload = JSON.parse(document.getElementById('account-types-json').textContent || '{}');

    const monthlyRevenue = monthlyPayload.revenue || [];
    const monthlyExpenses = monthlyPayload.expenses || [];
    const monthlyNet = monthlyPayload.net || [];
    const monthlyMonths = monthlyPayload.months || [];

    const accountTypeCounts = accountTypesPayload.series || [];
    const accountTypeLabels = accountTypesPayload.labels || [];
    // Monthly Cash Flow Chart
    new ApexCharts(document.querySelector('#monthlyCashFlowChart'), {
        chart: {
            type: 'area',
            height: 350,
            toolbar: {
                show: false
            }
        },
        series: [{
            name: 'Revenue',
            data: monthlyRevenue
        }, {
            name: 'Expenses',
            data: monthlyExpenses
        }, {
            name: 'Net',
            data: monthlyNet
        }],
        xaxis: {
            categories: monthlyMonths,
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        colors: ['#10B981', '#EF4444', '#3B82F6'],
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: '#f1f1f1',
        },
        markers: {
            size: 4,
            colors: ['#10B981', '#EF4444', '#3B82F6'],
            strokeColors: '#fff',
            strokeWidth: 2
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            markers: {
                width: 8,
                height: 8,
                radius: 12
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value.toFixed(2) + ' SR';
                }
            }
        }
    }).render();

    // Sparklines for KPI cards using tiny Apex line charts
    function renderSparkline(canvasId, series, color) {
        const el = document.getElementById(canvasId);
        if (!el) return;

        new ApexCharts(el, {
            chart: {
                type: 'area',
                height: 40,
                sparkline: { enabled: true }
            },
            stroke: { curve: 'smooth', width: 2 },
            series: [{ data: series }],
            fill: { opacity: 0.15 },
            colors: [color],
            markers: { size: 0 },
            tooltip: { enabled: false }
        }).render();
    }

    // Read sparkline payloads embedded by Blade
    (function() {
        const el = document.getElementById('kpi-sparklines-json');
        if (!el) return;
        try {
            const kpiPayload = JSON.parse(el.textContent || '{}');
            renderSparkline('netWorthSparkline', kpiPayload.netWorth || [], '#3B82F6');
            renderSparkline('netIncomeSparkline', kpiPayload.netIncome || [], '#10B981');
            renderSparkline('accountsReceivableSparkline', kpiPayload.accountsReceivable || [], '#F59E0B');
            renderSparkline('accountsPayableSparkline', kpiPayload.accountsPayable || [], '#EF4444');
        } catch (e) {
            console.warn('Failed to parse KPI sparklines JSON', e);
        }
    })();

    // Animate Liquid Gauge
    function animateLiquidGauge() {
        const waveContainers = document.querySelectorAll('.liquid-wave-container');
        
        waveContainers.forEach(container => {
            const percentage = parseFloat(container.getAttribute('data-percentage')) || 0;
            
            // Use setTimeout to allow the DOM to render first
            setTimeout(() => {
                container.style.height = percentage + '%';
            }, 100);
        });
    }

    // Initialize liquid gauge animation
    animateLiquidGauge();

    // Account Types Chart
    new ApexCharts(document.querySelector('#accountTypesChart'), {
        chart: {
            type: 'donut',
            height: 350,
            toolbar: {
                show: false
            }
        },
    series: accountTypeCounts,
    labels: accountTypeLabels,
        colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            markers: {
                width: 8,
                height: 8,
                radius: 12
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px',
                            fontFamily: 'Inter, sans-serif',
                            color: '#64748B'
                        },
                        value: {
                            show: true,
                            fontSize: '16px',
                            fontFamily: 'Inter, sans-serif',
                            color: '#1E293B',
                            formatter: function(val) {
                                return val;
                            }
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '14px',
                            fontFamily: 'Inter, sans-serif',
                            color: '#64748B'
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        }
    }).render();

    // Localized route template for pool details page (preserves current locale/prefix)
    const poolShowTemplate = "{{ route('investment-pools.show', ['pool' => '__ID__']) }}";

    // Timeline View Variables
    let timelineYear = new Date().getFullYear();

    // Timeline Functions
    function renderTimeline() {
        const timelineContainer = document.getElementById('pools-timeline');
        const yearSpan = document.getElementById('timeline-year');
        
        if (!timelineContainer || !yearSpan) return;
        
        yearSpan.textContent = timelineYear;
        
        // Load pools for timeline
        const startDate = `${timelineYear}-01-01`;
        const endDate = `${timelineYear}-12-31`;
        
        console.log('Loading timeline data...');
        
        fetch(`/admin/investment-pools/calendar-events?start=${startDate}&end=${endDate}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(pools => {
                console.log('API data received:', pools);
                generateTimeline(pools);
            })
            .catch(error => {
                console.log('API failed, using sample data:', error.message);
                // Use sample data as fallback
                generateTimeline(getSamplePools());
            });
    }

    function getSamplePools() {
        return [
            {
                title: 'Pool A - Q1 2025',
                start: `${timelineYear}-01-15`,
                end: `${timelineYear}-03-15`,
                extendedProps: {
                    poolData: {
                        id: 1,
                        name: 'Pool A - Q1 2025',
                        collection_rate: 95,
                        total_disbursed: 250000,
                        total_collected: 237500,
                        total_checkouts: 150,
                        status: 'active',
                        start_date: `${timelineYear}-01-15`,
                        end_date: `${timelineYear}-03-15`
                    }
                }
            },
            {
                title: 'Pool B - Feb-Apr',
                start: `${timelineYear}-02-01`,
                end: `${timelineYear}-04-01`,
                extendedProps: {
                    poolData: {
                        id: 2,
                        name: 'Pool B - Feb-Apr',
                        collection_rate: 78,
                        total_disbursed: 180000,
                        total_collected: 140400,
                        total_checkouts: 98,
                        status: 'active',
                        start_date: `${timelineYear}-02-01`,
                        end_date: `${timelineYear}-04-01`
                    }
                }
            },
            {
                title: 'Pool C - Spring',
                start: `${timelineYear}-03-10`,
                end: `${timelineYear}-05-10`,
                extendedProps: {
                    poolData: {
                        id: 3,
                        name: 'Pool C - Spring',
                        collection_rate: 88,
                        total_disbursed: 320000,
                        total_collected: 281600,
                        total_checkouts: 220,
                        status: 'active',
                        start_date: `${timelineYear}-03-10`,
                        end_date: `${timelineYear}-05-10`
                    }
                }
            },
            {
                title: 'Pool D - Summer',
                start: `${timelineYear}-06-01`,
                end: `${timelineYear}-08-01`,
                extendedProps: {
                    poolData: {
                        id: 4,
                        name: 'Pool D - Summer',
                        collection_rate: 92,
                        total_disbursed: 400000,
                        total_collected: 368000,
                        total_checkouts: 280,
                        status: 'active',
                        start_date: `${timelineYear}-06-01`,
                        end_date: `${timelineYear}-08-01`
                    }
                }
            },
            {
                title: 'Pool E - H2',
                start: `${timelineYear}-09-15`,
                end: `${timelineYear}-11-15`,
                extendedProps: {
                    poolData: {
                        id: 5,
                        name: 'Pool E - H2',
                        collection_rate: 85,
                        total_disbursed: 350000,
                        total_collected: 297500,
                        total_checkouts: 195,
                        status: 'active',
                        start_date: `${timelineYear}-09-15`,
                        end_date: `${timelineYear}-11-15`
                    }
                }
            }
        ];
    }

    function generateTimeline(pools) {
        const container = document.getElementById('pools-timeline');
        if (!container) {
            console.error('Timeline container not found');
            return;
        }
        
        console.log('Generating timeline with pools:', pools);
        
        container.innerHTML = '';

        // Month names
        const monthNames = [
            'Jan', 'Feb', 'Mar',
            'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep',
            'Oct', 'Nov', 'Dec'
        ];

        // Create months header row
        const headerRow = document.createElement('div');
        headerRow.className = 'row timeline-months-header g-0';
        
        monthNames.forEach(month => {
            const monthCol = document.createElement('div');
            monthCol.className = 'col timeline-month';
            monthCol.textContent = month;
            headerRow.appendChild(monthCol);
        });
        
       // container.appendChild(headerRow);

        // Create timeline container with relative positioning
        const timelineBody = document.createElement('div');
        timelineBody.style.position = 'relative';
        timelineBody.style.minHeight = '200px';

        // Add grid lines with month labels
        const gridLines = document.createElement('div');
        gridLines.className = 'timeline-grid-lines';
        
        for (let i = 0; i < 12; i++) {
            const line = document.createElement('div');
            line.className = 'grid-line';
            const position = (i / 12) * 100;
            line.style.left = `${position}%`;
            
            // Add month label at the bottom of each line
            const label = document.createElement('div');
            label.className = 'grid-line-label';
            label.textContent = monthNames[i];
            line.appendChild(label);
            
            gridLines.appendChild(line);
        }
        timelineBody.appendChild(gridLines);

        // Add "Today" indicator if viewing current year
        const today = new Date();
        const currentYear = today.getFullYear();
        
        if (timelineYear === currentYear) {
            const currentMonth = today.getMonth(); // 0-11
            const currentDay = today.getDate();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            
            // Calculate exact position: month position + day position within month
            const monthProgress = currentDay / daysInMonth;
            const todayPosition = ((currentMonth + monthProgress) / 12) * 100;
            
            const todayIndicator = document.createElement('div');
            todayIndicator.className = 'timeline-today-indicator';
            todayIndicator.style.left = `${todayPosition}%`;
            todayIndicator.title = `Today: ${today.toLocaleDateString()}`;
            timelineBody.appendChild(todayIndicator);
        }

        // Process each pool
        if (pools && pools.length > 0) {
            console.log(`Processing ${pools.length} pools`);
            pools.forEach((pool, index) => {
                const poolElement = createTimelinePoolBadge(pool, index);
                timelineBody.appendChild(poolElement);
            });
        } else {
            console.log('No pools to display');
            // Add a message for empty timeline
            const emptyMessage = document.createElement('div');
            emptyMessage.style.position = 'absolute';
            emptyMessage.style.top = '50%';
            emptyMessage.style.left = '50%';
            emptyMessage.style.transform = 'translate(-50%, -50%)';
            emptyMessage.style.textAlign = 'center';
            emptyMessage.style.color = '#a1a5b7';
            emptyMessage.innerHTML = '<i class="ki-filled ki-information-2 text-2xl mb-2"></i><br>No investment pools for this year';
            timelineBody.appendChild(emptyMessage);
        }

        container.appendChild(timelineBody);
    }

    function createTimelinePoolBadge(pool, rowIndex) {
        const poolData = pool.extendedProps.poolData;
        const startDate = new Date(pool.start);
        const endDate = new Date(pool.end);
        
        // Calculate position and width
        const startMonth = startDate.getMonth(); // 0-11
        const endMonth = endDate.getMonth(); // 0-11
        
        // Handle year spanning
        let monthSpan;
        if (endDate.getFullYear() > startDate.getFullYear()) {
            monthSpan = (12 - startMonth);
        } else {
            monthSpan = (endMonth - startMonth) + 1;
        }
        
        // Ensure minimum width and maximum span
        monthSpan = Math.max(1, Math.min(12, monthSpan));
        
        // Calculate position as percentage
        const leftPosition = (startMonth / 12) * 100;
        const width = (monthSpan / 12) * 100;

        // Create badge element
        const badge = document.createElement('div');
        badge.className = 'pool-badge';
        
        // Position the badge
        badge.style.left = leftPosition + '%';
        badge.style.width = width + '%';
        badge.style.top = (rowIndex * 40) + 'px';
        
        // Add performance styling
        const rate = parseFloat(poolData.collection_rate || 0);
        if (poolData.status !== 'active') {
            badge.classList.add('pool-badge-closed');
        } else if (rate >= 90) {
            badge.classList.add('pool-badge-high');
        } else if (rate >= 70) {
            badge.classList.add('pool-badge-medium');
        } else {
            badge.classList.add('pool-badge-low');
        }

        // Badge content
        badge.textContent = pool.title;
        badge.title = `${pool.title}\nPeriod: ${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}\nCollection Rate: ${rate}%\nAmount: ${parseFloat(poolData.total_disbursed || 0).toLocaleString()} SR`;

        // Click handler: navigate to pool details page (localized URL)
        badge.addEventListener('click', () => {
            if (poolData && poolData.id) {
                const target = poolShowTemplate.replace('__ID__', poolData.id);
                window.location.href = target;
            } else {
                console.warn('Pool ID missing in timeline data; cannot navigate.');
            }
        });

        return badge;
    }

    // Timeline navigation handlers
    const timelinePrevBtn = document.getElementById('timeline-year-prev');
    const timelineNextBtn = document.getElementById('timeline-year-next');
    const testTimelineBtn = document.getElementById('test-timeline-btn');
    
    if (timelinePrevBtn) {
        timelinePrevBtn.addEventListener('click', () => {
            timelineYear--;
            renderTimeline();
        });
    }
    
    if (timelineNextBtn) {
        timelineNextBtn.addEventListener('click', () => {
            timelineYear++;
            renderTimeline();
        });
    }

    if (testTimelineBtn) {
        testTimelineBtn.addEventListener('click', () => {
            console.log('Testing timeline with sample data...');
            generateTimeline(getSamplePools());
        });
    }

    // Initialize timeline on page load
    console.log('Initializing timeline...');
    setTimeout(() => {
        renderTimeline();
    }, 1000);
});

// investment pools bootstrap Timeline
</script>
@endpush
