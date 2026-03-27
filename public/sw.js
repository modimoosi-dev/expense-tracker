const CACHE_NAME = 'expense-tracker-v1';

const STATIC_ASSETS = [
    '/dashboard',
    '/expenses',
    '/budgets',
    '/reports',
    '/manifest.json',
];

// ── Install: pre-cache shell pages ──────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// ── Activate: remove old caches ──────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// ── Fetch: network-first for API, cache-first for assets ────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Always go to network for API and POST requests
    if (url.pathname.startsWith('/api/') || request.method !== 'GET') {
        event.respondWith(fetch(request));
        return;
    }

    // Cache-first for static assets (JS/CSS/fonts)
    if (url.pathname.match(/\.(js|css|woff2?|png|jpg|svg|ico)$/)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((res) => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                return res;
            }))
        );
        return;
    }

    // Network-first for HTML pages (fallback to cache when offline)
    event.respondWith(
        fetch(request)
            .then((res) => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                return res;
            })
            .catch(() => caches.match(request))
    );
});

// ── Push notifications ────────────────────────────────────────────────────────
self.addEventListener('push', (event) => {
    const data = event.data?.json() ?? {};
    event.waitUntil(
        self.registration.showNotification(data.title || 'Expense Tracker', {
            body: data.body || data.message || '',
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            tag: data.tag || 'expense-tracker',
            data: { url: data.url || '/dashboard' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data?.url || '/dashboard')
    );
});
