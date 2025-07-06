<div class="modal" data-modal="true" id="transfer_request">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h1>{{ translate('Transfer Request') }}</h1>
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('transfer-requests.store') }}">
            @csrf
            <div class="modal-body px-5 pt-0 pb-5 space-y-4">

                {{-- Transfer To --}}
                <div class="w-full">
                    <label class="form-label">{{ translate('Transfer To') }}</label>
                    <select name="to_user_id" class="select choices" required>
                        <option value="">{{ translate('Select User') }}</option>
                        @foreach ($employees as $user)
                            <option value="{{ $user->id }}">
                                @if ($user->is_manager)
                                    - {{ strtoupper($user->first_name) }} {{ strtoupper($user->last_name ?? '') }}
                                    ({{ translate('Manager') }})
                                @else
                                    -- {{ strtoupper($user->first_name) }} {{ strtoupper($user->last_name ?? '') }}
                                    ({{ translate('Employee') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Description --}}
                <div class="w-full mt-5">
                    <label class="form-label">{{ translate('Description') }}</label>
                    <textarea name="description" class="textarea" rows="3" placeholder="{{ translate('Optional') }}"></textarea>
                </div>

                {{-- Hidden Model Info --}}
                <input type="hidden" name="model_type" value="{{ $model_type }}">
                <input type="hidden" name="model_id" value="{{ $model_id }}">

                {{-- Submit --}}
                <div class="text-end pt-2">
                    <button type="submit" class="btn btn-primary">{{ translate('Send Request') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectElement = document.querySelector('select.choices');
            if (selectElement) {
                new Choices(selectElement, {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: '{{ translate('Select User') }}',
                });
            }
        });
    </script>
@endpush
