<?php
$flash = take_flash();
$loggedIn = isset($_SESSION['user_id']);
$currentRoute = is_string($_GET['route'] ?? null) ? $_GET['route'] : 'inicio';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#15100d">
    <title><?= h($pageTitle) ?> | <?= h(app_name()) ?></title>
    <link rel="manifest" href="index.php?route=manifest">
    <link rel="icon" href="assets/img/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= h(app_name()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="assets/app.css">
    <script src="assets/js.js" defer></script>
    <script src="assets/fondo.js" defer></script>
    <script src="assets/pwa.js" defer></script>
    <?php if ($loggedIn): ?>
        <script src="assets/panel.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <div class="fondo" aria-hidden="true"><canvas></canvas></div>
    <header class="site-header">
        <a class="brand" href="index.php"><?= logo_svg() ?><span><?= h(app_name()) ?></span></a>
        <?php if (!$loggedIn): ?>
            <div class="header-actions">
                <button class="button button-secondary button-small install-app" type="button" data-instalar hidden>Descargar app</button>
                <?php if ($currentRoute !== 'login'): ?>
                    <a class="button header-login" href="index.php?route=login">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <nav class="site-nav" aria-label="Menú principal">
                <a href="index.php?route=home"<?= $currentRoute === 'home' ? ' aria-current="page"' : '' ?>>Inicio</a>
                <a href="index.php?route=menu"<?= $currentRoute === 'menu' ? ' aria-current="page"' : '' ?>>Mi menú</a>
                <a href="index.php?route=pedidos"<?= $currentRoute === 'pedidos' ? ' aria-current="page"' : '' ?>>
                    Pedidos <span class="nav-badge" data-pedidos-pendientes hidden></span>
                </a>
                <button class="button-link boton-sonido" type="button" data-sonido aria-pressed="true">🔔 Sonido</button>
                <button class="button button-secondary button-small install-app" type="button" data-instalar hidden>Descargar app</button>
                <form action="index.php?route=logout" method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="button-link" type="submit">Salir</button>
                </form>
            </nav>
        <?php endif; ?>
    </header>
    <main class="page">
        <?php if ($flash !== null): ?>
            <div class="notice notice-<?= h((string) ($flash['type'] ?? 'success')) ?>" role="status">
                <?= h((string) ($flash['message'] ?? '')) ?>
            </div>
        <?php endif; ?>
        <?php require $viewFile; ?>
    </main>
</body>
</html>
