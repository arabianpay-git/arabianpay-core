@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                Add New Employee
                            </h3>
                        </div>
                        <form action="{{ route('employees.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">

                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="first_name">
                                            First Name <span class="text-red-600">*</span>
                                        </label>
                                        <input id="first_name" class="input @error('first_name') border-red-500 @enderror"
                                            name="first_name" type="text" value="{{ old('first_name') }}" required />
                                        @error('first_name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="last_name">
                                            Last Name <span class="text-red-600">*</span>
                                        </label>
                                        <input id="last_name" class="input @error('last_name') border-red-500 @enderror"
                                            name="last_name" type="text" value="{{ old('last_name') }}" required />
                                        @error('last_name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1 max-w-56" for="email">
                                        Email <span class="text-red-600">*</span>
                                    </label>
                                    <input id="email" class="input @error('email') border-red-500 @enderror"
                                        name="email" type="email" value="{{ old('email') }}" required />
                                    @error('email')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Phone Number -->
                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1 max-w-56" for="phone_number">
                                        Phone Number
                                    </label>
                                    <input id="phone_number" class="input @error('phone_number') border-red-500 @enderror"
                                        name="phone_number" type="text" value="{{ old('phone_number') }}" />
                                    @error('phone_number')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Department -->
                                <div class="w-full mb-4">
                                    <label class="form-label flex items-center gap-1 max-w-56" for="department">
                                        Department
                                    </label>
                                    <input id="department" class="input @error('department') border-red-500 @enderror"
                                        name="department" type="text" value="{{ old('department') }}" />
                                    @error('department')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Is Manager -->
                                <div class="w-full">
                                    <label for="is_manager" class="form-label">
                                        Is Manager
                                    </label>
                                    <select id="is_manager" name="is_manager"
                                        class="input @error('is_manager') border-red-500 @enderror">
                                        <option value="0" {{ old('is_manager') === '0' ? 'selected' : '' }}>No
                                        </option>
                                        <option value="1" {{ old('is_manager') === '1' ? 'selected' : '' }}>Yes
                                        </option>
                                    </select>
                                    @error('is_manager')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>



                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="form-label text-gray-900">Password</label>
                                        <div class="input flex items-center gap-2" data-toggle-password="true">
                                            <input name="password" placeholder="Enter Password" type="password"
                                                value="arabianpay@123" required class="flex-1" />
                                            <button class="btn btn-icon" type="button">
                                                <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                                                <i
                                                    class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="form-label text-gray-900">Confirm Password</label>
                                        <div class="input flex items-center gap-2" data-toggle-password="true">
                                            <input name="password_confirmation" placeholder="Re-enter Password"
                                                type="password" value="arabianpay@123" required class="flex-1" />
                                            <button class="btn btn-icon" type="button">
                                                <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                                                <i
                                                    class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2.5">
                                    <button type="submit" class="btn btn-primary">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll('[data-toggle-password="true"]').forEach(wrapper => {
            const input = wrapper.querySelector('input');
            const toggleButton = wrapper.querySelector('button');
            const eyeOpen = toggleButton.querySelector('.ki-eye');
            const eyeSlash = toggleButton.querySelector('.ki-eye-slash');

            toggleButton.addEventListener('click', () => {
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';

                eyeOpen.classList.toggle('hidden', !isVisible);
                eyeSlash.classList.toggle('hidden', isVisible);
            });
        });
    </script>
@endpush
