@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">

        <div class="container-fixed" id="content_container"></div>
        <div class="bg-center bg-cover bg-no-repeat hero-bg">

            @include('admin.accounts.includes.profile')

        </div>

        @include('admin.accounts.includes.header')

        <div class="container-fixed">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
                <div class="col-span-1 lg:col-span-2">
                    <div
                        class="card bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white shadow-lg rounded-2xl">
                        <div class="card-body p-6">
                            <h3 class="text-lg font-semibold mb-4">{{ translate('Finance Overview') }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Total Entitlement') }}</span>
                                    <span class="text-xl font-bold">
                                        <span class="icon-saudi_riyal"></span>
                                        {{ number_format($totalEntitlement ?? 0, 2) }}
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Total Paid') }}</span>
                                    <span class="text-xl font-bold">
                                        <span class="icon-saudi_riyal"></span>
                                        {{ number_format($totalPaid ?? 0, 2) }}
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Pending Payment') }}</span>
                                    <span class="text-xl font-bold">
                                        <span class="icon-saudi_riyal"></span>
                                        {{ number_format(($totalEntitlement ?? 0) - ($totalPaid ?? 0), 2) }}
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Total Commission') }}</span>
                                    <span class="text-xl font-bold">
                                        <span class="icon-saudi_riyal"></span>
                                        {{ number_format($totalCommission ?? 0, 2) }}
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Total Products in Stock') }}</span>
                                    <span class="text-xl font-bold">{{ $stockCount ?? 0 }}</span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Average Product Rating') }}</span>
                                    <span class="text-xl font-bold">{{ number_format($avgRating ?? 0, 1) }} / 5</span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Account Status') }}</span>
                                    <span class="text-xl font-bold">
                                        <span class="text-green-300">{{ $status ?? '-' }}</span>
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm opacity-80">{{ translate('Last Payment Date') }}</span>
                                    <span class="text-xl font-bold">
                                        {{ $lastPaymentDate ? \Carbon\Carbon::parse($lastPaymentDate)->format('d M Y H:m:s') : '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-1">
                    <div
                        class="card bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 text-white shadow-lg rounded-2xl">
                        <div class="card-body p-6">
                            <h3 class="text-lg font-semibold mb-4">{{ translate('Supplier Financial Stats') }}</h3>

                            <div class="flex flex-col gap-5">
                                <div class="w-full">
                                    <span class="text-sm opacity-80">{{ translate('Total Orders Supplied') }}</span>
                                    <span class="text-xl font-bold">{{ number_format($totalOrders ?? 0) }}</span>
                                </div>

                                <div class="w-full">
                                    <span class="text-sm opacity-80">{{ translate('Total Products Supplied') }}</span>
                                    <span class="text-xl font-bold">{{ number_format($totalProducts ?? 0) }}</span>
                                </div>

                                <div class="w-full">
                                    <span class="text-sm opacity-80">{{ translate('Average Delivery Time') }}</span>
                                    <span class="text-xl font-bold">
                                        {{ $avgDeliveryTime !== null ? number_format($avgDeliveryTime, 1) . ' ' . translate('days') : '-' }}
                                    </span>
                                </div>

                                <div class="w-full">
                                    <span class="text-sm opacity-80">{{ translate('Total Returned Orders') }}</span>
                                    <span class="text-xl font-bold">{{ number_format($totalReturns ?? 0) }}</span>
                                </div>

                                <div class="w-full">
                                    <span class="text-sm opacity-80">{{ translate('Total Cancelled Orders') }}</span>
                                    <span class="text-xl font-bold">{{ number_format($totalCancelled ?? 0) }}</span>
                                </div>

                                <div class="w-full">
                                    <span class="text-sm opacity-80">{{ translate('Last Supplied Order Date') }}</span>
                                    <span class="text-xl font-bold">
                                        {{ $lastOrderDate ? \Carbon\Carbon::parse($lastOrderDate)->format('d-M-Y') : '-' }}
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-1 lg:col-span-3 mt-4">
                <div class="card">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Log Changes To Limit the Customer') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table"
                                        placeholder="{{ translate('Search users') }}" type="text" value="" />
                                </label>
                            </div>
                            <a class="btn btn-sm btn-light" data-modal-toggle="#limit_create_modal">
                                {{ translate('Create New Limit') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                {{ translate('No') }}
                                            </th>
                                            <th>
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Limit Arabinpay After') }}
                                                    </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Limit Arabinpay Before') }}
                                                    </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Commission') }}
                                                    </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Created At') }}
                                                    </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Action') }}
                                                    </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($creditLimitLogs as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>{{ number_format($item->limit_arabianpay_after, 2) ?? '—' }}</td>
                                                <td>{{ number_format($item->limit_arabianpay_before, 2) ?? '—' }}</td>
                                                <td>{{ number_format($item->comission, 2) ?? '—' }} %</td>
                                                <td>{{ $item->created_at->format(dateFormat()) }}</td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            data-modal-toggle="#limit_update_modal"
                                                            data-id="{{ $item->id }}"
                                                            data-before="{{ $item->limit_arabianpay_before }}"
                                                            data-after="{{ $item->limit_arabianpay_after }}"
                                                            data-commission="{{ $item->comission }}"
                                                            data-action="{{ route('customerUpgradeLimit') }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @include('layouts.includes.table-pagination', [
                                'paginator' => $creditLimitLogs,
                            ])
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- End of Container -->
    </main>

    <div class="modal" data-modal="true" id="limit_update_modal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">User Credit Limit the Customer</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                    data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-0 pb-5">
                <form action="{{ route('customerUpgradeLimit') }}" method="POST" class="px-5 pt-3">
                    @csrf
                    <input type="hidden" id="credit_limit_id" name="credit_limit_id">

                    <div class="mb-4">
                        <label class="form-label" for="limit_arabianpay_before">Limit Arabianpay Before</label>
                        <input type="text" id="limit_arabianpay_before" name="limit_arabianpay_before" class="input"
                            value="{{ old('limit_arabianpay_before', $merchant->limit_arabianpay_after ?? '') }}"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="limit_arabianpay_after">Limit Arabianpay After</label>
                        <input type="text" id="limit_arabianpay_after" name="limit_arabianpay_after" class="input"
                            value="{{ old('limit_arabianpay_after', $merchant->limit_arabianpay_after ?? '') }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Commission</label>
                        <input type="text" name="comission" class="input"
                            value="{{ old('comission', $merchant->comission ?? '') }}" required>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Upgrade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" data-modal="true" id="limit_create_modal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">User Credit Limit the Customer</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                    data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-0 pb-5">
                <form action="{{ route('createCreditLimit') }}" method="POST" class="px-5 pt-3">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $merchant->user_id }}">
                    <input type="hidden" name="package_id" value="{{ $merchant->package_id }}">

                    <div class="mb-4">
                        <label class="form-label">Limit Arabianpay Before</label>
                        <input type="text" name="limit_arabianpay_before" class="input"
                            value="{{ old('limit_arabianpay_before', $merchant->limit_arabianpay_after ?? '') }}"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Limit Arabianpay After</label>
                        <input type="text" name="limit_arabianpay_after" class="input"
                            value="{{ old('limit_arabianpay_after', $merchant->limit_arabianpay_after ?? '') }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Commission</label>
                        <input type="text" name="comission" class="input"
                            value="{{ old('comission', $merchant->comission ?? '') }}" required>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('[data-modal-toggle="#limit_update_modal"]');

            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('limit_update_modal');
                    modal.querySelector('#credit_limit_id').value = this.getAttribute('data-id');
                    modal.querySelector('#limit_arabianpay_before').value = this.getAttribute(
                        'data-before');
                    modal.querySelector('#limit_arabianpay_after').value = this.getAttribute(
                        'data-after');
                    modal.querySelector('#comission').value = this.getAttribute('data-comission');
                    modal.querySelector('form').setAttribute('action', this.getAttribute(
                        'data-action'));
                    modal.querySelector('#credit_limit_id').value = this.getAttribute('data-id');
                });
            });
        });
    </script>
@endpush
