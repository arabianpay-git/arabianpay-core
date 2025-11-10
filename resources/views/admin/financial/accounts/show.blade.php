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
                        {{ translate('Financial Account Details') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <a class="text-gray-600 hover:text-primary" href="{{ route('financial.accounts.index') }}">
                            {{ translate('Financial Accounts') }}
                        </a>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900">{{ $fAccount->id}}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('financial.accounts.index') }}">
                        {{ translate('Back to List') }}
                    </a>
                    @can('financial-accounts.edit')
                        <a class="btn btn-sm btn-primary" href="{{ route('financial.accounts.edit', $fAccount) }}">
                            {{ translate('Edit Account') }}
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <!-- Account Information Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Account Information') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Account Code') }}</label>
                                <span class="text-gray-900 font-medium">{{ $fAccount->account_code }}</span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Account Name') }}</label>
                                <span class="text-gray-900 font-medium">{{ $fAccount->account_name }}</span>
                            </div>
                            
                            <div class="flex gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Account Category') }}</label>
                                <span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block {{ $fAccount->account_type1 == 1 ? 'badge-primary' : 'badge-secondary' }}">
                                    {{ $fAccount->account_type1 == 1 ? translate('Budget') : translate('Non-Budget') }}
                                </span>
                            </div>
                            
                            <div class="flex gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Account Type') }}</label>
                                <span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block {{ $fAccount->account_type2 == 1 ? 'badge-success' : 'badge-info' }}">
                                    {{ $fAccount->account_type2 == 1 ? translate('Debit') : translate('Credit') }}
                                </span>
                            </div>
                            
                            <div class="flex  gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Status') }}</label>
                                <span class="badge {{ $fAccount->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                    {{ translate(ucfirst($fAccount->status)) }}
                                </span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-gray-600 text-sm">{{ translate('Created At') }}</label>
                                <span class="text-gray-900">{{ $fAccount->created_at ? \Carbon\Carbon::parse($fAccount->created_at)->format('Y-m-d H:i:s') : '-' }}</span>
                            </div>
                            
                            @if($fAccount->description)
                                <div class="flex flex-col gap-2 lg:col-span-2">
                                    <label class="text-gray-600 text-sm">{{ translate('Description') }}</label>
                                    <span class="text-gray-900">{{ $fAccount->description }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Account Entries Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Account Entries') }}</h3>
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
                                            <th class="min-w-[150px]">{{ translate('Reference') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Debit') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Credit') }}</th>
                                            <th class="min-w-[100px]">{{ translate('Status') }}</th>
                                            <th class="min-w-[100px]">{{ translate('User') }}</th>
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
                                                    @if($entry->reference_id)
                                                        <span class="text-sm text-gray-600">#{{ $entry->reference_id }}</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->debit)
                                                        <span class="text-red-600 font-medium">{{ number_format($entry->debit, 2) }} SR</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->credit)
                                                        <span class="text-green-600 font-medium">{{ number_format($entry->credit, 2) }} SR</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-xs {{ $entry->status == 'completed' ? 'badge-success' : ($entry->status == 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                                        {{ translate(ucfirst($entry->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($entry->user)
                                                        <span class="text-sm">{{ $entry->user->name }}</span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
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
                                            <td class="font-medium text-red-600">{{ number_format($totalDebit, 2) }} SR</td>
                                            <td class="font-medium text-green-600">{{ number_format($totalCredit, 2) }} SR</td>
                                            <td colspan="3" class="font-medium">
                                                {{ translate('Balance') }}: 
                                                <span class="{{ $totalDebit - $totalCredit >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ number_format(abs($totalDebit - $totalCredit), 2) }} SR
                                                    {{ $totalDebit - $totalCredit >= 0 ? '(Dr)' : '(Cr)' }}
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
                                <span class="text-gray-600">{{ translate('No entries found for this account') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection