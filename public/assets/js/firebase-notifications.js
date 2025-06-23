// firebase-notifications.js

// Import Firebase scripts dynamically (optional if loaded separately)
// If you want to load these here, uncomment below or load via HTML <script> tags:
//
// importScripts('https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js');
// importScripts('https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js');

// Firebase config - replace with your own config here if needed
const firebaseConfig = {
    apiKey: "AIzaSyDd3L0YugwxowiKUmUOyrDAuJKQtjhjQJE",
    authDomain: "arabianpay-b76ba.firebaseapp.com",
    databaseURL: "https://arabianpay-b76ba-default-rtdb.firebaseio.com",
    projectId: "arabianpay-b76ba",
    storageBucket: "arabianpay-b76ba.firebasestorage.app",
    messagingSenderId: "561424805381",
    appId: "1:561424805381:web:58c7ecd5e6ac590aa8f66c",
    measurementId: "G-9RLSPZYMLB",
};

// Public VAPID key for FCM (must match your Firebase project settings)
const vapidKey =
    "BDOzEmj7l_OiAPrJQfQthU1JMebKjvP6Ey15WB6p1naP_lZ4-PDJxpJ5oAYjIFNKx6q4g8NQ9ZlUEWxGhCG7414";

// Initialize Firebase App & Messaging
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

/**
 * Save FCM token to backend (POST /device-token)
 * @param {string} token
 */
async function saveFcmTokenToServer(token) {
    try {
        await fetch("/admin/device-token", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ token }),
        });
        // console.log("FCM token saved to server:", token);
    } catch (error) {
        // console.error("Error saving FCM token:", error);
    }
}

/**
 * Request user notification permission
 * @returns {Promise<boolean>} true if permission granted, else false
 */
async function requestPermission() {
    if (Notification.permission === "granted") {
        // console.log("Notification permission already granted.");
        return true;
    } else if (Notification.permission === "denied") {
        // console.warn("Notification permission was previously denied.");
        return false;
    } else {
        try {
            const permission = await Notification.requestPermission();
            return permission === "granted";
        } catch (err) {
            // console.error("Permission request error:", err);
            return false;
        }
    }
}

/**
 * Initialize Firebase Messaging: register service worker, get token, save to backend
 */
async function initFirebaseMessaging() {
    if (!("serviceWorker" in navigator)) {
        // console.error("Service workers are not supported in this browser.");
        return;
    }

    try {
        const registration = await navigator.serviceWorker.register(
            "/firebase-messaging-sw.js"
        );
        // console.log("Service Worker registered:", registration.scope);

        const permissionGranted = await requestPermission();
        if (!permissionGranted)
            throw new Error("Notification permission not granted");

        const token = await messaging.getToken({
            vapidKey,
            serviceWorkerRegistration: registration,
        });

        if (!token) throw new Error("No registration token available.");

        // Save token to backend
        await saveFcmTokenToServer(token);

        // console.log("FCM token:", token);
    } catch (error) {
        console.error("FCM initialization error:", error);
    }
}

/**
 * Handle foreground messages (while app in focus)
 */
messaging.onMessage(async (payload) => {
    // console.log("Foreground message received:", payload);

    const notificationTitle =
        payload.notification?.title || payload.data?.title || "Notification";

    const url =
        payload.data?.url ||
        payload.data?.click_action ||
        "http://localhost:8000";

    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || "",
        icon: "https://core.arabianpay.net/assets/media/images/ap.png",
        requireInteraction: true,
        tag: "arabianpay-token",
        renotify: true,
        data: {
            url: url,
            sound: "/notification.wav",
        },
    };

    // Play notification sound manually
    const audio = new Audio(notificationOptions.data.sound);
    audio.play().catch((e) => console.warn("Audio play failed:", e));

    if (Notification.permission === "granted") {
        try {
            const registration =
                await navigator.serviceWorker.getRegistration();
            if (registration && "showNotification" in registration) {
                registration.showNotification(
                    notificationTitle,
                    notificationOptions
                );
            } else {
                new Notification(notificationTitle, notificationOptions);
            }
        } catch (error) {
            // console.error("Error showing notification:", error);
            new Notification(notificationTitle, notificationOptions);
        }
    } else {
        // console.warn("Notification permission not granted.");
    }
});

// Initialize everything on load
window.addEventListener("load", () => {
    initFirebaseMessaging();
});
