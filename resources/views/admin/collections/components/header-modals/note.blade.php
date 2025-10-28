<div class="modal" data-modal="true" id="add_note_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Add Note') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <form id="add_note_form">
                @csrf
                <input type="hidden" name="user_id" value="{{ $order->user_id }}">
                <div class="mb-3">
                    <label class="form-label">{{ translate('Note') }}</label>
                    <textarea name="note" class="textarea" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-info">{{ translate('Save Note') }}</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const noteForm = document.getElementById('add_note_form');

            noteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(noteForm);

                fetch("{{ route('notes.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            noteForm.reset();

                            // Optionally close modal
                            document.querySelector('#add_note_modal [data-modal-dismiss]').click();

                            // Optionally reload notes list or append new note
                            // Example: reloadNotes();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: res.message || '{{ translate('Something went wrong') }}',
                            });
                        }
                    })
                    .catch(err => {
                        let errors = err?.errors || err?.response?.data || {};
                        let errorMessages = Object.values(errors).flat().join('\n');
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Validation Error') }}',
                            text: errorMessages || '{{ translate('Something went wrong') }}',
                        });
                    });
            });
        });
    </script>
@endpush
