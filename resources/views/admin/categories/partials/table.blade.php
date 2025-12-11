<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th class="w-[60px] text-center">
                    {{ translate('No') }}
                </th>
                <th>
                    <span class="sort asc">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Icon') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>

                <th>
                    <span class="sort asc">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Name') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>

                <th>
                    <span class="sort asc">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Units') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>


                <th>
                    <span class="sort asc">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Parent') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>

                <th>
                    <span class="sort asc">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Order Level') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>

                <th>
                    <span class="sort asc">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Featured') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>

                <th>
                    <span class="sort">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Created At') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>

                <th>
                    <span class="sort">
                        <span class="sort-label font-normal text-gray-700">
                            {{ translate('Action') }}
                        </span>
                        <span class="sort-icon"> </span>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        @if ($category->icon)
                            <img src="{{ asset($category->icon) }}" alt="{{ $category->name }}"
                                class="w-10 h-10 object-contain border-7">
                        @else
                            <span class="text-gray-400">{{ translate('N/A') }}</span>
                        @endif
                    </td>
                    <td>{{ $category->name }}</td>
                    <td>
                        @if ($category->unit)
                            @foreach ($category->unit as $unit)
                                <button type="button"
                                    class="badge badge-sm badge-outline badge-success mt-1">{{ $unit }}</button>
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $category->parent?->name ?? '—' }}</td>
                    <td>{{ $category->order_level ?? 0 }}</td>
                    <td>
                        @if ($category->featured)
                            <span class="badge badge-sm badge-outline badge-success">{{ translate('Yes') }}</span>
                        @else
                            <span class="badge badge-sm badge-outline badge-danger">{{ translate('No') }}</span>
                        @endif
                    </td>
                    <td>{{ $category->created_at->format(dateFormat()) }}</td>
                    <td>
                        <div class="flex gap-1">
                            @can('category.update')
                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                    href="{{ route('categories.edit', ['category' => $category->id, 'page' => request()->get('page', 1)]) }}">
                                    <i class="ki-filled ki-notepad-edit"> </i>
                                </a>
                            @endcan
                            @can('category.delete')
                                <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                    href="{{ route('categories.destroy', $category->id) }}">
                                    <i class="ki-filled ki-trash"> </i>
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>

    <div class="mt-3">
        {{ $categories->links('layouts.includes.table-pagination') }}
    </div>
</div>
