<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;

final class PasswordRecoveryController
{
    public function __construct(
        private User $users,
        private PasswordResetToken $resetTokens
    ) {
    }

    public function recover(string $method): void
    {
        if ($method !== 'POST') {
            render('auth/recover', 'Recuperar contraseña');
            return;
        }

        require_valid_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->users->findByEmail($email)
            : null;

        if ($user !== null) {
            $token = $this->resetTokens->issue((int) $user['id']);
            $resetUrl = app_url(['route' => 'reset-password', 'token' => $token]);
            $from = getenv('APP_MAIL_FROM');

            if (is_string($from) && filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $subject = 'Recuperación de contraseña';
                $body = "Para cambiar tu contraseña, abre este enlace (válido por una hora):\n\n"
                    . $resetUrl . "\n";
                $headers = "From: {$from}\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n";

                if (!mail($user['email'], $subject, $body, $headers)) {
                    error_log('No se pudo enviar el correo de recuperación.');
                }
            } else {
                error_log('Define APP_MAIL_FROM para habilitar el envío de recuperación.');
            }
        }

        set_flash(
            'success',
            'Si el correo pertenece a una cuenta, recibirás instrucciones para cambiar la contraseña.'
        );
        redirect('recover');
    }

    public function reset(string $method): void
    {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            http_response_code(400);
            render('auth/reset-password', 'Cambiar contraseña', [
                'error' => 'El enlace no es válido o ya venció.',
                'token' => '',
            ]);
            return;
        }

        if ($method !== 'POST') {
            render('auth/reset-password', 'Cambiar contraseña', ['token' => $token]);
            return;
        }

        require_valid_csrf();
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8 || strlen($password) > 72) {
            render('auth/reset-password', 'Cambiar contraseña', [
                'error' => 'La contraseña debe tener entre 8 y 72 bytes.',
                'token' => $token,
            ]);
            return;
        }
        if ($password !== $confirmation) {
            render('auth/reset-password', 'Cambiar contraseña', [
                'error' => 'Las contraseñas no coinciden.',
                'token' => $token,
            ]);
            return;
        }

        if (!$this->resetTokens->consumeAndUpdatePassword(
            $token,
            password_hash($password, PASSWORD_DEFAULT)
        )) {
            http_response_code(400);
            render('auth/reset-password', 'Cambiar contraseña', [
                'error' => 'El enlace no es válido o ya venció.',
                'token' => '',
            ]);
            return;
        }

        set_flash('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
        redirect('login');
    }
}
