const CACHE_NAME = "offline-cache-v1";
const OFFLINE_URL = "/offline";

const ASSETS_TO_CACHE = [
    OFFLINE_URL,
    "/assets/css/styles.css",
    "/assets/js/core.bundle.js",
    "/assets/vendors/apexcharts/apexcharts.min.js",
    "/assets/js/network.js",
    "/assets/media/illustrations/9.svg",
    "/assets/media/images/favicon.png",
    "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap",
    "/assets/css/IBMPlexSansArabic-Regular.ttf",
];

self.addEventListener("install", function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting(); // Activate immediately
});

self.addEventListener("activate", function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", function (event) {
    if (event.request.mode === "navigate") {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL))
        );
    } else {
        event.respondWith(
            caches.match(event.request).then(function (response) {
                return response || fetch(event.request);
            })
        );
    }
});
