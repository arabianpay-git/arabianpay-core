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
                    Suppliers
                </h1>
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
                        Suppliers 
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
                                                    Name
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    CR Number
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Business Type
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Status
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
                                    @foreach ($merchants as $item)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="whitespace-nowrap">
                                                    {{ $item->user->first_name }} {{ $item->user->last_name }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        — {{ $item->user?->business_name ?? '—' }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>{{ $item->cr_number }}</td>
                                            <td>{{ $item->businessType->name ?? 'N/A' }}</td>
                                            <td>
                                                <!-- Badge for Status -->
                                                <span class="badge badge-sm badge-outline 
                                                    @if($item->status == 'approved') 
                                                        badge-success
                                                    @elseif($item->status == 'pending') 
                                                        badge-danger
                                                    @else
                                                        badge-warning
                                                    @endif">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <a class="btn btn-sm btn-icon btn-clear btn-primary" href="{{ route('supplierProfile', ['id' => $item->id]) }}">
                                                        <i class="ki-filled ki-notepad-edit"> </i>
                                                    </a>
                                                    
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $merchants])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>
@endsection
