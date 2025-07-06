@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            /* Make the legend items flow horizontally and center them */
            #loanFlowChart .apexcharts-legend,
            #paymentStatusChart .apexcharts-legend,
            #revenueChart .apexcharts-legend,
            #orderStatusChart .apexcharts-legend,
            #settlementChart .apexcharts-legend,
            #defaultRatesChart .apexcharts-legend,
            #walletChart .apexcharts-legend {
                display: grid !important;
                grid-auto-flow: column;
                grid-auto-columns: max-content;
                justify-content: center;
                align-items: center;
                gap: .15rem;
                padding-bottom: 0.5rem;
            }

            /* Prevent labels from wrapping */
            #loanFlowChart .apexcharts-legend-text,
            #paymentStatusChart .apexcharts-legend-text,
            #revenueChart .apexcharts-legend-text,
            #orderStatusChart .apexcharts-legend-text,
            #settlementChart .apexcharts-legend-text,
            #defaultRatesChart .apexcharts-legend,
            #walletChart .apexcharts-legend {
                white-space: nowrap !important;
            }

            .dash-card {
                padding-inline-start: .875rem;
                padding-inline-end: .875rem;
            }
        </style>
    @endpush



    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Dashboard') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-normal text-gray-700">
                        {{ translate('Welcome back! Here’s a quick overview of your application’s performance and recent activity.') }}
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="flex">
                        <select class="select select-sm w-40" id="loanRange">
                            <option value="1M" {{ request('date_range') == '1M' ? 'selected' : '' }}>
                                {{ translate('1 Month') }}</option>
                            <option value="3M" {{ request('date_range') == '3M' ? 'selected' : '' }}>
                                {{ translate('3 Months') }}</option>
                            <option value="6M" {{ request('date_range') == '6M' ? 'selected' : '' }}>
                                {{ translate('6 Months') }}</option>
                            <option value="12M"
                                {{ request('date_range') == '12M' || request('date_range') == null ? 'selected' : '' }}>
                                {{ translate('12 Months') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-3 lg:gap-7.5">
                <div class="grid lg:grid-cols-5 gap-y-3 lg:gap-4 items-stretch">
                    <!-- Active Loans -->
                    <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                        <div class="card">
                            <div class="card-body dash-card">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold">{{ translate('Active Loans') }}</h4>
                                        <div class="text-2xl font-bold">{{ number_format($loanData['active_loans']) }}
                                        </div>
                                    </div>
                                    <div class="bg-purple-100 p-3 rounded-full">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delinquency Rate -->
                    <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                        <div class="card">
                            <div class="card-body dash-card">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold">{{ translate('Delinquency Rate') }}</h4>
                                        <div class="text-2xl font-bold">{{ $loanData['overdue_instalments']['rate'] }}%
                                        </div>
                                    </div>
                                    <div class="bg-red-100 p-3 rounded-full">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Avg. Credit Score -->
                    <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                        <div class="card">
                            <div class="card-body dash-card">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold">{{ translate('Avg. Credit Score') }}</h4>
                                        <div class="text-2xl font-bold">{{ round($riskData['credit_scores'], 1) }}</div>
                                    </div>
                                    <div class="bg-blue-100 p-3 rounded-full">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Liquidity -->
                    <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                        <div class="card">
                            <div class="card-body dash-card">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold">{{ translate('Current Liquidity') }}</h4>
                                        <div class="text-2xl font-bold"><span
                                                class="icon-saudi_riyal"></span>{{ number_format($financialData['wallet_balances']->last()['balance']) }}
                                        </div>
                                    </div>
                                    <div class="bg-green-100 p-3 rounded-full">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Utilization -->
                    <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                        <div class="card">
                            <div class="card-body dash-card">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold">{{ translate('Credit Utilization') }}</h4>
                                        <div class="text-2xl font-bold">
                                            {{ $riskData['credit_utilization']['utilization_percent'] }}%</div>
                                    </div>
                                    <div class="bg-yellow-100 p-3 rounded-full">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <!-- Charts Grid: Organized into logical rows -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                        <!-- Row 1: Loan Flow (wide), Payment Status, Risk Exposure -->
                        <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Loan Flow') }}</h3>
                            </div>

                            <div id="loanFlowChart"></div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Payment Status') }}</h3>
                            </div>
                            <div id="paymentStatusChart"></div>
                        </div>

                        <!-- Row 2: Credit Utilization, Revenue Streams (wide), Wallet Balances -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Credit Utilization') }}</h3>
                            </div>
                            <div id="creditUtilizationChart"></div>
                        </div>

                        <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Revenue Streams') }}</h3>
                            </div>
                            <div id="revenueChart"></div>
                        </div>

                        <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Wallet Balances') }}</h3>
                            </div>
                            <div id="walletChart"></div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Order Status') }}</h3>
                            </div>
                            <div id="orderStatusChart"></div>
                        </div>

                        <div class="card col-span-3 md:col-span-2 lg:col-span-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Risk Exposure') }}</h3>
                            </div>
                            <div id="riskExposureChart"></div>
                        </div>

                        <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Fulfillment Times') }}</h3>
                            </div>
                            <div id="fulfillmentChart"></div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Settlement Status') }}</h3>
                            </div>
                            <div id="settlementChart"></div>
                        </div>

                        <!-- Optional blank card for alignment if needed -->
                        <div class="hidden lg:block"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Category-wise Product Sales -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Category-wise Product Sales') }}</h3>
                            </div>
                            <div id="categorySalesChart"></div>
                        </div>

                        <!-- Category-wise Product Stock -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Category-wise Product Stock') }}</h3>
                            </div>
                            <div id="categoryStockChart"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endsection
    {{-- @dd($riskData['default_rates']); --}}

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Loan Flow
                new ApexCharts(document.querySelector('#loanFlowChart'), {
                    chart: {
                        type: 'line',
                        height: 350
                    },
                    series: [{
                            name: 'Disbursed',
                            data: @json($loanData['disbursement_vs_repayment']['disbursed'])
                        },
                        {
                            name: 'Repaid',
                            data: @json($loanData['disbursement_vs_repayment']['repaid'])
                        }
                    ],
                    xaxis: {
                        categories: @json($loanData['disbursement_vs_repayment']['months'])
                    },
                    colors: ['#3B82F6', '#10B981']
                }).render();

                // Payment Status
                new ApexCharts(document.querySelector('#paymentStatusChart'), {
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center'
                    },
                    series: @json($loanData['payment_status']->pluck('count')),
                    colors: ['#F59E0B', '#10B981', '#3B82F6', '#EF4444'],
                    labels: @json($loanData['payment_status']->pluck('status')),
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    total: {
                                        show: true
                                    }
                                }
                            }
                        }
                    }
                }).render();

                // Risk Exposure
                new ApexCharts(document.querySelector('#riskExposureChart'), {
                    chart: {
                        type: 'heatmap',
                        height: 350
                    },
                    plotOptions: {
                        heatmap: {
                            shadeIntensity: 0.5
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    series: @json($riskData['default_rates']),
                    colors: ['#10B981', '#F59E0B', '#EF4444'], // ✅ COMMA ADDED HERE
                    yaxis: {
                        categories: ['abc', 'def', 'ghi'] // ✅ Static categories
                    }
                }).render();

                // Credit Utilization
                new ApexCharts(document.querySelector('#creditUtilizationChart'), {
                    chart: {
                        type: 'radialBar',
                        height: 350
                    },
                    series: [@json($riskData['credit_utilization']['utilization_percent'])],
                    plotOptions: {
                        radialBar: {
                            hollow: {
                                size: '60%'
                            }
                        }
                    },
                    labels: ['Credit Used']
                }).render();

                // Revenue Streams
                new ApexCharts(document.querySelector('#revenueChart'), {
                    chart: {
                        type: 'bar',
                        height: 350,
                        stacked: true
                    },
                    series: [{
                            name: 'Revenue',
                            data: @json($financialData['revenue_breakdown']['revenue'])
                        },
                        {
                            name: 'Shipping',
                            data: @json($financialData['revenue_breakdown']['shipping'])
                        },
                        {
                            name: 'Discounts',
                            data: @json($financialData['revenue_breakdown']['discounts'])
                        }
                    ]
                }).render();

                // Wallet Balances
                new ApexCharts(document.querySelector('#walletChart'), {
                    chart: {
                        type: 'area',
                        height: 350
                    },
                    series: [{
                            name: 'Overall Balance',
                            data: @json($financialData['wallet_balances']->pluck('balance'))
                        },
                        {
                            name: 'User Payment',
                            data: @json($financialData['wallet_balances']->pluck('user_payment'))
                        },
                        {
                            name: 'Loan Disbursment',
                            data: @json($financialData['wallet_balances']->pluck('loan_disbursment'))
                        },
                        {
                            name: 'Seller Payment',
                            data: @json($financialData['wallet_balances']->pluck('seller_payment'))
                        }
                    ],
                    xaxis: {
                        categories: @json($financialData['wallet_balances']->pluck('month'))
                    }
                }).render();


                // Operational: Order Status
                new ApexCharts(document.querySelector('#orderStatusChart'), {
                    chart: {
                        type: 'pie',
                        height: 350
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center'
                    },
                    series: @json($operationalData['order_statuses']->pluck('count')),
                    colors: ['#F59E0B', '#10B981', '#EF4444', '#3B82F6'],
                    labels: @json($operationalData['order_statuses']->pluck('status'))
                }).render();

                // // Risk: Default Rates
                // new ApexCharts(document.querySelector('#defaultRatesChart'), {
                //     chart: { type: 'bar', height: 350 },
                //     legend: { position: 'bottom', horizontalAlign: 'center'},
                //     series: @json($riskData['default_rates']),
                //     xaxis: {
                //         categories: @json(array_column($riskData['default_rates'], 'name'))
                //     }
                // }).render();

                // Operational: Fulfillment Times
                new ApexCharts(document.querySelector('#fulfillmentChart'), {
                    chart: {
                        type: 'line',
                        height: 350
                    },
                    series: [{
                        name: 'Avg Days',
                        data: @json($operationalData['fulfillment_times']['series'])
                    }],
                    xaxis: {
                        categories: @json($operationalData['fulfillment_times']['months'])
                    }
                }).render();

                // Operational: Settlement Status
                new ApexCharts(document.querySelector('#settlementChart'), {
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center'
                    },
                    series: @json($operationalData['settlement_status']->pluck('count')),
                    colors: ['#10B981', '#EF4444', '#F59E0B'],

                    labels: @json($operationalData['settlement_status']->pluck('settlement_status'))
                }).render();

                // Category: Sales
                new ApexCharts(document.querySelector("#categorySalesChart"), {
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    xaxis: {
                        categories: @json($categorySales->pluck('name'))
                    },
                    series: [{
                        name: 'Sales',
                        data: @json($categorySales->pluck('sales'))
                    }],
                    colors: ['#3B82F6']
                }).render();

                // Category: Stock
                new ApexCharts(document.querySelector("#categoryStockChart"), {
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    xaxis: {
                        categories: @json($categoryStock->pluck('name'))
                    },
                    series: [{
                        name: 'Stock',
                        data: @json($categoryStock->pluck('stock'))
                    }],
                    colors: ['#10B981']
                }).render();

                // date-range filter reload
                document.querySelectorAll('.select').forEach(sel => {
                    sel.addEventListener('change', function() {
                        window.location = `/admin/dashboard?date_range=${this.value}`;
                    });
                });
            });
        </script>
    @endpush
