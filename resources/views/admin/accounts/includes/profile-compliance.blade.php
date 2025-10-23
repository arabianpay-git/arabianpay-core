<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            {{ translate('Company Policy') }}
        </h3>
    </div>
    <div class="card-body">
        <table class="table-auto">
            <tbody>
                <tr>
                    <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                        {{ translate('Number of days for return') }}
                    </td>
                    <td class="text-sm text-gray-900 pb-3">
                        {{ $merchant->return_day_count ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                        {{ translate('Number of days for exchange') }}
                    </td>
                    <td class="text-sm text-gray-900 pb-3">
                        {{ $merchant->exchange_day_count ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                        {{ translate('Number of days for cancel') }}
                    </td>
                    <td class="text-sm text-gray-900 pb-3">
                        {{ $merchant->cancel_day_count ?? '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
