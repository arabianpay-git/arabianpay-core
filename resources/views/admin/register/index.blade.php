@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ __('Risk Register') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('risk-register.create') }}">
                        {{ __('Create New Risk') }}
                    </a>
                </div>
            </div>

        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ __('Risks') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#refund_requests_table" placeholder="Search"
                                        type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="refund_requests_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">Risk ID</th>
                                            <th class="text-center">Description</th>
                                            <th class="text-center">Type</th>
                                            <th class="text-center">Entity</th>
                                            <th class="text-center">Score</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                            <th class="text-left">Owner</th>
                                            <th class="text-left">Review Date</th>
                                            <th class="text-left">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($risks as $item)
                                            <tr>
                                                <td class="text-center">{{ $item->risk_id }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">{{ $item->description ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="whitespace-nowrap">{{ $item->type ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    {{ $item->entity ?? '-' }}
                                                </td>
                                                <td>
                                                    {{ number_format($item->score, 2) ?? '-' }}
                                                </td>

                                                <td class="text-center capitalize">
                                                    <span
                                                        class="badge badge-sm badge-outline
                                                            @if ($item->status === 'low') badge-success
                                                            @elseif ($item->status === 'medium') badge-warning
                                                            @elseif ($item->status === 'high') badge-danger
                                                            @else badge-dark @endif">
                                                        {{ $item->status }}
                                                    </span>
                                                </td>

                                                <td>{{ $item->action ?? '-' }}</td>
                                                <td>{{ $item->user->first_name ?? '-' }} {{ $item->user->last_name }}</td>
                                                <td>{{ $item->created_at->format('d M Y') }}</td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('risk-register.edit', $item->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('risk-register.destroy', $item->id) }}">
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
                            @include('layouts.includes.table-pagination', ['paginator' => $risks])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
