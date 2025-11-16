<!-- Orders Table -->


@if($checkout->orders->count() > 0)
<div class="overflow-x-auto">
    <table class="table table-striped" id="orders-table">
        <thead>
            <tr>
                <th class="min-w-[120px]">Order #</th>
                <th class="min-w-[100px]">Supplier</th>
                <th class="min-w-[100px]">Amount</th>
                <th class="min-w-[120px]">Payout Status</th>
                <th class="min-w-[120px]">Delivery Status</th>
                <th class="min-w-[100px]">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checkout->orders as $order)
            <tr>
                <td>
                    <div class="flex items-center gap-2">
                        <i class="ki-filled ki-package text-gray-500"></i>
                        <span class="font-medium text-gray-800">#{{ $order->order_number ?? 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </td>
                <td>
                    <div class="whitespace-nowrap">
                        {{$order->seller?->first_name }} {{ $order->seller?->last_name }}
                        <br>
                        <small class="text-gray-500">
                            — {{ $order->seller?->business_name ?? '—' }}
                        </small>
                    </div>
                </td>
                
                <td>
                    <div class="text-sm">
                        <div class="font-medium text-gray-800">{{ number_format($order->grand_total ?? 0, 2) }} SAR</div>
                        @if($order->discount_amount > 0)
                        <div class="text-xs text-green-600">-{{ number_format($order->discount_amount, 2) }} SAR</div>
                        @endif
                    </div>
                </td>
                <td>
                    @php
                    $status = $order->status ?? 'pending';
                    $statusConfig = [
                    'pending' => ['class' => 'badge-light-warning', 'text' => 'Pending'],
                    'processing' => ['class' => 'badge-light-info', 'text' => 'Processing'],
                    'shipped' => ['class' => 'badge-light-primary', 'text' => 'Shipped'],
                    'delivered' => ['class' => 'badge-light-success', 'text' => 'Delivered'],
                    'cancelled' => ['class' => 'badge-light-danger', 'text' => 'Cancelled'],
                    'refunded' => ['class' => 'badge-light-dark', 'text' => 'Refunded']
                    ];
                    $config = $statusConfig[$status] ?? ['class' => 'badge-light-secondary', 'text' => ucfirst($status)];
                    @endphp
                    <span class="badge {{ $config['class'] }}">{{ $config['text'] }}</span>
                </td>
                <td>
                    @php
                    $status = $order->status ?? 'pending';
                    $statusConfig = [
                    'pending' => ['class' => 'badge-light-warning', 'text' => 'Pending'],
                    'processing' => ['class' => 'badge-light-info', 'text' => 'Processing'],
                    'shipped' => ['class' => 'badge-light-primary', 'text' => 'Shipped'],
                    'delivered' => ['class' => 'badge-light-success', 'text' => 'Delivered'],
                    'cancelled' => ['class' => 'badge-light-danger', 'text' => 'Cancelled'],
                    'refunded' => ['class' => 'badge-light-dark', 'text' => 'Refunded']
                    ];
                    $config = $statusConfig[$status] ?? ['class' => 'badge-light-secondary', 'text' => ucfirst($status)];
                    @endphp
                    <span class="badge {{ $config['class'] }}">{{ $config['text'] }}</span>
                </td>
                <td>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('orders.details', $order->id) }}"
                            class="btn btn-sm btn-light btn-icon"
                            title="View Order">
                            <i class="ki-filled ki-eye text-sm"></i>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light btn-icon" data-toggle="dropdown">
                                <i class="ki-filled ki-dots-vertical text-sm"></i>
                            </button>
                            <div class="dropdown-content w-48">
                                @if(in_array($order->status, ['pending', 'processing']))
                                <a href="{{ route('admin.orders.edit', $order->id) }}"
                                    class="dropdown-item">
                                    <i class="ki-filled ki-pencil text-sm me-2"></i>
                                    Edit Order
                                </a>
                                @endif
                                @if($order->status !== 'cancelled')
                                <button type="button"
                                    class="dropdown-item text-danger cancel-order-btn"
                                    data-order-id="{{ $order->id }}">
                                    <i class="ki-filled ki-cross text-sm me-2"></i>
                                    Cancel Order
                                </button>
                                @endif
                                <a href="{{ route('order.downloadInvoice', $order->id) }}"
                                    class="dropdown-item">
                                    <i class="ki-filled ki-document text-sm me-2"></i>
                                    View Invoice
                                </a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Orders Summary -->
<div class="mt-6 p-4 bg-gray-50 rounded-lg">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
            <div class="text-lg font-semibold text-gray-800">{{ $checkout->orders->count() }}</div>
            <div class="text-sm text-gray-500">Total Orders</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-semibold text-green-600">{{ number_format($checkout->orders->sum('grand_total'), 2) }}</div>
            <div class="text-sm text-gray-500">Total Value (SAR)</div>
        </div>

    </div>
</div>

@else
<!-- Empty State -->
<div class="text-center py-12">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ki-filled ki-package text-2xl text-gray-400"></i>
    </div>
    <h4 class="text-lg font-semibold text-gray-600 mb-2">No Orders Found</h4>
    <p class="text-gray-500 mb-6">This checkout doesn't have any orders yet.</p>
    <a href="{{ route('admin.orders.create', ['checkout_id' => $checkout->id]) }}"
        class="btn btn-primary">
        <i class="ki-filled ki-plus me-2"></i>
        Create New Order
    </a>
</div>
@endif

<script>
    // Orders Search and Filter
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('orders-search');
        const filterSelect = document.getElementById('orders-filter');
        const table = document.getElementById('orders-table');

        if (searchInput && filterSelect && table) {
            function filterOrders() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusFilter = filterSelect.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const orderNumber = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                    const products = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const status = row.querySelector('td:nth-child(5) .badge').textContent.toLowerCase();

                    const matchesSearch = orderNumber.includes(searchTerm) || products.includes(searchTerm);
                    const matchesStatus = !statusFilter || status.includes(statusFilter);

                    row.style.display = matchesSearch && matchesStatus ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterOrders);
            filterSelect.addEventListener('change', filterOrders);
        }

        // Cancel order event listeners
        document.addEventListener('click', function(e) {
            if (e.target.closest('.cancel-order-btn')) {
                const button = e.target.closest('.cancel-order-btn');
                const orderId = button.getAttribute('data-order-id');
                cancelOrder(orderId);
            }
        });
    });

    // Cancel Order Function
    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order?')) {
            // Add your cancel order logic here
            fetch(`/admin/orders/${orderId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to cancel order. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        }
    }
</script>


<!--end::Orders Section-->

<script>
    // View order details
    function viewOrderDetails(orderId) {
        console.log('View order details:', orderId);
        // You can implement a modal or redirect to order details page
    }

    // Update order status
    function updateOrderStatus(orderId, status) {
        if (confirm('Are you sure you want to update this order status to ' + status + '?')) {
            fetch(`/admin/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                        // Show success message if you have a notification system
                    } else {
                        alert('Failed to update order status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating order status');
                });
        }
    }

    // Cancel order
    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            updateOrderStatus(orderId, 'cancelled');
        }
    }
</script>

<!--begin::Order Actions JavaScript-->
<script>
    // View order details
    function viewOrderDetails(orderId) {
        // You can implement a modal or redirect to order details page
        console.log('View order details:', orderId);
        // Example: window.open('/admin/orders/' + orderId, '_blank');
    }

    // Update order status
    function updateOrderStatus(orderId, status) {
        if (confirm('Are you sure you want to update this order status to ' + status + '?')) {
            // AJAX call to update order status
            fetch(`/admin/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh to show updated status
                        toastr.success('Order status updated successfully');
                    } else {
                        toastr.error('Failed to update order status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('An error occurred while updating order status');
                });
        }
    }

    // Cancel order
    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            updateOrderStatus(orderId, 'cancelled');
        }
    }

    // Initialize DataTable for orders
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.querySelector('[data-kt-orders-table-filter="search"]');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                // Implement search functionality
                const searchTerm = e.target.value.toLowerCase();
                const tableRows = document.querySelectorAll('#kt_orders_table tbody tr');

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Filter functionality
        const filterForm = document.querySelector('[data-kt-orders-table-filter="form"]');
        if (filterForm) {
            const filterButton = document.querySelector('[data-kt-orders-table-filter="filter"]');
            const resetButton = document.querySelector('[data-kt-orders-table-filter="reset"]');

            if (filterButton) {
                filterButton.addEventListener('click', function() {
                    // Implement filter functionality
                    console.log('Apply filters');
                });
            }

            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    // Reset all filters
                    filterForm.reset();
                    // Show all rows
                    document.querySelectorAll('#kt_orders_table tbody tr').forEach(row => {
                        row.style.display = '';
                    });
                });
            }
        }
    });
</script>
<!--end::Order Actions JavaScript-->