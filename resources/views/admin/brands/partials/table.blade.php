<div class="scrollable-x-auto">
    <table class="table table-auto table-border">
        <thead>
            <tr>
                <th class="w-[60px] text-center">{{ translate('No') }}</th>
                <th>{{ translate('Logo') }}</th>
                <th>{{ translate('Name') }}</th>
                <th>{{ translate('Order Level') }}</th>
                <th>{{ translate('Featured') }}</th>
                <th>{{ translate('Created At') }}</th>
                <th>{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($brands as $brand)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        @if ($brand->logo)
                            <img src="{{ asset($brand->logo) }}" alt="{{ $brand->name }}"
                                class="w-10 h-10 object-contain border-7">
                        @else
                            <span class="text-gray-400">{{ translate('N/A') }}</span>
                        @endif
                    </td>
                    <td>{{ $brand->name }}</td>
                    <td>{{ $brand->order_level ?? 0 }}</td>
                    <td>
                        @if ($brand->featured)
                            <span class="badge badge-sm badge-outline badge-success">{{ translate('Yes') }}</span>
                        @else
                            <span class="badge badge-sm badge-outline badge-danger">{{ translate('No') }}</span>
                        @endif
                    </td>
                    <td>{{ $brand->created_at->format(dateFormat()) }}</td>
                    <td>
                        <div class="flex gap-1">
                            @can('brand.update')
                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                    href="{{ route('brands.edit', $brand->id) }}">
                                    <i class="ki-filled ki-notepad-edit"> </i>
                                </a>
                            @endcan
                            @can('brand.delete')
                                <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                    href="{{ route('brands.destroy', $brand->id) }}">
                                    <i class="ki-filled ki-trash"> </i>
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $brands->links('layouts.includes.table-pagination') }}
    </div>
</div>
