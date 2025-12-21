@extends('layouts.base')

@section('content')
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
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Role') }}</h3>
                        </div>

                        <!-- Update Form -->
                        <form action="{{ route('roles.update', $role->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="p-5">
                                <div class="grid gap-5">
                                    <!-- Name (EN) -->
                                    <div class="w-full">
                                        <label class="form-label">{{ translate('Role Name') }}</label>
                                        <input class="input @error('name') border-red-500 @enderror" name="name"
                                            type="text" value="{{ old('name', $role->name) }}" required />
                                        @error('name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    @include('admin.employees.permissions', [
                                        'selected' => $role->sensitive_permissions,
                                    ])

                                </div>
                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">{{ translate('Save Changes') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
