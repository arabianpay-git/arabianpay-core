{{-- Edit Schedule Modal --}}
<div class="modal" id="edit_schedule_modal" data-modal="true">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Edit Schedule Payment') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <form id="edit-schedule-form">
                @csrf
                <input type="hidden" name="schedule_id" id="schedule_id">
                <div class="mb-3">
                    <label class="form-label">{{ translate('Due Date') }}</label>
                    <input type="text" name="due_date" id="due_date" class="input w-full flatpickr-date" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ translate('Amount') }}</label>
                    <input type="text" id="amount" class="input w-full" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ translate('Installment Number') }}</label>
                    <input type="text" id="installment" class="input w-full" readonly>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Open modal and populate data
            document.querySelectorAll('[data-modal-toggle="#edit_schedule_modal"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const modal = document.getElementById('edit_schedule_modal');
                    const dueInput = modal.querySelector('#due_date');

                    modal.querySelector('#schedule_id').value = btn.dataset.id;
                    modal.querySelector('#amount').value = btn.dataset.amount;
                    modal.querySelector('#installment').value = btn.dataset.installment;

                    // Set date as a Date object for Flatpickr
                    const selectedDate = btn.dataset.due; // "2025-10-28"
                    dueInput._flatpickr.setDate(selectedDate); // correctly selects the date

                    // Open modal (data-modal-toggle handles this automatically)
                });
            });

            // Initialize Flatpickr after DOM loaded
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });

            // AJAX submit
            document.getElementById('edit-schedule-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const id = this.querySelector('#schedule_id').value;
                const due_date = this.querySelector('#due_date').value;
                const updateUrl = "{{ route('schedule-payments.update', ['id' => ':id']) }}".replace(':id',
                    id);

                fetch(updateUrl, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            due_date
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Updated',
                                    text: data.message
                                })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error || 'Update failed'
                            });
                        }
                    })
                    .catch(() => Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Server error.'
                    }));
            });
        });
    </script>
@endpush
