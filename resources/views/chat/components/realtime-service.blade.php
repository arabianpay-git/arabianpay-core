@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const RealtimeService = {
                echo: null,

                async init() {
                    window.ChatApp.logger.log('RealtimeService initializing...');
                    await this.loadEcho();
                    this.setupEcho();
                    this.setupChannels();
                },

                async loadEcho() {
                    // CHANGED: Removed Pusher script completely
                    await this.loadScript(
                        'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js'); // CHANGED
                },

                setupEcho() {
                    // CHANGED: Removed pusher, removed key, changed broadcaster
                    this.echo = new Echo({
                        broadcaster: 'reverb', // CHANGED
                        wsHost: '{{ env('REVERB_HOST', 'core.arabianpay.net') }}', // CHANGED
                        wsPort: 8080,
                        wssPort: 8080,
                        forceTLS: true, // CHANGED (because you use HTTPS)
                        enabledTransports: ['ws', 'wss'],

                        // Auth for private/presence channels
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }
                    });

                    window.ChatApp.logger.log("Echo (Reverb) initialized");
                    window.ChatApp.events.emit('echo:ready');
                },

                loadScript(src) {
                    return new Promise((resolve, reject) => {
                        let s = document.createElement('script');
                        s.src = src;
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                },

                // CHANGED: All pusher.connection.bind removed
                setupChannels() {
                    const authUserId = window.ChatApp.config.authUserId;

                    // Presence channel
                    this.echo.join('presence.chat')
                        .here((users) => {
                            window.ChatApp.logger.log('Presence here', users);
                            window.ChatApp.events.emit('presence:here', users);
                        })
                        .joining((user) => {
                            window.ChatApp.logger.log('User joined', user);
                            window.ChatApp.events.emit('presence:joining', user);
                        })
                        .leaving((user) => {
                            window.ChatApp.logger.log('User left', user);
                            window.ChatApp.events.emit('presence:leaving', user);
                        })
                        .error((err) => {
                            window.ChatApp.logger.error('Presence error', err);
                        });

                    // Private channel
                    const channel = this.echo.private(`chat.${authUserId}`);

                    channel.listen('MessageSent', (e) => {
                        window.ChatApp.logger.log('MessageSent', e);
                        window.ChatApp.events.emit('message:received', e.message);
                    });

                    channel.listen('TypingEvent', (e) => {
                        window.ChatApp.events.emit('typing:start', e.senderId);
                    });

                    channel.listen('TypingStopped', (e) => {
                        window.ChatApp.events.emit('typing:stop', e.senderId);
                    });

                    channel.listen('MessagesRead', (e) => {
                        window.ChatApp.events.emit('messages:read', e);
                    });

                    channel.subscribed(() => {
                        window.ChatApp.logger.log('Private channel subscribed');
                    });
                },

                getEcho() {
                    return this.echo;
                }
            };

            window.ChatApp.RealtimeService = RealtimeService;
            RealtimeService.init();
        });
    </script>
@endpush
