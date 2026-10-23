<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

final class RegisterController
{
    public function __construct(private User $users, private string $adminEmail)
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
        $adminEmail = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        $errors = [];

        $admin = $adminEmail === strtolower($this->adminEmail)
            ? $this->users->findByEmail($adminEmail)
            : null;
        if ($admin === null || !password_verify($adminPassword, $admin['password_hash'])) {
            render('auth/register', 'Crear cuenta', [
                'errors' => ['El correo o la contraseña del administrador no son correctos.'],
                'email' => $email,
                'adminEmail' => $adminEmail,
            ]);
            return;
        }

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
                'adminEmail' => $adminEmail,
            ]);
            return;
        }

        $this->users->create($email, password_hash($password, PASSWORD_DEFAULT));
        set_flash('success', 'Usuario creado. Ya puede iniciar sesión con su correo y contraseña.');
        redirect('login');
    }
}
