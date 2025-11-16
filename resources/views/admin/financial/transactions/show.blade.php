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
                        {{ translate('Transaction Details') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <a class="text-gray-600 hover:text-primary" href="{{ route('financial.transactions.index') }}">
                            {{ translate('Financial Transactions') }}
                        </a>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900">#{{ $fTransaction->id }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('financial.transactions.index') }}">
                        {{ translate('Back to List') }}
                    </a>
                   
                </div>
            </div>
        </div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <!-- Transaction Information Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Transaction Information') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('UUID') }}</label>
                                <span class="text-gray-900 font-mono text-sm">{{ $fTransaction->uuid }}</span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Transaction Type') }}</label>
                                <span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block badge-info">
                                    {{ translate(ucfirst($fTransaction->transaction_type)) }}
                                </span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Amount') }}</label>
                                <span class="text-gray-900 font-medium text-lg">SR{{ number_format($fTransaction->amount, 2) }}</span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Status') }}</label>
                                <span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block 
                                    {{ $fTransaction->status == 'completed' ? 'badge-success' : 
                                       ($fTransaction->status == 'pending' ? 'badge-warning' : 
                                       ($fTransaction->status == 'failed' ? 'badge-danger' : 'badge-secondary')) }}">
                                    {{ translate(ucfirst($fTransaction->status)) }}
                                </span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Transaction Date') }}</label>
                                <span class="text-gray-900">{{ $fTransaction->transaction_date ? \Carbon\Carbon::parse($fTransaction->transaction_date)->format('Y-m-d H:i:s') : '-' }}</span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Created At') }}</label>
                                <span class="text-gray-900">{{ $fTransaction->created_at->format('Y-m-d H:i:s') }}</span>
                            </div>
                            
                            @if($fTransaction->reference_id)
                                <div class="flex flex-col gap-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Reference ID') }}</label>
                                    <span class="text-gray-900">#{{ $fTransaction->reference_id }}</span>
                                </div>
                            @endif
                            
                            @if($fTransaction->user)
                                <div class="flex flex-col gap-2">
                                    <label class="text-gray-600 text-sm">{{ translate('User') }}</label>
                                    <span class="text-gray-900">{{ $fTransaction->user->name }}</span>
                                </div>
                            @endif
                            
                            @if($fTransaction->customer)
                                <div class="flex flex-col gap-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Customer') }}</label>
                                    <span class="text-gray-900">{{ $fTransaction->customer->name }}</span>
                                </div>
                            @endif
                            
                            @if($fTransaction->supplier)
                                <div class="flex flex-col gap-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Supplier') }}</label>
                                    <span class="text-gray-900">{{ $fTransaction->supplier->name }}</span>
                                </div>
                            @endif
                            
                            @if($fTransaction->order)
                                <div class="flex flex-col gap-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Order') }}</label>
                                    <span class="text-gray-900">#{{ $fTransaction->order->id }}</span>
                                </div>
                            @endif
                            
                            @if($fTransaction->payment)
                                <div class="flex flex-col gap-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Payment') }}</label>
                                    <span class="text-gray-900">#{{ $fTransaction->payment->id }}</span>
                                </div>
                            @endif
                            
                            @if($fTransaction->notes)
                                <div class="flex flex-col gap-2 lg:col-span-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Notes') }}</label>
                                    <span class="text-gray-900">{{ $fTransaction->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Transaction Entries Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Journal Entries') }}</h3>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-light">{{ $entries->total() }} {{ translate('entries') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($entries->count() > 0)
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">{{ translate('Entry Date') }}</th>
                                            <th class="min-w-[150px]">{{ translate('Account') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Debit') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Credit') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Status') }}</th>
                                            <th class="min-w-[150px]">{{ translate('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalDebit = 0;
                                            $totalCredit = 0;
                                        @endphp
                                        @foreach($entries as $entry)
                                            @php
                                                $totalDebit += $entry->debit ?? 0;
                                                $totalCredit += $entry->credit ?? 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $entry->entry_date ? \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d') : '-' }}</td>
                                                <td>
                                                    @if($entry->account)
                                                        <span class="text-sm font-medium">{{ $entry->account->account_code }}</span>
                                                        <br>
                                                        <span class="text-xs text-gray-600">{{ $entry->account->account_name }}</span>
                                                    @else
                                                        <span class="text-gray-400">{{ $entry->account_name ?? '-' }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->debit)
                                                        <span class="text-red-600 font-medium">SR{{ number_format($entry->debit, 2) }}</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->credit)
                                                        <span class="text-green-600 font-medium">SR{{ number_format($entry->credit, 2) }}</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block {{ $entry->status == 'completed' ? 'badge-success' : ($entry->status == 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                                        {{ translate(ucfirst($entry->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($entry->notes)
                                                        <span class="text-sm">{{ Str::limit($entry->notes, 50) }}</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50">
                                            <td colspan="2" class="font-medium">{{ translate('Total') }}</td>
                                            <td class="font-medium text-red-600">SR{{ number_format($totalDebit, 2) }}</td>
                                            <td class="font-medium text-green-600">SR{{ number_format($totalCredit, 2) }}</td>
                                            <td colspan="2" class="font-medium">
                                                {{ translate('Balance') }}: 
                                                <span class="{{ $totalDebit - $totalCredit == 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    @if($totalDebit - $totalCredit == 0)
                                                        {{ translate('Balanced') }}
                                                    @else
                                                        SR {{ number_format(abs($totalDebit - $totalCredit), 2) }} 
                                                        {{ translate('Unbalanced') }}
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            @if($entries->hasPages())
                                <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                                    <div class="flex items-center gap-2">
                                        {{ translate('Showing') }} {{ $entries->firstItem() }} {{ translate('to') }} {{ $entries->lastItem() }} 
                                        {{ translate('of') }} {{ $entries->total() }} {{ translate('entries') }}
                                    </div>
                                    <div class="flex items-center gap-4">
                                        {{ $entries->links() }}
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="flex flex-col items-center gap-3 py-10">
                                <i class="ki-filled ki-file-sheet text-3xl text-gray-400"></i>
                                <span class="text-gray-600">{{ translate('No journal entries found for this transaction') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
