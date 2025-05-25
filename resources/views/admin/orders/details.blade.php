@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        Order Information #{{ \Illuminate\Support\Str::upper($order->tracking ?? '-') }}
                    </h1>
                </div>
                <div class="flex justify-end">
                    <button class="btn btn-sm btn-light" data-modal-toggle="#transfer_request">
                        <i class="ki-filled ki-disconnect">
                        </i> Transfer Request
                    </button>
                </div>
            </div>
        </div>
        @include('admin.components.transfer-request', [
            'employees' => getEmployees(),
            'model_type' => 'App\Models\Order',
            'model_id' => $order->id,
        ])
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <!-- begin: grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

                <div class="col-span-2">
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                Products Orders
                            </h3>
                            <div class="flex flex-wrap gap-2 lg:gap-5">
                                <div class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"> </i>
                                        <input data-datatable-search="#team_crew_table" placeholder="Search users"
                                            type="text" value="" />
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                                <div class="scrollable-x-auto">
                                    <table class="table table-auto table-border" data-datatable-table="true">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left border-b">Product</th>
                                                <th class="px-4 py-2 text-left border-b">Details</th>
                                                <th class="px-4 py-2 text-right border-b">Unit Price</th>
                                                <th class="px-4 py-2 text-center border-b">Qty</th>
                                                <th class="px-4 py-2 text-right border-b">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $subTotal = 0;
                                                $totalQuantity = 0;
                                                $totalShippingFee = 0;
                                            @endphp
                                            @foreach ($productDetails as $item)
                                                @php
                                                    $subTotal += $item['total'];
                                                    $totalQuantity += $item['quantity'];
                                                    $totalShippingFee += $order->shipping_cost;
                                                @endphp
                                                <tr>
                                                    <td class="px-4 py-2 border-b">
                                                        <img src="{{ $item['product']->thumbnail ?? 'https://via.placeholder.com/60' }}"
                                                            alt="Product Image" class="w-16 h-16 object-cover">
                                                    </td>
                                                    <td class="px-4 py-2 border-b">
                                                        <div class="font-semibold">{{ $item['product']->name ?? 'N/A' }}
                                                        </div>
                                                        <div class="text-xs text-gray-600">SKU:
                                                            {{ $item['product']->sku ?? 'N/A' }}</div>
                                                        @foreach ($item['attributes'] as $attribute)
                                                            <div class="text-xs text-gray-600">
                                                                {{ $attribute['attribute'] }}: {{ $attribute['value'] }}
                                                            </div>
                                                        @endforeach
                                                        <div class="text-xs text-gray-600">
                                                            ↳ Shipping
                                                            @if ($order->shipping_cost)
                                                                <span class="icon-saudi_riyal"></span>
                                                                {{ $order->shipping_cost }}
                                                            @else
                                                                Free delivery
                                                            @endif
                                                        </div>

                                                        <div class="text-xs text-gray-600">↳ Store
                                                            {{ $order->user->business_name ?? 'Default Store' }}</div>
                                                    </td>
                                                    <td class="px-4 py-2 text-right border-b"><span
                                                            class="icon-saudi_riyal"></span>
                                                        {{ number_format($item['price'], 2) }}</td>
                                                    <td class="px-4 py-2 text-center border-b">{{ $item['quantity'] }}</td>
                                                    <td class="px-4 py-2 text-right border-b"><span
                                                            class="icon-saudi_riyal"></span>
                                                        {{ number_format($item['total'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add this before the Products Orders card --}}
                    <div class="card grow mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Customer & Shipping Information</h3>
                        </div>
                        <div class="card-body pt-4 pb-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Customer Info Column -->
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-700 mb-3">Customer Details</h4>
                                    <table class="table-auto w-full">
                                        <tbody>
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Full Name</td>
                                                <td class="text-sm text-gray-800">{{ $order->user->first_name }}
                                                    {{ $order->user->last_name }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Email</td>
                                                <td class="text-sm text-gray-800">
                                                    <a href="mailto:{{ $order->user->email }}"
                                                        class="text-primary hover:underline">
                                                        {{ $order->user->email }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @if ($order->user->phone_number)
                                                <tr>
                                                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Phone Number</td>
                                                    <td class="text-sm text-gray-800">
                                                        <a href="tel:{{ $order->user->phone_number }}"
                                                            class="text-primary hover:underline">
                                                            {{ $order->user->phone_number }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-36 pb-5 pe-6">
                                                    Member Since
                                                </td>
                                                <td class="flex items-center gap-2.5 text-sm text-gray-800">
                                                    {{ $order->user->created_at->format('d M, Y') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Shipping Info Column -->
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-700 mb-3">Shipping Details</h4>
                                    <table class="table-auto w-full">
                                        <tbody>
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Recipient</td>
                                                <td class="text-sm text-gray-800">{{ $order->shipping_first_name }}
                                                    {{ $order->shipping_last_name }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Address Line 1</td>
                                                <td class="text-sm text-gray-800">{{ $order->shipping_address_line1 }}</td>
                                            </tr>
                                            @if ($order->shipping_address_line2)
                                                <tr>
                                                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Address Line 2</td>
                                                    <td class="text-sm text-gray-800">{{ $order->shipping_address_line2 }}
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">City/State/ZIP</td>
                                                <td class="text-sm text-gray-800">
                                                    {{ $order->shipping_city }}, {{ $order->shipping_state }}
                                                    {{ $order->shipping_postal_code }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Country</td>
                                                <td class="text-sm text-gray-800">{{ $order->shipping_country }}</td>
                                            </tr>
                                            @if ($order->shipping_email)
                                                <tr>
                                                    <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">Email</td>
                                                    <td class="text-sm text-gray-800">{{ $order->shipping_email }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td class="text-sm text-gray-600 min-w-36 pb-5 pe-6">
                                                    Shipping Method
                                                </td>
                                                <td class="flex items-center gap-2.5 text-sm text-gray-800">
                                                    <i class="ki-filled ki-truck-fast text-primary"></i>
                                                    {{ $order->shipping_type ?? 'Standard Shipping' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>


                <div class="col-span-1 lg:col-span-1">
                    <div class="card grow shadow-lg rounded-lg bg-white">
                        <div class="card-header flex justify-between items-center p-4 border-b">
                            <h3 class="card-title font-semibold text-xl text-gray-800">
                                Order Status
                            </h3>
                            <!-- Download Invoice Button -->
                            <a href="{{ route('order.downloadShippingLabel', $order->id) }}"
                                class="btn btn-light btn-sm bg-gray-100 text-gray-800 hover:bg-gray-200">
                                <i class="ki-filled ki-exit-down"></i> Print Shipping Label
                            </a>
                        </div>
                        <div class="card-body pt-4 pb-3">
                            <form action="{{ route('order.updateStatus', $order->id) }}" method="POST"
                                class="space-y-4">
                                @csrf
                                @method('PUT')

                                <!-- Delivery Status -->
                                <div>
                                    <label for="delivery_status"
                                        class="block text-sm font-medium text-gray-700 mb-1">Delivery Status</label>
                                    <select name="delivery_status" id="delivery_status" class="select">
                                        <option value="">-- Select Status --</option>
                                        <option value="pending"
                                            {{ $order->delivery_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="shipped"
                                            {{ $order->delivery_status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                        <option value="delivered"
                                            {{ $order->delivery_status === 'delivered' ? 'selected' : '' }}>Delivered
                                        </option>
                                        <option value="returned"
                                            {{ $order->delivery_status === 'returned' ? 'selected' : '' }}>Returned
                                        </option>
                                    </select>
                                </div>

                                <!-- General Status -->
                                <div class="w-full mt-4">
                                    <label for="general_status"
                                        class="block text-sm font-medium text-gray-700 mb-1">General Status</label>
                                    <select name="general_status" id="general_status" class="select">
                                        <option value="">-- Select Status --</option>
                                        <option value="processing"
                                            {{ $order->general_status === 'processing' ? 'selected' : '' }}>Processing
                                        </option>
                                        <option value="completed"
                                            {{ $order->general_status === 'completed' ? 'selected' : '' }}>Completed
                                        </option>
                                        <option value="cancelled"
                                            {{ $order->general_status === 'cancelled' ? 'selected' : '' }}>Cancelled
                                        </option>
                                        <option value="failed"
                                            {{ $order->general_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button type="submit" class="btn btn-primary">
                                        Update Status
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card grow shadow-lg rounded-lg bg-white mt-5">
                        <div class="card-header flex justify-between items-center p-4 border-b">
                            <h3 class="card-title font-semibold text-xl text-gray-800">
                                Order Summary
                            </h3>
                            <!-- Download Invoice Button -->
                            <button class="btn btn-light btn-sm bg-gray-100 text-gray-800 hover:bg-gray-200">
                                <a href="{{ route('order.downloadInvoice', $order->id) }}">
                                    <i class="ki-filled ki-exit-down"> </i> Download Invoice
                                </a>
                            </button>
                        </div>
                        <div class="card-body pt-4 pb-3">
                            <table class="table-auto w-full text-sm text-gray-700">
                                <tbody>
                                    <tr>
                                        <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                            Quantity
                                        </td>
                                        <td class="text-sm text-gray-800">
                                            {{ number_format($totalQuantity) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                            Sub Amount
                                        </td>
                                        <td class="text-sm text-gray-800">
                                            <span class="icon-saudi_riyal"></span> {{ number_format($subTotal, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                            Discount
                                        </td>
                                        <td class="text-sm text-gray-800">
                                            <span class="icon-saudi_riyal"></span>
                                            {{ number_format($order->coupon_discount, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                            Shipping Fee
                                        </td>
                                        <td class="text-sm text-gray-800">
                                            @if ($totalShippingFee)
                                                <span class="icon-saudi_riyal"></span>
                                                {{ number_format($totalShippingFee, 2) }}
                                            @else
                                                Free delivery
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                            Total Amount
                                        </td>
                                        <td class="text-sm text-gray-800">
                                            <span class="icon-saudi_riyal"></span>
                                            {{ number_format($subTotal + $order->shipping_fee - $order->coupon_discount, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                            Payment Status
                                        </td>
                                        <td class="text-sm text-gray-800">
                                            <!-- Payment Status Badge -->
                                            @php
                                                $status = strtolower($order->payment_status);
                                            @endphp
                                            @if ($status == 'pending')
                                                <span class="badge badge-sm badge-warning badge-outline">Pending</span>
                                            @elseif($status == 'completed')
                                                <span class="badge badge-sm badge-success badge-outline">Completed</span>
                                            @elseif($status == 'failed')
                                                <span class="badge badge-sm badge-error badge-outline">Failed</span>
                                            @elseif($status == 'refunded')
                                                <span class="badge badge-sm badge-info badge-outline">Refunded</span>
                                            @else
                                                <span class="badge badge-sm badge-secondary badge-outline">Unknown</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card grow shadow-lg rounded-lg bg-white mt-5">
                        <div class="card-header flex justify-between items-center p-4 border-b">
                            <h3 class="card-title font-semibold text-xl text-gray-800">
                                Pickup Point Information
                            </h3>
                        </div>
                        <div class="card-body pt-4 pb-3">
                            <table class="table-auto w-full text-sm text-gray-700">
                                <tbody>
                                    @if ($order->pickupPoint)
                                        <tr>
                                            <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                                Pickup Point
                                            </td>
                                            <td class="text-sm text-gray-800">
                                                {{ $order->pickupPoint->name }}
                                            </td>
                                        </tr>

                                        @php
                                            $pickupPointData = json_decode($order->pickupPoint->address, true);
                                        @endphp
                                        <tr>
                                            <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                                Address
                                            </td>
                                            <td class="text-sm text-gray-800">
                                                {{ $pickupPointData['address'] ?? 'No address available' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                                Latitude
                                            </td>
                                            <td class="text-sm text-gray-800">
                                                {{ $pickupPointData['latitude'] ?? 'No latitude available' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                                Longitude
                                            </td>
                                            <td class="text-sm text-gray-800">
                                                {{ $pickupPointData['longitude'] ?? 'No longitude available' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                                Phone Number
                                            </td>
                                            <td class="text-sm text-gray-800">
                                                <a href="tel:{{ $order->pickupPoint->phone_number }}"
                                                    class="text-primary hover:underline">
                                                    {{ $order->pickupPoint->phone_number }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 min-w-36 pb-3 pe-6">
                                                Pickup Status
                                            </td>
                                            <td class="text-sm text-gray-800">
                                                @if ($order->pickupPoint->pick_up_status)
                                                    <span class="badge badge-sm badge-success badge-outline">Active</span>
                                                @else
                                                    <span
                                                        class="badge badge-sm badge-warning badge-outline">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="2" class="text-sm text-gray-600 text-center">
                                                No pickup point assigned for this order.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>



                </div>
            </div>

            <!-- end: grid -->
        </div>
        <!-- End of Container -->
    </main>


@endsection
