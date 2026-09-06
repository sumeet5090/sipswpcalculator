/**
 * Service Worker (sw.js)
 * SIP & SWP Calculator Offline PWA Engine
 *
 * Provides instant cache-first asset loading and network-first navigation fallback,
 * enabling offline calculations for mobile and desktop users.
 */

const CACHE_NAME = 'sipswp-cache-v1';
const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/assets/favicon.svg',
    '/assets/favicon.png',
    '/assets/logo.svg',
    '/sip-calculator',
    '/swp-calculator'
];

// Install: precache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activate: clean up outdated caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Strategy based on request type
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests and cross-origin tracking/analytics
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Skip admin or API routes
    if (url.pathname.startsWith('/admin_insights') || url.pathname.startsWith('/log_insight')) {
        return;
    }

    // Static assets (CSS, JS, images, fonts): Stale-While-Revalidate
    if (url.pathname.startsWith('/dist/') || url.pathname.startsWith('/assets/')) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Navigation / HTML pages: Network-First with Cache Fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            }).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return caches.match('/sip-calculator');
                });
            })
        );
        return;
    }

    // Fallback: Cache first, then network
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            return cachedResponse || fetch(request);
        })
    );
});
