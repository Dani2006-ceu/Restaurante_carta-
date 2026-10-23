<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class MenuItem
{
    private const FIELDS = 'id, restaurant_id, category, name, description, image_url, price, discount_percent, is_available';

    public function __construct(private PDO $pdo)
    {
    }

    public function allForRestaurant(int $restaurantId, bool $onlyAvailable = false): array
    {
        $sql = 'SELECT ' . self::FIELDS . ' FROM menu_items WHERE restaurant_id = :restaurant_id';
        if ($onlyAvailable) {
            $sql .= ' AND is_available = 1';
        }
        $statement = $this->pdo->prepare($sql . ' ORDER BY category, name');
        $statement->execute(['restaurant_id' => $restaurantId]);

        return $statement->fetchAll();
    }

    /**
     * Agrupa los productos por categoría: ['Bebidas' => [...], 'Postres' => [...]].
     */
    public static function groupByCategory(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $grouped[$item['category']][] = $item;
        }

        return $grouped;
    }

    public function find(int $id, int $restaurantId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::FIELDS . ' FROM menu_items
             WHERE id = :id AND restaurant_id = :restaurant_id LIMIT 1'
        );
        $statement->execute(['id' => $id, 'restaurant_id' => $restaurantId]);
        $item = $statement->fetch();

        return is_array($item) ? $item : null;
    }

    public function save(int $restaurantId, array $item, ?int $id = null): void
    {
        $values = [
            'restaurant_id' => $restaurantId,
            'category' => $item['category'],
            'name' => $item['name'],
            'description' => $item['description'],
            'image_url' => $item['image_url'],
            'price' => $item['price'],
            'discount_percent' => $item['discount_percent'],
            'is_available' => $item['is_available'] ? 1 : 0,
        ];

        if ($id === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO menu_items
                    (restaurant_id, category, name, description, image_url, price,
                     discount_percent, is_available, created_at)
                 VALUES
                    (:restaurant_id, :category, :name, :description, :image_url, :price,
                     :discount_percent, :is_available, :created_at)'
            );
            $statement->execute($values + ['created_at' => gmdate('Y-m-d H:i:s')]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE menu_items
             SET category = :category, name = :name, description = :description,
                 image_url = :image_url, price = :price, discount_percent = :discount_percent,
                 is_available = :is_available
             WHERE id = :id AND restaurant_id = :restaurant_id'
        );
        $statement->execute($values + ['id' => $id]);
    }

    public function delete(int $id, int $restaurantId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM menu_items WHERE id = :id AND restaurant_id = :restaurant_id'
        );
        $statement->execute(['id' => $id, 'restaurant_id' => $restaurantId]);
    }

    public static function finalPrice(array $item): float
    {
        $price = (float) $item['price'];
        $discount = (int) $item['discount_percent'];

        return round($price * (100 - $discount) / 100, 2);
    }
}
