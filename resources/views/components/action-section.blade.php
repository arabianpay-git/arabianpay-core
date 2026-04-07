<div {{ $attributes->merge(['class' => 'card min-w-full']) }}>
    <div class="card-header border-b border-gray-200">
        <h3 class="card-title text-sm font-medium text-gray-900">{{ $title }}</h3>
        <p class="text-sm text-gray-600 mt-1">{{ $description }}</p>
    </div>
    <div class="card-body">
        {{ $content }}
    </div>
</div>
