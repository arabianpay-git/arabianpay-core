<div class="modal" data-modal="true" id="transfer_request_bulk">
    <div class="modal-content max-w-[600px] top-[10%]">
        <div class="modal-header py-4 px-5">
            <h1>{{ translate('Bulk Transfer Request') }}</h1>
            <button class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('transfer-requests.bulk') }}">
            @csrf
            <div class="modal-body px-5 pt-0 pb-5 space-y-4">

                {{-- Transfer To --}}
                <div class="w-full mt-5">
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

                {{-- Hidden array of IDs --}}
                <input type="hidden" name="model_type" value="{{ $model_type }}">
                <div id="bulk-model-ids"></div>

                {{-- Submit --}}
                <div class="text-end pt-2">
                    <button type="submit" class="btn btn-primary"
                        id="bulk-submit-btn">{{ translate('Send Bulk Request') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // toggle bulk button
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const bulkBtn = document.getElementById('bulk-transfer-btn');

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const any = Array.from(checkboxes).some(c => c.checked);
                    bulkBtn.classList.toggle('hidden', !any);
                });
            });

            // on bulk btn click, populate hidden inputs
            bulkBtn.addEventListener('click', () => {
                const container = document.getElementById('bulk-model-ids');
                container.innerHTML = ''; // clear
                Array.from(checkboxes)
                    .filter(c => c.checked)
                    .forEach(c => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'model_ids[]';
                        input.value = c.value;
                        container.appendChild(input);
                    });
            });

            // initialize Choices.js
            const select = document.querySelector('#transfer_request_bulk select.choices');
            if (select) new Choices(select, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: '{{ translate('Select User') }}',
            });

            // disable submit button on submit
            const bulkForm = document.querySelector('#transfer_request_bulk form');
            const submitBtn = document.getElementById('bulk-submit-btn');

            if (bulkForm && submitBtn) {
                bulkForm.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ translate('Sending...') }}`;
                });
            }
        });
    </script>
@endpush

@push('styles')
    <style>
        /* Choices.js dropdown scroll fix */
        .choices__list--dropdown {
            max-height: 200px !important;
            overflow-y: auto !important;
        }

        .choices__list--dropdown,
        .choices__list[aria-expanded] {
            position: relative;
        }

        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: text-bottom;
            border: 0.15em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border .75s linear infinite;
        }

        @keyframes spinner-border {
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush
