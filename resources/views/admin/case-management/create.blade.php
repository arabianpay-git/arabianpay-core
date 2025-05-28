@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                {{ __('Add New Case') }}
                            </h3>
                        </div>

                        <form action="{{ route('case-management.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body grid gap-5">
                                <div class="w-full">
                                    <label class="form-label">{{ __('Assign To') }}</label>
                                    <select name="user_id" id="to_user_id" class="select choices w-full" required>
                                        <option value="">{{ __('Select User') }}</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                @if ($user->is_manager)
                                                    - {{ strtoupper($user->first_name) }}
                                                    {{ strtoupper($user->last_name ?? '') }} ({{ __('Manager') }})
                                                @else
                                                    -- {{ strtoupper($user->first_name) }}
                                                    {{ strtoupper($user->last_name ?? '') }} ({{ __('Employee') }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex gap-4">
                                    <!-- Title -->
                                    <div class="w-full">
                                        <label for="title" class="form-label">{{ __('Title') }}</label>
                                        <input type="text" name="title" id="title" class="input w-full"
                                            value="{{ old('title') }}">
                                        @error('title')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Priority -->
                                    <div class="w-full">
                                        <label for="priority" class="form-label">{{ __('Priority') }}</label>
                                        <select name="priority" id="priority" class="input w-full">
                                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low
                                            </option>
                                            <option value="medium"
                                                {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium
                                            </option>
                                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High
                                            </option>
                                        </select>
                                        @error('priority')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="form-label">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="input w-full">
                                        <option value="open" {{ old('status', 'open') == 'open' ? 'selected' : '' }}>Open
                                        </option>
                                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>
                                            In Progress</option>
                                        <option value="resolved" {{ old('status') == 'resolved' ? 'selected' : '' }}>
                                            Resolved</option>
                                        <option value="closed" {{ old('status') == 'closed' ? 'selected' : '' }}>Closed
                                        </option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="form-label">{{ __('Description') }}</label>
                                    <textarea name="description" id="description" class="textarea w-full" rows="4">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Due Date -->
                                <div>
                                    <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                                    <input type="text" name="due_date" id="due_date" class="input w-full"
                                        value="{{ old('due_date') }}">
                                    @error('due_date')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Upload Documents -->
                                @include('media.multiple', [
                                    'name' => 'documents',
                                    'label' => __('Upload Documents'),
                                    'required' => false,
                                    'value' => old('documents', []),
                                    'multiple_select' => true,
                                ])

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button type="submit" class="btn btn-primary">{{ __('Save Case') }}</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#due_date", {
                altInput: true,
                altFormat: "F j, Y",
                dateFormat: "Y-m-d",
                defaultDate: "{{ old('due_date') }}"
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#to_user_id', {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholderValue: "{{ __('Select User') }}",
            });
        });
    </script>
@endpush
