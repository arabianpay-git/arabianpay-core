@push('styles')
    <style>
        .select {
            height: 32px !important;
            width: 8rem;
            line-height: 1.25;
        }
    </style>
@endpush

<div class="container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="card card-grid min-w-full">
            <div class="card-header flex-wrap gap-4 py-4">
                <div>
                    <h3 class="card-title font-semibold text-base text-gray-900">
                        {{ translate('Installments') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ translate('Manage payment installments and schedules') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 lg:gap-4">
                    <!-- Status Filter -->
                    <div class="flex">
                        <select id="filter-status" name="status" class="select">
                            <option value="">All Status</option>
                            <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming
                            </option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue
                            </option>
                            <option value="promise" {{ request('status') == 'promise' ? 'selected' : '' }}>Promise
                            </option>
                        </select>
                    </div>

                    <!-- DPD Filter -->
                    <div class="flex">
                        <select id="filter-dpd" name="dpd" class="select">
                            <option value="">All DPD</option>
                            <option value="0-30" {{ request('dpd') == '0-30' ? 'selected' : '' }}>0-30 DPD</option>
                            <option value="31-60" {{ request('dpd') == '31-60' ? 'selected' : '' }}>31-60 DPD</option>
                            <option value="61-90" {{ request('dpd') == '61-90' ? 'selected' : '' }}>61-90 DPD</option>
                            <option value="90+" {{ request('dpd') == '90+' ? 'selected' : '' }}>90+ DPD</option>
                        </select>
                    </div>

                    <!-- Channel Filter -->
                    <div class="flex">
                        <select id="filter-channel" name="channel" class="select">
                            <option value="">All Channels</option>
                            <option value="card" {{ request('channel') == 'card' ? 'selected' : '' }}>Card</option>
                            <option value="transfer" {{ request('channel') == 'transfer' ? 'selected' : '' }}>Transfer
                            </option>
                        </select>
                    </div>

                    <!-- Filter Buttons (JS handles building the URL) -->
                    <div class="flex items-center gap-2">
                        <button class="btn btn-sm btn-primary" id="installments-filter-btn" type="button">
                            <i class="ki-filled ki-filter-search"> </i>
                            {{ translate('Filter') }}
                        </button>
                        <button class="btn btn-sm btn-light" id="installments-clear-btn" type="button">
                            <i class="ki-filled ki-arrows-circle"> </i>
                            {{ translate('Clear') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div data-datatable="true" data-datatable-city-save="false" id="installments_table">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border" data-datatable-table="true">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="w-[60px] text-center py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('No') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Merchant') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Order') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Due Date') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Amount') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Status') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('DPD') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Channel') }}
                                    </th>
                                    <th class="py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ translate('Action') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($installments as $index => $installment)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="text-center py-3 text-sm text-gray-900">{{ $index + 1 }}</td>

                                        <td class="py-3 text-sm font-medium text-gray-900">
                                            <div class="whitespace-nowrap">
                                                <a href="{{ route('customerProfile', ['id' => $installment['order_id']]) }}"
                                                    class="underline">
                                                    {{ $installment['merchant'] }}
                                                </a>
                                                <br>
                                                <small class="text-gray-500">—
                                                    {{ $installment['merchant_business'] }}</small>
                                            </div>
                                        </td>

                                        <td class="py-3 text-sm text-gray-900">
                                            <a href="{{ route('orders.details', ['id' => $installment['order_id']]) }}"
                                                class="underline">{{ $installment['order'] }}</a>
                                        </td>

                                        <td class="py-3 text-sm text-gray-900">{{ $installment['due_date'] }}</td>

                                        <td class="py-3 text-sm font-semibold text-gray-900">
                                            {!! $installment['amount'] !!}</td>

                                        <td class="py-3">
                                            @php
                                                $statusColors = [
                                                    'paid' => 'success',
                                                    'pending' => 'info',
                                                    'due' => 'danger',
                                                    'late' => 'danger',
                                                    'failed' => 'danger',
                                                    'promise' => 'info',
                                                ];
                                                $statusIcons = [
                                                    'paid' => 'ki-check-circle',
                                                    'pending' => 'ki-watch',
                                                    'due' => 'ki-cross-circle',
                                                    'late' => 'ki-cross-circle',
                                                    'failed' => 'ki-cross-circle',
                                                    'promise' => 'ki-calendar-8',
                                                ];
                                                $status = $installment['status'] ?? 'unknown';
                                                $color = $statusColors[$status] ?? 'muted';
                                                $icon = $statusIcons[$status] ?? 'ki-information';
                                            @endphp
                                            <span
                                                class="badge badge-sm badge-outline badge-{{ $color }} flex items-center gap-1 w-min">
                                                <i class="ki-filled {{ $icon }} text-xs"></i>
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>

                                        <td class="py-3">
                                            <span
                                                class="text-sm font-medium {{ $installment['dpd'] != 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $installment['dpd'] }} days
                                            </span>
                                        </td>

                                        <td class="py-3">
                                            <span
                                                class="badge badge-sm badge-outline badge-{{ $installment['channel'] == 'card' ? 'primary' : 'info' }}">
                                                <i
                                                    class="ki-filled ki-{{ $installment['channel'] == 'card' ? 'credit-cart' : 'arrow-left-right' }} text-xs mr-1"></i>
                                                {{ ucfirst($installment['channel']) }}
                                            </span>
                                        </td>

                                        <td class="py-3">
                                            <div class="flex gap-1">
                                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                    href="{{ route('collections.installmentDetails', ['id' => $installment['order_id']]) }}">
                                                    <i class="ki-filled ki-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($installments) === 0)
                                    <tr>
                                        <td colspan="9" class="py-6 text-center text-sm text-gray-500">
                                            {{ translate('No installments found for the selected range.') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Build and navigate to URL with filters (preserves existing date_from/date_to params)
            document.getElementById('installments-filter-btn').addEventListener('click', function() {
                const status = document.getElementById('filter-status').value;
                const dpd = document.getElementById('filter-dpd').value;
                const channel = document.getElementById('filter-channel').value;
                const q = document.getElementById('filter-q').value;

                const params = new URLSearchParams(window.location.search);

                // keep date filters if present
                const dateFrom = params.get('date_from');
                const dateTo = params.get('date_to');

                if (status) params.set('status', status);
                else params.delete('status');
                if (dpd) params.set('dpd', dpd);
                else params.delete('dpd');
                if (channel) params.set('channel', channel);
                else params.delete('channel');
                if (q) params.set('q', q);
                else params.delete('q');

                // preserve date_from/date_to if they exist
                if (dateFrom) params.set('date_from', dateFrom);
                if (dateTo) params.set('date_to', dateTo);

                window.location.href = window.location.pathname + '?' + params.toString();
            });

            // Clear filters (keeps date_from/date_to)
            document.getElementById('installments-clear-btn').addEventListener('click', function() {
                const params = new URLSearchParams(window.location.search);
                params.delete('status');
                params.delete('dpd');
                params.delete('channel');
                params.delete('q');

                // preserve date_from/date_to if present
                const qs = params.toString();
                window.location.href = window.location.pathname + (qs ? '?' + qs : '');
            });
        });
    </script>
@endpush
