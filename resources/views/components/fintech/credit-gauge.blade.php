@props([
    'used' => '0.00',
    'limit' => '0.00',
    'currency' => 'SAR',
    'showLabels' => true,
])

@php
    $usedNorm = \App\Helpers\Money::normalize($used);
    $limitNorm = \App\Helpers\Money::normalize($limit);

    $percentage = \App\Helpers\Money::isZero($limitNorm)
        ? 0
        : min(100, round((float)$usedNorm / (float)$limitNorm * 100));

    $available = \App\Helpers\Money::subtract($limitNorm, $usedNorm);

    $barColor = $percentage < 60 ? 'bg-green-500'
        : ($percentage < 80 ? 'bg-amber-500'
        : 'bg-red-500');

    $textColor = $percentage < 60 ? 'text-green-700'
        : ($percentage < 80 ? 'text-amber-700'
        : 'text-red-700');
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($showLabels)
        <div class="flex justify-between items-baseline mb-2">
            <span class="text-xs text-gray-500">Credit Utilization</span>
            <span class="text-sm font-semibold {{ $textColor }} tabular-nums">{{ $percentage }}%</span>
        </div>
    @endif

    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
             style="width: {{ $percentage }}%"></div>
    </div>

    @if($showLabels)
        <div class="flex justify-between mt-2">
            <div>
                <p class="text-2xs text-gray-400 uppercase">Used</p>
                <x-fintech.money :amount="$usedNorm" :currency="$currency" size="sm" />
            </div>
            <div class="text-right">
                <p class="text-2xs text-gray-400 uppercase">Available</p>
                <x-fintech.money :amount="$available" :currency="$currency" size="sm" color="green" />
            </div>
        </div>
    @endif
</div>
