@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ $type }} Transactions
                    </h1>
                </div>
            </div>
        </div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            Transactions
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table" placeholder="Search users"
                                        type="text" value="" />
                                </label>
                            </div>
                            <div class="flex justify-end">
                                <button id="bulk-transfer-btn" class="btn btn-sm btn-primary hidden"
                                    data-modal-toggle="#transfer_request_bulk">
                                    <i class="ki-filled ki-disconnect"></i> {{ translate('Bulk Transfer') }}
                                </button>
                            </div>
                        </div>
                        @include('admin.components.transfer-request-bulk', [
                            'employees' => getEmployees(),
                            'model_type' => 'App\Models\Transaction',
                        ])
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input class="checkbox checkbox-sm" data-datatable-check="true"
                                                    type="checkbox" id="select-all-checkbox">
                                            </th>
                                            <th class="w-[60px] text-center">ID</th>
                                            <th class="text-left">User</th>
                                            <th class="text-left">Order ID</th>
                                            <th class="text-left">Loan Amount</th>
                                            <th class="text-left">Loan Period</th>
                                            <th class="text-left">Status</th>
                                            <th class="text-left">Settlement Status</th>
                                            <th class="text-left">Created At</th>
                                            <th class="text-left">Assigned To</th>
                                            <th class="text-left">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $item)
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm row-checkbox" type="checkbox"
                                                        name="ids[]" value="{{ $item->id }}">
                                                </td>
                                                <td class="text-center">{{ $item->id }}</td>

                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $item->user?->first_name }} {{ $item->user?->last_name }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $item->user?->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="whitespace-nowrap text-sm">
                                                        <div>
                                                            <span>Amount:</span>
                                                            {{ number_format($item->order->grand_total, 2) }} SAR
                                                        </div>
                                                        <div>
                                                            <span>City:</span> <small
                                                                class="text-xs text-gray-500">{{ $item->order->shipping_city ?? '—' }}</small>
                                                        </div>
                                                        <div>
                                                            <span>Status:</span>
                                                            <small
                                                                class="badge badge-sm badge-outline 
                                                        @if ($item->order->general_status == 'processing') badge-info
                                                        @elseif ($item->order->general_status == 'completed') badge-success
                                                        @elseif ($item->order->general_status == 'cancelled') badge-warning
                                                        @elseif ($item->order->general_status == 'failed') badge-danger
                                                        @else badge-secondary @endif">
                                                                {{ ucfirst($item->order->general_status) }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>


                                                <td class="whitespace-nowrap">
                                                    {{ number_format($item->loan_amount, 2) }} SAR
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    @if ($item->loan_start_date && $item->loan_end_date)
                                                        <div>
                                                            <strong>Start Date:</strong>
                                                            {{ \Carbon\Carbon::parse($item->loan_start_date)->format(dateFormat()) }}
                                                        </div>
                                                        <div>
                                                            <strong>End Date:</strong>
                                                            {{ \Carbon\Carbon::parse($item->loan_end_date)->format(dateFormat()) }}
                                                        </div>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    @php
                                                        $statusColors = [
                                                            'pending' => 'badge-warning',
                                                            'due' => 'badge-info',
                                                            'late' => 'badge-danger',
                                                            'paid' => 'badge-success',
                                                            'failed' => 'badge-secondary',
                                                        ];
                                                        $badgeColor =
                                                            $statusColors[$item->payment_status] ?? 'bg-gray-400';
                                                    @endphp

                                                    <span class="badge badge-sm badge-outline {{ $badgeColor }}">
                                                        {{ ucfirst($item->payment_status) }}
                                                    </span> <br>

                                                    <small class="text-gray-500">
                                                        —
                                                        @if ($item->payment_status !== 'paid' && $item->created_at)
                                                            {{ $item->created_at->diffForHumans() }}
                                                        @else
                                                            {{ $item->created_at ?? '—' }}
                                                        @endif
                                                    </small>
                                                </td>


                                                <td class="text-center">
                                                    @php
                                                        $settlementStatusColors = [
                                                            'settled' => 'badge-success',
                                                            'pending' => 'badge-warning',
                                                            'in_progress' => 'badge-info',
                                                            'failed' => 'badge-danger',
                                                        ];
                                                        $settlementBadgeColor =
                                                            $settlementStatusColors[$item->settlement_status] ??
                                                            'bg-gray-400';
                                                    @endphp
                                                    <span class="badge badge-sm badge-outline {{ $settlementBadgeColor }}">
                                                        {{ ucfirst($item->settlement_status) }}
                                                    </span>
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    {{ $item->created_at ? $item->created_at->format(dateFormat()) : 'N/A' }}
                                                </td>
                                                <td>
                                                    {{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : __('--Not Assigned--') }}
                                                </td>
                                                <td>
                                                    <button
                                                        class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                                        data-modal-toggle="#transfer_detail"
                                                        data-model-id="{{ $item->id }}"
                                                        data-model-type="App\Models\Transaction">
                                                        <i class="ki-filled ki-disconnect"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $transactions])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @include('admin.components.transfer-detail')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bulkBtn = document.getElementById('bulk-transfer-btn');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.querySelector('input[data-datatable-check="true"]');

            function toggleBulkBtn() {
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                bulkBtn.classList.toggle('hidden', !anyChecked);
            }

            // Toggle bulk button when any row checkbox changes
            checkboxes.forEach(cb => cb.addEventListener('change', () => {
                toggleBulkBtn();

                // Also update the "select all" checkbox state
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }));

            // When header "select all" checkbox toggled
            selectAllCheckbox.addEventListener('change', () => {
                const checked = selectAllCheckbox.checked;
                checkboxes.forEach(cb => cb.checked = checked);
                toggleBulkBtn();
            });
        });
    </script>
@endpush
