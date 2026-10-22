<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

final class RegisterController
{
    public function __construct(private User $users)
    {
    }

    public function handle(string $method): void
    {
        if ($method !== 'POST') {
            render('auth/register', 'Crear cuenta');
            return;
        }

        require_valid_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors[] = 'Ingresa un correo electrónico válido.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'La contraseña debe tener entre 8 y 72 bytes.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
        if ($errors === [] && $this->users->findByEmail($email) !== null) {
            $errors[] = 'Ya existe una cuenta con ese correo.';
        }

        if ($errors !== []) {
            render('auth/register', 'Crear cuenta', [
                'errors' => $errors,
                'email' => $email,
            ]);
            return;
        }

        $this->users->create($email, password_hash($password, PASSWORD_DEFAULT));
        set_flash('success', 'Cuenta creada. Ya puedes iniciar sesión.');
        redirect('login');
    }
}
