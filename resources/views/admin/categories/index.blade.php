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
                    Categories
                </h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a class="btn btn-sm btn-light" href="{{ route('categories.create') }}">
                    Create New Category
                </a>
            </div>
        </div>
    </div>
    <!-- End of Container -->
    <!-- Container -->
    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        Categories 
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#team_crew_table" placeholder="Search users" type="text" value="" />
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
                                        <th class="w-[60px] text-center">
                                            No
                                        </th>
                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Icon
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Name
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Parent
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Order Level
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Featured
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Created At
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                        
                                        <th class="">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Action
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
                                                    <img src="{{ asset($category->icon) }}" alt="{{ $category->name }}" class="w-10 h-10 object-contain border-7">
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                
                                            <td>{{ $category->name }}</td>
                                
                                            <td>{{ $category->parent?->name ?? '—' }}</td>
                                
                                            <td>{{ $category->order_level ?? 0 }}</td>
                                
                                            <td>
                                                @if ($category->featured)
                                                    <span class="badge badge-sm badge-outline badge-success">Yes</span>
                                                @else
                                                    <span class="badge badge-sm badge-outline badge-danger">No</span>
                                                @endif
                                            </td>
                                
                                            <td>{{ $category->created_at->format('d M Y') }}</td>
                                
                                            <td>
                                                <div class="flex gap-1">
                                                    <a class="btn btn-sm btn-icon btn-clear btn-primary" href="{{ route('categories.edit', $category->id) }}">
                                                        <i class="ki-filled ki-notepad-edit"> </i>
                                                    </a>
                                                    <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn" href="{{ route('categories.destroy', $category->id) }}">
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
                        @include('layouts.includes.table-pagination', ['paginator' => $categories])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection
