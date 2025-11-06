@extends('layouts.base')

@push('styles')
<style>
    .checkout-status-badge {
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

    .tab-content {
        min-height: 400px;
    }

    .customer-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <!-- Breadcrumbs -->
        <div class="flex flex-wrap items-center gap-1 pb-5">
            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-primary text-sm">
                <i class="ki-filled ki-home-2"></i>
                Dashboard
            </a>
            <i class="ki-filled ki-right text-gray-400 text-xs"></i>
            <a href="{{ route('checkouts.index') }}" class="text-gray-600 hover:text-primary text-sm">
                Checkouts
            </a>
            <i class="ki-filled ki-right text-gray-400 text-xs"></i>
            <span class="text-gray-900 text-sm font-medium">Checkout #{{ str_pad($checkout->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>

        <!-- Page Header -->
        <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Checkout Details
                </h1>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <span>Customer: {{ $checkout->user->name ?? 'Unknown Customer' }}</span>
                    <span class="text-gray-400">•</span>
                    <span>ID: #{{ str_pad($checkout->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2.5">

                <div class="dropdown" data-dropdown="true">
                    <button class="dropdown-toggle btn btn-sm btn-outline btn-primary" data-dropdown-trigger="click">
                        <i class="ki-filled ki-dots-vertical"></i>
                    </button>
                    <div class="dropdown-content menu-default w-full max-w-[175px]">
                        <div class="menu-item">
                            <a class="menu-link" href="#" onclick="printCheckout()">
                                <span class="menu-icon"><i class="ki-filled ki-printer"></i></span>
                                <span class="menu-title">Print</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="#" onclick="exportCheckout()">
                                <span class="menu-icon"><i class="ki-filled ki-file-down"></i></span>
                                <span class="menu-title">Export</span>
                            </a>
                        </div>
                        <div class="menu-separator"></div>
                        <div class="menu-item">
                            <a class="menu-link text-danger" href="#" onclick="deleteCheckout()">
                                <span class="menu-icon"><i class="ki-filled ki-trash"></i></span>
                                <span class="menu-title">Delete</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fixed">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5 mb-5">
            <!-- Customer Info -->
            <div class="col-span-1">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Customer Information</h3>
                    </div>
                    <div class="card-body">
                        <!-- Customer Avatar -->
                        <div class="flex items-center gap-4 pb-5 border-b border-gray-200">
                            <div class="customer-avatar">
                                {{ strtoupper(substr($checkout->user->first_name ?? 'U', 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">
                                    {{ $checkout->user->first_name ?? 'Unknown Customer' }}
                                </h4>
                                <p class="text-sm text-gray-600">Customer ID: #{{ str_pad($checkout->user->id ?? 0, 6, '0', STR_PAD_LEFT) }}</p>
                            </div>
                        </div>

                        <!-- Customer Details -->
                        <div class="pt-5 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Email:</span>
                                <a href="mailto:{{ $checkout->user->email }}" class="text-sm text-primary hover:text-primary-active">
                                    {{ $checkout->user->email ?? 'N/A' }}
                                </a>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Phone:</span>
                                <a href="tel:{{ $checkout->user->phone }}" class="text-sm text-primary hover:text-primary-active">
                                    {{ $checkout->user->phone ?? 'N/A' }}
                                </a>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Investment Pool:</span>
                                @if($checkout->investmentPool ?? null)
                                <a href="{{ route('investment-pools.show', $checkout->investmentPool) }}" class="text-sm text-primary hover:text-primary-active">
                                    {{ $checkout->investmentPool->name }}
                                </a>
                                @else
                                <span class="text-sm text-gray-500">Not assigned</span>
                                @endif
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Created:</span>
                                <span class="text-sm text-gray-700">
                                    {{ $checkout->created_at->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="col-span-1">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment Summary</h3>
                    </div>
                    <div class="card-body">
                        @php
                        $totalAmount = $checkout->total_amount ?? 0;
                        $collectedAmount = $checkout->schedulePayments->where('payment_status', 'paid')->sum('instalment_amount') ?? 0;
                        $outstandingAmount = $totalAmount - $collectedAmount;
                        $collectionPercentage = $totalAmount > 0 ? ($collectedAmount / $totalAmount) * 100 : 0;
                        @endphp

                        <!-- Summary Cards -->
                        <div class="space-y-4">
                            <!-- Total Amount -->
                            <div class="p-2 pt-0 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-small text-gray-600">Total Amount</p>
                                        <p class="text-5xl font-bold text-gray-900">{{ number_format($totalAmount, 2) }} SAR</p>
                                    </div>
                                    <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center">
                                        <i class="ki-filled ki-credit-cart text-primary text-xl"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Collected Amount -->
                            <div class="p-2 bg-green-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Collected</p>
                                        <p class="text-5xl font-bold text-green-600">{{ number_format($collectedAmount, 2) }} SAR</p>
                                    </div>
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="ki-filled ki-check-circle text-green-600 text-xl"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Outstanding Amount -->
                            <div class="p-2 bg-orange-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Outstanding</p>
                                        <p class="text-5xl font-bold text-orange-600">{{ number_format($outstandingAmount, 2) }} SAR</p>
                                    </div>
                                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                        <i class="ki-filled ki-time text-orange-600 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600">Collection Progress</span>
                                <span class="text-sm font-semibold text-gray-900">{{ number_format($collectionPercentage, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" style="width: {{ $collectionPercentage }}%"></div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5 mb-5">
            <div class="col-span-2">
                <div class="card">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex gap-4" id="langTabs">
                            <button class="tab-btn active" data-tab="orders">
                                <i class="ki-filled ki-package text-base me-2"></i>
                                Orders
                                @if($checkout->orders->count() > 0)
                                <span class="badge badge-sm badge-light-primary ms-2">{{ $checkout->orders->count() }}</span>
                                @endif
                            </button>
                            <button class="tab-btn" data-tab="payments">
                                <i class="ki-filled ki-calendar text-base me-2"></i>
                                Schedule Payments
                                @if($checkout->schedulePayments->count() > 0)
                                <span class="badge badge-sm badge-light-info ms-2">{{ $checkout->schedulePayments->count() }}</span>
                                @endif
                            </button>
                            <button class="tab-btn" data-tab="Finance Transaction">
                                <i class="ki-filled ki-wallet text-base me-2"></i>
                                Finance Transaction
                                
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-5">
                        <!-- Details Tab -->
                        <div class="tab-content" id="tab-orders">
                            @include('admin.checkouts.partials.orders')
                        </div>

                        <!-- Payments Tab -->
                        <div class="tab-content hidden" id="tab-payments">
                            @include('admin.checkouts.partials.schedule-payments')
                        </div>
                        <!-- Finance Transactions Tab -->
                        <div class="tab-content hidden" id="tab-Finance Transaction">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Journal Entries</h3>
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-light">{{ $entries->total() }} entries</span>
                                    </div>
                                </div>
                                    <div class="card-body">
                                    @if($entries->count() > 0)
                                    <div class="scrollable-x-auto">
                                        <table class="table table-auto table-border">
                                            <thead>
                                                <tr>
                                                    <th class="min-w-[150px]">Account</th>
                                                    <th class="min-w-[100px]">Debit</th>
                                                    <th class="min-w-[100px]">Credit</th>
                                                    <th class="min-w-[150px]">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                $totalDebit = 0;
                                                $totalCredit = 0;
                                                @endphp
                                                @foreach($entries as $entry)
                                                @php
                                                $totalDebit += $entry->debit ?? 0;
                                                $totalCredit += $entry->credit ?? 0;
                                                @endphp
                                                <tr>
                                                     <td>
                                                        @if($entry->account)
                                                        <span class="text-sm font-medium">{{ $entry->account->account_code }}</span>
                                                        <br>
                                                        <span class="text-xs text-gray-600">{{ $entry->account->account_name }}</span>
                                                        @else
                                                        <span class="text-gray-400">{{ $entry->account_name ?? '-' }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($entry->debit > 0)
                                                        <span class="text-red-600 font-medium"> {{ number_format($entry->debit, 2) }}</span>
                                                        @else
                                                        <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($entry->credit > 0)
                                                        <span class="text-green-600 font-medium"> {{ number_format($entry->credit, 2) }}</span>
                                                        @else
                                                        <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                   
                                                    <td>
                                                        @if($entry->notes)
                                                        <span class="text-sm text-wrap">{{ Str::limit($entry->notes, 500) }}</span>
                                                        @else
                                                        <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="bg-gray-50">
                                                    <td colspan="1" class="font-medium">{{ translate('Total') }}</td>
                                                    <td class="font-medium text-red-600">SR{{ number_format($totalDebit, 2) }}</td>
                                                    <td class="font-medium text-green-600">SR{{ number_format($totalCredit, 2) }}</td>
                                                    <td colspan="2" class="font-medium">
                                                        Balance:
                                                        <span class="{{ $totalDebit - $totalCredit == 0 ? 'text-green-600' : 'text-red-600' }}">
                                                            @if($totalDebit - $totalCredit == 0)
                                                            Balanced
                                                            @else
                                                            SR {{ number_format(abs($totalDebit - $totalCredit), 2) }}
                                                            Unbalanced
                                                            @endif
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    @if($entries->hasPages())
                                    <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                                        <div class="flex items-center gap-2">
                                            Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }}
                                            of {{ $entries->total() }} entries
                                        </div>
                                        <div class="flex items-center gap-4">
                                            {{ $entries->links() }}
                                        </div>
                                    </div>
                                    @endif
                                    @else
                                    <div class="flex flex-col items-center gap-3 py-10">
                                        <i class="ki-filled ki-file-sheet text-3xl text-gray-400"></i>
                                        <span class="text-gray-600">No journal entries found for this transaction</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content - Tabs -->

    </div>
</main>

<script>
    // Print checkout function
    function printCheckout() {
        window.print();
    }

    // Export checkout function  
    function exportCheckout() {
        console.log('Export checkout data');
    }

    // Delete checkout function
    function deleteCheckout() {
        if (confirm('Are you sure you want to delete this checkout? This action cannot be undone.')) {
            fetch(`{{ route('checkouts.destroy', $checkout) }}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("checkouts.index") }}';
                    } else {
                        alert('Failed to delete checkout');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting checkout');
                });
        }
    }
</script>
@endsection