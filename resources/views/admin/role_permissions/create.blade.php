@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <form id="role-permissions-form" action="{{ route('role-permissions.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="role_id" class="block text-sm font-medium text-gray-700">Select Role</label>
                        <select name="role_id" id="role_id" class="select w-full mt-1" required>
                            <option value="">-- Select Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">Select Department</label>
                        <select name="department_id" id="department_id" class="select w-full mt-1" required>
                            <option value="">-- Select Department --</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ ucfirst($department->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="permissions_wrapper" class="grid gap-5 pt-6"></div>

                <div class="pt-5">
                    <button type="submit" class="btn btn-primary">
                        Update Permissions
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.getElementById('department_id').addEventListener('change', function() {
            const departmentId = this.value;
            const roleId = document.getElementById('role_id').value;
            const wrapper = document.getElementById('permissions_wrapper');
            wrapper.innerHTML = '';

            if (departmentId && roleId) {
                fetch(`/admin/permissions-by-department/${departmentId}/${roleId}`)
                    .then(res => res.text())
                    .then(html => wrapper.innerHTML = html);
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
