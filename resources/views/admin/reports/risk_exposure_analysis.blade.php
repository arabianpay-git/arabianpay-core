@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Filter Card -->
        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Risk Exposure Filter',
        ])

        <div class="container-fixed">
            <div class="grid grid-cols-2 gap-4 gap-y-3 items-stretch mb-5">
                <!-- Total Exposure % -->
                <div class="card">
                    <div class="card-body dash-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold">Exposure</h4>
                                <div class="text-2xl font-bold">
                                    {{ number_format($reports['total_exposure_percentage'], 2) }}%
                                </div>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Score -->
                <div class="card">
                    <div class="card-body dash-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold">Avg. Score</h4>
                                <div class="text-2xl font-bold">
                                    {{ number_format($reports['average_score'], 2) }}
                                </div>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delinquency % -->
                <div class="card">
                    <div class="card-body dash-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold">Delinquency %</h4>
                                <div class="text-2xl font-bold">
                                    {{ number_format($reports['delinquency_percentage'], 2) }}%
                                </div>
                            </div>
                            <div class="bg-red-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- High Risk Count -->
                <div class="card">
                    <div class="card-body dash-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold">High Risk Count</h4>
                                <div class="text-2xl font-bold">
                                    {{ $reports['high_risk_count'] }}
                                </div>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-1 gap-5 lg:gap-7.5 items-stretch mt-6">
                <!-- Exposure Chart Card -->
                <div class="lg:col-span-2">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('Risk Exposure by Segment') }}</h3>
                        </div>
                        <div class="card-body flex flex-col justify-end items-stretch grow px-3 py-1">
                            <div id="risk_exposure_chart" class="h-[300px] w-full bg-white rounded shadow p-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Init datepickers if filter has date inputs
            flatpickr('#fromDate', {
                dateFormat: 'Y-m-d'
            });
            flatpickr('#toDate', {
                dateFormat: 'Y-m-d'
            });

            // Chart options
            var options = {
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                },
                series: [{
                    name: 'Exposure %',
                    data: @json($reports['exposure_percentages'])
                }],
                xaxis: {
                    categories: @json($reports['segments'])
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                    }
                },
                colors: ['#f97316'], // orange color for exposure %
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + '%';
                    }
                },
                yaxis: {
                    max: 100,
                    labels: {
                        formatter: function(val) {
                            return val + '%';
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + '%';
                        }
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#risk_exposure_chart"), options);
            chart.render();
        });
    </script>
@endpush
