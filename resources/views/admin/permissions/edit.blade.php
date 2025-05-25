@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header">
                        <h3 class="card-title">Edit Permission</h3>
                    </div>

                    <form action="{{ route('permissions.update', $permission->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-5">
                            <div class="grid gap-5">
                                <div class="w-full">
                                    <label class="form-label">Permission Name</label>
                                    <input class="input @error('name') border-red-500 @enderror"
                                           name="name"
                                           type="text"
                                           value="{{ old('name', $permission->name) }}"
                                           required />
                                    @error('name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
