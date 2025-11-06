@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Spinner */
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

<div class="modal" data-modal="true" id="accept_order">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h1>{{ translate('Accept Order') }}</h1>
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('orders.accept') }}" enctype="multipart/form-data" id="acceptOrderForm">
            @csrf
            <div class="modal-body px-5 pt-0 pb-5 space-y-4">

                {{-- Invoice Number --}}
                <div class="mb-4 mt-4">
                    <label for="invoice_number" class="block text-sm font-medium text-gray-700">
                        {{ translate('Invoice Number') }}
                    </label>
                    <input type="text" name="invoice_number" id="invoice_number" class="input w-full" required>
                </div>

                {{-- Estimated Delivery Date --}}
                <div class="mb-4">
                    <label for="estimated_delivery_date" class="block text-sm font-medium text-gray-700">
                        {{ translate('Estimated Delivery Date') }}
                    </label>
                    <input type="text" name="estimated_delivery_date" id="estimated_delivery_date"
                        class="input w-full flatpickr" value="{{ old('estimated_delivery_date') }}" required>
                </div>

                {{-- Upload File --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        {{ translate('Upload Invoice (PDF only)') }}
                    </label>
                    <div class="flex items-center w-full max-w-md relative">
                        <!-- Hidden file input -->
                        <input type="file" name="invoice_file" id="invoice_file" class="hidden"
                            accept="application/pdf">

                        <!-- Button to trigger file input -->
                        <button type="button"
                            class="absolute top-0 bottom-0 left-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-l"
                            id="selectFileBtn">
                            <i class="ki-filled ki-folder text-xl"></i>
                        </button>

                        <!-- Readonly input to show selected filename -->
                        <input type="text" id="fileNameDisplay" class="input w-full pl-12"
                            placeholder="{{ translate('Click to select file') }}" readonly
                            style="padding-inline-start: 2.75rem;">
                    </div>
                </div>

                {{-- Hidden Order ID --}}
                <input type="hidden" name="order_id" value="{{ $order->id ?? '' }}">

                {{-- Submit --}}
                <div class="text-end pt-2">
                    <button type="submit" class="btn btn-primary" id="accept-order-submit-btn">
                        {{ translate('Accept Order') }}
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr
            flatpickr(".flatpickr", {
                enableTime: true,
                dateFormat: "Y-m-d H:i:S",
                time_24hr: true,
                minDate: "today"
            });

            // File input handling
            const selectFileBtn = document.getElementById('selectFileBtn');
            const fileInput = document.getElementById('invoice_file');
            const fileNameDisplay = document.getElementById('fileNameDisplay');

            if (selectFileBtn && fileInput && fileNameDisplay) {
                const triggerFilePicker = () => fileInput.click();

                // Trigger hidden input
                selectFileBtn.addEventListener('click', triggerFilePicker);
                fileNameDisplay.addEventListener('click', triggerFilePicker);

                // Validate PDF
                fileInput.addEventListener('change', function() {
                    if (fileInput.files && fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        if (file.type !== 'application/pdf') {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Invalid File') }}',
                                text: '{{ translate('Only PDF files are allowed.') }}'
                            });
                            fileInput.value = '';
                            fileNameDisplay.value = '';
                            return;
                        }
                        fileNameDisplay.value = file.name;
                    } else {
                        fileNameDisplay.value = '';
                    }
                });
            }

            // AJAX form submit
            const acceptForm = document.getElementById('acceptOrderForm');
            const acceptBtn = document.getElementById('accept-order-submit-btn');

            $(acceptForm).on('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(acceptForm);
                acceptBtn.disabled = true;
                acceptBtn.innerHTML =
                    `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ translate('Processing...') }}`;

                $.ajax({
                    url: $(acceptForm).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        acceptBtn.disabled = false;
                        acceptBtn.innerHTML = "{{ translate('Accept Order') }}";

                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: response.message,
                                confirmButtonText: '{{ translate('OK') }}'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        acceptBtn.disabled = false;
                        acceptBtn.innerHTML = "{{ translate('Accept Order') }}";

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages += errors[key][0] + '\n';
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Validation Error') }}',
                                text: errorMessages
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ translate('Something went wrong!') }}'
                            });
                        }
                    }
                });
            });
        });
    </script>
@endpush
