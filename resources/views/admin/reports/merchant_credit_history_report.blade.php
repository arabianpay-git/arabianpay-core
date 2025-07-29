@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Header Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('customer Credit History') }}
                    </h1>
                </div>
            </div>
        </div>

        @include('admin.reports.includes.filter')

        <!-- Table Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('customer Credit Report') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#customer_credit_table"
                                        placeholder="{{ translate('Search customers') }}" type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="customer_credit_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center">{{ translate('User ID') }}</th>
                                            <th>{{ translate('Customer') }}</th>
                                            <th>{{ translate('Credit Limit') }}</th>
                                            <th>{{ translate('Utilized Amount') }}</th>
                                            <th>{{ translate('Repayment Timeliness') }}</th>
                                            <th>{{ translate('Score Changes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($customers as $customer)
                                            <tr>
                                                <td class="text-center">{{ $customer->user_id }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $customer->user->first_name ?? '-' }}
                                                        {{ $customer->user->last_name ?? '-' }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $custom->user->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td><span class="icon-saudi_riyal"></span>
                                                    {{ number_format($customer->credit_limit, 2) }}</td>
                                                <td><span class="icon-saudi_riyal"></span>
                                                    {{ number_format($customer->utilized_amount, 2) }}</td>
                                                <td>{{ $customer->repayment_rate }}% on-time</td>
                                                <td>
                                                    @if ($customer->score_change >= 0)
                                                        <span class="text-green-600 font-semibold">
                                                            +{{ number_format($customer->score_change, 2) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-red-600 font-semibold">
                                                            {{ number_format($customer->score_change, 2) }}%
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $customers])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
