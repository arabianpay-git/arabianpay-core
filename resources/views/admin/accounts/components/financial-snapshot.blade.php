@push('styles')
    <style>
        .chart-container {
            display: flex;
            flex-direction: column;
            /* Defined height ensures the container doesn't grow indefinitely */
            height: 350px;
        }

        .apex-chart-wrapper {
            flex-grow: 1;
            width: 100%;
            /* Constrains the chart area */
            max-height: 300px;
            min-height: 250px;
        }

        .apexcharts-legend {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            justify-content: center !important;
            white-space: nowrap !important;
            padding-top: 10px !important;
        }

        .apexcharts-legend-series {
            margin: 0 10px !important;
            display: flex !important;
            align-items: center !important;
        }
    </style>
@endpush

<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-slate-800">{{ translate('Performance Metrics') }}</h3>
        <span class="text-[10px] text-slate-400">{{ translate('Updated') }}
            {{ now()->format('d M Y') }}</span>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-900 text-white p-4 rounded-xl">
            <p class="text-[10px] opacity-70 font-bold uppercase">{{ translate('Total Revenue') }}</p>
            <p class="text-lg font-bold">SAR {{ number_format($totalRevenue ?? 0) }}</p>
        </div>
        <div class="bg-emerald-600 text-white p-4 rounded-xl">
            <p class="text-[10px] opacity-70 font-bold uppercase">{{ translate('Avg Order Value') }}</p>
            <p class="text-lg font-bold">SAR {{ number_format($avgOrderValue ?? 0, 2) }}</p>
        </div>
        <div class="bg-orange-500 text-white p-4 rounded-xl">
            <p class="text-[10px] opacity-70 font-bold uppercase">{{ translate('Completion Rate') }}</p>
            <p class="text-lg font-bold">{{ $completionRate ?? 0 }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
        <div class="chart-container">
            <p class="text-sm font-bold text-slate-700 mb-4">
                {{ translate('Revenue Trend (Last 6 Months)') }}</p>
            <div id="revenue_trend_chart" class="apex-chart-wrapper"></div>
        </div>
        <div class="chart-container">
            <p class="text-sm font-bold text-slate-700 mb-4">{{ translate('Order Status Distribution') }}
            </p>
            <div id="order_distribution_chart" class="apex-chart-wrapper"></div>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mt-8 pt-6 border-t border-slate-100">
        <div class="text-center border-r border-slate-50">
            <div class="text-lg font-bold text-blue-700">{{ $totalProducts ?? 0 }}</div>
            <div class="text-[10px] text-slate-500 uppercase">{{ translate('Products') }}</div>
        </div>
        <div class="text-center border-r border-slate-50">
            <div class="text-lg font-bold text-emerald-600">{{ $pendingOrders ?? 0 }}</div>
            <div class="text-[10px] text-slate-500 uppercase">{{ translate('Pending Orders') }}</div>
        </div>
        <div class="text-center border-r border-slate-50">
            <div class="text-lg font-bold text-orange-500">{{ $cancelledOrders ?? 0 }}</div>
            <div class="text-[10px] text-slate-500 uppercase">{{ translate('Cancelled') }}</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-purple-600">SAR {{ number_format($pendingPayouts ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-500 uppercase">{{ translate('Pending Payouts') }}</div>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        // 1. Revenue Trend
        const revenueTrendOptions = {
            series: [{
                name: '{{ translate('Revenue') }}',
                data: @json($monthlyRevenue ?? [])
            }],
            chart: {
                type: 'line',
                height: 300, // Fixed height prevents infinite expansion
                toolbar: {
                    show: false
                },
                sparkline: {
                    enabled: false
                }
            },
            colors: ['#3b82f6'],
            stroke: {
                width: 3,
                curve: 'smooth'
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
            markers: {
                size: 4,
                colors: ['#3b82f6'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: {
                    size: 6
                }
            },
            xaxis: {
                categories: @json($monthLabels ?? []),
                labels: {
                    style: {
                        colors: '#94a3b8',
                        fontSize: '10px'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#94a3b8',
                        fontSize: '10px'
                    },
                    formatter: function(value) {
                        return 'SAR ' + (value >= 1000 ? (value / 1000).toFixed(0) + 'K' : value);
                    }
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4,
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return 'SAR ' + value.toLocaleString();
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            }
        };

        new ApexCharts(document.querySelector("#revenue_trend_chart"), revenueTrendOptions).render();

        // 2. Order Distribution Donut
        const orderDistOptions = {
            series: [{{ $completedOrders ?? 0 }}, {{ $pendingOrders ?? 0 }}, {{ $cancelledOrders ?? 0 }}],
            chart: {
                type: 'donut',
                height: 300 // Fixed height prevents infinite expansion
            },
            colors: ['#10b981', '#f59e0b', '#ef4444'],
            labels: ['{{ translate('Completed') }}', '{{ translate('Pending') }}', '{{ translate('Cancelled') }}'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: '{{ translate('Total Orders') }}',
                                color: '#6b7280',
                                formatter: function(w) {
                                    return {{ $totalOrders ?? 0 }};
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                itemMargin: {
                    horizontal: 12,
                    vertical: 5
                }
            }
        };

        new ApexCharts(document.querySelector("#order_distribution_chart"), orderDistOptions).render();
    </script>
@endpush
