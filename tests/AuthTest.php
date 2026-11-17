<?php
// tests/AuthTest.php
// Jalankan: php tests/AuthTest.php

require_once __DIR__ . '/../config/database.php';

$passed = 0;
$failed = 0;

function assert_true($cond, $msg) {
    global $passed, $failed;
    if ($cond) {
        echo "✅ $msg\n";
        $passed++;
    } else {
        echo "❌ $msg\n";
        $failed++;
    }
}

echo "\n🧪 SIWARGA — Auth Tests\n";
echo str_repeat('═', 50) . "\n\n";

// ============================================
// 1. TEST KONEKSI DATABASE
// ============================================
echo "📦 Test 1: Koneksi Database\n";
assert_true($pdo !== null, "PDO terhubung ke database");
assert_true($pdo instanceof PDO, "Instance adalah PDO");

// ============================================
// 2. TEST TABEL USERS
// ============================================
echo "\n📦 Test 2: Tabel Users\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    assert_true($count >= 0, "Tabel users bisa di-query (total: $count user)");
} catch (Exception $e) {
    assert_true(false, "Tabel users error: " . $e->getMessage());
}

// ============================================
// 3. TEST USER DEMO
// ============================================
echo "\n📦 Test 3: User Demo\n";
$emails = ['super@admin.com', 'desa@demo.com', 'rw@demo.com', 'rt@demo.com', 'warga@demo.com'];
foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    assert_true($user !== false, "User '$email' ada di DB");
}

// ============================================
// 4. TEST PASSWORD HASH BCRYPT
// ============================================
echo "\n📦 Test 4: Password Hash\n";
$stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
$stmt->execute(['super@admin.com']);
$hash = $stmt->fetchColumn();

if ($hash) {
    assert_true(strlen($hash) === 60, "Panjang hash = 60 karakter (bcrypt)");
    assert_true(substr($hash, 0, 4) === '$2y$', "Hash mulai dengan \$2y\$ (bcrypt)");
    assert_true(password_verify('admin123', $hash), "Password 'admin123' cocok dengan hash");
    assert_true(!password_verify('salah', $hash), "Password 'salah' TIDAK cocok");
}

// ============================================
// 5. TEST TABEL PENGADUAN
// ============================================
echo "\n📦 Test 5: Tabel Pengaduan\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM pengaduan");
    $count = $stmt->fetchColumn();
    assert_true($count >= 0, "Tabel pengaduan OK (total: $count pengaduan)");
} catch (Exception $e) {
    assert_true(false, "Tabel pengaduan error: " . $e->getMessage());
}

// ============================================
// 6. TEST FUNGSI HELPER
// ============================================
echo "\n📦 Test 6: Helper Functions\n";
require_once __DIR__ . '/../app/Helpers/functions.php';

assert_true(function_exists('isLoggedIn'), "Fungsi isLoggedIn() ada");
assert_true(function_exists('auth'), "Fungsi auth() ada");
assert_true(function_exists('isRole'), "Fungsi isRole() ada");
assert_true(function_exists('canAccess'), "Fungsi canAccess() ada");
assert_true(function_exists('e'), "Fungsi e() ada");
assert_true(function_exists('log_error'), "Fungsi log_error() ada");
assert_true(function_exists('cache_set'), "Fungsi cache_set() ada");

// Test e()
assert_true(e('<script>') === '&lt;script&gt;', "e() escape HTML dengan benar");

// Test tanggal_indo()
assert_true(function_exists('tanggal_indo'), "Fungsi tanggal_indo() ada");

// ============================================
// SUMMARY
// ============================================
echo "\n" . str_repeat('═', 50) . "\n";
echo "📊 Hasil: {$passed} passed, {$failed} failed\n";
echo str_repeat('═', 50) . "\n\n";

exit($failed > 0 ? 1 : 0);