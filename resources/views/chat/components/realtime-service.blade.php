{{-- This component doesn't render HTML, just pushes JavaScript --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const RealtimeService = {
                init() {
                    this.echo = null;
                    this.setupEcho();
                    window.ChatApp.logger.log('RealtimeService initializing...');
                },

                async setupEcho() {
                    try {
                        // Load Pusher
                        await this.loadScript('https://js.pusher.com/7.0/pusher.min.js');
                        // Load Echo
                        await this.loadScript(
                            'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js');

                        // Initialize Echo
                        this.echo = new Echo({
                            broadcaster: 'pusher',
                            key: '{{ env('REVERB_APP_KEY', 'reverb_key') }}',

                            wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
                            wsPort: '{{ env('REVERB_PORT', '8080') }}',
                            wssPort: '{{ env('REVERB_PORT', '8080') }}',
                            forceTLS: '{{ env('REVERB_TLS', 'false') }}',
                            enabledTransports: ['ws', 'wss'],
                            authEndpoint: '/broadcasting/auth',
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            }
                        });

                        this.setupConnectionListeners();
                        this.setupChannels();

                        // Emit event when Echo is ready
                        setTimeout(() => {
                            if (window.ChatApp?.events?.emit) {
                                window.ChatApp.events.emit('echo:ready');
                            }
                            window.ChatApp.logger.log('Echo is ready for use');
                        }, 100);

                    } catch (error) {
                        window.ChatApp.logger.error('Failed to setup Echo', error);
                    }
                },

                loadScript(src) {
                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = src;
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                },

                setupConnectionListeners() {
                    const pusher = this.echo.connector.pusher;

                    pusher.connection.bind('connected', () => {
                        const socketId = pusher.connection.socket_id;
                        window.ChatApp.logger.log('Connected to Reverb', {
                            socketId
                        });
                        window.ChatApp.events.emit('connection:connected', socketId);
                    });

                    pusher.connection.bind('error', (error) => {
                        window.ChatApp.logger.error('Pusher connection error', error);
                        window.ChatApp.events.emit('connection:error', error);
                    });

                    pusher.connection.bind('disconnected', () => {
                        window.ChatApp.logger.log('Disconnected from Reverb');
                        window.ChatApp.events.emit('connection:disconnected');
                    });

                    pusher.connection.bind('unavailable', () => {
                        window.ChatApp.logger.error('Realtime service unavailable');
                        window.ChatApp.events.emit('connection:unavailable');
                    });

                    pusher.connection.bind('connecting', () => {
                        window.ChatApp.logger.log('Attempting to reconnect...');
                        window.ChatApp.events.emit('connection:reconnecting');
                    });
                },

                setupChannels() {
                    const authUserId = window.ChatApp.config.authUserId;

                    // Presence channel
                    this.echo.join('presence.chat')
                        .here((users) => {
                            window.ChatApp.logger.log('Presence here - initial users', users);
                            window.ChatApp.events.emit('presence:here', users);
                        })
                        .joining((user) => {
                            window.ChatApp.logger.log('User joining presence', user);
                            window.ChatApp.events.emit('presence:joining', user);
                        })
                        .leaving((user) => {
                            window.ChatApp.logger.log('User leaving presence', user);
                            window.ChatApp.events.emit('presence:leaving', user);
                        })
                        .error((error) => {
                            window.ChatApp.logger.error('Presence channel error', error);
                        });

                    // Private channel for messages and typing
                    const privateChannel = this.echo.private(`chat.${authUserId}`);

                    privateChannel
                        .listen('MessageSent', (event) => {
                            window.ChatApp.logger.log('MessageSent event', event);
                            window.ChatApp.events.emit('message:received', event.message);
                        })
                        .listen('TypingEvent', (event) => {
                            window.ChatApp.logger.log('TypingEvent received', event);
                            // Emit both to event bus and also emit a specific event
                            window.ChatApp.events.emit('typing:start', event.senderId);
                            window.ChatApp.events.emit('echo:typing:start', event);
                        })
                        .listen('TypingStopped', (event) => {
                            window.ChatApp.logger.log('TypingStopped received', event);
                            window.ChatApp.events.emit('typing:stop', event.senderId);
                            window.ChatApp.events.emit('echo:typing:stop', event);
                        })
                        .listen('MessagesRead', (event) => {
                            window.ChatApp.logger.log('MessagesRead event', event);
                            window.ChatApp.events.emit('messages:read', event);
                        })
                        .error((error) => {
                            window.ChatApp.logger.error('Private channel error', error);
                        });

                    // Log when subscribed
                    privateChannel.subscribed(() => {
                        window.ChatApp.logger.log('Private channel subscribed successfully');
                    });
                },

                // Public methods to access Echo
                getEcho() {
                    return this.echo;
                },

                getPusher() {
                    return this.echo?.connector?.pusher;
                }
            };

            // Store service globally
            window.ChatApp.RealtimeService = RealtimeService;

            // Initialize immediately to load Echo early
            RealtimeService.init();
        });
    </script>
@endpush
