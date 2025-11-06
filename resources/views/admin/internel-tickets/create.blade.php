@extends('layouts.base')
@section('content')
    @push('styles')
        <style>
            .choices {
                width: 100% !important;
            }

            .choices__inner {
                min-height: 2.5rem;
                padding: 0.375rem 0.75rem;
            }
        </style>
    @endpush

    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                {{ translate('Support Ticket') }}
                            </h3>
                        </div>

                        <form action="{{ route('ticketStore') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body grid gap-5">
                                <input type="hidden" name="model_type" value="App\Models\SupportTicket">

                                @php
                                    $employees = getEmployees();
                                @endphp
                                <div class="w-full" id="transfer_request_bulk">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label">{{ translate('Transfer To') }}</label>
                                        <select name="assigned_to" class="select choices" required>
                                            <option value="">{{ translate('Select User') }}</option>
                                            @foreach ($employees as $user)
                                                <option value="{{ $user->id }}">
                                                    @if ($user->is_manager)
                                                        - {{ strtoupper($user->first_name) }}
                                                        {{ strtoupper($user->last_name ?? '') }}
                                                        ({{ translate('Manager') }})
                                                    @else
                                                        -- {{ strtoupper($user->first_name) }}
                                                        {{ strtoupper($user->last_name ?? '') }}
                                                        ({{ translate('Employee') }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- Subject -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Subject') }} <span class="text-danger">*</span>
                                        </label>
                                        <input class="input @error('subject') border-red-500 @enderror" name="subject"
                                            type="text" value="{{ old('subject') }}" required />
                                    </div>
                                    @error('subject')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Details -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Details') }} <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="textarea @error('details') border-red-500 @enderror" name="details" rows="4" required>{{ old('details') }}</textarea>
                                    </div>
                                    @error('details')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Files (Media Picker) -->
                                @include('media.multiple', [
                                    'name' => 'files',
                                    'label' => translate('Files (Optional)'),
                                    'required' => false,
                                    'value' => old('files'),
                                    'multiple_select' => true,
                                ])

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">
                                        {{ translate('Create Ticket') }}
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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.querySelector('#transfer_request_bulk select.choices');
            console.log('Initializing Choices on:', select);
            if (select) {
                new Choices(select, {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: '{{ translate('Select User') }}'
                });
            }
        });
    </script>
@endpush
