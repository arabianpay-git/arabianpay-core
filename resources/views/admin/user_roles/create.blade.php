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

                        <form method="POST" action="{{ route('user-roles.store') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="block mb-1 font-medium">Select User</label>
                                <select name="user_id" class="form-select w-full">
                                    <option value="">-- Select User --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->first_name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-1 font-medium">Select Role</label>
                                <select name="role_id" class="form-select w-full">
                                    <option value="">-- Select Role --</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Assign Role</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
