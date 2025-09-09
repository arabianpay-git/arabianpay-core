<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tester</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-gray-100">

    <div class="max-w-3xl mx-auto mt-10 p-6 bg-white rounded shadow">
        <h1 class="text-2xl font-bold mb-4">API Tester</h1>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 mb-4 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('api.tester.send') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block font-semibold mb-1">API Endpoint:</label>
                <input type="text" name="endpoint" value="{{ old('endpoint') }}" class="w-full p-2 border rounded"
                    placeholder="https://api.example.com/resource">
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">HTTP Method:</label>
                <select name="method" class="w-full p-2 border rounded">
                    @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                        <option value="{{ $method }}" {{ old('method') == $method ? 'selected' : '' }}>
                            {{ $method }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Headers (JSON format):</label>
                <textarea name="headers" rows="4" class="w-full p-2 border rounded"
                    placeholder='{"Authorization": "Bearer token"}'>{{ old('headers') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Body (JSON format):</label>
                <textarea name="body" rows="6" class="w-full p-2 border rounded" placeholder='{"key": "value"}'>{{ old('body') }}</textarea>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Send
                Request</button>
        </form>

        @if (session('response'))
            <div class="mt-6">
                <h2 class="text-xl font-bold mb-2">Response (Status: {{ session('status') }})</h2>
                <pre class="bg-gray-100 p-4 rounded overflow-auto" id="responseArea">{{ session('response') }}</pre>
            </div>
        @endif
    </div>

    <script>
        // Format JSON response if possible
        const pre = document.getElementById('responseArea');
        if (pre) {
            try {
                const json = JSON.parse(pre.innerText);
                pre.innerText = JSON.stringify(json, null, 4);
            } catch (e) {
                // leave as-is
            }
        }
    </script>

</body>

</html>
