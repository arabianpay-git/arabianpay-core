<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Send Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ Tailwind CSS via CDN (v4) -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="w-full max-w-lg bg-white shadow-lg rounded-2xl p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Send Email</h1>

        @if (session('status'))
            <div class="mb-4 rounded-lg p-3 bg-green-100 text-green-800 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg p-3 bg-red-100 text-red-800 text-sm">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('email.send') }}" class="space-y-5">
            @csrf

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">To Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required>
            </div>

            <!-- Subject Field -->
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required>
            </div>

            <!-- Message Field -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea id="message" name="message" rows="6"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required>{{ old('message') }}</textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('email.create') }}"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Reset
                </a>
                <button type="submit"
                    class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                    Send
                </button>
            </div>
        </form>
    </div>
</body>

</html>
