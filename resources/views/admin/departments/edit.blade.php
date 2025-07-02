@extends('layouts.base')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <style>
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
                                            $decryptedName = $department->name;
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
                                        Roles <span class="text-red-600">*</span>
                                    </label>
                                    <select name="role[]" id="role" class="select w-full" multiple>
                                        @foreach ($roles as $item)
                                            <option value="{{ $item->id }}"
                                                {{ in_array($item->id, old('role', $department->roles->pluck('id')->toArray())) ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                @foreach ($permissions as $subject => $perms)
                                    <div class="card card-grid min-w-full">
                                        <div class="card-header border-b border-gray-200 flex items-center justify-between">
                                            <h3 class="card-title font-medium text-base text-gray-800">
                                                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', ucfirst($subject))) }}
                                            </h3>
                                            <label class="flex items-center gap-1 text-sm switch">
                                                <input type="checkbox" class="select-all-perms"
                                                    data-target="perm-group-{{ $loop->index }}">
                                                Select All
                                            </label>
                                        </div>

                                        <div class="card-body flex flex-wrap gap-7 perm-group-{{ $loop->index }}"
                                            style="padding: 0.725rem;">
                                            @foreach ($perms as $perm)
                                                @php
                                                    $actionName = Str::headline($perm->name);
                                                    $isChecked =
                                                        isset($department) &&
                                                        $department->permissions->contains($perm->id);
                                                @endphp
                                                <div class="flex items-center gap-2 p-2">
                                                    <span class="text-sm font-medium text-gray-700">
                                                        {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $actionName)) }}
                                                    </span>
                                                    <label class="switch">
                                                        <input type="checkbox" name="permissions[]" class="perm-checkbox"
                                                            data-permission-id="{{ $perm->id }}"
                                                            value="{{ $perm->id }}" {{ $isChecked ? 'checked' : '' }}>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        const roleSelect = new Choices('#role', {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Select Roles',
            maxItemCount: 10,
            searchResultLimit: 10,
            renderChoiceLimit: 10
        });

        // Map of role IDs to their permission IDs (server rendered)
        const rolePermissionsMap = @json($roles->mapWithKeys(fn($r) => [$r->id => $r->permissions->pluck('id')])->toArray());

        console.warn(rolePermissionsMap);

        // Auto-select permissions based on selected roles
        roleSelect.passedElement.element.addEventListener('change', function() {
            const selectedRoles = roleSelect.getValue(true).map(id => parseInt(id));
            const permissionCheckboxes = document.querySelectorAll('.perm-checkbox');

            const selectedPerms = new Set();
            selectedRoles.forEach(roleId => {
                const perms = rolePermissionsMap[roleId] || [];
                perms.forEach(p => selectedPerms.add(p));
            });

            permissionCheckboxes.forEach(cb => {
                cb.checked = selectedPerms.has(parseInt(cb.dataset.permissionId));
            });
        });


        // Select all toggle
        document.querySelectorAll('.select-all-perms').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const targetGroup = this.getAttribute('data-target');
                const checkboxes = document.querySelectorAll(`.${targetGroup} input[type="checkbox"]`);
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        });
    </script>
@endpush
