<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

final class PasswordResetToken
{
    public function __construct(private PDO $pdo)
    {
    }

    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $now = gmdate('Y-m-d H:i:s');
        $expiresAt = gmdate('Y-m-d H:i:s', time() + 3600);

        $this->pdo->beginTransaction();
        try {
            $invalidate = $this->pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = :now
                 WHERE user_id = :user_id AND used_at IS NULL'
            );
            $invalidate->execute(['now' => $now, 'user_id' => $userId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO password_reset_tokens
                 (user_id, token_hash, expires_at, created_at)
                 VALUES (:user_id, :token_hash, :expires_at, :created_at)'
            );
            $insert->execute([
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => $now,
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        return $token;
    }

    public function consumeAndUpdatePassword(string $token, string $passwordHash): bool
    {
        $now = gmdate('Y-m-d H:i:s');
        $tokenHash = hash('sha256', $token);

        $this->pdo->beginTransaction();
        try {
            $lookup = $this->pdo->prepare(
                'SELECT id, user_id
                 FROM password_reset_tokens
                 WHERE token_hash = :token_hash
                   AND used_at IS NULL
                   AND expires_at > :now
                 LIMIT 1'
            );
            $lookup->execute(['token_hash' => $tokenHash, 'now' => $now]);
            $record = $lookup->fetch();

            if (!is_array($record)) {
                $this->pdo->rollBack();
                return false;
            }

            $consume = $this->pdo->prepare(
                'UPDATE password_reset_tokens SET used_at = :now
                 WHERE id = :id AND used_at IS NULL AND expires_at > :expires_after'
            );
            $consume->execute([
                'now' => $now,
                'id' => $record['id'],
                'expires_after' => $now,
            ]);

            if ($consume->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET password_hash = :password_hash WHERE id = :user_id'
            );
            $update->execute([
                'password_hash' => $passwordHash,
                'user_id' => $record['user_id'],
            ]);

            $this->pdo->commit();
            return $update->rowCount() === 1;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
