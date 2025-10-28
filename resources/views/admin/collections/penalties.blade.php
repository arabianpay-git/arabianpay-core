@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Penalties & Fees') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="#">
                        {{ translate('Create New Fee Rule') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Fee & Penalty Rules') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#penalties_table"
                                        placeholder="{{ translate('Search rules') }}" type="text" value="" />
                                </label>
                            </div>
                            <select class="select select-sm select-bordered">
                                <option value="">{{ translate('Filter by Type') }}</option>
                                <option value="late_fee">{{ translate('Late Fee') }}</option>
                                <option value="admin_fee">{{ translate('Admin Fee') }}</option>
                                <option value="waiver">{{ translate('Hardship Waiver') }}</option>
                            </select>
                            <select class="select select-sm select-bordered">
                                <option value="">{{ translate('Filter by Status') }}</option>
                                <option value="active">{{ translate('Active') }}</option>
                                <option value="inactive">{{ translate('Inactive') }}</option>
                                <option value="pending_approval">{{ translate('Pending Approval') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="penalties_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('Rule Name') }}</th>
                                            <th>{{ translate('Contract Type') }}</th>
                                            <th>{{ translate('Fee/Penalty') }}</th>
                                            <th>{{ translate('Maximum Cap') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $items = [
                                                (object) [
                                                    'name' => 'Standard Late Fee - Tier 1',
                                                    'contract' => 'Installment Loan',
                                                    'fee' => '5% of Due Amount',
                                                    'cap' => '€50.00',
                                                    'status' => 'Active',
                                                ],
                                                (object) [
                                                    'name' => 'Admin Processing Fee',
                                                    'contract' => 'All',
                                                    'fee' => '€10.00 Flat',
                                                    'cap' => 'N/A',
                                                    'status' => 'Active',
                                                ],
                                                (object) [
                                                    'name' => 'Hardship Waiver - May 2025',
                                                    'contract' => 'Installment Loan',
                                                    'fee' => '100% Waiver',
                                                    'cap' => 'N/A',
                                                    'status' => 'Pending Approval',
                                                ],
                                            ];
                                        @endphp

                                        @foreach ($items as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->contract }}</td>
                                                <td>{{ $item->fee }}</td>
                                                <td>{{ $item->cap }}</td>
                                                <td>
                                                    @php
                                                        $badge_class = match ($item->status) {
                                                            'Active' => 'badge-success',
                                                            'Inactive' => 'badge-secondary',
                                                            'Pending Approval' => 'badge-warning',
                                                            default => 'badge-secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge badge-sm badge-outline {{ $badge_class }}">{{ $item->status }}</span>
                                                </td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary" href="#">
                                                            <i class="ki-filled ki-eye"> </i>
                                                        </a>
                                                        @if ($item->status === 'Pending Approval')
                                                            <a class="btn btn-sm btn-icon btn-clear btn-success"
                                                                href="#">
                                                                <i class="ki-filled ki-check"> </i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{-- @include('layouts.includes.table-pagination', ['paginator' => $items]) --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
