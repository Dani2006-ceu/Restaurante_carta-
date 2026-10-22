<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = self::environment('APP_DB_HOST', '127.0.0.1');
        $port = self::environment('APP_DB_PORT', '3306');
        $database = self::environment('APP_DB_NAME', 'loginb_db');
        $username = self::environment('APP_DB_USER', 'root');
        $password = self::environment('APP_DB_PASSWORD', '');
        $schemaPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db'
            . DIRECTORY_SEPARATOR . 'schema.sql';

        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('La extensión pdo_mysql no está habilitada en PHP.');
        }

        if (!is_file($schemaPath) || !is_readable($schemaPath)) {
            throw new RuntimeException('No se puede leer db/schema.sql.');
        }

        if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $database)) {
            throw new RuntimeException('APP_DB_NAME solo puede contener letras, números y guion bajo.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '1049') {
                throw new RuntimeException(
                    "La base de datos '{$database}' no existe. Créala en phpMyAdmin o configura APP_DB_NAME.",
                    0,
                    $exception
                );
            }
            throw $exception;
        }

        self::$connection->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        $schema = file_get_contents($schemaPath);
        if (!is_string($schema)) {
            throw new RuntimeException('No se pudo leer db/schema.sql.');
        }
        foreach (explode(';', $schema) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                self::$connection->exec($statement);
            }
        }

        return self::$connection;
    }

    private static function environment(string $name, string $default): string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
