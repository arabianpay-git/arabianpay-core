@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>

    <!-- Container -->
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ $type }} Payments
                </h1>
            </div>
        </div>
    </div>

    <!-- Container -->
    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        {{ $type }} Payments
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
                                        <th class="w-[60px] text-center">ID</th>
                                        <th class="text-left">Instalment Number</th>
                                        <th class="text-left">Due Date</th>
                                        <th class="text-left">Instalment Amount</th>
                                        <th class="text-left">Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($schedulePayments as $index => $item)
                                    <tr>
                                        <td class="text-center">{{ number_format($index + 1) }}</td>
                                        <td class="text-center">
                                            <small>{{ $item->uuid }}</small>
                                        </td>
                                        <td>{{ $item->instalment_number }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->due_date)->format('d-m-Y') }}</td>
                                        <td>{{ number_format($item->instalment_amount, 2) }} SAR</td>
                                        <td>
                                            <span class="badge badge-sm badge-outline 
                                                @if($item->payment_status == 'paid') badge-success 
                                                @elseif($item->payment_status == 'pending') badge-warning 
                                                @elseif($item->payment_status == 'late') badge-danger 
                                                @elseif($item->payment_status == 'failed') badge-secondary 
                                                @else badge-info 
                                                @endif">
                                                {{ ucfirst($item->payment_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $schedulePayments])
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
