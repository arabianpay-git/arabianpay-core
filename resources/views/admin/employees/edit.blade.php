@extends('layouts.base')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <style>
        .choices {
            position: relative !important;
        }

        .choices__inner {
            min-height: 2.6rem !important;
            max-height: 120px;
            overflow-y: auto;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            z-index: 1;
        }

        .choices__list--multiple .choices__item {
            border-radius: 0.375rem;
            font-size: 0.875rem;
            padding: 2px 8px;
            margin: 2px;
        }

        .choices__list--dropdown,
        .choices__list[aria-expanded="true"] {
            position: absolute !important;
            top: 100%;
            left: 0;
            width: 100%;
            z-index: 99999 !important;
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
                                {{ translate('Edit Employee') }}
                            </h3>
                        </div>
                        <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body grid gap-5">
                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="first_name">
                                            {{ translate('First Name') }} <span class="text-red-600">*</span>
                                        </label>
                                        <input id="first_name" class="input @error('first_name') border-red-500 @enderror"
                                            name="first_name" type="text"
                                            value="{{ old('first_name', $employee->first_name) }}" required />
                                        @error('first_name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="last_name">
                                            {{ translate('Last Name') }} <span class="text-red-600">*</span>
                                        </label>
                                        <input id="last_name" class="input @error('last_name') border-red-500 @enderror"
                                            name="last_name" type="text"
                                            value="{{ old('last_name', $employee->last_name) }}" required />
                                        @error('last_name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <!-- Email -->
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="email">
                                            {{ translate('Email') }} <span class="text-red-600">*</span>
                                        </label>
                                        <input id="email" class="input @error('email') border-red-500 @enderror"
                                            name="email" type="email" value="{{ old('email', $employee->email) }}"
                                            required />
                                        @error('email')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="phone_number">
                                            {{ translate('Phone Number') }}
                                        </label>
                                        <input id="phone_number"
                                            class="input @error('phone_number') border-red-500 @enderror"
                                            name="phone_number" type="text"
                                            value="{{ old('phone_number', $employee->phone_number) }}" />
                                        @error('phone_number')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1 max-w-56" for="department_id">
                                            {{ translate('Department') }} <span class="text-red-600">*</span>
                                        </label>
                                        <select id="department_id" name="department_id"
                                            class="select w-full @error('department_id') border-red-500 @enderror">
                                            <option value="">{{ translate('-- Select Department --') }}</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Role select (plain select) -->
                                    <div class="w-full">
                                        <label class="form-label" for="role_id">{{ translate('Role') }} <span
                                                class="text-red-600">*</span></label>
                                        <select name="role_id" id="role_id" class="input w-full">
                                            <option value="">{{ translate('-- Select Role --') }}</option>
                                        </select>

                                        @error('role_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Sensitive Permissions --}}
                                @include('admin.employees.permissions', [
                                    'selected' => old(
                                        'sensitive_permissions',
                                        $employee->sensitive_permissions ?? []),
                                ])

                                <div class="w-full">
                                    <label for="is_manager" class="form-label">
                                        {{ translate('Is Manager') }}
                                    </label>
                                    <select id="is_manager" name="is_manager"
                                        class="input @error('is_manager') border-red-500 @enderror">
                                        <option value="0"
                                            {{ old('is_manager', $employee->is_manager) == 0 ? 'selected' : '' }}>
                                            {{ translate('No') }}
                                        </option>
                                        <option value="1"
                                            {{ old('is_manager', $employee->is_manager) == 1 ? 'selected' : '' }}>
                                            {{ translate('Yes') }}
                                        </option>
                                    </select>
                                    @error('is_manager')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="form-label text-gray-900">{{ translate('Password') }}</label>
                                        <div class="input flex items-center gap-2" data-toggle-password="true">
                                            <input name="password"
                                                placeholder="{{ translate('Enter Password (leave blank to keep)') }}"
                                                type="password" class="flex-1" />
                                            <button class="btn btn-icon" type="button">
                                                <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"></i>
                                                <i
                                                    class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label
                                            class="form-label text-gray-900">{{ translate('Confirm Password') }}</label>
                                        <div class="input flex items-center gap-2" data-toggle-password="true">
                                            <input name="password_confirmation"
                                                placeholder="{{ translate('Re-enter Password') }}" type="password"
                                                class="flex-1" />
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
                                        {{ translate('Update Employee') }}
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
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        // Password visibility toggle
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.info('Employee edit form script loaded');

            // ---------- Elements ----------
            const departmentSelect = document.getElementById('department_id');
            const roleSelect = document.getElementById('role_id');
            const sensitiveSelect = document.getElementById('sensitive_permissions');

            // Initialize sensitive choices
            let sensitiveChoices = null;
            if (sensitiveSelect) {
                if (window.sensitiveChoices) {
                    try {
                        window.sensitiveChoices.destroy();
                    } catch (e) {
                        /* ignore */
                    }
                    window.sensitiveChoices = null;
                }

                sensitiveChoices = new Choices(sensitiveSelect, {
                    removeItemButton: true,
                    shouldSort: false,
                    duplicateItemsAllowed: false,
                    searchEnabled: true,
                    placeholderValue: 'Select Sensitive Permissions',
                });
                window.sensitiveChoices = sensitiveChoices;
            }

            // ---------- Cache for roles ----------
            let rolesCache = {};

            // ---------- Fetch wrapper ----------
            const doFetchJson = (url) => {
                console.info('Fetching:', url);
                return fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                }).then(res => {
                    if (!res.ok) {
                        const msg = `Fetch failed ${res.status} ${res.statusText} for ${url}`;
                        console.warn(msg);
                        throw new Error(msg);
                    }
                    return res.json();
                });
            };

            // ---------- Function to update sensitive permissions ----------
            function updateSensitivePermissions(permissionsArray) {
                if (!sensitiveChoices || !sensitiveSelect) {
                    console.warn('Sensitive permissions select not available');
                    return;
                }

                // Clear current selections
                sensitiveChoices.removeActiveItems();

                // Add new selections
                if (Array.isArray(permissionsArray) && permissionsArray.length > 0) {
                    permissionsArray.forEach(permission => {
                        // Find the option by value
                        const option = Array.from(sensitiveSelect.options).find(opt => opt.value ===
                            permission);
                        if (option) {
                            sensitiveChoices.setChoiceByValue(permission);
                        }
                    });
                }

                console.info('Updated sensitive permissions:', permissionsArray);
            }

            // ---------- Populate roles ----------
            function loadDepartmentData(departmentId, selectedRole = null) {
                if (!departmentId) {
                    console.info('No departmentId provided — clearing roles');
                    roleSelect.innerHTML = '<option value="">-- Select Role --</option>';
                    rolesCache = {};
                    return;
                }

                const url = `/admin/departments/${departmentId}/access`;

                doFetchJson(url)
                    .then(data => {
                        const roles = data.roles || [];
                        console.info(`Loaded ${roles.length} roles for department ${departmentId}`);

                        // Clear current options except the first one
                        roleSelect.innerHTML = '<option value="">-- Select Role --</option>';

                        // Populate roles
                        roles.forEach(role => {
                            // Cache role with sensitive permissions
                            rolesCache[String(role.id)] = {
                                id: role.id,
                                name: role.name,
                                sensitive_permissions: Array.isArray(role.sensitive_permissions) ?
                                    role.sensitive_permissions : []
                            };

                            const option = document.createElement('option');
                            option.value = role.id;
                            option.textContent = role.name;

                            // Check if this is the selected role
                            if (selectedRole && String(selectedRole) === String(role.id)) {
                                option.selected = true;
                            }

                            roleSelect.appendChild(option);
                        });

                        // If we have a selected role, update sensitive permissions
                        if (selectedRole && rolesCache[String(selectedRole)]) {
                            console.log('Updating sensitive permissions from selected role:', selectedRole);
                            updateSensitivePermissions(rolesCache[String(selectedRole)].sensitive_permissions);
                        }
                    })
                    .catch(err => {
                        console.error('Error loading department roles:', err);
                        roleSelect.innerHTML = '<option value="">-- Select Role --</option>';
                        rolesCache = {};
                    });
            }

            // ---------- Role change handler ----------
            function onRoleChanged(roleId) {
                if (!roleId) {
                    console.info('Role cleared, removing sensitive selections');
                    updateSensitivePermissions([]);
                    return;
                }

                // Check cache first
                if (rolesCache[String(roleId)]) {
                    console.info('Using cached sensitive_permissions for role', roleId);
                    // When role changes, ALWAYS override with role's sensitive permissions
                    updateSensitivePermissions(rolesCache[String(roleId)].sensitive_permissions);
                    return;
                }

                // Fallback: fetch the role directly if not in cache
                const url = `/admin/roles/${roleId}`;
                doFetchJson(url)
                    .then(data => {
                        const perms = Array.isArray(data.sensitive_permissions) ? data.sensitive_permissions :
                        [];

                        // Cache the role
                        rolesCache[String(data.id)] = {
                            id: data.id,
                            name: data.name || '',
                            sensitive_permissions: perms
                        };

                        // When role changes, ALWAYS override with role's sensitive permissions
                        updateSensitivePermissions(perms);
                    })
                    .catch(err => {
                        console.error('Error fetching single role:', err);
                        updateSensitivePermissions([]);
                    });
            }

            // ---------- Event listeners ----------
            if (departmentSelect) {
                departmentSelect.addEventListener('change', function() {
                    console.info('Department changed:', this.value);
                    // When department changes, clear role and sensitive permissions
                    roleSelect.innerHTML = '<option value="">-- Select Role --</option>';
                    updateSensitivePermissions([]);

                    if (this.value) {
                        loadDepartmentData(this.value);
                    }
                });
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    console.info('Role changed:', this.value);
                    // When role changes, always override sensitive permissions with role's permissions
                    onRoleChanged(this.value);
                });
            }

            // ---------- Initialize on load ----------
            (function initOnLoad() {
                const oldDepartment = @json(old('department_id'));
                const oldRole = @json(old('role_id'));
                const oldSensitivePermissions = @json(old('sensitive_permissions', []));

                // Use old input or current employee data for department
                const departmentValue = oldDepartment || (departmentSelect ? departmentSelect.value : '');

                // For role, check old input first, then employee's current role
                let roleValue = oldRole;
                if (!roleValue && @json($employee->roles->isNotEmpty())) {
                    // Get the employee's current role ID
                    roleValue = @json($employee->roles->first()->id);
                    console.log('Using employee current role:', roleValue);
                }

                // If there are old sensitive permissions (from form validation), use them
                // Otherwise use employee's current sensitive permissions
                let initialSensitivePermissions = oldSensitivePermissions;
                if (!initialSensitivePermissions || initialSensitivePermissions.length === 0) {
                    initialSensitivePermissions = @json($employee->sensitive_permissions ?? []);
                }

                // Apply initial sensitive permissions
                if (sensitiveChoices) {
                    updateSensitivePermissions(initialSensitivePermissions);
                }

                if (departmentValue) {
                    console.info('Initial load: department', departmentValue, 'role', roleValue);
                    loadDepartmentData(departmentValue, roleValue);
                } else {
                    console.info('No department preselected');
                    // Still load roles if department is already selected in the dropdown
                    if (departmentSelect.value) {
                        loadDepartmentData(departmentSelect.value, roleValue);
                    }
                }
            })();
        });
    </script>
@endpush
