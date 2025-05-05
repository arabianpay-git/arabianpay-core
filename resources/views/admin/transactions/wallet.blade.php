@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>

    <!-- Container -->
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Wallet History
                </h1>
            </div>
        </div>
    </div>

    <!-- Container -->
    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        Wallet History
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#wallet_history_table" placeholder="Search transactions" type="text" value="" />
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div data-datatable="true" data-datatable-state-save="false" id="wallet_history_table">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th class="w-[60px] text-center">No</th>
                                        <th class="text-left">Order</th>
                                        <th class="text-left">Amount</th>
                                        <th class="text-left">Balance After</th>
                                        <th class="text-left">Transaction Type</th>
                                        <th class="text-left">Status</th>
                                        <th class="text-left">Created At</th>
                                        <th class="text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($wallets as $index => $wallet)
                                    <tr>
                                        <td class="text-center">{{ number_format($index + 1) }}</td>

                                        <td>
                                            <div class="whitespace-nowrap">
                                                Order Invoice: {{ $wallet->order?->invoice_number ?? '—' }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="whitespace-nowrap text-sm">
                                                <span class="icon-saudi_riyal"></span> {{ number_format($wallet->amount, 2) }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="whitespace-nowrap text-sm">
                                                <span class="icon-saudi_riyal"></span> {{ number_format($wallet->balance_after, 2) }}
                                            </div>
                                        </td>

                                        <td>
                                            {{ ucwords(str_replace('_', ' ', $wallet->transaction_type)) }}
                                        </td>                                        

                                        <td>
                                            <span class="badge badge-sm badge-outline 
                                                @if ($wallet->status == 'active') badge-success
                                                @elseif ($wallet->status == 'pending') badge-warning
                                                @elseif ($wallet->status == 'closed') badge-danger
                                                @else badge-secondary @endif">
                                                {{ ucfirst($wallet->status) }}
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap">
                                            {{ $wallet->created_at ? $wallet->created_at->format('d M Y') : 'N/A' }}
                                        </td>

                                        <td>
                                            @if($wallet->order)
                                            <div class="flex gap-1 justify-center">
                                                <a class="btn btn-sm btn-icon btn-clear btn-primary" href="{{ route('merchant.invoice.generate', ['order' => $wallet->order->id]) }}">
                                                    <i class="ki-filled ki-exit-down"> </i>
                                                </a>
                                            </div>
                                            @else
                                                <span class="text-gray-400 text-sm">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $wallets])
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
