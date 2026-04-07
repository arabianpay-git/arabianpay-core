@props([
    'type' => 'info',    // info, warning, danger, success
    'icon' => null,
    'dismissible' => true,
    'count' => null,
])

@php
    $config = match($type) {
        'success' => [
            'bg' => 'bg-green-50 border-green-200',
            'text' => 'text-green-800',
            'icon' => $icon ?? 'ki-check-circle',
            'iconColor' => 'text-green-600',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 border-amber-200',
            'text' => 'text-amber-800',
            'icon' => $icon ?? 'ki-notification',
            'iconColor' => 'text-amber-600',
        ],
        'danger' => [
            'bg' => 'bg-red-50 border-red-200',
            'text' => 'text-red-800',
            'icon' => $icon ?? 'ki-shield-cross',
            'iconColor' => 'text-red-600',
        ],
        default => [
            'bg' => 'bg-blue-50 border-blue-200',
            'text' => 'text-blue-800',
            'icon' => $icon ?? 'ki-information-2',
            'iconColor' => 'text-blue-600',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-center gap-3 px-4 py-3 rounded-lg border {$config['bg']}"]) }}
     x-data="{ show: true }" x-show="show" x-transition>
    <i class="ki-filled {{ $config['icon'] }} text-lg {{ $config['iconColor'] }}"></i>
    <div class="flex-1 {{ $config['text'] }}">
        @if($count)
            <span class="font-bold tabular-nums">{{ $count }}</span>
        @endif
        <span class="text-sm">{{ $slot }}</span>
    </div>
    @if($dismissible)
        <button @click="show = false" class="btn btn-sm btn-icon btn-clear {{ $config['text'] }}">
            <i class="ki-filled ki-cross text-xs"></i>
        </button>
    @endif
</div>
