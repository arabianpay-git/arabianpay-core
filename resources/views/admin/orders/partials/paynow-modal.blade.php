{{-- Pay Now Modal --}}
<div class="modal" data-modal="true" id="pay_now_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Pay Now') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <form id="pay-now-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="schedule_id" id="pay_schedule_id">
                <input type="hidden" name="amount" id="pay_amount_hidden">

                <div class="mb-3">
                    <label class="form-label">{{ translate('Amount') }}</label>
                    <input type="text" id="pay_amount" class="input w-full" readonly>
                    <span class="text-red-500 text-sm mt-1 block" id="error_amount"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ translate('Payment Method') }}</label>
                    <select name="payment_method" class="input w-full">
                        <option value="Bank Transfer">{{ translate('Bank Transfer') }}</option>
                        <option value="Credit Card">{{ translate('Credit Card') }}</option>
                        <option value="Cash">{{ translate('Cash') }}</option>
                    </select>
                    <span class="text-red-500 text-sm mt-1 block" id="error_payment_method"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ translate('Receipt') }}</label>
                    <div class="flex items-center w-full max-w-md relative">
                        <button type="button"
                            class="absolute top-0 bottom-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-r">
                            <i class="ki-filled ki-folder text-xl"></i>
                        </button>
                        <input type="text" class="input w-full" placeholder="Click to select receipt" readonly
                            style="padding-inline-start: 2.75rem;">
                    </div>
                    <span class="text-red-500 text-sm mt-1 block" id="error_receipt"></span>
                </div>

                <button type="submit" class="btn btn-primary mt-4" id="pay-now-submit">
                    <span class="pay-now-text">{{ translate('Submit Payment') }}</span>
                    <span class="pay-now-spinner hidden loader ml-2"></span>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('pay_now_modal');
            const submitBtn = modal.querySelector('#pay-now-submit');
            const submitText = submitBtn.querySelector('.pay-now-text');
            const submitSpinner = submitBtn.querySelector('.pay-now-spinner');

            // Populate schedule_id and amount when opening
            document.querySelectorAll('[data-modal-toggle="#pay_now_modal"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.querySelector('#pay_schedule_id').value = btn.dataset.id || '';
                    modal.querySelector('#pay_amount').value = btn.dataset.amount || '';
                    modal.querySelector('#pay_amount_hidden').value = btn.dataset.amount || '';

                    // Reset visible filename
                    const visibleText = modal.querySelector(
                    '.flex.items-center input[type="text"]');
                    if (visibleText) visibleText.value = '';

                    // Clear previous hidden file input
                    if (modal._payNowFileInput) modal._payNowFileInput.value = '';
                });
            });

            // File input wiring
            const fileWrapper = modal.querySelector('.flex.items-center');
            if (fileWrapper) {
                const visibleTextInput = fileWrapper.querySelector('input[type="text"]');
                if (!modal._payNowFileInput) {
                    const fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.name = 'receipt';
                    fileInput.accept = 'image/*,application/pdf';
                    fileInput.style.display = 'none';
                    fileWrapper.appendChild(fileInput);
                    modal._payNowFileInput = fileInput;

                    const folderBtn = fileWrapper.querySelector('button');
                    if (folderBtn) folderBtn.addEventListener('click', () => fileInput.click());
                    if (visibleTextInput) visibleTextInput.addEventListener('click', () => fileInput.click());

                    fileInput.addEventListener('change', () => {
                        if (!visibleTextInput) return;
                        visibleTextInput.value = fileInput.files.length ? fileInput.files[0].name : '';
                    });
                }
            }

            // Submit handler
            const form = document.getElementById('pay-now-form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Disable button and show spinner
                submitBtn.disabled = true;
                submitText.classList.add('opacity-50');
                submitSpinner.classList.remove('hidden');
                submitSpinner.innerHTML =
                    `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>`;

                const formData = new FormData(this);
                const hiddenFile = modal._payNowFileInput;
                if (hiddenFile && hiddenFile.files[0]) {
                    formData.set('receipt', hiddenFile.files[0]);
                }

                fetch("{{ route('schedule-payments.pay-now') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(async res => {
                        let data = {};
                        const contentType = res.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            data = await res.json();
                        } else {
                            const text = await res.text();
                            try {
                                data = JSON.parse(text);
                            } catch {
                                data = {
                                    success: false,
                                    message: text
                                };
                            }
                        }

                        if (!res.ok) {
                            if (res.status === 422 && data.errors) {
                                let errorMessage = Object.values(data.errors).flat().join('\n');
                                throw new Error(errorMessage);
                            } else {
                                throw new Error(data.message || 'Server error');
                            }
                        }

                        return data;
                    })
                    .then(data => {
                        if (!data) return;

                        if (data.success) {
                            modal.querySelector('[data-modal-dismiss="true"]')?.click();

                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: data.message ||
                                    '{{ translate('Payment submitted successfully') }}'
                            }).then(() => {
                                if (modal._payNowFileInput) modal._payNowFileInput.value = '';
                                const vis = modal.querySelector(
                                    '.flex.items-center input[type="text"]');
                                if (vis) vis.value = '';
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: data.message || '{{ translate('Something went wrong') }}'
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Validation Error') }}',
                            text: err.message
                        });
                    })
                    .finally(() => {
                        // Re-enable button and hide spinner
                        submitBtn.disabled = false;
                        submitText.classList.remove('opacity-50');
                        submitSpinner.classList.add('hidden');
                        submitSpinner.innerHTML = '';
                    });
            });
        });
    </script>
@endpush
