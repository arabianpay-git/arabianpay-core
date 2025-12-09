@extends('layouts.base')
@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush
@section('content')
    {{ Auth::user()->name }}
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Chat with {{ $otherUser->first_name }} {{ $otherUser->last_name }}</h1>

        <!-- Connection Status -->
        <div class="mb-4 p-3 border rounded bg-blue-50">
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

        <div class="grid grid-cols-12 gap-4">
            <!-- Sidebar (chat list) -->
            <aside class="col-span-12 lg:col-span-4 bg-white border rounded shadow-sm overflow-hidden">
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
                        <input id="search-users" class="w-full pl-10 pr-4 py-2 border rounded-xl text-sm"
                            style="padding-left: 2rem" placeholder="Search chats..." />
                    </div>
                </div>

                <div id="chat-list" class="overflow-y-auto" style="max-height:520px;">
                    <!-- sidebar items injected via JS -->
                </div>
            </aside>

            <!-- Main chat column -->
            <main class="col-span-12 lg:col-span-8 flex flex-col bg-gray-50 rounded-lg shadow-sm">
                <div class="p-4 border-b bg-white flex items-center justify-between rounded-t-lg">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <img id="header-avatar"
                                src="{{ $otherUser->avatar ?? "https://ui-avatars.com/api/?name={$otherUser->first_name}+{$otherUser->last_name}&color=7F9CF5&background=EBF4FF" }}"
                                class="w-10 h-10 rounded-full object-cover"
                                alt="{{ $otherUser->first_name }} {{ $otherUser->last_name }}" />
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-gray-400 rounded-full ring-1 ring-white"
                                id="header-online-dot"></span>
                        </div>
                        <div>
                            <div id="header-name" class="font-semibold">{{ $otherUser->first_name }}
                                {{ $otherUser->last_name }}</div>
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

                <!-- messages area - Fixed height container -->
                <div id="chat-box" class="flex-1 overflow-hidden bg-gradient-to-b from-gray-50 to-gray-100">
                    <div id="messages-list" class="p-6 space-y-4 overflow-y-auto h-full" style="max-height: 500px;">
                        <div class="text-center text-gray-500 py-8 no-messages">No messages yet</div>
                    </div>
                </div>

                <!-- compose -->
                <form id="chat-form" class="p-4 border-t bg-white flex items-end gap-3 rounded-b-lg"
                    enctype="multipart/form-data">
                    <div class="flex items-center gap-2">
                        <label for="file" class="cursor-pointer p-2 rounded hover:bg-gray-100">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 5v14M5 12h14" />
                            </svg>
                        </label>
                        <input id="file" name="file" type="file" class="hidden"
                            accept="image/*,application/pdf" />

                        <!-- preview area -->
                        <div id="file-preview" class="hidden items-center gap-2 bg-gray-100 p-2 rounded-lg"></div>
                    </div>

                    <input id="message" name="message" type="text" placeholder="Type a message"
                        class="flex-1 border p-2 rounded" autocomplete="off" />

                    <button type="submit" id="send-btn"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Send</button>
                </form>
            </main>
        </div>

        <!-- Debug Panel -->
        <div id="debug-panel" class="mt-6 p-4 border rounded-lg bg-gray-50 shadow-sm">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-bold text-gray-800">Debug Console</h2>
                <div class="flex space-x-2">
                    <button type="button" id="test-connection"
                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-medium">Test
                        Connection</button>
                    <button type="button" id="test-presence"
                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm font-medium">
                        Test Presence
                    </button>
                    <button type="button" id="clear-logs"
                        class="px-3 py-1.5 border border-gray-300 hover:bg-gray-100 rounded text-sm">
                        Clear Logs
                    </button>
                </div>
            </div>

            <div class="border rounded bg-white">
                <div class="flex justify-between items-center p-2 border-b bg-gray-50">
                    <div class="text-sm font-medium text-gray-700">Event Log</div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500" id="log-count">0 entries</span>
                    </div>
                </div>
                <pre id="dbg-log"
                    class="p-3 text-xs font-mono overflow-auto h-64 bg-gray-900 text-gray-100 whitespace-pre-wrap break-all"></pre>
            </div>
        </div>
    </div>

    <!-- Context Menu for Messages -->
    <div class="context-menu fixed hidden z-50 bg-white border rounded-lg shadow-lg p-2 min-w-[120px]" data-menu="message"
        aria-hidden="true">
        <button class="menu-item w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm text-red-600"
            data-action="delete-message">
            Delete
        </button>
        <button class="menu-item w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-action="copy-message">
            Copy Text
        </button>
    </div>

    <!-- Context Menu for Header -->
    <div class="context-menu fixed hidden z-50 bg-white border rounded-lg shadow-lg p-2 min-w-[120px]" data-menu="header"
        aria-hidden="true">
        <button class="menu-item w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-action="clear-chat">
            Clear Chat
        </button>
        <button class="menu-item w-full text-left px-3 py-2 hover:bg-gray-100 rounded text-sm" data-action="block-user">
            Block User
        </button>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /* -------------------------
               Config
               ------------------------- */
            const CURRENT_CHAT_ID = {{ $otherUser->id }};
            const AUTH_USER_ID = {{ Auth::id() }};

            /* ---------- DOM helpers ---------- */
            const DOM = (() => {
                const qs = id => document.querySelector(id);
                return {
                    chatList: qs('#chat-list'),
                    messagesList: qs('#messages-list'),
                    chatForm: qs('#chat-form'),
                    messageInput: qs('#message'),
                    fileInput: qs('#file'),
                    filePreview: qs('#file-preview'),
                    sendBtn: qs('#send-btn'),
                    headerPresence: qs('#header-presence'),
                    headerAvatar: qs('#header-avatar'),
                    headerOnlineDot: qs('#header-online-dot'),
                    logEl: qs('#dbg-log'),
                    logCount: qs('#log-count'),
                    searchInput: qs('#search-users'),
                };
            })();

            /* ---------- Logger ---------- */
            const Logger = (() => {
                let logs = [];
                return {
                    log: (msg, data = null) => {
                        const t = new Date().toLocaleTimeString();
                        logs.unshift({
                            t,
                            msg,
                            data
                        });
                        if (logs.length > 200) logs.pop();
                        DOM.logEl.textContent = logs.map(l =>
                                `[${l.t}] ${l.msg}${l.data ? '\n  ' + JSON.stringify(l.data, null, 2) : ''}`
                            )
                            .join('\n');
                        DOM.logCount.textContent = `${logs.length} entries`;
                        console.log(msg, data || '');
                    },
                    clear: () => {
                        logs = [];
                        DOM.logEl.textContent = '';
                        DOM.logCount.textContent = '0 entries';
                    }
                }
            })();

            /* ---------- Presence & typing state ---------- */
            const onlineUsers = new Set();
            const typingUsers = new Map(); // userId -> timeoutId
            let userDataCache = new Map(); // Cache user data for sidebar

            function isUserOnline(userId) {
                return onlineUsers.has(Number(userId));
            }

            function updateHeaderPresenceUI() {
                const isOnline = isUserOnline(CURRENT_CHAT_ID);
                const isTyping = typingUsers.has(CURRENT_CHAT_ID);

                if (isTyping) {
                    DOM.headerPresence.textContent = 'Typing...';
                    DOM.headerPresence.className = 'text-xs text-blue-500 italic';
                } else {
                    DOM.headerPresence.textContent = isOnline ? 'Online' : 'Offline';
                    DOM.headerPresence.className = 'text-xs text-gray-500';
                }

                DOM.headerOnlineDot.className =
                    `absolute bottom-0 right-0 w-2.5 h-2.5 ${isOnline ? 'bg-green-500' : 'bg-gray-400'} rounded-full ring-1 ring-white`;
            }

            function setUserTyping(userId, isTyping) {
                Logger.log(`Setting typing for user ${userId}: ${isTyping}`);
                if (isTyping) {
                    // Clear existing timeout
                    if (typingUsers.has(userId)) {
                        clearTimeout(typingUsers.get(userId));
                    }

                    // Set new timeout to clear typing after 3 seconds
                    const timeoutId = setTimeout(() => {
                        Logger.log(`Auto-clearing typing for user ${userId}`);
                        typingUsers.delete(userId);
                        updateHeaderPresenceUI();
                        refreshSidebarOnlineIndicators();
                    }, 3000);

                    typingUsers.set(userId, timeoutId);
                } else {
                    if (typingUsers.has(userId)) {
                        clearTimeout(typingUsers.get(userId));
                        typingUsers.delete(userId);
                    }
                }

                updateHeaderPresenceUI();
                refreshSidebarOnlineIndicators();
            }

            function scrollToBottom() {
                const messagesContainer = document.querySelector('#messages-list');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }

            function refreshSidebarOnlineIndicators() {
                const items = DOM.chatList.querySelectorAll('[data-chat-id]');
                items.forEach(item => {
                    const id = Number(item.dataset.chatId);
                    const dot = item.querySelector('span.absolute.bottom-0.right-0.w-3.h-3');
                    if (dot) {
                        dot.className =
                            `absolute bottom-0 right-0 w-3 h-3 ${isUserOnline(id) ? 'bg-green-500' : 'bg-gray-400'} rounded-full ring-2 ring-white`;
                    }

                    // Update typing indicator in sidebar
                    const lastMessageEl = item.querySelector('.last-message-text');
                    const typingEl = item.querySelector('.typing-indicator');

                    if (lastMessageEl && typingEl) {
                        if (typingUsers.has(id)) {
                            lastMessageEl.classList.add('hidden');
                            typingEl.classList.remove('hidden');
                            typingEl.textContent = 'typing...';
                        } else {
                            lastMessageEl.classList.remove('hidden');
                            typingEl.classList.add('hidden');
                        }
                    }
                });
            }

            /* -------------------------
               FilePreview
               ------------------------- */
            const FilePreview = (() => {
                let currentFile = null;
                let previewDataUrl = null;

                function isImage(f) {
                    return f && f.type && f.type.startsWith('image/');
                }

                function renderDataUrl(dataUrl, file) {
                    DOM.filePreview.innerHTML = '';
                    if (!file) {
                        DOM.filePreview.classList.add('hidden');
                        previewDataUrl = null;
                        currentFile = null;
                        return;
                    }
                    currentFile = file;
                    previewDataUrl = dataUrl;
                    const wrap = document.createElement('div');
                    wrap.className = 'flex items-center gap-2';
                    if (isImage(file)) {
                        const img = document.createElement('img');
                        img.className = 'h-14 rounded object-cover';
                        img.src = dataUrl;
                        wrap.appendChild(img);
                    } else {
                        const el = document.createElement('div');
                        el.className = 'px-3 py-2 bg-white border rounded text-sm';
                        el.textContent = file.name;
                        wrap.appendChild(el);
                    }
                    const remove = document.createElement('button');
                    remove.className = 'text-xs px-2 py-1 bg-red-600 text-white rounded';
                    remove.textContent = 'Remove';
                    remove.addEventListener('click', e => {
                        e.preventDefault();
                        DOM.fileInput.value = '';
                        renderDataUrl(null, null);
                    });
                    wrap.appendChild(remove);
                    DOM.filePreview.appendChild(wrap);
                    DOM.filePreview.classList.remove('hidden');
                }
                DOM.fileInput.addEventListener('change', e => {
                    const file = e.target.files[0] || null;
                    if (!file) {
                        renderDataUrl(null, null);
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = ev => renderDataUrl(ev.target.result, file);
                    reader.readAsDataURL(file);
                });
                return {
                    getFile: () => currentFile,
                    getPreview: () => previewDataUrl,
                    clear: () => {
                        DOM.fileInput.value = '';
                        renderDataUrl(null, null);
                    }
                };
            })();

            /* -------------------------
               Messages module with auto-scroll
               ------------------------- */
            const Messages = (() => {
                const list = DOM.messagesList;
                const localMap = new Map();

                function removePlaceholder() {
                    const ph = list.querySelector('.no-messages');
                    if (ph) ph.remove();
                }

                function createMessageElement(msg) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'message-wrapper group flex flex-col';
                    wrapper.dataset.messageId = msg.id;
                    const row = document.createElement('div');
                    row.className = 'message-row flex items-end gap-2';
                    const bubbleWrap = document.createElement('div');
                    bubbleWrap.className = (msg.isOwn ? 'ml-auto' : 'mr-auto') +
                        ' max-w-[75%] relative flex items-start';
                    const bubble = document.createElement('div');
                    bubble.className = (msg.isOwn ? 'message-content bg-blue-600 text-white' :
                            'message-content bg-white') +
                        ' px-4 py-2 rounded-xl border shadow-sm';
                    if (msg.message) {
                        const p = document.createElement('div');
                        p.innerHTML = escapeHtml(msg.message).replace(/\n/g, '<br>');
                        bubble.appendChild(p);
                    }
                    if (msg.localPreview || msg.file_path) {
                        const wrap = document.createElement('div');
                        wrap.className = 'mt-2';
                        const src = msg.localPreview ? msg.localPreview : `/storage/${msg.file_path}`;
                        if (msg.localPreview || /\.(jpe?g|png|gif|webp|svg)$/i.test(src)) {
                            const img = document.createElement('img');
                            img.src = src;
                            img.className = 'rounded border shadow max-h-60 object-cover';
                            wrap.appendChild(img);
                        } else {
                            const a = document.createElement('a');
                            a.href = src;
                            a.target = '_blank';
                            a.className = 'text-blue-600 text-sm';
                            a.textContent = '📎 File';
                            wrap.appendChild(a);
                        }
                        bubble.appendChild(wrap);
                    }
                    bubbleWrap.appendChild(bubble);
                    const btn = document.createElement('button');
                    btn.className = 'message-options-btn p-1 rounded-full hover:bg-gray-200';
                    btn.title = 'Message options';
                    btn.dataset.menuOpen = 'message';
                    btn.dataset.targetId = msg.id;
                    btn.innerHTML =
                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>';
                    if (msg.isOwn) {
                        row.appendChild(btn);
                        row.appendChild(bubbleWrap);
                    } else {
                        row.appendChild(bubbleWrap);
                        row.appendChild(btn);
                    }
                    const ts = document.createElement('div');
                    ts.className = 'message-ts text-xs text-gray-400 mt-1';
                    ts.textContent = formatTime(msg.created_at || new Date());
                    wrapper.appendChild(row);
                    wrapper.appendChild(ts);
                    return {
                        wrapper,
                        bubble
                    };
                }

                function formatTime(d) {
                    try {
                        return new Date(d).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } catch {
                        return '';
                    }
                }

                function escapeHtml(s) {
                    return s ? s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(
                        /"/g, '&quot;').replace(/'/g, '&#039;') : '';
                }

                function append(msg) {
                    if (msg.id && document.querySelector(`[data-message-id="${msg.id}"]`)) return null;
                    removePlaceholder();
                    const {
                        wrapper
                    } = createMessageElement(msg);
                    if (String(msg.id).startsWith('local-')) {
                        wrapper.classList.add('opacity-80');
                        wrapper.dataset.local = '1';
                        localMap.set(msg.id, wrapper);
                    }
                    list.appendChild(wrapper);

                    // Scroll to bottom after adding message
                    setTimeout(() => {
                        scrollToBottom();
                    }, 50);

                    return wrapper;
                }

                function replaceLocal(localId, serverMsg) {
                    const el = localMap.get(localId);
                    if (!el) {
                        append(serverMsg);
                        return;
                    }
                    el.dataset.messageId = serverMsg.id;
                    el.classList.remove('opacity-80');
                    el.removeAttribute('data-local');
                    const bubble = el.querySelector('.message-content');
                    bubble.innerHTML = '';
                    if (serverMsg.message) {
                        const p = document.createElement('div');
                        p.innerHTML = escapeHtml(serverMsg.message).replace(/\n/g, '<br>');
                        bubble.appendChild(p);
                    }
                    const imgEl = el.querySelector('img');
                    if (serverMsg.file_path) {
                        const src = `/storage/${serverMsg.file_path}`;
                        if (imgEl) imgEl.src = src;
                        else {
                            const wrap = document.createElement('div');
                            wrap.className = 'mt-2';
                            const a = document.createElement('a');
                            a.href = src;
                            a.target = '_blank';
                            a.className = 'text-blue-600 text-sm';
                            a.textContent = '📎 File';
                            wrap.appendChild(a);
                            bubble.appendChild(wrap);
                        }
                    }
                    localMap.delete(localId);

                    // Scroll to bottom after replacing
                    setTimeout(() => {
                        scrollToBottom();
                    }, 50);
                }

                // Initial scroll to bottom
                setTimeout(() => {
                    scrollToBottom();
                }, 100);

                return {
                    append,
                    replaceLocal
                };
            })();

            /* -------------------------
               Sidebar module with unread count and typing indicator
               ------------------------- */
            const Sidebar = (() => {
                let debounceTimer = null;

                async function refresh(query = '') {
                    try {
                        const url = `/admin/chat-users?search=${encodeURIComponent(query)}`;
                        const res = await fetch(url, {
                            credentials: 'include',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const users = await res.json();

                        // Update cache
                        users.forEach(u => userDataCache.set(u.id, u));

                        render(users);
                        refreshSidebarOnlineIndicators();

                        Logger.log('Sidebar refreshed', {
                            userCount: users.length,
                            onlineUsers: Array.from(onlineUsers),
                            typingUsers: Array.from(typingUsers.keys())
                        });

                    } catch (err) {
                        Logger.log('Sidebar refresh failed', err?.message || err);
                    }
                }

                function escapeHtml(s) {
                    return s ? s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(
                        /"/g, '&quot;').replace(/'/g, '&#039;') : '';
                }

                function updateUnreadCount(userId, increment = true) {
                    const item = DOM.chatList.querySelector(`[data-chat-id="${userId}"]`);
                    if (!item) return;

                    const badge = item.querySelector('.unread-badge');
                    let currentCount = parseInt(item.dataset.unreadCount || '0');

                    if (increment) {
                        currentCount++;
                    } else {
                        currentCount = 0;
                    }

                    item.dataset.unreadCount = currentCount;

                    if (currentCount > 0) {
                        if (!badge) {
                            const badgeContainer = item.querySelector('.flex.flex-col.items-end');
                            if (badgeContainer) {
                                const newBadge = document.createElement('span');
                                newBadge.className =
                                    'unread-badge mt-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full';
                                newBadge.textContent = currentCount > 99 ? '99+' : currentCount;
                                badgeContainer.appendChild(newBadge);
                            }
                        } else {
                            badge.textContent = currentCount > 99 ? '99+' : currentCount;
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }

                function render(users) {
                    DOM.chatList.innerHTML = '';
                    if (!Array.isArray(users) || users.length === 0) {
                        DOM.chatList.innerHTML = '<div class="p-4 text-sm text-gray-500">No chats</div>';
                        return;
                    }

                    users.forEach(u => {
                        const active = (u.id === CURRENT_CHAT_ID);
                        const online = isUserOnline(u.id) || !!u.online;
                        const isTyping = typingUsers.has(u.id);
                        const hasUnread = u.unread_count > 0;

                        const el = document.createElement('div');
                        el.className =
                            `chat-item group flex items-center p-4 hover:bg-gray-50 border-b relative cursor-pointer ${active ? 'bg-indigo-50 border-l-4 border-indigo-600' : ''} ${hasUnread ? 'bg-blue-50' : ''}`;
                        el.dataset.chatId = u.id;
                        el.dataset.unreadCount = u.unread_count || 0;

                        el.innerHTML = `
          <div class="relative">
            <img src="${escapeHtml(u.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}&color=7F9CF5&background=EBF4FF`)}" 
                 class="w-11 h-11 rounded-full object-cover" alt="${escapeHtml(u.name)}" />
            <span class="absolute bottom-0 right-0 w-3 h-3 ${online ? 'bg-green-500' : 'bg-gray-400'} rounded-full ring-2 ring-white"></span>
          </div>
          <div class="ml-3 flex-1 overflow-hidden">
            <div class="font-semibold text-sm truncate">${escapeHtml(u.name)}</div>
            <div class="flex items-center gap-1">
              <div class="last-message-text text-xs text-gray-500 truncate ${isTyping ? 'hidden' : ''}">
                ${escapeHtml(u.last_message || 'No messages yet')}
              </div>
              <div class="typing-indicator text-xs text-blue-500 italic ${isTyping ? '' : 'hidden'}">
                typing...
              </div>
            </div>
          </div>
          <div class="flex flex-col items-end ml-2 flex-shrink-0">
            <div class="text-xs text-gray-400 font-medium">${u.last_time || ''}</div>
            ${hasUnread ? `<span class="unread-badge mt-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">${u.unread_count > 99 ? '99+' : u.unread_count}</span>` : ''}
          </div>
        `;

                        el.addEventListener('click', () => {
                            // Mark as read when clicked
                            if (el.dataset.unreadCount > 0) {
                                markAsRead(u.id);
                                updateUnreadCount(u.id, false);
                            }
                            window.location.href = `/admin/chat/${u.id}`;
                        });

                        DOM.chatList.appendChild(el);
                    });
                }

                DOM.searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => refresh(e.target.value.trim()), 300);
                });

                return {
                    refresh,
                    updateUnreadCount
                };
            })();

            /* ---------- Mark messages as read ---------- */
            async function markAsRead(userId) {
                try {
                    await fetch(`/admin/messages/${userId}/read`, {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    Logger.log(`Marked messages from ${userId} as read`);
                } catch (err) {
                    Logger.log('Failed to mark as read', err?.message || err);
                }
            }

            /* ---------- Menus ---------- */
            (function setupMenus() {
                const OPEN_BTN_SELECTOR = '[data-menu-open]';
                let activeMenuEl = null;

                function hide() {
                    if (!activeMenuEl) return;
                    activeMenuEl.style.display = 'none';
                    activeMenuEl = null;
                }

                function show(menuEl, trigger) {
                    menuEl.style.display = 'block';
                    const btnRect = trigger.getBoundingClientRect();
                    const menuRect = menuEl.getBoundingClientRect();
                    let left = btnRect.right + 8;
                    let top = btnRect.top + (btnRect.height / 2) - (menuRect.height / 2);
                    if (left + menuRect.width > window.innerWidth - 8) left = btnRect.left - menuRect.width - 8;
                    if (top < 8) top = 8;
                    if (top + menuRect.height > window.innerHeight - 8) top = window.innerHeight - menuRect
                        .height - 8;
                    menuEl.style.left = `${Math.max(8,left)}px`;
                    menuEl.style.top = `${top}px`;
                    activeMenuEl = menuEl;
                }
                document.addEventListener('click', (e) => {
                    const openBtn = e.target.closest(OPEN_BTN_SELECTOR);
                    if (openBtn) {
                        const key = openBtn.dataset.menuOpen;
                        const menuEl = document.querySelector(`.context-menu[data-menu="${key}"]`);
                        if (!menuEl) return;
                        if (activeMenuEl === menuEl && menuEl.getAttribute('aria-hidden') === 'false') {
                            hide();
                            return;
                        }
                        hide();
                        show(menuEl, openBtn);
                        return;
                    }
                    if (activeMenuEl && !e.target.closest('.context-menu')) hide();
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') hide();
                });
            })();

            /* -------------------------
               Realtime & Presence - Final working version
               ------------------------- */
            const Realtime = (() => {
                async function initEcho() {
                    Logger.log('Initializing Echo + presence...');
                    try {
                        await loadScript('https://js.pusher.com/7.0/pusher.min.js');
                        await loadScript(
                            'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js');

                        window.Echo = new Echo({
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

                        const pusher = window.Echo.connector.pusher;

                        // Connection status events
                        pusher.connection.bind('connected', () => {
                            Logger.log('Connected to Reverb', {
                                socketId: pusher.connection.socket_id
                            });
                            document.querySelector('#status-text').textContent = 'Connected';
                            document.querySelector('#status-dot').className =
                                'w-2 h-2 rounded-full mr-2 bg-green-500';
                            document.querySelector('#socket-id').textContent =
                                `Socket ID: ${pusher.connection.socket_id}`;
                        });

                        pusher.connection.bind('error', (err) => {
                            Logger.log('Pusher connection error', err);
                            document.querySelector('#status-text').textContent =
                                'Connection error';
                            document.querySelector('#status-dot').className =
                                'w-2 h-2 rounded-full mr-2 bg-red-500';
                        });

                        // Presence channel for online status
                        window.Echo.join('presence.chat')
                            .here((users) => {
                                Logger.log('Presence here - initial users:', users);
                                onlineUsers.clear();
                                users.forEach(u => {
                                    if (u && u.id) {
                                        onlineUsers.add(Number(u.id));
                                        Logger.log(`User online: ${u.id} - ${u.name}`);
                                    }
                                });
                                refreshSidebarOnlineIndicators();
                                updateHeaderPresenceUI();
                            })
                            .joining((user) => {
                                Logger.log('User joining presence:', user);
                                if (user && user.id) {
                                    onlineUsers.add(Number(user.id));
                                    refreshSidebarOnlineIndicators();
                                    updateHeaderPresenceUI();
                                    showToast(`${user.name} is now online`);
                                }
                            })
                            .leaving((user) => {
                                Logger.log('User leaving presence:', user);
                                if (user && user.id) {
                                    onlineUsers.delete(Number(user.id));
                                    refreshSidebarOnlineIndicators();
                                    updateHeaderPresenceUI();
                                    showToast(`${user.name} is now offline`);
                                }
                            })
                            .error((error) => {
                                Logger.log('Presence channel error:', error);
                                // Try to reconnect after error
                                setTimeout(() => {
                                    window.Echo.join('chat.presence');
                                }, 3000);
                            });

                        // Private channel for messages and typing
                        window.Echo.private(`chat.${AUTH_USER_ID}`)
                            .listen('MessageSent', (event) => {
                                Logger.log('MessageSent event', event);
                                const m = event.message;
                                if (!m) return;

                                // Clear typing indicator for sender
                                setUserTyping(m.sender_id, false);

                                if ((m.sender_id === CURRENT_CHAT_ID || m.receiver_id ===
                                        CURRENT_CHAT_ID)) {
                                    if (!document.querySelector(`[data-message-id="${m.id}"]`)) {
                                        Messages.append({
                                            id: m.id,
                                            sender_id: m.sender_id,
                                            message: m.message,
                                            file_path: m.file_path,
                                            created_at: m.created_at,
                                            isOwn: m.sender_id === AUTH_USER_ID
                                        });

                                        // If we're in this chat, mark as read
                                        if (m.sender_id === CURRENT_CHAT_ID) {
                                            markAsRead(CURRENT_CHAT_ID);
                                            Sidebar.updateUnreadCount(CURRENT_CHAT_ID, false);
                                        }
                                    }
                                }

                                // Update unread count for sidebar
                                if (m.sender_id !== AUTH_USER_ID) {
                                    Sidebar.updateUnreadCount(m.sender_id, true);
                                }

                                Sidebar.refresh();
                            })
                            .listen('TypingEvent', (event) => {
                                Logger.log('TypingEvent received', event);
                                if (event.senderId && event.senderId !== AUTH_USER_ID) {
                                    setUserTyping(event.senderId, true);
                                }
                            })
                            .listen('MessagesRead', (event) => {
                                Logger.log('MessagesRead event', event);
                                if (event.readerId && event.readerId !== AUTH_USER_ID) {
                                    // Someone read our messages
                                    Sidebar.refresh();
                                }
                            });

                    } catch (err) {
                        Logger.log('Echo init failed', err?.message || err);
                        document.querySelector('#status-text').textContent = 'Connection failed';
                        document.querySelector('#status-dot').className =
                            'w-2 h-2 rounded-full mr-2 bg-red-500';
                    }
                }

                function loadScript(src) {
                    return new Promise((res, rej) => {
                        const s = document.createElement('script');
                        s.src = src;
                        s.onload = res;
                        s.onerror = rej;
                        document.head.appendChild(s);
                    });
                }

                return {
                    initEcho
                };
            })();

            /* ---------- Fetch messages ---------- */
            async function fetchMessages() {
                try {
                    Logger.log('Fetching messages...');
                    const otherUserId = CURRENT_CHAT_ID;
                    const res = await fetch(`/admin/messages/${otherUserId}`, {
                        credentials: 'include',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const messages = await res.json();
                    if (!Array.isArray(messages) || messages.length === 0) {
                        Logger.log('No messages from server');
                        return;
                    }
                    messages.forEach(m => {
                        Messages.append({
                            id: m.id,
                            sender_id: m.sender_id,
                            message: m.message,
                            file_path: m.file_path,
                            created_at: m.created_at,
                            isOwn: m.sender_id === AUTH_USER_ID
                        });
                    });
                    Sidebar.refresh();
                } catch (err) {
                    Logger.log('Failed to fetch messages', err?.message || err);
                    DOM.messagesList.innerHTML =
                        '<div class="text-center text-red-500 py-4">Failed to load messages</div>';
                }
            }

            /* ---------- Send message ---------- */
            DOM.chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = (DOM.messageInput.value || '').trim();
                const file = FilePreview.getFile();
                if (!text && !file) return;

                const localId = 'local-' + Math.random().toString(36).slice(2, 9);
                const localMsg = {
                    id: localId,
                    sender_id: AUTH_USER_ID,
                    message: text,
                    created_at: new Date(),
                    isOwn: true,
                    localPreview: FilePreview.getPreview()
                };
                Messages.append(localMsg);

                const fd = new FormData();
                fd.append('receiver_id', CURRENT_CHAT_ID);
                if (text) fd.append('message', text);
                if (file) fd.append('file', file);

                DOM.messageInput.value = '';
                FilePreview.clear();

                try {
                    const res = await fetch('/admin/messages', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: fd
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const saved = await res.json();
                    Messages.replaceLocal(localId, {
                        id: saved.id,
                        sender_id: saved.sender_id,
                        message: saved.message,
                        file_path: saved.file_path,
                        created_at: saved.created_at,
                        isOwn: saved.sender_id === AUTH_USER_ID
                    });
                    Logger.log('Message saved', saved);
                    Sidebar.refresh();
                } catch (err) {
                    Logger.log('Failed to send', err?.message || err);
                    showToast('Failed to send message');
                }
            });

            /* ---------- Typing sender ---------- */
            let typingTimeout;
            DOM.messageInput.addEventListener('input', () => {
                // Clear previous timeout
                clearTimeout(typingTimeout);

                // Send typing indicator
                fetch('/admin/typing', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        receiver_id: CURRENT_CHAT_ID
                    })
                }).then(() => {
                    Logger.log('Typing indicator sent');
                }).catch((err) => {
                    Logger.log('Failed to send typing indicator', err);
                });

                // Set new timeout to stop typing after 1 second of inactivity
                typingTimeout = setTimeout(() => {
                    fetch('/admin/typing/stop', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            receiver_id: CURRENT_CHAT_ID
                        })
                    }).then(() => {
                        Logger.log('Typing stopped');
                    }).catch((err) => {
                        Logger.log('Failed to send typing stop', err);
                    });
                }, 1000);
            });

            // Also handle focus/blur events for better typing detection
            DOM.messageInput.addEventListener('focus', () => {
                // Send initial typing indicator when user focuses on input
                fetch('/admin/typing', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        receiver_id: CURRENT_CHAT_ID
                    })
                }).catch(() => {});
            });

            DOM.messageInput.addEventListener('blur', () => {
                // Clear typing timeout on blur
                clearTimeout(typingTimeout);

                // Send stop typing when user leaves the input
                fetch('/admin/typing/stop', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        receiver_id: CURRENT_CHAT_ID
                    })
                }).catch(() => {});
            });

            /* ---------- Test buttons ---------- */
            document.getElementById('test-connection').addEventListener('click', () => {
                const pusher = window.Echo?.connector?.pusher;
                if (pusher) {
                    const state = pusher.connection.state;
                    Logger.log('Connection test:', {
                        state: state,
                        socketId: pusher.connection.socket_id,
                        channels: Object.keys(pusher.channels.channels || {})
                    });
                    showToast(`Connection: ${state}`);
                } else {
                    Logger.log('Pusher not initialized');
                    showToast('Pusher not initialized');
                }
            });

            document.getElementById('test-presence').addEventListener('click', () => {
                Logger.log('=== PRESENCE TEST ===');
                Logger.log('Online users set:', Array.from(onlineUsers));
                Logger.log('Current user ID:', AUTH_USER_ID);
                Logger.log('Chat user ID:', CURRENT_CHAT_ID);
                Logger.log('Is chat user online?', isUserOnline(CURRENT_CHAT_ID));

                const pusher = window.Echo?.connector?.pusher;
                if (pusher) {
                    // Changed from 'presence-chat.presence' to 'presence-presence.chat'
                    const channel = pusher.channel('presence-presence.chat');
                    if (channel) {
                        Logger.log('Presence channel found:', {
                            name: channel.name,
                            subscribed: channel.subscribed,
                            members: channel.members ? Object.keys(channel.members.members) :
                                'No members',
                            count: channel.members ? channel.members.count : 0
                        });
                    } else {
                        Logger.log('Presence channel not found in Pusher channels');
                    }
                }

                // Manually test if we can add user to online set
                onlineUsers.add(CURRENT_CHAT_ID);
                Logger.log('Manually added user to online set');
                updateHeaderPresenceUI();
                refreshSidebarOnlineIndicators();

                showToast(`Online: ${onlineUsers.size}, Testing presence...`);
            });

            document.getElementById('clear-logs').addEventListener('click', () => {
                Logger.clear();
            });

            /* ---------- Init ---------- */
            Logger.log('Chat UI init');
            Sidebar.refresh();
            fetchMessages();
            Realtime.initEcho();

            // Mark current chat as read on load
            if (CURRENT_CHAT_ID) {
                markAsRead(CURRENT_CHAT_ID);
                Sidebar.updateUnreadCount(CURRENT_CHAT_ID, false);
            }

            // Initial scroll to bottom
            setTimeout(() => {
                scrollToBottom();
            }, 200);

            /* ---------- Misc helpers ---------- */
            function showToast(text, ms = 1600) {
                const el = document.createElement('div');
                el.textContent = text;
                el.style.position = 'fixed';
                el.style.right = '16px';
                el.style.bottom = '16px';
                el.style.padding = '8px 12px';
                el.style.background = 'rgba(15,23,42,0.9)';
                el.style.color = 'white';
                el.style.borderRadius = '8px';
                el.style.zIndex = 99999;
                el.style.fontSize = '14px';
                el.style.fontWeight = '500';
                document.body.appendChild(el);
                setTimeout(() => el.remove(), ms);
            }
        });
    </script>
@endpush
