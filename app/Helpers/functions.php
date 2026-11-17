<?php
// app/Helpers/functions.php

// ============================================================
// DEBUG & UTILITIES
// ============================================================
function dd($data) {
    echo "<pre style='background:#f4f4f4;padding:15px;border-radius:8px;'>";
    var_dump($data);
    echo "</pre>";
    die();
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================================
// AUTH & SESSION
// ============================================================
function isLoggedIn() {
    return isset($_SESSION['user']) || isset($_SESSION['user_id']);
}

function auth() {
    return $_SESSION['user'] ?? null;
}

function isRole($role) {
    $user = auth();
    return $user && $user['role'] === $role;
}

function isRoleIn($roles) {
    $user = auth();
    return $user && in_array($user['role'], $roles);
}

function isSuperAdmin() { return isRole('super_admin'); }
function isDesa()       { return isRole('desa'); }
function isRw()         { return isRole('rw'); }
function isRt()         { return isRole('rt'); }
function isWarga()      { return isRole('warga'); }

// Cek akses modul per role
function canAccess($modul) {
    $user = auth();
    if (!$user) return false;

    $role = $user['role'];

    $permissions = [
        'super_admin' => ['dashboard', 'wilayah', 'rt_rw', 'users', 'warga', 'pelayanan', 'pengaduan', 'surat', 'laporan', 'ai', 'ai-config', 'whatsapp-config'],
        'desa'        => ['dashboard', 'wilayah', 'rt_rw', 'warga', 'pelayanan', 'pengaduan', 'surat', 'laporan', 'ai'],
        'rw'          => ['dashboard', 'rt_rw', 'warga', 'pelayanan', 'pengaduan', 'laporan', 'ai'],
        'rt'          => ['dashboard', 'warga', 'pelayanan', 'pengaduan', 'surat', 'laporan', 'ai'],
        'warga'       => ['dashboard', 'pelayanan', 'pengaduan', 'surat', 'ai'],
    ];

    return in_array($modul, $permissions[$role] ?? []);
}

// ============================================================
// FLASH MESSAGE (notifikasi 1x tampil)
// ============================================================
function flash($message = null, $type = 'success') {
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return;
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
// LOGGING — catat error & aktivitas
// ============================================================

/**
 * Log error ke storage/logs/error.log
 */
function log_error($message, $context = []) {
    $logDir = __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

    $logFile = $logDir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $userInfo = isLoggedIn() ? 'user_id=' . ($_SESSION['user_id'] ?? '?') : 'guest';
    $ctx = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $line = "[$timestamp] [$userInfo] $message$ctx" . PHP_EOL;

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Log aktivitas user (login, buat surat, kirim pengaduan, dll)
 */
function log_activity($action, $detail = '') {
    $logDir = __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

    $logFile = $logDir . '/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? '-';
    $userName = $_SESSION['user']['name'] ?? 'guest';
    $line = "[$timestamp] user_id=$userId ($userName) action=$action";
    if ($detail) $line .= " | $detail";
    $line .= PHP_EOL;

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ============================================================
// CACHE — simpan data sementara biar gak query DB terus
// ============================================================

/**
 * Ambil cache. Return null kalau gak ada / expired.
 */
function cache_get($key, $ttl = 300) {
    $dir = __DIR__ . '/../../storage/cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $file = $dir . '/' . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    if (time() - filemtime($file) > $ttl) {
        @unlink($file);
        return null;
    }
    $data = @file_get_contents($file);
    return $data !== false ? @unserialize($data) : null;
}

/**
 * Simpan cache.
 */
function cache_set($key, $value) {
    $dir = __DIR__ . '/../../storage/cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $file = $dir . '/' . md5($key) . '.cache';
    @file_put_contents($file, serialize($value), LOCK_EX);
}

/**
 * Hapus cache tertentu.
 */
function cache_forget($key) {
    $file = __DIR__ . '/../../storage/cache/' . md5($key) . '.cache';
    @unlink($file);
}

/**
 * Hapus semua cache.
 */
function cache_flush() {
    $dir = __DIR__ . '/../../storage/cache';
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*.cache') as $file) {
        @unlink($file);
    }
}

// ============================================================
// FORMATTING HELPERS
// ============================================================

/**
 * Format tanggal Indonesia (2 Feb 2026)
 */
function tanggal_indo($date, $withTime = false) {
    if (!$date) return '-';
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $ts = strtotime($date);
    $result = date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) $result .= ' ' . date('H:i', $ts);
    return $result;
}

/**
 * Waktu relatif (2 menit lalu, 1 jam lalu, dll)
 */
function waktu_lalu($datetime) {
    if (!$datetime) return '-';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'Baru saja';
    if ($diff < 3600)    return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)   return floor($diff / 3600) . ' jam lalu';
    if ($diff < 2592000) return floor($diff / 86400) . ' hari lalu';
    return tanggal_indo($datetime);
}

/**
 * Potong teks panjang dengan ...
 */
function str_limit($text, $length = 100, $end = '...') {
    $text = strip_tags($text ?? '');
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $end;
}

/**
 * Buat slug dari teks
 */
function slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Format ukuran file
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}