<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Laravel Passkey App')</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <header class="bg-white shadow p-4">
        <div class="container mx-auto">
            <h1 class="text-xl font-semibold">Laravel Passkey App</h1>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="bg-white shadow p-4 mt-auto text-center text-sm text-gray-600">
        &copy; {{ date('Y') }} Your Company
    </footer>

    @stack('scripts')
</body>

</html>
