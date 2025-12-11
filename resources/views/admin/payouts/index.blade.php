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
                        Payout Portal
                    </h1>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">

            <div class="grid gap-5 lg:gap-7.5">
                <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                    <div class="lg:col-span-1">
                        <div class="kt-card h-full">
                            <div class="kt-card-header">


                            </div>
                            @php
                                $paidCount = $payouts->count();
                                $readyToPayCount = $orders->where('general_status', 'processing')->count();
                                $rejectedCount = $orders->where('general_status', 'rejected')->count();
                                $totalCount = $paidCount + $readyToPayCount + $rejectedCount;
                                $paidPercent = $totalCount > 0 ? ($paidCount / $totalCount) * 100 : 0;
                                $readyToPayPercent = $totalCount > 0 ? ($readyToPayCount / $totalCount) * 100 : 0;
                                $rejectedPercent = $totalCount > 0 ? ($rejectedCount / $totalCount) * 100 : 0;
                            @endphp
                            <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-normal text-secondary-foreground">
                                        Orders Ready to Pay
                                    </span>
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-3xl font-semibold text-mono">

                                            {{ number_format((float) $orders->where('general_status', 'processing')->sum('grand_total') / 1000, 2) }}k

                                        </span>
                                        <span class="kt-badge kt-badge-outline kt-badge-success kt-badge-sm">
                                            +{{ round($readyToPayPercent) }}%
                                        </span>
                                    </div>
                                </div>
                                <!-- Progress Bars -->
                                <div class="flex items-center gap-1 mb-1.5">
                                    <div class="badge-success h-2 rounded-xs" style="width: {{ round($paidPercent) }}%">
                                    </div>

                                    <div class="badge-info h-2 rounded-xs" style="width: {{ round($readyToPayPercent) }}%">
                                    </div>
                                    <div class="badge-danger h-2 rounded-xs" style="width: {{ round($rejectedPercent) }}%">
                                    </div>
                                </div>
                                <div class="flex items-center flex-wrap gap-4 mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="badge-success size-2 rounded-full">
                                        </span>
                                        <span class="text-sm font-normal text-foreground">
                                            Paid
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded-full size-2 rounded-full badge-info">
                                        </span>
                                        <span class="text-sm font-normal text-foreground">
                                            Ready to Pay
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded-full size-2 rounded-full badge-danger">
                                        </span>
                                        <span class="text-sm font-normal text-foreground">
                                            Cancelled
                                        </span>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="kt-card h-full">
                            <form method="get" action="" class="flex items-end gap-3">
                                <div>
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" name="date_from" value="{{ $date_from }}">
                                </div>
                                <div>
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" name="date_to" value="{{ $date_to }}">
                                </div>
                                <select id="general-status-filter" name="genral_status" class="select select-sm w-48">
                                    <option value="">All Orders</option>
                                    <option value="processing">Processing</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="pending">Pending</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <div>
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-primary w-full">Apply</button>
                                </div>
                            </form>
                            <div class="border-b border-input m-5">

                            </div>
                            <div class="grid gap-3 mt-5 mr-5">
                                <div class="flex items-center gap-3">
                                    <i class="ki-filled ki-shop text-base text-muted-foreground"></i>
                                    <span class="text-sm font-normal text-mono">Orders</span>
                                    <span class="text-sm font-medium text-foreground ml-5">
                                        {{ number_format((float) $orders->sum('grand_total') / 1000, 2) }}k
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <i class="ki-filled ki-facebook text-base text-muted-foreground"></i>
                                    <span class="text-sm font-normal text-mono">Payouts</span>
                                    <span class="text-sm font-medium text-foreground ml-5">
                                        {{ number_format((float) $payouts->sum('amount') / 1000, 2) }}k
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <i class="ki-filled ki-instagram text-base text-muted-foreground"></i>
                                    <span class="text-sm font-normal text-mono">Cancelled</span>
                                    <span
                                        class="text-sm font-medium text-foreground ml-5">{{ number_format((float) $orders->where('general_status', 'cancelled')->sum('grand_total') / 1000, 2) }}K</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-4" id="langTabs">
                        <button class="tab-btn active" data-tab="orders">
                            <i class="ki-filled ki-package text-base me-2"></i>
                            Orders
                            @if ($orders->count() > 0)
                                <span
                                    class="badge badge-sm badge-danger  ml-2">{{ $orders->where('general_status', 'processing')->count() }}</span>
                            @endif
                        </button>
                        <button class="tab-btn" data-tab="payouts">
                            <i class="ki-filled ki-calendar text-base me-2"></i>
                            Payouts
                            @if ($payouts->count() > 0)
                                <span class="badge badge-sm badge-success ms-2">{{ $payouts->count() }}</span>
                            @endif
                        </button>


                    </nav>
                    <div class="tab-content mt-5" id="tab-orders">
                        <div class="card card-grid min-w-full">
                            <div class="card-header flex-wrap gap-2">
                                <h3 class="card-title font-medium text-sm">
                                    Orders
                                </h3>

                            </div>
                            <div class="card-body">
                                <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                                    <div class="scrollable-x-auto">
                                        <table class="table table-auto table-border" data-datatable-table="true">
                                            <thead>
                                                <tr>
                                                    <th>
                                                        <input class="checkbox checkbox-sm" data-datatable-check="true"
                                                            type="checkbox" id="select-all-checkbox">
                                                    </th>
                                                    <th class="w-[60px] text-center">ID</th>
                                                    <th class="text-left">
                                                        <span class="sort asc">
                                                            <span class="sort-label font-normal text-gray-700">User</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-left">
                                                        <span class="sort asc">
                                                            <span
                                                                class="sort-label font-normal text-gray-700">Seller</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>

                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">Grand
                                                                Total</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>

                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">Delivery
                                                                Status</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">General
                                                                Status</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">Created
                                                                At</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">
                                                                Assigned To
                                                            </span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span
                                                                class="sort-label font-normal text-gray-700">Action</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="orders-tbody">
                                                @foreach ($orders as $item)
                                                    <tr class="order-row"
                                                        data-payment-status="{{ $item->payment_status }}"
                                                        data-delivery-status="{{ $item->delivery_status }}"
                                                        data-general-status="{{ $item->general_status }}"
                                                        data-search-text="{{ strtolower($item->id . ' ' . $item->user?->first_name . ' ' . $item->user?->last_name . ' ' . $item->seller?->first_name . ' ' . $item->seller?->last_name . ' ' . $item->payment_type) }}">
                                                        <td>
                                                            <input class="checkbox checkbox-sm row-checkbox"
                                                                type="checkbox" name="ids[]"
                                                                value="{{ $item->id }}">
                                                        </td>
                                                        <td class="text-center">{{ $item->id }}</td>
                                                        <td>
                                                            <div class="whitespace-nowrap">
                                                                {{ $item->user?->first_name }}
                                                                {{ $item->user?->last_name }}
                                                                <br>
                                                                <small class="text-gray-500">
                                                                    — {{ $item->user?->business_name ?? '—' }}
                                                                </small>
                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="whitespace-nowrap">
                                                                {{ $item->seller?->first_name }}
                                                                {{ $item->seller?->last_name }}
                                                                <br>
                                                                <small class="text-gray-500">
                                                                    — {{ $item->seller?->business_name ?? '—' }}
                                                                </small>
                                                            </div>
                                                        </td>



                                                        <td class="text-center">
                                                            <div class="font-semibold text-gray-900">
                                                                {{ number_format((float) $item->grand_total, 2) }}</div>

                                                            <div class="text-xs text-success"> -
                                                                {{ number_format((float) $item->commission_amount, 2) }}
                                                                Commission</div>

                                                        </td>

                                                        <td class="text-center">
                                                            @if ($item->delivery_status == 'delivered')
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-success">{{ ucfirst($item->delivery_status) }}</span>
                                                            @elseif($item->delivery_status == 'shipped')
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-warning">{{ ucfirst($item->delivery_status) }}</span>
                                                            @elseif($item->delivery_status == 'pending')
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-primary">{{ ucfirst($item->delivery_status) }}</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-info">{{ ucfirst($item->delivery_status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($item->general_status == 'completed')
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-success">{{ ucfirst($item->general_status) }}</span>
                                                            @elseif($item->general_status == 'Processing')
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-warning">{{ ucfirst($item->general_status) }}</span>
                                                            @elseif($item->general_status == 'cancelled')
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-danger">{{ ucfirst($item->general_status) }}</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-sm badge-outline badge-info">{{ ucfirst($item->general_status) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            {{ $item->created_at->format(dateFormat()) }}</td>
                                                        <td>
                                                            {{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : __('--Not Assigned--') }}
                                                        </td>
                                                        <!-- Action buttons -->
                                                        <td class="text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button
                                                                    class="btn btn-sm btn-icon btn-clear btn-info order-details-btn"
                                                                    data-modal-toggle="#order_details_modal"
                                                                    data-model-id="{{ $item->id }}"
                                                                    data-url="{{ route('orders.modal-data', $item->id) }}"
                                                                    data-model-type="App\Models\Order">
                                                                    <i class="ki-filled ki-eye"> </i>
                                                                </button>
                                                                @if ($item->general_status == 'processing')
                                                                    <button
                                                                        class="btn btn-sm btn-icon btn-clear btn-success payout-order-btn"
                                                                        data-modal-toggle="#payout_order_modal"
                                                                        data-model-id="{{ $item->id }}"
                                                                        data-model-amount="{{ $item->grand_total - $item->commission_amount }}"
                                                                        data-model-user="{{ $item->user?->first_name }} {{ $item->user?->last_name }}"
                                                                        data-model-seller="{{ $item->seller?->first_name }} {{ $item->seller?->last_name }}"
                                                                        data-model-type="App\Models\Order">
                                                                        <i class="ki-filled ki-dollar"> </i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>

                                    </div>
                                    <!-- Client-side filtering - no pagination needed -->
                                    <div class="p-4 text-sm text-gray-600">
                                        <span id="order-count">Showing {{ $orders->count() }} orders</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content mt-5 hidden" id="tab-payouts">
                        <div class="card card-grid min-w-full">
                            <div class="card-header flex-wrap gap-2">
                                <h3 class="card-title font-medium text-sm">
                                    Payouts
                                </h3>
                            </div>
                            <div class="card-body">
                                <div data-datatable="true" data-datatable-state-save="false" id="payouts_table">
                                    <div class="scrollable-x-auto">
                                        <table class="table table-auto table-border" data-datatable-table="true">
                                            <thead>
                                                <tr>
                                                    <th class="w-[60px] text-center">ID</th>
                                                    <th class="text-left">
                                                        <span class="sort asc">
                                                            <span class="sort-label font-normal text-gray-700">Order
                                                                ID</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-left">
                                                        <span class="sort asc">
                                                            <span
                                                                class="sort-label font-normal text-gray-700">Seller</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span
                                                                class="sort-label font-normal text-gray-700">Amount</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th>
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">Notes</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>

                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">Created
                                                                At</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span class="sort-label font-normal text-gray-700">Created
                                                                By</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="sort">
                                                            <span
                                                                class="sort-label font-normal text-gray-700">Action</span>
                                                            <span class="sort-icon"> </span>
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="payouts-tbody">
                                                @foreach ($payouts as $payout)
                                                    <tr class="payout-row"
                                                        data-search-text="{{ strtolower($payout->id . ' ' . $payout->order_id . ' ' . $payout->seller?->first_name . ' ' . $payout->seller?->last_name) }}">
                                                        <td class="text-center">{{ $payout->id }}</td>
                                                        <td>
                                                            <div class="whitespace-nowrap">
                                                                {{ $payout->order_id }}

                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="whitespace-nowrap">
                                                                {{ $payout->seller?->first_name }}
                                                                {{ $payout->seller?->last_name }}
                                                                <br>
                                                                <small class="text-gray-500">
                                                                    — {{ $payout->seller?->business_name ?? '—' }}
                                                                </small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="font-semibold text-gray-900">
                                                                {{ number_format((float) $payout->amount, 2) }}</div>

                                                        </td>
                                                        <td>
                                                            {{ $payout->notes ?? '--' }}
                                                        <td class="text-center">
                                                            {{ $payout->created_at->format(dateFormat()) }}</td>
                                                        <td class="text-center">
                                                            {{ $payout->createdBy ? $payout->creator->first_name . ' ' . $payout->createdBy->last_name : __('--Unknown--') }}
                                                        </td>
                                                        <!-- Action buttons -->
                                                        <td class="text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button
                                                                    class="btn btn-sm btn-icon btn-clear btn-info payout-details-btn"
                                                                    data-modal-toggle="#payout_details_modal"
                                                                    data-model-id="{{ $payout->id }}"
                                                                    data-url="{{ route('payouts.details-modal-data', $payout->id) }}">
                                                                    <i class="fas fa-info-circle"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End of Container -->
    </main>
    @include('admin.components.transfer-detail')
    @include('admin.components.order-details')
    @include('admin.components.payout-create')
    @push('scripts')
        <script>
            console.log('=== Payouts page script loaded ===');

            // Inline script scoped to this page to load order details into modal
            const ORDER_LOADING_TEXT = 'Loading order details...';
            const ORDER_ERROR_TEXT = 'Failed to load order details. Please try again.';

            window.loadOrderDetailsIntoModal = async function(orderId, urlOverride = null) {
                console.log('=== loadOrderDetailsIntoModal called ===');
                console.log('loadOrderDetailsIntoModal called with ID:', orderId, 'URL:', urlOverride);
                const body = document.getElementById('order_details_body');
                if (!body) {
                    console.error('order_details_body element not found');
                    return;
                }
                body.innerHTML = `
            <div class="py-8 text-center text-gray-500">
                <span class="spinner-border animate-spin inline-block w-5 h-5 border-2 border-current border-r-transparent rounded-full align-[-0.125em] mr-2"></span>
                ${ORDER_LOADING_TEXT}
            </div>
        `;
                try {
                    const url = urlOverride || `/admin/orders/${encodeURIComponent(orderId)}/modal-data`;
                    console.log('Fetching order data from:', url);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    console.log('Response status:', res.status, 'OK:', res.ok);
                    console.log('Response headers:', Object.fromEntries(res.headers.entries()));

                    const html = await res.text();
                    console.log('Response content length:', html.length);
                    console.log('Response content (first 200 chars):', html.substring(0, 200));

                    if (!res.ok) {
                        console.error('Response not OK. Status:', res.status, 'Body:', html);
                        throw new Error(`Failed to load (${res.status})`);
                    }

                    if (html.trim().length === 0) {
                        console.warn('Empty response received');
                        body.innerHTML = `<div class="p-6 text-center text-gray-500">No content available</div>`;
                        return;
                    }

                    body.innerHTML = html;
                    console.log('Order details loaded successfully');
                } catch (err) {
                    console.error('Order modal load error:', err);
                    body.innerHTML = `
                <div class="p-6 text-center text-danger-600">${ORDER_ERROR_TEXT}</div>
            `;
                }
            }

            console.log('loadOrderDetailsIntoModal function defined successfully');

            // Check if buttons exist on page load
            setTimeout(() => {
                const buttons = document.querySelectorAll('.order-details-btn');
                console.log('Found', buttons.length, 'order-details-btn buttons');
                if (buttons.length > 0) {
                    console.log('First button attributes:', {
                        id: buttons[0].getAttribute('data-model-id'),
                        url: buttons[0].getAttribute('data-url')
                    });

                    // Test if clicking works
                    console.log('Testing click on first button...');
                    // Uncomment next line to auto-test: buttons[0].click();
                }
            }, 500);

            // Native delegated handler (no dependency on jQuery load order)
            // Use capture phase to run BEFORE the modal toggle handler
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.order-details-btn');
                if (!btn) return;
                console.log('Order details button clicked!');
                const id = btn.getAttribute('data-model-id');
                if (!id) {
                    console.error('No data-model-id found on button');
                    return;
                }
                const url = btn.getAttribute('data-url');
                console.log('Order details button clicked (native). ID:', id, 'URL:', url);

                // Load the data immediately
                window.loadOrderDetailsIntoModal(String(id), url);

                // The data-modal-toggle attribute will handle opening the modal
            }, true); // Use capture phase to ensure this runs first

            console.log('Click handler registered successfully');

            // ==================== Payout Button Handler ====================
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.payout-order-btn');
                if (!btn) return;

                console.log('Payout button clicked!');

                const orderId = btn.getAttribute('data-model-id');
                const amount = btn.getAttribute('data-model-amount');
                const customerName = btn.getAttribute('data-model-user');
                const sellerName = btn.getAttribute('data-model-seller');

                console.log('Payout data:', {
                    orderId,
                    amount,
                    customerName,
                    sellerName
                });

                // Populate form fields
                document.getElementById('payout-order-id').value = orderId;
                document.getElementById('display-order-id').textContent = orderId;
                document.getElementById('display-customer-name').textContent = customerName || 'N/A';
                document.getElementById('display-seller-name').textContent = sellerName || 'N/A';
                document.getElementById('display-order-amount').textContent = amount ?
                    `${parseFloat(amount).toFixed(2)}` : 'N/A';

                // Pre-fill the amount field with the order amount (user can modify)
                if (amount) {
                    document.getElementById('payout-amount').value = parseFloat(amount).toFixed(2);
                }

                // Clear notes
                document.getElementById('payout-notes').value = '';

                console.log('Payout form populated successfully');
            }, true); // Use capture phase

            console.log('Payout button handler registered');
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const generalStatusFilter = document.getElementById('general-status-filter');
                const tbody = document.getElementById('orders-tbody');
                const allRows = Array.from(tbody.querySelectorAll('.order-row'));
                const countDisplay = document.getElementById('order-count');

                console.log(`Captured ${allRows.length} rows for filtering`);

                function filterOrders() {
                    const generalStatus = (generalStatusFilter.value || '').toLowerCase();
                    let visibleCount = 0;

                    allRows.forEach(row => {
                        const rowStatus = (row.dataset.generalStatus || '').toLowerCase();

                        const shouldShow = !generalStatus || rowStatus === generalStatus;
                        row.style.display = shouldShow ? '' : 'none';

                        if (shouldShow) visibleCount++;
                    });

                    if (countDisplay) {
                        countDisplay.textContent = `Showing ${visibleCount} of ${allRows.length} orders`;
                    }

                    console.log(`Filter applied: "${generalStatus}" — showing ${visibleCount}`);
                }

                // Apply filter when dropdown changes
                generalStatusFilter.addEventListener('change', filterOrders);

                // Initial display update (optional)
                filterOrders();
            });
        </script>
    @endpush
@endsection
