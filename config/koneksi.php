<?php
// config/koneksi.php - Koneksi Database PDO

$host = '127.0.0.1';
$dbname = 'siwarga';      // Ganti dengan nama database kamu
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Untuk testing (opsional)
    // echo "✅ Koneksi berhasil!";
    
} catch (PDOException $e) {
    die("❌ Koneksi database gagal: " . $e->getMessage());
}