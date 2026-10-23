<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;

final class PublicMenuController
{
    public const MAX_QUANTITY = 20;

    public function __construct(
        private Restaurant $restaurants,
        private MenuItem $items,
        private Order $orders
    ) {
    }

    public function show(): void
    {
        $restaurant = $this->findRestaurant();
        $items = array_map(
            static fn (array $item): array => $item + ['final_price' => MenuItem::finalPrice($item)],
            $this->items->allForRestaurant((int) $restaurant['id'], true)
        );

        render('menu/public', $restaurant['name'], [
            'restaurant' => $restaurant,
            'itemsByCategory' => MenuItem::groupByCategory($items),
            'orderSent' => isset($_GET['enviado']),
        ], 'public');
    }

    public function manifest(): void
    {
        $restaurant = $this->findRestaurant();
        $startUrl = 'index.php?route=carta&m=' . $restaurant['public_token'];
        send_manifest('Menú · ' . $restaurant['name'], (string) $restaurant['name'], $startUrl, $startUrl, '#f6efe3');
    }

    public function placeOrder(string $method): void
    {
        $restaurant = $this->findRestaurant();
        $token = (string) $restaurant['public_token'];
        if ($method !== 'POST') {
            redirect('carta', ['m' => $token]);
        }
        require_valid_csrf();

        $available = [];
        foreach ($this->items->allForRestaurant((int) $restaurant['id'], true) as $item) {
            $available[(int) $item['id']] = $item;
        }

        $lines = [];
        $quantities = $_POST['items'] ?? [];
        foreach (is_array($quantities) ? $quantities : [] as $id => $quantity) {
            $id = (int) $id;
            $quantity = min(self::MAX_QUANTITY, max(0, (int) $quantity));
            if ($quantity === 0 || !isset($available[$id])) {
                continue;
            }
            $lines[] = [
                'menu_item_id' => $id,
                'name' => $available[$id]['name'],
                'unit_price' => MenuItem::finalPrice($available[$id]),
                'quantity' => $quantity,
            ];
        }

        $type = (string) ($_POST['order_type'] ?? 'mesa');
        $customer = [
            'name' => post_text('customer_name', 80),
            'phone' => post_text('customer_phone', 30),
            'type' => array_key_exists($type, Order::TYPES) ? $type : 'mesa',
            'table' => post_text('table_label', 120),
            'note' => post_text('note', 500),
        ];

        if ($lines === [] || $customer['name'] === '') {
            set_flash('error', 'Agrega al menos un producto y escribe tu nombre para enviar el pedido.');
            redirect('carta', ['m' => $token]);
        }
        $customer = null_if_empty($customer, ['phone', 'table', 'note']);

        $this->orders->create((int) $restaurant['id'], $customer, $lines);
        redirect('carta', ['m' => $token, 'enviado' => 1]);
    }

    private function findRestaurant(): array
    {
        $token = $_GET['m'] ?? $_POST['m'] ?? '';
        $restaurant = is_string($token) && preg_match('/\A[a-f0-9]{32}\z/', $token)
            ? $this->restaurants->findByToken($token)
            : null;

        if ($restaurant === null) {
            http_response_code(404);
            render('errors/404', 'Menú no disponible');
            exit;
        }

        return $restaurant;
    }
}
