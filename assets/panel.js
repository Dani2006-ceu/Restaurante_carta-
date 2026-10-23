'use strict';

// Panel del restaurante: copiar link, QR, confirmaciones y aviso de pedidos nuevos
(function () {
    document.querySelectorAll('form[data-confirmar]').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            if (!window.confirm(form.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-copiar]').forEach((boton) => {
        boton.addEventListener('click', async () => {
            const campo = document.querySelector(boton.dataset.copiar);
            try {
                await navigator.clipboard.writeText(campo.value);
            } catch (error) {
                campo.select();
                document.execCommand('copy');
            }
            const texto = boton.textContent;
            boton.textContent = '¡Copiado!';
            setTimeout(() => { boton.textContent = texto; }, 1800);
        });
    });

    window.addEventListener('load', () => {
        const qr = document.querySelector('[data-qr]');
        if (!qr || typeof window.QRCode !== 'function') {
            return;
        }
        new window.QRCode(qr, {
            text: qr.dataset.qr,
            width: 400,
            height: 400,
            colorDark: '#2a1d14',
            colorLight: '#fbf6ee',
            correctLevel: window.QRCode.CorrectLevel.M,
        });
    });

    document.querySelectorAll('[data-descargar-qr]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const contenedor = document.querySelector(boton.dataset.descargarQr);
            const canvas = contenedor && contenedor.querySelector('canvas');
            if (!canvas) {
                return;
            }
            // Tarjeta lista para imprimir: fondo crema, QR con margen, nombre y una línea dorada
            const titulo = contenedor.dataset.qrTitulo || 'Nuestro menú';
            const margen = 60;
            const salida = document.createElement('canvas');
            salida.width = canvas.width + margen * 2;
            salida.height = canvas.height + margen * 2 + 150;
            const ctx = salida.getContext('2d');
            ctx.fillStyle = '#fbf6ee';
            ctx.fillRect(0, 0, salida.width, salida.height);
            ctx.strokeStyle = '#d9b56f';
            ctx.lineWidth = 3;
            ctx.strokeRect(18, 18, salida.width - 36, salida.height - 36);
            ctx.drawImage(canvas, margen, margen);
            ctx.textAlign = 'center';
            ctx.fillStyle = '#2a1d14';
            ctx.font = '700 44px "Cormorant Garamond", Georgia, serif';
            ctx.fillText(titulo, salida.width / 2, canvas.height + margen + 70, salida.width - 80);
            ctx.fillStyle = '#8a2b38';
            ctx.font = '500 22px Inter, "Segoe UI", sans-serif';
            ctx.fillText('Escanea para ver el menú y pedir', salida.width / 2, canvas.height + margen + 112);
            const enlace = document.createElement('a');
            enlace.href = salida.toDataURL('image/png');
            enlace.download = 'qr-menu.png';
            enlace.click();
        });
    });

    // Muestra la hora de cada pedido en la zona horaria del navegador
    function horaLocal(raiz) {
        raiz.querySelectorAll('time[data-hora-local]').forEach((tiempo) => {
            const fecha = new Date(tiempo.getAttribute('datetime').replace(' ', 'T'));
            if (!Number.isNaN(fecha.getTime())) {
                tiempo.textContent = fecha.toLocaleString('es', { dateStyle: 'short', timeStyle: 'short' });
            }
        });
    }
    horaLocal(document);

    // Foto del producto: vista previa y reducción antes de subirla (las fotos del celular pesan varios MB)
    const LADO_MAXIMO = 1200;
    document.querySelectorAll('input[type="file"][data-foto]').forEach((campo) => {
        const formulario = campo.form;
        const vista = formulario.querySelector('[data-foto-preview]');
        const estado = formulario.querySelector('[data-foto-estado]');
        const quitar = formulario.querySelector('input[name="remove_photo"]');

        function mostrar(archivo) {
            const img = document.createElement('img');
            img.alt = 'Vista previa';
            img.src = URL.createObjectURL(archivo);
            vista.replaceChildren(img);
            const kb = Math.round(archivo.size / 1024);
            estado.textContent = `${archivo.name} · ${kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${kb} KB`}`;
            if (quitar) {
                quitar.checked = false;
            }
        }

        async function achicar(archivo) {
            if (!/^image\/(jpeg|png|webp)$/.test(archivo.type) || typeof window.DataTransfer !== 'function') {
                return archivo;
            }
            const imagen = await new Promise((resolver, rechazar) => {
                const img = new Image();
                img.onload = () => resolver(img);
                img.onerror = rechazar;
                img.src = URL.createObjectURL(archivo);
            });
            const escala = Math.min(1, LADO_MAXIMO / Math.max(imagen.naturalWidth, imagen.naturalHeight));
            if (escala === 1 && archivo.size < 600 * 1024) {
                return archivo;
            }
            const lienzo = document.createElement('canvas');
            lienzo.width = Math.round(imagen.naturalWidth * escala);
            lienzo.height = Math.round(imagen.naturalHeight * escala);
            const ctx = lienzo.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, lienzo.width, lienzo.height);
            ctx.drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
            const blob = await new Promise((resolver) => lienzo.toBlob(resolver, 'image/jpeg', 0.82));
            if (!blob || blob.size >= archivo.size) {
                return archivo;
            }
            return new File([blob], archivo.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
        }

        campo.addEventListener('change', async () => {
            const original = campo.files && campo.files[0];
            if (!original) {
                return;
            }
            if (!original.type.startsWith('image/')) {
                estado.textContent = 'Elige un archivo de imagen (JPG, PNG o WebP).';
                campo.value = '';
                return;
            }
            estado.textContent = 'Preparando foto…';
            let final = original;
            try {
                final = await achicar(original);
                if (final !== original) {
                    const transferencia = new DataTransfer();
                    transferencia.items.add(final);
                    campo.files = transferencia.files;
                }
            } catch (error) {
                // Si el navegador no puede procesarla, se sube la original
            }
            if (final.size > 5 * 1024 * 1024) {
                estado.textContent = 'La foto pesa más de 5 MB. Elige una más liviana.';
                campo.value = '';
                return;
            }
            mostrar(final);
        });
    });

    // Aviso de pedidos nuevos: contador en el menú, sonido y recarga de la página de pedidos
    const insignias = document.querySelectorAll('[data-pedidos-pendientes]');
    if (insignias.length === 0) {
        return;
    }
    const listaPedidos = document.querySelector('[data-pedidos-lista]');
    const tituloOriginal = document.title;
    let anterior = null;

    // Sonido: los navegadores solo dejan reproducir audio después de que el usuario toca la página,
    // por eso el audio se "desbloquea" con el primer toque o con el botón de la campanita.
    // La elección (tipo, volumen, activado) se guarda en este dispositivo.
    const botonesSonido = document.querySelectorAll('[data-sonido]');
    const leer = (clave, porDefecto) => {
        try {
            return localStorage.getItem(clave) ?? porDefecto;
        } catch (error) {
            return porDefecto;
        }
    };
    const guardar = (clave, valor) => {
        try {
            localStorage.setItem(clave, valor);
        } catch (error) {
            // Sin almacenamiento local la elección dura mientras la página esté abierta
        }
    };
    let audio = null;
    let sonidoActivo = leer('sonidoPedidos', 'si') !== 'no';
    let tipoSonido = leer('sonidoPedidosTipo', 'timbre');
    let volumenSonido = Math.min(100, Math.max(10, parseInt(leer('sonidoPedidosVolumen', '70'), 10) || 70));

    function prepararAudio() {
        const Contexto = window.AudioContext || window.webkitAudioContext;
        if (!Contexto) {
            return null;
        }
        audio = audio || new Contexto();
        if (audio.state === 'suspended') {
            audio.resume();
        }
        return audio;
    }

    // Una nota sintetizada: frecuencia, inicio y duración en segundos, forma de onda y sobretonos
    function nota(ctx, frecuencia, inicio, duracion, forma, fuerza, armonicos = [[1, 1]]) {
        const t = ctx.currentTime + inicio;
        armonicos.forEach(([multiplo, proporcion]) => {
            const tono = ctx.createOscillator();
            const volumen = ctx.createGain();
            tono.type = forma;
            tono.frequency.value = frecuencia * multiplo;
            volumen.gain.setValueAtTime(0.0001, t);
            volumen.gain.exponentialRampToValueAtTime(Math.max(0.0002, fuerza * proporcion), t + 0.01);
            volumen.gain.exponentialRampToValueAtTime(0.0001, t + duracion);
            tono.connect(volumen).connect(ctx.destination);
            tono.start(t);
            tono.stop(t + duracion + 0.05);
        });
    }

    const CAMPANA = [[1, 1], [2.76, 0.25], [5.4, 0.08]];
    const SONIDOS = {
        // Timbre de mostrador: mi – do, dos veces
        timbre: (ctx, v) => [0, 0.32, 0.99, 1.31].forEach((inicio, i) => {
            nota(ctx, i % 2 === 0 ? 1318.5 : 1046.5, inicio, 1.1, 'triangle', 0.35 * v, [[1, 1], [2.76, 0.23]]);
        }),
        // Una campanada grave y larga
        campana: (ctx, v) => {
            nota(ctx, 523.3, 0, 2.6, 'sine', 0.45 * v, CAMPANA);
            nota(ctx, 523.3, 1.4, 2.4, 'sine', 0.3 * v, CAMPANA);
        },
        // Campanita de "pedido listo" de cocina: tres toques rápidos y agudos
        cocina: (ctx, v) => [0, 0.16, 0.32].forEach((inicio) => {
            nota(ctx, 2093, inicio, 0.7, 'sine', 0.3 * v, CAMPANA);
        }),
        // Marimba: do – mi – sol ascendente
        marimba: (ctx, v) => [523.3, 659.3, 784].forEach((frecuencia, i) => {
            nota(ctx, frecuencia, i * 0.14, 0.5, 'sine', 0.45 * v, [[1, 1], [4, 0.12]]);
        }),
        // Suave: dos notas largas y tranquilas
        suave: (ctx, v) => {
            nota(ctx, 659.3, 0, 1.6, 'sine', 0.3 * v);
            nota(ctx, 987.8, 0.35, 1.8, 'sine', 0.25 * v);
        },
        // Pitido digital: bip – bip – bip
        pitido: (ctx, v) => [0, 0.2, 0.4].forEach((inicio) => {
            nota(ctx, 1000, inicio, 0.12, 'square', 0.12 * v);
        }),
    };

    function reproducir(tipo) {
        const ctx = prepararAudio();
        if (!ctx) {
            return;
        }
        (SONIDOS[tipo] || SONIDOS.timbre)(ctx, volumenSonido / 100);
    }

    // Se llama cuando llega un pedido
    function timbre() {
        if (sonidoActivo) {
            reproducir(tipoSonido);
        }
    }

    const casillaActivo = document.querySelector('[data-sonido-activo]');
    const barraVolumen = document.querySelector('[data-sonido-volumen]');

    function pintarSonido() {
        botonesSonido.forEach((boton) => {
            boton.textContent = sonidoActivo ? '🔔 Sonido' : '🔕 Sin sonido';
            boton.setAttribute('aria-pressed', String(sonidoActivo));
            boton.title = sonidoActivo ? 'Sonará un aviso cuando llegue un pedido' : 'Activar el aviso de pedidos';
        });
        if (casillaActivo) {
            casillaActivo.checked = sonidoActivo;
        }
    }

    function cambiarActivo(activo) {
        sonidoActivo = activo;
        guardar('sonidoPedidos', activo ? 'si' : 'no');
        pintarSonido();
        if (activo) {
            reproducir(tipoSonido);
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
    }

    botonesSonido.forEach((boton) => {
        boton.addEventListener('click', () => cambiarActivo(!sonidoActivo));
    });
    if (casillaActivo) {
        casillaActivo.addEventListener('change', () => cambiarActivo(casillaActivo.checked));
    }

    // Controles de la página de pedidos: tipo de sonido, volumen y probar
    document.querySelectorAll('[data-sonido-tipo]').forEach((opcion) => {
        opcion.checked = opcion.value === tipoSonido;
        opcion.addEventListener('change', () => {
            tipoSonido = opcion.value;
            guardar('sonidoPedidosTipo', tipoSonido);
            reproducir(tipoSonido);
        });
    });
    if (barraVolumen) {
        barraVolumen.value = String(volumenSonido);
        barraVolumen.addEventListener('change', () => {
            volumenSonido = parseInt(barraVolumen.value, 10) || 70;
            guardar('sonidoPedidosVolumen', String(volumenSonido));
            reproducir(tipoSonido);
        });
    }
    document.querySelectorAll('[data-sonido-probar]').forEach((boton) => {
        boton.addEventListener('click', () => reproducir(tipoSonido));
    });
    pintarSonido();
    ['pointerdown', 'keydown'].forEach((tipo) => {
        window.addEventListener(tipo, prepararAudio, { once: true });
    });

    function avisarEnSegundoPlano(pendientes) {
        if (!document.hidden || !('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        new Notification('¡Nuevo pedido!', {
            body: `Tienes ${pendientes} ${pendientes === 1 ? 'pedido pendiente' : 'pedidos pendientes'}.`,
            icon: 'assets/img/icon-192.png',
            tag: 'pedidos',
        });
    }

    // En la página de pedidos se actualiza la lista sin recargar (así el sonido sigue desbloqueado)
    async function actualizarListaPedidos() {
        const respuesta = await fetch('index.php?route=pedidos', { cache: 'no-store' });
        const html = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
        const nueva = html.querySelector('[data-pedidos-lista]');
        if (nueva) {
            listaPedidos.replaceChildren(...Array.from(nueva.childNodes, (nodo) => document.adoptNode(nodo)));
            horaLocal(listaPedidos);
            const primero = listaPedidos.querySelector('.order-card');
            if (primero) {
                primero.classList.add('recien-llegado');
            }
        }
    }

    async function revisar() {
        try {
            const respuesta = await fetch('index.php?route=pedidos-pendientes', { cache: 'no-store' });
            if (!respuesta.ok) {
                return;
            }
            const { pending } = await respuesta.json();
            insignias.forEach((insignia) => {
                insignia.textContent = String(pending);
                insignia.hidden = pending === 0;
            });
            document.title = pending > 0 ? `(${pending}) ${tituloOriginal}` : tituloOriginal;

            if (anterior !== null && pending > anterior) {
                timbre();
                avisarEnSegundoPlano(pending);
                if (listaPedidos) {
                    await actualizarListaPedidos();
                }
            }
            anterior = pending;
        } catch (error) {
            // Sin conexión: se vuelve a intentar en la siguiente revisión
        }
    }

    revisar();
    setInterval(revisar, 10000);
})();
