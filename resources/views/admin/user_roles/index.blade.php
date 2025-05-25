@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    User Roles
                </h1>
            </div>
        </div>
    </div>

    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">User Roles</h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"></i>
                                <input data-datatable-search="#role_permission_table" placeholder="Search users" type="text" />
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
                                        <th>User</th>
                                        <th>Roles</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $index => $user)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $user->first_name ?? '-' }} {{ $user->last_name }}</td>
                                            <td>
                                                @forelse($user->roles as $role)
                                                    <span class="badge badge-sm badge-info badge-outline me-1 mb-1 inline-block">
                                                        {{ ucfirst($role->name) }}
                                                    </span>
                                                @empty
                                                    <span class="text-sm text-gray-600">-</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                <div class="flex gap-1">
                                                    <a class="btn btn-sm btn-icon btn-clear btn-primary" href="{{ route('user-roles.edit', $user->id) }}">
                                                        <i class="ki-filled ki-notepad-edit"></i>
                                                    </a>                                                    
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @include('layouts.includes.table-pagination', ['paginator' => $users])
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
