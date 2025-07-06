@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Product Reviews') }}
                    </h1>
                </div>
                {{-- 
            <div class="flex items-center gap-2.5">
                <a class="btn btn-sm btn-light" href="{{ route('products.create') }}">
                    {{ translate('Create New Product') }}
                </a>
            </div>
            --}}
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Product Reviews') }}
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
                                                        {{ translate('Stock') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="">
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Reviews') }}
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
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Created At') }}
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
                                                            — {{ $product->brand?->name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td>{{ $product->current_stock ?? 0 }}</td>

                                                <td>
                                                    <div class="rating">
                                                        @php
                                                            $rating = $product->rating;
                                                        @endphp

                                                        @for ($i = 1; $i <= 5; $i++)
                                                            @if ($rating >= $i)
                                                                <div class="rating-label checked">
                                                                    <i
                                                                        class="rating-on ki-solid ki-star text-base leading-none"></i>
                                                                    <i
                                                                        class="rating-off ki-outline ki-star text-base leading-none"></i>
                                                                </div>
                                                            @elseif ($rating >= $i - 0.5)
                                                                <div class="rating-label indeterminate">
                                                                    <i class="rating-on ki-solid ki-star text-base leading-none"
                                                                        style="width: 50%;"></i>
                                                                    <i
                                                                        class="rating-off ki-outline ki-star text-base leading-none"></i>
                                                                </div>
                                                            @else
                                                                <div class="rating-label">
                                                                    <i
                                                                        class="rating-on ki-solid ki-star text-base leading-none"></i>
                                                                    <i
                                                                        class="rating-off ki-outline ki-star text-base leading-none"></i>
                                                                </div>
                                                            @endif
                                                        @endfor
                                                    </div>

                                                    <small>({{ $rating > 0 ? number_format($rating, 1) : '-' }})</small>
                                                </td>

                                                <td>
                                                    @if ($product->approved == 'approved')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ translate($product->approved) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-sm badge-outline badge-danger">{{ translate($product->approved) }}</span>
                                                    @endif
                                                </td>

                                                <td>{{ $product->created_at->format('d M Y') }}</td>
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
        <!-- End of Container -->
    </main>
@endsection
