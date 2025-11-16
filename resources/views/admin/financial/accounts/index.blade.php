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
                        {{ translate('Financial Accounts') }}
                    </h1>
                </div>
             
                    <div class="flex items-center gap-2.5">
                        <a class="btn btn-sm btn-light" href="{{ route('financial.accounts.create') }}">
                            {{ translate('Create New Account') }}
                        </a>
                    </div>
                
            </div>
        </div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Financial Accounts') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input id="search_input" type="text" placeholder="{{ translate('Search accounts') }}"
                                        value="{{ request()->input('query', '') }}" />
                                </label>
                            </div>
                            @can('financial-accounts.create')
                                <div class="flex">
                                    <a class="btn btn-sm btn-primary" href="{{ route('financial.accounts.create') }}">
                                        <i class="ki-filled ki-plus"></i>
                                        {{ translate('Add Account') }}
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="financial_accounts_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px]">
                                                <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox">
                                            </th>
                                            <th class="min-w-[150px]">
                                                <span class="sort asc">
                                                    <span class="sort-label">{{ translate('Account Code') }}</span>
                                                    <span class="sort-icon"></span>
                                                </span>
                                            </th>
                                            <th class="min-w-[200px]">{{ translate('Account Name') }}</th>
                                            <th class="min-w-[150px]">{{ translate('Description') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Type') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Status') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accounts as $account)
                                            <tr>
                                                <td>
                                                    <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="font-medium text-gray-900">{{ $account->id }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-900">{{ $account->account_name }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-700">{{ Str::limit($account->description, 50) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-xs {{ $account->account_type2 == 1 ? 'badge-success' : 'badge-info' }}">
                                                        @if($account->account_type1==1 && $account->account_type2 == 1)
                                                        {{ translate('Assets') }}
                                                    @elseif($account->account_type1==1 && $account->account_type2 == 2)
                                                        {{ translate('Liabilities') }}
                                                    @elseif($account->account_type1 == 2 && $account->account_type2 == 1)
                                                        {{ translate('Expenses') }}
                                                    @else
                                                        {{ translate('Revenue') }}
                                                    @endif
                                                    </span>
                                                </td>
                                                
                                                <td>
                                                    <span class="badge badge-xs {{ $account->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                                        {{ translate(ucfirst($account->status)) }}
                                                    </span>
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
                                                                    <a class="menu-link" href="{{ route('financial.accounts.show', $account) }}">
                                                                        <span class="menu-icon">
                                                                            <i class="ki-filled ki-eye"></i>
                                                                        </span>
                                                                        <span class="menu-title">{{ translate('View') }}</span>
                                                                    </a>
                                                                </div>
                                                                @can('financial-accounts.edit')
                                                                    <div class="menu-item">
                                                                        <a class="menu-link" href="{{ route('financial.accounts.edit', $account) }}">
                                                                            <span class="menu-icon">
                                                                                <i class="ki-filled ki-notepad-edit"></i>
                                                                            </span>
                                                                            <span class="menu-title">{{ translate('Edit') }}</span>
                                                                        </a>
                                                                    </div>
                                                                @endcan
                                                                @can('financial-accounts.delete')
                                                                    <div class="menu-item">
                                                                        <form action="{{ route('financial.accounts.destroy', $account) }}" method="POST"
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="menu-link text-danger" onclick="return confirm('Are you sure you want to delete this account?')">
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
                                                <td colspan="8" class="text-center py-10">
                                                    <div class="flex flex-col items-center gap-3">
                                                        <i class="ki-filled ki-file-sheet text-3xl text-gray-400"></i>
                                                        <span class="text-gray-600">{{ translate('No financial accounts found') }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        @if($accounts->hasPages())
                            <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                                <div class="flex items-center gap-2">
                                    {{ translate('Showing') }} {{ $accounts->firstItem() }} {{ translate('to') }} {{ $accounts->lastItem() }} 
                                    {{ translate('of') }} {{ $accounts->total() }} {{ translate('entries') }}
                                </div>
                                <div class="flex items-center gap-4">
                                    {{ $accounts->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection