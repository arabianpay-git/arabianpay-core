@extends('layouts.base')

@section('content')
    <div class="container py-6">
        <div class="m-5 mt-0">
            <a href="{{ route('financial.dashboard') }}" class="btn btn-light btn-sm">&larr; Back to Dashboard</a>
        </div>

        <div class="card m-5">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h3 class="card-title">Ledger - Account #{{ $account->id }}: {{ $account->account_name }}</h3>
                    <div class="text-2sm text-gray-600 mt-1">
                        Natural balance: {{ $isDebitNormal ? 'Debit' : 'Credit' }}
                    </div>
                </div>

            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                    <div class="card">
                        <div class="p-4 ">
                            <div class="text-gray-600 text-2sm">Opening balance (before {{ $date_from }})</div>
                            <div class="text-lg font-semibold"><span
                                    class="icon-saudi_riyal"></span>{{ round($openingBalance, 2) }}</div>
                        </div>
                        <div class="p-4 ">
                            <div class="text-gray-600 text-2sm">Period totals ({{ $date_from }} to {{ $date_to }})
                            </div>
                            <div class="flex gap-6 mt-1">
                                @if ($isDebitNormal)
                                    <div>Debit: <span class="font-semibold text-success"><span
                                                class="icon-saudi_riyal"></span>{{ round($totals->sum_debit ?? 0, 2) }}</span>
                                    </div>
                                    <div>Credit: <span class="font-semibold text-danger"><span
                                                class="icon-saudi_riyal"></span>{{ round($totals->sum_credit ?? 0, 2) }}</span>
                                    </div>
                                @else
                                    <div>Debit: <span class="font-semibold text-danger"><span
                                                class="icon-saudi_riyal"></span>{{ round($totals->sum_debit ?? 0, 2) }}</span>
                                    </div>
                                    <div>Credit: <span class="font-semibold text-success"><span
                                                class="icon-saudi_riyal"></span>{{ round($totals->sum_credit ?? 0, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="p-4 ">
                            <div class="text-gray-600 text-2sm">Closing balance (page)</div>
                            <div class="text-lg font-semibold"><span
                                    class="icon-saudi_riyal"></span>{{ round($closingBalance, 2) }}</div>
                        </div>
                    </div>
                    <form method="get" action="{{ route('financial.accounts.ledger', $account->id) }}"
                        class="grid grid-cols-1 md:grid-cols-3 gap-3 card p-4">
                        <div class="grid grid-cols-2">
                            <div>
                                <label class="form-label">From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $date_from }}">
                            </div>
                            <div>
                                <label class="form-label">To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $date_to }}">
                            </div>
                        </div>
                        @if ($account->id == 1203)
                            {{-- Accounts Receivable - Customer Filter --}}
                            <div>
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-select">
                                    <option value="">All Customers</option>
                                    @foreach ($customers ?? [] as $customer)
                                        <option value="{{ $customer->id }}"
                                            {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->business_name }} - {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($account->id == 2400)
                            {{-- Accounts Payable - Supplier Filter --}}
                            <div>
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">All Suppliers</option>
                                    @foreach ($suppliers ?? [] as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->business_name }} - {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary flex-end">Apply</button>
                        </div>
                    </form>
                </div>

                <div class="scrollable-x-auto">
                    <table class="table table-auto table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[130px]">Date</th>
                                <th class="min-w-[140px]">Reference</th>
                                <th class="min-w-[200px]">Description</th>
                                <th class="min-w-[110px] text-right">Debit</th>
                                <th class="min-w-[110px] text-right">Credit</th>
                                <th class="min-w-[140px] text-right">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-gray-50">
                                <td colspan="5" class="text-gray-700">Opening balance</td>
                                @if ($openingBalance > 0)
                                    <td class="text-right font-medium"><span
                                            class="icon-saudi_riyal"></span>{{ $openingBalance }}</td>
                                @else
                                    <td class="text-right font-medium">-</td>
                                @endif
                            </tr>
                            @forelse($entries as $row)
                                <tr>
                                    <td class="w-12">
                                        {{ $row->entry_date ? \Carbon\Carbon::parse($row->entry_date)->format(dateFormat()) : '-' }}
                                    </td>
                                    <td>
                                        @if ($row->transaction_id)
                                            TX-{{ str_pad($row->transaction_id, 6, '0', STR_PAD_LEFT) }}
                                        @elseif(!empty($row->reference_id))
                                            {{ $row->reference_id }}
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($row->notes))
                                            {{ \Illuminate\Support\Str::limit($row->notes, 80) }}
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>

                                    <!-- if value =0 then '-' -->
                                    @if ($row->debit > 0)
                                        <td class="text-right"><span
                                                class="icon-saudi_riyal"></span>{{ $row->debit ? ($row->debit == 0 ? 0 : $row->debit) : ' ' }}
                                        </td>
                                    @else
                                        <td class="text-right"> </td>
                                    @endif
                                    @if ($row->credit > 0)
                                        <td class="text-right"><span
                                                class="icon-saudi_riyal"></span>{{ $row->credit ? ($row->credit == 0 ? 0 : $row->credit) : ' ' }}
                                        </td>
                                    @else
                                        <td class="text-right"> </td>
                                    @endif
                                    <td class="text-right font-semibold"><span
                                            class="icon-saudi_riyal"></span>{{ round($row->running_balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-6 text-gray-600">No entries in this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($entries->hasPages())
                <div
                    class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                    <div class="flex items-center gap-2">
                        Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} of {{ $entries->total() }}
                        entries
                    </div>
                    <div class="flex items-center gap-4">
                        {{ $entries->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
