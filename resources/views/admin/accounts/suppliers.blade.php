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
                        {{ translate('Suppliers') }}
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
                            {{ translate('Suppliers') }}
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
                            @include('admin.components.transfer-request-bulk', [
                                'employees' => getEmployees(),
                                'model_type' => 'App\Models\Merchant',
                            ])

                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input class="checkbox checkbox-sm" data-datatable-check="true"
                                                    type="checkbox" id="select-all-checkbox">
                                            </th>
                                            <th class="w-[60px] text-center">
                                                {{ translate('ID') }}
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Name') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('CR Number') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Business Type') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="">
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Assigned To') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Status') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Action') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($merchants as $item)
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
                                                            — {{ $item->user?->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>{{ $item->cr_number }}</td>
                                                <td>{{ $item->businessType->name ?? 'N/A' }}</td>
                                                <td>
                                                    {{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : __('--Not Assigned--') }}
                                                </td>
                                                <td>
                                                    <!-- Badge for Status -->
                                                    <span
                                                        class="badge badge-sm badge-outline 
                                                    @if ($item->status == 'approved') badge-success
                                                    @elseif($item->status == 'pending') 
                                                        badge-danger
                                                    @else
                                                        badge-warning @endif">
                                                        {{ ucfirst($item->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('supplierProfile', ['id' => $item->user_id]) }}">
                                                            <i class="ki-filled ki-notepad-edit"> </i>
                                                        </a>
                                                        <button
                                                            class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                                            data-modal-toggle="#transfer_detail"
                                                            data-model-id="{{ $item->id }}"
                                                            data-model-type="App\Models\Merchant">
                                                            <i class="ki-filled ki-disconnect"></i>
                                                        </button>

                                                        @if (Auth::user()->user_type == 'admin')
                                                            <a target="__blank"
                                                                class="btn btn-sm btn-icon btn-clear btn-warning"
                                                                href="{{ route('impersonate.redirect', ['id' => $item->user_id]) }}"
                                                                onclick="return confirm('Login to this partner account?')">
                                                                <i class="ki-filled ki-wrench"> </i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $merchants])
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
