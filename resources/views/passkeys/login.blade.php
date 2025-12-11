@extends('layouts.pass')

@section('content')
    <div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Login with Passkey</h2>

        <form id="login-form">
            <label for="email" class="block mb-2">Email</label>
            <input type="email" id="email" name="email" required class="border p-2 rounded w-full mb-4">

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Start Passkey
                Login</button>
        </form>

        <div id="messages" class="mt-4"></div>
        <button id="authenticate-passkey" class="hidden px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 mt-4">
            Login with Passkey
        </button>
    </div>
@endsection
@push('scripts')
    <script>
        function base64urlToUint8Array(base64url) {
            let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            while (base64.length % 4) base64 += '=';
            const binary = atob(base64);
            return Uint8Array.from([...binary].map(c => c.charCodeAt(0)));
        }

        function arrayBufferToBase64url(buffer) {
            const bytes = new Uint8Array(buffer);
            const binary = String.fromCharCode(...bytes);
            const base64 = btoa(binary);
            return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('login-form');
            const messages = document.getElementById('messages');
            const authBtn = document.getElementById('authenticate-passkey');
            let options = null;

            function showMessage(text, type = 'info') {
                const colors = {
                    info: 'bg-blue-100 text-blue-700',
                    success: 'bg-green-100 text-green-700',
                    error: 'bg-red-100 text-red-700',
                };
                messages.innerHTML = `<div class="p-3 rounded ${colors[type]}">${text}</div>`;
            }

            form.addEventListener('submit', async e => {
                e.preventDefault();
                showMessage('Requesting passkey challenge…', 'info');

                const email = form.email.value.trim();
                if (!email) return showMessage('Please enter your email.', 'error');

                try {
                    const res = await fetch("{{ route('passkeys.getPublicKey') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            email
                        }),
                    });

                    if (!res.ok) {
                        const err = await res.text();
                        return showMessage(`Error: ${err}`, 'error');
                    }

                    options = await res.json();

                    if (!Array.isArray(options.allowCredentials) || !options.allowCredentials.length) {
                        console.warn('No passkeys found for this user.');
                        return showMessage('No passkey found for this email on this device.', 'error');
                    }

                    const platformAvailable = await PublicKeyCredential
                        .isUserVerifyingPlatformAuthenticatorAvailable();

                    if (!platformAvailable) {
                        console.warn('Platform authenticator is not available.');
                        return showMessage(
                            'No supported passkey authenticator is available on this device.',
                            'error');
                    }

                    console.log('Passkey found and platform authenticator available.');
                    showMessage('Passkey found for this device. Click below to authenticate.',
                        'success');
                    authBtn.classList.remove('hidden');
                } catch (err) {
                    console.error('Error during challenge fetch:', err);
                    showMessage('Network or server error.', 'error');
                }
            });

            authBtn.addEventListener('click', async () => {
                if (!options) {
                    return showMessage('Submit your email first.', 'error');
                }
                showMessage('Starting authentication…', 'info');

                try {
                    const publicKey = {
                        challenge: base64urlToUint8Array(options.challenge),
                        timeout: options.timeout,
                        rpId: options.rpId,
                        userVerification: options.userVerification,
                        allowCredentials: options.allowCredentials.map(c => ({
                            type: c.type,
                            id: base64urlToUint8Array(c.id),
                        })),
                    };

                    const assertion = await navigator.credentials.get({
                        publicKey
                    });

                    const credential = {
                        id: assertion.id,
                        rawId: arrayBufferToBase64url(assertion.rawId),
                        type: assertion.type,
                        response: {
                            authenticatorData: arrayBufferToBase64url(assertion.response
                                .authenticatorData),
                            clientDataJSON: arrayBufferToBase64url(assertion.response
                                .clientDataJSON),
                            signature: arrayBufferToBase64url(assertion.response.signature),
                            ...(assertion.response.userHandle && {
                                userHandle: arrayBufferToBase64url(assertion.response
                                    .userHandle)
                            }),
                        },
                    };

                    showMessage('Sending authentication to server…', 'info');

                    const res = await fetch('{{ route('passkeys.authenticate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(credential),
                    });

                    if (res.ok) {
                        // server returns JSON with redirect URL
                        const data = await res.json().catch(() => null);
                        showMessage('Logged in! Redirecting…', 'success');
                        const redirectUrl = data?.redirect ?? "{{ route('dashboard') }}";
                        setTimeout(() => window.location.href = redirectUrl, 900);
                    } else {
                        const err = await res.text();
                        showMessage(`Authentication failed: ${err}`, 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showMessage('Authentication cancelled or failed.', 'error');
                }
            });
        });
    </script>
@endpush
