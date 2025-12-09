<main class="col-span-12 lg:col-span-8 flex flex-col bg-gray-50 rounded-lg shadow-sm" id="main-chat-container">
    <div class="p-4 border-b bg-white flex items-center justify-between rounded-t-lg">
        <div class="flex items-center gap-3">
            <div class="relative">
                <img id="header-avatar"
                    src="{{ $otherUser->profile_photo_path ?? "https://ui-avatars.com/api/?name={$otherUser->first_name}+{$otherUser->last_name}&color=7F9CF5&background=EBF4FF" }}"
                    class="w-10 h-10 rounded-full object-cover" alt="{{ $otherUser->name }}" />
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-gray-400 rounded-full ring-1 ring-white"
                    id="header-online-dot"></span>
            </div>
            <div>
                <div id="header-name" class="font-semibold">{{ $otherUser->first_name }} {{ $otherUser->last_name }}
                </div>
                <div id="header-presence" class="text-xs text-gray-500">Offline</div>
            </div>
        </div>

        <div>
            <button class="icon-btn p-2 rounded-full hover:bg-gray-100" title="More" data-menu-open="header"
                data-target-id="header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        d="M12 5v.01M12 12v.01M12 19v.01" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Messages list component -->
    @include('chat.components.messages-list')

    <!-- Chat form component -->
    @include('chat.components.chat-form')
</main>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const MainChat = {
                config: {
                    typingTimeout: null,
                    onlineUsers: new Set(),
                    typingUsers: new Map()
                },

                init() {
                    this.elements = {
                        container: document.getElementById('main-chat-container'),
                        headerPresence: document.getElementById('header-presence'),
                        headerOnlineDot: document.getElementById('header-online-dot'),
                        headerName: document.getElementById('header-name'),
                        headerAvatar: document.getElementById('header-avatar')
                    };

                    this.setupEventListeners();
                    this.setupHeaderPresence();
                    this.loadMessages();

                    // Wait for Echo to be initialized, then setup typing listener
                    this.waitForEchoAndSetupTypingListener();

                    if (window.ChatApp?.logger) {
                        window.ChatApp.logger.log('MainChat initialized');
                    } else {
                        console.log('MainChat initialized');
                    }
                },

                // In MainChat component, add this to setupEventListeners:
                setupEventListeners() {
                    // Listen for presence events from global ChatApp
                    if (window.ChatApp?.events) {
                        window.ChatApp.events.on('presence:here', (users) => this.handlePresenceHere(users));
                        window.ChatApp.events.on('presence:joining', (user) => this.handlePresenceJoining(
                            user));
                        window.ChatApp.events.on('presence:leaving', (user) => this.handlePresenceLeaving(
                            user));
                        window.ChatApp.events.on('typing:start', (userId) => this.handleTypingStart(userId));
                        window.ChatApp.events.on('typing:stop', (userId) => this.handleTypingStop(userId));
                        window.ChatApp.events.on('echo:ready', () => {
                            console.log('MainChat: Echo is ready, setting up typing listener');
                            this.setupTypingListener();
                        });
                    }
                },

                waitForEchoAndSetupTypingListener() {
                    let attempts = 0;
                    const maxAttempts = 10;

                    const checkEcho = () => {
                        if (typeof window.Echo !== 'undefined') {
                            this.setupTypingListener();
                        } else if (attempts < maxAttempts) {
                            attempts++;
                            setTimeout(checkEcho, 500);
                        } else {
                            console.error('Echo not available after multiple attempts');
                        }
                    };

                    checkEcho();
                },

                setupTypingListener() {
                    const authUserId = window.ChatApp?.config?.authUserId || {{ Auth::id() }};
                    const channelName = `chat.${authUserId}`;

                    console.log('MainChat: Setting up typing listener on channel', channelName);

                    if (window.ChatApp?.logger) {
                        window.ChatApp.logger.log('Setting up Echo typing listener', {
                            channel: channelName
                        });
                    }

                    try {
                        window.Echo.private(channelName)
                            .listen('TypingEvent', (event) => {
                                console.log('MainChat: TypingEvent received', event);

                                if (window.ChatApp?.logger) {
                                    window.ChatApp.logger.log('TypingEvent received via Echo', event);
                                }

                                if (event.senderId && event.senderId !== authUserId) {
                                    this.handleTypingStart(event.senderId);
                                }
                            })
                            .listen('TypingStopped', (event) => {
                                console.log('MainChat: TypingStopped received', event);

                                if (window.ChatApp?.logger) {
                                    window.ChatApp.logger.log('TypingStopped received via Echo', event);
                                }

                                if (event.senderId && event.senderId !== authUserId) {
                                    this.handleTypingStop(event.senderId);
                                }
                            })
                            .error((error) => {
                                console.error('MainChat: Echo typing channel error', error);

                                if (window.ChatApp?.logger) {
                                    window.ChatApp.logger.error('Echo typing channel error', error);
                                }
                            });

                        console.log('MainChat: Echo typing listener set up successfully');

                    } catch (error) {
                        console.error('MainChat: Failed to setup typing listener', error);

                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.error('Failed to setup typing listener', error);
                        }
                    }
                },

                setupHeaderPresence() {
                    this.updateHeaderPresence();
                },

                updateHeaderPresence() {
                    if (!this.elements.headerPresence || !this.elements.headerOnlineDot) return;

                    const currentChatId = window.ChatApp?.config?.currentChatId || {{ $otherUser->id }};
                    const isOnline = this.config.onlineUsers.has(Number(currentChatId));
                    const isTyping = this.config.typingUsers.has(currentChatId);

                    if (isTyping) {
                        this.elements.headerPresence.textContent = 'Typing...';
                        this.elements.headerPresence.className = 'text-xs text-blue-500 italic';
                    } else {
                        this.elements.headerPresence.textContent = isOnline ? 'Online' : 'Offline';
                        this.elements.headerPresence.className = 'text-xs text-gray-500';
                    }

                    this.elements.headerOnlineDot.className =
                        `absolute bottom-0 right-0 w-2.5 h-2.5 ${isOnline ? 'bg-green-500' : 'bg-gray-400'} rounded-full ring-1 ring-white`;

                    // Log the change
                    if (window.ChatApp?.logger) {
                        window.ChatApp.logger.log('Header presence updated', {
                            userId: currentChatId,
                            isOnline: isOnline,
                            isTyping: isTyping
                        });
                    }
                },

                handlePresenceHere(users) {
                    this.config.onlineUsers.clear();
                    users.forEach(user => {
                        if (user && user.id) {
                            this.config.onlineUsers.add(Number(user.id));
                        }
                    });
                    this.updateHeaderPresence();

                    // Log presence update
                    if (window.ChatApp?.logger) {
                        window.ChatApp.logger.log('Presence: here', {
                            users: users,
                            onlineCount: this.config.onlineUsers.size
                        });
                    }
                },

                handlePresenceJoining(user) {
                    if (user && user.id) {
                        this.config.onlineUsers.add(Number(user.id));
                        this.updateHeaderPresence();

                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.log('Presence: joining', user);
                        }
                    }
                },

                handlePresenceLeaving(user) {
                    if (user && user.id) {
                        this.config.onlineUsers.delete(Number(user.id));
                        this.updateHeaderPresence();

                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.log('Presence: leaving', user);
                        }
                    }
                },

                handleTypingStart(userId) {
                    const currentChatId = window.ChatApp?.config?.currentChatId || {{ $otherUser->id }};

                    console.log('MainChat: handleTypingStart called', {
                        userId,
                        currentChatId,
                        isCurrentChat: userId == currentChatId
                    });

                    // Only handle typing for current chat user
                    if (userId != currentChatId) {
                        console.log('MainChat: Ignoring typing start for non-current user');
                        return;
                    }

                    // Clear existing timeout
                    if (this.config.typingUsers.has(userId)) {
                        clearTimeout(this.config.typingUsers.get(userId));
                    }

                    // Set new timeout to clear typing after 3 seconds
                    const timeoutId = setTimeout(() => {
                        console.log('MainChat: Auto-clearing typing for user:', userId);
                        this.config.typingUsers.delete(userId);
                        this.updateHeaderPresence();
                    }, 3000);

                    this.config.typingUsers.set(userId, timeoutId);
                    this.updateHeaderPresence();

                    // Log typing start
                    if (window.ChatApp?.logger) {
                        window.ChatApp.logger.log('Typing: start', {
                            userId: userId,
                            currentChatId: currentChatId
                        });
                    }
                },

                handleTypingStop(userId) {
                    const currentChatId = window.ChatApp?.config?.currentChatId || {{ $otherUser->id }};

                    // Only handle typing for current chat user
                    if (userId != currentChatId) {
                        console.log('MainChat: Ignoring typing stop for non-current user');
                        return;
                    }

                    if (this.config.typingUsers.has(userId)) {
                        clearTimeout(this.config.typingUsers.get(userId));
                        this.config.typingUsers.delete(userId);
                        this.updateHeaderPresence();

                        // Log typing stop
                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.log('Typing: stop', {
                                userId: userId,
                                currentChatId: currentChatId
                            });
                        }
                    }
                },

                async loadMessages() {
                    try {
                        const currentChatId = window.ChatApp?.config?.currentChatId || {{ $otherUser->id }};
                        const csrfToken = window.ChatApp?.config?.csrfToken || '{{ csrf_token() }}';

                        const response = await fetch(
                            `/admin/messages/${currentChatId}`, {
                                credentials: 'include',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            });

                        if (!response.ok) throw new Error(`HTTP ${response.status}`);

                        const messages = await response.json();

                        // Emit event for messages-list component
                        if (window.ChatApp?.events?.emit) {
                            window.ChatApp.events.emit('messages:loaded', messages);
                        }

                        // Log message load
                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.log('Messages loaded', {
                                count: messages.length,
                                chatId: currentChatId
                            });
                        }

                    } catch (error) {
                        const errorMsg = error.message || error;
                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.error('Failed to load messages', errorMsg);
                        } else {
                            console.error('Failed to load messages', errorMsg);
                        }
                    }
                },

                async markAsRead() {
                    try {
                        const currentChatId = window.ChatApp?.config?.currentChatId ||
                            {{ $otherUser->id }};
                        const csrfToken = window.ChatApp?.config?.csrfToken || '{{ csrf_token() }}';

                        await fetch(`/admin/messages/${currentChatId}/read`, {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });

                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.log('Messages marked as read', {
                                chatId: currentChatId
                            });
                        }
                    } catch (error) {
                        const errorMsg = error.message || error;
                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.error('Failed to mark as read', errorMsg);
                        } else {
                            console.error('Failed to mark as read', errorMsg);
                        }
                    }
                },

                handleLogEntry(logEntry) {
                    // This method handles log entries from the global logger
                    console.log('Global log entry:', logEntry);
                }
            };

            // Initialize MainChat with a slight delay to ensure all components are loaded
            setTimeout(() => {
                console.log('Initializing MainChat component...');
                MainChat.init();

                // Mark as read on initial load
                setTimeout(() => {
                    MainChat.markAsRead();
                }, 500);
            }, 300);
        });
    </script>
@endpush
