'use strict';

// Fondo minimalista: pocas brasas doradas que suben despacio y una luz cálida que sigue al ratón
(function () {
    const fondo = document.querySelector('.fondo');
    const canvas = fondo && fondo.querySelector('canvas');
    if (!canvas) {
        return;
    }
    const ctx = canvas.getContext('2d');
    const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let ancho = 0;
    let alto = 0;
    let brasas = [];

    function nuevaBrasa(enCualquierAltura) {
        return {
            x: Math.random() * ancho,
            y: enCualquierAltura ? Math.random() * alto : alto + 10,
            radio: Math.random() * 1.4 + 0.5,
            velocidad: Math.random() * 0.25 + 0.08,
            vaiven: Math.random() * Math.PI * 2,
            brillo: Math.random() * 0.5 + 0.25,
        };
    }

    function ajustar() {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        ancho = window.innerWidth;
        alto = window.innerHeight;
        canvas.width = ancho * ratio;
        canvas.height = alto * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        const total = Math.max(12, Math.min(40, Math.round((ancho * alto) / 40000)));
        brasas = Array.from({ length: total }, () => nuevaBrasa(true));
        if (reducirMovimiento) {
            dibujar(0);
        }
    }

    function dibujar(tiempo) {
        ctx.clearRect(0, 0, ancho, alto);
        ctx.shadowColor = 'rgba(226, 170, 90, 0.8)';
        ctx.shadowBlur = 6;
        for (const b of brasas) {
            const parpadeo = 0.75 + Math.sin(tiempo / 900 + b.vaiven) * 0.25;
            // Se apagan al subir: más brillo abajo, casi nada arriba
            const alfa = b.brillo * parpadeo * Math.min(1, b.y / alto + 0.15);
            ctx.beginPath();
            ctx.fillStyle = `rgba(226, 190, 120, ${alfa})`;
            ctx.arc(b.x, b.y, b.radio, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.shadowBlur = 0;
    }

    let ultimo = 0;
    function animar(tiempo) {
        // ~30 cuadros por segundo bastan para un movimiento tan lento y ahorran batería
        if (tiempo - ultimo > 33) {
            for (const b of brasas) {
                b.y -= b.velocidad;
                b.x += Math.sin(tiempo / 2000 + b.vaiven) * 0.12;
                if (b.y < -10) {
                    Object.assign(b, nuevaBrasa(false));
                }
            }
            dibujar(tiempo);
            ultimo = tiempo;
        }
        requestAnimationFrame(animar);
    }

    ajustar();
    // En el celular "resize" se dispara muchas veces al hacer scroll (barra de direcciones):
    // se espera a que termine y se ignoran los cambios pequeños de alto.
    let esperaResize = 0;
    window.addEventListener('resize', () => {
        clearTimeout(esperaResize);
        esperaResize = setTimeout(() => {
            if (window.innerWidth !== ancho || Math.abs(window.innerHeight - alto) > 120) {
                ajustar();
            }
        }, 150);
    });
    if (reducirMovimiento) {
        return;
    }

    window.addEventListener('pointermove', (evento) => {
        fondo.style.setProperty('--mx', `${evento.clientX}px`);
        fondo.style.setProperty('--my', `${evento.clientY}px`);
    }, { passive: true });
    requestAnimationFrame(animar);
})();
