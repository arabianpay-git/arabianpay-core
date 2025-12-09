<form id="chat-form" class="p-4 border-t bg-white flex items-end gap-3 rounded-b-lg" enctype="multipart/form-data">
    <div class="flex items-center gap-2">
        <label for="file" class="cursor-pointer p-2 rounded hover:bg-gray-100">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    d="M12 5v14M5 12h14" />
            </svg>
        </label>
        <input id="file" name="file" type="file" class="hidden"
            accept="image/*,application/pdf,.doc,.docx,.txt" />

        <!-- preview area -->
        <div id="file-preview" class="hidden items-center gap-2"></div>
    </div>

    <textarea id="message" name="message" placeholder="Type a message"
        class="flex-1 border p-2 rounded resize-none overflow-auto text-sm" style="height: 40px; max-height: 120px;"></textarea>

    <button type="submit" id="send-btn" class="btn btn-success flex items-center gap-2">
        <i class="ki-filled ki-paper-plane"></i>
        <span>Send</span>
    </button>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('message');

            textarea.addEventListener('input', () => {
                textarea.style.height = '40px'; // reset to default height
                textarea.style.height = Math.min(textarea.scrollHeight, 120) +
                    'px'; // grow until max height
            });

            const ChatForm = {
                config: {
                    typingTimeout: null,
                    currentFile: null,
                    previewDataUrl: null
                },

                init() {
                    this.elements = {
                        form: document.getElementById('chat-form'),
                        messageInput: document.getElementById('message'),
                        sendBtn: document.getElementById('send-btn'),
                        fileInput: document.getElementById('file'),
                        filePreview: document.getElementById('file-preview')
                    };

                    // Get logger from global scope if available
                    this.logger = window.ChatApp?.logger || this.createFallbackLogger();

                    this.setupEventListeners();
                    this.setupTypingListener();

                    this.logger.log('ChatForm initialized');
                },

                createFallbackLogger() {
                    // Create a simple logger that logs to both console and debug panel
                    return {
                        log: (msg, data = null) => {
                            console.log(msg, data || '');
                            this.logToDebugPanel(msg, data);
                        },
                        error: (msg, data = null) => {
                            console.error(msg, data || '');
                            this.logToDebugPanel(`ERROR: ${msg}`, data);
                        }
                    };
                },

                logToDebugPanel(msg, data = null) {
                    try {
                        const logEl = document.getElementById('dbg-log');
                        const logCount = document.getElementById('log-count');

                        if (!logEl || !logCount) {
                            console.log('Debug panel elements not found');
                            return;
                        }

                        const t = new Date().toLocaleTimeString();
                        const currentLogs = logEl.textContent;
                        const newEntry = `[${t}] ${msg}${data ? '\n  ' + JSON.stringify(data, null, 2) : ''}`;

                        // Update log element - prepend new entry
                        logEl.textContent = newEntry + (currentLogs ? '\n' + currentLogs : '');

                        // Update log count
                        const currentCount = parseInt(logCount.textContent) || 0;
                        logCount.textContent = `${currentCount + 1} entries`;

                    } catch (e) {
                        console.error('Failed to log to debug panel:', e);
                    }
                },

                setupEventListeners() {
                    // Form submission
                    if (this.elements.form) {
                        this.elements.form.addEventListener('submit', (e) => this.handleSubmit(e));
                    }

                    // Typing indicators
                    if (this.elements.messageInput) {
                        this.elements.messageInput.addEventListener('input', () => this.handleTyping());
                        this.elements.messageInput.addEventListener('focus', () => this.handleFocus());
                        this.elements.messageInput.addEventListener('blur', () => this.handleBlur());
                    }

                    // File input
                    if (this.elements.fileInput) {
                        this.elements.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
                    }
                },

                // In ChatForm component, update setupTypingListener:
                setupTypingListener() {
                    // Wait for Echo to be ready
                    if (window.ChatApp?.events) {
                        window.ChatApp.events.on('echo:ready', () => {
                            console.log('ChatForm: Echo is ready, setting up typing listener');
                            this.setupEchoTypingListener();
                        });
                    } else {
                        // Fallback: try immediately
                        setTimeout(() => {
                            if (typeof window.Echo !== 'undefined') {
                                this.setupEchoTypingListener();
                            }
                        }, 1000);
                    }
                },

                setupEchoTypingListener() {
                    const authUserId = window.ChatApp?.config?.authUserId || {{ Auth::id() }};
                    const channelName = `chat.${authUserId}`;

                    console.log('ChatForm: Setting up Echo typing listener on channel', channelName);

                    try {
                        window.Echo.private(channelName)
                            .listen('TypingEvent', (event) => {
                                console.log('ChatForm: TypingEvent received', event);

                                // Emit event to global event bus
                                if (window.ChatApp?.events?.emit) {
                                    window.ChatApp.events.emit('typing:start', event.senderId);
                                }
                            })
                            .listen('TypingStopped', (event) => {
                                console.log('ChatForm: TypingStopped received', event);

                                // Emit event to global event bus
                                if (window.ChatApp?.events?.emit) {
                                    window.ChatApp.events.emit('typing:stop', event.senderId);
                                }
                            })
                            .error((error) => {
                                console.error('ChatForm: Typing channel error', error);
                            });

                        console.log('ChatForm: Echo typing listener set up successfully');
                    } catch (error) {
                        console.error('ChatForm: Failed to setup typing listener', error);
                    }
                },

                updateTypingIndicator(userId, isTyping) {
                    const currentChatId = window.ChatApp?.config?.currentChatId || {{ $otherUser->id }};

                    if (userId == currentChatId) {
                        const headerPresence = document.getElementById('header-presence');
                        if (headerPresence) {
                            if (isTyping) {
                                headerPresence.textContent = 'Typing...';
                                headerPresence.className = 'text-xs text-blue-500 italic';
                            } else {
                                // Reset to online/offline status
                                const isOnline = window.ChatApp?.onlineUsers?.has(userId) || false;
                                headerPresence.textContent = isOnline ? 'Online' : 'Offline';
                                headerPresence.className = 'text-xs text-gray-500';
                            }
                        }
                    }

                    // Also update sidebar if needed
                    if (window.ChatApp?.refreshSidebarOnlineIndicators) {
                        window.ChatApp.refreshSidebarOnlineIndicators();
                    }
                },

                async handleSubmit(e) {
                    e.preventDefault();

                    const message = this.elements.messageInput?.value.trim();
                    const file = this.config.currentFile;

                    // Allow sending even if only file is selected
                    if (!message && !file) {
                        this.logger.log('No message or file to send');
                        return;
                    }

                    this.logger.log('Sending message', {
                        hasMessage: !!message,
                        hasFile: !!file,
                        receiver: window.ChatApp?.config?.currentChatId || {{ $otherUser->id }}
                    });

                    // Create local message preview
                    const localId = 'local-' + Date.now();
                    const localMessage = {
                        id: localId,
                        sender_id: window.ChatApp?.config?.authUserId || {{ Auth::id() }},
                        message: message,
                        created_at: new Date(),
                        isOwn: true,
                        localPreview: this.config.previewDataUrl,
                        fileName: file ? file.name : null,
                        fileType: file ? file.type : null
                    };

                    // Emit event to display local message
                    if (window.ChatApp?.events?.emit) {
                        window.ChatApp.events.emit('message:local', localMessage);
                    }

                    // Send stop typing indicator immediately
                    this.sendTypingIndicator(false);

                    // Send to server
                    try {
                        const formData = new FormData();
                        formData.append('receiver_id', window.ChatApp?.config?.currentChatId ||
                            {{ $otherUser->id }});
                        if (message) formData.append('message', message);
                        if (file) formData.append('file', file);

                        const response = await fetch('/admin/messages', {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });

                        if (!response.ok) {
                            const errorText = await response.text();
                            throw new Error(`HTTP ${response.status}: ${errorText}`);
                        }

                        const savedMessage = await response.json();

                        this.logger.log('Message saved successfully', {
                            messageId: savedMessage.id
                        });

                        // Clear form after successful send
                        this.elements.messageInput.value = '';
                        this.clearFile();

                        // Reset textarea height
                        this.elements.messageInput.style.height = '40px';

                        if (window.ChatApp?.events?.emit) {
                            window.ChatApp.events.emit('message:sent', savedMessage);
                        }

                    } catch (error) {
                        this.logger.error('Failed to send message', error.message || error);
                        alert('Failed to send message');
                    }
                },

                handleTyping() {
                    clearTimeout(this.config.typingTimeout);

                    // Send typing indicator
                    this.sendTypingIndicator(true);

                    // Set timeout to stop typing indicator after 1 second of inactivity
                    this.config.typingTimeout = setTimeout(() => {
                        this.sendTypingIndicator(false);
                    }, 1000);
                },

                handleFocus() {
                    this.sendTypingIndicator(true);
                },

                handleBlur() {
                    clearTimeout(this.config.typingTimeout);
                    this.sendTypingIndicator(false);
                },

                async sendTypingIndicator(isTyping) {
                    try {
                        const endpoint = isTyping ? '/admin/typing' : '/admin/typing/stop';
                        const receiverId = window.ChatApp?.config?.currentChatId || {{ $otherUser->id }};

                        this.logger.log(`Sending typing indicator: ${isTyping ? 'typing' : 'stopped'}`, {
                            receiverId: receiverId
                        });

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            credentials: 'include',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                receiver_id: receiverId
                            })
                        });

                        if (!response.ok) {
                            const errorText = await response.text();
                            throw new Error(`HTTP ${response.status}: ${errorText}`);
                        }

                        this.logger.log(`Typing indicator ${isTyping ? 'sent' : 'stopped'} successfully`);

                    } catch (error) {
                        this.logger.error('Failed to send typing indicator', error.message || error);
                    }
                },

                handleFileSelect(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.config.currentFile = file;
                    this.logger.log('File selected', {
                        fileName: file.name,
                        fileType: file.type,
                        fileSize: this.formatFileSize(file.size)
                    });

                    // Create preview for images
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            this.config.previewDataUrl = event.target.result;
                            this.renderFilePreview(file, event.target.result);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        this.renderFilePreview(file);
                    }
                },

                renderFilePreview(file, dataUrl = null) {
                    if (!this.elements.filePreview) return;

                    this.elements.filePreview.innerHTML = '';
                    this.elements.filePreview.classList.remove('hidden');

                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex items-center gap-2 bg-gray-50 border rounded-lg p-2 shadow-sm';

                    if (dataUrl) {
                        // Image preview with fixed 60px height
                        const imgWrapper = document.createElement('div');
                        imgWrapper.className = 'relative';

                        const img = document.createElement('img');
                        img.src = dataUrl;
                        img.className = 'h-[60px] w-auto rounded object-cover';
                        img.style.maxWidth = '100px';
                        img.alt = file.name;
                        imgWrapper.appendChild(img);

                        // Remove button for image (positioned on top right of image)
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className =
                            'absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition-colors';
                        removeBtn.innerHTML = `
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        `;
                        removeBtn.title = 'Remove image';
                        removeBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            this.clearFile();
                        });

                        imgWrapper.appendChild(removeBtn);
                        wrapper.appendChild(imgWrapper);

                        // File info below image
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex flex-col';
                        fileInfo.innerHTML = `
                            <span class="text-xs font-medium truncate max-w-[100px]">${this.escapeHtml(file.name)}</span>
                            <span class="text-xs text-gray-500">${this.formatFileSize(file.size)}</span>
                        `;
                        wrapper.appendChild(fileInfo);

                    } else {
                        // Document file preview
                        const fileIcon = document.createElement('div');
                        fileIcon.className = 'flex items-center justify-center w-10 h-10 bg-blue-100 rounded';
                        fileIcon.innerHTML = `
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        `;

                        wrapper.appendChild(fileIcon);

                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex flex-col';
                        fileInfo.innerHTML = `
                            <span class="text-xs font-medium truncate max-w-[120px]">${this.escapeHtml(file.name)}</span>
                            <span class="text-xs text-gray-500">${this.formatFileSize(file.size)}</span>
                        `;
                        wrapper.appendChild(fileInfo);

                        // Remove button for document
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'ml-2 p-1 text-gray-400 hover:text-red-500 transition-colors';
                        removeBtn.innerHTML = `
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        `;
                        removeBtn.title = 'Remove file';
                        removeBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            this.clearFile();
                        });
                        wrapper.appendChild(removeBtn);
                    }

                    this.elements.filePreview.appendChild(wrapper);
                },

                clearFile() {
                    this.logger.log('Clearing file selection');
                    this.config.currentFile = null;
                    this.config.previewDataUrl = null;
                    if (this.elements.fileInput) this.elements.fileInput.value = '';
                    if (this.elements.filePreview) {
                        this.elements.filePreview.innerHTML = '';
                        this.elements.filePreview.classList.add('hidden');
                    }
                },

                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },

                escapeHtml(text) {
                    if (!text && text !== 0) return '';
                    return String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }
            };

            // Initialize when DOM is loaded
            setTimeout(() => {
                ChatForm.init();
            }, 100);
        });
    </script>
@endpush
