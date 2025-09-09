<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tester</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-gray-100 h-screen flex items-stretch">

    <!-- Left: Form -->
    <div class="w-1/2 p-6 bg-white shadow overflow-auto">
        <h1 class="text-2xl font-bold mb-6">API Tester</h1>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 mb-4 rounded">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form id="apiTesterForm" action="{{ route('api.tester.send') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block font-semibold mb-1">API Endpoint:</label>
                <input type="text" id="endpoint" name="endpoint" value="{{ old('endpoint') }}"
                    class="w-full p-1 border rounded" placeholder="https://api.example.com/resource">
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">HTTP Method:</label>
                <select id="method" name="method" class="w-full p-2 border rounded">
                    @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                        <option value="{{ $method }}" {{ old('method') == $method ? 'selected' : '' }}>
                            {{ $method }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Headers (JSON format):</label>
                <textarea id="headers" name="headers" rows="4" class="w-full p-2 border rounded"
                    placeholder='{"Authorization": "Bearer token"}'>
                    {!! old(
                        'headers',
                        json_encode(
                            [
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                            ],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                    ) !!}
            </textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Body (JSON format):</label>
                <textarea id="body" name="body" rows="6" class="w-full p-2 border rounded" placeholder='{"key": "value"}'>{{ old('body', '{}') }}</textarea>
            </div>

            <button id="sendBtn" type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center justify-center">
                <span id="btnText">Send Request</span>
                <svg id="btnSpinner" class="animate-spin h-5 w-5 ml-2 text-white hidden"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4l-3 3 3 3H4z"></path>
                </svg>
            </button>
        </form>

    </div>

    <!-- Right: Response -->
    <div class="w-1/2 p-6 bg-gray-50 overflow-auto">
        <h2 class="text-2xl font-bold mb-4">Response</h2>

        @if (session('response'))
            <pre class="bg-white p-4 rounded shadow overflow-auto h-[80vh]" id="responseArea">{{ session('response') }}</pre>
        @else
            <pre class="bg-white p-4 rounded shadow overflow-auto h-[80vh] text-gray-400">Response will appear here...</pre>
        @endif
    </div>
    <script>
        const form = document.getElementById('apiTesterForm');
        const btn = document.getElementById('sendBtn');
        const spinner = document.getElementById('btnSpinner');
        const btnText = document.getElementById('btnText');

        const endpointField = document.getElementById('endpoint');
        const methodField = document.getElementById('method');
        const headersField = document.getElementById('headers');
        const bodyField = document.getElementById('body');
        const responsePre = document.getElementById('responseArea');

        // --------- Form persistence ----------
        // Load saved values from localStorage
        if (localStorage.getItem('apiTesterData')) {
            const data = JSON.parse(localStorage.getItem('apiTesterData'));
            endpointField.value = data.endpoint || endpointField.value;
            methodField.value = data.method || methodField.value;
            headersField.value = data.headers || headersField.value;
            bodyField.value = data.body || bodyField.value;
        }

        // Save form values to localStorage on change
        [endpointField, methodField, headersField, bodyField].forEach(field => {
            field.addEventListener('input', () => {
                const data = {
                    endpoint: endpointField.value,
                    method: methodField.value,
                    headers: headersField.value,
                    body: bodyField.value
                };
                localStorage.setItem('apiTesterData', JSON.stringify(data));
            });
        });

        // --------- Button spinner ----------
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btnText.innerText = "Sending...";
            spinner.classList.remove('hidden');
        });

        // --------- Response formatting ----------
        function formatJSON(text) {
            try {
                const obj = JSON.parse(text);
                return JSON.stringify(obj, null, 4);
            } catch (e) {
                return text; // leave as-is if invalid JSON
            }
        }

        // Format the server response (session) on page load
        if (responsePre) {
            responsePre.innerText = formatJSON(responsePre.innerText);
            if (responsePre.innerText.trim() !== "Response will appear here...") {
                responsePre.classList.remove('text-gray-400');
                // Save last response to localStorage
                localStorage.setItem('apiTesterLastResponse', responsePre.innerText);
            }
        }

        // Load last response from localStorage if no session response
        if (!responsePre.innerText.trim() || responsePre.innerText.trim() === "Response will appear here...") {
            const lastResp = localStorage.getItem('apiTesterLastResponse');
            if (lastResp) {
                responsePre.innerText = lastResp;
                responsePre.classList.remove('text-gray-400');
            }
        }
    </script>

</body>

</html>
