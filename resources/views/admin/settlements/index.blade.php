@extends('layouts.base')

@section('content')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        Settlement Management
                    </h1>
                    <span class="text-sm text-gray-500">Manage weekly supplier settlements and payouts</span>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        
        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                
                <!-- Period Cards -->
                <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5">
                    <!-- Finished Period Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-calendar text-primary"></i>
                                Finished Period
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="flex flex-col gap-5">
                                <!-- Period Dates -->
                                <div class="flex items-center justify-between p-3 bg-light rounded-lg">
                                    <span class="text-sm text-gray-600">Period:</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $finishedPeriod['start_date']->format('M d') }} - {{ $finishedPeriod['end_date']->format('M d, Y') }}
                                    </span>
                                </div>
                                @if($finishedOrders->count() > 0)
                                <!-- Stats -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-gray-500 font-medium">Orders</span>
                                        <div class="flex items-center gap-2">
                                            <i class="ki-filled ki-package text-xl text-primary"></i>
                                            <span class="text-2xl font-bold text-gray-900">{{ number_format($finishedPeriod['orders_count']) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-gray-500 font-medium">Total Amount</span>
                                        <x-fintech.money :amount="$finishedPeriod['total_amount']" size="xl" color="green" />
                                    </div>
                                </div>
                                @endif
                                @if($finishedPeriod['unsettled_orders_count'] > 0)
                                <!-- Stats -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-gray-500 font-medium">Unsettled Orders</span>
                                        <div class="flex items-center gap-2">
                                            <i class="ki-filled ki-package text-xl text-primary"></i>
                                            <span class="text-2xl font-bold text-gray-900">{{ number_format($finishedPeriod['unsettled_orders_count']) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-gray-500 font-medium">Unsettled Amount</span>
                                        <x-fintech.money :amount="$finishedPeriod['unsettled_amount']" size="xl" color="green" />
                                    </div>
                                </div>
                                @endif
                                <!-- Generate Button -->
                                @can('settlement.create')
                                @if($finishedOrdersUnsettled->count() > 0)
                                    <a href="{{ route('settlements.generate') }}" class="btn btn-primary btn-sm w-full">
                                        <i class="ki-filled ki-plus-square"></i>
                                        Generate Settlements for This Period
                                    </a>
                                @endif
                                @endcan
                            </div>
                        </div>
                    </div>

                    <!-- Current Period Card -->
                    <div class="card border-2 border-dashed border-primary">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2">
                                <i class="ki-filled ki-calendar-tick text-warning"></i>
                                Current Period (In Progress)
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="flex flex-col gap-5">
                                <!-- Period Dates -->
                                <div class="flex items-center justify-between p-3 bg-warning-light rounded-lg">
                                    <span class="text-sm text-gray-600">Period:</span>
                                    <span class="font-semibold text-gray-900">
                                        {{ $currentPeriod['start_date']->format('M d') }} - {{ $currentPeriod['end_date']->format('M d, Y') }}
                                    </span>
                                </div>

                                <!-- Stats -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-gray-500 font-medium">Orders (accumulating)</span>
                                        <div class="flex items-center gap-2">
                                            <i class="ki-filled ki-package text-xl text-warning"></i>
                                            <span class="text-2xl font-bold text-gray-900">{{ number_format($currentPeriod['orders_count']) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-gray-500 font-medium">Running Total</span>
                                        <x-fintech.money :amount="$currentPeriod['total_amount']" size="xl" />
                                    </div>
                                </div>

                                <!-- Next Settlement Info -->
                                <div class="flex items-center gap-2 p-3 bg-light rounded-lg">
                                    <i class="ki-filled ki-check-circle text-success"></i>
                                    <span class="text-sm text-gray-700">
                                        Will be settled on <strong>{{ $currentPeriod['next_settlement']->format('M d, Y') }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                    <div class="card-body p-5">
                        <form method="get" action="{{ route('settlements.index') }}">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                <div class="flex flex-col gap-1">
                                     <input type="date" name="date_from"  placeholder="Date From"  >
                                </div>
                                <div class="flex flex-col gap-1">
                                    <input type="date" name="date_to"  placeholder="Date To"  >
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="form-label text-xs font-medium text-gray-700">Supplier ID</label>
                                    <select name="supplier_id" class="select select-sm w-full">
                                        <option value="">All Suppliers</option>
                                        @foreach ($merchants as $merchant)
                                            <option value="{{ $merchant->id }}" {{ request('supplier_id') == $merchant->id ? 'selected' : '' }}>{{ $merchant->first_name }} {{ $merchant->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="form-label text-xs font-medium text-gray-700">Status</label>
                                    <select name="status" class="select select-sm w-full">
                                        <option value="">All Statuses</option>
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending (legacy)</option>
                                        <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-4">
                                <button class="btn btn-sm btn-primary">
                                    <i class="ki-filled ki-filter"></i> Filter
                                </button>
                                <a href="{{ route('settlements.index') }}" class="btn btn-sm btn-light">
                                    <i class="ki-filled ki-arrows-circle"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                </div>

                
                

                <!-- Settlements Table -->
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2 items-center justify-between">
                        <h3 class="card-title font-medium text-sm">
                            Weekly Settlements
                        </h3>
                        
                        <!-- Batch Actions Toolbar -->
                        <div class="flex gap-2 items-center">
                            <span id="selected-count" class="text-sm text-gray-500 font-medium">0 selected</span>
                            <select id="batch-action-select" class="select select-sm w-56">
                                <option value="">Select Batch Action</option>
                                @can('settlement.approve')<option value="approve">✓ Approve Selected</option>@endcan
                                @can('settlement.pay')<option value="payout">💵 Process Payout</option>@endcan
                                @can('settlement.cancel')<option value="cancel">✗ Cancel Selected</option>@endcan
                                @can('settlement.export')<option value="bankfile">📁 Bank Transfer File</option>@endcan
                            </select>
                            <button id="execute-batch-action" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-flash"></i> Execute
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="batch-form" method="POST" action="">
                            @csrf
                            <input type="hidden" name="action_type" id="batch-action-type">
                            
                            <div data-datatable="true" data-datatable-state-save="false" id="settlements_table">
                                <div class="scrollable-x-auto">
                                    <table class="table table-auto table-border" data-datatable-table="true">
                                        <thead>
                                            <tr>
                                                <th class="w-[40px]">
                                                    <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox" id="select-all-checkbox">
                                                </th>
                                                <th class="w-[120px] text-center">Number</th>
                                                <th class="text-left">Supplier</th>
                                                <th class="text-center">Period</th>
                                                <th class="text-center">Orders</th>
                                                <th class="text-right">Amount</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Created</th>
                                                <th class="text-center w-[120px]">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($settlements as $item)
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td>
                                                        <input class="checkbox checkbox-sm row-checkbox" type="checkbox" name="settlement_ids[]" value="{{ $item->id }}">
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('settlements.show', $item->id) }}" class="text-primary hover:underline font-medium">
                                                            {{ $item->settlement_number }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="flex flex-col">
                                                            <span class="font-medium text-gray-900">{{ $item->supplier->business_name ?? 'Unknown' }}</span>
                                                            <span class="text-xs text-gray-500">{{ $item->supplier->first_name }} {{ $item->supplier->last_name }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-sm">
                                                        <div class="flex flex-col">
                                                            <span>{{ $item->start_date->format('M d') }}</span>
                                                            <span class="text-xs text-gray-500">to {{ $item->end_date->format('M d, Y') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-sm badge-outline badge-info">
                                                            <i class="ki-filled ki-package"></i>
                                                            {{ $item->orders_count }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <x-fintech.money :amount="$item->payable_amount" />
                                                    </td>
                                                    <td class="text-center">
                                                        <x-fintech.status-badge :status="$item->status" />
                                                    </td>
                                                    <td class="text-center text-gray-500 text-xs">
                                                        {{ $item->created_at->format('M d, Y') }}
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="flex gap-1 justify-center">
                                                            <a href="{{ route('settlements.show', $item->id) }}" class="btn btn-sm btn-icon btn-light" title="View Details">
                                                                <i class="ki-filled ki-eye"></i>
                                                            </a>
                                                            
                                                            @php $statusValue = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status; @endphp
                                                            @can('settlement.approve')
                                                            @if($statusValue === 'draft' || $statusValue === 'pending' || $statusValue === 'pending_approval')
                                                                <form action="{{ route('settlements.approve', $item->id) }}" method="POST" onsubmit="return confirm('Approve this settlement?');" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-icon btn-light-success" title="Approve">
                                                                        <i class="ki-filled ki-check"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @endcan

                                                            @can('settlement.pay')
                                                            @if($statusValue === 'approved')
                                                                <form action="{{ route('settlements.pay', $item->id) }}" method="POST" onsubmit="return confirm('Mark as Paid? This will generate financial entries.');" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-icon btn-light-primary" title="Mark Paid">
                                                                        <i class="ki-filled ki-dollar"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center py-10">
                                                        <div class="flex flex-col items-center justify-center gap-3">
                                                            <div class="rounded-full bg-light-primary p-4">
                                                                <i class="ki-filled ki-file-sheet text-4xl text-primary"></i>
                                                            </div>
                                                            <span class="text-gray-700 font-medium text-lg">No settlements found</span>
                                                            <span class="text-sm text-gray-500">Try adjusting your filters or generate new settlements from the finished period.</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-5 py-4">
                                    {{ $settlements->links() }}
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js"></script>

    <script>
         flatpickr("input[name='date_from']", {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
        });

        flatpickr("input[name='date_to']", {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
        });
        document.addEventListener('DOMContentLoaded', function() {
            // Select All Checkbox Logic
            const selectAll = document.getElementById('select-all-checkbox');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');

            if(selectAll) {
                selectAll.addEventListener('change', function() {
                    const checked = this.checked;
                    rowCheckboxes.forEach(cb => cb.checked = checked);
                    updateSelectedCount();
                });
            }

            // Update selected count on checkbox change
            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            function updateSelectedCount() {
                const count = document.querySelectorAll('.row-checkbox:checked').length;
                document.getElementById('selected-count').textContent = `${count} selected`;
                
                // Update select-all checkbox state
                if (selectAll) {
                    selectAll.checked = count === rowCheckboxes.length && count > 0;
                    selectAll.indeterminate = count > 0 && count < rowCheckboxes.length;
                }
            }

            // Execute Batch Action
            document.getElementById('execute-batch-action').addEventListener('click', function() {
                const action = document.getElementById('batch-action-select').value;
                const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
                
                if (!action) {
                    alert('Please select an action');
                    return;
                }
                
                if (selected.length === 0) {
                    alert('Please select at least one settlement');
                    return;
                }
                
                switch(action) {
                    case 'approve':
                        handleAjaxBatch('{{ route("settlements.batch-approve") }}', 'Approve selected settlements?');
                        break;
                    case 'payout':
                        handleAjaxBatch('{{ route("settlements.batch-payout") }}', 'Process payout for selected settlements? This will create financial entries.');
                        break;
                    case 'cancel':
                        handleBatchCancel();
                        break;
                    
                    case 'bankfile':
                        submitBatch('{{ route("settlements.bank-transfer-file") }}', null);
                        break;
                }
            });

            // Batch Form Submit
            const batchForm = document.getElementById('batch-form');

            function submitBatch(actionUrl, confirmMsg) {
                const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
                if (selected.length === 0) {
                    alert('Please select at least one settlement.');
                    return;
                }

                if (confirmMsg && !confirm(confirmMsg)) {
                    return;
                }

                batchForm.action = actionUrl;
                batchForm.submit();
            }

            function handleAjaxBatch(url, confirmMsg) {
                const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
                if (selected.length === 0) {
                    alert('Please select at least one settlement.');
                    return;
                }
                if (!confirm(confirmMsg)) return;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ settlement_ids: selected })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
                });
            }

            function handleBatchCancel() {
                const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
                
                if (selected.length === 0) {
                    alert('Please select at least one settlement');
                    return;
                }
                
                const reason = prompt('Enter cancellation reason:');
                if (!reason || reason.trim() === '') {
                    return;
                }
                
                fetch('{{ route("settlements.batch-cancel") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        settlement_ids: selected,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
                });
            }

            // Initialize
            updateSelectedCount();
        });
    </script>
    @endpush
@endsection
