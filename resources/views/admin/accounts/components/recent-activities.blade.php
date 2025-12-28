<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="p-6 flex justify-between items-center border-b border-slate-100">
        <h3 class="font-bold text-slate-800">{{ translate('Recent Orders') }}</h3>
        <a href="{{ route('orders') }}" class="text-blue-600 text-xs font-bold">
            {{ translate('View All') }} ({{ $totalOrders }})
        </a>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($recentOrders->take(5) as $order)
            <a href="{{ route('orders.details', $order->id) }}" target="__blank" class="block">
                <div class="p-4 flex items-center justify-between hover:bg-slate-50 cursor-pointer transition">
                    <div class="flex items-center gap-3">
                        @php
                            $iconClass = 'ki-filled ki-handcart text-xl';
                            $bgColor = 'bg-emerald-50';
                            $textColor = 'text-emerald-600';

                            if ($order->delivery_status === 'pending') {
                                $bgColor = 'bg-blue-50';
                                $textColor = 'text-blue-600';
                            } elseif ($order->delivery_status === 'cancelled') {
                                $bgColor = 'bg-red-50';
                                $textColor = 'text-red-600';
                            } elseif ($order->delivery_status === 'delivered') {
                                $bgColor = 'bg-emerald-50';
                                $textColor = 'text-emerald-600';
                            } elseif ($order->delivery_status === 'shipped') {
                                $bgColor = 'bg-orange-50';
                                $textColor = 'text-orange-600';
                            } elseif ($order->delivery_status === 'processing') {
                                $bgColor = 'bg-purple-50';
                                $textColor = 'text-purple-600';
                            }
                        @endphp

                        <div class="p-2 {{ $bgColor }} {{ $textColor }} rounded-lg">
                            <i class="{{ $iconClass }}"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold underline link text-black">{{ translate('Order') }}
                                #{{ $order->tracking ? $order->tracking : $order->id }}
                            </p>
                            <p class="text-[10px] text-slate-400">
                                {{ ucfirst($order->delivery_status) }} •
                                {{ $order->created_at?->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <p
                        class="text-sm font-bold {{ $order->delivery_status === 'cancelled' ? 'text-red-600' : 'text-emerald-600' }} flex items-center gap-2">
                        SAR {{ number_format($order->grand_total) }}
                        <i class="ki-outline ki-right text-slate-300 text-xs"></i>
                    </p>
                </div>
            </a>
        @empty
            <div class="p-4 text-center text-sm text-slate-500">
                {{ translate('No recent orders found.') }}
            </div>
        @endforelse
    </div>
</div>
