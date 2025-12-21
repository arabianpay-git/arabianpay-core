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
        // Elements
        const departmentSelect = document.getElementById('department_id');
        const roleSelect = document.getElementById('role_id');

        // Initialize Choices on selects
        const roleChoices = new Choices(roleSelect, {
            removeItemButton: false,
            maxItemCount: 1,
            shouldSort: false,
            searchEnabled: true,
            placeholderValue: 'Select Role',
        });

        // Load roles for selected department
        function loadDepartmentData(departmentId, selectedRole = null) {
            if (!departmentId) {
                // clear roles if no department
                roleChoices.clearChoices();
                return;
            }

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
                    // Map roles, ensuring string values and marking selected one
                    const choices = (data.roles || []).map(role => ({
                        value: String(role.id),
                        label: role.name,
                        selected: selectedRole !== null && String(selectedRole) === String(role.id)
                    }));

                    // replace choices entirely
                    roleChoices.setChoices(choices, 'value', 'label', true);

                    // If a selectedRole is provided but Choices didn't pre-select it (fallback), set it explicitly
                    if (selectedRole !== null && choices.some(c => c.selected)) {
                        try {
                            roleChoices.setChoiceByValue(String(selectedRole));
                        } catch (e) {
                            // ignore if setChoiceByValue not available in this Choices version
                        }
                    }
                })
                .catch(err => {
                    console.error('Error loading roles:', err);
                    roleChoices.clearChoices();
                });
        }

        // department change handler
        departmentSelect.addEventListener('change', function() {
            loadDepartmentData(this.value);
        });

        // On page load: if there was old input, load roles and select the old role
        (function initOnLoad() {
            const oldDepartment = @json(old('department_id'));
            const oldRole = @json(old('role_id'));

            const departmentValue = (oldDepartment && oldDepartment !== '') ? oldDepartment : (departmentSelect.value ||
                '');

            if (departmentValue) {
                loadDepartmentData(departmentValue, oldRole ?? null);
            }
        })();
    </script>
@endpush
