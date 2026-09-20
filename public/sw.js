// DISI-NUCLEAR: SW de un solo uso que borra todos los caches y se
// desregistra. Para cuando el SW viejo no se deja reemplazar.
//
// Activar SOLO cuando: el user sigue viendo dark theme despues de
// (a) cerrar todas las pestanas, (b) hard reload, (c) unregister manual
// en DevTools. Este SW es el ultimo recurso: una vez que todos los
// clientes esten en master limpio, se reemplaza por el SW normal
// (v4 con cache de assets reales, o se elimina si no se quiere PWA).

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        Promise.all([
            // Borrar TODOS los caches (viejos + este mismo)
            caches.keys().then((keys) =>
                Promise.all(keys.map((k) => caches.delete(k)))
            ),
            // Des-registrar este SW para que el navegador no lo cargue mas
            self.registration.unregister(),
        ])
        .then(() => self.clients.matchAll({ type: 'window' }))
        .then((clients) => {
            // Forzar reload de todos los clientes para que carguen
            // la version master limpia sin SW
            clients.forEach((client) => client.navigate(client.url));
        })
    );
});

// No handler de fetch: cualquier peticion pasa directo a la red.
// El SW solo existe para borrar caches y auto-desregistrarse.
