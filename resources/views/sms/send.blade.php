<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Send SMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white shadow-lg rounded-2xl p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Send SMS</h1>

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

        <form method="POST" action="{{ route('sms.send') }}" class="space-y-5" id="smsForm">
            @csrf

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required>
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea id="message" name="message" rows="5"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required>{{ old('message') }}</textarea>
            </div>

            <div>
                <label for="count" class="block text-sm font-medium text-gray-700 mb-1">Number of SMS</label>
                <input id="count" name="count" type="number" value="{{ old('count', 1) }}" min="1"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" id="sendBtn"
                    class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                    Send
                </button>
            </div>
        </form>
    </div>

    <script>
        const form = document.getElementById('smsForm');
        const btn = document.getElementById('sendBtn');

        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerText = 'Sending...';
        });
    </script>
</body>

</html>
