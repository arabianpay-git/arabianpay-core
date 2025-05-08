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
                    User Searches
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
                        User Searches
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
                                        <th class="text-center">
                                            No
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    User
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Search Term
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($userSearches as $userSearch)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="whitespace-nowrap">
                                                    {{ $userSearch->user->first_name }} {{ $userSearch->user->last_name }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        — {{ $userSearch->user->business_name ?? '—' }}
                                                    </small>
                                                </div>
                                            </td>

                                            <td>{{ $userSearch->search_term }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $userSearches])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>
@endsection
