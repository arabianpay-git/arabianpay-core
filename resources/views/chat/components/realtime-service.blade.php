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
                        isSecureContext: window.isSecureContext
                    });

                    this.loadAndSetupEcho();
                },

                async loadAndSetupEcho() {
                    try {
                        window.ChatApp.logger.log('Loading Pusher and Echo scripts...');

                        // Load Pusher first
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

                        // Wait a moment for scripts to initialize
                        await new Promise(resolve => setTimeout(resolve, 100));

                        // Check if Echo is available
                        if (typeof window.Echo === 'undefined') {
                            throw new Error('Echo is not defined after loading script');
                        }

                        window.ChatApp.logger.log('Echo available:', {
                            hasEcho: typeof window.Echo !== 'undefined',
                            echoType: typeof window.Echo,
                            isFunction: typeof window.Echo === 'function'
                        });

                        this.setupEchoConnection();

                    } catch (error) {
                        window.ChatApp.logger.error('Failed to load scripts', {
                            error: error.message,
                            stack: error.stack
                        });
                    }
                },

                setupEchoConnection() {
                    try {
                        // IMPORTANT: Try different connection strategies
                        const connectionStrategies = [{
                            name: 'direct-ip-ws',
                            config: {
                                broadcaster: 'pusher',
                                key: '{{ env('REVERB_APP_KEY', 'reverb_key') }}',
                                wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
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
                        }];

                        // Try first strategy
                        const strategy = connectionStrategies[0];
                        window.ChatApp.logger.log('Using connection strategy:', {
                            strategy: strategy.name,
                            wsUrl: `ws://${strategy.config.wsHost}:${strategy.config.wsPort}/app/${strategy.config.key}`
                        });

                        // Create Echo instance
                        this.echo = new window.Echo(strategy.config);

                        // CRITICAL: Make Echo instance globally available
                        window.echoInstance = this.echo;

                        // Also make it available via ChatApp
                        window.ChatApp.echo = this.echo;

                        // Verify Echo instance
                        window.ChatApp.logger.log('Echo instance created:', {
                            hasJoin: typeof this.echo.join === 'function',
                            hasPrivate: typeof this.echo.private === 'function',
                            hasChannel: typeof this.echo.channel === 'function',
                            instanceType: this.echo.constructor.name,
                            // Log the actual methods available
                            methods: Object.getOwnPropertyNames(Object.getPrototypeOf(this.echo))
                        });

                        if (!this.echo.private || !this.echo.join) {
                            throw new Error('Echo instance missing required methods');
                        }

                        this.setupConnectionListeners();
                        this.setupChannels();

                        window.ChatApp.logger.log('✅ Echo setup complete, attempting connection...');

                        // Emit event that Echo is ready
                        setTimeout(() => {
                            window.ChatApp.events.emit('echo:ready', this.echo);
                        }, 100);

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
                        pusherAvailable: !!pusher
                    });

                    // Log all connection states
                    const states = ['initialized', 'connecting', 'connected', 'unavailable', 'failed',
                        'disconnected'
                    ];
                    states.forEach(state => {
                        pusher.connection.bind(state, (data) => {
                            window.ChatApp.logger.log(`Pusher state: ${state}`, {
                                data: data,
                                socketId: pusher.connection.socket_id,
                                activityTimeout: pusher.connection.activity_timeout
                            });
                        });
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
                                .name : 'unknown'
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
                            state: pusher.connection.state
                        });
                        window.ChatApp.events.emit('connection:error', error);
                        this.isConnecting = false;

                        // Try alternative configuration
                        setTimeout(() => this.retryConnection(), 2000);
                    });

                    pusher.connection.bind('unavailable', () => {
                        window.ChatApp.logger.error('🚫 Reverb server unavailable');
                        window.ChatApp.events.emit('connection:unavailable');
                        this.isConnecting = false;

                        // Retry after delay
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
                                    count: users.length,
                                    users: users.map(u => ({
                                        id: u.id,
                                        name: u.name
                                    }))
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
                                window.ChatApp.logger.log('📨 Message received:', event);
                                window.ChatApp.events.emit('message:received', event.message);
                            })
                            .listen('TypingEvent', (event) => {
                                window.ChatApp.logger.log('⌨️ Typing started:', event);
                                window.ChatApp.events.emit('typing:start', event.senderId);
                            })
                            .listen('TypingStopped', (event) => {
                                window.ChatApp.logger.log('⏹️ Typing stopped:', event);
                                window.ChatApp.events.emit('typing:stop', event.senderId);
                            })
                            .listen('MessagesRead', (event) => {
                                window.ChatApp.logger.log('👁️ Messages read:', event);
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

                // Public methods
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

            // Store service globally
            window.ChatApp.RealtimeService = RealtimeService;

            // Initialize immediately
            RealtimeService.init();

            // Add global helper to get Echo instance
            window.getEchoInstance = function() {
                return window.ChatApp.RealtimeService.getEcho();
            };

            // Add global helper for backward compatibility
            window.getEcho = function() {
                return window.ChatApp.RealtimeService.getEcho();
            };
        });
    </script>
@endpush
