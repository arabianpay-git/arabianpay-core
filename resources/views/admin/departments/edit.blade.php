@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">Edit Department</h3>
                        </div>
                        <form action="{{ route('departments.update', $department->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="card-body grid gap-5">
                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1 max-w-56" for="name">
                                        Name <span class="text-red-600">*</span>
                                    </label>

                                    @php
                                        try {
                                            $decryptedName = decrypt($department->name);
                                        } catch (\Exception $e) {
                                            $decryptedName = $department->name; // fallback
                                        }
                                    @endphp

                                    <input id="name" class="input @error('name') border-red-500 @enderror"
                                        name="name" type="text" value="{{ old('name', $decryptedName) }}" required />

                                    @error('name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1 max-w-56" for="role">
                                        Role <span class="text-red-600">*</span>
                                    </label>
                                    <select name="role" id="role"
                                        class="select w-full @error('role') border-red-500 @enderror">
                                        <option value="">-- Select Role --</option>
                                        @foreach ($roles as $item)
                                            <option value="{{ $item->id }}"
                                                {{ old('role', $department->roles->first()?->id) == $item->id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex justify-end pt-2.5">
                                    <button type="submit" class="btn btn-primary">
                                        Update Department
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
