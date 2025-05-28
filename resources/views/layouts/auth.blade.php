<!DOCTYPE html>
<html class="h-full" data-theme="true" data-theme-mode="light" dir="ltr" lang="en">

<head>
    <title>
        ArabianPay Sign In
    </title>
    <meta charset="utf-8" />
    <meta content="follow, index" name="robots" />
    <link href="https://127.0.0.1:8001/metronic-tailwind-html/demo1/authentication/classic/sign-in" rel="canonical" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <meta content="ArabianPay Sign In" name="description" />
    <meta content="@keenthemes" name="twitter:site" />
    <meta content="@keenthemes" name="twitter:creator" />
    <meta content="summary_large_image" name="twitter:card" />
    <meta content="ArabianPay Sign In" name="twitter:title" />
    <meta content="ArabianPay Sign In" name="twitter:description" />
    <meta content="assets/media/app/og-image.png') }}" name="twitter:image" />
    <meta content="https://127.0.0.1:8001/metronic-tailwind-html/demo1/authentication/classic/sign-in"
        property="og:url" />
    <meta content="en_US" property="og:locale" />
    <meta content="website" property="og:type" />
    <meta content="@keenthemes" property="og:site_name" />
    <meta content="ArabianPay Sign In" property="og:title" />
    <meta content="ArabianPay Sign In" property="og:description" />
    <link href="{{ asset('assets/media/images/favicon.png') }}" rel="apple-touch-icon" sizes="180x180" />
    <link href="{{ asset('assets/media/images/favicon.png') }}" rel="icon" sizes="32x32" type="image/png" />
    <link href="{{ asset('assets/media/images/favicon.png') }}" rel="icon" sizes="16x16" type="image/png" />
    <link href="{{ asset('assets/media/images/favicon.png') }}" rel="shortcut icon" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
</head>

<body class="antialiased flex h-full text-base text-gray-700 dark:bg-coal-500">
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
    <style>
        .page-bg {
            background-image: url("{{ asset('assets/media/images/2600x1200/bg-10.png') }}");
        }

        .dark .page-bg {
            background-image: url("{{ asset('assets/media/images/2600x1200/bg-10-dark.png') }}");
        }
    </style>

    @yield('content')
    <!-- End of Page -->
    <!-- Scripts -->
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <!-- End of Scripts -->
</body>

</html>
