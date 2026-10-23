<div class="landing">
    <section class="landing-hero">
        <?= logo_svg('landing-logo') ?>
        <p class="eyebrow">Menú digital para restaurantes</p>
        <h1>Tu carta, servida <em>en cada mesa</em></h1>
        <p class="muted">Publica tus platos, bebidas y licores con precios y descuentos. Comparte un link o un código QR y recibe los pedidos directamente en tu panel.</p>
        <div class="landing-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="button" href="index.php?route=menu">Ir a mi menú</a>
            <?php else: ?>
                <a class="button" href="index.php?route=login">Iniciar sesión</a>
            <?php endif; ?>
            <button class="button button-secondary install-app" type="button" data-instalar hidden>Descargar app</button>
        </div>
    </section>

    <section class="landing-features" aria-label="Cómo funciona">
        <article class="card landing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 3v8a2 2 0 0 0 2 2h0v8M11 3v6M3 3v6a4 4 0 0 0 4 4M17 21V3c2.5 1 4 3.5 4 7v3h-4"/>
            </svg>
            <h2>Arma tu carta</h2>
            <p class="muted">Entradas, platos de fondo, bebidas, licores y lo que vendas, con foto, precio y descuento.</p>
        </article>
        <article class="card landing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>
            </svg>
            <h2>Comparte link o QR</h2>
            <p class="muted">Tus clientes abren la carta en el celular y pueden guardarla como app, sin cuentas ni contraseñas.</p>
        </article>
        <article class="card landing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
            <h2>Recibe pedidos</h2>
            <p class="muted">Cada pedido llega a tu panel con aviso sonoro: en mesa, para llevar o a domicilio.</p>
        </article>
    </section>
</div>
