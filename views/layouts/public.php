<?php
$flash = take_flash();
$token = (string) $restaurant['public_token'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f6efe3">
    <title><?= h($pageTitle) ?> · Menú</title>
    <link rel="manifest" href="index.php?route=carta-manifest&amp;m=<?= h($token) ?>">
    <link rel="icon" href="assets/img/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= h((string) $restaurant['name']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="assets/carta.css">
    <script src="assets/carta.js" defer></script>
    <script src="assets/pwa.js" defer></script>
</head>
<body>
    <?php if ($flash !== null): ?>
        <div class="carta-aviso carta-aviso-<?= h((string) ($flash['type'] ?? 'success')) ?>" role="status">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>
    <?php require $viewFile; ?>
</body>
</html>
