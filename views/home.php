<section class="card">
    <header class="card-head">
        <p class="eyebrow">Sesión iniciada</p>
        <h1>Hola, <?= h((string) ($_SESSION['user_email'] ?? '')) ?></h1>
        <p class="muted">Administra el menú de tu restaurante y recibe los pedidos de tus clientes.</p>
    </header>

    <div class="home-actions">
        <a class="button" href="index.php?route=menu">Mi menú y link / QR</a>
        <a class="button button-secondary" href="index.php?route=pedidos">
            Ver pedidos <span class="nav-badge" data-pedidos-pendientes hidden></span>
        </a>
    </div>
</section>
