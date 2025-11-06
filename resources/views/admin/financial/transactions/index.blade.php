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
                        {{ translate('Financial Transactions') }}
                    </h1>
                </div>
                @can('financial-transactions.create')
                    <div class="flex items-center gap-2.5">
                        <a class="btn btn-sm btn-light" href="{{ route('financial.transactions.create') }}">
                            {{ translate('Create New Transaction') }}
                        </a>
                    </div>
                @endcan
            </div>
        </div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Financial Transactions') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input id="search_input" type="text" placeholder="{{ translate('Search transactions') }}"
                                        value="{{ request()->input('query', '') }}" />
                                </label>
                            </div>
                            @can('financial-transactions.create')
                                <div class="flex">
                                    <a class="btn btn-sm btn-primary" href="{{ route('financial.transactions.create') }}">
                                        <i class="ki-filled ki-plus"></i>
                                        {{ translate('Add Transaction') }}
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="financial_transactions_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px]">
                                                <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox">
                                            </th>
                                            <th class="min-w-[80px]">{{ translate('Date') }}</th>
                                            <th class="min-w-[250px]">{{ translate('Description') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Amount') }}</th>
                                            <th class="min-w-[120px]">{{ translate('User') }}</th>
                                            <th class="min-w-[70px]">{{ translate('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $transaction)
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="{{ $transaction->id }}">
                                                </td>
                                                <td>
                                                    <span class="text-gray-900">{{ $transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') : '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-700 font-mono text-xs">{{ Str::limit($transaction->notes, 200) }}</span>
                                                </td>
                                                
                                                <td>
                                                    <span class="text-gray-900 font-medium">{{ number_format($transaction->amount, 2) }} SR</span>
                                                </td>
                                               
                                               
                                                <td>
                                                    @if($transaction->user)
                                                        <span class="text-gray-700">{{ $transaction->user->first_name }} {{ $transaction->user->last_name }}</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="menu" data-menu="true">
                                                        <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end"
                                                            data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
                                                            <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                                <i class="ki-filled ki-dots-vertical"></i>
                                                            </button>
                                                            <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                                <div class="menu-item">
                                                                    <button class="menu-link" onclick="showTransactionModal('{{ $transaction->id }}')">
                                                                        <span class="menu-icon">
                                                                            <i class="ki-filled ki-eye"></i>
                                                                        </span>
                                                                        <span class="menu-title">{{ translate('View') }}</span>
                                                                    </button>
                                                                </div>
                                                                @can('financial-transactions.edit')
                                                                    <div class="menu-item">
                                                                        <a class="menu-link" href="{{ route('financial.transactions.edit', $transaction) }}">
                                                                            <span class="menu-icon">
                                                                                <i class="ki-filled ki-notepad-edit"></i>
                                                                            </span>
                                                                            <span class="menu-title">{{ translate('Edit') }}</span>
                                                                        </a>
                                                                    </div>
                                                                @endcan
                                                                @can('financial-transactions.delete')
                                                                    <div class="menu-item">
                                                                        <form action="{{ route('financial.transactions.destroy', $transaction) }}" method="POST">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="menu-link text-danger" onclick="return confirm('Are you sure you want to delete this transaction?')">
                                                                                <span class="menu-icon">
                                                                                    <i class="ki-filled ki-trash"></i>
                                                                                </span>
                                                                                <span class="menu-title">{{ translate('Delete') }}</span>
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                @endcan
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-10">
                                                    <div class="flex flex-col items-center gap-3">
                                                        <i class="ki-filled ki-file-sheet text-3xl text-gray-400"></i>
                                                        <span class="text-gray-600">{{ translate('No financial transactions found') }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        @if($transactions->hasPages())
                            <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                                <div class="flex items-center gap-2">
                                    {{ translate('Showing') }} {{ $transactions->firstItem() }} {{ translate('to') }} {{ $transactions->lastItem() }} 
                                    {{ translate('of') }} {{ $transactions->total() }} {{ translate('entries') }}
                                </div>
                                <div class="flex items-center gap-4">
                                    {{ $transactions->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>

    @include('admin.financial.transactions.modal')
@endsection