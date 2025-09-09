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
                    class="w-full p-2 border rounded" placeholder="https://api.example.com/resource">
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
                    placeholder='{"Authorization": "Bearer token"}'>{!! old(
                        'headers',
                        json_encode(
                            [
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                            ],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                    ) !!}</textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-1">Body (JSON format):</label>
                <textarea id="body" name="body" rows="6" class="w-full p-2 border rounded" placeholder='{"key": "value"}'>{!! old('body', json_encode(new stdClass(), JSON_PRETTY_PRINT)) !!}</textarea>
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
    <div class="w-1/2 p-6 bg-gray-50 flex flex-col overflow-hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold">Response</h2>

            @if (session('status'))
                @php
                    $status = strtolower(session('status')); // normalize
                    $badgeColor = match ($status) {
                        'success' => 'border-green-500 text-green-500',
                        'error', 'fail' => 'border-red-500 text-red-500',
                        'warning' => 'border-yellow-400 text-yellow-400',
                        default => 'border-blue-500 text-blue-500',
                    };
                @endphp
                <span class="inline-block px-3 py-1 text-sm font-semibold rounded-[3px] border {{ $badgeColor }}">
                    {{ ucfirst($status) }}
                </span>
            @endif
        </div>

        <!-- Response Panel -->
        <pre id="responseArea" class="bg-gray-900 text-white p-4 rounded shadow overflow-auto h-[80vh] font-mono text-sm">
@if (session('response'))
@php
    $decoded = json_decode(session('response'), true);
    $pretty = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : session('response');
@endphp
{!! e($pretty) !!}
@else
<span class="text-gray-500">Response will appear here...</span>
@endif
    </pre>
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

        // --------- Form persistence (fields only) ----------
        function loadFormData() {
            const data = JSON.parse(localStorage.getItem('apiTesterData') || '{}');
            endpointField.value = data.endpoint || '';
            methodField.value = data.method || 'GET';
            headersField.value = data.headers || JSON.stringify({
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }, null, 4);
            bodyField.value = data.body || '{}';
        }
        loadFormData();

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
        form.addEventListener('submit', () => {
            btn.disabled = true;
            btnText.innerText = "Sending...";
            spinner.classList.remove('hidden');
        });

        // --------- Syntax highlighting ----------
        function syntaxHighlight(json) {
            if (!json) return '';
            return json.replace(
                /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|\b\d+(\.\d+)?\b)/g,
                match => {
                    let cls = 'text-yellow-300'; // default numbers
                    if (/^"/.test(match)) cls = /:$/.test(match) ? 'text-blue-400' : 'text-green-400';
                    else if (/true|false/.test(match)) cls = 'text-purple-400';
                    else if (/null/.test(match)) cls = 'text-red-400';
                    return `<span class="${cls}">${match}</span>`;
                }
            );
        }

        // --------- Apply syntax highlighting on load ----------
        if (responsePre && responsePre.innerText.trim() !== "Response will appear here...") {
            responsePre.innerHTML = syntaxHighlight(responsePre.innerText);
        }
    </script>



</body>

</html>
