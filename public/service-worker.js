const CACHE_NAME = 'driver-app-v1';
const OFFLINE_URL = '/offline.html';

const FILES_TO_CACHE = [
    OFFLINE_URL,
    '/manifest.json',
    '/images/logo-jalldev.png',
    // Kita tidak cache CSS/JS filament secara agresif dulu karena dinamik,
    // tapi kita cache offline page biar minimal ada tampilan "No Signal" yg bagus.
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(FILES_TO_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keyList) => {
            return Promise.all(keyList.map((key) => {
                if (key !== CACHE_NAME) {
                    return caches.delete(key);
                }
            }));
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Hanya handle navigate request (pindah halaman)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    // Untuk aset statis gambar/js/css (opsional)
    // event.respondWith(
    //     caches.match(event.request).then((response) => {
    //         return response || fetch(event.request);
    //     })
    // );
});
