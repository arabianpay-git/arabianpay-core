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
                        {{ translate('Supplier Roles') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('supplier_roles.create') }}">
                        {{ translate('Create New Supplier Role') }}
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
                            {{ translate('Supplier Roles List') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#supplier_roles_table"
                                        placeholder="{{ translate('Search roles') }}" type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="supplier_roles_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">
                                                {{ translate('No') }}
                                            </th>
                                            <th>
                                                <span class="sort asc">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Name') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Permissions') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Created At') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th>
                                                <span class="sort">
                                                    <span class="sort-label font-normal text-gray-700">
                                                        {{ translate('Action') }}
                                                    </span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($roles as $index => $role)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>{{ $role->name }}</td>
                                                <td>
                                                    @if (!empty($role->permissions) && is_array($role->permissions))
                                                        @foreach ($role->permissions as $permission)
                                                            <span
                                                                class="badge badge-sm badge-success badge-outline me-1 mb-1 inline-block">
                                                                {{ translate(ucwords(str_replace('_', ' ', $permission))) }}
                                                            </span>
                                                        @endforeach
                                                    @else
                                                        <span
                                                            class="text-gray-500 text-sm">{{ translate('No permissions') }}</span>
                                                    @endif

                                                </td>
                                                <td>{{ $role->created_at->format('d M Y') }}</td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('supplier_roles.edit', $role->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"> </i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('supplier_roles.destroy', $role->id) }}">
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
                            @include('layouts.includes.table-pagination', ['paginator' => $roles])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
