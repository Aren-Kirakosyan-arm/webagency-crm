<?php
declare(strict_types=1);

/*
 * Local database settings.
 * Common MAMP defaults vary by installation:
 * Windows often uses port 3306; macOS MAMP often uses 8889.
 * Check MAMP's MySQL port and update these values if needed.
 */
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'webagency_crm';
$dbUser = 'root';
$dbPassword = 'root';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('WebAgency CRM database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit(
        '<h1>Database connection failed</h1>' .
        '<p>Check the MySQL port, username, password, and run database/schema.sql in phpMyAdmin.</p>'
    );
}
