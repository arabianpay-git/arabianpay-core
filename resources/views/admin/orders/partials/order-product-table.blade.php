<div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
    <div class="scrollable-x-auto">
        <table class="table table-auto table-border" data-datatable-table="true">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left border-b">{{ translate('Product') }}</th>
                    <th class="px-4 py-2 text-left border-b">{{ translate('Details') }}</th>
                    <th class="px-4 py-2 text-right border-b">{{ translate('Unit Price') }}</th>
                    <th class="px-4 py-2 text-center border-b">{{ translate('Qty') }}</th>
                    <th class="px-4 py-2 text-right border-b">{{ translate('Total') }}</th>
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
                            <img src="{{ getMediaUrl($item['product']->thumbnail) }}"
                                alt="{{ translate('Product Image') }}" class="w-16 h-16 object-cover">
                        </td>
                        <td class="px-4 py-2 border-b">
                            <div class="font-semibold">
                                {{ $item['product']->name ?? translate('N/A') }}</div>
                            <div class="text-xs text-gray-600">{{ translate('SKU') }}:
                                {{ $item['product']->sku ?? translate('N/A') }}</div>
                            @foreach ($item['attributes'] as $attribute)
                                <div class="text-xs text-gray-600">
                                    {{ $attribute['attribute'] }}: {{ $attribute['value'] }}
                                </div>
                            @endforeach
                            <div class="text-xs text-gray-600">
                                ↳ {{ translate('Shipping') }}
                                @if ($order->shipping_cost)
                                    <span class="icon-saudi_riyal"></span>
                                    {{ $order->shipping_cost }}
                                @else
                                    {{ translate('Free delivery') }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-600">
                                ↳ {{ translate('Store') }}
                                {{ $order->user->business_name ?? translate('Default Store') }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right border-b"><span class="icon-saudi_riyal"></span>
                            {{ number_format($item['price'], 2) }}</td>
                        <td class="px-4 py-2 text-center border-b">{{ $item['quantity'] }}</td>
                        <td class="px-4 py-2 text-right border-b"><span class="icon-saudi_riyal"></span>
                            {{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
