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
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ translate('Add New Employee') }}
                            </h3>
                        </div>

                        <form action="{{ route('employees.store') }}" method="POST">
                            @csrf

                            <div class="card-body grid gap-5">

                                {{-- First / Last Name --}}
                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <label class="form-label">{{ translate('First Name') }} *</label>
                                        <input name="first_name" class="input" value="{{ old('first_name') }}" required>
                                    </div>
                                    <div class="w-full">
                                        <label class="form-label">{{ translate('Last Name') }} *</label>
                                        <input name="last_name" class="input" value="{{ old('last_name') }}" required>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    {{-- Email --}}
                                    <div class="w-full">
                                        <label class="form-label">{{ translate('Email') }} *</label>
                                        <input name="email" type="email" class="input" value="{{ old('email') }}"
                                            required>
                                    </div>

                                    {{-- Phone --}}
                                    <div class="w-full">
                                        <label class="form-label">{{ translate('Phone Number') }}</label>
                                        <input name="phone_number" class="input" value="{{ old('phone_number') }}">
                                    </div>
                                </div>


                                {{-- Department & Role --}}
                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <label class="form-label">{{ translate('Department') }} *</label>
                                        <select id="department_id" name="department_id" class="select w-full">
                                            <option value="">{{ translate('-- Select Department --') }}</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label">{{ translate('Role') }} *</label>
                                        <select id="role_id" name="role_id" class="select w-full"></select>
                                    </div>
                                </div>

                                {{-- Sensitive Permissions --}}
                                @include('admin.employees.permissions')

                                {{-- Is Manager --}}
                                <div class="">
                                    <label class="form-label">{{ translate('Is Manager') }}</label>
                                    <select name="is_manager" class="input">
                                        <option value="0" {{ old('is_manager') === '0' ? 'selected' : '' }}>No
                                        </option>
                                        <option value="1" {{ old('is_manager') === '1' ? 'selected' : '' }}>Yes
                                        </option>
                                    </select>
                                </div>

                                {{-- Passwords --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">{{ translate('Password') }}</label>
                                        <input type="password" name="password" class="input" value="Arabianpay@123"
                                            required>
                                    </div>
                                    <div>
                                        <label class="form-label">{{ translate('Confirm Password') }}</label>
                                        <input type="password" name="password_confirmation" class="input"
                                            value="Arabianpay@123" required>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <div class="flex justify-end">
                                    <button class="btn btn-primary">
                                        {{ translate('Save Changes') }}
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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.info('Employee form script loaded (DOMContentLoaded)');

            // ---------- Elements ----------
            const departmentSelect = document.getElementById('department_id');
            const roleSelect = document.getElementById('role_id');
            const sensitiveSelect = document.getElementById('sensitive_permissions');

            if (!departmentSelect) {
                console.error('departmentSelect not found (#department_id). Check your markup.');
            }

            if (!roleSelect) {
                console.error('roleSelect not found (#role_id). Check your markup.');
            }

            if (!sensitiveSelect) {
                console.error('sensitiveSelect not found (#sensitive_permissions). Check your markup.');
            }

            // ---------- Choices instances ----------
            const roleChoices = new Choices(roleSelect, {
                removeItemButton: false,
                maxItemCount: 1,
                shouldSort: false,
                searchEnabled: true,
                placeholderValue: 'Select Role',
            });

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
                    console.info('Fetched:', res);
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
                    console.info('No departmentId provided — clearing roles & cache');
                    roleChoices.clearChoices();
                    rolesCache = {};
                    return;
                }

                const url = `/admin/departments/${departmentId}/access`;

                doFetchJson(url)
                    .then(data => {
                        const roles = data.roles || [];
                        console.info(`Loaded ${roles.length} roles for department ${departmentId}`);

                        const choices = roles.map(role => {
                            // Cache role with sensitive permissions
                            rolesCache[String(role.id)] = {
                                id: role.id,
                                name: role.name,
                                sensitive_permissions: Array.isArray(role.sensitive_permissions) ?
                                    role.sensitive_permissions : []
                            };

                            return {
                                value: String(role.id),
                                label: role.name,
                                selected: selectedRole !== null && String(selectedRole) === String(role
                                    .id)
                            };
                        });

                        roleChoices.setChoices(choices, 'value', 'label', true);

                        if (selectedRole !== null) {
                            try {
                                roleChoices.setChoiceByValue(String(selectedRole));
                            } catch (e) {
                                console.warn('Could not set role by value:', e);
                            }

                            // If we have a selected role, update sensitive permissions
                            if (rolesCache[String(selectedRole)]) {
                                updateSensitivePermissions(rolesCache[String(selectedRole)]
                                    .sensitive_permissions);
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error loading department roles:', err);
                        roleChoices.clearChoices();
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
                    loadDepartmentData(this.value);
                });
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    console.info('Role changed:', this.value);
                    onRoleChanged(this.value);
                });
            }

            // ---------- Initialize on load ----------
            (function initOnLoad() {
                const oldDepartment = @json(old('department_id'));
                const oldRole = @json(old('role_id'));
                const oldSensitivePermissions = @json(old('sensitive_permissions', []));

                const departmentValue = oldDepartment || (departmentSelect ? departmentSelect.value : '');

                // If there are old sensitive permissions (from form validation), use them
                if (oldSensitivePermissions && oldSensitivePermissions.length > 0 && sensitiveChoices) {
                    updateSensitivePermissions(oldSensitivePermissions);
                }

                if (departmentValue) {
                    console.info('Initial load: department', departmentValue, 'role', oldRole);
                    loadDepartmentData(departmentValue, oldRole);
                } else {
                    console.info('No department preselected');
                }
            })();
        });
    </script>
@endpush
