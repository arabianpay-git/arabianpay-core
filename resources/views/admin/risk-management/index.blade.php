@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">

        <!-- Container for page title -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ __('Risk Management') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">

                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                Risk Analytics
                            </h3>
                        </div>
                        <form action="{{ route('risk.show') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">
                                <!-- Country Name Field -->
                                <div class="w-full">
                                    <label for="from_date"
                                        class="block mb-1 text-sm font-medium text-gray-700">{{ __('From Date') }}</label>
                                    <input type="month" id="from_date" name="from_date" required class="input input-sm"
                                        value="{{ old('from_date') }}">
                                </div>

                                <div>
                                    <label for="to_date"
                                        class="block mb-1 text-sm font-medium text-gray-700">{{ __('To Date') }}</label>
                                    <input type="month" id="to_date" name="to_date" required class="input input-sm"
                                        value="{{ old('to_date') }}">
                                </div>

                                <div>
                                    <button type="submit"
                                        class="btn btn-primary btn-sm">{{ __('Show Risk Analysis') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container for the results cards -->
        @if (isset($labels))
            <div class="container-fixed mt-4">
                <div class="grid gap-5 lg:gap-7.5 grid-cols-1 md:grid-cols-2">

                    <!-- Multi Risk Profile (Radar Chart) -->
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">{{ __('Multi Risk Profile') }}</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="radarChart" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Delinquency Default Rate (Bar Chart) -->
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">{{ __('Delinquency Default Rate') }}</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="delinquencyChart" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Risk Exposure Trend (Line Chart) -->
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">{{ __('Risk Exposure Trend') }}</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="riskExposureChart" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Fraud Alert Trend (Line Chart) -->
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">{{ __('Fraud Alert Trend') }}</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="fraudAlertChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if (isset($labels))
        <script>
            // Risk Exposure Line Chart
            const riskExposureCtx = document.getElementById('riskExposureChart').getContext('2d');
            new Chart(riskExposureCtx, {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: '{{ __('Risk Exposure') }}',
                        data: @json($riskExposure),
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                }
            });

            // Delinquency Bar Chart
            const delinquencyCtx = document.getElementById('delinquencyChart').getContext('2d');
            new Chart(delinquencyCtx, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: '{{ __('Delinquency Rate (%)') }}',
                        data: @json($delinquencyRate),
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                }
            });

            // Fraud Alert Trend Line Chart
            const fraudAlertCtx = document.getElementById('fraudAlertChart').getContext('2d');
            new Chart(fraudAlertCtx, {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: '{{ __('Fraud Alerts') }}',
                        data: @json($fraudAlerts),
                        borderColor: 'rgba(255, 206, 86, 1)',
                        backgroundColor: 'rgba(255, 206, 86, 0.3)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                }
            });

            // Radar Chart for Multi Risk Profile
            const radarCtx = document.getElementById('radarChart').getContext('2d');
            new Chart(radarCtx, {
                type: 'radar',
                data: {
                    labels: @json($radarLabels),
                    datasets: [{
                        label: '{{ __('Risk Scores') }}',
                        data: @json($radarData),
                        backgroundColor: 'rgba(153, 102, 255, 0.3)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        pointBackgroundColor: 'rgba(153, 102, 255, 1)',
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        </script>
    @endif
@endpush
