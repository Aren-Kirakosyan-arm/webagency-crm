<?php
declare(strict_types=1);

$host = '127.0.0.1';
$port = '3306'; // Проверь порт MySQL в настройках MAMP.
$dbname = 'webagency_crm';
$username = 'root';
$password = 'root'; // В MAMP пароль может отличаться.

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Не удалось подключиться к базе данных. Проверьте настройки MAMP в includes/config.php.');
}
