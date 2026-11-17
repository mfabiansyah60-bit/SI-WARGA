<?php
$host = '127.0.0.1';
$dbname = 'siwarga';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_TIMEOUT => 2
    ]);
    echo "MySQL connected successfully!\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "MySQL connection failed: " . $e->getMessage() . "\n";
}

