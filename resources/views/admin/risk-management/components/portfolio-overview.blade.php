@push('styles')
    <style>
        .apexcharts-legend {
            display: flex;
            flex-direction: row !important;
        }

        .icon-saudi_riyal {
            font-size: 28px !important;
        }
    </style>
@endpush
<div class="container-fixed mt-4">
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <h3 class="card-title font-semibold text-base text-gray-900">
                {{ translate('Portfolio Analysis') }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ translate('Comprehensive portfolio risk metrics and exposure analysis') }}
            </p>
        </div>

        <div class="card-body p-6 space-y-6">
            <!-- Key Metrics Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Exposure -->
                <div class="card shadow-sm border border-gray-200 rounded-xl hover:shadow-md transition">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ translate('Total Exposure') }}</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">
                                    {!! $portfolioData['exposure']['total'] !!}
                                </h3>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex items-center text-sm font-medium 
                                    {{ $portfolioData['exposure']['trend'] === 'up' ? 'text-danger' : 'text-success' }}">
                                    {{ $portfolioData['exposure']['change'] }}
                                    <i
                                        class="ki-filled ki-{{ $portfolioData['exposure']['trend'] === 'up' ? 'arrow-up' : 'arrow-down' }} ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilization Rate -->
                <div class="card shadow-sm border border-gray-200 rounded-xl hover:shadow-md transition">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ translate('Utilization Rate') }}</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">
                                    {{ $portfolioData['utilization']['rate'] }}
                                </h3>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex items-center text-sm font-medium 
                                    {{ $portfolioData['utilization']['trend'] === 'up' ? 'text-danger' : 'text-success' }}">
                                    {{ $portfolioData['utilization']['change'] }}
                                    <i
                                        class="ki-filled ki-{{ $portfolioData['utilization']['trend'] === 'up' ? 'arrow-up' : 'arrow-down' }} ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NPL Ratio -->
                <div class="card shadow-sm border border-gray-200 rounded-xl hover:shadow-md transition">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ translate('NPL Ratio') }}</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">
                                    {{ $portfolioData['npl_ratio']['current'] }}
                                </h3>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex items-center text-sm font-medium 
                                    {{ $portfolioData['npl_ratio']['trend'] === 'up' ? 'text-danger' : 'text-success' }}">
                                    {{ $portfolioData['npl_ratio']['change'] }}
                                    <i
                                        class="ki-filled ki-{{ $portfolioData['npl_ratio']['trend'] === 'up' ? 'arrow-up' : 'arrow-down' }} ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charge-off Rate -->
                <div class="card shadow-sm border border-gray-200 rounded-xl hover:shadow-md transition">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ translate('Charge-off Rate') }}</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1">
                                    {{ $portfolioData['cor']['current'] }}
                                </h3>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex items-center text-sm font-medium 
                                    {{ $portfolioData['cor']['status'] === 'above_target' ? 'text-danger' : 'text-success' }}">
                                    {{ translate('Target') }}: {{ $portfolioData['cor']['target'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                <!-- Exposure Breakdown -->
                <div class="card border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="card-header border-b border-gray-100 px-4 py-3">
                        <h4 class="text-md font-semibold text-gray-900">{{ translate('Exposure Breakdown') }}</h4>
                    </div>
                    <div class="card-body p-4">
                        <div id="exposure-chart"></div>
                    </div>
                </div>

                <!-- DPD Buckets -->
                <div class="card border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="card-header border-b border-gray-100 px-4 py-3">
                        <h4 class="text-md font-semibold text-gray-900">{{ translate('Days Past Due (DPD) Buckets') }}
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div id="dpd-chart"></div>
                    </div>
                </div>

                <!-- Vintage Curves -->
                <div class="card border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition lg:col-span-2">
                    <div class="card-header border-b border-gray-100 px-4 py-3">
                        <h4 class="text-md font-semibold text-gray-900">
                            {{ translate('Vintage Curves - Delinquency Roll Rates') }}</h4>
                    </div>
                    <div class="card-body p-4">
                        <div id="vintage-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Convert Exposure Breakdown to numbers
            const exposureData = Object.values(@json($portfolioData['exposure']['breakdown'])).map(val => Number(val));

            // Exposure Breakdown Chart
            const exposureChart = new ApexCharts(document.querySelector("#exposure-chart"), {
                series: exposureData,
                chart: {
                    type: 'donut',
                    height: 300
                },
                labels: Object.keys(@json($portfolioData['exposure']['breakdown'])),
                colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
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
                                    label: '{{ translate('Total Exposure') }}',
                                    formatter: function() {
                                        const total = exposureData.reduce((a, b) => a + b, 0);
                                        return total.toLocaleString(undefined, {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        }
                    }
                }
            });
            exposureChart.render();

            // DPD Buckets Chart
            const dpdChart = new ApexCharts(document.querySelector("#dpd-chart"), {
                series: [{
                    data: @json($portfolioData['dpd_buckets']['data'])
                }],
                chart: {
                    type: 'bar',
                    height: 300
                },
                plotOptions: {
                    bar: {
                        distributed: true,
                        borderRadius: 4
                    }
                },
                colors: @json($portfolioData['dpd_buckets']['colors']),
                xaxis: {
                    categories: @json($portfolioData['dpd_buckets']['labels'])
                },
                tooltip: {
                    y: {
                        formatter: val => val + '%'
                    }
                }
            });
            dpdChart.render();

            // Vintage Curves Chart
            const vintageChart = new ApexCharts(document.querySelector("#vintage-chart"), {
                series: @json($portfolioData['vintage_curves']['series']),
                chart: {
                    type: 'line',
                    height: 350
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: @json($portfolioData['vintage_curves']['labels'])
                },
                markers: {
                    size: 5
                },
                colors: ['#3B82F6', '#10B981', '#EF4444'],
                yaxis: {
                    title: {
                        text: '{{ translate('Delinquency Rate (%)') }}'
                    }
                },
                legend: {
                    position: 'top'
                }
            });
            vintageChart.render();
        });
    </script>
@endpush
