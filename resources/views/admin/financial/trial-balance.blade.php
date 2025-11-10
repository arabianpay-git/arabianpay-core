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
                        {{ translate('Trial Balance') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <span>{{ translate('Financial statement showing debits and credits for all accounts') }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('financial.trial-balance.export', ['date' => $balanceDate]) }}">
                        <i class="ki-filled ki-file-down"></i>
                        {{ translate('Export CSV') }}
                    </a>
                    <a class="btn btn-sm btn-primary" href="{{ route('financial.dashboard') }}">
                        <i class="ki-filled ki-chart-line"></i>
                        {{ translate('Dashboard') }}
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">{{ translate('Filter Options') }}</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('financial.trial-balance') }}" class="flex gap-5 items-end">
                        <div class="flex flex-col gap-2">
                            <label class="form-label text-sm font-medium text-gray-900">
                                {{ translate('As of Date') }}
                            </label>
                            <input type="date" name="date" value="{{ $balanceDate }}" 
                                   class="input input-sm" 
                                   max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-magnifier"></i>
                                {{ translate('Generate') }}
                            </button>
                            <a href="{{ route('financial.trial-balance') }}" class="btn btn-sm btn-light">
                                {{ translate('Reset') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Trial Balance Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ translate('Trial Balance') }} - {{ \Carbon\Carbon::parse($balanceDate)->format('F d, Y') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        @php
                            $isBalanced = abs($totalDebits - $totalCredits) < 0.01;
                        @endphp
                        @if($isBalanced)
                            <span class="badge badge-success badge-sm">
                                <i class="ki-filled ki-check-circle"></i>
                                {{ translate('Balanced') }}
                            </span>
                        @else
                            <span class="badge badge-danger badge-sm">
                                <i class="ki-filled ki-cross-circle"></i>
                                {{ translate('Unbalanced') }}
                            </span>
                        @endif
                        <span class="text-sm text-gray-600">
                            {{ $trialBalance->count() }} {{ translate('Accounts') }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($trialBalance->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-rounded table-striped border gs-7">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                                        <th class="min-w-150px">{{ translate('Account Code') }}</th>
                                        <th class="min-w-250px">{{ translate('Account Name') }}</th>
                                        <th class="min-w-100px">{{ translate('Type') }}</th>
                                        <th class="min-w-120px text-end">{{ translate('Debit') }}</th>
                                        <th class="min-w-120px text-end">{{ translate('Credit') }}</th>
                                        <th class="min-w-120px text-end">{{ translate('Balance') }}</th>
                                       
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trialBalance as $account)
                                        <tr>
                                            <td>
                                                <div class="font-medium text-gray-900">{{ $account->id }}</div>
                                            </td>
                                            <td>
                                                <div class="font-semibold text-gray-900">{{ $account->account_name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    @if($account->account_type1 == 1)
                                                        {{ translate('Budget Account') }}
                                                    @else
                                                        {{ translate('Non-Budget Account') }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                              
                                                    @if($account->account_type1==1 && $account->account_type2 == 1)
                                                      <span class="badge badge-sm badge-primary">
                                                        {{ translate('Assets') }}
                                                     </span>
                                                    @elseif($account->account_type1==1 && $account->account_type2 == 2)
                                                        <span class="badge badge-sm badge-info">
                                                            {{ translate('Liabilities') }}
                                                        </span>
                                                    @elseif($account->account_type1 == 2 && $account->account_type2 == 1)
                                                        <span class="badge badge-sm badge-danger">
                                                            {{ translate('Expenses') }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-sm badge-success">
                                                            {{ translate('Revenue') }}
                                                        </span>
                                                    @endif
                                               
                                            </td>
                                            <td class="text-end">
                                                @if($account->total_debit > 0)
                                                    <span class="font-semibold text-gray-900">
                                                        {{ number_format($account->total_debit, 2) }} SR
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($account->total_credit > 0)
                                                    <span class="font-semibold text-gray-900">
                                                        {{ number_format($account->total_credit, 2) }} SR
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="font-bold {{ $account->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format(abs($account->balance), 2) }} SR
                                                </span>
                                                @if($account->balance < 0)
                                                    <div class="text-xs text-danger">({{ translate('Negative') }})</div>
                                                @endif
                                            </td>
                                          
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-top-2 bg-gray-50">
                                        <td colspan="3" class="font-bold text-gray-900 py-4">
                                            {{ translate('TOTALS') }}
                                        </td>
                                        <td class="text-end font-bold text-gray-900 py-4">
                                            {{ number_format($totalDebits, 2) }} SR
                                        </td>
                                        <td class="text-end font-bold text-gray-900 py-4">
                                            {{ number_format($totalCredits, 2) }} SR
                                        </td>
                                        <td class="text-end py-4">
                                            @php
                                                $difference = abs($totalDebits - $totalCredits);
                                                $isBalanced = $difference < 0.01;
                                            @endphp
                                            <span class="badge badge-{{ $isBalanced ? 'success' : 'danger' }} badge-sm">
                                                @if($isBalanced)
                                                    {{ translate('Balanced') }}
                                                @else
                                                    {{ translate('Difference') }}: {{ number_format($difference, 2) }} SR
                                                @endif
                                            </span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-20">
                            <i class="ki-filled ki-file-sheet text-5xl text-gray-300 mb-5"></i>
                            <h3 class="text-gray-600 text-lg font-medium mb-2">{{ translate('No Accounts Found') }}</h3>
                            <p class="text-gray-500 mb-6">{{ translate('No accounts with transactions found for the selected date.') }}</p>
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('financial.accounts.create') }}" class="btn btn-sm btn-primary">
                                    <i class="ki-filled ki-plus"></i>
                                    {{ translate('Create Account') }}
                                </a>
                                <a href="{{ route('financial.transactions.create') }}" class="btn btn-sm btn-light">
                                    <i class="ki-filled ki-note-2"></i>
                                    {{ translate('Create Transaction') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($trialBalance->count() > 0)
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-5 mt-5">
                    <!-- Total Accounts -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="text-3xl font-bold text-gray-900 mb-1">
                                {{ $trialBalance->count() }}
                            </div>
                            <div class="text-sm text-gray-600">{{ translate('Total Accounts') }}</div>
                        </div>
                    </div>
                    
                    <!-- Active Accounts -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="text-3xl font-bold text-success mb-1">
                                {{ $trialBalance->filter(function($account) { return $account->total_debit > 0 || $account->total_credit > 0; })->count() }}
                            </div>
                            <div class="text-sm text-gray-600">{{ translate('Active Accounts') }}</div>
                        </div>
                    </div>
                    
                    <!-- Total Debits -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="text-2xl font-bold text-primary mb-1">
                                {{ number_format($totalDebits, 2) }} SR
                            </div>
                            <div class="text-sm text-gray-600">{{ translate('Total Debits') }}</div>
                        </div>
                    </div>
                    
                    <!-- Total Credits -->
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="text-2xl font-bold text-info mb-1">
                                {{ number_format($totalCredits, 2) }} SR
                            </div>
                            <div class="text-sm text-gray-600">{{ translate('Total Credits') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <!-- End of Container -->
    </main>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when date changes
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Add tooltips for balance validation
    const balanceStatus = document.querySelector('.badge');
    if (balanceStatus) {
        balanceStatus.setAttribute('data-tooltip', 'Trial balance should have equal debits and credits');
    }
});
</script>
@endsection