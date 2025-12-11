@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ Illuminate\Support\Str::ucfirst($status) }} {{ translate('Requests') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ Illuminate\Support\Str::ucfirst($status) }} {{ translate('Requests') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#refund_requests_table"
                                        placeholder="{{ translate('Search') }}" type="text" value="" />
                                </label>
                            </div>
                            <div class="flex justify-end">
                                <button id="bulk-transfer-btn" class="btn btn-sm btn-primary hidden"
                                    data-modal-toggle="#transfer_request_bulk">
                                    <i class="ki-filled ki-disconnect"></i> {{ translate('Bulk Transfer') }}
                                </button>
                            </div>
                            @include('admin.components.transfer-request-bulk', [
                                'employees' => getEmployees(),
                                'model_type' => 'App\Models\RefundRequest',
                            ])
                        </div>
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
                                            <th class="text-center">{{ translate('User') }}</th>
                                            <th class="text-center">{{ translate('Order') }}</th>
                                            <th class="text-center">{{ translate('Refund Amount') }}</th>
                                            <th class="text-center">{{ translate('Refund Status') }}</th>
                                            <th class="text-center">{{ translate('Created At') }}</th>
                                            <th class="text-left">{{ translate('Assigned To') }}</th>
                                            <th class="text-center">{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($refundRequests as $request)
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm row-checkbox" type="checkbox"
                                                        name="ids[]" value="{{ $request->id }}">
                                                </td>
                                                <td class="text-center">{{ $request->id }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        <a href="{{ route('customerProfile', ['id' => $request->user?->id]) }}"
                                                            class="underline">{{ $request->user?->business_name ?? $request->user?->first_name }}</a>
                                                        <br>
                                                        <small class="text-gray-500">
                                                            {{ $request->user?->email ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="whitespace-nowrap text-sm">
                                                        <div>
                                                            <span>{{ translate('Amount') }}:</span>
                                                            <span class="icon-saudi_riyal"></span>
                                                            {{ isset($request->order) && isset($request->order->grand_total) ? number_format($request->order->grand_total, 2) : 'N/A' }}

                                                        </div>
                                                        <div>
                                                            <span>{{ translate('City') }}:</span>
                                                            <small class="text-xs text-gray-500">
                                                                {{ $request->order->shipping_city ?? '—' }}
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <span>{{ translate('Status') }}:</span>
                                                            <small
                                                                class="badge badge-sm badge-outline 
                                                            @if ($request->order->general_status == 'processing') badge-info
                                                            @elseif ($request->order->general_status == 'completed') badge-success
                                                            @elseif ($request->order->general_status == 'cancelled') badge-warning
                                                            @elseif ($request->order->general_status == 'failed') badge-danger
                                                            @else badge-secondary @endif">
                                                                {{ Str::ucfirst($request->order->general_status) }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ $request->refund_amount }}</td>
                                                <td class="text-center">
                                                    @if ($request->refund_status)
                                                        <span
                                                            class="badge badge-sm badge-outline badge-{{ $request->refund_status == 'approved' ? 'success' : ($request->refund_status == 'rejected' ? 'danger' : 'warning') }}">
                                                            {{ Str::ucfirst($request->refund_status) }}
                                                        </span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-secondary">{{ translate('N/A') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $request->created_at->format(dateFormat()) }}
                                                </td>
                                                <td>
                                                    {{ $request->assigned ? $request->assigned->first_name . ' ' . $request->assigned->last_name : translate('--Not Assigned--') }}
                                                </td>
                                                <td class="text-center">
                                                    <form
                                                        action="{{ route('refund-requests.update-status', $request->id) }}"
                                                        method="POST" id="statusForm-{{ $request->id }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="refund_status" class="select"
                                                            onchange="document.getElementById('statusForm-{{ $request->id }}').submit()">
                                                            <option value="pending"
                                                                {{ $request->refund_status == 'pending' ? 'selected' : '' }}>
                                                                {{ translate('Pending') }}
                                                            </option>
                                                            <option value="approved"
                                                                {{ $request->refund_status == 'approved' ? 'selected' : '' }}>
                                                                {{ translate('Approve') }}
                                                            </option>
                                                            <option value="rejected"
                                                                {{ $request->refund_status == 'rejected' ? 'selected' : '' }}>
                                                                {{ translate('Reject') }}
                                                            </option>
                                                        </select>
                                                    </form>

                                                    <div class="flex gap-1 justify-center mt-2">
                                                        <button
                                                            class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                                            data-modal-toggle="#transfer_detail"
                                                            data-model-id="{{ $request->id }}"
                                                            data-model-type="App\Models\RefundRequest">
                                                            <i class="ki-filled ki-disconnect"></i>
                                                        </button>

                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('orders.details', ['id' => $request->order->id]) }}">
                                                            <i class="ki-filled ki-eye"> </i>
                                                        </a>
                                                    </div>
                                                </td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $refundRequests])
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
