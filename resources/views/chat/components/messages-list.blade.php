<div id="chat-box" class="flex-1 overflow-hidden bg-gradient-to-b from-gray-50 to-gray-100">
    <div id="messages-list" class="p-6 space-y-4 overflow-y-auto h-full" style="max-height: 500px;">
        <!-- Loading spinner for initial load -->
        <div id="loading-spinner" class="text-center py-8">
            <div class="inline-flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Loading messages...</span>
            </div>
        </div>

        <!-- Load more indicator -->
        <div id="load-more-indicator" class="text-center py-2 hidden">
            <button id="load-more-btn"
                class="text-sm text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg border border-blue-200 bg-white">
                Load older messages
            </button>
        </div>

        <!-- Messages will be inserted here -->
        <div id="messages-container" class="space-y-4"></div>

        <!-- No messages placeholder -->
        <div id="no-messages" class="text-center text-gray-500 py-8 hidden">
            <div class="mb-2">
                <svg class="w-12 h-12 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                    </path>
                </svg>
            </div>
            <p>No messages yet</p>
            <p class="text-sm mt-1">Start a conversation by sending a message</p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const MessagesList = {
                config: {
                    messages: new Map(),
                    localMessages: new Map(),
                    isLoading: false,
                    hasMoreMessages: true,
                    currentPage: 1,
                    messagesPerPage: 20,
                    scrollPosition: 0
                },

                init() {
                    this.elements = {
                        container: document.getElementById('messages-list'),
                        messagesContainer: document.getElementById('messages-container'),
                        loadingSpinner: document.getElementById('loading-spinner'),
                        noMessages: document.getElementById('no-messages'),
                        loadMoreIndicator: document.getElementById('load-more-indicator'),
                        loadMoreBtn: document.getElementById('load-more-btn'),
                        chatBox: document.getElementById('chat-box')
                    };

                    this.setupEventListeners();
                    this.setupScrollListener();

                    // Show loading spinner initially
                    this.showLoading();

                    window.ChatApp.logger.log('MessagesList initialized');
                },

                setupEventListeners() {
                    window.ChatApp.events.on('messages:loaded', (messages) => this.handleMessagesLoaded(
                        messages));
                    window.ChatApp.events.on('message:local', (message) => this.addLocalMessage(message));
                    window.ChatApp.events.on('message:sent', (message) => this.replaceLocalMessage(message));
                    window.ChatApp.events.on('message:received', (message) => this.addMessage(message));

                    // Load more button
                    if (this.elements.loadMoreBtn) {
                        this.elements.loadMoreBtn.addEventListener('click', () => this.loadMoreMessages());
                    }
                },

                setupScrollListener() {
                    if (this.elements.container) {
                        this.elements.container.addEventListener('scroll', () => {
                            // Check if we're near the top (for infinite scroll)
                            if (this.elements.container.scrollTop < 100 &&
                                !this.config.isLoading &&
                                this.config.hasMoreMessages) {
                                this.loadMoreMessages();
                            }
                        });
                    }
                },

                showLoading() {
                    if (this.elements.loadingSpinner) {
                        this.elements.loadingSpinner.classList.remove('hidden');
                    }
                    if (this.elements.noMessages) {
                        this.elements.noMessages.classList.add('hidden');
                    }
                    this.config.isLoading = true;
                },

                hideLoading() {
                    if (this.elements.loadingSpinner) {
                        this.elements.loadingSpinner.classList.add('hidden');
                    }
                    this.config.isLoading = false;
                },

                showLoadMoreButton(show = true) {
                    if (this.elements.loadMoreIndicator) {
                        if (show && this.config.hasMoreMessages) {
                            this.elements.loadMoreIndicator.classList.remove('hidden');
                        } else {
                            this.elements.loadMoreIndicator.classList.add('hidden');
                        }
                    }
                },

                async handleMessagesLoaded(messages) {
                    this.hideLoading();

                    if (!Array.isArray(messages) || messages.length === 0) {
                        this.showNoMessages();
                        return;
                    }

                    // Clear existing messages if this is first page
                    if (this.config.currentPage === 1) {
                        this.clearMessages();
                    }

                    // Store current scroll position
                    const prevScrollHeight = this.elements.container.scrollHeight;

                    // Add messages
                    messages.forEach(message => {
                        this.addMessage(message);
                    });

                    // Update pagination state
                    if (messages.length < this.config.messagesPerPage) {
                        this.config.hasMoreMessages = false;
                    } else {
                        this.config.currentPage++;
                    }

                    // Show/hide load more button
                    this.showLoadMoreButton(this.config.hasMoreMessages);

                    // Maintain scroll position when loading more messages
                    if (this.config.currentPage > 1) {
                        const newScrollHeight = this.elements.container.scrollHeight;
                        this.elements.container.scrollTop = newScrollHeight - prevScrollHeight + this.config
                            .scrollPosition;
                    } else {
                        // Scroll to bottom for first page
                        setTimeout(() => this.scrollToBottom(), 100);
                    }
                },

                async loadMoreMessages() {
                    if (this.config.isLoading || !this.config.hasMoreMessages) return;

                    this.config.isLoading = true;

                    // Store current scroll position
                    this.config.scrollPosition = this.elements.container.scrollHeight - this.elements
                        .container.scrollTop;

                    try {
                        const chatId = window.ChatApp.config.currentChatId;
                        const response = await fetch(
                            `/admin/messages/${chatId}?page=${this.config.currentPage + 1}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                        if (!response.ok) throw new Error(`HTTP ${response.status}`);

                        const messages = await response.json();

                        if (Array.isArray(messages) && messages.length > 0) {
                            window.ChatApp.events.emit('messages:loaded', messages);
                        } else {
                            this.config.hasMoreMessages = false;
                            this.showLoadMoreButton(false);
                        }
                    } catch (error) {
                        window.ChatApp.logger.error('Failed to load more messages', error);
                        this.config.isLoading = false;
                    }
                },

                clearMessages() {
                    if (this.elements.messagesContainer) {
                        this.elements.messagesContainer.innerHTML = '';
                    }
                    this.config.messages.clear();
                    this.config.localMessages.clear();
                },

                showNoMessages() {
                    if (this.elements.noMessages) {
                        this.elements.noMessages.classList.remove('hidden');
                    }
                    if (this.elements.messagesContainer) {
                        this.elements.messagesContainer.innerHTML = '';
                    }
                    this.showLoadMoreButton(false);
                },

                addMessage(message) {
                    if (this.config.messages.has(message.id)) return;

                    this.config.messages.set(message.id, message);

                    // Check if we have a local message to replace
                    const localMessage = Array.from(this.config.localMessages.values())
                        .find(msg => msg.sender_id === message.sender_id &&
                            msg.message === message.message &&
                            Math.abs(new Date(msg.created_at) - new Date(message.created_at)) < 5000);

                    if (localMessage) {
                        this.replaceLocalMessageElement(localMessage.id, message);
                    } else {
                        this.appendMessageElement(message);
                    }

                    // Hide no messages placeholder
                    if (this.elements.noMessages) {
                        this.elements.noMessages.classList.add('hidden');
                    }

                    // Auto-scroll to bottom if user is near bottom
                    const scrollThreshold = 100; // pixels from bottom
                    const isNearBottom = this.elements.container.scrollHeight -
                        this.elements.container.scrollTop -
                        this.elements.container.clientHeight < scrollThreshold;

                    if (isNearBottom || this.config.currentPage === 1) {
                        setTimeout(() => this.scrollToBottom(), 50);
                    }
                },

                addLocalMessage(message) {
                    this.config.localMessages.set(message.id, message);
                    this.appendMessageElement(message, true);

                    // Hide no messages placeholder
                    if (this.elements.noMessages) {
                        this.elements.noMessages.classList.add('hidden');
                    }

                    this.scrollToBottom();
                },

                replaceLocalMessage(serverMessage) {
                    // Find and replace the local message
                    const localEntry = Array.from(this.config.localMessages.entries())
                        .find(([id, msg]) => msg.sender_id === serverMessage.sender_id &&
                            Math.abs(new Date(msg.created_at) - new Date(serverMessage.created_at)) < 5000);

                    if (localEntry) {
                        const [localId, localMessage] = localEntry;
                        this.replaceLocalMessageElement(localId, serverMessage);
                        this.config.localMessages.delete(localId);
                    } else {
                        this.addMessage(serverMessage);
                    }
                },

                appendMessageElement(message, isLocal = false) {
                    if (!this.elements.messagesContainer) return;

                    const messageElement = this.createMessageElement(message, isLocal);

                    if (isLocal) {
                        // Add local message at the end
                        this.elements.messagesContainer.appendChild(messageElement);
                        messageElement.classList.add('opacity-80');
                    } else {
                        // Add regular message in chronological order
                        const existingMessages = Array.from(this.elements.messagesContainer.children);

                        if (existingMessages.length === 0) {
                            this.elements.messagesContainer.appendChild(messageElement);
                        } else {
                            let inserted = false;
                            const messageTime = new Date(message.created_at);

                            for (let i = 0; i < existingMessages.length; i++) {
                                const existingMsg = existingMessages[i];
                                const existingTime = new Date(existingMsg.dataset.timestamp || 0);

                                if (messageTime < existingTime) {
                                    this.elements.messagesContainer.insertBefore(messageElement, existingMsg);
                                    inserted = true;
                                    break;
                                }
                            }

                            if (!inserted) {
                                this.elements.messagesContainer.appendChild(messageElement);
                            }
                        }
                    }
                },

                replaceLocalMessageElement(localId, serverMessage) {
                    const messageElement = this.elements.messagesContainer.querySelector(
                        `[data-message-id="${localId}"]`);
                    if (!messageElement) return;

                    messageElement.dataset.messageId = serverMessage.id;
                    messageElement.classList.remove('opacity-80');
                    messageElement.removeAttribute('data-local');

                    // Update content
                    const bubble = messageElement.querySelector('.message-content');
                    if (bubble) {
                        bubble.innerHTML = this.getMessageContent(serverMessage);
                    }

                    // Update timestamp
                    const timestamp = messageElement.querySelector('.message-ts');
                    if (timestamp) {
                        timestamp.textContent = this.formatTime(serverMessage.created_at);
                        timestamp.title = new Date(serverMessage.created_at).toLocaleString();
                    }

                    this.config.messages.set(serverMessage.id, serverMessage);
                },

                createMessageElement(message, isLocal = false) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'message-wrapper group flex flex-col';
                    wrapper.dataset.messageId = message.id;
                    wrapper.dataset.timestamp = new Date(message.created_at).getTime();
                    if (isLocal) wrapper.dataset.local = 'true';

                    const isOwn = message.sender_id === window.ChatApp.config.authUserId;

                    const html = `
                    <div class="message-row flex items-end gap-2">
                        ${isOwn ? this.getOptionsButton(message.id) : ''}
                        <div class="${isOwn ? 'ml-auto' : 'mr-auto'} max-w-[75%] relative flex items-start">
                            <div class="message-content ${isOwn ? 'bg-blue-600 text-white' : 'bg-white'} 
                                 px-4 py-2 rounded-xl border shadow-sm relative ${isLocal ? 'message-sending' : ''}">
                                ${this.getMessageContent(message)}
                                ${isLocal ? '<div class="absolute -right-6 -top-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div></div>' : ''}
                            </div>
                        </div>
                        ${!isOwn ? this.getOptionsButton(message.id) : ''}
                    </div>
                    <div class="message-ts text-xs text-gray-400 mt-1" title="${new Date(message.created_at || new Date()).toLocaleString()}">
                        ${this.formatTime(message.created_at || new Date())}
                        ${isLocal ? ' · Sending...' : ''}
                    </div>
                `;

                    wrapper.innerHTML = html;
                    return wrapper;
                },

                getMessageContent(message) {
                    let content = '';

                    if (message.localPreview || message.file_path) {
                        const src = message.localPreview ? message.localPreview :
                            `/storage/${message.file_path}`;
                        if (message.localPreview || /\.(jpe?g|png|gif|webp|svg)$/i.test(src)) {
                            // Add lazy loading for images
                            content +=
                                `<div class="mt-2"><img src="${src}" loading="lazy" class="rounded border shadow max-h-60 object-cover" /></div>`;
                        } else {
                            content +=
                                `<div class="mt-2"><a href="${src}" target="_blank" class="text-blue-600 text-sm">📎 ${this.getFileIcon(message.file_path)} ${this.getFileName(message.file_path)}</a></div>`;
                        }
                    }

                    if (message.message) {
                        content +=
                            `<div class="message-text">${this.escapeHtml(message.message).replace(/\n/g, '<br>')}</div>`;
                    }

                    return content;
                },

                getFileIcon(filePath) {
                    if (!filePath) return '📎';

                    const ext = filePath.split('.').pop().toLowerCase();
                    const icons = {
                        pdf: '📕',
                        doc: '📘',
                        docx: '📘',
                        txt: '📄',
                        xls: '📊',
                        xlsx: '📊',
                        zip: '🗜️',
                        rar: '🗜️'
                    };

                    return icons[ext] || '📎';
                },

                getFileName(filePath) {
                    if (!filePath) return 'File';
                    return filePath.split('/').pop();
                },

                getOptionsButton(messageId) {
                    return `
                    <button class="message-options-btn p-1 rounded-full hover:bg-gray-200 opacity-0 group-hover:opacity-100 transition-opacity" 
                            title="Message options"
                            data-menu-open="message"
                            data-target-id="${messageId}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                  d="M12 5v.01M12 12v.01M12 19v.01"/>
                        </svg>
                    </button>
                `;
                },

                scrollToBottom() {
                    if (this.elements.container) {
                        setTimeout(() => {
                            this.elements.container.scrollTop = this.elements.container.scrollHeight;
                        }, 100);
                    }
                },

                escapeHtml(text) {
                    if (!text) return '';
                    return text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                formatTime(date) {
                    try {
                        const messageDate = new Date(date);
                        const now = new Date();
                        const diffMs = now - messageDate;
                        const diffMins = Math.floor(diffMs / 60000);
                        const diffHours = Math.floor(diffMs / 3600000);
                        const diffDays = Math.floor(diffMs / 86400000);

                        if (diffMins < 1) {
                            return 'Just now';
                        } else if (diffMins < 60) {
                            return `${diffMins}m ago`;
                        } else if (diffHours < 24) {
                            return `${diffHours}h ago`;
                        } else if (diffDays < 7) {
                            return `${diffDays}d ago`;
                        } else {
                            return messageDate.toLocaleDateString();
                        }
                    } catch {
                        return '';
                    }
                }
            };

            MessagesList.init();
        });
    </script>
@endpush
