<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Restaurant
{
    private const FIELDS = 'id, user_id, name, description, phone, address, opening_hours, currency, public_token';

    public function __construct(private PDO $pdo)
    {
    }

    public function findOrCreateForUser(int $userId): array
    {
        $restaurant = $this->findByUser($userId);
        if ($restaurant !== null) {
            return $restaurant;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO restaurants (user_id, name, created_at)
             VALUES (:user_id, :name, :created_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'name' => 'Mi restaurante',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $this->findByUser($userId) ?? [];
    }

    public function findByToken(string $token): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::FIELDS . ' FROM restaurants WHERE public_token = :token LIMIT 1'
        );
        $statement->execute(['token' => $token]);
        $restaurant = $statement->fetch();

        return is_array($restaurant) ? $restaurant : null;
    }

    public function updateSettings(int $id, array $settings): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE restaurants
             SET name = :name, description = :description, phone = :phone,
                 address = :address, opening_hours = :opening_hours, currency = :currency
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $settings['name'],
            'description' => $settings['description'],
            'phone' => $settings['phone'],
            'address' => $settings['address'],
            'opening_hours' => $settings['opening_hours'],
            'currency' => $settings['currency'],
        ]);
    }

    public function regenerateToken(int $id): string
    {
        $token = bin2hex(random_bytes(16));
        $statement = $this->pdo->prepare('UPDATE restaurants SET public_token = :token WHERE id = :id');
        $statement->execute(['token' => $token, 'id' => $id]);

        return $token;
    }

    private function findByUser(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::FIELDS . ' FROM restaurants WHERE user_id = :user_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $restaurant = $statement->fetch();

        return is_array($restaurant) ? $restaurant : null;
    }
}
