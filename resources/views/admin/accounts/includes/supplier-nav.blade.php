<nav class="flex border-b border-slate-200 gap-8 overflow-x-auto">

    {{-- Profile --}}
    <a href="{{ route('supplierProfile', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierProfile')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Profile') }}
    </a>

    {{-- Financial Analysis --}}
    <a href="{{ route('singleview.index', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('singleview.index')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Financial Analysis') }}
    </a>

    {{-- Shop Settings --}}
    <a href="{{ route('supplierShop', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierShop')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Shop Settings') }}
    </a>

    {{-- Transactions --}}
    <a href="{{ route('supplierTransactions', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierTransactions')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Transactions') }}
    </a>

    {{-- Orders --}}
    <a href="{{ route('supplierOrders', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierOrders')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Orders') }}
    </a>

    {{-- Payments --}}
    <a href="{{ route('supplierPayments', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierPayments')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Payments') }}
    </a>

    {{-- Finance --}}
    {{-- <a href="{{ route('supplierFinance', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierFinance')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Finance') }}
    </a> --}}

    {{-- Products --}}
    <a href="{{ route('supplierProducts', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierProducts')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Products') }}
    </a>

    {{-- Sales --}}
    <a href="{{ route('supplierSales', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierSales')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Sales') }}
    </a>

    {{-- Compliance --}}
    <a href="{{ route('supplierCompliance', ['id' => $merchant->user_id]) }}"
        class="pb-3 text-sm whitespace-nowrap
        {{ Route::is('supplierCompliance')
            ? 'text-blue-600 border-b-2 border-blue-600 font-bold'
            : 'text-slate-400 hover:text-slate-600 font-medium' }}">
        {{ translate('Compliance') }}
    </a>

</nav>
