@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">

        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Financial Summary Report') }}
                    </h1>
                    <p class="text-gray-600">
                        {{ translate('Monthly revenue, payouts, fees, cost of credit, and profitability snapshot') }}
                    </p>
                </div>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Finance Data Filter',
        ])
        <!-- Summary Cards -->
        <div class="container-fixed grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="card p-4 text-center bg-white shadow rounded">
                <h3 class="text-lg font-semibold text-gray-700">{{ translate('Total Revenue') }}</h3>
                <p class="text-2xl font-bold text-green-600">{{ number_format($summary['total_revenue'] ?? 0, 2) }} SAR</p>
            </div>
            <div class="card p-4 text-center bg-white shadow rounded">
                <h3 class="text-lg font-semibold text-gray-700">{{ translate('Supplier Payouts') }}</h3>
                <p class="text-2xl font-bold text-red-600">{{ number_format($summary['supplier_payouts'] ?? 0, 2) }} SAR
                </p>
            </div>
            <div class="card p-4 text-center bg-white shadow rounded">
                <h3 class="text-lg font-semibold text-gray-700">{{ translate('Operational Costs') }}</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ number_format($summary['operational_costs'] ?? 0, 2) }}
                    SAR</p>
            </div>
            <div class="card p-4 text-center bg-white shadow rounded">
                <h3 class="text-lg font-semibold text-gray-700">{{ translate('Net Profit') }}</h3>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['net_profit'] ?? 0, 2) }} SAR</p>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid lg:grid-cols-1 gap-5 lg:gap-7.5 items-stretch mb-5">
                <!-- Highlights Card -->
                <div class="lg:col-span-1">
                    <div class="card p-5 bg-white shadow rounded">
                        <h3 class="text-lg font-semibold mb-4">{{ translate('Monthly Revenue') }}</h3>
                        <canvas id="monthlyRevenueChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="grid lg:grid-cols-5 gap-5 lg:gap-7.5 items-stretch">
                <div class="lg:col-span-2">
                    <!-- Profitability Breakdown Chart -->
                    <div class="card p-5 bg-white shadow rounded">
                        <h3 class="text-lg font-semibold mb-4">{{ translate('Profitability Breakdown') }}</h3>
                        <canvas id="profitabilityChart" height="300"></canvas>
                    </div>
                </div>
            </div>

        </div>


    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Monthly Revenue Chart
            const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
            const monthlyRevenueChart = new Chart(monthlyRevenueCtx, {
                type: 'line',
                data: {
                    labels: @json($charts['months'] ?? []),
                    datasets: [{
                        label: '{{ translate('Revenue') }} (SAR)',
                        data: @json($charts['monthly_revenue'] ?? []),
                        borderColor: 'rgba(34, 197, 94, 1)', // green
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Profitability Breakdown Chart (Pie)
            const profitabilityCtx = document.getElementById('profitabilityChart').getContext('2d');
            const profitabilityChart = new Chart(profitabilityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Supplier Payouts', 'Operational Costs', 'Net Profit'],
                    datasets: [{
                        data: [
                            {{ $summary['supplier_payouts'] ?? 0 }},
                            {{ $summary['operational_costs'] ?? 0 }},
                            {{ $summary['net_profit'] ?? 0 }}
                        ],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.7)', // red
                            'rgba(234, 179, 8, 0.7)', // yellow
                            'rgba(59, 130, 246, 0.7)' // blue
                        ],
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
@endpush
