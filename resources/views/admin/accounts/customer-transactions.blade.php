@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>
        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            <!-- Container -->
            @include('admin.accounts.includes.customer')
            <!-- End of Container -->
        </div>
        <!-- Container -->
        @include('admin.accounts.includes.customer-header')
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <!-- begin: grid -->
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Transactions') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table" placeholder="Search users"
                                        type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th class="text-left">{{ translate('User') }}</th>
                                            <th class="text-left">{{ translate('Order ID') }}</th>
                                            <th class="text-left">{{ translate('Loan Amount') }}</th>
                                            <th class="text-left">{{ translate('Loan Period') }}</th>
                                            <th class="text-left">{{ translate('Status') }}</th>
                                            <th class="text-left">{{ translate('Settlement Status') }}</th>
                                            <th class="text-left">{{ translate('Created At') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($transactions as $index => $item)
                                            <tr>
                                                <td class="text-center">{{ number_format($index + 1) }}</td>

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
                                                    <div class="whitespace-nowrap text-sm">
                                                        <div>
                                                            <span>{{ translate('Amount') }}:</span>
                                                            <span class="icon-saudi_riyal"></span>
                                                            {{ number_format($item->order->grand_total, 2) }}
                                                        </div>
                                                        <div>
                                                            <span>{{ translate('City') }}:</span> <small
                                                                class="text-xs text-gray-500">{{ $item->order->shipping_city ?? '—' }}</small>
                                                        </div>
                                                        <div>
                                                            <span>{{ translate('Status') }}:</span>
                                                            <small
                                                                class="badge badge-sm badge-outline 
                                                        @if ($item->order->general_status == 'processing') badge-info
                                                        @elseif ($item->order->general_status == 'completed') badge-success
                                                        @elseif ($item->order->general_status == 'cancelled') badge-warning
                                                        @elseif ($item->order->general_status == 'failed') badge-danger
                                                        @else badge-secondary @endif">
                                                                {{ ucfirst($item->order->general_status) }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>


                                                <td class="whitespace-nowrap">
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($item->loan_amount, 2) }}
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    @if ($item->loan_start_date && $item->loan_end_date)
                                                        <div>
                                                            <strong>{{ translate('Start Date') }}:</strong>
                                                            {{ \Carbon\Carbon::parse($item->loan_start_date)->format(dateFormat()) }}
                                                        </div>
                                                        <div>
                                                            <strong>{{ translate('End Date') }}:</strong>
                                                            {{ \Carbon\Carbon::parse($item->loan_end_date)->format(dateFormat()) }}
                                                        </div>
                                                    @else
                                                        {{ translate('N/A') }}
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    @php
                                                        $statusColors = [
                                                            'pending' => 'badge-warning',
                                                            'due' => 'badge-info',
                                                            'late' => 'badge-danger',
                                                            'paid' => 'badge-success',
                                                            'failed' => 'badge-secondary',
                                                        ];
                                                        $badgeColor =
                                                            $statusColors[$item->payment_status] ?? 'bg-gray-400';
                                                    @endphp
                                                    <span class="badge badge-sm badge-outline {{ $badgeColor }}">
                                                        {{ ucfirst($item->payment_status) }}
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    @php
                                                        $settlementStatusColors = [
                                                            'settled' => 'badge-success',
                                                            'pending' => 'badge-warning',
                                                            'in_progress' => 'badge-info',
                                                            'failed' => 'badge-danger',
                                                        ];
                                                        $settlementBadgeColor =
                                                            $settlementStatusColors[$item->settlement_status] ??
                                                            'bg-gray-400';
                                                    @endphp
                                                    <span class="badge badge-sm badge-outline {{ $settlementBadgeColor }}">
                                                        {{ ucfirst($item->settlement_status) }}
                                                    </span>
                                                </td>

                                                <td class="whitespace-nowrap">
                                                    {{ $item->created_at ? $item->created_at->format(dateFormat()) : 'N/A' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $transactions])
                        </div>
                    </div>
                </div>
            </div>
            <!-- end: grid -->
        </div>
        <!-- End of Container -->
    </main>
@endsection
