const CACHE_NAME = 'dofit-v1';

// Only precache what is guaranteed to exist. cache.addAll() rejects as a whole
// if a single entry 404s, which would stop the worker from ever installing.
const PRECACHE_URLS = [
    '/offline',
    '/images/icons/icon-72x72.png',
    '/images/icons/icon-96x96.png',
    '/images/icons/icon-128x128.png',
    '/images/icons/icon-144x144.png',
    '/images/icons/icon-152x152.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-384x384.png',
    '/images/icons/icon-512x512.png',
];

self.addEventListener('install', (event) => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((names) => Promise.all(
                names
                    .filter((name) => name.startsWith('dofit-') && name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            ))
            .then(() => self.clients.claim())
    );
});

/**
 * Vite emits hashed filenames, so build output is cached the first time it is
 * requested rather than listed up front.
 */
const isCacheableAsset = (url) =>
    url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/');

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Never get in the way of Livewire round trips, uploads or any other
    // non-GET traffic, and leave other origins alone.
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Pages are served fresh so the app never shows stale data; the offline
    // page is the fallback when the network is unreachable.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline'))
        );

        return;
    }

    if (! isCacheableAsset(url)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();

                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }

                return response;
            });
        })
    );
});
