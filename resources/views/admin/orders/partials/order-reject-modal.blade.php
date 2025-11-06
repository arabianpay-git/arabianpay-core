<div class="modal" data-modal="true" id="reject_order">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h1>{{ translate('Reject Order') }}</h1>
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('orders.reject') }}" id="rejectOrderForm">
            @csrf
            <div class="modal-body px-5 pt-0 pb-5 space-y-4">

                {{-- Warning Message --}}
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-2 rounded mt-4">
                    <p class="font-medium">{{ translate('Warning!') }}</p>
                    <p class="text-sm">
                        {{ translate('Once you reject this order, you will not be able to perform any further actions on it.') }}
                    </p>
                </div>

                {{-- Rejection Reason --}}
                <div class="mb-4 mt-4">
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700">
                        {{ translate('Rejection Reason') }}
                    </label>
                    <textarea name="rejection_reason" id="rejection_reason" class="textarea w-full" rows="4"
                        placeholder="{{ translate('Enter reason for rejection') }}" required></textarea>
                </div>

                {{-- Hidden Order ID --}}
                <input type="hidden" name="order_id" value="{{ $order->id ?? '' }}">

                {{-- Submit --}}
                <div class="text-end pt-2">
                    <button type="submit" class="btn btn-danger" id="reject-order-submit-btn">
                        {{ translate('Reject Order') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rejectForm = document.getElementById('rejectOrderForm');
            const rejectBtn = document.getElementById('reject-order-submit-btn');

            $(rejectForm).on('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(rejectForm);
                rejectBtn.disabled = true;
                rejectBtn.innerHTML =
                    `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ translate('Processing...') }}`;

                $.ajax({
                    url: $(rejectForm).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        rejectBtn.disabled = false;
                        rejectBtn.innerHTML = "{{ translate('Reject Order') }}";

                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#reject_order')
                                .fadeOut(); // Adjust according to your modal library
                            rejectForm.reset();
                        }
                    },
                    error: function(xhr) {
                        rejectBtn.disabled = false;
                        rejectBtn.innerHTML = "{{ translate('Reject Order') }}";

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
