<section class="card">
    <header class="card-head">
        <p class="eyebrow">Acceso a tu cuenta</p>
        <h1>Recuperar contraseña</h1>
        <p class="muted">Te enviaremos un enlace si el correo está registrado.</p>
    </header>

    <form method="post" action="index.php?route=recover">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autocomplete="email"
               maxlength="254" required>
        <button class="button" type="submit">Enviar instrucciones</button>
    </form>
    <p class="form-footer"><a href="index.php?route=login">Volver a iniciar sesión</a></p>
</section>
