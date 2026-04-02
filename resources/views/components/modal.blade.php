@props(['id', 'maxWidth'])

@php
$id = $id ?? md5($attributes->wire('model'));

$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth ?? '2xl'];
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    id="{{ $id }}"
    class="jetstream-modal fixed inset-0 z-[10050] overflow-y-auto px-3 py-3 sm:px-6"
    style="display: none; margin: auto; width: fit-content; height: fit-content;padding: 20px;"
    role="dialog"
    aria-modal="true"
>
    {{-- Centered shell: keeps dialog fully visible above Metronic header/sidebar --}}
    <div class="flex min-h-full items-center justify-center">
        {{-- Backdrop --}}
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-10 bg-gray-500 opacity-75"
            x-on:click="show = false"
            aria-hidden="true"
        ></div>

        {{-- Panel (above backdrop and Metronic chrome) --}}
        <div
            x-show="show"
            x-trap.inert.noscroll="show"
            x-on:click.stop
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative z-20 w-full max-h-[min(90vh,100%)] overflow-y-auto bg-white rounded-lg border border-gray-200 shadow-xl sm:w-full {{ $maxWidth }} sm:mx-auto"
            style="padding: 20px;"
        >
            {{ $slot }}
        </div>
    </div>
</div>
