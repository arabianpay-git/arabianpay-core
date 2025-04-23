<!DOCTYPE html>
<html class="h-full" data-theme="true" data-theme-mode="light" dir="ltr" lang="en">
    <head>
        <title>
            ArabianPay
        </title>
        @include('layouts.includes.meta')
        @include('layouts.includes.styles')
    </head>
    <body class="antialiased flex h-full text-base text-gray-700 [--tw-page-bg:#fefefe] [--tw-page-bg-dark:var(--tw-coal-500)] demo1 sidebar-fixed header-fixed bg-[--tw-page-bg] dark:bg-[--tw-page-bg-dark]">
        <!-- Theme Mode -->
        <script>
            const defaultThemeMode = "light"; // light|dark|system
            let themeMode;

            if (document.documentElement) {
                if (localStorage.getItem("theme")) {
                    themeMode = localStorage.getItem("theme");
                } else if (document.documentElement.hasAttribute("data-theme-mode")) {
                    themeMode = document.documentElement.getAttribute("data-theme-mode");
                } else {
                    themeMode = defaultThemeMode;
                }

                if (themeMode === "system") {
                    themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
                }

                document.documentElement.classList.add(themeMode);
            }
        </script>
        <!-- End of Theme Mode -->
        <!-- Page -->
        <div class="flex grow">
            @include('layouts.sidebar')
            <!-- Wrapper -->
            <div class="wrapper flex grow flex-col">
                @include('layouts.header') 
            
                @yield('content') 
                
                @include('layouts.footer')
            </div>
        </div>
        <!-- End of Wrapper -->
       
        <!-- Scripts -->
        @include('layouts.includes.scripts')
    </body>
</html>
