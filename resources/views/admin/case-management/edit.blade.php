@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Case') }}</h3>
                        </div>

                        <form action="{{ route('case-management.update', $case->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="card-body grid gap-5">
                                <!-- Assign To -->
                                <div class="w-full">
                                    <label class="form-label">{{ translate('Assign To') }}</label>
                                    <select name="user_id" id="to_user_id" class="select choices w-full" required>
                                        <option value="">{{ translate('Select User') }}</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ $case->user_id == $user->id ? 'selected' : '' }}>
                                                @if ($user->is_manager)
                                                    - {{ strtoupper($user->first_name) }}
                                                    {{ strtoupper($user->last_name ?? '') }} ({{ translate('Manager') }})
                                                @else
                                                    -- {{ strtoupper($user->first_name) }}
                                                    {{ strtoupper($user->last_name ?? '') }} ({{ translate('Employee') }})
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
                                        <label for="title" class="form-label">{{ translate('Title') }}</label>
                                        <input type="text" name="title" id="title" class="input w-full"
                                            value="{{ old('title', $case->title) }}">
                                        @error('title')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Priority -->
                                    <div class="w-full">
                                        <label for="priority" class="form-label">{{ translate('Priority') }}</label>
                                        <select name="priority" id="priority" class="input w-full">
                                            <option value="low"
                                                {{ old('priority', $case->priority) == 'low' ? 'selected' : '' }}>
                                                {{ translate('Low') }}
                                            </option>
                                            <option value="medium"
                                                {{ old('priority', $case->priority) == 'medium' ? 'selected' : '' }}>
                                                {{ translate('Medium') }}
                                            </option>
                                            <option value="high"
                                                {{ old('priority', $case->priority) == 'high' ? 'selected' : '' }}>
                                                {{ translate('High') }}
                                            </option>
                                        </select>
                                        @error('priority')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="form-label">{{ translate('Status') }}</label>
                                    <select name="status" id="status" class="input w-full">
                                        <option value="open"
                                            {{ old('status', $case->status) == 'open' ? 'selected' : '' }}>
                                            {{ translate('Open') }}
                                        </option>
                                        <option value="in_progress"
                                            {{ old('status', $case->status) == 'in_progress' ? 'selected' : '' }}>
                                            {{ translate('In Progress') }}
                                        </option>
                                        <option value="resolved"
                                            {{ old('status', $case->status) == 'resolved' ? 'selected' : '' }}>
                                            {{ translate('Resolved') }}
                                        </option>
                                        <option value="closed"
                                            {{ old('status', $case->status) == 'closed' ? 'selected' : '' }}>
                                            {{ translate('Closed') }}
                                        </option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="form-label">{{ translate('Description') }}</label>
                                    <textarea name="description" id="description" class="textarea w-full" rows="4">{{ old('description', $case->description) }}</textarea>
                                    @error('description')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Due Date -->
                                <div>
                                    <label for="due_date" class="form-label">{{ translate('Due Date') }}</label>
                                    <input type="text" name="due_date" id="due_date" class="input w-full"
                                        value="{{ old('due_date', $case->due_date) }}">
                                    @error('due_date')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Upload Documents -->
                                @include('media.multiple', [
                                    'name' => 'documents',
                                    'label' => translate('Upload Documents'),
                                    'required' => false,
                                    'value' => old('documents', $case->documents ?? []),
                                    'multiple_select' => true,
                                ])

                                <!-- Submit -->
                                <div class="flex justify-end pt-2.5">
                                    <button type="submit" class="btn btn-primary">{{ translate('Update Case') }}</button>
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
                defaultDate: "{{ old('due_date', $case->due_date) }}"
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
                placeholderValue: "{{ translate('Select User') }}",
            });
        });
    </script>
@endpush
