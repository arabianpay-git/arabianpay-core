@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header">
                            <h3 class="card-title">Assign Role</h3>
                        </div>

                        <form method="POST" action="{{ route('user-roles.update', $user->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="p-5">
                                <div class="grid gap-5">
                                    <div class="w-full">
                                        <label class="block mb-1 font-medium">Select Role</label>
                                        <select name="role_id" class="select">
                                            <option value="">-- Select Role --</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    {{ $user->department->hasRole($role->name) ? 'selected' : '' }}>
                                                    {{ ucfirst($role->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">Assign Role</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
