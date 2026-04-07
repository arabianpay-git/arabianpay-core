    @extends('layouts.base')

    @section('content')
        <main class="grow content pt-5" id="content" role="content">
            <div class="container-fixed">
                <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                    <div class="flex flex-col justify-center gap-2">
                        <h1 class="text-xl font-medium leading-none text-gray-900">
                            {{ translate('Customers Schedule Payments') }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="container-fixed">
                <div class="grid gap-5 lg:gap-7.5">
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ translate('Schedule Payments') }}
                            </h3>
                            <div class="flex flex-wrap gap-2 lg:gap-5 items-center">
                                <div class="flex">
                                    <form method="GET" action="{{ route('repaymentSchedule') }}" class="flex">
                                        <label class="input input-sm">
                                            <i class="ki-filled ki-magnifier"></i>
                                            <input name="search" type="text"
                                                placeholder="{{ translate('Search by user') }}"
                                                value="{{ request('search') }}" />
                                        </label>
                                        <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                                            {{ translate('Search') }}
                                        </button>
                                    </form>
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
                                'model_type' => 'App\Models\SchedulePayment',
                            ])
                        </div>
                        <div class="card-body">
                            <div data-datatable="true" data-datatable-state-save="false" id="refund_requests_table">
                                <div class="scrollable-x-auto">
                                    <table class="table table-auto table-border" data-datatable-table="true">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input class="checkbox checkbox-sm" data-datatable-check="true"
                                                        type="checkbox" id="select-all-checkbox">
                                                </th>
                                                <th class="w-[60px] text-center">{{ translate('ID') }}</th>
                                                <th>{{ translate('User Name') }}</th>
                                                <th class="text-left">{{ translate('Instalment ID') }}</th>
                                                <th class="text-left">{{ translate('Instalment Number') }}</th>
                                                <th class="text-left">{{ translate('Due Date') }}</th>
                                                <th class="text-left">{{ translate('Instalment Amount') }}</th>
                                                <th class="text-left">{{ translate('Payment Status') }}</th>
                                                <th class="text-left">{{ translate('Assigned To') }}</th>
                                                <th class="text-left">{{ translate('Action') }}</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($schedulePayments as $item)
                                                <tr>
                                                    <td>
                                                        <input class="checkbox checkbox-sm row-checkbox" type="checkbox"
                                                            name="ids[]" value="{{ $item->id }}">
                                                    </td>
                                                    <td class="text-center">{{ $item->id }}</td>
                                                    <td>
                                                        <div class="whitespace-nowrap">
                                                            {{ $item->user->first_name }} {{ $item->user->last_name }}
                                                            <br>
                                                            <small class="text-gray-500">
                                                                — {{ $item->user->business_name ?? '-' }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <small>{{ $item->uuid }}</small>
                                                    </td>
                                                    <td>{{ $item->instalment_number }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($item->due_date)->format('d-m-Y') }}</td>
                                                    <td>{{ number_format($item->instalment_amount, 2) }} SAR</td>
                                                    <td>
                                                        @php
                                                            $statusValue = $item->payment_status instanceof \BackedEnum
                                                                ? $item->payment_status->value
                                                                : (string) ($item->payment_status ?? '');
                                                            $paymentBadgeClass = match ($statusValue) {
                                                                'paid' => 'badge-success',
                                                                'pending' => 'badge-warning',
                                                                'due' => 'badge-primary',
                                                                'late' => 'badge-danger',
                                                                'failed' => 'badge-secondary',
                                                                'unpaid' => 'badge-secondary',
                                                                'current' => 'badge-primary',
                                                                'partially_paid' => 'badge-warning',
                                                                'cancelled' => 'badge-secondary',
                                                                'canceled' => 'badge-secondary',
                                                                default => 'badge-info',
                                                            };
                                                            $statusLabel = ucfirst(str_replace('_', ' ', $statusValue));
                                                        @endphp
                                                        <span
                                                            class="badge badge-sm badge-outline {{ $paymentBadgeClass }}">
                                                            {{ translate($statusLabel) }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        {{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : __('--Not Assigned--') }}
                                                    </td>
                                                    <td>
                                                        <button
                                                            class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                                            data-modal-toggle="#transfer_detail"
                                                            data-model-id="{{ $item->id }}"
                                                            data-model-type="App\Models\SchedulePayment">
                                                            <i class="ki-filled ki-disconnect"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination Footer -->
                                @include('layouts.includes.table-pagination', [
                                    'paginator' => $schedulePayments,
                                ])
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
