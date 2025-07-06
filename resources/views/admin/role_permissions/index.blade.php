@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Role Permissions') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('role-permissions.create') }}">
                        {{ translate('Create New Role Permission') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ translate('Roles') }}</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#role_permission_table"
                                        placeholder="{{ translate('Search roles') }}" type="text" value="" />
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
                                            <th>{{ translate('Department') }}</th>
                                            <th>{{ translate('Role') }}</th>
                                            <th>{{ translate('Permissions') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($roles as $role)
                                            @php
                                                $permissionIds = $role->permissions->pluck('id')->toArray();

                                                $departmentPermissionMap = DB::table('department_has_permissions')
                                                    ->whereIn('permission_id', $permissionIds)
                                                    ->get()
                                                    ->groupBy('department_id');
                                            @endphp

                                            @foreach ($departmentPermissionMap as $departmentId => $permissionsGroup)
                                                @php
                                                    $department = $departments[$departmentId] ?? null;
                                                    $permIds = collect($permissionsGroup)
                                                        ->pluck('permission_id')
                                                        ->toArray();
                                                    $permissions = $role->permissions->whereIn('id', $permIds);
                                                @endphp
                                                @if ($department)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                                                        <td>{{ ucfirst($department->name) }}</td>
                                                        <td>{{ ucfirst($role->name) }}</td>
                                                        <td>
                                                            @foreach ($permissions as $permission)
                                                                <span
                                                                    class="badge badge-sm badge-success badge-outline me-1 mb-1 inline-block">
                                                                    {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name)) }}
                                                                </span>
                                                            @endforeach
                                                        </td>
                                                        <td>
                                                            <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                                href="{{ route('role-permissions.edit', [$role->id, $department->id]) }}">
                                                                <i class="ki-filled ki-notepad-edit"></i>
                                                            </a>

                                                            <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                                href="{{ route('role-permissions.destroy', [$role->id, $department->id]) }}">
                                                                <i class="ki-filled ki-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            @include('layouts.includes.table-pagination', ['paginator' => $roles])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
