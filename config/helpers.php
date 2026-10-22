<?php
declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render(string $page, string $title, array $data = []): void
{
    $viewFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views'
        . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page) . '.php';

    if (!is_file($viewFile)) {
        throw new RuntimeException('No existe la vista solicitada.');
    }

    $pageTitle = $title;
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views'
        . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
}

function redirect(string $route, array $query = []): void
{
    $query = ['route' => $route] + $query;
    header('Location: index.php?' . http_build_query($query));
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_is_valid(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';

    return is_string($submitted)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted);
}

function require_valid_csrf(): void
{
    if (!csrf_is_valid()) {
        http_response_code(403);
        render('errors/403', 'Solicitud no válida');
        exit;
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function app_url(array $query = []): string
{
    $baseUrl = getenv('APP_BASE_URL');
    if (!is_string($baseUrl) || $baseUrl === '') {
        $baseUrl = 'http://localhost/LoginB';
    }

    return rtrim($baseUrl, '/') . '/index.php?' . http_build_query($query);
}
