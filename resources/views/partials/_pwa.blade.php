{{-- DISI-82: meta tags PWA + service worker. Incluir en layouts/app y layouts/guest. --}}
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#3730a3">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Gameday">
<link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">
<meta name="mobile-web-app-capable" content="yes">

<script>
    // DISI-82: registrar el service worker solo si el navegador lo soporta.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((reg) => console.log('[PWA] Service worker registrado:', reg.scope))
                .catch((err) => console.warn('[PWA] Fallo al registrar SW:', err));
        });
    }
</script>