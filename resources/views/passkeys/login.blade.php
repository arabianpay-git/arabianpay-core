<!-- resources/views/passkeys/login-form.blade.php -->
@extends('layouts.pass')

@section('content')
    <div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Login with Passkey</h2>

        <form id="login-form">
            <label for="email" class="block mb-2">Email</label>
            <input type="email" id="email" name="email" required class="border p-2 rounded w-full mb-4">

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Check for Passkey
            </button>
        </form>

        <div id="messages" class="mt-4"></div>
        <button id="authenticate-passkey" class="hidden px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 mt-4">
            Login with Passkey
        </button>

        <div id="error-message" class="hidden mt-4 p-3 bg-red-100 text-red-700 rounded"></div>

        <!-- Alternative login methods -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-600 mb-3">No passkey? Use another login method:</p>
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Use password instead →
            </a>
        </div>
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
            const errorDiv = document.getElementById('error-message');
            let options = null;

            function showMessage(text, type = 'info') {
                const colors = {
                    info: 'bg-blue-100 text-blue-700',
                    success: 'bg-green-100 text-green-700',
                    error: 'bg-red-100 text-red-700',
                };
                messages.innerHTML = `<div class="p-3 rounded ${colors[type]}">${text}</div>`;
            }

            function showError(message) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
                authBtn.classList.add('hidden');
            }

            function clearError() {
                errorDiv.classList.add('hidden');
                errorDiv.textContent = '';
            }

            function resetForm() {
                clearError();
                authBtn.classList.add('hidden');
                showMessage('', 'info');
                options = null;
            }

            form.addEventListener('submit', async e => {
                e.preventDefault();
                resetForm();
                showMessage('Checking account and passkeys…', 'info');

                const email = form.email.value.trim();
                if (!email) {
                    showError('Please enter your email.');
                    return;
                }

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

                    const data = await res.json();

                    // Check if user has passkeys
                    if (data.hasPasskeys === false) {
                        if (data.error === 'User not found') {
                            showError('No account found with this email address.');
                        } else {
                            showError(
                                'This email address does not have any passkeys registered. Please use password login or contact support to register a passkey.'
                                );
                        }
                        return;
                    }

                    // Check if we have valid credentials to proceed
                    if (!data.allowCredentials || !Array.isArray(data.allowCredentials) || data
                        .allowCredentials.length === 0) {
                        showError(
                            'No passkeys found for this email address. Please register a passkey first.'
                            );
                        return;
                    }

                    options = data;

                    // Check if platform authenticator is available
                    const platformAvailable = await PublicKeyCredential
                        .isUserVerifyingPlatformAuthenticatorAvailable();

                    if (!platformAvailable) {
                        showError(
                            'Your device/browser does not support passkey authentication. Please use password login instead.'
                            );
                        return;
                    }

                    // Show success message and authentication button
                    showMessage(
                        '✓ Passkey found for this email. Click the button below to authenticate.',
                        'success');
                    authBtn.classList.remove('hidden');

                } catch (err) {
                    console.error('Error during passkey check:', err);
                    showError('Unable to check for passkeys. Please try again or use password login.');
                }
            });

            authBtn.addEventListener('click', async () => {
                if (!options) {
                    showError('Please check your email first.');
                    return;
                }

                clearError();
                showMessage('Prompting for passkey authentication…', 'info');

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

                    // If we get here, user successfully authenticated with their device
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

                    showMessage('Verifying passkey with server…', 'info');

                    const res = await fetch('{{ route('passkeys.authenticate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(credential),
                    });

                    if (res.ok) {
                        showMessage('✓ Authentication successful! Redirecting…', 'success');
                        setTimeout(() => window.location.href = "{{ route('dashboard') }}", 1000);
                    } else {
                        const err = await res.text();
                        if (res.status === 401) {
                            showError('Authentication failed: Invalid passkey or user mismatch.');
                        } else {
                            showError(`Authentication failed: ${err}`);
                        }
                    }
                } catch (err) {
                    console.error('Authentication error:', err);
                    if (err.name === 'NotAllowedError') {
                        showError('Authentication was cancelled or timed out. Please try again.');
                    } else if (err.name === 'NotSupportedError') {
                        showError(
                            'Your browser does not support passkey authentication. Please try a different browser.'
                            );
                    } else {
                        showError('Authentication failed. Please try again or use password login.');
                    }
                }
            });

            // Reset when user changes email
            form.email.addEventListener('input', () => {
                resetForm();
            });
        });
    </script>
@endpush
