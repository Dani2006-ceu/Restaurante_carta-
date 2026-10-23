'use strict';

// Service worker: guarda el diseño y el último menú visto para abrirlos sin conexión.
// Las páginas del panel y los pedidos nunca se guardan: siempre van a la red.
const VERSION = 'v2';
const ESTATICOS = `estaticos-${VERSION}`;
const MENUS = `menus-${VERSION}`;
const ARCHIVOS = [
    'assets/app.css',
    'assets/carta.css',
    'assets/carta.js',
    'assets/fondo.js',
    'assets/pwa.js',
    'assets/img/icon.svg',
    'assets/img/icon-192.png',
    'assets/img/icon-512.png',
];

self.addEventListener('install', (evento) => {
    evento.waitUntil(caches.open(ESTATICOS).then((cache) => cache.addAll(ARCHIVOS)));
    self.skipWaiting();
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys()
            .then((nombres) => Promise.all(
                nombres.filter((n) => n !== ESTATICOS && n !== MENUS).map((n) => caches.delete(n))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (evento) => {
    const peticion = evento.request;
    const url = new URL(peticion.url);
    if (peticion.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    const ruta = url.searchParams.get('route');
    if (ruta === 'carta' || ruta === 'carta-manifest') {
        // Menú del cliente: primero la red (precios al día); sin conexión, la última copia
        url.searchParams.delete('enviado');
        evento.respondWith(redPrimero(peticion, url.toString()));
        return;
    }

    if (url.pathname.includes('/assets/') || url.pathname.includes('/uploads/menu/')) {
        evento.respondWith(cachePrimero(peticion));
    }
});

async function redPrimero(peticion, clave) {
    const cache = await caches.open(MENUS);
    try {
        const respuesta = await fetch(peticion);
        if (respuesta.ok) {
            cache.put(clave, respuesta.clone());
        }
        return respuesta;
    } catch (error) {
        const guardada = await cache.match(clave);
        return guardada || new Response(
            '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
            + '<p style="font-family:sans-serif;padding:24px">Sin conexión. Abre el menú una vez con internet para guardarlo.</p>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

async function cachePrimero(peticion) {
    const cache = await caches.open(ESTATICOS);
    const guardada = await cache.match(peticion, { ignoreSearch: true });
    const deRed = fetch(peticion)
        .then((respuesta) => {
            if (respuesta.ok) {
                cache.put(peticion, respuesta.clone());
            }
            return respuesta;
        })
        .catch(() => guardada);

    return guardada || deRed;
}
