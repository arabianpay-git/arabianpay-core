@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>
        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            <!-- Container -->
            @include('admin.accounts.includes.customer')
            <!-- End of Container -->
        </div>
        <!-- Container -->
        @include('admin.accounts.includes.customer-header')

        <div class="container-fixed">
            <!-- Header Section -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Credit Risk Assessment</h1>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Credit Score</p>
                                    <p class="text-2xl font-bold text-primary-600">
                                        {{ $creditScore['creditScore']['compositeScore'] }}/100</p>
                                </div>
                                <div class="bg-primary-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Risk Level</p>
                                    <p class="text-2xl font-bold text-yellow-600">{{ $riskLevel }}</p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Business Age</p>
                                    <p class="text-2xl font-bold text-green-600">{{ $businessAge }}</p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Risk Score Gauge -->
                <div class="card p-4">
                    <h3 class="text-lg font-semibold mb-4">Composite Risk Score</h3>
                    <div id="scoreGauge"></div>
                </div>

                <!-- Score Breakdown Chart -->
                <div class="card p-4">
                    <h3 class="text-lg font-semibold mb-4">Score Components</h3>
                    <div id="scoreBreakdown"></div>
                </div>

                <!-- Payment History Timeline -->
                <div class="card p-4 lg:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">Payment History</h3>
                    <div id="paymentTimeline"></div>
                </div>

                <!-- Risk Factors Table -->
                <div class="card p-4">
                    <h3 class="text-lg font-semibold mb-4">Flagged Risk Factors</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-2">Industry Volatility</td>
                                    <td class="py-2 text-red-600">High Risk</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2">Debt-to-Revenue Ratio</td>
                                    <td class="py-2 text-yellow-600">1.2:1</td>
                                </tr>
                                <tr>
                                    <td class="py-2">Recent Disputes</td>
                                    <td class="py-2 text-red-600">3 Cases</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Compliance Status -->
                <div class="card p-4">
                    <h3 class="text-lg font-semibold mb-4">Compliance Status</h3>
                    <div class="space-y-2">
                        @foreach ($complianceStatus as $status)
                            <div class="flex justify-between items-center mb-2">
                                <span>{{ $status['name'] }}</span>
                                <span class="badge {{ $status['badge'] }}">{{ $status['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </main>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Score Gauge
        const gaugeOptions = {
            series: [{{ $creditScore['creditScore']['compositeScore'] }}],
            chart: {
                height: 350,
                type: 'radialBar'
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: {
                        size: '70%'
                    },
                    dataLabels: {
                        name: {
                            fontSize: '16px'
                        },
                        value: {
                            fontSize: '24px',
                            formatter: val => val + "/100"
                        }
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    shadeIntensity: 0.15,
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 50, 65, 91]
                }
            },
            stroke: {
                dashArray: 4
            },
            labels: ['Composite Score'],
        };
        new ApexCharts(document.querySelector("#scoreGauge"), gaugeOptions).render();

        // Score Breakdown
        const breakdownOptions = {
            series: [{
                data: {!! json_encode(array_values($scoreComponents)) !!}
            }],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: true, // different color per bar
                    dataLabels: {
                        position: 'center'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: val => val + "%",
                style: {
                    fontSize: '12px',
                    colors: ['#fff']
                },
                textAnchor: 'middle'
            },
            xaxis: {
                categories: {!! json_encode(array_keys($scoreComponents)) !!}
            },
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#6366F1', '#EC4899', '#14B8A6', '#F97316'],
            legend: {
                show: false
            },
            markers: {
                size: 0
            }
        };

        new ApexCharts(document.querySelector("#scoreBreakdown"), breakdownOptions).render();

        // Payment Timeline
        const timelineOptions = {
            series: [{
                name: "On-time Payments",
                data: {!! json_encode($paymentTimeline['data']) !!}
            }],
            chart: {
                height: 350,
                type: 'line'
            },
            xaxis: {
                categories: {!! json_encode($paymentTimeline['categories']) !!}
            },
            colors: ['#10B981']
        };
        new ApexCharts(document.querySelector("#paymentTimeline"), timelineOptions).render();
    </script>
@endpush
