<div class="container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-end justify-between border-b border-b-gray-200 dark:border-b-coal-100 gap-3 lg:gap-6 mb-5 lg:mb-10">
        <div class="grid">
            <div class="scrollable-x-auto">
                <div class="menu gap-3" data-menu="true">
                    <div class="menu-item border-b-2 border-b-transparent 
                        {{ Route::is('supplierProfile') ? 'border-b-primary' : '' }} menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('supplierProfile', ['id' => $merchant->id]) }}">
                            <span class="menu-title text-nowrap font-medium text-sm text-gray-700 
                                {{ Route::is('supplierProfile') ? 'text-primary font-semibold' : 'menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary' }}">
                                Profile
                            </span>
                        </a>
                    </div>

                    <div class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('supplierProfile', ['id' => $merchant->id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary"
                            >
                                Transactions
                            </span>
                        </a>
                    </div>

                    <div class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('supplierProfile', ['id' => $merchant->id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary"
                            >
                                Orders
                            </span>
                        </a>
                    </div>

                    <div class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('supplierProfile', ['id' => $merchant->id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary"
                            >
                                Payments
                            </span>
                        </a>
                    </div>

                    <div class="menu-item border-b-2 border-b-transparent 
                        {{ Route::is('supplierProducts') ? 'border-b-primary' : '' }} menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('supplierProducts', ['id' => $merchant->user_id]) }}">
                            <span class="menu-title text-nowrap font-medium text-sm text-gray-700 
                                {{ Route::is('supplierProducts') ? 'text-primary font-semibold' : 'menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary' }}">
                                Products
                            </span>
                        </a>
                    </div>


                    <div class="menu-item border-b-2 border-b-transparent menu-item-active:border-b-primary menu-item-here:border-b-primary">
                        <a class="menu-link gap-1.5 pb-2 lg:pb-4 px-2" href="{{ route('supplierProfile', ['id' => $merchant->id]) }}">
                            <span
                                class="menu-title text-nowrap font-medium text-sm text-gray-700 menu-item-active:text-primary menu-item-active:font-semibold menu-item-here:text-primary menu-item-here:font-semibold menu-item-show:text-primary menu-link-hover:text-primary"
                            >
                                Sales
                            </span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <div class="flex items-center justify-end grow lg:grow-0 lg:pb-4 gap-2.5 mb-3 lg:mb-0">
            <div class="dropdown" data-dropdown="true" data-dropdown-placement="bottom-end" data-dropdown-placement-rtl="bottom-start" data-dropdown-trigger="click">
                <button class="dropdown-toggle btn btn-sm btn-icon btn-light">
                    <i class="ki-filled ki-dots-vertical"> </i>
                </button>
                <div class="dropdown-content menu-default w-full max-w-[220px]">
                    <form action="{{ route('updateSupplierStatus', ['id' => $merchant->id]) }}" method="POST" id="statusForm">
                        @csrf
                        @method('PUT')
                        <!-- Hidden Input for Status -->
                        <input type="hidden" name="status" id="statusInput">
                
                        <!-- Approve Menu Item -->
                        <div class="menu-item status {{ $merchant->status == 'approved' ? 'active' : '' }}" data-dropdown-dismiss="true" data-status="approved">
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
                        <div class="menu-item status {{ $merchant->status == 'rejected' ? 'active' : '' }}" data-dropdown-dismiss="true" data-status="rejected">
                            <a class="menu-link" href="javascript:void(0)">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-shield-cross"> </i>
                                </span>
                                <span class="menu-title">
                                    Rejected
                                </span>
                            </a>
                        </div>
                
                        <!-- Pending Menu Item -->
                        <div class="menu-item status {{ $merchant->status == 'pending' ? 'active' : '' }}" data-dropdown-dismiss="true" data-status="pending">
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
                        <div class="menu-item status {{ $merchant->status == 'draft' ? 'active' : '' }}" data-dropdown-dismiss="true" data-status="draft">
                            <a class="menu-link" href="javascript:void(0)">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-wrench"> </i>
                                </span>
                                <span class="menu-title">
                                    Draft
                                </span>
                            </a>
                        </div>
                
                        <!-- Block Menu Item -->
                        <div class="menu-item status {{ $merchant->status == 'blocked' ? 'active' : '' }}" data-dropdown-dismiss="true" data-status="blocked">
                            <a class="menu-link" href="javascript:void(0)">
                                <span class="menu-icon">
                                    <i class="ki-filled ki-abstract-11"> </i>
                                </span>
                                <span class="menu-title">
                                    Block
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