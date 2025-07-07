@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush


<div class="container-fixed">
    <div
        class="flex items-center flex-wrap md:flex-nowrap lg:items-end justify-between border-b border-b-gray-200 dark:border-b-coal-100 gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="grid">
            <div class="scrollable-x-auto">
                <div class="menu gap-3" data-menu="true">
                    <div
                        class="menu-item border-b-2 border-b-transparent 
                        {{ Route::is('supplierProfile') ? 'border-b-primary' : '' }} menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierProfile', ['id' => $merchant->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 
                                {{ Route::is('supplierProfile') ? 'text-primary font-semibold' : 'menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary' }}">
                                Profile
                            </span>
                        </a>
                    </div>

                    <div
                        class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="{{ Route::is('supplierTransactions') ? 'border-b-primary' : '' }} menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierTransactions', ['id' => $merchant->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 
                                {{ Route::is('supplierTransactions') ? 'border-b-primary' : '' }}
                                 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Transactions
                            </span>
                        </a>
                    </div>

                    <div
                        class="{{ Route::is('supplierOrders') ? 'border-b-primary' : '' }} menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierOrders', ['id' => $merchant->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 
                                {{ Route::is('supplierOrders') ? 'border-b-primary' : '' }} menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Orders
                            </span>
                        </a>
                    </div>

                    <div
                        class="{{ Route::is('supplierPayments') ? 'border-b-primary' : '' }} menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierPayments', ['id' => $merchant->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 {{ Route::is('supplierPayments') ? 'border-b-primary' : '' }}
                                menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Payments
                            </span>
                        </a>
                    </div>

                    <div
                        class="{{ Route::is('supplierFinance') ? 'border-b-primary' : '' }} menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierFinance', ['id' => $merchant->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 {{ Route::is('supplierFinance') ? 'border-b-primary' : '' }}
                                menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Finance
                            </span>
                        </a>
                    </div>

                    <div
                        class="menu-item border-b-2 border-b-transparent 
                        {{ Route::is('supplierProducts') ? 'border-b-primary' : '' }} menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierProducts', ['id' => $merchant->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 
                                {{ Route::is('supplierProducts') ? 'text-primary font-semibold' : 'menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary' }}">
                                Products
                            </span>
                        </a>
                    </div>

                    <div
                        class="{{ Route::is('supplierSales') ? 'border-b-primary' : '' }} menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierSales', ['id' => $merchant->user_id]) }}">
                            <span
                                class="{{ Route::is('supplierSales') ? 'border-b-primary' : '' }} menu-title text-nowrap font-medium text-sm text-gray-700 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Sales
                            </span>
                        </a>
                    </div>

                    <div
                        class="{{ Route::is('supplierCompliance') ? 'border-b-primary' : '' }} menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('supplierCompliance', ['id' => $merchant->user_id]) }}">
                            <span
                                class="{{ Route::is('supplierCompliance') ? 'border-b-primary' : '' }} menu-title text-nowrap font-medium text-sm text-gray-700 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Compliance
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @can('supplier.manage')
            <div class="flex items-center justify-end grow lg:grow-0 lg:pb-4 gap-2.5 mb-3 lg:mb-0">
                <div class="dropdown" data-dropdown="true" data-dropdown-placement="bottom-end"
                    data-dropdown-placement-rtl="bottom-start" data-dropdown-trigger="click">
                    <button class="dropdown-toggle btn btn-sm btn-icon btn-light">
                        <i class="ki-filled ki-dots-vertical"> </i>
                    </button>
                    <div class="dropdown-content menu-default w-full max-w-[220px]">
                        <form action="{{ route('updateSupplierStatus', ['id' => $merchant->user_id]) }}" method="POST"
                            id="statusForm">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" id="statusInput">

                            <!-- Approve Menu Item -->
                            <div class="menu-item status {{ $merchant->status == 'approved' ? 'active' : '' }}"
                                data-dropdown-dismiss="true" data-status="approved">
                                <button type="button" class="menu-link" data-modal-toggle="#approve_modal">
                                    <span class="menu-icon">
                                        <i class="ki-filled ki-shield-tick"></i>
                                    </span>
                                    <span class="menu-title">Approve</span>
                                </button>
                            </div>

                            <!-- Suspended Menu Item -->
                            <div class="menu-item status {{ $merchant->status == 'suspended' ? 'active' : '' }}"
                                data-dropdown-dismiss="true" data-status="suspended">
                                <a class="menu-link" href="javascript:void(0)" onclick="submitStatus('suspended')">
                                    <span class="menu-icon">
                                        <i class="ki-filled ki-shield-cross"></i>
                                    </span>
                                    <span class="menu-title">Suspended</span>
                                </a>
                            </div>

                            <!-- Pending Menu Item -->
                            <div class="menu-item status {{ $merchant->status == 'pending' ? 'active' : '' }}"
                                data-dropdown-dismiss="true" data-status="pending">
                                <a class="menu-link" href="javascript:void(0)" onclick="submitStatus('pending')">
                                    <span class="menu-icon">
                                        <i class="ki-filled ki-watch"></i>
                                    </span>
                                    <span class="menu-title">Pending</span>
                                </a>
                            </div>

                            <!-- Blacklisted Menu Item -->
                            <div class="menu-item status {{ $merchant->status == 'blacklisted' ? 'active' : '' }}"
                                data-dropdown-dismiss="true" data-status="blacklisted">
                                <a class="menu-link" href="javascript:void(0)" onclick="submitStatus('blacklisted')">
                                    <span class="menu-icon">
                                        <i class="ki-filled ki-wrench"></i>
                                    </span>
                                    <span class="menu-title">Blacklisted</span>
                                </a>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        @endcan
    </div>
</div>


<div class="modal" data-modal="true" id="approve_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">Supplier Approval</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <form action="{{ route('updateSupplierStatusApprove', ['id' => $merchant->user_id]) }}" method="POST"
            class="modal-body p-5" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input type="hidden" name="status" value="approved">

            @php
                $service = new App\Services\RiskAnalyticsService();
                $user = App\Models\User::find($merchant->user_id);
                $riskScore = $service->calculateForUser($user);
            @endphp

            <input type="hidden" name="fahman_score" value="{{ $riskScore->total_score }}">

            <div class="mb-4">
                <label for="commission" class="block text-sm font-medium text-gray-700">Commission Percentage</label>
                <input type="number" name="commission" id="commission" class="input" step="0.01"
                    min="0" max="100" value="{{ old('commission') }}" required>
            </div>

            <div class="mb-4">
                <label for="payment_schedule" class="block text-sm font-medium text-gray-700">Payment Schedule
                    (Days)</label>
                <input type="number" name="payment_schedule" id="payment_schedule" class="input" step="1"
                    min="0" max="100" value="{{ old('payment_schedule') }}" required>
            </div>

            <div class="mb-4">
                <label for="reason" class="block text-sm font-medium text-gray-700">Reason to Approve</label>
                <textarea name="reason" id="reason" rows="3" class="textarea" required>{{ old('reason') }}</textarea>
            </div>

            <div class="mb-4">
                <div class="flex items-center w-full max-w-md relative">
                    <!-- Hidden file input -->
                    <input type="file" name="contract" id="contract" class="hidden">

                    <!-- Button to trigger file input -->
                    <button type="button"
                        class="absolute top-0 bottom-0 left-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-l"
                        id="selectFileBtn">
                        <i class="ki-filled ki-folder text-xl"></i>
                    </button>

                    <!-- Readonly input to show selected filename -->
                    <input type="text" id="fileNameDisplay" class="input w-full pl-12"
                        placeholder="Click to select media" readonly style="padding-inline-start: 2.75rem;">
                </div>
            </div>

            <div class="mb-4">
                <label for="contract_end_date" class="block text-sm font-medium text-gray-700">Contract End
                    Date</label>
                <input type="text" name="contract_end_date" id="contract_end_date" class="input w-full flatpickr"
                    value="{{ old('contract_end_date') }}" required>
            </div>



            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Submit Approval</button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('.status').forEach(item => {
            item.addEventListener('click', function(event) {
                const status = this.getAttribute('data-status');
                document.getElementById('statusInput').value = status;

                if (status === 'approved') {
                    // Prevent form submission if status is "approved"
                    event.preventDefault();
                    console.log('Approval selected. Form submission prevented.');
                    // Do your custom JS logic here for "approved"
                    return;
                }

                document.getElementById('statusForm').submit();
            });
        });

        function submitStatus(status) {
            document.getElementById('statusInput').value = status;

            if (status === 'approved') {
                console.log('Approval selected via function. Form submission prevented.');
                // Do your custom JS logic here for "approved"
                return;
            }

            document.getElementById('statusForm').submit();
        }
    </script>

    <script>
        const fileInput = document.getElementById('contract');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const selectFileBtn = document.getElementById('selectFileBtn');

        // When button or input clicked, open file dialog
        selectFileBtn.addEventListener('click', () => fileInput.click());
        fileNameDisplay.addEventListener('click', () => fileInput.click());

        // On file select, show filename in text input
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                fileNameDisplay.value = fileInput.files[0].name;
            } else {
                fileNameDisplay.value = '';
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr(".flatpickr", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            time_24hr: true
        });
    </script>
@endpush
