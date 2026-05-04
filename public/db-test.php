<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "PHP OK\n";
echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'OK' : 'NO') . "\n";
echo "mbstring: " . (extension_loaded('mbstring') ? 'OK' : 'NO') . "\n";

echo "DB_HOST: " . Config::get('DB_HOST', 'NO DEFINIDO') . "\n";
echo "DB_PORT: " . Config::get('DB_PORT', 'NO DEFINIDO') . "\n";
echo "DB_NAME: " . Config::get('DB_NAME', 'NO DEFINIDO') . "\n";
echo "DB_USER: " . Config::get('DB_USER', 'NO DEFINIDO') . "\n";

try {
    $pdo = Database::connection();
    echo "DB CONNECTION: OK\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM leads");
    echo "LEADS TABLE: OK\n";
    echo "LEADS COUNT: " . $stmt->fetchColumn() . "\n";
} catch (Throwable $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
}
