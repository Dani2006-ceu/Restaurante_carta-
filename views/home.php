<section class="card">
    <p class="eyebrow">Sesión iniciada</p>
    <h1>Hola, <?= h((string) ($_SESSION['user_email'] ?? '')) ?></h1>
    <p class="muted">Tu autenticación básica ya está funcionando. Puedes empezar a construir el resto del proyecto aquí.</p>
</section>
