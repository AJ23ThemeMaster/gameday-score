// DISI-82: service worker de la PWA de Gameday Score.
// Estrategia: network-first, con fallback a cache. Solo cacheamos
// respuestas GET del mismo origen y exitosas.
//
// v3: fix install failure. Antes usabamos cache.addAll() que rechaza
// si UN solo asset retorna 404 (ej. /build/assets/app.css cuando
// Vite no compilo). Eso bloqueaba la instalacion del SW y el nuevo
// SW v2 nunca reemplazaba al v1, dejando el cache viejo (dark theme
// de feature-design) activo indefinidamente.
// Solucion: Promise.allSettled() con .catch() por asset. La instalacion
// SIEMPRE completa; los assets que fallen simplemente no se cachean.
const CACHE_VERSION = 'gameday-v3';
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
    // skipWaiting debe ejecutarse lo antes posible para que el SW
    // nuevo tome el control sin esperar a que se cierren las pestañas.
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) =>
            Promise.allSettled(
                CORE_ASSETS.map((url) =>
                    cache.add(new Request(url, { cache: 'reload' }))
                        .catch((err) => console.warn('[SW] No se pudo pre-cachear:', url, err.message))
                )
            )
        )
    );
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