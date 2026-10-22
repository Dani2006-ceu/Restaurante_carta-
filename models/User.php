<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower($email)]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function create(string $email, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, created_at)
             VALUES (:email, :password_hash, :created_at)'
        );
        $statement->execute([
            'email' => strtolower($email),
            'password_hash' => $passwordHash,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
