@props([
    'title' => '',
    'value' => '',
    'subtitle' => null,
    'icon' => 'ki-chart-line',
    'trend' => null,        // 'up', 'down', 'flat', or null
    'trendValue' => null,   // e.g. '+12%'
    'color' => 'primary',   // primary, success, danger, warning, info
    'isMoney' => false,
    'currency' => 'SAR',
])

@php
    $borderColor = match($color) {
        'success' => 'border-green-200',
        'danger'  => 'border-red-200',
        'warning' => 'border-amber-200',
        'info'    => 'border-blue-200',
        default   => 'border-primary-200',
    };

    $iconBg = match($color) {
        'success' => 'bg-green-50 text-green-600',
        'danger'  => 'bg-red-50 text-red-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'info'    => 'bg-blue-50 text-blue-600',
        default   => 'bg-primary-50 text-primary-600',
    };

    $trendIcon = match($trend) {
        'up'   => 'ki-arrow-up',
        'down' => 'ki-arrow-down',
        default => null,
    };

    $trendColor = match($trend) {
        'up'   => 'text-green-600',
        'down' => 'text-red-600',
        default => 'text-gray-400',
    };
@endphp

<div {{ $attributes->merge(['class' => "card border-l-4 {$borderColor}"]) }}>
    <div class="card-body flex items-center gap-4 py-4 px-5">
        <div class="rounded-lg p-3 {{ $iconBg }}">
            <i class="ki-filled {{ $icon }} text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $title }}</p>
            <div class="flex items-baseline gap-2 mt-1">
                @if($isMoney)
                    <x-fintech.money :amount="$value" :currency="$currency" size="xl" />
                @else
                    <span class="text-2xl font-bold text-gray-900 tabular-nums">{{ $value }}</span>
                @endif

                @if($trend && $trendValue)
                    <span class="flex items-center gap-0.5 text-xs font-medium {{ $trendColor }}">
                        @if($trendIcon)
                            <i class="ki-filled {{ $trendIcon }} text-2xs"></i>
                        @endif
                        {{ $trendValue }}
                    </span>
                @endif
            </div>
            @if($subtitle)
                <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</div>
