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
                    Total Supplier Debt
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
                        Total Supplier Debt
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
                    <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th class="w-[60px] text-center">No</th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Opening Balance</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Total Purchases</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Total Payments</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Remaining Balance</span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">1</td>
                                        <td class="text-center">
                                            <span class="icon-saudi_riyal"></span> {{ number_format((float)$openingBalance, 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="icon-saudi_riyal"></span> {{ number_format((float)$totalPurchases, 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="icon-saudi_riyal"></span> {{ number_format((float)$totalPayments, 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="icon-saudi_riyal"></span> {{ number_format((float)$remainingBalance, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection
