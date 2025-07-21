@if (!($userHasPasskey ?? false))
    <div class="modal" data-modal="true" id="passkeyRegisterModal" role="dialog" aria-modal="true"
        aria-labelledby="passkeyModalTitle">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5 flex items-center justify-between gap-3">
                <h5 class="modal-title text-lg font-semibold" id="passkeyModalTitle">Register Passkey</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" id="passkeyModalClose"
                    data-modal-dismiss="true" aria-label="Close modal">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>

            <div class="modal-body px-5 pb-5">
                <p>You haven't registered a passkey. Would you like to register one now for easier login?</p>
                <div id="passkeyMessage" class="mt-3 hidden p-3 rounded-md border text-sm" role="alert"
                    aria-live="polite"></div>
            </div>

            <div class="modal-footer px-5 pb-4 flex justify-end gap-3">
                <button type="button" class="btn btn-secondary" id="passkeyModalClose2" data-modal-dismiss="true">No,
                    thanks</button>
                <button type="button" class="btn btn-primary" id="passkeyYesBtn">Yes, register</button>
            </div>
        </div>
    </div>
@endif

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
            const declinedKey = 'passkeyRegisterDeclined';
            const modal = document.getElementById('passkeyRegisterModal');
            const messageEl = document.getElementById('passkeyMessage');
            const button = document.getElementById('register-passkey');
            const messages = document.getElementById('messages');
            const userHasPasskey = @json($userHasPasskey ?? false);

            function showModal(modal) {
                modal?.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function hideModal(modal) {
                modal?.classList.remove('open');
                document.body.style.overflow = '';
            }

            function setModalMessage(text, isError = true) {
                messageEl.classList.remove('hidden');
                messageEl.className =
                    `mt-3 p-3 rounded-md border text-sm ${isError
                    ? 'bg-red-100 text-red-700 border-red-300'
                    : 'bg-green-100 text-green-700 border-green-300'
                }`;
                messageEl.textContent = text;
            }

            function showMessage(text, type = 'info') {
                const colors = {
                    info: 'bg-blue-100 text-blue-700',
                    success: 'bg-green-100 text-green-700',
                    error: 'bg-red-100 text-red-700',
                };
                messages.innerHTML = `<div class="p-3 rounded ${colors[type]}">${text}</div>`;
            }

            async function fetchPublicKeyOptions() {
                const res = await fetch('{{ route('passkeys.registrationOptions') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });

                if (!res.ok) throw new Error('Failed to fetch registration options');
                return await res.json();
            }

            async function startPasskeyRegistration() {
                setModalMessage('Fetching registration options...', false);
                try {
                    const options = await fetchPublicKeyOptions();

                    const publicKey = {
                        ...options,
                        challenge: base64urlToUint8Array(options.challenge),
                        user: {
                            ...options.user,
                            id: base64urlToUint8Array(options.user.id),
                        },
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
                            clientDataJSON: arrayBufferToBase64url(credential.response.clientDataJSON),
                        },
                        clientExtensionResults: credential.getClientExtensionResults(),
                    };

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
                        setModalMessage('Passkey registered successfully! Reloading...', false);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        const txt = await res.text();
                        setModalMessage(`Registration failed: ${txt}`);
                    }
                } catch (err) {
                    console.error(err);
                    setModalMessage(err.message || 'Unexpected error occurred.');
                }
            }

            // Modal open condition
            if (!userHasPasskey && !localStorage.getItem(declinedKey) && modal) {
                showModal(modal);
            }

            // Modal close buttons
            document.getElementById('passkeyModalClose')?.addEventListener('click', () => {
                localStorage.setItem(declinedKey, 'true');
                hideModal(modal);
            });

            document.getElementById('passkeyModalClose2')?.addEventListener('click', () => {
                localStorage.setItem(declinedKey, 'true');
                hideModal(modal);
            });

            // Trigger from modal "Yes" button
            document.getElementById('passkeyYesBtn')?.addEventListener('click', () => {
                startPasskeyRegistration();
            });

            // Fallback manual button
            button?.classList.remove('hidden');
            button?.addEventListener('click', () => {
                startPasskeyRegistration();
            });
        });
    </script>
@endpush
