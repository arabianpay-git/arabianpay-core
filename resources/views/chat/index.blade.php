@extends('layouts.base')
@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush
@section('content')
    {{ Auth::user()->name }}
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Chat with {{ $otherUser->name }}</h1>

        @include('chat.components.connection-status')

        <div class="grid grid-cols-12 gap-4">
            @include('chat.components.sidebar')
            @include('chat.components.main-chat')
        </div>

        @include('chat.components.debug-panel')
    </div>

    @include('chat.components.context-menus')
    @include('chat.components.realtime-service')
@endsection

@push('scripts')
    <script>
        // Create global event bus if it doesn't exist
        window.ChatApp = window.ChatApp || {};
        window.ChatApp.events = (function() {
            const listeners = {};

            return {
                on: function(event, callback) {
                    if (!listeners[event]) listeners[event] = [];
                    listeners[event].push(callback);
                },
                emit: function(event, data) {
                    if (listeners[event]) {
                        listeners[event].forEach(callback => callback(data));
                    }
                }
            };
        })();

        // Add config
        window.ChatApp.config = {
            authUserId: {{ Auth::id() }},
            currentChatId: {{ $otherUser->id }},
            csrfToken: '{{ csrf_token() }}'
        };

        // Add online users set
        window.ChatApp.onlineUsers = new Set();

        // Global logger
        window.ChatApp.logger = {
            logs: [],
            log: function(message, data = null) {
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = {
                    timestamp,
                    message,
                    data
                };
                this.logs.unshift(logEntry);
                if (this.logs.length > 200) this.logs.pop();

                // Emit to debug panel
                window.ChatApp.events.emit('log', logEntry);
                console.log(`[Chat] ${message}`, data || '');
            },
            error: function(message, error) {
                this.log(`ERROR: ${message}`, error?.message || error);
                console.error(`[Chat Error] ${message}`, error);
            }
        };

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            window.ChatApp.logger.log('Chat application initializing...');
        });
    </script>
@endpush
