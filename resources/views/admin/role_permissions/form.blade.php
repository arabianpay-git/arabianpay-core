@extends('layouts.base') @section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>

    <!-- Container -->
    <div class="container-fixed">
        <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Edit <span class="text-primary text-xl">{{ ucfirst($role->name) }}</span> Permissions
                </h1>
            </div>
        </div>
    </div>

    <!-- Container -->
    <div class="container-fixed">
        <form action="{{ route('role-permissions.update', $role->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="role_id" value="{{ $role->id }}">
            <div class="grid gap-5 lg:gap-7.5">
                @foreach($permissions as $subject => $perms)
                    <div class="card card-grid min-w-full">
                        <div class="card-header border-b border-gray-200">
                            <h3 class="card-title font-medium text-base text-gray-800">
                                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', ucfirst($subject))) }}
                            </h3>
                        </div>
                        <div class="card-body flex flex-wrap gap-7" style="padding: 0.725rem;">
                            @foreach($perms as $perm)
                            @php
                                $actionName = Str::headline($perm->name);
                                $isChecked  = isset($role) && $role->hasPermissionTo($perm->name);
                            @endphp
                            <div class="flex items-center gap-2 p-2">
                                <span class="text-sm font-medium text-gray-700">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $actionName)) }}</span>
                                <label class="switch">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $perm->name }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-5">
                <button type="submit" class="btn btn-primary">
                    Update Permissions
                </button>
            </div>
        </form>
    </div>
</main>
@endsection
