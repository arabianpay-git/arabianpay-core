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
                    Customers
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
                        Customers 
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
                                                    User Info
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Business Data
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Value of Goods
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
                                                    Joining Date
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
                                    @foreach ($customers as $item)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="whitespace-nowrap">
                                                    {{ $item->user?->first_name }} {{ $item->user?->last_name }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        — {{ $item->user?->phone_number ?? '—' }}
                                                    </small>
                                                    <br />
                                                    <small class="text-gray-500">
                                                        — {{ $item->user?->email ?? '—' }}
                                                    </small>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="whitespace-nowrap">
                                                    {{ $item->user?->business_name }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        — CR Number: {{ $item->cr_number ?? '—' }}
                                                    </small>
                                                    <br />
                                                    <small class="text-gray-500">
                                                        — {{ $item->address ?? '—' }}
                                                    </small>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="whitespace-nowrap">
                                                    <span class="icon-saudi_riyal"></span> 
                                                    {{ $item->purchasing_volume !== null ? number_format($item->purchasing_volume) : 0 }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        — Used: 
                                                        <span class="icon-saudi_riyal"></span> 
                                                        {{ $item->total_order_amount !== null ? number_format($item->total_order_amount) : 0 }}
                                                    </small>
                                                    <br>
                                                    @if ($item->package->logo)
                                                        <img src="{{ asset($item->package->logo) }}" alt="{{ $item->package->name }}" class="h-10 object-contain border-7">
                                                    @else
                                                        <span class="text-gray-400">N/A</span>
                                                    @endif
                                                </div>
                                            </td>
                                            

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

                                            <td>{{ $item->created_at->format('d M Y') }}</td>

                                            <td>
                                                <div class="flex gap-1">
                                                    <a class="btn btn-sm btn-icon btn-clear btn-primary" href="{{ route('customerProfile', ['id' => $item->user_id]) }}">
                                                        <i class="ki-filled ki-eye"> </i>
                                                    </a>
                                                    
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $customers])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>
@endsection
