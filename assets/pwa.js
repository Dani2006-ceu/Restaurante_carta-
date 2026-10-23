'use strict';

// Instalar como app (PWA): registra el service worker y maneja los botones [data-instalar]
(function () {
    const botones = Array.from(document.querySelectorAll('[data-instalar]'));
    const instalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const esIos = /iphone|ipad|ipod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    let solicitud = null;

    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js').catch(() => {
                // Sin service worker el sitio funciona igual, solo que no se instala
            });
        });
    }

    const mostrar = (visible) => botones.forEach((boton) => { boton.hidden = !visible; });
    mostrar(false);
    if (instalada || botones.length === 0) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (evento) => {
        evento.preventDefault();
        solicitud = evento;
        mostrar(true);
    });

    window.addEventListener('appinstalled', () => {
        solicitud = null;
        mostrar(false);
    });

    // En iPhone/iPad no existe el aviso automático: se explica cómo agregarla
    if (esIos) {
        mostrar(true);
    }

    function ayudaIos() {
        const ayuda = document.createElement('div');
        ayuda.className = 'ayuda-instalar';
        ayuda.setAttribute('role', 'dialog');
        ayuda.setAttribute('aria-label', 'Cómo instalar');
        const texto = document.createElement('p');
        texto.textContent = 'Toca el botón Compartir (el cuadrado con la flecha) y luego «Agregar a pantalla de inicio».';
        const cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.textContent = 'Entendido';
        cerrar.addEventListener('click', () => ayuda.remove());
        ayuda.append(texto, cerrar);
        document.body.append(ayuda);
    }

    botones.forEach((boton) => {
        boton.addEventListener('click', async () => {
            if (solicitud) {
                solicitud.prompt();
                await solicitud.userChoice;
                solicitud = null;
                mostrar(false);
            } else if (esIos) {
                ayudaIos();
            }
        });
    });
})();
