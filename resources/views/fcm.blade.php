<!DOCTYPE html>
<html lang="en">

<head>
    <title>FCM Test</title>
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
</head>

<body>
    <h2>Send FCM Notification</h2>

    <form id="notificationForm">
        <input type="text" id="title" name="title" placeholder="Title" required />
        <textarea id="body" name="body" placeholder="Body" required></textarea>
        <input type="hidden" id="device_token" name="device_token" />
        <button type="submit">Send Notification</button>
    </form>

    <p id="response"></p>

    <script>
        // Firebase config
        const firebaseConfig = {
            apiKey: "AIzaSyDd3L0YugwxowiKUmUOyrDAuJKQtjhjQJE",
            authDomain: "arabianpay-b76ba.firebaseapp.com",
            databaseURL: "https://arabianpay-b76ba-default-rtdb.firebaseio.com",
            projectId: "arabianpay-b76ba",
            storageBucket: "arabianpay-b76ba.firebasestorage.app",
            messagingSenderId: "561424805381",
            appId: "1:561424805381:web:58c7ecd5e6ac590aa8f66c",
            measurementId: "G-9RLSPZYMLB"
        };

        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();
        const vapidKey = 'BDOzEmj7l_OiAPrJQfQthU1JMebKjvP6Ey15WB6p1naP_lZ4-PDJxpJ5oAYjIFNKx6q4g8NQ9ZlUEWxGhCG7414';

        async function requestPermission() {
            if (Notification.permission === 'granted') {
                console.log('Notification permission already granted.');
                return true;
            } else if (Notification.permission === 'denied') {
                console.warn('Notification permission was previously denied.');
                return false;
            } else {
                try {
                    const permission = await Notification.requestPermission();
                    return permission === 'granted';
                } catch (err) {
                    console.error('Permission request error:', err);
                    return false;
                }
            }
        }

        async function initFirebaseMessagingRegistration() {
            if (!('serviceWorker' in navigator)) {
                document.getElementById('response').innerText = 'Service Workers are not supported in this browser.';
                console.error('Service workers are not supported.');
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                console.log('Service Worker registered:', registration.scope);

                const permissionGranted = await requestPermission();
                if (!permissionGranted) {
                    throw new Error('Notification permission not granted');
                }

                const token = await messaging.getToken({
                    vapidKey,
                    serviceWorkerRegistration: registration,
                });

                if (!token) {
                    throw new Error('No registration token available.');
                }

                document.getElementById('device_token').value = token;
                console.log('FCM Token:', token);
                document.getElementById('response').innerText = 'Notification permission granted and token obtained.';
            } catch (error) {
                console.error('FCM registration error:', error);
                document.getElementById('response').innerText = 'Error initializing notifications: ' + error.message;
            }
        }

        initFirebaseMessagingRegistration();

        // Handle foreground messages
        messaging.onMessage(async (payload) => {
            console.log('Foreground message received:', payload);

            const notificationTitle = payload.notification?.title || payload.data?.title || 'Notification';
            const notificationOptions = {
                body: payload.notification?.body || payload.data?.body || '',
                icon: 'https://core.arabianpay.net/assets/media/images/ap.png',
                requireInteraction: true,
                tag: 'fcm-notification',
                renotify: true,
                data: {
                    url: "http://localhost:8000",
                    sound: "/notification.wav", // put your sound URL here
                },
            };

            // Manually play sound for foreground notifications
            const audio = new Audio(notificationOptions.data.sound);
            audio.play().catch((e) => console.warn("Audio play failed:", e));

            if (Notification.permission === 'granted') {
                try {
                    const registration = await navigator.serviceWorker.getRegistration();

                    if (registration) {
                        console.log(JSON.stringify(notificationOptions));
                        if ('showNotification' in registration) {
                            registration.showNotification(notificationTitle, notificationOptions);
                        } else {
                            new Notification(notificationTitle, notificationOptions);
                        }
                        console.log('Notification shown via service worker');
                    } else {
                        console.warn('No service worker registration found. Using direct Notification API.');
                        new Notification(notificationTitle, notificationOptions);
                    }
                } catch (error) {
                    console.error('Error showing notification:', error);
                    new Notification(notificationTitle, notificationOptions);
                }
            } else {
                console.warn('Notification permission not granted.');
            }
        });


        // Form submit handler - send notification data to your backend route
        document.getElementById('notificationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            document.getElementById('response').innerText = 'Sending notification...';

            try {
                const response = await fetch("/send-fcm", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                    },
                    body: JSON.stringify({
                        title: document.getElementById('title').value,
                        body: document.getElementById('body').value,
                        device_token: document.getElementById('device_token').value,
                    }),
                });

                const result = await response.json();

                if (result.message) {
                    document.getElementById('response').innerText = result.message;
                } else if (result.error) {
                    document.getElementById('response').innerText = "Error: " + result.error;
                    console.error(result.details);
                }
            } catch (err) {
                console.error('Fetch error:', err);
                document.getElementById('response').innerText = 'Failed to send notification.';
            }
        });
    </script>
</body>

</html>
