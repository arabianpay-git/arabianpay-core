<div class="modal" data-modal="true" id="template_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Dunning Template') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form id="dunningTemplateForm" method="POST" action="{{ route('dunning.store') }}">
            @csrf
            {{-- _method (PUT) will be injected by JS when editing --}}
            <div class="modal-body p-5 flex flex-col gap-4">
                {{-- Template Name --}}
                <div class="flex flex-col gap-1">
                    <label class="font-medium text-gray-700">{{ translate('Name') }}</label>
                    <input type="text" name="name" class="input input-bordered"
                        placeholder="{{ translate('Enter template name') }}">
                    <small class="text-red-500 error-text name_error"></small>
                </div>

                {{-- Channel + DPD --}}
                <div class="flex gap-4">
                    <div class="w-full">
                        <label class="font-medium text-gray-700">{{ translate('Channel') }}</label>
                        <select name="type" class="select select-bordered">
                            <option value="">{{ translate('Select Channel') }}</option>
                            <option value="sms">{{ translate('SMS') }}</option>
                            <option value="email">{{ translate('Email') }}</option>
                            <option value="ivr">{{ translate('IVR') }}</option>
                            <option value="task">{{ translate('Agent Task') }}</option>
                        </select>
                        <small class="text-red-500 error-text type_error"></small>
                    </div>

                    <div class="w-full">
                        <label class="font-medium text-gray-700">{{ translate('DPD Bucket') }}</label>
                        <select name="dpd_bucket" class="select select-bordered">
                            <option value="">{{ translate('Select DPD Bucket') }}</option>
                            <option value="0-3">{{ translate('0-3 DPD') }}</option>
                            <option value="4-7">{{ translate('4-7 DPD') }}</option>
                            <option value="8-15">{{ translate('8-15 DPD') }}</option>
                        </select>
                        <small class="text-red-500 error-text dpd_bucket_error"></small>
                    </div>
                </div>

                {{-- Throttling + Language --}}
                <div class="flex gap-4">
                    <div class="w-full">
                        <label class="font-medium text-gray-700">{{ translate('Throttling') }}</label>
                        <select name="throttling" class="select select-bordered">
                            <option value="">{{ translate('Select Throttling') }}</option>
                            <option value="1/Day">1/Day</option>
                            <option value="2/Days">2/Days</option>
                            <option value="3/Days">3/Days</option>
                            <option value="1/Week">1/Week</option>
                            <option value="2/Weeks">2/Weeks</option>
                            <option value="1/Month">1/Month</option>
                            <option value="None">None</option>
                        </select>
                        <small class="text-red-500 error-text throttling_error"></small>
                    </div>

                    <div class="w-full">
                        <label class="font-medium text-gray-700">{{ translate('Language') }}</label>
                        <select name="language" class="select select-bordered">
                            <option value="">{{ translate('Select Language') }}</option>
                            <option value="EN">EN</option>
                            <option value="AR">AR</option>
                        </select>
                        <small class="text-red-500 error-text language_error"></small>
                    </div>
                </div>

                {{-- Message content (Field name changed from 'content' to 'message') --}}
                <div class="flex flex-col gap-1">
                    <label class="font-medium text-gray-700">{{ translate('Message Content') }}</label>
                    <textarea name="message" class="textarea input-bordered" placeholder="{{ translate('Enter message content') }}"></textarea>
                    {{-- Error text updated to message_error --}}
                    <small class="text-red-500 error-text message_error"></small>
                </div>
            </div>

            <div class="modal-footer flex justify-end gap-2 py-4 px-5">
                <button type="button" class="btn btn-light" data-modal-dismiss="true">
                    {{ translate('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary submit-btn">
                    {{ translate('Save Template') }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="[https://cdn.jsdelivr.net/npm/sweetalert2@11](https://cdn.jsdelivr.net/npm/sweetalert2@11)"></script>

    <script>
        (function() {
            // Elements
            const modalSelector = '#template_modal';
            const modalToggleSelector = '[data-modal-toggle="#template_modal"]';
            const $form = $('#dunningTemplateForm');
            const $modal = $(`${modalSelector}`); // jQuery object for the modal
            const $submitBtn = $form.find('.submit-btn');

            // Keep original create action so we can reset to it
            const createAction = $form.attr('action');

            // helper: clear inline errors
            function clearErrors() {
                $form.find('.error-text').text('');
            }

            // helper: set form mode to create
            function setCreateMode() {
                clearErrors();
                $form.trigger('reset');
                $form.attr('action', createAction);
                $form.find('input[name="_method"]').remove(); // remove any method override
                $submitBtn.text('{{ translate('Save Template') }}');
                $form.data('mode', 'create');
            }

            // helper: set form to edit mode with data
            function setEditMode(id, data) {
                clearErrors();
                $form.trigger('reset');

                // populate fields (Updated 'content' to 'message')
                $form.find('[name="name"]').val(data.name || '');
                $form.find('[name="type"]').val(data.type || '');
                $form.find('[name="dpd_bucket"]').val(data.dpd_bucket || '');
                $form.find('[name="throttling"]').val(data.throttling || '');
                $form.find('[name="language"]').val(data.language || '');
                $form.find('[name="message"]').val(data.message || ''); // <-- Updated to 'message'

                // set action to update route
                let updateUrl = "{{ route('dunning.update', ['id' => ':id']) }}".replace(':id', id);
                $form.attr('action', updateUrl);

                // ensure we have _method PUT
                if ($form.find('input[name="_method"]').length === 0) {
                    $form.append('<input type="hidden" name="_method" value="PUT">');
                } else {
                    $form.find('input[name="_method"]').val('PUT');
                }

                $submitBtn.text('{{ translate('Update Template') }}');
                $form.data('mode', 'edit');
            }

            // When Create button opens modal, reset to create mode.
            document.querySelectorAll(modalToggleSelector).forEach(btn => {
                btn.addEventListener('click', () => {
                    setCreateMode();
                });
            });

            // ---------- Edit button: fetch data & open modal ----------
            $(document).on('click', '.edit-template', function(e) {
                e.preventDefault();
                const $button = $(this);
                const id = $button.data('id');

                // *** START LOADER ***
                const originalHtml = $button.html();
                // Assumes your UI uses an icon or spinner class for loading
                $button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> {{ translate('Loading...') }}'
                );


                // Correct named route reference
                const showUrl = "{{ route('dunning.template.show', ['id' => ':id']) }}".replace(':id', id);

                $.ajax({
                    url: showUrl,
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        // *** STOP LOADER ***
                        $button.prop('disabled', false).html(originalHtml);

                        if (res && res.success) {
                            setEditMode(id, res.data);

                            // Programmatically open the modal via the existing toggle mechanism
                            document.querySelector(modalToggleSelector)?.click();

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: res.message ||
                                    '{{ translate('Failed to load template') }}'
                            });
                        }
                    },
                    error: function(xhr) {
                        // *** STOP LOADER ***
                        $button.prop('disabled', false).html(originalHtml);

                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: xhr.responseJSON?.message ||
                                '{{ translate('Something went wrong while fetching template') }}'
                        });
                    }
                });
            });

            // ---------- Delete button ----------
            $(document).on('click', '.delete-template', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const deleteUrl = "{{ route('dunning.destroy', ['id' => ':id']) }}".replace(':id', id);

                Swal.fire({
                    title: '{{ translate('Are you sure?') }}',
                    text: '{{ translate('This will delete the template permanently.') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ translate('Yes, delete it') }}',
                    cancelButtonText: '{{ translate('Cancel') }}',
                }).then(result => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ translate('Deleted') }}',
                                    text: res.message ||
                                        '{{ translate('Template deleted') }}',
                                    timer: 1200,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ translate('Error') }}',
                                    text: res.message ||
                                        '{{ translate('Delete failed') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ translate('Server error') }}'
                            });
                        }
                    });
                });
            });

            // ---------- Form submit (create or update) ----------
            $form.on('submit', function(e) {
                e.preventDefault();

                clearErrors();

                const actionUrl = $form.attr('action');
                const method = ($form.find('input[name="_method"]').val() || 'POST').toUpperCase();

                // Serialize data
                const formData = $form.serialize();

                // Disable submit
                $submitBtn.prop('disabled', true).text('{{ translate('Saving...') }}');

                $.ajax({
                    url: actionUrl,
                    type: method,
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        $submitBtn.prop('disabled', false).text($form.data('mode') === 'edit' ?
                            '{{ translate('Update Template') }}' :
                            '{{ translate('Save Template') }}');

                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: res.message || '{{ translate('Saved') }}',
                                timer: 1300,
                                showConfirmButton: false
                            });

                            // Close modal by triggering the dismiss button click
                            $form[0].querySelector('[data-modal-dismiss="true"]')?.click();

                            setTimeout(() => location.reload(), 700);
                        } else {
                            // server responded success: false
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: res.message || '{{ translate('Operation failed') }}'
                            });
                        }
                    },
                    error: function(xhr) {
                        $submitBtn.prop('disabled', false).text($form.data('mode') === 'edit' ?
                            '{{ translate('Update Template') }}' :
                            '{{ translate('Save Template') }}');

                        // Validation errors
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON?.errors || {};
                            // Show inline
                            Object.keys(errors).forEach(key => {
                                const msg = errors[key][0] || errors[key];
                                // Note: Using key_error convention for validation messages
                                $form.find('.' + key + '_error').text(msg);
                            });

                            // Also show consolidated SweetAlert with first few messages
                            const allMessages = [];
                            Object.keys(errors).forEach(key => {
                                allMessages.push(errors[key][0]);
                            });

                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Validation Error') }}',
                                html: allMessages.slice(0, 5).map(m => `<div>${m}</div>`)
                                    .join(''),
                            });
                        } else {
                            // Other errors
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ translate('Something went wrong') }}'
                            });
                        }
                    }
                });
            });
        })();
    </script>
@endpush
