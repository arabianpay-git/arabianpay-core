<div class="dropdown" data-dropdown="true" data-dropdown-offset="70px, 10px" data-dropdown-offset-rtl="-70px, 10px"
    data-dropdown-placement="bottom-end" data-dropdown-placement-rtl="bottom-start" data-dropdown-trigger="click|lg:click">
    <button
        class="dropdown-toggle btn btn-icon btn-icon-lg relative cursor-pointer size-9 rounded-full hover:bg-primary-light hover:text-primary dropdown-open:bg-primary-light dropdown-open:text-primary text-gray-500"
        id="notificationDropdown">
        <i class="ki-filled ki-notification-status"></i>
    </button>

    <div class="dropdown-content light:border-gray-300 w-full max-w-[460px]" id="notificationsContent">
        <div
            class="flex items-center justify-between gap-2.5 text-sm text-gray-900 font-semibold px-5 py-2.5 border-b border-b-gray-200">
            Notifications
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-dropdown-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="tabs justify-between px-5 mb-2" data-tabs="true">
            <div class="flex items-center gap-5">
                <button class="tab active" data-tab-toggle="#notifications_tab_all">All</button>
                <button class="tab relative" data-tab-toggle="#notifications_tab_unread">
                    Unread
                    <span id="notificationUnreadDot"
                        class="badge badge-dot badge-success size-[5px] absolute top-2 rtl:start-0 end-0 transform translate-y-1/2 translate-x-full">
                    </span>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button class="btn btn-xs btn-light" onclick="markAllNotificationsRead()">
                    <i class="ki-filled ki-check"></i> Mark all as read
                </button>
                <button class="btn btn-xs btn-light text-danger" onclick="deleteAllNotifications()">
                    <i class="ki-filled ki-trash"></i> Delete all
                </button>
            </div>
        </div>

        <div class="grow" id="notifications_tab_all">
            <div class="scrollable-y-auto max-h-[300px]" id="notificationListAll">
                <div class="flex flex-col items-center justify-center p-5 text-gray-500" id="loadingAll">
                    Loading...
                </div>
            </div>
        </div>

        <div class="grow hidden" id="notifications_tab_unread">
            <div class="scrollable-y-auto max-h-[300px]" id="notificationListUnread">
                <div class="flex flex-col items-center justify-center p-5 text-gray-500" id="loadingUnread">
                    Loading...
                </div>
            </div>
        </div>

        <div class="border-t border-t-gray-200 p-3 text-center text-xs text-gray-400">
            © {{ date('Y') }} All rights reserved
        </div>
    </div>
</div>

<style>
    .notification-item {
        position: relative;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
        border-left: 4px solid transparent;
        padding-right: 80px;
    }

    .notification-item:hover {
        background-color: #f3f4f6;
        border-left-color: #3b82f6;
    }

    .notification-item .mark-read-btn {
        position: absolute;
        top: 25%;
        right: 10px;
        transform: translateY(-50%);
        display: none;
        font-size: 0.75rem;
        color: #2563eb;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        text-decoration: underline;
    }

    .notification-item:hover .mark-read-btn {
        display: inline-block;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        loadNotifications();
    });

    function loadNotifications() {
        showLoading('#notificationListAll');
        showLoading('#notificationListUnread');

        fetch("{{ route('notifications.index') }}")
            .then(res => res.json())
            .then(data => {
                renderNotifications(data.notifications.data, '#notificationListAll');
                renderNotifications(data.notifications.data.filter(n => !n.read_at), '#notificationListUnread');

                const dot = document.getElementById('notificationUnreadDot');
                if (dot) dot.classList.toggle('hidden', data.unread_count === 0);
            })
            .catch(() => {
                showError('#notificationListAll');
                showError('#notificationListUnread');
            });
    }

    function renderNotifications(notifications, containerSelector) {
        const el = document.querySelector(containerSelector);
        el.innerHTML = "";

        if (!notifications.length) {
            el.innerHTML = `<div class="text-center py-5 text-gray-500">No notifications.</div>`;
            return;
        }

        notifications.forEach(n => {
            const data = typeof n.data === 'string' ? JSON.parse(n.data) : n.data;
            const isRead = !!n.read_at; // true if read_at is set
            const clickAction = data.click_action || '#';

            el.innerHTML += `
                <div
                    class="notification-item flex items-start gap-3 p-3 border-l-4 border-transparent hover:border-primary cursor-pointer"
                    onclick="window.open('${clickAction}', '_blank')"
                >
                    <div class="flex-shrink-0 pt-1">
                        <i class="ki-filled ${isRead ? 'ki-check-circle text-green-500' : 'ki-notification-status text-primary'} text-xl"></i>
                    </div>
                    <div class="text-sm text-gray-700 flex-grow">
                        <div class="font-medium text-gray-900">
                            <span class="select-none">${data.title || 'Notification'}</span>
                        </div>
                        <div class="text-xs underline decoration-dotted hover:underline">${data.description || ''}</div>
                        <div class="text-2xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</div>
                    </div>
                    ${!isRead ? `<button class="mark-read-btn" onclick="event.stopPropagation(); markNotificationRead('${n.id}')">Mark as Read</button>` : ''}
                </div>
            `;
        });
    }

    function showLoading(container) {
        const el = document.querySelector(container);
        if (el) el.innerHTML = `<div class="text-center py-5 text-gray-500">Loading...</div>`;
    }

    function showError(container) {
        const el = document.querySelector(container);
        if (el) el.innerHTML = `<div class="text-center py-5 text-red-500">Failed to load notifications.</div>`;
    }

    function markAllNotificationsRead() {
        fetch("{{ route('notifications.markAllRead') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(() => loadNotifications());
    }

    function deleteAllNotifications() {
        fetch("{{ route('notifications.deleteAll') }}", {
            method: "DELETE",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(() => loadNotifications());
    }

    function markNotificationRead(id) {
        fetch(`{{ url('/admin/notifications') }}/${id}/read`, {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(() => loadNotifications());
    }
</script>
