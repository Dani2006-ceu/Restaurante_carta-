<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Controllers\LoginController;
use App\Controllers\PasswordRecoveryController;
use App\Controllers\RegisterController;
use App\Models\PasswordResetToken;
use App\Models\User;

$pdo = Database::connection();
$users = new User($pdo);
$resetTokens = new PasswordResetToken($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$route = $_GET['route'] ?? 'login';

if (!is_string($route)) {
    http_response_code(404);
    render('errors/404', 'Página no encontrada');
    exit;
}

switch ($route) {
    case 'login':
        (new LoginController($users))->handle($method);
        break;
    case 'register':
        (new RegisterController($users))->handle($method);
        break;
    case 'recover':
        (new PasswordRecoveryController($users, $resetTokens))->recover($method);
        break;
    case 'reset-password':
        (new PasswordRecoveryController($users, $resetTokens))->reset($method);
        break;
    case 'logout':
        (new LoginController($users))->logout($method);
        break;
    case 'home':
        (new LoginController($users))->home();
        break;
    default:
        http_response_code(404);
        render('errors/404', 'Página no encontrada');
}
