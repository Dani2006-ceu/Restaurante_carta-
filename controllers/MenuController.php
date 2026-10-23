<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\PhotoStorage;
use App\Models\Restaurant;
use InvalidArgumentException;

final class MenuController
{
    public const CATEGORIES = [
        'Entradas', 'Platos fuertes', 'Sopas', 'Postres', 'Bebidas', 'Jugos',
        'Cervezas', 'Vinos', 'Licores', 'Cócteles', 'Combos', 'Promociones',
    ];

    public function __construct(
        private Restaurant $restaurants,
        private MenuItem $items,
        private Order $orders,
        private PhotoStorage $photos
    ) {
    }

    public function index(): void
    {
        $restaurant = $this->restaurants->findOrCreateForUser(require_login());
        $restaurantId = (int) $restaurant['id'];
        $editId = (int) ($_GET['editar'] ?? 0);
        $localOnly = $this->isLocalHost();

        render('menu/index', 'Mi menú', [
            'restaurant' => $restaurant,
            'itemsByCategory' => MenuItem::groupByCategory($this->items->allForRestaurant($restaurantId)),
            'editing' => $editId > 0 ? $this->items->find($editId, $restaurantId) : null,
            'publicUrl' => $this->publicUrl($restaurant),
            'pendingOrders' => $this->orders->countPending($restaurantId),
            'localOnly' => $localOnly,
            'networkPanelUrl' => $localOnly ? $this->networkPanelUrl() : null,
        ]);
    }

    public function saveSettings(string $method): void
    {
        $restaurant = $this->guardPost($method);
        $settings = [
            'name' => post_text('name', 120),
            'description' => post_text('description', 300),
            'phone' => post_text('phone', 30),
            'address' => post_text('address', 200),
            'opening_hours' => post_text('opening_hours', 120),
            'currency' => post_text('currency', 5),
        ];

        if ($settings['name'] === '') {
            set_flash('error', 'El nombre del restaurante es obligatorio.');
            redirect('menu');
        }
        $settings['currency'] = $settings['currency'] === '' ? '$' : $settings['currency'];
        $settings = null_if_empty($settings, ['description', 'phone', 'address', 'opening_hours']);

        $this->restaurants->updateSettings((int) $restaurant['id'], $settings);
        set_flash('success', 'Datos del restaurante guardados.');
        redirect('menu');
    }

    public function saveItem(string $method): void
    {
        $restaurant = $this->guardPost($method);
        $restaurantId = (int) $restaurant['id'];
        $id = (int) ($_POST['item_id'] ?? 0);
        $price = str_replace(',', '.', trim((string) ($_POST['price'] ?? '')));
        $discount = trim((string) ($_POST['discount_percent'] ?? '0'));
        $item = [
            'category' => post_text('category', 60),
            'name' => post_text('name', 120),
            'description' => post_text('description', 500),
            'image_url' => post_text('image_url', 500),
            'price' => $price,
            'discount_percent' => $discount === '' ? 0 : (int) $discount,
            'is_available' => isset($_POST['is_available']),
        ];
        $existing = $id > 0 ? $this->items->find($id, $restaurantId) : null;
        $upload = $_FILES['photo'] ?? [];

        $errors = [];
        if ($item['name'] === '') {
            $errors[] = 'Escribe el nombre del producto.';
        }
        if ($item['category'] === '') {
            $errors[] = 'Elige o escribe una categoría.';
        }
        if (!is_numeric($price) || (float) $price < 0 || (float) $price > 99999999) {
            $errors[] = 'El precio debe ser un número válido.';
        }
        if (!ctype_digit($discount === '' ? '0' : $discount) || $item['discount_percent'] > 100) {
            $errors[] = 'El descuento debe estar entre 0 y 100 %.';
        }
        if ($item['image_url'] !== ''
            && (!filter_var($item['image_url'], FILTER_VALIDATE_URL)
                || !preg_match('#\Ahttps?://#i', $item['image_url']))) {
            $errors[] = 'La foto debe ser un enlace que empiece por http:// o https://.';
        }
        if ($id > 0 && $existing === null) {
            $errors[] = 'Ese producto ya no existe.';
        }

        // Foto: archivo subido > link pegado > quitar > se conserva la que ya tenía
        $oldPhoto = $existing['image_url'] ?? null;
        $photo = $item['image_url'] !== '' ? $item['image_url'] : (isset($_POST['remove_photo']) ? null : $oldPhoto);
        if ($errors === [] && is_array($upload) && PhotoStorage::wasSent($upload)) {
            try {
                $photo = $this->photos->store($upload);
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            set_flash('error', implode(' ', $errors));
            redirect('menu', $id > 0 ? ['editar' => $id] : []);
        }

        $item['price'] = round((float) $price, 2);
        $item = null_if_empty($item, ['description']);
        $item['image_url'] = $photo;
        $this->items->save($restaurantId, $item, $id > 0 ? $id : null);
        if ($oldPhoto !== $photo) {
            $this->photos->delete($oldPhoto);
        }
        set_flash('success', $id > 0 ? 'Producto actualizado.' : 'Producto agregado al menú.');
        redirect('menu');
    }

    public function deleteItem(string $method): void
    {
        $restaurant = $this->guardPost($method);
        $id = (int) ($_POST['item_id'] ?? 0);
        $item = $this->items->find($id, (int) $restaurant['id']);
        $this->items->delete($id, (int) $restaurant['id']);
        $this->photos->delete($item['image_url'] ?? null);
        set_flash('success', 'Producto eliminado.');
        redirect('menu');
    }

    public function generateLink(string $method): void
    {
        $restaurant = $this->guardPost($method);
        $this->restaurants->regenerateToken((int) $restaurant['id']);
        set_flash(
            'success',
            $restaurant['public_token'] === null
                ? 'Link del menú generado. Ya puedes compartirlo o imprimir el QR.'
                : 'Se generó un link nuevo. El link y el QR anteriores ya no funcionan.'
        );
        redirect('menu');
    }

    public function orders(): void
    {
        $restaurant = $this->restaurants->findOrCreateForUser(require_login());

        render('menu/orders', 'Pedidos', [
            'restaurant' => $restaurant,
            'orders' => $this->orders->allForRestaurant((int) $restaurant['id']),
        ]);
    }

    public function updateOrder(string $method): void
    {
        $restaurant = $this->guardPost($method);
        $status = (string) ($_POST['status'] ?? '');
        if (array_key_exists($status, Order::STATUSES)) {
            $this->orders->updateStatus((int) ($_POST['order_id'] ?? 0), (int) $restaurant['id'], $status);
        }
        redirect('pedidos');
    }

    public function pendingCount(): void
    {
        $userId = require_login();
        // Solo lee la sesión: la libera ya para no bloquear otras pestañas mientras consulta.
        session_write_close();
        $restaurant = $this->restaurants->findOrCreateForUser($userId);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['pending' => $this->orders->countPending((int) $restaurant['id'])]);
    }

    private function guardPost(string $method): array
    {
        $userId = require_login();
        require_post($method);
        require_valid_csrf();

        return $this->restaurants->findOrCreateForUser($userId);
    }

    private function isLocalHost(): bool
    {
        $host = strtolower(preg_replace('/:\d+\z/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));

        return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
    }

    /**
     * En XAMPP busca la IP de la PC en la red local (192.168.x.x, 10.x.x.x…)
     * para que el QR se pueda abrir desde un celular conectado al mismo Wi-Fi.
     */
    private function networkPanelUrl(): ?string
    {
        $ips = gethostbynamel((string) gethostname()) ?: [];
        foreach ($ips as $ip) {
            $isPrivate = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
            if ($isPrivate) {
                $host = (string) $_SERVER['HTTP_HOST'];
                $port = preg_match('/:\d+\z/', $host, $match) ? $match[0] : '';

                return str_replace('//' . $host, '//' . $ip . $port, app_url(['route' => 'menu']));
            }
        }

        return null;
    }

    private function publicUrl(array $restaurant): ?string
    {
        return $restaurant['public_token'] === null
            ? null
            : app_url(['route' => 'carta', 'm' => $restaurant['public_token']]);
    }
}
