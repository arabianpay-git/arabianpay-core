@props([
    'amount' => '0.00',
    'currency' => 'SAR',
    'showCurrency' => true,
    'size' => 'base',       // sm, base, lg, xl
    'color' => null,        // null = auto (red for negative), or force: green, red, gray
    'inline' => false,
])

@php
    $normalized = \App\Helpers\Money::normalize($amount);
    $isNegative = \App\Helpers\Money::compare($normalized, '0.00') < 0;
    $isZero = \App\Helpers\Money::isZero($normalized);

    $autoColor = $isNegative ? 'text-red-600' : ($isZero ? 'text-gray-400' : 'text-gray-900');
    $colorClass = match($color) {
        'green' => 'text-green-600',
        'red' => 'text-red-600',
        'gray' => 'text-gray-500',
        default => $autoColor,
    };

    $sizeClass = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-lg font-semibold',
        'xl' => 'text-2xl font-bold',
        default => 'text-sm font-medium',
    };

    $formatted = number_format(abs((float) $normalized), 2, '.', ',');
    $display = ($isNegative ? '-' : '') . ($showCurrency ? $currency . ' ' : '') . $formatted;
@endphp

@if($inline)
    <span {{ $attributes->merge(['class' => "{$colorClass} {$sizeClass} tabular-nums text-right font-mono"]) }}>
        {{ $display }}
    </span>
@else
    <div {{ $attributes->merge(['class' => "{$colorClass} {$sizeClass} tabular-nums text-right font-mono"]) }}>
        {{ $display }}
    </div>
@endif
