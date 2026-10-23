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

        $host = env_value('APP_DB_HOST', '127.0.0.1');
        $port = env_value('APP_DB_PORT', '3306');
        $database = env_value('APP_DB_NAME', 'loginb_db');
        $username = env_value('APP_DB_USER', 'root');
        $password = env_value('APP_DB_PASSWORD', '');
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
        self::installSchema($schemaPath);

        return self::$connection;
    }

    /**
     * Crea las tablas solo cuando db/schema.sql cambia, no en cada petición.
     * La versión aplicada se guarda en db/.schema-version.
     */
    private static function installSchema(string $schemaPath): void
    {
        $markerPath = dirname($schemaPath) . DIRECTORY_SEPARATOR . '.schema-version';
        $version = filemtime($schemaPath) . '-' . filesize($schemaPath);
        if (is_file($markerPath) && file_get_contents($markerPath) === $version) {
            return;
        }

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
        @file_put_contents($markerPath, $version);
    }
}
