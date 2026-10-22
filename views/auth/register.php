<section class="card">
    <p class="eyebrow">Nueva cuenta</p>
    <h1>Crear cuenta</h1>
    <p class="muted">Usa un correo válido y una contraseña de al menos 8 caracteres.</p>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?route=register">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="email"
               value="<?= h($email ?? '') ?>" maxlength="254" required>

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password"
               autocomplete="new-password" minlength="8" maxlength="72" required>

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password"
               autocomplete="new-password" minlength="8" maxlength="72" required>

        <button class="button" type="submit">Registrarme</button>
    </form>
    <p class="form-footer"><a href="index.php?route=login">Volver a iniciar sesión</a></p>
</section>
