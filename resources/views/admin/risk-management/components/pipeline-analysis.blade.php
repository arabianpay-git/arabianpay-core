@push('styles')
    <style>
        .rounded-5 {
            border-radius: 5px;
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .chart-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            background-color: #ffffff;
            transition: all 0.3s ease;
        }


        .trend-dot {
            font-size: 0.875rem;
        }
    </style>
@endpush

<div class="container-fixed mt-4">
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <h3 class="card-title font-semibold text-base text-gray-900">
                {{ translate('Pipeline Analysis') }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ translate('Underwriting performance and application flow metrics') }}
            </p>
        </div>

        <div class="card-body p-6">
            <!-- SLA Metrics -->
            <div class="grid grid-cols-3 md:grid-cols-3 gap-4 mb-4">
                @foreach ($pipelineData['sla_metrics'] as $metric => $data)
                    @if (in_array($metric, ['underwriting', 'approval', 'disbursement']))
                        <div class="chart-card">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-gray-700 capitalize">
                                    {{ translate(str_replace('_', ' ', $metric) . ' SLA') }}
                                </p>
                                <span
                                    class="text-xs px-2 py-1 rounded-5 {{ $data['compliance'] >= 90 ? 'bg-green-100 text-green-800' : ($data['compliance'] >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $data['compliance'] }}% {{ translate('Compliance') }}
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between mb-2">
                                <h3 class="text-xl font-bold text-gray-900">{{ $data['current'] }}</h3>
                                <span class="text-sm text-gray-500">{{ translate('Target:') }}
                                    {{ $data['target'] }}</span>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                {{ translate('Trend (Last 7 Days):') }}
                                @foreach (array_slice($data['trend'], -7) as $trendValue)
                                    <span
                                        class="trend-dot {{ $trendValue >= 90 ? 'text-success' : ($trendValue >= 80 ? 'text-warning' : 'text-red-500') }}">●</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Pipeline Summary Cards -->
            <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-4">
                @php
                    $summaryColors = ['blue', 'orange', 'green', 'purple'];
                    $summaryIcons = ['document', 'time', 'check-circle', 'clock'];
                    $summaryKeys = ['total_applications', 'active_applications', 'completion_rate'];
                    $summaryLabels = ['Total Applications', 'Active Applications', 'Completion Rate'];
                @endphp
                @foreach ($summaryKeys as $index => $key)
                    <div class="stat-card card border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ translate($summaryLabels[$index]) }}
                                </p>
                                <h3 class="text-2xl font-bold text-{{ $summaryColors[$index] }}-600">
                                    @if ($key === 'completion_rate')
                                        {{ number_format((float) $pipelineData['summary'][$key], 2) }}%
                                    @else
                                        {{ number_format((int) $pipelineData['summary'][$key]) }}
                                    @endif
                                </h3>
                            </div>
                            <div class="p-3 bg-{{ $summaryColors[$index] }}-50 rounded-lg">
                                <i
                                    class="ki-filled ki-{{ $summaryIcons[$index] }} text-{{ $summaryColors[$index] }}-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Processing Time Cards -->
            <div class="grid lg:grid-cols-5 gap-y-3 lg:gap-4 items-stretch">
                @foreach ($pipelineData['processing_times'] as $key => $value)
                    @php
                        $color = match ($key) {
                            'avg_approval' => 'green',
                            'avg_rejection' => 'red',
                            'min' => 'blue',
                            'max' => 'orange',
                            'avg_processing_time' => 'purple',
                            default => 'gray',
                        };
                        $label = str_replace('_', ' ', ucfirst($key));
                    @endphp
                    <div class="chart-card text-center">
                        <p class="text-sm text-gray-600 mb-1">{{ translate($label) }}</p>
                        <p class="text-lg font-semibold text-{{ $color }}-600">
                            {{ is_numeric($value) ? $value : $value }}
                            @if ($key === 'completion_rate' || $key === 'avg_processing_time')
                                %
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                <!-- Application Decision Distribution -->
                <div class="chart-card">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-gray-900 text-base">
                            {{ translate('Application Decision Distribution') }}</h4>
                    </div>
                    <div id="approval-chart"></div>
                    <div class="mt-4 text-center text-sm text-gray-600">
                        {{ translate('Total Applications:') }}
                        {{ number_format($pipelineData['approval_rates']['total_applications']) }}
                    </div>
                </div>

                <!-- Exception Rate Breakdown -->
                <div class="chart-card">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-gray-900 text-base">{{ translate('Exception Rate Breakdown') }}
                        </h4>
                        <div class="text-right">
                            <span
                                class="text-2xl font-bold {{ $pipelineData['exception_rate']['trend'] === 'down' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $pipelineData['exception_rate']['current'] }}
                            </span>
                            <span
                                class="text-sm ml-2 {{ $pipelineData['exception_rate']['trend'] === 'down' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $pipelineData['exception_rate']['change'] }}
                            </span>
                        </div>
                    </div>
                    <div id="exception-chart"></div>
                    <div class="mt-3 text-center text-sm text-gray-600">
                        {{ $pipelineData['exception_rate']['count'] }} of
                        {{ $pipelineData['exception_rate']['total_count'] }} applications
                    </div>
                </div>

                <!-- Application Trend -->
                <div class="chart-card lg:col-span-2">
                    <h4 class="font-semibold text-gray-900 text-base mb-4">
                        {{ translate('Application Trend (Last 30 Days)') }}</h4>
                    <div id="application-trend-chart"></div>
                </div>

                <!-- SLA Compliance Trend -->
                <div class="chart-card lg:col-span-2">
                    <h4 class="font-semibold text-gray-900 text-base mb-4">
                        {{ translate('SLA Compliance Trend (Last 30 Days)') }}</h4>
                    <div id="sla-trend-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Approval Chart
            const approvalChart = new ApexCharts(document.querySelector("#approval-chart"), {
                series: [
                    @json($pipelineData['approval_rates']['approved']['percentage']),
                    @json($pipelineData['approval_rates']['declined']['percentage']),
                    @json($pipelineData['approval_rates']['pending']['percentage'])
                ],
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: ["{{ translate('Approved') }}", "{{ translate('Declined') }}",
                    "{{ translate('Pending') }}"
                ],
                colors: ['#10B981', '#EF4444', '#F59E0B'],
                legend: {
                    position: 'bottom'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: "{{ translate('Total') }}",
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + '%';
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value, {
                            seriesIndex
                        }) {
                            const counts = [
                                @json($pipelineData['approval_rates']['approved']['count']),
                                @json($pipelineData['approval_rates']['declined']['count']),
                                @json($pipelineData['approval_rates']['pending']['count'])
                            ];
                            return value + '% (' + counts[seriesIndex] + ' applications)';
                        }
                    }
                }
            });
            approvalChart.render();

            // Exception Chart
            const exceptionChart = new ApexCharts(document.querySelector("#exception-chart"), {
                series: Object.values(@json($pipelineData['exception_rate']['breakdown'])),
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: Object.keys(@json($pipelineData['exception_rate']['breakdown'])),
                colors: ['#EF4444', '#F59E0B', '#8B5CF6', '#6B7280'],
                legend: {
                    position: 'bottom'
                }
            });
            exceptionChart.render();

            // Application Trend Chart
            const applicationTrendChart = new ApexCharts(document.querySelector("#application-trend-chart"), {
                series: @json($pipelineData['application_trend']['series']),
                chart: {
                    type: 'line',
                    height: 350
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: @json($pipelineData['application_trend']['labels'])
                },
                colors: ['#3B82F6', '#10B981', '#EF4444'],
                yaxis: {
                    title: {
                        text: "{{ translate('Number of Applications') }}"
                    },
                    min: 0
                },
                legend: {
                    position: 'top'
                },
                markers: {
                    size: 4
                },
                tooltip: {
                    shared: true,
                    intersect: false
                }
            });
            applicationTrendChart.render();

            // SLA Trend Chart
            const slaTrendChart = new ApexCharts(document.querySelector("#sla-trend-chart"), {
                series: [{
                        name: "{{ translate('Underwriting SLA') }}",
                        data: @json($pipelineData['sla_metrics']['trend_data']['underwriting'])
                    },
                    {
                        name: "{{ translate('Approval SLA') }}",
                        data: @json($pipelineData['sla_metrics']['trend_data']['approval'])
                    },
                    {
                        name: "{{ translate('Disbursement SLA') }}",
                        data: @json($pipelineData['sla_metrics']['trend_data']['disbursement'])
                    }
                ],
                chart: {
                    type: 'line',
                    height: 350
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: @json($pipelineData['sla_metrics']['trend_data']['labels'])
                },
                colors: ['#3B82F6', '#10B981', '#F59E0B'],
                yaxis: {
                    title: {
                        text: "{{ translate('Compliance Rate (%)') }}"
                    },
                    min: 80,
                    max: 100
                },
                legend: {
                    position: 'top'
                },
                markers: {
                    size: 4
                }
            });
            slaTrendChart.render();
        });
    </script>
@endpush
