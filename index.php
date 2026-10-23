<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

use App\Controllers\LoginController;
use App\Controllers\MenuController;
use App\Controllers\PasswordRecoveryController;
use App\Controllers\PublicMenuController;
use App\Controllers\RegisterController;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\PasswordResetToken;
use App\Models\PhotoStorage;
use App\Models\Restaurant;
use App\Models\User;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$route = $_GET['route'] ?? 'inicio';

if (!is_string($route)) {
    http_response_code(404);
    render('errors/404', 'Página no encontrada');
    exit;
}

// Estas rutas no necesitan base de datos.
if ($route === 'inicio') {
    render('landing', 'Menú digital para restaurantes');
    exit;
}
if ($route === 'manifest') {
    send_manifest(app_name(), app_name(), 'index.php?route=home', 'index.php?app=panel');
    exit;
}

$pdo = Database::connection();
$users = new User($pdo);
$resetTokens = new PasswordResetToken($pdo);
$restaurants = new Restaurant($pdo);
$menuItems = new MenuItem($pdo);
$orders = new Order($pdo);
$menu = new MenuController($restaurants, $menuItems, $orders, new PhotoStorage());
$carta = new PublicMenuController($restaurants, $menuItems, $orders);
$adminEmail = strtolower(env_value('APP_ADMIN_EMAIL', 'admindash@gmail.com'));
if ($route === 'login' || $route === 'register') {
    $users->ensureExists($adminEmail, env_value('APP_ADMIN_PASSWORD'));
}

switch ($route) {
    case 'login':
        (new LoginController($users))->handle($method);
        break;
    case 'register':
        (new RegisterController($users, $adminEmail))->handle($method);
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
    case 'menu':
        $menu->index();
        break;
    case 'menu-ajustes':
        $menu->saveSettings($method);
        break;
    case 'menu-producto':
        $menu->saveItem($method);
        break;
    case 'menu-producto-eliminar':
        $menu->deleteItem($method);
        break;
    case 'menu-link':
        $menu->generateLink($method);
        break;
    case 'pedidos':
        $menu->orders();
        break;
    case 'pedido-estado':
        $menu->updateOrder($method);
        break;
    case 'pedidos-pendientes':
        $menu->pendingCount();
        break;
    case 'carta':
        $carta->show();
        break;
    case 'carta-manifest':
        $carta->manifest();
        break;
    case 'carta-pedido':
        $carta->placeOrder($method);
        break;
    default:
        http_response_code(404);
        render('errors/404', 'Página no encontrada');
}
