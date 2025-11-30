@extends('layouts.auth')

@section('content')
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">
            <form action="{{ route('login') }}" class="card-body flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
                @csrf

                <div class="text-center mb-8">
                    <a href="{{ route('dashboard') }}" class="inline-flex justify-center">
                        <img src="{{ asset('assets/media/images/logo.png') }}" alt="arabianpay-logo"
                            class="h-20 object-contain" />
                    </a>

                    <h3
                        class="mt-6 text-2xl font-semibold text-gray-900 flex items-center justify-center gap-2 select-none">
                        {{ translate('Welcome Back') }} <span class="text-3xl leading-none">👋</span>
                    </h3>
                </div>

                <x-validation-errors class="mb-4" />
                <!--
                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Email
                    </label>
                    <input id="email" class="input" name="email" placeholder="email@email.com" type="email"
                        value="{{ old('email') }}" required />
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="form-label font-normal text-gray-900">
                            Password
                        </label>
                    </div>
                    <div class="input" data-toggle-password="true">
                        <input name="password" placeholder="Enter Password" type="password" required />
                        <button class="btn btn-icon" data-toggle-password-trigger="true" type="button">
                            <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"> </i>
                            <i class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"> </i>
                        </button>
                    </div>
                </div>

                <label class="checkbox-group">
                    <input class="checkbox checkbox-sm" id="remember_me" name="remember" type="checkbox" value="1" />
                    <span class="checkbox-label">
                        Remember me
                    </span>
                </label>
                -->
                <div class="flex flex-col gap-3 mt-4">
                    <!--
                    <button class="btn btn-primary w-full flex justify-center items-center" type="submit">
                        {{ __('Signin') }}
                    </button>
                    -->

                    <button type="button" id="passkey-btn"
                        class="btn btn-outline btn-secondary w-full flex items-center justify-center gap-2">
                        <i class="ki-duotone ki-key text-xl"></i>
                        {{ __('Login with Passkey') }}
                    </button>

                     <button type="button" id="microsoft-login"
                        class="btn btn-outline btn-light w-full flex items-center justify-center gap-2"
                        onclick="window.location.href='{{ route('auth.microsoft.redirect') }}'">
                        <img src="{{ asset('assets/media/microsoft-svgrepo-com.svg') }}"
                            alt="M"
                            class="w-5 h-5">
                        {{ __('Sign in with Microsoft') }}
                    </button>


                    <div id="passkey-message" class="text-sm mt-2 text-center hidden p-2 rounded"></div>
                </div>
            </form>
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
            const passkeyBtn = document.getElementById('passkey-btn');
            const emailInput = document.getElementById('email');
            const messageBox = document.getElementById('passkey-message');

            function showMessage(msg, type = 'info') {
                messageBox.classList.remove('hidden', 'text-danger', 'text-success');
                messageBox.classList.add(type === 'success' ? 'text-success' : 'text-danger');
                messageBox.innerText = msg;
            }

            passkeyBtn.addEventListener('click', async () => {
                const email = emailInput.value.trim();
                if (!email) return showMessage('Please enter your email address.', 'error');

                showMessage('Checking for registered passkey…');
                passkeyBtn.disabled = true;

                try {
                    const res = await fetch("{{ route('passkeys.getPublicKey') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            email
                        }),
                    });

                    if (!res.ok) {
                        const err = await res.text();
                        passkeyBtn.disabled = false;
                        return showMessage(err, 'error');
                    }

                    const options = await res.json();

                    const publicKey = {
                        challenge: base64urlToUint8Array(options.challenge),
                        timeout: options.timeout,
                        rpId: options.rpId,
                        userVerification: options.userVerification,
                        allowCredentials: options.allowCredentials.map(cred => ({
                            id: base64urlToUint8Array(cred.id),
                            type: cred.type,
                        })),
                    };

                    const credential = await navigator.credentials.get({
                        publicKey
                    });

                    const response = {
                        id: credential.id,
                        rawId: arrayBufferToBase64url(credential.rawId),
                        type: credential.type,
                        response: {
                            authenticatorData: arrayBufferToBase64url(credential.response
                                .authenticatorData),
                            clientDataJSON: arrayBufferToBase64url(credential.response
                                .clientDataJSON),
                            signature: arrayBufferToBase64url(credential.response.signature),
                            ...(credential.response.userHandle && {
                                userHandle: arrayBufferToBase64url(credential.response
                                    .userHandle),
                            }),
                        },
                    };

                    showMessage('Authenticating…');

                    const login = await fetch('{{ route('passkeys.authenticate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(response),
                    });

                    if (login.ok) {
                        showMessage('Login successful. Redirecting…', 'success');
                        setTimeout(() => window.location.href = "{{ route('dashboard') }}", 1000);
                    } else {
                        const err = await login.text();
                        showMessage(err, 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showMessage('Authentication failed or cancelled.', 'error');
                } finally {
                    passkeyBtn.disabled = false;
                }
            });
        });
    </script>
@endpush
