<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '5432');
        $database = Config::get('DB_NAME', 'leads_db');
        $user = Config::get('DB_USER', 'leads_app');
        $password = Config::get('DB_PASSWORD', '');
        $sslMode = Config::get('DB_SSLMODE', 'prefer');

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $database, $sslMode);

        self::$pdo = new PDO($dsn, (string) $user, (string) $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
