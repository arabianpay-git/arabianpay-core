{{-- This component doesn't render HTML, just pushes JavaScript --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const RealtimeService = {
                init() {
                    this.echo = null;
                    this.isConnecting = false;
                    this.connectionAttempt = 0;

                    window.ChatApp.logger.log('RealtimeService initializing...', {
                        currentProtocol: window.location.protocol,
                        currentHost: window.location.hostname,
                        isSecureContext: window.isSecureContext,
                        mustUseWSS: window.location.protocol === 'https:'
                    });

                    // IMPORTANT: If on HTTPS, we MUST use WSS
                    if (window.location.protocol === 'https:' && !this.canUseWSS()) {
                        window.ChatApp.logger.error('❌ Page is HTTPS but WSS is not available!');
                        this.showHttpsWarning();
                        return;
                    }

                    this.loadAndSetupEcho();
                },

                canUseWSS() {
                    // Check if WebSocket and WSS are supported
                    return 'WebSocket' in window && window.isSecureContext;
                },

                showHttpsWarning() {
                    if (window.ChatApp?.notification) {
                        window.ChatApp.notification.error(
                            'Cannot establish secure WebSocket connection. ' +
                            'Please ensure the server supports WSS (WebSocket Secure) on port 443.'
                        );
                    }
                },

                async loadAndSetupEcho() {
                    try {
                        window.ChatApp.logger.log('Loading Pusher and Echo scripts...');

                        // Load Pusher
                        if (typeof Pusher === 'undefined') {
                            await this.loadScript('https://js.pusher.com/7.6/pusher.min.js');
                            window.ChatApp.logger.log('✅ Pusher script loaded');
                        }

                        // Load Echo
                        if (typeof Echo === 'undefined') {
                            await this.loadScript(
                                'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js');
                            window.ChatApp.logger.log('✅ Echo script loaded');
                        }

                        await new Promise(resolve => setTimeout(resolve, 100));

                        if (typeof window.Echo === 'undefined') {
                            throw new Error('Echo is not defined after loading script');
                        }

                        window.ChatApp.logger.log('Echo available:', {
                            hasEcho: typeof window.Echo !== 'undefined',
                            isFunction: typeof window.Echo === 'function'
                        });

                        this.setupEchoConnection();

                    } catch (error) {
                        window.ChatApp.logger.error('Failed to load scripts', {
                            error: error.message
                        });
                    }
                },

                setupEchoConnection() {
                    try {
                        // Determine connection strategy based on protocol
                        const isHttps = window.location.protocol === 'https:';

                        const connectionStrategies = isHttps ? [{
                                name: 'secure-wss-443',
                                config: {
                                    broadcaster: 'pusher',
                                    key: '{{ env('REVERB_APP_KEY', 'reverb_key') }}',
                                    wsHost: 'core.arabianpay.net',
                                    wsPort: 443,
                                    wssPort: 443,
                                    forceTLS: true,
                                    enabledTransports: ['wss'],
                                    disableStats: true,
                                    cluster: 'mt1',
                                    authEndpoint: '/broadcasting/auth',
                                    auth: {
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    }
                                }
                            },
                            {
                                name: 'secure-wss-6001',
                                config: {
                                    broadcaster: 'pusher',
                                    key: '{{ env('REVERB_APP_KEY', 'reverb_key') }}',
                                    wsHost: 'core.arabianpay.net',
                                    wsPort: 6001,
                                    wssPort: 6001,
                                    forceTLS: true,
                                    enabledTransports: ['wss'],
                                    disableStats: true,
                                    cluster: 'mt1',
                                    authEndpoint: '/broadcasting/auth',
                                    auth: {
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    }
                                }
                            }
                        ] : [{
                            name: 'direct-ws',
                            config: {
                                broadcaster: 'pusher',
                                key: '{{ env('REVERB_APP_KEY', 'reverb_key') }}',
                                wsHost: 'core.arabianpay.net',
                                wsPort: 6001,
                                wssPort: 6001,
                                forceTLS: false,
                                enabledTransports: ['ws', 'wss'],
                                disableStats: true,
                                cluster: 'mt1',
                                authEndpoint: '/broadcasting/auth',
                                auth: {
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                }
                            }
                        }];

                        // Try strategies in order
                        for (let strategy of connectionStrategies) {
                            try {
                                window.ChatApp.logger.log('Trying connection strategy:', {
                                    strategy: strategy.name,
                                    config: strategy.config
                                });

                                this.echo = new window.Echo(strategy.config);
                                window.echoInstance = this.echo;
                                window.ChatApp.echo = this.echo;

                                // Verify Echo instance
                                if (!this.echo.private || !this.echo.join) {
                                    throw new Error('Echo instance missing required methods');
                                }

                                this.setupConnectionListeners();
                                this.setupChannels();

                                window.ChatApp.logger.log(`✅ Using ${strategy.name} configuration`);

                                // Emit event that Echo is ready
                                setTimeout(() => {
                                    window.ChatApp.events.emit('echo:ready', this.echo);
                                }, 100);

                                return; // Success, exit loop

                            } catch (strategyError) {
                                window.ChatApp.logger.warn(`Strategy ${strategy.name} failed:`, {
                                    error: strategyError.message
                                });
                                continue; // Try next strategy
                            }
                        }

                        throw new Error('All connection strategies failed');

                    } catch (error) {
                        window.ChatApp.logger.error('Failed to setup Echo connection', {
                            error: error.message,
                            errorName: error.name
                        });
                        this.retryConnection();
                    }
                },

                loadScript(src) {
                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = src;
                        script.onload = () => {
                            window.ChatApp.logger.log(`Script loaded: ${src}`);
                            resolve();
                        };
                        script.onerror = (error) => {
                            window.ChatApp.logger.error(`Failed to load script: ${src}`);
                            reject(new Error(`Failed to load script: ${src}`));
                        };
                        document.head.appendChild(script);
                    });
                },

                setupConnectionListeners() {
                    if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
                        window.ChatApp.logger.error('Cannot setup listeners: Echo or Pusher not available');
                        return;
                    }

                    const pusher = this.echo.connector.pusher;
                    this.connectionAttempt++;

                    window.ChatApp.logger.log('Setting up Pusher connection listeners', {
                        attempt: this.connectionAttempt,
                        pusherAvailable: !!pusher,
                        config: this.echo.connector.options
                    });

                    pusher.connection.bind('connecting', () => {
                        window.ChatApp.logger.log('🔄 Connecting to WebSocket server...');
                        this.isConnecting = true;
                    });

                    pusher.connection.bind('connected', () => {
                        const socketId = pusher.connection.socket_id;
                        window.ChatApp.logger.log('✅ Connected to Reverb server!', {
                            socketId: socketId,
                            transport: pusher.connection.transport ? pusher.connection.transport
                                .name : 'unknown',
                            url: pusher.connection.transport ? pusher.connection.transport.url :
                                'unknown'
                        });
                        window.ChatApp.events.emit('connection:connected', socketId);
                        this.isConnecting = false;
                        this.connectionAttempt = 0;
                    });

                    pusher.connection.bind('error', (error) => {
                        window.ChatApp.logger.error('❌ WebSocket connection error:', {
                            type: error.type,
                            message: error.message,
                            code: error.code,
                            state: pusher.connection.state,
                            transport: pusher.connection.transport ? pusher.connection.transport
                                .name : 'unknown'
                        });
                        window.ChatApp.events.emit('connection:error', error);
                        this.isConnecting = false;

                        setTimeout(() => this.retryConnection(), 2000);
                    });

                    pusher.connection.bind('unavailable', () => {
                        window.ChatApp.logger.error('🚫 Reverb server unavailable');
                        window.ChatApp.events.emit('connection:unavailable');
                        this.isConnecting = false;

                        setTimeout(() => this.retryConnection(), 3000);
                    });
                },

                setupChannels() {
                    const authUserId = window.ChatApp?.config?.authUserId;

                    if (!authUserId) {
                        window.ChatApp.logger.error('Cannot setup channels: authUserId not found');
                        return;
                    }

                    if (!this.echo || typeof this.echo.private !== 'function') {
                        window.ChatApp.logger.error('Cannot setup channels: Echo.private not available');
                        return;
                    }

                    window.ChatApp.logger.log('Setting up channels for user:', {
                        userId: authUserId
                    });

                    // Presence channel
                    try {
                        this.echo.join('presence.chat')
                            .here((users) => {
                                window.ChatApp.logger.log('👥 Users online:', {
                                    count: users.length
                                });
                                window.ChatApp.events.emit('presence:here', users);
                            })
                            .joining((user) => {
                                window.ChatApp.logger.log('➕ User joined:', user);
                                window.ChatApp.events.emit('presence:joining', user);
                            })
                            .leaving((user) => {
                                window.ChatApp.logger.log('➖ User left:', user);
                                window.ChatApp.events.emit('presence:leaving', user);
                            })
                            .error((error) => {
                                window.ChatApp.logger.error('Presence channel error:', error);
                            });

                        window.ChatApp.logger.log('✅ Presence channel setup complete');
                    } catch (error) {
                        window.ChatApp.logger.error('Failed to setup presence channel:', error);
                    }

                    // Private channel
                    try {
                        const privateChannel = this.echo.private(`chat.${authUserId}`);

                        privateChannel.subscribed(() => {
                            window.ChatApp.logger.log('✅ Private channel subscribed');
                        });

                        privateChannel
                            .listen('MessageSent', (event) => {
                                window.ChatApp.logger.log('📨 Message received');
                                window.ChatApp.events.emit('message:received', event.message);
                            })
                            .listen('TypingEvent', (event) => {
                                window.ChatApp.logger.log('⌨️ Typing started');
                                window.ChatApp.events.emit('typing:start', event.senderId);
                            })
                            .listen('TypingStopped', (event) => {
                                window.ChatApp.logger.log('⏹️ Typing stopped');
                                window.ChatApp.events.emit('typing:stop', event.senderId);
                            })
                            .listen('MessagesRead', (event) => {
                                window.ChatApp.logger.log('👁️ Messages read');
                                window.ChatApp.events.emit('messages:read', event);
                            })
                            .error((error) => {
                                window.ChatApp.logger.error('Private channel error:', error);
                            });

                        window.ChatApp.logger.log('✅ Private channel setup complete');
                    } catch (error) {
                        window.ChatApp.logger.error('Failed to setup private channel:', error);
                    }
                },

                retryConnection() {
                    if (this.connectionAttempt >= 3) {
                        window.ChatApp.logger.error('Max connection attempts reached. Stopping.');
                        return;
                    }

                    window.ChatApp.logger.log(
                        `Retrying connection (attempt ${this.connectionAttempt + 1}/3)...`);

                    setTimeout(() => {
                        if (this.echo && this.echo.connector && this.echo.connector.pusher) {
                            this.echo.connector.pusher.connect();
                        } else {
                            this.loadAndSetupEcho();
                        }
                    }, 2000);
                },

                getConnectionStatus() {
                    if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
                        return 'not_initialized';
                    }
                    return this.echo.connector.pusher.connection.state;
                },

                getSocketId() {
                    if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
                        return null;
                    }
                    return this.echo.connector.pusher.connection.socket_id;
                },

                getEcho() {
                    return this.echo;
                },

                disconnect() {
                    if (this.echo && this.echo.connector && this.echo.connector.pusher) {
                        this.echo.connector.pusher.disconnect();
                    }
                }
            };

            window.ChatApp.RealtimeService = RealtimeService;
            RealtimeService.init();

            window.getEchoInstance = function() {
                return window.ChatApp.RealtimeService.getEcho();
            };
        });
    </script>
@endpush
