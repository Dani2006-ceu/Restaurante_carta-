<section class="card">
    <header class="card-head">
        <p class="eyebrow">Nueva cuenta</p>
        <h1>Crear cuenta</h1>
        <p class="muted">Solo el administrador puede agregar usuarios. Escribe sus datos para autorizar la cuenta nueva.</p>
    </header>

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

        <fieldset class="form-group">
            <legend>Usuario nuevo</legend>
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="off"
                   value="<?= h($email ?? '') ?>" maxlength="254" required>

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password"
                   autocomplete="new-password" minlength="8" maxlength="72" required>

            <label for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   autocomplete="new-password" minlength="8" maxlength="72" required>
        </fieldset>

        <fieldset class="form-group form-group-admin">
            <legend>Autorización del administrador</legend>
            <label for="admin_email">Correo del administrador</label>
            <input id="admin_email" name="admin_email" type="email" autocomplete="off"
                   value="<?= h($adminEmail ?? '') ?>" maxlength="254" required>

            <label for="admin_password">Contraseña del administrador</label>
            <input id="admin_password" name="admin_password" type="password"
                   autocomplete="off" maxlength="72" required>
        </fieldset>

        <button class="button" type="submit">Crear usuario</button>
    </form>
    <p class="form-footer"><a href="index.php?route=login">Volver a iniciar sesión</a></p>
</section>
