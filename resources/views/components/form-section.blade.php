@props(['submit'])

<div {{ $attributes->merge(['class' => 'card min-w-full']) }}>
    <div class="card-header border-b border-gray-200">
        <h3 class="card-title text-sm font-medium text-gray-900">{{ $title }}</h3>
        <p class="text-sm text-gray-600 mt-1">{{ $description }}</p>
    </div>
    <form wire:submit="{{ $submit }}">
        <div class="card-body">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-6">
                {{ $form }}
            </div>
        </div>

        @if (isset($actions))
            <div
                class="card-footer flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/80 px-6 py-4">
                {{ $actions }}
            </div>
        @endif
    </form>
</div>
