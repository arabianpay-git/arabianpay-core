@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate($type) }} {{ translate('Orders') }}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate($type) }} {{ translate('Orders') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#team_crew_table"
                                        placeholder="{{ translate('Search users') }}" type="text" value="" />
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
                            'model_type' => 'App\Models\Order',
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
                                            <th class="text-center">{{ translate('ID') }}</th>
                                            <th class="text-left">{{ translate('Customer') }}</th>
                                            <th class="text-left">{{ translate('Seller') }}</th>
                                            <th class="text-center">{{ translate('Payment Status') }}</th>
                                            <th class="text-center">{{ translate('Grand Total') }}</th>
                                            <th class="text-center">{{ translate('Delivery Status') }}</th>
                                            <th class="text-center">{{ translate('General Status') }}</th>
                                            <th class="text-center">{{ translate('Created At') }}</th>
                                            <th>{{ translate('Assigned To') }}</th>
                                            <th class="text-center">{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $item)
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm row-checkbox" type="checkbox"
                                                        name="ids[]" value="{{ $item->id }}">
                                                </td>
                                                <td class="text-center">{{ $item->id }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        <a href="{{ route('customerProfile', ['id' => $item->user?->id]) }}"
                                                            class="underline">
                                                            {{ $item->user?->first_name }} {{ $item->user?->last_name }}
                                                        </a>
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $item->user?->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        <a href="{{ route('supplierProfile', ['id' => $item->seller?->id]) }}"
                                                            class="underline">
                                                            {{ $item->seller?->first_name }}
                                                            {{ $item->seller?->last_name }}
                                                        </a>
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $item->seller?->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td class="text-center">
                                                    <div class="flex justify-center align-items-center flex-wrap"
                                                        style="gap:5px;">
                                                        @forelse ($item->schedulePayments as $payment)
                                                            @php
                                                                $statusValue = $payment->payment_status instanceof \BackedEnum
                                                                    ? $payment->payment_status->value
                                                                    : (string) $payment->payment_status;
                                                                $statusClass = match ($statusValue) {
                                                                    'paid' => 'badge-success',
                                                                    'pending' => 'badge-warning',
                                                                    'due' => 'badge-primary',
                                                                    'late' => 'badge-danger',
                                                                    'failed' => 'badge-dark',
                                                                    'unpaid' => 'badge-secondary',
                                                                    'current' => 'badge-primary',
                                                                    'partially_paid' => 'badge-warning',
                                                                    'cancelled' => 'badge-secondary',
                                                                    'canceled' => 'badge-secondary',
                                                                    default => 'badge-info',
                                                                };
                                                                $statusLabel = ucfirst(str_replace('_', ' ', $statusValue));
                                                            @endphp
                                                            <span class="badge badge-sm badge-outline {{ $statusClass }}"
                                                                style="width: 70px">
                                                                {{ translate($statusLabel) }}
                                                            </span>
                                                        @empty
                                                            <span
                                                                class="badge badge-sm badge-outline badge-secondary">{{ translate('No Payments') }}</span>
                                                        @endforelse
                                                    </div>
                                                </td>

                                                <td class="text-center">
                                                    <span
                                                        class="icon-saudi_riyal"></span>{{ number_format((float) $item->grand_total, 2) }}
                                                </td>

                                                <td class="text-center">
                                                    @if ($item->delivery_status == 'delivered')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ translate('Delivered') }}</span>
                                                    @elseif($item->delivery_status == 'shipped')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-warning">{{ translate('Shipped') }}</span>
                                                    @elseif($item->delivery_status == 'pending')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-primary">{{ translate('Pending') }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-info">{{ translate(ucfirst($item->delivery_status)) }}</span>
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    @if ($item->general_status == 'completed')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ translate('Completed') }}</span>
                                                    @elseif($item->general_status == 'Processing')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-warning">{{ translate('Processing') }}</span>
                                                    @elseif($item->general_status == 'cancelled')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-danger">{{ translate('Cancelled') }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-info">{{ translate(ucfirst($item->general_status)) }}</span>
                                                    @endif
                                                </td>

                                                <td class="text-center">{{ $item->created_at->format(dateFormat()) }}</td>
                                                <td>{{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : translate('--Not Assigned--') }}
                                                </td>

                                                <td class="text-center">
                                                    <div class="flex gap-1 justify-center">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('orders.details', ['id' => $item->id]) }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <button
                                                            class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                                            data-modal-toggle="#transfer_detail"
                                                            data-model-id="{{ $item->id }}"
                                                            data-model-type="App\Models\Order">
                                                            <i class="ki-filled ki-disconnect"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @include('layouts.includes.table-pagination', ['paginator' => $orders])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- End of Container -->
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
