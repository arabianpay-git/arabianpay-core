@php
    $selected = old('category_id', $selectedId ?? null) == $category->id ? 'selected' : '';
@endphp

<option value="{{ $category->id }}" {{ $selected }}>
    {{ $prefix }}{{ $category->name }}
</option>

@foreach ($categories->where('parent_id', $category->id) as $child)
    @include('admin.products.option', [
        'category' => $child,
        'categories' => $categories,
        'prefix' => $prefix . '— ',
        'selectedId' => $selectedId,
    ])
@endforeach
