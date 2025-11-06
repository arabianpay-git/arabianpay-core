@extends('layouts.pass')

@section('content')
    <div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">

        <h2 class="text-xl font-bold mb-4">Register a Passkey</h2>

        <div id="messages" class="mb-4"></div>

        <p class="mb-4">This will register a passkey (biometric/faceID/security key) to your account.</p>

        <button id="register-passkey" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Register Passkey
        </button>
    </div>
@endsection
@push('scripts')
    <script>
        function arrayBufferToBase64url(buffer) {
            const bytes = new Uint8Array(buffer);
            const binary = String.fromCharCode(...bytes);
            return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        }

        function base64urlToUint8Array(base64url) {
            let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            while (base64.length % 4) base64 += '=';
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const messages = document.getElementById('messages');
            const button = document.getElementById('register-passkey');

            let options = @json($publicKey);

            if (typeof options === 'string') {
                try {
                    options = JSON.parse(options);
                } catch {
                    return console.error('Invalid JSON for publicKey options');
                }
            }

            function showMessage(text, type = 'info') {
                const colors = {
                    info: 'bg-blue-100 text-blue-700',
                    success: 'bg-green-100 text-green-700',
                    error: 'bg-red-100 text-red-700',
                };
                messages.innerHTML = `<div class="p-3 rounded ${colors[type]}">${text}</div>`;
            }

            button.addEventListener('click', async () => {
                showMessage('Starting registration…', 'info');

                if (!window.PublicKeyCredential) {
                    return showMessage('WebAuthn is not supported in this browser.', 'error');
                }

                try {
                    if (!options.challenge || !options.user || !options.user.id) {
                        throw new Error('Missing required fields (challenge or user.id)');
                    }

                    const publicKey = {
                        ...options,
                        challenge: base64urlToUint8Array(options.challenge),
                        user: {
                            ...options.user,
                            id: base64urlToUint8Array(options.user.id),
                        },
                        // Optional, force platform (biometrics, device bound)
                        authenticatorSelection: {
                            authenticatorAttachment: "platform",
                            userVerification: "required",
                            residentKey: "required"
                        },
                        timeout: 60000
                    };

                    const credential = await navigator.credentials.create({
                        publicKey
                    });

                    const transformed = {
                        id: credential.id,
                        rawId: arrayBufferToBase64url(credential.rawId),
                        type: credential.type,
                        response: {
                            attestationObject: arrayBufferToBase64url(credential.response
                                .attestationObject),
                            clientDataJSON: arrayBufferToBase64url(credential.response
                                .clientDataJSON),
                        },
                        clientExtensionResults: credential.getClientExtensionResults(),
                    };

                    showMessage('Sending credential to server...', 'info');

                    const res = await fetch('{{ route('passkeys.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            passkeyJson: JSON.stringify(transformed),
                            passkeyOptionsJson: JSON.stringify(options),
                        }),
                    });

                    if (res.ok) {
                        showMessage('Passkey registered successfully! Redirecting…', 'success');
                        setTimeout(() => window.location.href = "{{ route('dashboard') }}", 1000);
                    } else {
                        const txt = await res.text();
                        showMessage(`Registration failed: ${txt}`, 'error');
                        console.error('Server response:', txt);
                    }
                } catch (err) {
                    console.error('Client error:', err);
                    showMessage(`Error: ${err.message}`, 'error');
                }
            });
        });
    </script>
@endpush
