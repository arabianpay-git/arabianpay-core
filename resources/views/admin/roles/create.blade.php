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
                                {{ translate('Add New Role') }}
                            </h3>
                        </div>
                        <form action="{{ route('roles.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">
                                <!-- Country Name Field -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Role Name') }}
                                        </label>
                                        <input class="input @error('name') border-red-500 @enderror" name="name"
                                            type="text" value="{{ old('name') }}" required />
                                    </div>
                                    @error('name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                @include('admin.employees.permissions')

                                <div class="flex justify-end pt-2.5">
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
        <!-- End of Container -->
    </main>
@endsection
