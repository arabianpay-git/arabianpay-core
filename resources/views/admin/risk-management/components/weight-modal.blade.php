{{-- resources/views/admin/risk-management/partials/weight-modal.blade.php --}}
<div class="modal" data-modal="true" id="weight_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Set Risk Weights') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <form id="weight_form" action="{{ route('riskWeights.store') }}" method="POST" class="px-5 pt-3">
                @csrf
                <input type="hidden" name="weight_id" id="weight_id" value="">

                <div class="mb-4">
                    <label class="form-label">{{ translate('CR/ID Weight') }}</label>
                    <input type="number" step="0.01" min="0" name="cr_id" id="cr_id" class="input"
                        required>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ translate('POS Weight') }}</label>
                    <input type="number" step="0.01" min="0" name="pos" id="pos" class="input"
                        required>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ translate('Repayment Weight') }}</label>
                    <input type="number" step="0.01" min="0" name="repayment" id="repayment" class="input"
                        required>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ translate('Industry Weight') }}</label>
                    <input type="number" step="0.01" min="0" name="industry" id="industry" class="input"
                        required>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ translate('Location Weight') }}</label>
                    <input type="number" step="0.01" min="0" name="location" id="location" class="input"
                        required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" id="weight_submit_btn" class="btn btn-primary">
                        {{ translate('Save Weights') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weightForm = document.getElementById('weight_form');
            const weightModal = document.getElementById('weight_modal');
            const openWeightBtn = document.querySelector('[data-modal-toggle="#weight_modal"]');
            const submitBtn = document.getElementById('weight_submit_btn');

            // URLs
            const storeUrl = "{{ route('riskWeights.store') }}";
            const updateBaseUrl = "{{ route('riskWeights.update', ':id') }}";
            const lastUrl = "{{ route('riskWeights.last') }}";

            function openModal() {
                weightModal.dataset.modal = true;
            }

            function closeModal() {
                weightModal.dataset.modal = false;
            }

            function disableEl(el) {
                if (!el) return;
                el.setAttribute('disabled', 'disabled');
                el.classList.add('opacity-60', 'cursor-not-allowed');
            }

            function enableEl(el) {
                if (!el) return;
                el.removeAttribute('disabled');
                el.classList.remove('opacity-60', 'cursor-not-allowed');
            }

            // Open modal and fetch last weights
            if (openWeightBtn) {
                openWeightBtn.addEventListener('click', function() {
                    disableEl(openWeightBtn);

                    fetch(lastUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.ok ? res.json() : {
                            success: false
                        })
                        .then(data => {
                            let w = data.success && data.weights ? data.weights : null;
                            document.getElementById('cr_id').value = w?.cr_id ?? 25;
                            document.getElementById('pos').value = w?.pos ?? 25;
                            document.getElementById('repayment').value = w?.repayment ?? 20;
                            document.getElementById('industry').value = w?.industry ?? 15;
                            document.getElementById('location').value = w?.location ?? 10;
                            document.getElementById('weight_id').value = w?.id ?? '';
                            openModal();
                        })
                        .catch(err => {
                            // fallback defaults
                            document.getElementById('cr_id').value = 25;
                            document.getElementById('pos').value = 25;
                            document.getElementById('repayment').value = 20;
                            document.getElementById('industry').value = 15;
                            document.getElementById('location').value = 10;
                            document.getElementById('weight_id').value = '';
                            openModal();
                        })
                        .finally(() => enableEl(openWeightBtn));
                });
            }

            // Submit form
            weightForm.addEventListener('submit', function(e) {
                e.preventDefault();
                disableEl(submitBtn);

                const formData = new FormData(weightForm);
                const id = formData.get('weight_id');
                let url = storeUrl;
                if (id) {
                    url = updateBaseUrl.replace(':id', id);
                    formData.set('_method', 'PUT');
                }

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));
                        if (res.ok && json.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: json.message ?? '{{ translate('Weights saved') }}',
                            });

                            // Keep latest values in inputs
                            if (json.data && json.data.id && json.data.new_weight) {
                                document.getElementById('weight_id').value = json.data.id;
                                const w = json.data.new_weight;
                                document.getElementById('cr_id').value = w.cr_id ?? 25;
                                document.getElementById('pos').value = w.pos ?? 25;
                                document.getElementById('repayment').value = w.repayment ?? 20;
                                document.getElementById('industry').value = w.industry ?? 15;
                                document.getElementById('location').value = w.location ?? 10;
                            }
                        } else {
                            let errMsg = json.message ||
                                '{{ translate('Validation failed or server error') }}';
                            if (json.errors) {
                                const messages = [];
                                Object.values(json.errors).forEach(v => {
                                    if (Array.isArray(v)) messages.push(...v);
                                    else messages.push(v);
                                });
                                if (messages.length) errMsg = messages.join(' ');
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: errMsg
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('Server error') }}'
                        });
                    })
                    .finally(() => enableEl(submitBtn));
            });
        });
    </script>
@endpush
