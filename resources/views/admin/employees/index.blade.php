@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        Employees
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('employees.create') }}">
                        Create New Employee
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">Employees</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#role_permission_table" placeholder="Search employees"
                                        type="text" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="role_permission_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">No</th>
                                            <th>Name</th>
                                            <th>Is Manager</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Department</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employees as $index => $employee)
                                            <tr>
                                                <td class="text-center">{{ $index + $employees->firstItem() }}</td>
                                                <td>{{ $employee->first_name ?? '-' }} {{ $employee->last_name ?? '' }}</td>
                                                <td class="text-center">
                                                    @if ($employee->is_manager)
                                                        <span class="badge badge-sm badge-outline badge-success">Yes</span>
                                                    @else
                                                        <span class="badge badge-outline badge-secondary">No</span>
                                                    @endif
                                                </td>
                                                <td>{{ $employee->email }}</td>
                                                <td>{{ $employee->phone_number ?? '-' }}</td>
                                                <td>{{ $employee->department ?? '-' }}</td>
                                                <td class="text-center">
                                                    <div class="flex gap-1 justify-center">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('employees.edit', $employee->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('employees.destroy', $employee->id) }}">
                                                            <i class="ki-filled ki-trash"> </i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @include('layouts.includes.table-pagination', ['paginator' => $employees])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
