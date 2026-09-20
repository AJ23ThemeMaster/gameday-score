// DISI-82: service worker de la PWA de Gameday Score.
// Estrategia: network-first, con fallback a cache. Solo cacheamos
// respuestas GET del mismo origen y exitosas.
//
// v2: bump para invalidar el cache遗留 del dark theme de la rama
// feature-design (commits b9ab23c/b63022e revertidos). Si volvés a
// ver dark theme en master, bump a v3 o mas.
const CACHE_VERSION = 'gameday-v2';
const CORE_ASSETS = [
    '/',
    '/juego',
    '/manifest.json',
    '/offline',
    '/build/assets/app.css',
    '/build/assets/app.js',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(CORE_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Network-first con fallback a cache y a /offline para navegacion.
    event.respondWith(
        fetch(req)
            .then((resp) => {
                // Solo cachear respuestas exitosas de paths estaticos / no sensibles
                if (resp.ok && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/'))) {
                    const clone = resp.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(req, clone));
                }
                return resp;
            })
            .catch(() =>
                caches.match(req).then((cached) => cached || (req.mode === 'navigate'
                    ? caches.match('/offline')
                    : new Response('', { status: 504, statusText: 'Offline' })))
            )
    );
});