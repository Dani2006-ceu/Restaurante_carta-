<?php
declare(strict_types=1);

/**
 * Lee la configuración desde variables de entorno o, si no existen (por ejemplo
 * en InfinityFree), desde config/env.php.
 */
function env_value(string $name, string $default = ''): string
{
    static $file = null;
    if ($file === null) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'env.php';
        $file = is_file($path) ? (array) require $path : [];
    }

    $value = getenv($name);
    if (is_string($value) && $value !== '') {
        return $value;
    }
    $value = $file[$name] ?? '';

    return is_scalar($value) && (string) $value !== '' ? (string) $value : $default;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render(string $page, string $title, array $data = [], string $layout = 'main'): void
{
    $viewFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views'
        . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page) . '.php';

    if (!is_file($viewFile)) {
        throw new RuntimeException('No existe la vista solicitada.');
    }

    $pageTitle = $title;
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views'
        . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
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

function base_url(): string
{
    $baseUrl = env_value('APP_BASE_URL');
    if ($baseUrl !== '') {
        return rtrim($baseUrl, '/');
    }

    $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $path = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

    return ($https ? 'https' : 'http') . '://' . $host . $path;
}

function app_url(array $query = []): string
{
    return base_url() . '/index.php?' . http_build_query($query);
}

function require_post(string $method): void
{
    if ($method !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        render('errors/405', 'Método no permitido');
        exit;
    }
}

/**
 * Texto de un campo del formulario, sin espacios a los lados y recortado a $maxLength.
 */
function post_text(string $field, int $maxLength): string
{
    return mb_substr(trim((string) ($_POST[$field] ?? '')), 0, $maxLength);
}

/**
 * Convierte en null los campos indicados que estén vacíos.
 */
function null_if_empty(array $data, array $keys): array
{
    foreach ($keys as $key) {
        $data[$key] = $data[$key] === '' ? null : $data[$key];
    }

    return $data;
}

function require_login(): int
{
    if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
        redirect('login');
    }

    return (int) $_SESSION['user_id'];
}

/**
 * Logo (cloche de restaurante) en SVG; toma el color del texto con currentColor.
 */
function logo_svg(string $class = 'brand-logo'): string
{
    return '<svg class="' . h($class) . '" viewBox="0 0 64 64" fill="none" stroke="currentColor" '
        . 'stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<circle cx="32" cy="32" r="29.5" stroke-width="1.4" opacity=".5"/>'
        . '<circle cx="32" cy="21" r="2.2"/><path d="M15 41a17 17 0 0 1 34 0z"/>'
        . '<path d="M21.5 35.5a11 11 0 0 1 6.5-7.5" opacity=".55"/><path d="M11 45.5h42"/></svg>';
}

function app_name(): string
{
    return env_value('APP_NAME', 'Carta Digital');
}

/**
 * Manifiesto PWA: permite instalar el sitio como app en el celular o la computadora.
 */
function send_manifest(
    string $name,
    string $shortName,
    string $startUrl,
    string $id,
    string $color = '#15100d'
): void {
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: no-cache');
    echo json_encode([
        'id' => $id,
        'name' => $name,
        'short_name' => mb_substr($shortName, 0, 24),
        'start_url' => $startUrl,
        'scope' => './',
        'display' => 'standalone',
        'background_color' => $color,
        'theme_color' => $color,
        'lang' => 'es',
        'icons' => [
            ['src' => 'assets/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => 'assets/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            [
                'src' => 'assets/img/icon-maskable-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function money(float $amount, string $currency): string
{
    return $currency . ' ' . number_format($amount, 2, ',', '.');
}
