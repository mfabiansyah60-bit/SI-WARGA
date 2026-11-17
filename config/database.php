<?php
// config/database.php

// Load .env jika ada
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, "\"'");
            if (!array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

$host     = getenv('DB_HOST') ?: '127.0.0.1';
$dbname   = getenv('DB_DATABASE') ?: 'siwarga';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (php_sapi_name() === 'cli') {
            $pdo = new PDO("sqlite::memory:");
        } else {
            die("❌ Koneksi database gagal: " . $e->getMessage());
        }
    }
}

// ============================================
// KONFIGURASI GEMINI AI
// ============================================
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
}
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-3.5-flash');
}
if (!defined('GEMINI_ENDPOINT')) {
    define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');
}