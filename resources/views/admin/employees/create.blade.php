@extends('layouts.base')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <style>
        /* Add your custom styles */
        .choices__inner {
            min-height: 2.4rem !important;
            height: 2.4rem !important;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            border-radius: 0.375rem;
        }

        .choices__input {
            height: auto !important;
            margin: 0 !important;
        }

        .choices__list--multiple .choices__item {
            border-radius: 0.375rem;
            font-size: 0.875rem;
            padding: 0 7px;
        }

        .choices__list {
            position: relative !important;
            z-index: 9999 !important;
        }

        .choices {
            position: relative !important;
        }
    </style>
@endpush
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

                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <label class="form-label" for="department_id">Department <span
                                                class="text-red-600">*</span></label>
                                        <select id="department_id" name="department_id"
                                            class="select w-full @error('department_id') border-red-500 @enderror">
                                            <option value="">-- Select Department --</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label" for="role_id">Role <span
                                                class="text-red-600">*</span></label>
                                        <select name="role_id" id="role_id" class="select w-full"></select>
                                        @error('role_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="w-full">
                                    <label class="form-label" for="permission_ids">Permissions</label>
                                    <select name="permission_ids[]" id="permission_ids" class="select w-full"
                                        multiple></select>
                                    @error('permission_ids')
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
                                                value="Arabianpay@123" required class="flex-1" />
                                            <button class="btn btn-icon" type="button">
                                                <i
                                                    class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                                                <i
                                                    class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="form-label text-gray-900">Confirm Password</label>
                                        <div class="input flex items-center gap-2" data-toggle-password="true">
                                            <input name="password_confirmation" placeholder="Re-enter Password"
                                                type="password" value="Arabianpay@123" required class="flex-1" />
                                            <button class="btn btn-icon" type="button">
                                                <i
                                                    class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
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

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        const roleSelect = document.getElementById('role_id');
        const permissionSelect = document.getElementById('permission_ids');
        const departmentSelect = document.getElementById('department_id');

        const roleChoices = new Choices(roleSelect, {
            removeItemButton: true,
            placeholderValue: 'Select Role',
            searchPlaceholderValue: 'Search Roles...',
            maxItemCount: 1, // single select
            shouldSort: false,
        });

        const permissionChoices = new Choices(permissionSelect, {
            removeItemButton: true,
            placeholderValue: 'Select Permissions',
            searchPlaceholderValue: 'Search Permissions...',
            allowHTML: false,
        });

        function clearChoices() {
            roleChoices.clearStore();
            roleChoices.clearChoices();
            roleChoices.setChoiceByValue([]);

            permissionChoices.clearStore();
            permissionChoices.clearChoices();
            permissionChoices.setChoiceByValue([]);
        }

        function loadDepartmentData(departmentId, selectedRole = null, selectedPermissions = []) {
            if (!departmentId) return;

            clearChoices();

            fetch(`/admin/departments/${departmentId}/access`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    roleChoices.setChoices(
                        data.roles.map(role => ({
                            value: role.id,
                            label: role.name,
                            selected: selectedRole == role.id
                        })),
                        'value', 'label', true
                    );

                    permissionChoices.setChoices(
                        data.permissions.map(perm => ({
                            value: perm.id,
                            label: perm.name.replaceAll('.', ' ').split(' ').map(w => w.charAt(0)
                                .toUpperCase() + w.slice(1)).join(' '),
                            selected: selectedPermissions.includes(perm.id)
                        })),
                        'value', 'label', true
                    );
                })
                .catch(err => console.error('Error loading roles/permissions:', err));
        }

        departmentSelect.addEventListener('change', function() {
            clearChoices();
            loadDepartmentData(this.value);
        });

        // On page load, load roles/permissions if department is selected with old values
        if (departmentSelect.value) {
            const selectedRole = @json(old('role_id'));
            const selectedPermissions = @json(old('permission_ids', []));
            loadDepartmentData(departmentSelect.value, selectedRole, selectedPermissions);
        }
    </script>
@endpush
