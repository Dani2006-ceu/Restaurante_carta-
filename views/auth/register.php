<style>
    /* Fondo animado solo para la página de registro */
    body {
        background: linear-gradient(-45deg, #2a0a4a, #6a1b9a, #9c27b0, #c2185b, #4a148c, #3949ab);
        background-size: 400% 400%;
        animation: registro-fondo 15s ease infinite;
        overflow-x: hidden;
    }

    body::before,
    body::after {
        content: "";
        position: fixed;
        z-index: 0;
        border-radius: 50%;
        filter: blur(70px);
        opacity: 0.55;
        pointer-events: none;
    }

    body::before {
        top: -120px;
        left: -100px;
        width: 380px;
        height: 380px;
        background: #e040fb;
        animation: registro-burbuja 18s ease-in-out infinite;
    }

    body::after {
        right: -120px;
        bottom: -120px;
        width: 420px;
        height: 420px;
        background: #7c4dff;
        animation: registro-burbuja 22s ease-in-out infinite reverse;
    }

    .site-header,
    .page {
        position: relative;
        z-index: 1;
    }

    .site-header {
        background: rgb(255 255 255 / 12%);
        backdrop-filter: blur(10px);
    }

    .site-header .brand {
        color: #fff;
    }

    .card {
        border-color: rgb(255 255 255 / 35%);
        background: rgb(255 255 255 / 92%);
        box-shadow: 0 20px 60px rgb(30 0 60 / 45%);
    }

    .card .button {
        background: linear-gradient(90deg, #7b1fa2, #c2185b);
        border: none;
    }

    @keyframes registro-fondo {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes registro-burbuja {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(120px, 80px) scale(1.15); }
        66% { transform: translate(-60px, 140px) scale(0.9); }
    }

    @media (prefers-reduced-motion: reduce) {
        body,
        body::before,
        body::after {
            animation: none;
        }
    }
</style>

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
