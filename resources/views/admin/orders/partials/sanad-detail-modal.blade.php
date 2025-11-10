{{-- resources/views/admin/orders/partials/sanad-detail-modal.blade.php --}}
<div class="modal" data-modal="true" id="sanad_detail_modal">
    <div
        class="modal-content max-w-[950px] top-[5%] rounded-2xl shadow-2xl border border-gray-200 bg-white overflow-hidden">
        {{-- Header --}}
        <div class="modal-header py-4 px-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h5 class="modal-title text-xl font-semibold text-gray-800">
                <i class="ki-filled ki-file-text text-primary mr-2"></i> {{ translate('SANAD Details') }}
            </h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                <i class="ki-filled ki-cross text-gray-500 hover:text-gray-800"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="modal-body p-6 bg-gray-50">
            <div id="sanadDetailContainer" class="space-y-4">

                {{-- Loader --}}
                <div id="sanadLoading" class="text-center py-12">
                    <div role="status" class="flex flex-col items-center justify-center space-y-3">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
                        <p class="text-gray-500 text-sm">{{ translate('Loading SANAD details...') }}</p>
                    </div>
                </div>

                {{-- SANAD Content --}}
                <div id="sanadContent" class="hidden">
                    <div class="flex justify-end mb-5">
                        <a id="sanadDownloadBtn" class="btn btn-sm btn-primary" href="#" target="_blank">
                            <i class="ki-filled ki-exit-down mr-2"></i> {{ translate('Download SANAD PDF') }}
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" id="sanadDetailCards"></div>
                </div>

                {{-- Error --}}
                <div id="sanadError" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                        <i class="ki-filled ki-alert-triangle text-red-500 text-2xl mb-2"></i>
                        <p class="text-red-600 font-medium" id="sanadErrorMessage">
                            {{ translate('Unable to load SANAD details.') }}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            window.openSanadModal = async function(orderId) {
                const loading = document.getElementById('sanadLoading');
                const content = document.getElementById('sanadContent');
                const error = document.getElementById('sanadError');
                const errorMsg = document.getElementById('sanadErrorMessage');
                const cardsContainer = document.getElementById('sanadDetailCards');
                const downloadBtn = document.getElementById('sanadDownloadBtn');

                // Reset UI
                loading.classList.remove('hidden');
                content.classList.add('hidden');
                error.classList.add('hidden');
                cardsContainer.innerHTML = '';

                try {
                    const detailUrl = "{{ route('sanad.detail', ['order' => ':id']) }}".replace(':id',
                        encodeURIComponent(orderId));

                    const res = await fetch(detailUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const json = await res.json().catch(() => ({
                        success: false,
                        message: '{{ translate('Invalid JSON response') }}'
                    }));

                    if (!res.ok || json.success === false) {
                        throw new Error(json.message || json.error ||
                            '{{ translate('Failed to fetch SANAD details.') }}');
                    }

                    const data = json.data || {};

                    // Field list
                    const fields = [{
                            label: '{{ translate('SANAD ID') }}',
                            value: data.id
                        },
                        {
                            label: '{{ translate('SANAD Number') }}',
                            value: data.number
                        },
                        {
                            label: '{{ translate('Reference ID') }}',
                            value: data.reference_id
                        },
                        {
                            label: '{{ translate('Due Type') }}',
                            value: data.due_type
                        },
                        {
                            label: '{{ translate('Due Date') }}',
                            value: formatDate(data.due_date)
                        },
                        {
                            label: '{{ translate('Total Value') }}',
                            value: data.total_value ? `${data.total_value} SAR` : ''
                        },
                        {
                            label: '{{ translate('Status') }}',
                            value: formatStatus(data.status)
                        },
                        {
                            label: '{{ translate('Code') }}',
                            value: data.code
                        },
                        {
                            label: '{{ translate('Created At') }}',
                            value: formatDate(data.created_at)
                        },
                        {
                            label: '{{ translate('Updated At') }}',
                            value: formatDate(data.updated_at)
                        },
                    ];

                    // Render cards
                    cardsContainer.innerHTML = fields
                        .filter(f => f.value)
                        .map(f => renderCard(f.label, f.value))
                        .join('');

                    // Update download link
                    const downloadUrl = "{{ route('sanad.download', ['order' => ':id']) }}".replace(':id',
                        encodeURIComponent(orderId));
                    downloadBtn.setAttribute('href', downloadUrl);

                    // Show content
                    loading.classList.add('hidden');
                    content.classList.remove('hidden');

                } catch (err) {
                    loading.classList.add('hidden');
                    content.classList.add('hidden');
                    error.classList.remove('hidden');
                    errorMsg.textContent = err.message || '{{ translate('Error loading SANAD details.') }}';

                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('Error Loading SANAD') }}',
                        text: err.message ||
                            '{{ translate('Something went wrong while fetching SANAD details.') }}',
                        confirmButtonColor: '#d33',
                    });
                }
            };

            // Helpers
            function renderCard(label, value) {
                return `
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition-all duration-200">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">${label}</p>
                <p class="text-sm font-semibold text-gray-800">${value}</p>
            </div>
        `;
            }

            function formatStatus(status) {
                if (!status) return '';
                const classes = {
                    pending: 'bg-yellow-100 text-yellow-800',
                    no_response: 'bg-gray-200 text-gray-800',
                    approved: 'bg-green-100 text-green-800',
                    rejected: 'bg-red-100 text-red-800'
                };
                const cls = classes[status] || 'bg-blue-100 text-blue-800';
                return `<span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${cls}">${status.replace('_', ' ').toUpperCase()}</span>`;
            }

            function formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleString('en-SA', {
                    dateStyle: 'medium',
                    timeStyle: 'short'
                });
            }
        })();
    </script>
@endpush
