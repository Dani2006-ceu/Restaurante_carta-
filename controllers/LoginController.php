<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

final class LoginController
{
    public function __construct(private User $users)
    {
    }

    public function handle(string $method): void
    {
        if (isset($_SESSION['user_id'])) {
            redirect('home');
        }

        if ($method !== 'POST') {
            render('auth/login', 'Iniciar sesión');
            return;
        }

        require_valid_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->users->findByEmail($email)
            : null;

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            render('auth/login', 'Iniciar sesión', [
                'error' => 'Correo o contraseña incorrectos.',
                'email' => $email,
            ]);
            return;
        }

        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        csrf_token();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_email'] = $user['email'];
        redirect('home');
    }

    public function home(): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
            redirect('login');
        }

        render('home', 'Inicio');
    }

    public function logout(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            render('errors/405', 'Método no permitido');
            return;
        }

        require_valid_csrf();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
        redirect('login');
    }
}
