<div class="container-fixed">
    <div
        class="flex items-center flex-wrap md:flex-nowrap lg:items-end justify-between border-b border-b-gray-200 dark:border-b-coal-100 gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="grid">
            <div class="scrollable-x-auto">
                <div class="menu gap-3" data-menu="true">

                    {{-- Profile --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerProfile') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerProfile', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerProfile') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Profile
                            </span>
                        </a>
                    </div>

                    {{-- Business Info --}}
                    {{-- <div
                        class="menu-item border-b-2 {{ Route::is('customerBusiness') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerBusiness', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerBusiness') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Business Information
                            </span>
                        </a>
                    </div> --}}

                    {{-- Simah Report --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerSimah') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerSimah', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerSimah') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Simah Report
                            </span>
                        </a>
                    </div>

                    {{-- Financial Info --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerFinance') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerFinance', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerFinance') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Financial Information
                            </span>
                        </a>
                    </div>

                    <div
                        class="{{ Route::is('customerCreditAssessment') ? 'border-b-primary' : '' }} menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerCreditAssessment', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 {{ Route::is('customerCreditAssessment') ? 'border-b-primary' : '' }}
                                menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary">
                                Credit Assessment
                            </span>
                        </a>
                    </div>

                    {{-- Transactions --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerTransactions') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerTransactions', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerTransactions') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Transactions
                            </span>
                        </a>
                    </div>

                    {{-- Orders --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerOrders') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerOrders', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerOrders') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Orders
                            </span>
                        </a>
                    </div>

                    {{-- Payments --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerPayments') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerPayments', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerPayments') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Payments
                            </span>
                        </a>
                    </div>

                    {{-- compliance --}}
                    <div
                        class="menu-item border-b-2 {{ Route::is('customerCompliance') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2"
                            href="{{ route('customerCompliance', ['id' => $customer->user_id]) }}">
                            <span
                                class="menu-title text-sm font-medium {{ Route::is('customerCompliance') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Compliance
                            </span>
                        </a>
                    </div>

                    {{-- Log --}}
                    <div class="menu-item border-b-2 {{ Route::is('customerLog') ? 'border-b-primary' : 'border-b-transparent' }}">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('customerLog', ['id' => $customer->user_id]) }}">
                            <span class="menu-title text-sm font-medium {{ Route::is('customerLog') ? 'text-primary font-semibold' : 'text-gray-700' }}">
                                Log
                            </span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
        <div class="flex items-center justify-end grow lg:grow-0 lg:pb-4 gap-2.5 mb-3 lg:mb-0">
            <div class="dropdown" data-dropdown="true" data-dropdown-placement="bottom-end"
                data-dropdown-placement-rtl="bottom-start" data-dropdown-trigger="click">
                <button class="dropdown-toggle btn btn-sm btn-icon btn-light">
                    <i class="ki-filled ki-dots-vertical"> </i>
                </button>
                <div class="dropdown-content menu-default w-full max-w-[220px]">
                    <form action="{{ route('updateCustomerStatus', ['id' => $customer->id]) }}" method="POST"
                        id="statusForm">
                        @csrf
                        @method('PUT')
                        <!-- Hidden Input for Status -->
                        <input type="hidden" name="status" id="statusInput">

                        <!-- Approve Menu Item -->
                        <div class="menu-item status {{ $customer->status == 'approved' ? 'active' : '' }}"
                            data-dropdown-dismiss="true" data-status="approved">
                            <button type="button" class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-shield-tick"> </i>
                                </span>
                                <span class="menu-title">
                                    Approve
                                </span>
                            </button>
                        </div>

                        <!-- Rejected Menu Item -->
                        <div class="menu-item status {{ $customer->status == 'suspended' ? 'active' : '' }}"
                            data-dropdown-dismiss="true" data-status="suspended">
                            <a class="menu-link" href="javascript:void(0)">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-shield-cross"> </i>
                                </span>
                                <span class="menu-title">
                                    Suspended
                                </span>
                            </a>
                        </div>

                        <!-- Pending Menu Item -->
                        <div class="menu-item status {{ $customer->status == 'pending' ? 'active' : '' }}"
                            data-dropdown-dismiss="true" data-status="pending">
                            <a class="menu-link" href="javascript:void(0)">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-watch"> </i>
                                </span>
                                <span class="menu-title">
                                    Pending
                                </span>
                            </a>
                        </div>

                        <!-- Draft Menu Item -->
                        <div class="menu-item status {{ $customer->status == 'blacklisted' ? 'active' : '' }}"
                            data-dropdown-dismiss="true" data-status="blacklisted">
                            <a class="menu-link" href="javascript:void(0)">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-wrench"> </i>
                                </span>
                                <span class="menu-title">
                                    Blacklisted
                                </span>
                            </a>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('.status').forEach(item => {
            item.addEventListener('click', function() {
                // Get the status from the clicked item
                const status = this.getAttribute('data-status');

                // Set the status in the hidden input field
                document.getElementById('statusInput').value = status;

                // Submit the form
                document.getElementById('statusForm').submit();
            });
        });
    </script>
@endpush
