<aside class="col-span-12 lg:col-span-4 bg-white border rounded shadow-sm overflow-hidden" id="sidebar-container">
    <div class="p-4 border-b flex items-center justify-between">
        <div class="font-semibold">Messages</div>
        <button id="sidebar-new" class="px-2 py-1 rounded bg-indigo-50 text-indigo-700 text-sm">New</button>
    </div>

    <div class="p-3 border-b">
        <div class="relative">
            <svg class="absolute text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none"
                style="top: 30%; left: 3%;">
                <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M11.5 19a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
            </svg>
            <input id="search-users" class="w-full pl-10 pr-4 py-2 border rounded-xl text-sm" style="padding-left: 2rem"
                placeholder="Search chats..." />
        </div>
    </div>

    <div id="chat-list" class="overflow-y-auto" style="max-height:520px;">
        <!-- sidebar items injected via JS -->
    </div>
</aside>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Sidebar = {
                config: {
                    debounceTimer: null,
                    onlineUsers: new Set(),
                    // Map<Number(userId), timeoutId>
                    typingUsers: new Map(),
                    userDataCache: new Map()
                },

                init() {
                    this.elements = {
                        container: document.getElementById('sidebar-container'),
                        chatList: document.getElementById('chat-list'),
                        searchInput: document.getElementById('search-users'),
                        newChatBtn: document.getElementById('sidebar-new')
                    };

                    this.setupEventListeners();
                    this.refresh();
                    if (window.ChatApp?.logger) window.ChatApp.logger.log('Sidebar initialized');
                },

                setupEventListeners() {
                    // Search input
                    if (this.elements.searchInput) {
                        this.elements.searchInput.addEventListener('input', (e) => {
                            clearTimeout(this.config.debounceTimer);
                            this.config.debounceTimer = setTimeout(() => {
                                this.refresh(e.target.value.trim());
                            }, 300);
                        });
                    }

                    // New chat button
                    if (this.elements.newChatBtn) {
                        this.elements.newChatBtn.addEventListener('click', () => {
                            // Implement new chat functionality
                            window.ChatApp?.logger?.log('New chat button clicked');
                        });
                    }

                    // Listen for events (normalize IDs when passing through)
                    window.ChatApp?.events?.on('presence:here', (users) => this.handlePresenceHere(users));
                    window.ChatApp?.events?.on('presence:joining', (user) => this.handlePresenceJoining(user));
                    window.ChatApp?.events?.on('presence:leaving', (user) => this.handlePresenceLeaving(user));
                    window.ChatApp?.events?.on('typing:start', (userId) => this.handleTypingStart(userId));
                    window.ChatApp?.events?.on('typing:stop', (userId) => this.handleTypingStop(userId));
                    window.ChatApp?.events?.on('message:sent', (message) => this.handleNewMessage(message));
                    window.ChatApp?.events?.on('message:received', (message) => this.handleNewMessage(message));
                    window.ChatApp?.events?.on('messages:read', (userId) => this.handleMessagesRead(userId));
                },

                async refresh(query = '') {
                    try {
                        const url = `/admin/chat-users?search=${encodeURIComponent(query)}`;
                        const response = await fetch(url, {
                            credentials: 'include',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error(`HTTP ${response.status}`);

                        const users = await response.json();
                        this.renderChatList(users);
                        window.ChatApp?.logger?.log('Sidebar refreshed', {
                            userCount: Array.isArray(users) ? users.length : 0
                        });

                    } catch (error) {
                        window.ChatApp?.logger?.error?.('Failed to refresh sidebar', error) || console
                            .error(error);
                    }
                },

                renderChatList(users) {
                    if (!this.elements.chatList) return;

                    this.elements.chatList.innerHTML = '';

                    if (!Array.isArray(users) || users.length === 0) {
                        this.elements.chatList.innerHTML =
                            '<div class="p-4 text-sm text-gray-500">No chats</div>';
                        return;
                    }

                    users.forEach(user => {
                        const element = this.createChatItem(user);
                        this.elements.chatList.appendChild(element);
                    });

                    // After rendering, ensure typing/online indicators reflect current state
                    this.refreshOnlineIndicators();
                    this.refreshTypingIndicators();
                },

                createChatItem(user) {
                    // ensure numeric id
                    const userIdNum = Number(user.id);
                    const isActive = userIdNum === Number(window.ChatApp?.config?.currentChatId);
                    const isOnline = this.config.onlineUsers.has(userIdNum);
                    const isTyping = this.config.typingUsers.has(userIdNum);
                    const hasUnread = Number(user.unread_count) > 0;

                    const element = document.createElement('div');
                    element.className = `chat-item group flex items-center p-4 hover:bg-gray-50 border-b relative cursor-pointer 
                    ${isActive ? 'bg-indigo-50 border-l-4 border-indigo-600' : ''} 
                    ${hasUnread ? 'bg-gray-50' : ''}`;
                    element.dataset.chatId = String(userIdNum);
                    element.dataset.unreadCount = String(user.unread_count || 0);
                    element.dataset.lastMessage = this.escapeHtml(user.last_message || '');
                    element.dataset.lastTime = user.last_time || '';

                    element.innerHTML = `
                    <div class="relative">
                        <img src="${this.escapeHtml(user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=7F9CF5&background=EBF4FF`)}" 
                             class="w-11 h-11 rounded-full object-cover" alt="${this.escapeHtml(user.name)}" />
                        <span class="online-dot absolute bottom-0 right-0 w-[10px] h-[10px] ${isOnline ? 'bg-green-500' : 'bg-gray-400'} rounded-full"></span>
                    </div>
                    <div class="ml-3 flex-1 overflow-hidden">
                        <div class="font-semibold text-sm truncate">${this.escapeHtml(user.name)}</div>
                        <div class="flex items-center gap-1">
                            <div class="last-message-text text-xs text-gray-500 truncate ${isTyping ? 'hidden' : ''}"
                                 data-message="${this.escapeHtml(user.last_message || '')}">
                                ${this.escapeHtml(user.last_message || 'No messages yet')}
                            </div>
                            <div class="typing-indicator text-xs text-blue-500 italic ${isTyping ? '' : 'hidden'}">
                                typing...
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end ml-2 flex-shrink-0">
                        <div class="last-message-time text-xs text-gray-400 font-medium" 
                             data-time="${user.last_time || ''}">
                            ${user.last_time || ''}
                        </div>
                        ${hasUnread ? `<span class="unread-badge mt-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-green-600 rounded-full">
                                                                                        ${user.unread_count > 99 ? '99+' : this.escapeHtml(String(user.unread_count))}
                                                                                    </span>` : ''}
                    </div>
                `;

                    element.addEventListener('click', () => {
                        if (Number(element.dataset.unreadCount) > 0) {
                            this.markAsRead(userIdNum);
                            element.dataset.unreadCount = '0';
                            const badge = element.querySelector('.unread-badge');
                            if (badge) badge.remove();
                        }

                        // Determine locale prefix
                        const pathParts = window.location.pathname.split('/').filter(
                            Boolean); // remove empty
                        const locale = pathParts[0]?.length === 2 ? pathParts[0] : ''; // e.g., 'en'
                        const prefix = locale ? `/${locale}` : '';

                        window.location.href = `${prefix}/admin/chat/${userIdNum}`;
                    });

                    return element;
                },

                escapeHtml(text) {
                    if (!text && text !== 0) return '';
                    return String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                handlePresenceHere(users) {
                    this.config.onlineUsers.clear();
                    users.forEach(user => {
                        if (user && user.id) {
                            this.config.onlineUsers.add(Number(user.id));
                        }
                    });
                    this.refreshOnlineIndicators();
                },

                handlePresenceJoining(user) {
                    if (user && user.id) {
                        this.config.onlineUsers.add(Number(user.id));
                        this.refreshOnlineIndicators();
                    }
                },

                handlePresenceLeaving(user) {
                    if (user && user.id) {
                        this.config.onlineUsers.delete(Number(user.id));
                        this.refreshOnlineIndicators();
                    }
                },

                handleTypingStart(rawUserId) {
                    const userId = Number(rawUserId);
                    if (Number.isNaN(userId)) return;

                    // If existing, clear previous timeout (reset)
                    if (this.config.typingUsers.has(userId)) {
                        clearTimeout(this.config.typingUsers.get(userId));
                    }

                    // Set new timeout; store the timeout id in the Map.
                    const timeoutId = setTimeout(() => {
                        // Auto-clear and refresh UI when timeout expires
                        this.config.typingUsers.delete(userId);
                        this.refreshTypingIndicators();
                    }, 3000);

                    this.config.typingUsers.set(userId, timeoutId);
                    this.refreshTypingIndicators();

                    if (window.ChatApp?.logger) {
                        window.ChatApp.logger.log('Sidebar typing:start', {
                            userId
                        });
                    }
                },

                handleTypingStop(rawUserId) {
                    const userId = Number(rawUserId);
                    if (Number.isNaN(userId)) return;

                    if (this.config.typingUsers.has(userId)) {
                        clearTimeout(this.config.typingUsers.get(userId));
                        this.config.typingUsers.delete(userId);
                        this.refreshTypingIndicators();

                        if (window.ChatApp?.logger) {
                            window.ChatApp.logger.log('Sidebar typing:stop', {
                                userId
                            });
                        }
                    }
                },

                handleNewMessage(message) {
                    if (!message || !message.sender_id) return;

                    const userId = Number(message.sender_id);
                    const chatItem = this.elements.chatList?.querySelector(`[data-chat-id="${userId}"]`);

                    if (chatItem) {
                        // Update last message
                        const lastMessageEl = chatItem.querySelector('.last-message-text');
                        if (lastMessageEl) {
                            lastMessageEl.textContent = this.escapeHtml(message.message || '');
                            lastMessageEl.dataset.message = this.escapeHtml(message.message || '');
                        }

                        // Update timestamp
                        const timeEl = chatItem.querySelector('.last-message-time');
                        if (timeEl) {
                            const now = new Date();
                            const formattedTime = this.formatTime(now);
                            timeEl.textContent = formattedTime;
                            timeEl.dataset.time = formattedTime;
                        }

                        // Update unread count if message is from other user
                        const currentChatId = Number(window.ChatApp?.config?.currentChatId);
                        if (userId !== currentChatId) {
                            const unreadCount = parseInt(chatItem.dataset.unreadCount || 0) + 1;
                            chatItem.dataset.unreadCount = unreadCount.toString();

                            let badge = chatItem.querySelector('.unread-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className =
                                    'unread-badge mt-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full';
                                const badgeContainer = chatItem.querySelector('.flex.flex-col.items-end');
                                if (badgeContainer) {
                                    badgeContainer.appendChild(badge);
                                }
                                chatItem.classList.add('bg-blue-50');
                            }
                            badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                        }

                        // Move chat item to top
                        this.moveChatToTop(userId);
                    } else {
                        // If chat item doesn't exist, refresh the list
                        this.refresh();
                    }
                },

                handleMessagesRead(userId) {
                    const userIdNum = Number(userId);
                    const chatItem = this.elements.chatList?.querySelector(`[data-chat-id="${userIdNum}"]`);

                    if (chatItem) {
                        chatItem.dataset.unreadCount = '0';
                        const badge = chatItem.querySelector('.unread-badge');
                        if (badge) badge.remove();
                        chatItem.classList.remove('bg-blue-50');
                    }
                },

                refreshOnlineIndicators() {
                    const items = this.elements.chatList?.querySelectorAll('[data-chat-id]');
                    if (!items) return;

                    items.forEach(item => {
                        const id = Number(item.dataset.chatId);
                        const dot = item.querySelector('.online-dot');
                        if (dot) {
                            dot.className = `online-dot absolute bottom-0 right-0 w-[10px] h-[10px] 
                                ${this.config.onlineUsers.has(id) ? 'bg-green-500' : 'bg-gray-400'} 
                                rounded-full`;
                        }
                    });
                },

                refreshTypingIndicators() {
                    const items = this.elements.chatList?.querySelectorAll('[data-chat-id]');
                    if (!items) return;

                    items.forEach(item => {
                        const id = Number(item.dataset.chatId);
                        const lastMessage = item.querySelector('.last-message-text');
                        const typingEl = item.querySelector('.typing-indicator');

                        const isTyping = this.config.typingUsers.has(id);

                        if (lastMessage && typingEl) {
                            if (isTyping) {
                                lastMessage.classList.add('hidden');
                                typingEl.classList.remove('hidden');
                            } else {
                                lastMessage.classList.remove('hidden');
                                typingEl.classList.add('hidden');
                            }
                        }
                    });
                },

                async markAsRead(userId) {
                    try {
                        await fetch(`/admin/messages/${userId}/read`, {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': window.ChatApp?.config?.csrfToken
                            }
                        });
                        window.ChatApp?.logger?.log(`Marked messages from ${userId} as read`);
                    } catch (error) {
                        window.ChatApp?.logger?.error?.('Failed to mark as read', error) || console.error(
                            error);
                    }
                },

                moveChatToTop(userId) {
                    const chatItem = this.elements.chatList?.querySelector(`[data-chat-id="${userId}"]`);
                    if (chatItem && this.elements.chatList.firstChild !== chatItem) {
                        this.elements.chatList.insertBefore(chatItem, this.elements.chatList.firstChild);
                    }
                },

                formatTime(date) {
                    const now = new Date();
                    const messageDate = new Date(date);

                    if (now.toDateString() === messageDate.toDateString()) {
                        // Today: show time
                        return messageDate.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else if (now.getTime() - messageDate.getTime() < 7 * 24 * 60 * 60 * 1000) {
                        // Within a week: show day
                        return messageDate.toLocaleDateString([], {
                            weekday: 'short'
                        });
                    } else {
                        // Older: show date
                        return messageDate.toLocaleDateString([], {
                            month: 'short',
                            day: 'numeric'
                        });
                    }
                }
            };

            Sidebar.init();
        });
    </script>
@endpush
