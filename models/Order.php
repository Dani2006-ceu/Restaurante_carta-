<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

final class Order
{
    /** Estados en el orden en que se muestran, con su texto para el panel. */
    public const STATUSES = [
        'pendiente' => 'Nuevo',
        'preparando' => 'Preparando',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
    ];
    public const TYPES = [
        'mesa' => 'En mesa',
        'llevar' => 'Para llevar',
        'domicilio' => 'A domicilio',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<int, array{menu_item_id: int, name: string, unit_price: float, quantity: int}> $lines
     */
    public function create(int $restaurantId, array $customer, array $lines): int
    {
        $total = 0.0;
        foreach ($lines as $line) {
            $total += $line['unit_price'] * $line['quantity'];
        }

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO orders
                    (restaurant_id, customer_name, customer_phone, order_type, table_label,
                     note, total, status, created_at)
                 VALUES
                    (:restaurant_id, :customer_name, :customer_phone, :order_type, :table_label,
                     :note, :total, :status, :created_at)'
            );
            $statement->execute([
                'restaurant_id' => $restaurantId,
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'order_type' => $customer['type'],
                'table_label' => $customer['table'],
                'note' => $customer['note'],
                'total' => round($total, 2),
                'status' => 'pendiente',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $orderId = (int) $this->pdo->lastInsertId();

            $lineStatement = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, name, unit_price, quantity)
                 VALUES (:order_id, :menu_item_id, :name, :unit_price, :quantity)'
            );
            foreach ($lines as $line) {
                $lineStatement->execute(['order_id' => $orderId] + $line);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $orderId;
    }

    public function allForRestaurant(int $restaurantId, int $limit = 100): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, customer_name, customer_phone, order_type, table_label, note, total, status, created_at
             FROM orders WHERE restaurant_id = :restaurant_id
             ORDER BY FIELD(status, \'' . implode("', '", array_keys(self::STATUSES)) . '\'), id DESC
             LIMIT ' . $limit
        );
        $statement->execute(['restaurant_id' => $restaurantId]);
        $orders = $statement->fetchAll();
        if ($orders === []) {
            return [];
        }

        $ids = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $lineStatement = $this->pdo->prepare(
            "SELECT order_id, name, unit_price, quantity FROM order_items
             WHERE order_id IN ({$placeholders}) ORDER BY id"
        );
        $lineStatement->execute($ids);

        $linesByOrder = [];
        foreach ($lineStatement->fetchAll() as $line) {
            $linesByOrder[$line['order_id']][] = $line;
        }
        foreach ($orders as &$order) {
            $order['lines'] = $linesByOrder[$order['id']] ?? [];
        }

        return $orders;
    }

    public function countPending(int $restaurantId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM orders WHERE restaurant_id = :restaurant_id AND status = \'pendiente\''
        );
        $statement->execute(['restaurant_id' => $restaurantId]);

        return (int) $statement->fetchColumn();
    }

    public function updateStatus(int $id, int $restaurantId, string $status): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE orders SET status = :status WHERE id = :id AND restaurant_id = :restaurant_id'
        );
        $statement->execute(['status' => $status, 'id' => $id, 'restaurant_id' => $restaurantId]);
    }
}
