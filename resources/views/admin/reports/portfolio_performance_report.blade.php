@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Filter Card -->
        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Filter Performance Performance',
        ])

        <div class="container-fixed">
            <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                <!-- Highlights Card -->
                <div class="lg:col-span-1">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('Highlights') }}</h3>
                        </div>
                        <div class="card-body flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                            <!-- Credit Summary -->
                            <div class="flex flex-col gap-0.5">
                                <span class="text-sm font-normal text-gray-700">{{ __('Total Credit Issued') }}</span>
                                <div class="flex items-center gap-2.5">
                                    <span class="text-3xl font-semibold text-primary">
                                        {{ number_format($reports['total_credit_issued'], 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 mb-1.5">
                                <div class="bg-gray-200 h-2 w-full rounded-sm overflow-hidden">
                                    <div class="bg-success h-full" style="width: {{ $reports['utilized_percent'] }}%;">
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center flex-wrap gap-4 mb-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="badge badge-dot size-2 badge-success"></span>
                                    <span class="text-xs text-gray-800">
                                        {{ __('Used') }}
                                        <strong>{{ number_format($reports['utilized_amount'], 2) }}</strong> <span
                                            class="icon-saudi_riyal"></span> [{{ $reports['utilized_percent'] }}%]
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="badge badge-dot size-2 badge-gray-100"></span>
                                    <span class="text-xs text-gray-800">
                                        {{ __('Unused') }}
                                        <strong>{{ number_format($reports['unused_amount'], 2) }}</strong> <span
                                            class="icon-saudi_riyal"></span> [{{ $reports['unused_percent'] }}%]
                                    </span>
                                </div>
                            </div>
                            <div class="border-b border-gray-300"></div>

                            <!-- Stats -->
                            <div class="grid gap-3">
                                @foreach ([['title' => 'Repayment rate', 'value' => $reports['current_repayment_rate'], 'change' => $reports['previous_repayment_rate'], 'icon' => 'ki-shop'], ['title' => 'Average DPD', 'value' => $reports['current_average_dpd'], 'change' => $reports['avg_dpd_change'], 'icon' => 'ki-disconnect'], ['title' => 'NPL Ratio', 'value' => $reports['current_npl_ratio'], 'change' => $reports['previous_npl_ratio'], 'icon' => 'ki-archive']] as $stat)
                                    <div class="grid grid-cols-2 items-center gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <i class="ki-filled {{ $stat['icon'] }} text-base text-gray-500"></i>
                                            <span class="text-xs text-gray-900">{{ $stat['title'] }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 text-sm font-medium text-gray-800 gap-2">
                                            <div class="text-right">{{ $stat['value'] }}%</div>
                                            <div class="flex items-center justify-end gap-1">
                                                {{ abs($stat['change']) }}%
                                                <i
                                                    class="ki-filled ki-arrow-{{ $stat['change'] >= 0 ? 'up text-success' : 'down text-danger' }}"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Chart Card -->
                <div class="lg:col-span-2">
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('Credit Issued') }}</h3>
                        </div>
                        <div class="card-body flex flex-col justify-end items-stretch grow px-3 py-1">
                            <div id="creditlimit_chart" class="h-[300px] w-full bg-white rounded shadow p-4"></div>
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
            // Init datepickers
            flatpickr('#fromDate', {
                dateFormat: 'Y-m-d'
            });
            flatpickr('#toDate', {
                dateFormat: 'Y-m-d'
            });

            // Chart options
            var options = {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Credit Limit Issued',
                    data: @json($reports['monthly_credits'])
                }],
                xaxis: {
                    categories: @json($reports['monthly_labels'])
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    colors: ['#3b82f6']
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        opacityFrom: 0.3,
                        opacityTo: 0
                    },
                    colors: ['#3b82f6']
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return "<span class='icon-saudi_riyal'></span>" + val + "K";
                        }
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#creditlimit_chart"), options);
            chart.render();
        });
    </script>
@endpush
