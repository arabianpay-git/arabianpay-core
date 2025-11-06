importScripts(
    "https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js"
);

firebase.initializeApp({
    apiKey: "AIzaSyDd3L0YugwxowiKUmUOyrDAuJKQtjhjQJE",
    authDomain: "arabianpay-b76ba.firebaseapp.com",
    databaseURL: "https://arabianpay-b76ba-default-rtdb.firebaseio.com",
    projectId: "arabianpay-b76ba",
    storageBucket: "arabianpay-b76ba.firebasestorage.app",
    messagingSenderId: "561424805381",
    appId: "1:561424805381:web:58c7ecd5e6ac590aa8f66c",
    measurementId: "G-9RLSPZYMLB",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    console.log(
        "[firebase-messaging-sw.js] Received background message:",
        payload
    );

    const title =
        payload.notification?.title || payload.data?.title || "Notification";
    const url =
        payload.data?.url ||
        payload.data?.click_action ||
        "http://localhost:8000";

    const options = {
        body: payload.notification?.body || payload.data?.body || "",
        icon: "https://core.arabianpay.net/assets/media/images/ap.png",
        tag: "arabianpay-token",
        renotify: true,
        requireInteraction: true,
        data: {
            url: url,
            sound: "/notification.wav",
        },
    };

    self.registration.showNotification(title, options);
});

self.addEventListener("notificationclick", function (event) {
    event.notification.close();

    const targetUrl = event.notification.data?.url || "/";
    event.waitUntil(
        clients
            .matchAll({ type: "window", includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url === targetUrl && "focus" in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});
