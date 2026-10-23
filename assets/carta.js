'use strict';

// Menú público: botones "Pedir" / −  + , barra de total y hoja del pedido
(function () {
    // "Descargar PDF": abre el diálogo de impresión, que en el celular permite guardar como PDF
    document.querySelectorAll('[data-imprimir]').forEach((boton) => {
        boton.addEventListener('click', () => window.print());
    });

    const form = document.querySelector('form.carta');
    if (!form) {
        return;
    }
    document.documentElement.classList.add('js');

    const moneda = form.dataset.moneda || '$';
    const barra = form.querySelector('.carrito-barra');
    const hoja = form.querySelector('.pedido-hoja');
    const lista = form.querySelector('[data-pedido-lista]');
    const productos = Array.from(form.querySelectorAll('[data-producto]'));
    const formato = new Intl.NumberFormat('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const dinero = (valor) => `${moneda} ${formato.format(valor)}`;

    function crearControles(producto) {
        const input = producto.querySelector('input[type="number"]');
        const contenedor = producto.querySelector('.cantidad');
        const pedir = document.createElement('button');
        pedir.type = 'button';
        pedir.className = 'boton-pedir';
        pedir.textContent = 'Pedir';

        const stepper = document.createElement('div');
        stepper.className = 'stepper';
        stepper.innerHTML = '<button type="button" aria-label="Quitar uno">−</button>'
            + '<output aria-live="polite">0</output>'
            + '<button type="button" aria-label="Agregar uno">+</button>';
        const [menos, mas] = stepper.querySelectorAll('button');
        const salida = stepper.querySelector('output');

        const cambiar = (delta) => {
            const valor = Math.max(0, Math.min(parseInt(input.max, 10), (parseInt(input.value, 10) || 0) + delta));
            input.value = String(valor);
            pintar();
            actualizar();
        };
        const pintar = () => {
            const valor = parseInt(input.value, 10) || 0;
            salida.textContent = String(valor);
            pedir.hidden = valor > 0;
            stepper.hidden = valor === 0;
            producto.classList.toggle('seleccionado', valor > 0);
        };

        pedir.addEventListener('click', () => cambiar(1));
        menos.addEventListener('click', () => cambiar(-1));
        mas.addEventListener('click', () => cambiar(1));
        contenedor.append(pedir, stepper);
        producto.pintar = pintar;
        pintar();
    }

    function actualizar() {
        let cantidad = 0;
        let total = 0;
        lista.innerHTML = '';

        for (const producto of productos) {
            const input = producto.querySelector('input[type="number"]');
            const valor = parseInt(input.value, 10) || 0;
            if (valor === 0) {
                continue;
            }
            const precio = parseFloat(producto.dataset.precio) || 0;
            cantidad += valor;
            total += precio * valor;

            const item = document.createElement('li');
            const nombre = document.createElement('span');
            nombre.textContent = `${valor} × ${producto.dataset.nombre}`;
            const subtotal = document.createElement('span');
            subtotal.textContent = dinero(precio * valor);
            const quitar = document.createElement('button');
            quitar.type = 'button';
            quitar.className = 'quitar';
            quitar.setAttribute('aria-label', `Quitar ${producto.dataset.nombre}`);
            quitar.textContent = '×';
            quitar.addEventListener('click', () => {
                input.value = '0';
                producto.pintar();
                actualizar();
            });
            item.append(nombre, subtotal, quitar);
            lista.append(item);
        }

        form.querySelectorAll('[data-total-cantidad]').forEach((el) => {
            el.textContent = `${cantidad} ${cantidad === 1 ? 'producto' : 'productos'}`;
        });
        form.querySelectorAll('[data-total-precio]').forEach((el) => {
            el.textContent = dinero(total);
        });
        barra.hidden = cantidad === 0;
        if (cantidad === 0) {
            cerrarHoja();
        }
    }

    function abrirHoja() {
        hoja.classList.add('abierta');
        document.body.classList.add('sin-scroll');
    }

    function cerrarHoja() {
        hoja.classList.remove('abierta');
        document.body.classList.remove('sin-scroll');
    }

    // Cambia el texto del campo según sea mesa, para llevar o domicilio
    const ubicacion = form.querySelector('#table_label');
    const etiqueta = form.querySelector('[data-etiqueta-ubicacion]');
    const textos = {
        mesa: ['Número de mesa', 'Ej. Mesa 4', false],
        llevar: ['Hora de recogida (opcional)', 'Ej. 20:30', false],
        domicilio: ['Dirección de entrega', 'Calle, número, referencia', true],
    };
    form.querySelectorAll('input[name="order_type"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const [texto, ejemplo, requerido] = textos[radio.value] || textos.mesa;
            etiqueta.textContent = texto;
            ubicacion.placeholder = ejemplo;
            ubicacion.required = requerido;
        });
    });
    ubicacion.placeholder = textos.mesa[1];

    productos.forEach(crearControles);

    // Cada producto aparece suavemente al entrar en pantalla y se marca la categoría visible
    if ('IntersectionObserver' in window) {
        const alVer = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('visible');
                    alVer.unobserve(entrada.target);
                }
            });
        }, { rootMargin: '0px 0px -20px 0px' });
        productos.forEach((producto) => alVer.observe(producto));

        const chips = new Map(Array.from(document.querySelectorAll('.carta-categorias a'))
            .map((chip) => [chip.getAttribute('href').slice(1), chip]));
        const alPasar = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                const chip = chips.get(entrada.target.id);
                if (entrada.isIntersecting && chip) {
                    chips.forEach((otro) => otro.classList.remove('activa'));
                    chip.classList.add('activa');
                    chip.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
                }
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        document.querySelectorAll('.carta-seccion').forEach((seccion) => alPasar.observe(seccion));
    } else {
        productos.forEach((producto) => producto.classList.add('visible'));
    }

    // Al imprimir o guardar como PDF se muestran todos los productos
    window.addEventListener('beforeprint', () => {
        productos.forEach((producto) => producto.classList.add('visible'));
    });
    form.querySelector('[data-abrir-pedido]').addEventListener('click', abrirHoja);
    form.querySelector('[data-cerrar-pedido]').addEventListener('click', cerrarHoja);
    hoja.addEventListener('click', (evento) => {
        if (evento.target === hoja) {
            cerrarHoja();
        }
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            cerrarHoja();
        }
    });
    form.addEventListener('submit', (evento) => {
        if (barra.hidden) {
            evento.preventDefault();
            return;
        }
        form.querySelector('.carta-enviar').disabled = true;
    });
    actualizar();
})();
