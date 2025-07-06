@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <form method="POST" action="{{ route('role-permissions.update', [$role->id, $department->id]) }}"
                class="space-y-6">
                @csrf
                @method('PUT')

                <input type="hidden" name="role_id" value="{{ $role->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="role_id_display" class="block text-sm font-medium text-gray-700">
                            {{ translate('Selected Role') }}
                        </label>
                        <select id="role_id_display" class="select w-full mt-1 bg-gray-100 cursor-not-allowed" disabled>
                            <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">
                            {{ translate('Select Department') }}
                        </label>
                        <select name="department_id" id="department_id" class="select w-full mt-1" required>
                            <option value="">{{ translate('-- Select Department --') }}</option>
                            @foreach ($departments as $item)
                                <option value="{{ $item->id }}" @if ($item->id == $department->id) selected @endif>
                                    {{ ucfirst($item->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="permissions_wrapper" class="grid gap-5 pt-6"></div>

                <div class="pt-5">
                    <button type="submit" class="btn btn-primary">
                        {{ translate('Update Permissions') }}
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        function loadPermissions(departmentId, roleId) {
            const wrapper = document.getElementById('permissions_wrapper');
            wrapper.innerHTML = '';
            if (departmentId && roleId) {
                fetch(`/admin/permissions-by-department/${departmentId}/${roleId}`)
                    .then(res => res.text())
                    .then(html => wrapper.innerHTML = html);
            }
        }

        document.getElementById('department_id').addEventListener('change', function() {
            loadPermissions(this.value, "{{ $role->id }}");
        });

        // On page load, check if a department is already selected, then load permissions
        document.addEventListener('DOMContentLoaded', function() {
            const departmentSelect = document.getElementById('department_id');
            if (departmentSelect.value) {
                loadPermissions(departmentSelect.value, "{{ $role->id }}");
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('select-all-perms')) {
                const targetGroup = e.target.getAttribute('data-target');
                const checkboxes = document.querySelectorAll(`.${targetGroup} input[type="checkbox"]`);
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            }
        });
    </script>
@endpush
