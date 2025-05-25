<div class="modal" data-modal="true" id="transfer_detail">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h1>{{ __('Transfer Details') }}</h1>
            <button class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body px-5 pb-5">
            <table class="table table-bordered w-full" id="transfer-request-table">
                <thead>
                    <tr>
                        <th>{{ __('No') }}</th>
                        <th>{{ __('From User') }}</th>
                        <th>{{ __('To User') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Requested At') }}</th>
                    </tr>
                </thead>
                <tbody id="transfer-request-body">
                    <tr>
                        <td colspan="4" class="text-center">{{ __('No data loaded') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const fetchUrl = "{{ route('transfer.requests.fetch') }}";

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.transfer-requests-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const modelId = this.getAttribute('data-model-id');
                    const modelType = this.getAttribute('data-model-type');

                    const tbody = document.getElementById('transfer-request-body');
                    tbody.innerHTML =
                        `<tr><td colspan="4" class="text-center">Loading...</td></tr>`;

                    fetch(
                            `${fetchUrl}?model_id=${modelId}&model_type=${encodeURIComponent(modelType)}`
                        )
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            console.warn(data);

                            if (data.data.length > 0) {
                                tbody.innerHTML = '';
                                data.data.forEach((item, index) => {

                                    tbody.insertAdjacentHTML('beforeend', `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.from_user ? item.from_user.first_name + ' ' + item.from_user.last_name : '-'}</td>
                                        <td>${item.to_user ? item.to_user.first_name + ' ' + item.to_user.last_name : '-'}</td>
                                        <td>${item.description ?? '-'}</td>
                                        <td>${item.created_at ? new Date(item.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'}</td>
                                    </tr>

                                `);
                                });
                            } else {
                                tbody.innerHTML =
                                    `<tr><td colspan="5" class="text-center">No transfer requests found</td></tr>`;
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            tbody.innerHTML =
                                `<tr><td colspan="5" class="text-center">Error loading data</td></tr>`;
                        });
                });
            });
        });
    </script>
@endpush
