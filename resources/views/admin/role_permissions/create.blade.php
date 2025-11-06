@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <form id="role-permissions-form" action="{{ route('role-permissions.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">
                            {{ translate('Select Department') }}
                        </label>
                        <select name="department_id" id="department_id" class="select w-full mt-1" required>
                            <option value="">{{ translate('-- Select Department --') }}</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ ucfirst($department->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="role_id" class="block text-sm font-medium text-gray-700">
                            {{ translate('Select Role') }}
                        </label>
                        <select name="role_id" id="role_id" class="select w-full mt-1" required disabled>
                            <option value="">{{ translate('-- Select Role --') }}</option>
                            {{-- Roles will be loaded dynamically --}}
                        </select>
                    </div>
                </div>

                <div id="permissions_wrapper" class="grid gap-5 pt-6"></div>

                <div class="pt-5">
                    <button type="submit" class="btn btn-primary" disabled id="submit_btn">
                        {{ translate('Update Permissions') }}
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        const departmentSelect = document.getElementById('department_id');
        const roleSelect = document.getElementById('role_id');
        const permissionsWrapper = document.getElementById('permissions_wrapper');
        const submitBtn = document.getElementById('submit_btn');

        departmentSelect.addEventListener('change', function() {
            const departmentId = this.value;
            roleSelect.innerHTML = '<option value="">-- Select Role --</option>';
            permissionsWrapper.innerHTML = '';
            submitBtn.disabled = true;
            roleSelect.disabled = true;

            if (!departmentId) return;

            // Fetch roles assigned to the selected department
            fetch(`/admin/roles-by-department/${departmentId}`)
                .then(res => res.json())
                .then(roles => {
                    if (roles.length > 0) {
                        roles.forEach(role => {
                            const option = document.createElement('option');
                            option.value = role.id;
                            option.textContent = role.name.charAt(0).toUpperCase() + role.name.slice(1);
                            roleSelect.appendChild(option);
                        });
                        roleSelect.disabled = false;
                    } else {
                        roleSelect.disabled = true;
                    }
                });
        });

        roleSelect.addEventListener('change', function() {
            const roleId = this.value;
            const departmentId = departmentSelect.value;

            permissionsWrapper.innerHTML = '';
            submitBtn.disabled = true;

            if (roleId && departmentId) {
                fetch(`/admin/permissions-by-department/${departmentId}/${roleId}`)
                    .then(res => res.text())
                    .then(html => {
                        permissionsWrapper.innerHTML = html;
                        submitBtn.disabled = false;
                    });
            }
        });

        // Optional: Select All permissions checkbox functionality (keep your existing code)
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('select-all-perms')) {
                const targetGroup = e.target.getAttribute('data-target');
                const checkboxes = document.querySelectorAll(`.${targetGroup} input[type="checkbox"]`);
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            }
        });
    </script>
@endpush
