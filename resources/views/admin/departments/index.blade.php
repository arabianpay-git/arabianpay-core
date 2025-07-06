@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Department') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('departments.create') }}">
                        {{ translate('Create New Department') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ translate('Departments') }}</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#role_permission_table"
                                        placeholder="{{ translate('Search departments') }}" type="text" />
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
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('Name') }}</th>
                                            <th>{{ translate('Member Count') }}</th>
                                            <th>{{ translate('Role') }}</th>
                                            <th>{{ translate('Permissions') }}</th>
                                            <th class="text-center">{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($departments as $department)
                                            <tr>
                                                <td class="text-center">{{ $department->id }}</td>
                                                <td>{{ $department->name ?? '-' }}</td>
                                                <td>{{ $department->users->count() }}</td>
                                                <td>
                                                    @if ($department->roles->count())
                                                        {{ $department->roles->pluck('name')->join(', ') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($department->permissions->isEmpty())
                                                        <span class="text-sm text-gray-600">-</span>
                                                    @else
                                                        @foreach ($department->permissions as $permission)
                                                            <span
                                                                class="badge badge-sm badge-success badge-outline me-1 mb-1 inline-block">
                                                                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name)) }}
                                                            </span>
                                                        @endforeach
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    <div class="flex gap-1 justify-center">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('departments.edit', $department->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('departments.destroy', $department->id) }}">
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
                            @include('layouts.includes.table-pagination', ['paginator' => $departments])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
