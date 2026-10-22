<section class="card">
    <p class="eyebrow">Bienvenido</p>
    <h1>Iniciar sesión</h1>
    <p class="muted">Ingresa tus datos para continuar.</p>

    <?php if (isset($error)): ?>
        <div class="notice notice-error" role="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?route=login">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="email"
               value="<?= h($email ?? '') ?>" maxlength="254" required>

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password"
               autocomplete="current-password" required>

        <button class="button" type="submit">Entrar</button>
    </form>

    <nav class="form-links" aria-label="Opciones de cuenta">
        <a href="index.php?route=recover">¿Olvidaste tu contraseña?</a>
        <a href="index.php?route=register">Crear una cuenta</a>
    </nav>
</section>
