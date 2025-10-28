@php
    // Fetch clients of type 'user' (merchants)
    $clients = App\Models\User::where('user_type', 'user')->get();
@endphp

<div class="modal" data-modal="true" id="promisetopay_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Create New Promise') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('promises.store') }}" id="promiseForm">
            @csrf
            <div class="modal-body p-5 flex flex-col gap-4">

                {{-- Client Name --}}
                <div class="flex flex-col gap-1">
                    <label class="font-medium text-gray-700">{{ translate('Client Name') }}</label>
                    <select name="user_id" class="select2 input input-bordered w-full" id="clientSelect" required>
                        <option value="">{{ translate('Select Client') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->first_name }} {{ $client->last_name }} — {{ $client->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Installment --}}
                <div class="mb-3">
                    <label class="form-label">{{ translate('Select Installment') }}</label>
                    <select name="schedule_payment_id" class="select" id="installmentSelect" required>
                        <option value="">{{ translate('Select an unpaid installment') }}</option>
                    </select>
                </div>

                {{-- Due Date --}}
                <div class="flex flex-col gap-1">
                    <label class="font-medium text-gray-700">{{ translate('Due Date') }}</label>
                    <input type="text" name="promise_date" class="input input-bordered flatpickr"
                        placeholder="{{ translate('Select due date') }}" required>
                </div>

                {{-- Contact Method --}}
                <div class="flex gap-4">
                    <div class="w-full">
                        <label class="font-medium text-gray-700">{{ translate('Contact Method') }}</label>
                        <select name="method" class="select select-bordered" required>
                            <option value="">{{ translate('Select Method') }}</option>
                            <option value="call">{{ translate('Call') }}</option>
                            <option value="email">{{ translate('Email') }}</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="modal-footer flex justify-end gap-2 py-4 px-5">
                <button type="button" class="btn btn-light"
                    data-modal-dismiss="true">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ translate('Create Promise') }}</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    {{-- CDNs for Select2, Flatpickr & SweetAlert --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 & Flatpickr
            $('.select2').select2({
                placeholder: "{{ translate('Select Client') }}",
                width: '100%'
            });

            flatpickr(".flatpickr", {
                dateFormat: "Y-m-d",
                minDate: "today" // Only future dates including today
            });

            // Fetch unpaid installments dynamically
            $('#clientSelect').on('change', function() {
                let userId = $(this).val();
                let installmentSelect = $('#installmentSelect');

                installmentSelect.html('<option value="">{{ translate('Loading...') }}</option>');

                if (userId) {
                    $.get("{{ url('admin/promisetopay/unpaid-installments') }}/" + userId, function(res) {
                        installmentSelect.empty();
                        if (res.success && res.data.length > 0) {
                            installmentSelect.append(
                                '<option value="">{{ translate('Select an unpaid installment') }}</option>'
                            );
                            res.data.forEach(function(payment) {
                                installmentSelect.append(
                                    `<option value="${payment.id}">SAR ${parseFloat(payment.instalment_amount).toFixed(2)} — Due: ${payment.due_date}</option>`
                                );
                            });
                        } else {
                            installmentSelect.append(
                                '<option value="">{{ translate('No unpaid installments') }}</option>'
                            );
                        }
                    }).fail(function() {
                        installmentSelect.empty().append(
                            '<option value="">{{ translate('Error fetching installments') }}</option>'
                        );
                    });
                } else {
                    installmentSelect.html(
                        '<option value="">{{ translate('Select an unpaid installment') }}</option>'
                    );
                }
            });

            // AJAX form submission with SweetAlert
            $('#promiseForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let url = form.attr('action');
                let data = form.serialize();
                let submitBtn = form.find('button[type="submit"]');

                // Disable button to prevent multiple submissions
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: "{{ translate('Success') }}",
                            text: res.message ||
                                "{{ translate('Promise created successfully') }}",
                            confirmButtonText: "{{ translate('OK') }}"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload(); // Reload the page on OK
                            }
                        });

                        // Reset form & selects
                        form.trigger('reset');
                        $('.select2').val(null).trigger('change');
                        $('#installmentSelect').html(
                            '<option value="">{{ translate('Select an unpaid installment') }}</option>'
                        );

                        // Re-enable button (in case user closes alert manually)
                        submitBtn.prop('disabled', false);
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let message = '';
                        if (errors) {
                            for (let key in errors) {
                                message += errors[key][0] + '\n';
                            }
                        } else {
                            message = xhr.responseJSON?.message ||
                                "{{ translate('Something went wrong') }}";
                        }
                        Swal.fire({
                            icon: 'error',
                            title: "{{ translate('Error') }}",
                            text: message
                        });

                        // Re-enable button on error
                        submitBtn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
