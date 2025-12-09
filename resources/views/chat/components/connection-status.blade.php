<div class="mb-4 p-3 border rounded bg-blue-50" id="connection-status-container">
    <div class="flex items-center mb-2">
        <span id="connection-status"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
            <span id="status-dot" class="w-2 h-2 rounded-full mr-2 bg-gray-400"></span>
            <span id="status-text">Connecting...</span>
        </span>
        <span class="ml-3 text-sm" id="socket-id">Socket ID: —</span>
    </div>
    <div id="typing-indicator" class="text-sm text-gray-500"></div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ConnectionStatus = {
                init() {
                    this.elements = {
                        statusText: document.getElementById('status-text'),
                        statusDot: document.getElementById('status-dot'),
                        socketId: document.getElementById('socket-id'),
                        typingIndicator: document.getElementById('typing-indicator')
                    };

                    this.setupEventListeners();
                    window.ChatApp.logger.log('ConnectionStatus initialized');
                },

                setupEventListeners() {
                    window.ChatApp.events.on('connection:connected', (data) => this.handleConnected(data));
                    window.ChatApp.events.on('connection:error', (error) => this.handleError(error));
                    window.ChatApp.events.on('typing:start', (userId) => this.handleTyping(userId));
                    window.ChatApp.events.on('typing:stop', (userId) => this.handleTypingStop(userId));
                },

                handleConnected(socketId) {
                    if (this.elements.statusText) {
                        this.elements.statusText.textContent = 'Connected';
                    }
                    if (this.elements.statusDot) {
                        this.elements.statusDot.className = 'w-2 h-2 rounded-full mr-2 bg-green-500';
                    }
                    if (this.elements.socketId) {
                        this.elements.socketId.textContent = `Socket ID: ${socketId}`;
                    }
                },

                handleError(error) {
                    if (this.elements.statusText) {
                        this.elements.statusText.textContent = 'Connection error';
                    }
                    if (this.elements.statusDot) {
                        this.elements.statusDot.className = 'w-2 h-2 rounded-full mr-2 bg-red-500';
                    }
                    window.ChatApp.logger.error('Connection error', error);
                },

                handleTyping(userId) {
                    if (userId === window.ChatApp.config.currentChatId && this.elements.typingIndicator) {
                        this.elements.typingIndicator.textContent = 'User is typing...';
                    }
                },

                handleTypingStop(userId) {
                    if (userId === window.ChatApp.config.currentChatId && this.elements.typingIndicator) {
                        this.elements.typingIndicator.textContent = '';
                    }
                }
            };

            ConnectionStatus.init();
        });
    </script>
@endpush
