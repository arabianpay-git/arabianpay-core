    <!-- Sidebar -->
    <div
        class="sidebar dark:bg-coal-600 bg-light border-e border-e-gray-200 dark:border-e-coal-100 fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0"
        data-drawer="true"
        data-drawer-class="drawer drawer-start top-0 bottom-0"
        data-drawer-enable="true|lg:false"
        id="sidebar">
        <div class="sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
            <a class="dark:hidden" href="html/demo1.html">
                <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo.svg' ) }}" />
                <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/mini-logo.svg' ) }}" />
            </a>
            <a class="hidden dark:block" href="html/demo1.html">
                <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/default-logo-dark.svg' ) }}" />
                <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/mini-logo.svg' ) }}" />
            </a>
            <button
                class="btn btn-icon btn-icon-md size-[30px] rounded-lg border border-gray-200 dark:border-gray-300 bg-light text-gray-500 hover:text-gray-700 toggle absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
                data-toggle="body"
                data-toggle-class="sidebar-collapse"
                id="sidebar_toggle"
            >
                <i class="ki-filled ki-black-left-line toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:toggle-active:rotate-0"> </i>
            </button>
        </div>
        <div class="sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
            <div
                class="scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3"
                data-scrollable="true"
                data-scrollable-dependencies="#sidebar_header"
                data-scrollable-height="auto"
                data-scrollable-offset="0px"
                data-scrollable-wrappers="#sidebar_content"
                id="sidebar_scrollable">
                <!-- Sidebar Menu -->
                <div class="menu flex flex-col grow gap-0.5" data-menu="true" data-menu-accordion-expand-all="false" id="sidebar_menu">
                    <div class="menu-item">
                        <div class="menu-label border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" href="" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-element-11 text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800">
                                Dashboard
                            </span>
                        </div>
                    </div>
                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Products
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Products
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('products.create') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Add New Product
                                            </span>
                                        </a>
                                    </div>
                                    
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('products.index') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                All Products
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('productApproval') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Product Approval
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('productReviews') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Product Reviews
                                            </span>
                                        </a>
                                    </div>

                                </div>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Categories
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('categories.create') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Add New Category
                                            </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('categories.index') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                All Categories
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Brands
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('brands.create') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Add New Brand
                                            </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('brands.index') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                All Brands
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('attributes.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Attributes
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Bulk Import / Export
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/3-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Import
                                            </span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Export
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Statics
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('products.statics') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Product
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('categories.statics') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Category
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('brands.statics') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Brand
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="{{ route('reviews.statics') }}"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Reviews
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Orders
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Confirmed Order
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Pending Orders
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Ap Flex Order
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Risk Orders
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Shipping Orders
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/3-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                All Shipping Orders
                                            </span>
                                        </a>
                                    </div>
                                    
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Confirmed Shipping Orders
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Pending Shipping Orders
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Processing Shipping Orders
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Delivered Shipping Orders
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Cancelled Shipping Orders
                                            </span>
                                        </a>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Financial
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Transactions
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('intalment-plans.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Plans
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Scheduled Payments
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/3-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                All Payments
                                            </span>
                                        </a>
                                    </div>
                                    
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Pending Payments
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Due Payments
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Late Payments
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Paid Payments
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Supplier & Sales
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/3-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Detailed Purchases
                                            </span>
                                        </a>
                                    </div>
                                    
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Total Purchases
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Payment of Suppliers
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Detailed Supplier Debt
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Total Supplier Debt
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Supplier Account Statment
                                            </span>
                                        </a>
                                    </div>

                                </div>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Customer & Sales
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/3-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Detailed Sale Reports
                                            </span>
                                        </a>
                                    </div>
                                    
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Total Sale Reports
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Customer Collection Reports
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Detailed Customer Debt
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Total Customer Debt
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Customer Account Statment
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Reconstruction of Customer Debt
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                                <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                        Financial Details
                                    </span>
                                    <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                        <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                        <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                                    </span>
                                </div>
                                <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Users Card
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Currency
                                            </span>
                                        </a>
                                    </div>

                                    <div class="menu-item">
                                        <a
                                            class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                            href="html/demo1/public-profile/projects/2-columns.html"
                                            tabindex="0"
                                        >
                                            <span
                                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                            >
                                            </span>
                                            <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                                Vat & Tax
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Marketing
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('coupons.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Coupons
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Abandoned Carts
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Notifications
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Ads Managment
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Ads Statics
                                    </span>
                                </a>
                            </div>

                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Support & Tickets
                            </span>
                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Media Uploads
                            </span>
                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Reports
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Product Stock
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Wishlist Products
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        User Searches
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Refunds
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Refund Requests
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Approved Refunds
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Rejected Refunds
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Refunds Configuration
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item pt-2.25 pb-px">
                        <span class="menu-heading uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]">
                            Supplier Managment
                        </span>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                            <span
                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                            >
                            </span>
                            <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                Supplier Accounts
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/projects/3-columns.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Supplier Entitilements
                                    </span>
                                </a>
                            </div>
                            
                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/projects/2-columns.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Supplier Accounts
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/projects/2-columns.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Supplier Payouts
                                    </span>
                                </a>
                            </div>

                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                            <span
                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                            >
                            </span>
                            <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                Locations
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('countries.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Countries
                                    </span>
                                </a>
                            </div>
                            
                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('states.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        States
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('cities.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Cities
                                    </span>
                                </a>
                            </div>

                        </div>
                    </div>

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                            <span
                                class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                            >
                            </span>
                            <span class="menu-title text-2sm font-normal me-1 text-gray-800 menu-item-active:text-primary menu-item-active:font-medium menu-link-hover:!text-primary">
                                Business Type & Category
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">
                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('business-types.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Business Type
                                    </span>
                                </a>
                            </div>
                            
                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[5px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{{ route('business-categories.index') }}"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Business Category
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item pt-2.25 pb-px">
                        <span class="menu-heading uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]">
                            Web & App
                        </span>
                    </div>
                    

                    <div class="menu-item" data-menu-item-toggle="accordion" data-menu-item-trigger="click">
                        <div class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                                <i class="ki-filled ki-profile-circle text-lg"> </i>
                            </span>
                            <span class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
                                Setup & Configuration
                            </span>
                            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                                <i class="ki-filled ki-plus text-2xs menu-item-show:hidden"> </i>
                                <i class="ki-filled ki-minus text-2xs hidden menu-item-show:inline-flex"> </i>
                            </span>
                        </div>
                        <div class="menu-accordion gap-0.5 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-gray-200">

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        App Settings
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        FAQ's
                                    </span>
                                </a>
                            </div>

                            <div class="menu-item">
                                <a
                                    class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="html/demo1/public-profile/works.html"
                                    tabindex="0"
                                >
                                    <span
                                        class="menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary"
                                    >
                                    </span>
                                    <span class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
                                        Pages
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!-- End of Sidebar Menu -->
            </div>
        </div>
    </div>
    <!-- End of Sidebar -->