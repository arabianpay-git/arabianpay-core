@extends('layouts.base')

@section('content')
    <main class="flex-grow w-full mx-auto p-6 space-y-6 bg-slate-50">

        @include('admin.accounts.includes.supplier-profile-header')

        @include('admin.accounts.includes.supplier-nav')

        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 space-y-6">
                <div class="card">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Supplier Orders') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table"
                                        placeholder="{{ translate('Search users') }}" type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('User') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Seller') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Payment Type') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Payment Status') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Grand Total') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Coupon Discount') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Delivery Status') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('General Status') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Created At') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Action') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $index => $item)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
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
                                                    <div class="whitespace-nowrap">
                                                        {{ $item->seller?->first_name }}
                                                        {{ $item->seller?->last_name }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $item->seller?->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td class="text-center">{{ $item->payment_type }}</td>
                                                <td class="text-center">
                                                    @if ($item->payment_status == 'completed')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ ucfirst($item->payment_status) }}</span>
                                                    @elseif($item->payment_status == 'pending')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-warning">{{ ucfirst($item->payment_status) }}</span>
                                                    @elseif($item->payment_status == 'failed')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-danger">{{ ucfirst($item->payment_status) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-info">{{ ucfirst($item->payment_status) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ number_format($item->grand_total, 2) }}</td>
                                                <td class="text-center">{{ number_format($item->coupon_discount, 2) }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($item->delivery_status == 'delivered')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ ucfirst($item->delivery_status) }}</span>
                                                    @elseif($item->delivery_status == 'shipped')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-warning">{{ ucfirst($item->delivery_status) }}</span>
                                                    @elseif($item->delivery_status == 'pending')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-primary">{{ ucfirst($item->delivery_status) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-info">{{ ucfirst($item->delivery_status) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($item->general_status == 'completed')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ ucfirst($item->general_status) }}</span>
                                                    @elseif($item->general_status == 'Processing')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-warning">{{ ucfirst($item->general_status) }}</span>
                                                    @elseif($item->general_status == 'cancelled')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-danger">{{ ucfirst($item->general_status) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-info">{{ ucfirst($item->general_status) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item->created_at->format(dateFormat()) }}
                                                </td>
                                                <!-- Action buttons -->
                                                <td class="text-center">
                                                    <div class="flex gap-1 justify-center">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('orders.details', ['id' => $item->id]) }}">
                                                            <i class="ki-filled ki-notepad-edit"> </i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $orders])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
