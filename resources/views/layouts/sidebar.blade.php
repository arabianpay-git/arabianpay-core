<!-- Sidebar -->

@php
    $hasNewCustomers = App\Models\Customer::where('status', 'pending')->exists();
    $hasNewSuppliers = App\Models\Merchant::where('status', 'pending')->exists();
    $hasNewProduct = App\Models\Product::where('approved', 'pending')->exists();
    $hasNewTicket = App\Models\SupportTicket::where('status', 'active')->whereNull('assigned_to')->exists();
    $hasNewInternelTicket = App\Models\SupportTicket::where('status', 'active')
        ->where('assigned_to', Auth::id())
        ->exists();
@endphp

<div class="sidebar dark:bg-coal-600 bg-light border-e border-e-gray-200 dark:border-e-coal-100 fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0"
    data-drawer="true" data-drawer-class="drawer drawer-start top-0 bottom-0" data-drawer-enable="true|lg:false"
    id="sidebar">
    <div class="sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0"
        id="sidebar_header">
        <a class="dark:hidden" href="{{ route('dashboard') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo.svg') }}" />
            <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo.svg') }}" />
        </a>
        <a class="hidden dark:block" href="{{ route('dashboard') }}">
            <img class="default-logo min-h-[22px] max-w-none"
                src="{{ asset('assets/media/app/default-logo-dark.svg') }}" />
            <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo.svg') }}" />
        </a>
        <button
            class="btn btn-icon btn-icon-md size-[30px] rounded-lg border border-gray-200 dark:border-gray-300 bg-light text-gray-500 hover:text-gray-700 toggle absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
            data-toggle="body" data-toggle-class="sidebar-collapse" id="sidebar_toggle">
            <i
                class="ki-filled ki-black-left-line toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:toggle-active:rotate-0">
            </i>
        </button>
    </div>
    <div class="sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
        <div class="scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3" data-scrollable="true"
            data-scrollable-dependencies="#sidebar_header" data-scrollable-height="auto" data-scrollable-offset="0px"
            data-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
            <!-- Sidebar Menu -->
            <div class="menu flex flex-col grow gap-0.5" data-menu="true" data-menu-accordion-expand-all="false"
                id="sidebar_menu">
                <a href="{{ route('dashboard') }}">
                    <div class="menu-item">
                        <div class="menu-label border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            href="" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-element-8 text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800">
                                {{ translate('Dashboard') }}
                            </span>
                        </div>
                    </div>
                </a>

                @canany(['customer.view', 'customer.manage', 'supplier.view', 'supplier.manage', 'nafath.view'])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-user-square text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Accounts') }}
                            </span>
                            @if ($hasNewCustomers || $hasNewSuppliers)
                                <span class="menu-badge me-[-10px]">
                                    <span class="badge badge-success badge-xs">
                                        {{ translate('New') }}
                                    </span>
                                </span>
                            @endif
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('customer.view')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('customers') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Customers') }}
                                        </span>
                                        @if ($hasNewCustomers)
                                            <span class="menu-badge me-[-10px]">
                                                <span class="badge badge-success badge-xs">
                                                    {{ translate('New') }}
                                                </span>
                                            </span>
                                        @endif
                                    </a>
                                </div>
                            @endcan
                            @can('supplier.view')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('suppliers') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier') }}
                                        </span>

                                        @if ($hasNewSuppliers)
                                            <span class="menu-badge me-[-10px]">
                                                <span class="badge badge-success badge-xs">
                                                    {{ translate('New') }}
                                                </span>
                                            </span>
                                        @endif
                                    </a>
                                </div>
                            @endcan
                            @can('nafath.view')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('nafath') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Nafath') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany
                @canany(['product.create', 'product.read', 'product.approval', 'product.reviews', 'category.create',
                    'category.read', 'brand.create', 'brand.read', 'attribute.read', 'statics.product', 'statics.category',
                    'statics.brand', 'statics.reviews'])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        @canany(['product.create', 'product.read', 'product.approval', 'product.reviews'])
                            <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                                tabindex="0">
                                <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                    <i class="ki-filled ki-parcel text-lg"> </i>
                                </span>
                                <span
                                    class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                    {{ translate('Products') }}
                                </span>
                                @if ($hasNewProduct)
                                    <span class="menu-badge me-[-10px]">
                                        <span class="badge badge-success badge-xs">
                                            {{ translate('New') }}
                                        </span>
                                    </span>
                                @endif
                                <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                    <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                    <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                </span>
                            </div>
                            <div
                                class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                    <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                            {{ translate('Products') }}
                                        </span>
                                        @if ($hasNewProduct)
                                            <span class="menu-badge me-[-10px]">
                                                <span class="badge badge-success badge-xs">
                                                    {{ translate('New') }}
                                                </span>
                                            </span>
                                        @endif
                                        <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                            <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                            <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                        </span>
                                    </div>

                                    <div
                                        class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                        @can('product.create')
                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('products.create') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Add New Product') }}
                                                    </span>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('product.create')
                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('productsBulkUpload') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Bulk Import Product') }}
                                                    </span>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('product.read')
                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('products.index') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('All Products') }}
                                                    </span>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('product.approval')
                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('productApproval') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Product Approval') }}
                                                    </span>
                                                    @if ($hasNewProduct)
                                                        <span class="menu-badge me-[-10px]">
                                                            <span class="badge badge-success badge-xs">
                                                                {{ translate('New') }}
                                                            </span>
                                                        </span>
                                                    @endif
                                                </a>
                                            </div>
                                        @endcan
                                        @can('product.reviews')
                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('productReviews') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Product Reviews') }}
                                                    </span>
                                                </a>
                                            </div>
                                        @endcan
                                    </div>
                                </div>

                                @canany(['category.create', 'category.read'])
                                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                            tabindex="0">
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                            </span>
                                            <span
                                                class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                                {{ translate('Categories') }}
                                            </span>
                                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                            </span>
                                        </div>
                                        <div
                                            class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                            @can('category.create')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('categories.create') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('Add New Category') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('category.read')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('categories.index') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('All Categories') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                @endcanany
                                @canany(['brand.create', 'brand.read'])
                                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                            tabindex="0">
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                            </span>
                                            <span
                                                class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                                {{ translate('Brands') }}
                                            </span>
                                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                            </span>
                                        </div>
                                        <div
                                            class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                            @can('brand.create')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('brands.create') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('Add New Brand') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('brand.read')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('brands.index') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('All Brands') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                @endcanany
                                @can('attribute.read')
                                    <div class="menu-item">
                                        <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('attributes.index') }}" tabindex="0">
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                            </span>
                                            <span
                                                class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                {{ translate('Attributes') }}
                                            </span>
                                        </a>
                                    </div>
                                @endcan

                                @canany(['statics.product', 'statics.category', 'statics.brand', 'statics.reviews'])
                                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                            tabindex="0">
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                            </span>
                                            <span
                                                class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                                {{ translate('Statics') }}
                                            </span>
                                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                            </span>
                                        </div>
                                        <div
                                            class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                            @can('statics.product')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('products.statics') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('Product') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('statics.category')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('categories.statics') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('Category') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('statics.brand')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('brands.statics') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('Brand') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('statics.reviews')
                                                <div class="menu-item">
                                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                        href="{{ route('reviews.statics') }}" tabindex="0">
                                                        <span
                                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                        </span>
                                                        <span
                                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                            {{ translate('Reviews') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                @endcanany
                            </div>
                        @endcanany
                    </div>
                @endcanany

                @canany(['order.manage', 'shipping_order.manage'])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-courier-express text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Orders') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('order.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('orders') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('All Order') }}
                                        </span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('orders.confirmed') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Confirmed Order') }}
                                        </span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('orders.processing') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Processing Orders') }}
                                        </span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('orders.cancelled') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Cancelled Orders') }}
                                        </span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('orders.failed') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Failed Orders') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                            @can('shipping_order.manage')
                                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                    <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                            {{ translate('Shipping Orders') }}
                                        </span>
                                        <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                            <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                            <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                        </span>
                                    </div>
                                    <div
                                        class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('shippingOrders') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('All Shipping Orders') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('shippingOrder', ['status' => 'shipped']) }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Confirmed Shipping Orders') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('shippingOrder', ['status' => 'pending']) }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Pending Shipping Orders') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('shippingOrder', ['status' => 'delivered']) }}"
                                                tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Delivered Shipping Orders') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('shippingOrder', ['status' => 'returned']) }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Returned Shipping Orders') }}
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>

                @endcanany

                @canany(['transaction.view', 'schedule_payment.view', 'supplier_and_sales.view',
                    'customer_and_sales.view'])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-wallet text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Financial') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('transaction.view')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('transactionHistory') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Transactions') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @canany('schedule_payment.view')
                                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                    <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                            {{ translate('Scheduled Payments') }}
                                        </span>
                                        <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                            <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                            <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                        </span>
                                    </div>
                                    <div
                                        class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                        @can('schedule_payment.view')
                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('payments') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('All Payments') }}
                                                    </span>
                                                </a>
                                            </div>

                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('pendingPayments') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Pending Payments') }}
                                                    </span>
                                                </a>
                                            </div>

                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('duePayments') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Due Payments') }}
                                                    </span>
                                                </a>
                                            </div>

                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('latePayments') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Late Payments') }}
                                                    </span>
                                                </a>
                                            </div>

                                            <div class="menu-item">
                                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                    href="{{ route('paidPayments') }}" tabindex="0">
                                                    <span
                                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                    </span>
                                                    <span
                                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                        {{ translate('Paid Payments') }}
                                                    </span>
                                                </a>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            @endcanany

                            @can('supplier_and_sales.view')
                                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                    <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                            {{ translate('Supplier & Sales') }}
                                        </span>
                                        <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                            <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                            <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                        </span>
                                    </div>
                                    <div
                                        class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('detailPurchases') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Detailed Purchases') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('totalPurchases') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Total Purchases') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('paymentOfSupplier') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Payment of Suppliers') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('detailedSupplierDebt') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Detailed Supplier Debt') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('totalSupplierDebt') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Total Supplier Debt') }}
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcan

                            @can('customer_and_sales.view')
                                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                    <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                            {{ translate('Customer & Sales') }}
                                        </span>
                                        <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                            <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                            <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                        </span>
                                    </div>
                                    <div
                                        class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('saleReport') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Detailed Sale Reports') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('totalSaleReport') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Total Sale Reports') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('collectionReport') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Customer Collection Reports') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('detailedCustomerDebt') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Detailed Customer Debt') }}
                                                </span>
                                            </a>
                                        </div>

                                        <div class="menu-item">
                                            <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                                href="{{ route('totalCustomerDebt') }}" tabindex="0">
                                                <span
                                                    class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                                </span>
                                                <span
                                                    class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                    {{ translate('Total Customer Debt') }}
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @can('risk.managment')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-book text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Risk Management') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('risk.score') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Scoring Engine') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('risk-register.index') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Risk Register Table') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('real-time-alerts.index') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Alerts Engine') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('case-management.index') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Case Manager') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('risk.score', ['order' => 'asc']) }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Audit Trail') }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('credit.managment')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-graph-3 text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Credit Management') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('creditProfile') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Customer Credit Profile') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('creditLimit') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Credit Limit Engine') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('repaymentSchedule') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Repayment Schedule Engine') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="#" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Credit Application System') }}
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="#" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Credit Adjustment Requests') }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('plan.read')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <a href="{{ route('instalment-plans.index') }}">
                            <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                                tabindex="0">
                                <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                    <i class="ki-filled ki-data text-lg"> </i>
                                </span>
                                <span
                                    class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                    {{ translate('Instalment Plans') }}
                                </span>
                            </div>
                        </a>
                    </div>
                @endcan

                @canany(['coupon.read', 'coupon.create', 'coupon.update', 'coupon.delete', 'abandoned_cart.view',
                    'notification.manage', 'ad.manage'])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-chart-line-up text-lg"></i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Marketing') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"></i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"></i>
                            </span>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('coupon.read')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('coupons.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"></span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Coupons') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('abandoned_cart.view')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"></span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Abandoned Carts') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('notification.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"></span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Notifications') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('ad.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"></span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Ads Management') }}
                                        </span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"></span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Ads Statistics') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @can('ticket.manage')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <a href="{{ route('tickets') }}">
                            <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                                tabindex="0">
                                <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                    <i class="ki-filled ki-support text-lg"> </i>
                                </span>
                                <span
                                    class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                    {{ translate('Support & Tickets') }}
                                </span>
                                @if ($hasNewTicket)
                                    <span class="menu-badge me-[-10px]">
                                        <span class="badge badge-success badge-xs">
                                            {{ translate('New') }}
                                        </span>
                                    </span>
                                @endif
                            </div>
                        </a>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <a href="{{ route('internelTickets') }}">
                            <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                                tabindex="0">
                                <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                    <i class="ki-filled ki-messages text-lg"> </i>
                                </span>
                                <span
                                    class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                    {{ translate('Internal Ticket') }}
                                </span>
                                @if ($hasNewInternelTicket)
                                    <span class="menu-badge me-[-10px]">
                                        <span class="badge badge-success badge-xs">
                                            {{ translate('New') }}
                                        </span>
                                    </span>
                                @endif
                            </div>
                        </a>
                    </div>
                @endcan

                @can('package.manage')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <a href="{{ route('packages.index') }}">
                            <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                                tabindex="0">
                                <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                    <i class="ki-filled ki-badge text-lg"> </i>
                                </span>
                                <span
                                    class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                    {{ translate('Customer Packages') }}
                                </span>
                            </div>
                        </a>
                    </div>
                @endcan

                @can('media.manage')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <a href="{{ route('media.index') }}">
                            <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                                tabindex="0">
                                <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                    <i class="ki-filled ki-picture text-lg"> </i>
                                </span>
                                <span
                                    class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                    {{ translate('Media Uploads') }}
                                </span>
                            </div>
                        </a>
                    </div>
                @endcan

                @can('report.view')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-financial-schedule text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Reports') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('portfolioPerformanceReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Portfolio Performance') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('merchantCreditHistoryReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Merchant Credit History') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('supplierTransactionReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Supplier Transaction') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('instalmentRepaymentReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Instalment Repayment') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('riskExposureAnalysis') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Risk Exposure') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('amlActivityReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('AML Activity') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('collectionEfficiencyReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Collection Efficiency') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('onboardingFunnelReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Onboarding Funnel') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('regulatoryComplianceReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Regulatory Compliance') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('systemActivityAuditReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('System Activity & Audit') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('financialSummaryReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Financial Summary') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('delinquencyAgingReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Delinquency Aging') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('productSkuPerformanceReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Product & SKU Performance') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('supportTicketResolutionReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Support Ticket Resolution') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('campaignEffectivenessReport') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Campaign Effectiveness') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('productStock') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Product Stock') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('productWishlist') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Wishlist Products') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('userSearch') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('User Searches') }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('refund.manage')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-update-folder text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Refunds') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('refund-requests') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Refund Requests') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('refund-requests.status', ['status' => 'approved']) }}"
                                    tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Approved Refunds') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('refund-requests.status', ['status' => 'rejected']) }}"
                                    tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Rejected Refunds') }}
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('refund-requests.status', ['status' => 'pending']) }}"
                                    tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Pending Refunds') }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                @canany(['role.manage', 'role.permission', 'role.assign', 'role.user', 'role.create_admin_user'])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-user-tick text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Role and Permission') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('role.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('roles.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Roles') }}
                                        </span>
                                    </a>
                                </div>

                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('supplier_roles.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier Roles') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('role.permission')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('permissions.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Permissions') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('role.assign')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('role-permissions.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Permission for Role') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            {{-- The commented out section for 'role.user' has been omitted as per the original request. --}}

                            @can('role.create_admin_user')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('user-roles.subadmin') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Create Sub Admin') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @can('employee.read')
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-face-id text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Employee Management') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('employees.index') }}" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                    </span>
                                    <span
                                        class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        {{ translate('Employees') }}
                                    </span>
                                </a>
                            </div>

                            @can('employee.read')
                                {{-- This nested @can seems redundant if the parent @can('employee.read') already covers it. --}}
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('departments.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Department') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcan

                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                    <a href="{{ route('transferRequests.index') }}">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-disconnect text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Transfer Requests') }}
                            </span>
                        </div>
                    </a>
                </div>

                <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                    <a href="{{ route('activity-logs.index') }}">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-abstract-14 text-lg"></i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Activity Logs') }}
                            </span>
                        </div>
                    </a>
                </div>

                @canany([
                    // Supplier Management
                    'supplier_entitlement.manage',
                    'supplier_account.manage',
                    'supplier_payout.manage',

                    // Locations (these are grouped below, but kept here for the canany check)
                    'country.read',
                    'country.create',
                    'country.update',
                    'country.delete',
                    'state.read',
                    'state.create',
                    'state.update',
                    'state.delete',
                    'city.read',
                    'city.create',
                    'city.update',
                    'city.delete',

                    // Business Types & Categories (these are grouped below, but kept here for the canany check)
                    'business_type.read',
                    'business_type.create',
                    'business_type.update',
                    'business_type.delete',
                    'business_category.read',
                    'business_category.create',
                    'business_category.update',
                    'business_category.delete',
                    ])
                    <div class="menu-item pt-2.25 pb-px">
                        <span class="menu-heading uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]">
                            {{ translate('Supplier Management') }}
                        </span>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-people text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                {{ translate('Supplier Accounts') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>

                        <div
                            class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('supplier_entitlement.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('supplierEntitilements') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier Entitlements') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('supplier_account.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('supplierAccounts') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier Accounts') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('supplier_payout.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('supplierPayouts') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier Payouts') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany([
                    // Locations
                    'country.read',
                    'country.create',
                    'country.update',
                    'country.delete',
                    'state.read',
                    'state.create',
                    'state.update',
                    'state.delete',
                    'city.read',
                    'city.create',
                    'city.update',
                    'city.delete',
                    ])
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-map text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                {{ translate('Locations') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('country.read')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('countries.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Countries') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('state.read')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('states.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('States') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('city.read')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('cities.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Cities') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany([
                    // Business Types & Categories
                    'business_type.read',
                    'business_type.create',
                    'business_type.update',
                    'business_type.delete',
                    'business_category.read',
                    'business_category.create',
                    'business_category.update',
                    'business_category.delete',
                    ])

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-category text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                {{ translate('Supplier Type & Category') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('business_type.read')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('business-types.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier Type') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('business_category.read')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                        href="{{ route('business-categories.index') }}" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Supplier Category') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany

                @canany(['settings.manage', 'faq.manage', 'page.manage'])
                    <div class="menu-item pt-2.25 pb-px">
                        <span class="menu-heading uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]">
                            {{ translate('Web & App') }}
                        </span>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                            tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-setting-2 text-lg"> </i>
                            </span>
                            <span
                                class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                {{ translate('Setup & Configuration') }}
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div
                            class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            @can('settings.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('App Settings') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('faq.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('FAQ\'s') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan

                            @can('page.manage')
                                <div class="menu-item">
                                    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                        href="#" tabindex="0">
                                        <span
                                            class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
                                        </span>
                                        <span
                                            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                            {{ translate('Pages') }}
                                        </span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcanany
            </div>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</div>
<!-- End of Sidebar -->

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const currentUrl = window.location.href;

            document.querySelectorAll('.menu-item > a').forEach(anchor => {
                if (anchor.href === currentUrl) {
                    const menuItem = anchor.closest('.menu-item');
                    const menuLink = anchor.querySelector('.menu-link');
                    const menuTitle = anchor.querySelector('.menu-title');
                    const menuIcon = anchor.querySelector('.menu-icon');

                    if (menuItem) {
                        menuItem.classList.add('active');
                    }

                    // Apply active classes to direct links (non-accordion items)
                    if (!menuItem.querySelector('.menu-accordion')) {
                        if (menuLink) {
                            menuLink.classList.add('active-link');
                        }
                        if (menuTitle) {
                            menuTitle.classList.add('active-title');
                        }
                        if (menuIcon) {
                            menuIcon.classList.add('active-icon');
                        }
                    }

                    // Open parent accordion menus
                    let parent = menuItem.parentElement;
                    while (parent) {
                        const accordion = parent.closest('.menu-item[data-menu-item-toggle="accordion"]');
                        if (accordion) {
                            accordion.classList.add('menu-item-show', 'show');
                            parent = accordion.parentElement;
                        } else {
                            break;
                        }
                    }
                }
            });
        });
    </script>
@endpush
