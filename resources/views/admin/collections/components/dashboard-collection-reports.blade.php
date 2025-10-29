{{-- Required Custom CSS for DPD Distribution Labels and ApexCharts Legend Fix --}}
@push('styles')
    <style>
        /* Custom styling for the DPD labels grid to achieve the requested look */
        .dpd-labels-grid {
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5rem;
        }

        /* Vertical separator for desktop (lg and above) */
        @media (min-width: 1024px) {
            .dpd-labels-grid>div:not(:last-child) {
                border-right: 1px solid #e5e7eb;
            }
        }

        /* Remove border on the right for the second item on mobile/tablet (grid-cols-2) */
        @media (max-width: 1023px) {
            .dpd-labels-grid>div:nth-child(2n) {
                border-right: none;
            }
        }

        /* Uniform styling for select dropdowns for consistent card header height */
        .form-select-uniform {
            width: 9rem;
            height: 32px;
            line-height: 1.25;
        }

        /* Force ApexCharts legend items onto a single row */
        .apexcharts-legend.apx-legend-position-bottom.apexcharts-align-center,
        .apexcharts-legend.apx-legend-position-top.apexcharts-align-center {
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            display: flex !important;
            -webkit-box-orient: horizontal !important;
            -webkit-box-direction: normal !important;
        }

        /* Ensure both chart containers have equal height */
        .chart-container {
            height: 320px !important;
            min-height: 320px !important;
        }

        .apexcharts-legend {
            flex-direction: row !important;
        }
    </style>
@endpush

<div class="container-fixed mb-7.5">
    <div class="grid grid-cols-1 lg:grid-cols-1 gap-5">
        <!-- Collection Performance - Bar Chart -->
        <div class="card shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col">
            <div class="card-header border-b border-gray-200 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="card-title font-semibold text-base text-gray-900">
                        {{ translate('Collection Performance') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Monthly collection vs targets</p>
                </div>
                <div class="flex items-center gap-2">
                    <select id="collection-range" name="collection_range" class="select">
                        <option value="7" {{ request('collection_range') == '7' ? 'selected' : '' }}>Last 7 days
                        </option>
                        <option value="30" {{ request('collection_range', '30') == '30' ? 'selected' : '' }}>Last 30
                            days</option>
                        <option value="90" {{ request('collection_range') == '90' ? 'selected' : '' }}>Last 90 days
                        </option>
                    </select>
                </div>
            </div>
            <div class="card-body p-6 flex-grow">
                <div id="collection-chart" class="chart-container"></div>
            </div>
        </div>

        <!-- DPD Distribution - Donut Chart (unchanged) -->
        <div class="card shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col">
            <div class="card-header border-b border-gray-200 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="card-title font-semibold text-base text-gray-900">
                        {{ translate('DPD Distribution') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Days Past Due analysis</p>
                </div>
                <div class="flex items-center gap-2">
                    <select id="dpd-period" name="dpd_period" class="select">
                        <option value="current" {{ request('dpd_period', 'current') == 'current' ? 'selected' : '' }}>
                            Current Month</option>
                        <option value="previous" {{ request('dpd_period') == 'previous' ? 'selected' : '' }}>
                            Previous Month</option>
                        <option value="quarter" {{ request('dpd_period') == 'quarter' ? 'selected' : '' }}>
                            Quarterly</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-6 flex flex-col flex-grow">
                <div id="dpd-chart" class="flex items-center justify-center">
                    <!-- Chart will render here -->
                </div>

                <!-- Custom DPD Labels Grid -->
                <div class="dpd-labels-grid grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6 w-full text-center">
                    @foreach ($dpdDistribution['labels'] as $index => $label)
                        <div class="flex flex-col items-center p-2">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <span class="text-xl font-extrabold text-gray-900">
                                    {{ $dpdDistribution['data'][$index] }}%
                                </span>
                            </div>
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full"
                                    style="background-color: {{ $dpdDistribution['colors'][$index] }}"></div>
                                <span class="text-xs text-gray-600 font-medium whitespace-nowrap">
                                    {{ $label }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart data prepared server-side
            const collectionData = {
                labels: @json($collectionPerformance['labels']),
                collected: @json($collectionPerformance['collected']),
                target: @json($collectionPerformance['target']),
                remaining: @json($collectionPerformance['remaining']),
                overdue: @json($collectionPerformance['overdue']),
            };

            // Collection Performance - Bar Chart (now includes Remaining series)
            const collectionChart = new ApexCharts(document.querySelector("#collection-chart"), {
                series: [{
                        name: 'Collected',
                        data: collectionData.collected
                    },
                    {
                        name: 'Target',
                        data: collectionData.target
                    },
                    {
                        name: 'Remaining',
                        data: collectionData.remaining
                    },
                    {
                        name: 'Overdue',
                        data: collectionData.overdue
                    }
                ],
                chart: {
                    type: 'bar',
                    height: '100%',
                    stacked: false,
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4,
                        borderRadiusApplication: 'end',
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f9f9f9', 'transparent'],
                        opacity: 0.5
                    }
                },
                xaxis: {
                    categories: collectionData.labels,
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount (SAR)',
                        style: {
                            fontSize: '12px',
                            color: '#6B7280'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return 'SAR ' + val.toLocaleString();
                        },
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '12px'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return 'SAR ' + val.toLocaleString();
                        }
                    }
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '65%'
                            }
                        }
                    }
                }]
            });
            collectionChart.render();

            // DPD Distribution chart unchanged
            const dpdChart = new ApexCharts(document.querySelector("#dpd-chart"), {
                series: @json($dpdDistribution['data']),
                chart: {
                    type: 'donut',
                    height: '100%'
                },
                labels: @json($dpdDistribution['labels']),
                colors: @json($dpdDistribution['colors']),
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Accounts',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#6B7280',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + '%';
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex] + '%';
                    },
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    },
                    dropShadow: {
                        enabled: true
                    }
                },
                legend: {
                    show: false
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        chart: {
                            width: '100%'
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '60%'
                                }
                            }
                        }
                    }
                }]
            });
            dpdChart.render();

            // selects logic (unchanged)
            const collectionSelect = document.getElementById('collection-range');
            const dpdSelect = document.getElementById('dpd-period');

            function updateChartsQueryParam() {
                const collVal = collectionSelect.value;
                const dpdVal = dpdSelect.value;
                const params = new URLSearchParams(window.location.search);

                if (collVal) params.set('collection_range', collVal);
                else params.delete('collection_range');

                if (dpdVal) params.set('dpd_period', dpdVal);
                else params.delete('dpd_period');

                window.location.href = window.location.pathname + '?' + params.toString();
            }

            collectionSelect.addEventListener('change', updateChartsQueryParam);
            dpdSelect.addEventListener('change', updateChartsQueryParam);
        });
    </script>
@endpush
