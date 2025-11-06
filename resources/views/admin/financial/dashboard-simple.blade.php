@extends('layouts.base')

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Monthly Cash Flow Chart
    const cashFlowCtx = document.getElementById('monthlyCashFlowChart');
    if (cashFlowCtx) {
        const monthlyData = @json($chartData['monthly_data']);
        new Chart(cashFlowCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [
                    {
                        label: '{{ translate("Revenue") }}',
                        data: monthlyData.map(d => d.revenue),
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        fill: true
                    },
                    {
                        label: '{{ translate("Expenses") }}',
                        data: monthlyData.map(d => d.expenses),
                        borderColor: '#e74a3b',
                        backgroundColor: 'rgba(231, 74, 59, 0.1)',
                        fill: true
                    },
                    {
                        label: '{{ translate("Net") }}',
                        data: monthlyData.map(d => d.net),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' SR';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(2) + ' SR';
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize Account Types Chart
    const typeCtx = document.getElementById('accountTypesChart');
    if (typeCtx) {
        const accountTypes = @json($chartData['account_types']);
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: accountTypes.map(t => t.account_type1),
                datasets: [{
                    data: accountTypes.map(t => t.count),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
@endpush

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>

    <!-- Container -->
    <div class="container-fixed">
        <!-- Header -->
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Financial Dashboard') }}
                </h1>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>{{ translate('Overview of your financial position and recent activities') }}</span>
                    <span class="badge badge-sm badge-outline">{{ date('F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-7.5 mb-5">
            <!-- Net Worth Card -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">
                                {{ translate('Net Worth') }}
                            </div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ number_format($kpis['total_assets'] - $kpis['total_liabilities'], 2) }} SR
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ translate('Assets') }}: {{ number_format($kpis['total_assets'], 2) }} SR<br>
                                {{ translate('Liabilities') }}: {{ number_format($kpis['total_liabilities'], 2) }} SR
                            </div>
                        </div>
                        <div>
                            <i class="ki-filled ki-chart-line-star text-3xl text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Net Income -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">
                                {{ translate('Monthly Net Income') }}
                            </div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ number_format($kpis['net_income'], 2) }} SR
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ translate('Revenue') }}: {{ number_format($kpis['monthly_revenue'], 2) }} SR<br>
                                {{ translate('Expenses') }}: {{ number_format($kpis['monthly_expenses'], 2) }} SR
                            </div>
                        </div>
                        <div>
                            <i class="ki-filled ki-chart-line text-3xl text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts Receivable -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">
                                {{ translate('Accounts Receivable') }}
                            </div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ number_format($kpis['accounts_receivable'], 2) }} SR
                            </div>
                        </div>
                        <div>
                            <i class="ki-filled ki-dollar text-3xl text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts Payable -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">
                                {{ translate('Accounts Payable') }}
                            </div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ number_format($kpis['accounts_payable'], 2) }} SR
                            </div>
                        </div>
                        <div>
                            <i class="ki-filled ki-dollar text-3xl text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5 mb-5">
            <!-- Monthly Cash Flow Chart -->
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Monthly Cash Flow Trend') }}</h3>
                    </div>
                    <div class="card-body">
                        <div style="height: 320px;">
                            <canvas id="monthlyCashFlowChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Types Distribution -->
            <div class="lg:col-span-1">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Account Distribution') }}</h3>
                    </div>
                    <div class="card-body">
                        <div style="height: 245px;">
                            <canvas id="accountTypesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trial Balance -->
        <!-- Keep existing trial balance section -->

        <!-- Recent Activities -->
        <!-- Keep existing activities section -->
    </div>
</main>
@endsection