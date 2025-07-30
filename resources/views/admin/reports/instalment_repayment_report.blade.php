@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Header Container -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Instalment Repayment Report') }}
                    </h1>
                </div>
            </div>
        </div>

        @include('admin.reports.includes.customer-filter', [
            'filterHeading' => 'Instalment Repayment Filter',
        ])

        <!-- Table Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Instalment Repayment Records') }}
                        </h3>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="instalment_repayment_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th>{{ translate('Merchant ID') }}</th>
                                            <th class="text-center">{{ translate('Instalment ID') }}</th>
                                            <th>{{ translate('Due Date') }}</th>
                                            <th>{{ translate('Amount') }}</th>
                                            <th>{{ translate('Paid Date') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($instalments as $instalment)
                                            <tr>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        <strong>ID:</strong> {{ $instalment->user->id ?? '-' }}<br>
                                                        <strong>Name:</strong>
                                                        {{ trim(($instalment->user->first_name ?? '') . ' ' . ($instalment->user->last_name ?? '')) ?: '-' }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            &mdash; {{ $instalment->user->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td class="text-center">{{ $instalment->id }}</td>
                                                <td>{{ $instalment->due_date->format('Y-m-d') }}</td>
                                                <td><span class="icon-saudi_riyal"></span>
                                                    {{ number_format($instalment->instalment_amount, 2) }}</td>
                                                <td>{{ $instalment->updated_at ? $instalment->updated_at->format('Y-m-d') : '-' }}
                                                </td>
                                                <td>
                                                    @switch($instalment->payment_status)
                                                        @case('paid')
                                                            <span
                                                                class="badge badge-outline badge-success">{{ translate('Paid') }}</span>
                                                        @break

                                                        @case('late')
                                                            <span
                                                                class="badge badge-outline badge-danger">{{ translate('Late') }}</span>
                                                        @break

                                                        @case('pending')
                                                            <span
                                                                class="badge badge-outline badge-warning">{{ translate('Pending') }}</span>
                                                        @break

                                                        @default
                                                            <span
                                                                class="badge badge-outline badge-secondary">{{ ucfirst($instalment->payment_status) }}</span>
                                                    @endswitch
                                                </td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $instalments])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
