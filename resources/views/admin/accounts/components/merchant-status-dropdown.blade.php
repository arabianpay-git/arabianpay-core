<div class="dropdown" data-dropdown="true" data-dropdown-placement="bottom-end" data-dropdown-trigger="click"
    id="status_dropdown">
    <button class="dropdown-toggle btn btn-sm btn-light border-slate-300 flex items-center gap-2">
        <i class="ki-filled ki-setting-4 text-base"></i>
        <span>{{ translate('Manage Status') }}</span>
        <i class="ki-filled ki-down text-xs"></i>
    </button>

    <div class="dropdown-content menu-default w-full max-w-[220px] shadow-lg border border-slate-200">
        <form action="{{ route('updateSupplierStatus', ['id' => $merchant->user_id]) }}" method="POST" id="statusForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" id="statusInput">

            @php
                $statusItems = [
                    'active' => ['icon' => 'copy-success', 'label' => 'Active'],
                    'under_review' => ['icon' => 'watch', 'label' => 'Under Review'],
                    'contract_sent' => ['icon' => 'update-file', 'label' => 'Contract Sent'],
                    'approved' => ['icon' => 'shield-tick', 'label' => 'Approve', 'modal' => '#approve_modal'],
                    'suspended' => ['icon' => 'shield-cross', 'label' => 'Suspended'],
                    'pending' => ['icon' => 'timer', 'label' => 'Pending'],
                    'blacklisted' => ['icon' => 'wrench', 'label' => 'Blacklisted'],
                ];
            @endphp

            @foreach ($statusItems as $key => $item)
                <div class="menu-item {{ $merchant->status == $key ? 'active' : '' }}">
                    @if (isset($item['modal']))
                        {{-- Added onclick to dismiss dropdown manually before opening modal --}}
                        <button type="button" class="menu-link flex items-center w-full"
                            data-modal-toggle="{{ $item['modal'] }}" onclick="dismissDropdown('status_dropdown')">
                            <span class="menu-icon"><i class="ki-filled ki-{{ $item['icon'] }}"></i></span>
                            <span class="menu-title">{{ translate($item['label']) }}</span>
                        </button>
                    @else
                        <a class="menu-link flex items-center" href="javascript:void(0)"
                            onclick="submitStatus('{{ $key }}')">
                            <span class="menu-icon"><i class="ki-filled ki-{{ $item['icon'] }}"></i></span>
                            <span class="menu-title">{{ translate($item['label']) }}</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </form>
    </div>
</div>


@push('scripts')
    <script>
        /**
         * Manually dismiss Metronic Dropdown
         */
        function dismissDropdown(id) {
            const element = document.getElementById(id);
            if (element) {
                const dropdown = KTDropdown.getInstance(element);
                if (dropdown) {
                    dropdown.hide();
                }
            }
        }

        /**
         * Unified Status Submitter
         */
        function submitStatus(status) {
            // Dismiss the dropdown immediately
            dismissDropdown('status_dropdown');

            if (status === 'approved') {
                // Modal is handled by data-modal-toggle, but we double-check here
                return;
            }

            const input = document.getElementById('statusInput');
            const form = document.getElementById('statusForm');

            if (input && form) {
                input.value = status;
                form.submit();
            }
        }
    </script>
@endpush
