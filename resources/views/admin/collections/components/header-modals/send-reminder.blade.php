<div class="modal" data-modal="true" id="send_reminder_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Send Reminder') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-5">
            <form id="send-reminder-form">
                @csrf
                {{-- Ensure $order and $order->customer are available in this view --}}
                <input type="hidden" name="user_id" value="{{ $order->customer->id }}">

                <div class="mb-3">
                    <label class="form-label">{{ translate('Send via') }}</label>
                    <select name="method" id="reminder_method" class="select" required>
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                        <option value="both">Both</option>
                    </select>
                </div>

                <div class="mb-3">
                    <div
                        class="contact-info-bar flex align-items-center justify-content-between gap-4 p-3 rounded-lg bg-white shadow-sm border">
                        <div class="flex align-items-center gap-2">
                            <i class="ki ki-user fs-4 text-primary" aria-hidden="true"></i>
                            <span class="fw-semibold">
                                {{ $order->customer->first_name }} {{ $order->customer->last_name }}
                            </span>
                            <span class="text-muted small">({{ __('ID:') }} {{ $order->customer->id }})</span>
                        </div>

                        <div class="vr" style="height: 25px;"></div>

                        @if ($order->customer->email)
                            <div class="flex align-items-center gap-2">
                                <i class="ki ki-envelope fs-4 text-info" aria-hidden="true"></i>
                                <span class="fw-medium">{{ $order->customer->email }}</span>
                            </div>
                        @else
                            <div class="flex align-items-center gap-2 text-danger">
                                <i class="ki ki-warning fs-4" aria-hidden="true"></i>
                                <span class="small">{{ translate('No email') }}</span>
                            </div>
                        @endif

                        <div class="vr" style="height: 25px;"></div>

                        @if ($order->customer->phone_number)
                            <div class="flex align-items-center gap-2">
                                <i class="ki ki-phone fs-4 text-success" aria-hidden="true"></i>
                                <span class="fw-medium">{{ $order->customer->phone_number }}</span>
                            </div>
                        @else
                            <div class="flex align-items-center gap-2 text-danger">
                                <i class="ki ki-cross fs-4" aria-hidden="true"></i>
                                <span class="small">{{ translate('No phone') }}</span>
                            </div>
                        @endif
                    </div>
                </div>


                <div class="mb-3">
                    <label class="form-label">{{ translate('Message') }}</label>
                    <textarea name="message" class="textarea" rows="4" required>{{ translate('Dear user, please pay your installment on time.') }}</textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-success">{{ translate('Send Reminder') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('send-reminder-form');
        const dismissButton = document.querySelector('#send_reminder_modal [data-modal-dismiss="true"]');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(form);

            Swal.fire({
                title: "{{ translate('Sending Reminder...') }}",
                text: "{{ translate('Please wait while we send the reminder.') }}",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const response = await fetch("{{ route('reminders.send') }}", {
                    method: "POST",
                    // For FormData, Fetch API handles 'Content-Type' automatically for the boundary,
                    // but we must manually set the CSRF token via a header.
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('input[name="_token"]')
                            .value,
                        "Accept": "application/json" // Explicitly request JSON to avoid unexpected HTML responses
                    },
                    body: formData,
                });

                // Check for validation errors (422) or other server errors (400, 500)
                if (!response.ok) {
                    // Try to parse JSON for structured errors (like Laravel validation)
                    const errorData = await response.json();

                    Swal.close();

                    let errorMessage = errorData.message ||
                        "{{ translate('An unexpected error occurred.') }}";

                    // Laravel Validation Error Handling (422 Unprocessable Entity)
                    if (response.status === 422 && errorData.errors) {
                        const validationErrors = Object.values(errorData.errors).flat().join('\n');
                        errorMessage =
                            `{{ translate('Validation Failed:') }}\n${validationErrors}`;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: "{{ translate('Error') }}",
                        text: errorMessage,
                    });
                    return; // Stop execution on error
                }

                // Handle successful response (200 OK)
                const data = await response.json();
                Swal.close();

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: "{{ translate('Success!') }}",
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => {
                        // After success, close the modal and reset the form
                        form.reset();
                        dismissButton.click();
                    });
                } else {
                    // Handle 'error' status returned in the JSON body (e.g., custom controller logic)
                    Swal.fire({
                        icon: 'error',
                        title: "{{ translate('Error') }}",
                        text: data.message ||
                            "{{ translate('Something went wrong during sending.') }}",
                    });
                }
            } catch (err) {
                // Catch network errors (e.g., no internet, connection refused)
                Swal.close();
                console.error('Fetch error:', err);
                Swal.fire({
                    icon: 'error',
                    title: "{{ translate('Connection Error') }}",
                    text: "{{ translate('Failed to communicate with the server.') }}",
                });
            }
        });
    });
</script>

<style>
    .contact-info-bar {
        border-left: 4px solid #0d6efd;
        transition: box-shadow 0.2s ease-in-out;
    }

    .contact-info-bar:hover {
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .vr {
        width: 1px;
        background-color: #e5e7eb;
    }
</style>
