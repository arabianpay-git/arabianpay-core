@extends('layouts.base')

@section('content')
    <main class="flex-grow w-full h-full mx-auto p-6 space-y-6 bg-slate-50">

        @include('admin.accounts.includes.supplier-profile-header')

        @include('admin.accounts.includes.supplier-nav')

        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 space-y-6">
                <div class="card">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Supplier Products') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table"
                                        placeholder="{{ translate('Search users') }}" type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                {{ translate('No') }}
                                            </th>
                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Thumbnail') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Name') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Unit Price') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Stock') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Status') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Published') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Created At') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
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
                                        @foreach ($products as $product)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>
                                                    @if ($product->thumbnail)
                                                        <img src="{{ supplierMedia($product->thumbnail) }}"
                                                            alt="{{ $product->name }}"
                                                            class="w-10 h-10 object-contain border-7">
                                                    @else
                                                        <span class="text-gray-400">{{ translate('N/A') }}</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ \Illuminate\Support\Str::limit($product->name, 20) }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            —
                                                            {{ $product->brand?->name ?? ($product->category?->name ?? '—') }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td>{{ $product->unit_price ?? '—' }}</td>

                                                <td>{{ $product->current_stock ?? 0 }}</td>

                                                <td>
                                                    @if ($product->approved == 'approved')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ translate($product->approved) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-danger">{{ translate($product->approved) }}</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($product->published == 'published')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ translate($product->published) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-danger">{{ translate($product->published) }}</span>
                                                    @endif
                                                </td>

                                                <td>{{ $product->created_at->format(dateFormat()) }}</td>

                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('products.edit', $product->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"> </i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('products.destroy', $product->id) }}">
                                                            <i class="ki-filled ki-trash"> </i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $products])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
