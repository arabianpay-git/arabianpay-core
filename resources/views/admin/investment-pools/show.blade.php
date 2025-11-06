@extends('layouts.base')

@push('styles')
<style>
    /* Pool Details Page Styles */
    .pool-status-badge {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
    }

    .metric-card {
        transition: all 0.3s ease;
        border: 1px solid #e4e6ea;
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .payment-timeline {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }

    .timeline-bar {
        height: 24px;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        background: #e4e6ea;
        margin: 0.5rem 0;
    }

    .timeline-segment {
        height: 100%;
        float: left;
        position: relative;
    }

    .timeline-segment.paid {
        background: linear-gradient(90deg, #50cd89, #3ac47d);
    }

    .timeline-segment.pending {
        background: linear-gradient(90deg, #ffc700, #f1bc00);
    }

    .timeline-segment.late {
        background: linear-gradient(90deg, #f1416c, #e02454);
    }

    .timeline-segment.due {
        background: linear-gradient(90deg, #7239ea, #5014d0);
    }

    .installment-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-paid {
        background: #d1fae5;
        color: #065f46;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-late {
        background: #fecaca;
        color: #991b1b;
    }

    .status-due {
        background: #e0e7ff;
        color: #3730a3;
    }

    .pool-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .risk-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .risk-low {
        background: #d1fae5;
        color: #065f46;
    }

    .risk-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .risk-high {
        background: #fecaca;
        color: #991b1b;
    }
</style>
@endpush

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <!-- Breadcrumbs -->
        <div class="flex flex-wrap items-center gap-1 pb-5">
            <a href="{{ route('financial.dashboard') }}" class="text-gray-600 hover:text-primary text-sm">
                <i class="ki-filled ki-home-2"></i>
                Financial Dashboard
            </a>
            <i class="ki-filled ki-right text-gray-400 text-xs"></i>
            <a href="{{ route('investment-pools.calendar') }}" class="text-gray-600 hover:text-primary text-sm">
                Investment Pools
            </a>
            <i class="ki-filled ki-right text-gray-400 text-xs"></i>
            <span class="text-gray-900 text-sm font-medium">{{ $pool->name }}</span>
        </div>

        <!-- Pool Header -->
        <div class="pool-header">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-bold text-white">{{ $pool->name }}</h1>
                        <span class="pool-status-badge {{ $pool->status === 'active' ? 'bg-success text-white' : 'bg-gray-400 text-white' }}">
                            {{ ucfirst($pool->status) }}
                        </span>
                        @if($pool->collection_rate >= 90)
                        <span class="risk-indicator risk-low">
                            <i class="ki-filled ki-shield-tick"></i>
                            Low Risk
                        </span>
                        @elseif($pool->collection_rate >= 70)
                        <span class="risk-indicator risk-medium">
                            <i class="ki-filled ki-information"></i>
                            Medium Risk
                        </span>
                        @else
                        <span class="risk-indicator risk-high">
                            <i class="ki-filled ki-cross-circle"></i>
                            High Risk
                        </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-6 text-white/80">
                        <div class="flex items-center gap-2">
                            <i class="ki-filled ki-calendar text-lg"></i>
                            <span>{{ $pool->start_date->format('M d, Y') }} - {{ $pool->end_date->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ki-filled ki-time text-lg"></i>
                            <span>{{ abs(round($pool->days_remaining)) }} days {{ round($pool->days_remaining) < 0 ? 'overdue' : 'remaining' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ki-filled ki-code text-lg"></i>
                            <span>{{ $pool->uuid }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-light btn-sm">
                        <i class="ki-filled ki-file-down"></i>
                        Export
                    </button>
                    <button class="btn btn-light btn-sm">
                        <i class="ki-filled ki-setting-2"></i>
                        Settings
                    </button>
                    <a href="{{ route('financial.dashboard') }}" class="btn btn-primary btn-sm">
                        <i class="ki-filled ki-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Disbursed -->
            <div class="card metric-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">Total Disbursed</div>
                            <div class="text-2xl font-bold text-gray-900">
                                @if($pool->total_disbursed > 1000000)
                                <span class="icon-saudi_riyal"></span>{{ round($pool->total_disbursed / 1000000, 3) }}M
                                @elseif($pool->total_disbursed > 1000)
                                <span class="icon-saudi_riyal"></span>{{ round($pool->total_disbursed/1000, 2) }}K
                                @else($pool->total_disbursed <10000)
                                    <span class="icon-saudi_riyal"></span>{{ number_format($pool->total_disbursed, 2) }}
                                    @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $pool->total_checkouts }} checkouts
                            </div>
                        </div>
                        <div class="metric-icon bg-primary-light">
                            <i class="ki-filled ki-arrow-up text-primary text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collection Rate -->
            <div class="card metric-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">Collection Rate</div>
                            <div class="text-2xl font-bold {{ $pool->collection_rate >= 90 ? 'text-success' : ($pool->collection_rate >= 70 ? 'text-warning' : 'text-danger') }}">
                                {{ number_format($pool->collection_rate, 1) }}%
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                @if($pool->total_collected > 1000000)
                                <span class="icon-saudi_riyal"></span>{{ round($pool->total_collected/1000000, 3) }}M
                                @elseif($pool->total_collected > 1000)
                                <span class="icon-saudi_riyal"></span>{{ round($pool->total_collected/1000, 2) }}K
                                @else
                                <span class="icon-saudi_riyal"></span>{{ number_format($pool->total_collected, 0) }}
                                @endif
                                collected
                            </div>
                        </div>
                        <div class="metric-icon {{ $pool->collection_rate >= 90 ? 'bg-success-light' : ($pool->collection_rate >= 70 ? 'bg-warning-light' : 'bg-danger-light') }}">
                            <i class="ki-filled ki-percentage {{ $pool->collection_rate >= 90 ? 'text-success' : ($pool->collection_rate >= 70 ? 'text-warning' : 'text-danger') }} text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expected Collections -->
            <div class="card metric-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">Expected Collections</div>
                            <div class="text-2xl font-bold text-gray-900">
                                @if($pool->expected_collections > 1000000)
                                <span class="icon-saudi_riyal"></span> {{ round($pool->expected_collections/1000000, 3) }}M
                                @elseif($pool->expected_collections >1000)
                                <span class="icon-saudi_riyal"></span>{{ round($pool->expected_collections/1000, 2) }}K
                                @else
                                <span class="icon-saudi_riyal"></span>{{ number_format($pool->expected_collections ?? 0, 2) }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ number_format(($pool->expected_collections ?? 0) - $pool->total_collected, 0) }} SR remaining
                            </div>
                        </div>
                        <div class="metric-icon bg-info-light">
                            <i class="ki-filled ki-chart-line text-info text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Installments -->
            <div class="card metric-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 mb-1">Active Installments</div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ $installmentStats['total'] ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $installmentStats['overdue'] ?? 0 }} overdue
                            </div>
                        </div>
                        <div class="metric-icon bg-warning-light">
                            <i class="ki-filled ki-calendar-tick text-warning text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Associated Checkouts -->
        <div class="card mb-8">
            <div class="card-header">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="card-title">
                        <i class="ki-filled ki-shop text-primary mr-2"></i>
                        Associated Checkouts ({{ $pool->total_checkouts }})
                    </h3>
                    <div class="flex items-center gap-2">
                        <button class="btn btn-sm btn-light">
                            <i class="ki-filled ki-filter"></i>
                            Filter
                        </button>
                        <button class="btn btn-sm btn-light">
                            <i class="ki-filled ki-file-down"></i>
                            Export
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-rounded table-striped border gs-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                                <th class="min-w-100px">Checkout ID</th>
                                <th class="min-w-150px">Customer</th>
                                <th class="min-w-120px">Amount</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-120px">Payment Rate</th>
                                <th class="min-w-120px">Next Payment</th>
                                <th class="min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($checkouts as $checkout)
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-900">#{{ $checkout->id }}</div>
                                    <div class="text-xs text-gray-500">{{ $checkout->created_at->format('M d, Y') }}</div>
                                </td>
                                <td>
                                    <div class="font-medium text-gray-900">{{ $checkout->user->first_name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $checkout->user->email ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-gray-900">{{ number_format($checkout->total_amount, 0) }} SR</div>
                                </td>
                                <td>
                                    <span class="badge badge-sm {{ $checkout->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ ucfirst($checkout->status) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                    $paymentRate = $checkout->payment_rate ?? 0;
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="progress h-2 w-16 bg-gray-200 rounded-full">
                                            <div class="progress-bar {{ $paymentRate >= 90 ? 'bg-success' : ($paymentRate >= 70 ? 'bg-warning' : 'bg-danger') }} h-2 rounded-full"
                                                style="width: {{ $paymentRate }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">{{ number_format($paymentRate, 0) }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @if($checkout->next_payment_date)
                                    <div class="text-sm text-gray-900">{{ $checkout->next_payment_date->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($checkout->next_payment_amount, 0) }} SR</div>
                                    @else
                                    <span class="text-gray-400">No upcoming</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <button class="btn btn-sm btn-light" onclick="window.location.href='{{ route('checkouts.show', $checkout->id) }}'">
                                            <i class="ki-filled ki-eye"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-8">
                                    <i class="ki-filled ki-file-sheet text-4xl text-gray-300 mb-4"></i>
                                    <h4 class="text-gray-600 mb-2">No Checkouts Found</h4>
                                    <p class="text-gray-500">This pool doesn't have any associated checkouts yet.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(isset($checkouts) && $checkouts->hasPages())
                <div class="card-footer">
                    {{ $checkouts->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- participants on this pool -->
        <div class="kt-container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-mono">
                        Participants
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                        Merchants and customers who participants on this pool
                    </div>
                </div>

            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ki-filled ki-user text-primary mr-2"></i>
                        Suppliers ({{ $suppliers->count() }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-rounded table-striped border gs-7">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                                    <th class="min-w-100px">Name</th>
                                    <th class="min-w-150px">Orders Counts</th>
                                    <th class="min-w-120px">Orders Amount</th>
                                    <th class="min-w-100px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suppliers as $supplier)
                                    <tr>
                                        <td>{{ $supplier->name }}</td>
                                        <td>{{ $supplier->orders_count }}</td>
                                        <td>{{ $supplier->orders_amount }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-light" onclick="window.location.href='{{ route('supplierProfile', $supplier->user_id) }}'">
                                                <i class="ki-filled ki-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ki-filled ki-user text-primary mr-2"></i>
                        Customers ({{ $customers->count() }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-rounded table-striped border gs-7">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                                    <th class="min-w-100px">Name</th>
                                    <th class="min-w-150px">Checkouts Counts</th>
                                    <th class="min-w-120px">Checkouts Amount</th>
                                    <th class="min-w-120px">Collections Percent</th>
                                    <th class="min-w-100px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $customer)
                                    <tr>
                                        <td>{{ $customer->user->first_name }}</td>
                                        <td>{{ $customer->orders_count }}</td>
                                        <td>{{ $customer->orders_amount }}</td>
                                        <td>{{ number_format($customer->collection_percent, 2) }}%</td>
                                        <td>
                                            <button class="btn btn-sm btn-light" onclick="window.location.href='{{ route('customerProfile', $customer->user_id) }}'">
                                                <i class="ki-filled ki-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    





    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('js/scripts.bundle.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Collection Trend Chart
        const collectionTrendOptions = {
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Daily Collections',
                data: @json($collectionTrend ?? [])
            }],
            xaxis: {
                categories: @json($collectionDates ?? []),
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            colors: ['#50cd89'],
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value.toFixed(0) + ' SR';
                    }
                }
            }
        };

        new ApexCharts(document.querySelector('#collectionTrendChart'), collectionTrendOptions).render();

        // Payment Status Distribution Chart
        const paymentStatusOptions = {
            chart: {
                type: 'donut',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: @json(array_values($paymentStats ?? [])),
            labels: @json(array_keys($paymentStats ?? [])),
            colors: ['#50cd89', '#ffc700', '#7239ea', '#f1416c'],
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '14px'
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        };

        new ApexCharts(document.querySelector('#paymentStatusChart'), paymentStatusOptions).render();

        // Schedule Payments Table Functionality
        let currentFilter = 'all';
        let currentSort = 'due_date-asc';
        let searchTerm = '';

        // Quick Filter Buttons
        document.querySelectorAll('.schedule-filter').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.schedule-filter').forEach(btn => {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-light');
                });

                // Add active class to clicked button
                this.classList.remove('btn-light');
                this.classList.add('active', 'btn-primary');

                currentFilter = this.dataset.filter;
                filterAndSortTable();
            });
        });

        // Search Input
        const searchInput = document.getElementById('scheduleSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchTerm = this.value.toLowerCase();
                filterAndSortTable();
            });
        }

        // Sort Select
        const sortSelect = document.getElementById('scheduleSort');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                currentSort = this.value;
                filterAndSortTable();
            });
        }

        function filterAndSortTable() {
            const rows = Array.from(document.querySelectorAll('.schedule-payment-row'));
            const today = new Date().toISOString().split('T')[0];
            const weekStart = getWeekStart(new Date());
            const weekEnd = getWeekEnd(new Date());
            const monthStart = getMonthStart(new Date());
            const monthEnd = getMonthEnd(new Date());

            // Filter rows
            let visibleRows = rows.filter(row => {
                const status = row.dataset.status;
                const dueDate = row.dataset.dueDate;
                const checkout = row.dataset.checkout.toLowerCase();
                const customer = row.dataset.customer.toLowerCase();

                // Search filter
                if (searchTerm && !checkout.includes(searchTerm) && !customer.includes(searchTerm)) {
                    return false;
                }

                // Status filter
                switch (currentFilter) {
                    case 'all':
                        return true;
                    case 'paid':
                        return status === 'paid';
                    case 'due-today':
                        return dueDate === today && status !== 'paid';
                    case 'due-week':
                        return dueDate >= weekStart && dueDate <= weekEnd && status !== 'paid';
                    case 'due-month':
                        return dueDate >= monthStart && dueDate <= monthEnd && status !== 'paid';
                    case 'late':
                        return dueDate < today && status !== 'paid';
                    default:
                        return true;
                }
            });

            // Sort rows
            visibleRows.sort((a, b) => {
                const [field, direction] = currentSort.split('-');
                let aVal, bVal;

                switch (field) {
                    case 'due_date':
                        aVal = new Date(a.dataset.dueDate);
                        bVal = new Date(b.dataset.dueDate);
                        break;
                    case 'amount':
                        aVal = parseFloat(a.dataset.amount);
                        bVal = parseFloat(b.dataset.amount);
                        break;
                    case 'status':
                        aVal = a.dataset.status;
                        bVal = b.dataset.status;
                        break;
                    case 'checkout':
                        aVal = parseInt(a.dataset.checkout);
                        bVal = parseInt(b.dataset.checkout);
                        break;
                    default:
                        return 0;
                }

                if (direction === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });

            // Hide all rows
            rows.forEach(row => row.style.display = 'none');

            // Show filtered and sorted rows
            const tbody = document.getElementById('schedulePaymentsBody');
            visibleRows.forEach(row => {
                row.style.display = '';
                tbody.appendChild(row); // Reorder in DOM
            });

            // Update counters
            updateCounters(visibleRows);

            // Show/hide no results message
            const noResultsRow = document.getElementById('no-payments-row');
            if (visibleRows.length === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    const newRow = document.createElement('tr');
                    newRow.id = 'no-results-row';
                    newRow.innerHTML = `
                    <td colspan="8" class="text-center py-8">
                        <i class="ki-filled ki-magnifier text-4xl text-gray-300 mb-4"></i>
                        <h4 class="text-gray-600 mb-2">No Matching Payments</h4>
                        <p class="text-gray-500">Try adjusting your filters or search terms.</p>
                    </td>
                `;
                    tbody.appendChild(newRow);
                }
            } else {
                const noResultsRow = document.getElementById('no-results-row');
                if (noResultsRow) {
                    noResultsRow.remove();
                }
            }
        }

        function updateCounters(visibleRows) {
            const totalCount = visibleRows.length;
            let totalAmount = 0;
            let collectedAmount = 0;

            visibleRows.forEach(row => {
                const amount = parseFloat(row.dataset.amount);
                const status = row.dataset.status;

                totalAmount += amount;
                if (status === 'paid') {
                    collectedAmount += amount;
                }
            });

            const outstandingAmount = totalAmount - collectedAmount;

            // Update counters with null checks
            const visibleCountEl = document.getElementById('visibleCount');
            const totalCountEl = document.getElementById('totalCount');
            const totalAmountEl = document.getElementById('totalAmount');
            const collectedAmountEl = document.getElementById('collectedAmount');
            const outstandingAmountEl = document.getElementById('outstandingAmount');

            if (visibleCountEl) visibleCountEl.textContent = totalCount;
            if (totalCountEl) totalCountEl.textContent = totalCount;
            if (totalAmountEl) totalAmountEl.textContent = totalAmount.toFixed(2) + ' SR';
            if (collectedAmountEl) collectedAmountEl.textContent = collectedAmount.toFixed(2) + ' SR';
            if (outstandingAmountEl) outstandingAmountEl.textContent = outstandingAmount.toFixed(2) + ' SR';
        }

        // Helper functions for date calculations
        function getWeekStart(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            return new Date(d.setDate(diff)).toISOString().split('T')[0];
        }

        function getWeekEnd(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? 0 : 7);
            return new Date(d.setDate(diff)).toISOString().split('T')[0];
        }

        function getMonthStart(date) {
            return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
        }

        function getMonthEnd(date) {
            return new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
        }
    });

    // Action Functions
    function markAsPaid(paymentId) {
        if (confirm('Mark this payment as paid?')) {
            // Add your AJAX call here to update payment status
            console.log('Marking payment as paid:', paymentId);
            // After successful update, refresh the page or update the row
        }
    }

    function viewPaymentDetails(paymentId) {
        // Delegate to the working overlay modal implementation
        try {
            showPaymentDetails(paymentId);
        } catch (e) {
            console.error('Error opening payment details modal:', e);
        }
    }

    function createClaim(paymentId, claimType) {
        // Show claim creation modal
        console.log('Creating claim modal for payment:', paymentId, 'type:', claimType);

        // Create modal backdrop
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = '9999';

        modal.innerHTML = `
        <div style="background: white; border-radius: 8px; padding: 0; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">Create ${claimType.toUpperCase()} Claim</h3>
                    <button type="button" onclick="this.closest('div[style*=\"fixed\"]').remove()" 
                            style="background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">×</button>
                </div>
            </div>
            <div style="padding: 1.5rem;">
                <form id="claimForm">
                    <input type="hidden" name="schedule_payment_id" value="${paymentId}">
                    <input type="hidden" name="claim_type" value="${claimType}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <!-- Priority -->
                        <div>
                            <label for="claim-priority" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Priority</label>
                            <select name="priority" id="claim-priority" required style="
                                width: 100%;
                                padding: 8px 12px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                background-color: white;
                                font-size: 14px;
                            ">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <!-- Contact Method -->
                        <div>
                            <label for="contact_method" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Contact Method</label>
                            <input type="text" name="contact_method" id="contact_method" 
                                placeholder="Phone/Email used" 
                                style="
                                    width: 100%;
                                    padding: 8px 12px;
                                    border: 1px solid #d1d5db;
                                    border-radius: 6px;
                                    font-size: 14px;
                                ">
                        </div>
                    </div>

                    <!-- Customer Response -->
                    <div style="margin-bottom: 15px;">
                        <label for="customer_response" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Customer Response</label>
                        <select name="customer_response" id="customer_response" style="
                            width: 100%;
                            padding: 8px 12px;
                            border: 1px solid #d1d5db;
                            border-radius: 6px;
                            background-color: white;
                            font-size: 14px;
                        ">
                            <option value="">Select response...</option>
                            <option value="no_answer">No Answer</option>
                            <option value="answered">Answered</option>
                            <option value="busy">Busy</option>
                            <option value="declined">Declined</option>
                            <option value="promised">Promised to Pay</option>
                            <option value="disputed">Disputed</option>
                            <option value="paid">Already Paid</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div style="margin-bottom: 15px;">
                        <label for="claim-notes" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Notes</label>
                        <textarea name="notes" id="claim-notes" rows="3" 
                            placeholder="Conversation details, outcome, customer comments..." 
                            style="
                                width: 100%;
                                padding: 8px 12px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                font-size: 14px;
                                resize: vertical;
                            "></textarea>
                    </div>

                    <!-- Customer Reason (shown when declined/disputed) -->
                    <div id="customerReasonDiv" style="margin-bottom: 15px; display: none;">
                        <label for="customer_reason" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Customer's Reason</label>
                        <textarea name="customer_reason" id="customer_reason" rows="2" 
                            placeholder="Why customer cannot pay or is disputing..." 
                            style="
                                width: 100%;
                                padding: 8px 12px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                font-size: 14px;
                                resize: vertical;
                            "></textarea>
                    </div>

                    <!-- Promise Details (shown when promised) -->
                    <div id="promiseDiv" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label for="promised_amount" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Promised Amount</label>
                                <input type="number" name="promised_amount" id="promised_amount" 
                                    step="0.01" min="0" placeholder="0.00"
                                    style="
                                        width: 100%;
                                        padding: 8px 12px;
                                        border: 1px solid #d1d5db;
                                        border-radius: 6px;
                                        font-size: 14px;
                                    ">
                            </div>
                            <div>
                                <label for="promised_payment_date" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Promised Date</label>
                                <input type="datetime-local" name="promised_payment_date" id="promised_payment_date" 
                                    style="
                                        width: 100%;
                                        padding: 8px 12px;
                                        border: 1px solid #d1d5db;
                                        border-radius: 6px;
                                        font-size: 14px;
                                    ">
                            </div>
                        </div>
                    </div>

                    <!-- Next Follow-up -->
                    <div style="margin-bottom: 15px;">
                        <label for="claim-followup" style="display: block; margin-bottom: 5px; font-weight: 600; color: #374151;">Next Follow-up</label>
                        <input type="datetime-local" name="next_follow_up" id="claim-followup" 
                            value="${new Date(Date.now() + 2*60*60*1000).toISOString().slice(0,16)}"
                            style="
                                width: 100%;
                                padding: 8px 12px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                font-size: 14px;
                            ">
                    </div>

                    <!-- Escalation -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; font-weight: 600; color: #374151; cursor: pointer;">
                            <input type="checkbox" name="requires_escalation" id="requires_escalation" value="1" 
                                style="margin-right: 8px; width: 16px; height: 16px;">
                            Requires Escalation
                        </label>
                        <div id="escalationReasonDiv" style="margin-top: 10px; display: none;">
                            <textarea name="escalation_reason" id="escalation_reason" rows="2" 
                                placeholder="Reason for escalation..." 
                                style="
                                    width: 100%;
                                    padding: 8px 12px;
                                    border: 1px solid #d1d5db;
                                    border-radius: 6px;
                                    font-size: 14px;
                                    resize: vertical;
                                "></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="this.closest('div[style*=\"fixed\"]').remove()" 
                        style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 0.375rem; cursor: pointer;">Cancel</button>
                <button type="button" onclick="submitClaim()" 
                        style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">Create Claim</button>
            </div>
        </div>
    `;

        // Add click outside to close
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        };

        document.body.appendChild(modal);
        console.log('Modal appended to body');

        // Add event listeners for dynamic form behavior
        const customerResponseSelect = document.getElementById('customer_response');
        const customerReasonDiv = document.getElementById('customerReasonDiv');
        const promiseDiv = document.getElementById('promiseDiv');
        const escalationCheckbox = document.getElementById('requires_escalation');
        const escalationReasonDiv = document.getElementById('escalationReasonDiv');

        // Handle customer response changes
        customerResponseSelect.addEventListener('change', function() {
            const value = this.value;

            // Show/hide customer reason field
            if (value === 'declined' || value === 'disputed') {
                customerReasonDiv.style.display = 'block';
            } else {
                customerReasonDiv.style.display = 'none';
                document.getElementById('customer_reason').value = '';
            }

            // Show/hide promise fields
            if (value === 'promised') {
                promiseDiv.style.display = 'block';
            } else {
                promiseDiv.style.display = 'none';
                document.getElementById('promised_amount').value = '';
                document.getElementById('promised_payment_date').value = '';
            }
        });

        // Handle escalation checkbox
        escalationCheckbox.addEventListener('change', function() {
            if (this.checked) {
                escalationReasonDiv.style.display = 'block';
            } else {
                escalationReasonDiv.style.display = 'none';
                document.getElementById('escalation_reason').value = '';
            }
        });
    }

    function closeModal() {
        const modal = document.querySelector('div[style*="fixed"]');
        if (modal) {
            modal.remove();
        }
    }

    function showPaymentDetails(paymentId) {
        console.log('Loading payment details for:', paymentId);

        // Create modal backdrop
        const modal = document.createElement('div');
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;

        // Create modal content container
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
        background: white;
        border-radius: 12px;
        max-width: 800px;
        width: 90vw;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        position: relative;
    `;

        // Add loading state
        modalContent.innerHTML = `
        <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0;
        ">
            <h5 style="margin: 0; color: #212529; font-weight: 600;">Payment Schedule Details</h5>
            <button onclick="closeModal()" style="
                background: none;
                border: none;
                font-size: 24px;
                color: #6c757d;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            ">&times;</button>
        </div>
        <div style="padding: 40px; text-align: center;">
            <div style="display: inline-block; width: 32px; height: 32px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #6b7280;">Loading payment details...</p>
        </div>
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
    `;

        modal.appendChild(modalContent);

        // Add click outside to close
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        };

        document.body.appendChild(modal);

        // Fetch payment details (JSON) and render a clean partial to avoid layout/sidebar duplication
        const paymentJsonTemplate = "{{ route('schedulePayments.payment.json', ['schedulePayment' => '__ID__']) }}";
        const jsonUrl = paymentJsonTemplate.replace('__ID__', paymentId);

        fetch(jsonUrl)
            .then(response => {
                if (!response.ok) throw new Error('Failed to load payment details');
                return response.json();
            })
            .then(({
                success,
                data
            }) => {
                if (!success || !data) throw new Error('Invalid response');

                const {
                    schedule,
                    payment,
                    user,
                    checkout,
                    claims
                } = data;

                const currency = (v) => (v == null ? '-' : Number(v).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })) + ' SR';
                const dt = (v) => v ? new Date(v).toLocaleString() : '-';
                const d = (v) => v || '-';

                const statusBadge = (() => {
                    const map = {
                        paid: '#16a34a',
                        pending: '#f59e0b',
                        overdue: '#dc2626',
                        partial: '#06b6d4'
                    };
                    const bg = map[schedule.status] || '#6b7280';
                    return `<span style="display:inline-block;padding:4px 8px;border-radius:9999px;color:white;background:${bg};font-size:12px;text-transform:capitalize;">${schedule.status || 'unknown'}</span>`;
                })();

                const maybeLate = schedule.is_late ? `<span style="color:#dc2626;font-size:12px;margin-left:8px;">${schedule.late_days ?? 0} days late</span>` : '';

                modalContent.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:20px;background-color:#f8f9fa;border-bottom:1px solid #e9ecef;border-radius:12px 12px 0 0;">
                        <h5 style="margin:0;color:#212529;font-weight:600;">Payment Schedule Details</h5>
                        <button onclick="closeModal()" style="background:none;border:none;font-size:24px;color:#6c757d;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">&times;</button>
                    </div>
                    <div style="padding:20px;max-height:72vh;overflow:auto;">
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                                <div style="font-weight:600;color:#374151;margin-bottom:8px;">Schedule</div>
                                <div style="display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;">
                                    <div style="color:#6b7280;">ID</div><div>#${String(schedule.id).padStart(4,'0')}</div>
                                    <div style="color:#6b7280;">Installment</div><div>${d(schedule.instalment_number)}</div>
                                    <div style="color:#6b7280;">Due date</div><div>${d(schedule.due_date)}</div>
                                    <div style="color:#6b7280;">Amount</div><div>${currency(schedule.instalment_amount)}${schedule.late_fee ? ` <span style=\"color:#dc2626;font-size:12px;\">(+${currency(schedule.late_fee)} late fee)</span>` : ''}</div>
                                    <div style="color:#6b7280;">Status</div><div>${statusBadge}${maybeLate}</div>
                                </div>
                            </div>
                            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                                <div style="font-weight:600;color:#374151;margin-bottom:8px;">Payment</div>
                                <div style="display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;">
                                    <div style="color:#6b7280;">Status</div><div>${payment ? (payment.status || 'paid') : '-'}</div>
                                    <div style="color:#6b7280;">Amount</div><div>${payment ? currency(payment.amount) : '-'}</div>
                                    <div style="color:#6b7280;">Method</div><div>${payment?.method || '-'}</div>
                                    <div style="color:#6b7280;">Reference</div><div>${payment?.reference || '-'}</div>
                                    <div style="color:#6b7280;">Paid at</div><div>${payment ? dt(payment.created_at) : '-'}</div>
                                </div>
                            </div>
                            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                                <div style="font-weight:600;color:#374151;margin-bottom:8px;">Customer</div>
                                <div style="display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;">
                                    <div style="color:#6b7280;">Name</div><div>${user?.name || '-'}</div>
                                    <div style="color:#6b7280;">Email</div><div>${user?.email || '-'}</div>
                                </div>
                            </div>
                            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                                <div style="font-weight:600;color:#374151;margin-bottom:8px;">Checkout</div>
                                <div style="display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;">
                                    <div style="color:#6b7280;">ID</div><div>${checkout?.id ?? '-'}</div>
                                    <div style="color:#6b7280;">Total</div><div>${checkout ? currency(checkout.total_amount) : '-'}</div>
                                </div>
                            </div>
                            <div style="grid-column:1/-1;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                                <div style="font-weight:600;color:#374151;margin-bottom:8px;">Claims</div>
                                ${Array.isArray(claims) && claims.length ? `
                                <div style="overflow:auto;">
                                    <table style="width:100%;border-collapse:collapse;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Date</th>
                                                <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Type</th>
                                                <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Priority</th>
                                                <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Status</th>
                                                <th style="text-align:right;padding:8px;border-bottom:1px solid #e5e7eb;">Attempts</th>
                                                <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Next follow-up</th>
                                                <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${claims.map(c => `
                                            <tr>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;white-space:nowrap;">${c.created_at ? new Date(c.created_at).toLocaleString() : '-'}</td>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-transform:uppercase;">${c.claim_type || '-'}</td>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-transform:capitalize;">${c.priority || '-'}</td>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-transform:capitalize;">${c.claim_status || '-'}</td>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:right;">${c.attempt_count ?? 0}</td>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;white-space:nowrap;">${c.next_follow_up ? new Date(c.next_follow_up).toLocaleString() : '-'}</td>
                                                <td style="padding:8px;border-bottom:1px solid #f3f4f6;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${c.notes ? String(c.notes).replace(/</g,'&lt;').replace(/>/g,'&gt;') : '-'}</td>
                                            </tr>`).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                ` : `<div style="color:#6b7280;font-size:14px;">No claims found for this payment.</div>`}
                            </div>
                        </div>
                    </div>
                    <div style="padding:12px 16px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:8px;">
                        <button type="button" onclick="closeModal()" style="padding:8px 12px;border:1px solid #d1d5db;background:white;color:#374151;border-radius:6px;cursor:pointer;">Close</button>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error loading payment details:', error);
                modalContent.innerHTML = `
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-bottom: 1px solid #e9ecef;
                    border-radius: 12px 12px 0 0;
                ">
                    <h5 style="margin: 0; color: #212529; font-weight: 600;">Payment Schedule Details</h5>
                    <button onclick="closeModal()" style="
                        background: none;
                        border: none;
                        font-size: 24px;
                        color: #6c757d;
                        cursor: pointer;
                        padding: 0;
                        width: 30px;
                        height: 30px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">&times;</button>
                </div>
                <div style="padding: 40px; text-align: center; color: #dc2626;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p style="margin: 0;">Failed to load payment details. Please try again.</p>
                </div>
            `;
            });
    }

    function submitClaim() {
        console.log('Submitting claim...');
        const form = document.getElementById('claimForm');
        if (!form) {
            alert('Form not found');
            return;
        }

        const formData = new FormData(form);

        // Set timestamps based on customer response
        const customerResponse = document.getElementById('customer_response').value;
        const now = new Date().toISOString();

        // Always set attempted_at since this is a claim attempt
        formData.append('attempted_at', now);

        // Set contacted_at if customer was reached
        if (['answered', 'promised', 'disputed', 'paid'].includes(customerResponse)) {
            formData.append('contacted_at', now);
        }

        // Set claim status based on response
        let claimStatus = 'attempted'; // default
        if (customerResponse === 'answered' || customerResponse === 'promised') {
            claimStatus = 'contacted';
        } else if (customerResponse === 'promised') {
            claimStatus = 'promised';
        } else if (customerResponse === 'paid') {
            claimStatus = 'resolved';
        }
        formData.append('claim_status', claimStatus);

        // Get CSRF token
        let csrfToken = '';
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            csrfToken = metaToken.getAttribute('content');
        } else {
            // Try to get from hidden input if meta tag doesn't exist
            const tokenInput = document.querySelector('input[name="_token"]');
            if (tokenInput) {
                csrfToken = tokenInput.value;
            }
        }

        if (!csrfToken) {
            alert('CSRF token not found. Please refresh the page and try again.');
            return;
        }

        console.log('Sending request to /admin/claims');

        fetch('/admin/claims', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    alert('Claim created successfully!');
                    // Remove modal using the specific style selector
                    const modal = document.querySelector('div[style*="fixed"]');
                    if (modal) modal.remove();
                    // Optionally refresh the table or add visual indicator
                    location.reload(); // Refresh page to show updated status
                } else {
                    alert('Error creating claim: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating claim: ' + error.message);
            });
    }

    function viewClaims(paymentId) {
        // Show claims history modal
        fetch(`/admin/claims/schedule-payment/${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showClaimsModal(data.claims);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading claims');
            });
    }

    function showClaimsModal(claims) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

        const claimsHtml = claims.map(claim => `
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge badge-${getStatusColor(claim.claim_status)}">${claim.claim_status}</span>
                        <span class="badge badge-outline-${getPriorityColor(claim.priority)} ms-2">${claim.priority}</span>
                    </div>
                    <small class="text-muted">${new Date(claim.created_at).toLocaleDateString()}</small>
                </div>
                <p class="mb-1"><strong>Type:</strong> ${claim.claim_type.toUpperCase()}</p>
                <p class="mb-1"><strong>Attempts:</strong> ${claim.attempt_count}</p>
                ${claim.notes ? `<p class="mb-1"><strong>Notes:</strong> ${claim.notes}</p>` : ''}
                ${claim.next_follow_up ? `<p class="mb-0"><strong>Next Follow-up:</strong> ${new Date(claim.next_follow_up).toLocaleString()}</p>` : ''}
            </div>
        </div>
    `).join('');

        modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Claims History</h5>
                    <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                </div>
                <div class="modal-body">
                    ${claims.length > 0 ? claimsHtml : '<p class="text-center text-muted">No claims found for this payment.</p>'}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                </div>
            </div>
        </div>
    `;
        document.body.appendChild(modal);
    }

    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'attempted': 'info',
            'contacted': 'primary',
            'promised': 'success',
            'failed': 'danger',
            'resolved': 'success'
        };
        return colors[status] || 'secondary';
    }

    function getPriorityColor(priority) {
        const colors = {
            'low': 'secondary',
            'medium': 'info',
            'high': 'warning',
            'urgent': 'danger'
        };
        return colors[priority] || 'secondary';
    }

    function sendReminder(paymentId) {
        if (confirm('Send payment reminder to customer?')) {
            // Add your AJAX call here to send reminder
            console.log('Sending reminder for payment:', paymentId);
        }
    }

    function exportSchedulePayments() {
        // Add your export logic here
        console.log('Exporting schedule payments');
    }
</script>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <div class="py-10 text-center">
                    <span class="spinner-border spinner-border-sm align-middle me-2"></span>
                    <span class="text-gray-600">Loading...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('paymentDetailsModal');
        const contentEl = document.getElementById('paymentDetailsContent');

        function renderPaymentDetails(data) {
            const {
                schedule,
                payment,
                user,
                checkout
            } = data;
            return `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-600">Schedule</div>
                    <div class="mt-2 space-y-1">
                        <div><span class="text-gray-600">Instalment #:</span> <strong>${schedule.instalment_number}</strong></div>
                        <div><span class="text-gray-600">Due Date:</span> <strong>${schedule.due_date || '-'}</strong></div>
                        <div><span class="text-gray-600">Amount:</span> <strong>${Number(schedule.instalment_amount || 0).toLocaleString()} SAR</strong></div>
                        <div><span class="text-gray-600">Late Fee:</span> <strong>${Number(schedule.late_fee || 0).toLocaleString()} SAR</strong></div>
                        <div><span class="text-gray-600">Status:</span> <strong>${(schedule.status || '').toUpperCase()}</strong></div>
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Payment</div>
                    ${payment ? `
                        <div class="mt-2 space-y-1">
                            <div><span class="text-gray-600">Payment ID:</span> <strong>#${String(payment.id).padStart(6, '0')}</strong></div>
                            <div><span class="text-gray-600">Amount:</span> <strong>${Number(payment.amount || 0).toLocaleString()} SAR</strong></div>
                            <div><span class="text-gray-600">Status:</span> <strong>${(payment.status || '').toUpperCase()}</strong></div>
                            <div><span class="text-gray-600">Method:</span> <strong>${payment.method || '-'}</strong></div>
                            <div><span class="text-gray-600">Reference:</span> <strong>${payment.reference || '-'}</strong></div>
                            <div><span class="text-gray-600">Date:</span> <strong>${payment.created_at || '-'}</strong></div>
                        </div>
                    ` : `
                        <div class="text-gray-600 mt-2">No payment recorded for this instalment.</div>
                    `}
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-600">Customer</div>
                    <div class="mt-2">
                        <div class="font-semibold">${user?.name || 'N/A'}</div>
                        <div class="text-xs text-gray-500">${user?.email || ''}</div>
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Checkout</div>
                    <div class="mt-2">
                        <div class="font-semibold">#${checkout?.id || '-'}</div>
                        <div class="text-xs text-gray-500">${checkout ? Number(checkout.total_amount || 0).toLocaleString() + ' SAR' : ''}</div>
                    </div>
                </div>
            </div>
        `;
        }

        function showModal() {
            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                // Fallback
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.style.position = 'fixed';
                modalEl.style.inset = '0';
                modalEl.style.zIndex = '1056';
                modalEl.style.overflowY = 'auto';
                modalEl.removeAttribute('aria-hidden');
                modalEl.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');
                let backdrop = document.getElementById('paymentDetailsBackdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.id = 'paymentDetailsBackdrop';
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.style.zIndex = '1055';
                    document.body.appendChild(backdrop);
                    backdrop.addEventListener('click', hideModal);
                }
            }
        }

        function hideModal() {
            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            } else {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                document.body.classList.remove('modal-open');
                const backdrop = document.getElementById('paymentDetailsBackdrop');
                if (backdrop) backdrop.remove();
            }
        }

        window.viewPaymentDetails = function(scheduleId) {
            contentEl.innerHTML = '<div class="py-10 text-center"><span class="spinner-border spinner-border-sm align-middle me-2"></span><span class="text-gray-600">Loading...</span></div>';
            showModal();
            const url = "{{ route('schedulePayments.payment.json', ['schedulePayment' => '__ID__']) }}".replace('__ID__', scheduleId);
            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        contentEl.innerHTML = renderPaymentDetails(res.data);
                    } else {
                        contentEl.innerHTML = '<div class="text-danger">Failed to load payment details.</div>';
                    }
                })
                .catch(() => {
                    contentEl.innerHTML = '<div class="text-danger">An error occurred while loading details.</div>';
                });
        }
    });
</script>
@endpush