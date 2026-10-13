/*
 * Festes Majors de Catalunya — service worker (basic PWA support).
 *  - Pages: network-first, falling back to the cached copy, then /offline.
 *  - Built assets, fonts, covers and map tiles: stale-while-revalidate.
 *  - The admin panel and API writes are never cached.
 */
const VERSION = 'festes-v1';
const PAGES = `${VERSION}-pages`;
const ASSETS = `${VERSION}-assets`;
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PAGES)
            .then((cache) => cache.addAll([OFFLINE_URL, '/manifest.webmanifest', '/icons/icon.svg']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(VERSION)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

function isAsset(url) {
    return (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/uploads/') ||
        url.pathname.startsWith('/img/') ||
        url.pathname.startsWith('/icons/') ||
        url.hostname.endsWith('fonts.gstatic.com') ||
        url.hostname.endsWith('fonts.googleapis.com') ||
        url.hostname === 'tile.openstreetmap.org'
    );
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(ASSETS);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
            return response;
        })
        .catch(() => cached);
    return cached || network;
}

async function networkFirst(request) {
    const cache = await caches.open(PAGES);
    try {
        const response = await fetch(request);
        if (response.ok) cache.put(request, response.clone());
        return response;
    } catch {
        return (await cache.match(request)) || (await cache.match(OFFLINE_URL));
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin === self.location.origin && (url.pathname.startsWith('/admin') || url.pathname.startsWith('/story/'))) return;

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request));
    } else if (isAsset(url)) {
        event.respondWith(staleWhileRevalidate(request));
    } else if (url.origin === self.location.origin && url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request));
    }
});
