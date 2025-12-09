{{-- This component doesn't render HTML, just pushes JavaScript --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const RealtimeService = {
                init() {
                    this.echo = null;
                    this.connectionAttempts = 0;
                    this.maxConnectionAttempts = 3;

                    window.ChatApp.logger.log('RealtimeService initializing...', {
                        env: {
                            REVERB_HOST: '{{ env('REVERB_HOST', '127.0.0.1') }}',
                            REVERB_APP_KEY: '{{ substr(env('REVERB_APP_KEY', 'reverb_key'), 0, 10) }}...',
                            currentProtocol: window.location.protocol,
                            currentHost: window.location.hostname,
                            currentPort: window.location.port,
                            isSecureContext: window.isSecureContext
                        }
                    });
                    this.setupEcho();
                },

                async setupEcho() {
                    try {
                        window.ChatApp.logger.log('Loading Pusher and Echo scripts...');

                        // Load Pusher
                        await this.loadScript('https://js.pusher.com/7.0/pusher.min.js');
                        window.ChatApp.logger.log('Pusher script loaded successfully');

                        // Load Echo
                        await this.loadScript(
                            'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js');
                        window.ChatApp.logger.log('Echo script loaded successfully');

                        // IMPORTANT: Try different configurations
                        const configs = [{
                                // Try direct WebSocket without Cloudflare proxy
                                name: 'direct-ws',
                                wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
                                wsPort: 8080,
                                forceTLS: false,
                                enabledTransports: ['ws'],
                                cluster: 'mt1'
                            },
                            {
                                // Try with Cloudflare (wss)
                                name: 'cloudflare-wss',
                                wsHost: window.location.hostname,
                                wsPort: 443,
                                forceTLS: true,
                                enabledTransports: ['wss'],
                                cluster: 'mt1'
                            },
                            {
                                // Try with Cloudflare (ws on 8080)
                                name: 'cloudflare-ws-8080',
                                wsHost: window.location.hostname,
                                wsPort: 8080,
                                forceTLS: false,
                                enabledTransports: ['ws'],
                                cluster: 'mt1'
                            }
                        ];

                        const echoConfig = {
                            broadcaster: 'pusher',
                            key: '{{ env('REVERB_APP_KEY', 'reverb_key') }}',
                            ...configs[0], // Start with first config
                            disableStats: true,
                            authEndpoint: '/broadcasting/auth',
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            }
                        };

                        // Safe logging without circular references
                        window.ChatApp.logger.log('Creating Echo instance with config:', {
                            config: JSON.parse(JSON.stringify(echoConfig)),
                            connectionUrl: `ws://${echoConfig.wsHost}:${echoConfig.wsPort}/app/${echoConfig.key}`
                        });

                        // Initialize Echo
                        this.echo = new Echo(echoConfig);

                        this.setupConnectionListeners();
                        this.setupChannels();

                        // Emit event when Echo is ready
                        setTimeout(() => {
                            if (window.ChatApp?.events?.emit) {
                                window.ChatApp.events.emit('echo:ready');
                            }
                            window.ChatApp.logger.log('Echo instance created');
                        }, 100);

                    } catch (error) {
                        window.ChatApp.logger.error('Failed to setup Echo', {
                            errorMessage: error.message,
                            errorName: error.name
                        });
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
                        window.ChatApp.logger.error('Echo or Pusher not initialized');
                        return;
                    }

                    const pusher = this.echo.connector.pusher;

                    // Safe logging without circular references
                    const safeOptions = {};
                    if (this.echo.connector.options) {
                        const opts = this.echo.connector.options;
                        safeOptions.key = opts.key;
                        safeOptions.wsHost = opts.wsHost;
                        safeOptions.wsPort = opts.wsPort;
                        safeOptions.forceTLS = opts.forceTLS;
                        safeOptions.enabledTransports = opts.enabledTransports;
                        safeOptions.cluster = opts.cluster;
                    }

                    window.ChatApp.logger.log('Setting up Pusher connection listeners', {
                        pusherVersion: typeof Pusher !== 'undefined' ? Pusher.VERSION : 'undefined',
                        echoConfig: safeOptions
                    });

                    pusher.connection.bind('connecting', () => {
                        this.connectionAttempts++;
                        window.ChatApp.logger.log(
                            `🔄 Connection attempt ${this.connectionAttempts}/${this.maxConnectionAttempts}`, {
                                state: pusher.connection.state,
                                transport: pusher.connection.transport ? {
                                    name: pusher.connection.transport.name,
                                    type: pusher.connection.transport.type
                                } : null
                            });
                    });

                    pusher.connection.bind('connected', () => {
                        const socketId = pusher.connection.socket_id;
                        window.ChatApp.logger.log('✅ Connected to Reverb', {
                            socketId: socketId,
                            state: pusher.connection.state,
                            transport: pusher.connection.transport ? {
                                name: pusher.connection.transport.name,
                                url: pusher.connection.transport.url
                            } : null
                        });
                        window.ChatApp.events.emit('connection:connected', socketId);
                        this.connectionAttempts = 0;
                    });

                    pusher.connection.bind('error', (error) => {
                        window.ChatApp.logger.error('❌ Pusher connection error', {
                            errorType: error.type,
                            errorMessage: error.message,
                            errorCode: error.code,
                            state: pusher.connection.state
                        });
                        window.ChatApp.events.emit('connection:error', error);

                        // Try alternative configuration if too many failures
                        if (this.connectionAttempts >= this.maxConnectionAttempts) {
                            this.tryAlternativeConfiguration();
                        }
                    });

                    pusher.connection.bind('disconnected', () => {
                        window.ChatApp.logger.log('🔌 Disconnected from Reverb', {
                            state: pusher.connection.state
                        });
                        window.ChatApp.events.emit('connection:disconnected');
                    });

                    pusher.connection.bind('unavailable', () => {
                        window.ChatApp.logger.error('🚫 Realtime service unavailable');
                        window.ChatApp.events.emit('connection:unavailable');
                    });
                },

                tryAlternativeConfiguration() {
                    window.ChatApp.logger.log('Trying alternative WebSocket configuration...');

                    // We'll implement reconnection logic here if needed
                    // For now, just log and let user know
                    if (window.ChatApp?.notification) {
                        window.ChatApp.notification.error(
                            'WebSocket connection failed. Please check if port 8080 is accessible.'
                        );
                    }
                },

                setupChannels() {
                    const authUserId = window.ChatApp.config.authUserId;

                    if (!authUserId) {
                        window.ChatApp.logger.error('No authUserId found in config');
                        return;
                    }

                    window.ChatApp.logger.log('Setting up channels', {
                        authUserId: authUserId,
                        privateChannelName: `chat.${authUserId}`,
                        presenceChannelName: 'presence.chat'
                    });

                    // Presence channel
                    try {
                        this.echo.join('presence.chat')
                            .here((users) => {
                                window.ChatApp.logger.log('👥 Presence here - initial users', {
                                    count: users.length,
                                    userNames: users.map(u => u.name || u.id).slice(0,
                                        5) // Limit to 5 for logging
                                });
                                window.ChatApp.events.emit('presence:here', users);
                            })
                            .joining((user) => {
                                window.ChatApp.logger.log('➕ User joining presence', {
                                    userName: user.name || user.id
                                });
                                window.ChatApp.events.emit('presence:joining', user);
                            })
                            .leaving((user) => {
                                window.ChatApp.logger.log('➖ User leaving presence', {
                                    userName: user.name || user.id
                                });
                                window.ChatApp.events.emit('presence:leaving', user);
                            })
                            .error((error) => {
                                window.ChatApp.logger.error('❌ Presence channel error', {
                                    errorMessage: error.message
                                });
                            });
                    } catch (error) {
                        window.ChatApp.logger.error('Failed to join presence channel', {
                            errorMessage: error.message
                        });
                    }

                    // Private channel for messages and typing
                    try {
                        const privateChannel = this.echo.private(`chat.${authUserId}`);

                        privateChannel
                            .subscribed(() => {
                                window.ChatApp.logger.log('✅ Private channel subscribed successfully');
                            })
                            .listen('MessageSent', (event) => {
                                window.ChatApp.logger.log('📨 MessageSent event received');
                                if (event && event.message) {
                                    window.ChatApp.events.emit('message:received', event.message);
                                }
                            })
                            .listen('TypingEvent', (event) => {
                                window.ChatApp.logger.log('⌨️ TypingEvent received');
                                if (event && event.senderId) {
                                    window.ChatApp.events.emit('typing:start', event.senderId);
                                    window.ChatApp.events.emit('echo:typing:start', event);
                                }
                            })
                            .listen('TypingStopped', (event) => {
                                window.ChatApp.logger.log('⏹️ TypingStopped received');
                                if (event && event.senderId) {
                                    window.ChatApp.events.emit('typing:stop', event.senderId);
                                    window.ChatApp.events.emit('echo:typing:stop', event);
                                }
                            })
                            .listen('MessagesRead', (event) => {
                                window.ChatApp.logger.log('👁️ MessagesRead event');
                                window.ChatApp.events.emit('messages:read', event);
                            })
                            .error((error) => {
                                window.ChatApp.logger.error('❌ Private channel error', {
                                    errorMessage: error.message
                                });
                            });
                    } catch (error) {
                        window.ChatApp.logger.error('Failed to setup private channel', {
                            errorMessage: error.message
                        });
                    }
                },

                // Safe method to get connection status
                getConnectionStatus() {
                    if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
                        return 'not_initialized';
                    }
                    return this.echo.connector.pusher.connection.state;
                },

                // Safe method to get socket ID
                getSocketId() {
                    if (!this.echo || !this.echo.connector || !this.echo.connector.pusher) {
                        return null;
                    }
                    return this.echo.connector.pusher.connection.socket_id;
                }
            };

            // Store service globally
            window.ChatApp.RealtimeService = RealtimeService;

            // Helper function to test WebSocket connection
            window.testWebSocketConnection = function() {
                window.ChatApp.logger.log('Testing WebSocket connection...');

                // Test if WebSocket is supported
                if (!window.WebSocket) {
                    window.ChatApp.logger.error('WebSocket not supported in this browser');
                    return false;
                }

                // Test direct connection to Reverb server
                const testWs = new WebSocket('ws://core.arabianpay.net:8080');

                testWs.onopen = function() {
                    window.ChatApp.logger.log('✅ Direct WebSocket test: Connected');
                    testWs.close();
                };

                testWs.onerror = function(error) {
                    window.ChatApp.logger.error('❌ Direct WebSocket test: Failed to connect', {
                        error: error
                    });

                    // Try with wss (Cloudflare)
                    const testWss = new WebSocket('wss://core.arabianpay.net:8080');

                    testWss.onopen = function() {
                        window.ChatApp.logger.log('✅ WSS WebSocket test: Connected (via Cloudflare)');
                        testWss.close();
                    };

                    testWss.onerror = function(wssError) {
                        window.ChatApp.logger.error('❌ WSS WebSocket test: Also failed', {
                            error: wssError
                        });
                    };
                };

                return true;
            };

            // Initialize
            RealtimeService.init();

            // Test connection after 2 seconds
            setTimeout(() => {
                if (RealtimeService.getConnectionStatus() !== 'connected') {
                    window.ChatApp.logger.warn('Connection not established, running diagnostic...');
                    window.testWebSocketConnection();
                }
            }, 2000);
        });
    </script>
@endpush
