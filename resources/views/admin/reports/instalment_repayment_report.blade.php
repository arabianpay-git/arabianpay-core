@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .order-link {
                color: #2563eb;
                /* Tailwind blue-600 */
                text-decoration: underline;
                font-weight: 600;
                transition: color 0.2s ease;
            }

            .order-link:hover {
                color: #1d4ed8;
                /* Tailwind blue-700 */
                text-decoration: underline;
            }
        </style>
    @endpush
    <main class="grow content pt-5" id="content" role="content">
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Instalment Repayment Report') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- Filter -->
        @include('admin.reports.includes.customer-filter', [
            'filterHeading' => 'Instalment Repayment Filter',
        ])

        <!-- Table -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Instalment Repayment Records') }}
                        </h3>
                    </div>

                    <div class="card-body">
                        <div id="instalment_repayment_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border w-full">
                                    <thead>
                                        <tr>
                                            <th>{{ translate('Merchant') }}</th>
                                            <th class="text-center">{{ translate('Order ID') }}</th>
                                            <th colspan="4" class="text-center">{{ translate('Instalments') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($instalments as $orderId => $groupedInstalments)
                                            @php
                                                $user = $groupedInstalments->first()->user;
                                            @endphp
                                            <tr>
                                                <!-- Merchant -->
                                                <td class="align-top w-1/4">
                                                    <div class="whitespace-nowrap text-sm">
                                                        <strong>ID:</strong> {{ $user->id ?? '-' }}<br>
                                                        <strong>Name:</strong>
                                                        {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '-' }}<br>
                                                        <span class="text-secondary text-xs">—
                                                            {{ $user->business_name ?? '—' }}</span>
                                                    </div>
                                                </td>

                                                <!-- Order ID -->

                                                <td class="align-top font-medium w-60">
                                                    <div class="text-sm leading-snug">
                                                        <a href="{{ route('orders.details', $orderId) }}"
                                                            class="order-link font-semibold">
                                                            #{{ $orderId }}
                                                        </a><br>
                                                        <span class="text-xs text-secondary">
                                                            {{ $groupedInstalments->first()->created_at->format('Y-m-d') }}
                                                        </span>
                                                        @if ($groupedInstalments->first()->order && $groupedInstalments->first()->order->products_count ?? false)
                                                            <br>
                                                            <span class="text-xs text-gray-400">
                                                                {{ $groupedInstalments->first()->order->products_count }}
                                                                products
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <!-- Instalments -->
                                                <td colspan="4">
                                                    <div
                                                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                                                        @foreach ($groupedInstalments as $instalment)
                                                            <div
                                                                class="border border-gray-200 rounded-md p-2 bg-white text-xs">
                                                                <div class="font-semibold text-gray-700">
                                                                    #{{ $instalment->id }} •
                                                                    {{ $instalment->due_date->format('Y-m-d') }}
                                                                </div>
                                                                <div class="text-gray-600">
                                                                    <span class="icon-saudi_riyal"></span>
                                                                    {{ number_format($instalment->instalment_amount, 2) }}
                                                                    •
                                                                    {{ $instalment->updated_at ? $instalment->updated_at->format('Y-m-d') : '-' }}
                                                                </div>
                                                                <div>
                                                                    @switch($instalment->payment_status)
                                                                        @case('paid')
                                                                            <span
                                                                                class="text-success font-medium">{{ translate('Paid') }}</span>
                                                                        @break

                                                                        @case('late')
                                                                            <span
                                                                                class="text-danger font-medium">{{ translate('Late') }}</span>
                                                                        @break

                                                                        @case('pending')
                                                                            <span
                                                                                class="text-warning font-medium">{{ translate('Pending') }}</span>
                                                                        @break

                                                                        @default
                                                                            <span
                                                                                class="text-secondary">{{ ucfirst($instalment->payment_status) }}</span>
                                                                    @endswitch
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-6 text-secondary">
                                                        {{ translate('No instalment records found.') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                @if ($instalments instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    @include('layouts.includes.table-pagination', [
                                        'paginator' => $instalments,
                                    ])
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    @endsection
