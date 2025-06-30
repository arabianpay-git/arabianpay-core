<!-- Header -->
<header
    class="header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-[--tw-page-bg] dark:bg-[--tw-page-bg-dark]"
    data-sticky="true" data-sticky-class="shadow-sm" data-sticky-name="header" id="header">
    <!-- Container -->
    <div class="container-fixed flex justify-between items-stretch lg:gap-4" id="header_container">
        <!-- Mobile Logo -->
        <div class="flex gap-1 lg:hidden items-center -ms-1">
            <a class="shrink-0" href="{{ route('dashboard') }}">
                <img class="max-h-[25px] w-full" src="{{ asset('assets/media/app/default-logo.svg') }}" />
            </a>
            <div class="flex items-center">
                <button class="btn btn-icon btn-light btn-clear btn-sm" data-drawer-toggle="#sidebar">
                    <i class="ki-filled ki-menu"> </i>
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        <!--Megamenu Contaoner-->
        <div class="flex items-stretch" id="mega_menu_container">
            <!--Megamenu Inner-->
            <div class="flex items-stretch" data-reparent="true" data-reparent-mode="prepend|lg:prepend"
                data-reparent-target="body|lg:#mega_menu_container">
                <!--Megamenu Wrapper-->
                <div class="hidden lg:flex lg:items-stretch" data-drawer="true"
                    data-drawer-class="drawer drawer-start fixed z-10 top-0 bottom-0 w-full me-5 max-w-[250px] p-5 lg:p-0 overflow-auto"
                    data-drawer-enable="true|lg:false" id="mega_menu_wrapper">

                </div>
                <!--End of Megamenu Wrapper-->
            </div>
            <!--End of Megamenu Inner-->
        </div>
        <!--End of Megamenu Contaoner-->
        <!-- Topbar -->
        <div class="flex items-center gap-2 lg:gap-3.5">
            {{-- @include('layouts.includes.notifications') --}}
            <div class="menu" data-menu="true">
                <div class="menu-item" data-menu-item-offset="20px, 10px" data-menu-item-offset-rtl="-20px, 10px"
                    data-menu-item-placement="bottom-end" data-menu-item-placement-rtl="bottom-start"
                    data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                    <div class="menu-toggle btn btn-icon rounded-full">
                        <img class="size-9 rounded-full border-2 border-success shrink-0"
                            src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}"
                            style="object-fit: contain;" />
                    </div>
                    <div class="menu-dropdown menu-default light:border-gray-300 w-screen max-w-[250px]">
                        <div class="flex items-center justify-between px-5 py-1.5 gap-1.5">
                            <div class="flex items-center gap-2">
                                <img class="size-9 rounded-full border-2 border-success"
                                    src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}"
                                    style="object-fit: contain;" />
                                <div class="flex flex-col gap-1.5">
                                    <span class="text-sm text-gray-800 font-semibold leading-none">
                                        {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                                    </span>
                                    <a class="text-xs text-gray-600 hover:text-primary font-medium leading-none"
                                        href="html/demo1/account/home/get-started.html">
                                        {{ Auth::user()->email }}
                                    </a>
                                </div>
                            </div>
                            <span class="badge badge-xs badge-primary badge-outline">
                                {{ ucfirst(Auth::user()->user_type) }}
                            </span>
                        </div>
                        <div class="menu-separator"></div>
                        <div class="flex flex-col">
                            <div class="menu-item">
                                <a class="menu-link" href="{{ route('profile.show') }}">
                                    <span class="menu-icon">
                                        <i class="ki-filled ki-badge"> </i>
                                    </span>
                                    <span class="menu-title">
                                        My Profile
                                    </span>
                                </a>
                            </div>

                            @include('layouts.includes.languages')

                        </div>
                        <div class="menu-separator"></div>
                        <div class="flex flex-col">
                            <div class="menu-item mb-0.5">
                                <div class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-filled ki-moon"> </i>
                                    </span>
                                    <span class="menu-title">
                                        Dark Mode
                                    </span>
                                    <label class="switch switch-sm">
                                        <input data-theme-state="dark" data-theme-toggle="true" name="check"
                                            type="checkbox" value="1" />
                                    </label>
                                </div>
                            </div>
                            <div class="menu-item px-4 py-1.5">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light justify-center w-full">
                                        Log out
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
<!-- End of Header -->
