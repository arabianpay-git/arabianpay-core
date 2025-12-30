@extends('layouts.base')

@section('content')
    @push('styles')
        <script src="https://cdn.tailwindcss.com"></script>
    @endpush

    <main class="grow content pt-5 bg-white" id="content" role="content">
        <!-- Header Container -->
        <div class="container-fixed">
            <div class="flex items-center justify-between gap-5 pb-7.5">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Suppliers') }}
                </h1>

                <a href="{{ route('merchants.trashed') }}" class="btn btn-sm btn-outline btn-warning">
                    <i class="ki-filled ki-trash"></i>
                    {{ translate('View Trash') }}
                    @php
                        $trashCount = \App\Models\Merchant::onlyTrashed()->count();
                    @endphp
                    @if ($trashCount > 0)
                        <span class="ml-1 badge badge-sm badge-danger">{{ $trashCount }}</span>
                    @endif
                </a>
            </div>

        </div>

        <!-- Search + Controls Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Suppliers') }}
                        </h3>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex gap-2">
                                <div class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input id="supplier-search-input" placeholder="Search users" type="text"
                                            value="{{ request('search') }}" autocomplete="off" />
                                    </label>
                                </div>

                                <select id="filter-status" class="select select-sm" style="width: 10rem;">
                                    <option value="">{{ translate('All Statuses') }}</option>
                                    @foreach (['under_review', 'contract_sent', 'active', 'pending', 'approved', 'suspended', 'blacklisted'] as $st)
                                        <option value="{{ $st }}"
                                            {{ request('status') == $st ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                                        </option>
                                    @endforeach
                                </select>

                                <select id="filter-employee" class="select select-sm" style="width: 10rem;">
                                    <option value="">{{ translate('All Employees') }}</option>
                                    @foreach (\App\Models\User::where('user_type', 'employee')->get() as $emp)
                                        <option value="{{ $emp->id }}"
                                            {{ request('employee') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->first_name }} {{ $emp->last_name }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Custom Onboarding Step Dropdown -->
                                {{-- @include('admin.accounts.components.on-boarding-filter') --}}

                                <a href="{{ route('suppliers') }}" class="btn btn-sm btn-light" id="clear-filters-btn"
                                    type="button">
                                    <i class="ki-filled ki-arrows-circle"></i>
                                    {{ translate('Clear') }}
                                </a>
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
                        <div id="suppliers-table-container">
                            @include('admin.accounts.partials.suppliers-trashed-table', [
                                'merchants' => $merchants,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('admin.components.transfer-detail')
@endsection
