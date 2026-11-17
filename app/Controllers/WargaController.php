<?php
// app/Controllers/WargaController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }

$user = auth();
$role = $user['role'] ?? 'warga';

// Proteksi Hak Akses: Hanya perangkat desa (Super Admin, Desa, RW, RT) yang boleh mengakses
if (!in_array($role, ['super_admin', 'desa', 'rw', 'rt'])) {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak! Anda tidak memiliki izin untuk melihat data warga.', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$rw_user = trim($user['rw'] ?? '');
$rt_user = trim($user['rt'] ?? '');

// ============================================
// AMBIL DATA WARGA SESUAI ROLE
// ============================================
try {
    if ($role === 'super_admin' || $role === 'desa') {
        // Super admin & desa: SEMUA warga
        $stmt = $pdo->query("
            SELECT u.*, 
                   d.nama AS desa_nama
            FROM users u
            LEFT JOIN desa d ON u.desa_id = d.id
            WHERE u.role = 'warga'
            ORDER BY u.created_at DESC
        ");
        $warga_list = $stmt->fetchAll();
        $scope_label = 'Semua Warga Desa';

    } elseif ($role === 'rw' && $rw_user !== '') {
        // Ketua RW: filter by RW (DI-FIX PAKAI LPAD)
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   d.nama AS desa_nama
            FROM users u
            LEFT JOIN desa d ON u.desa_id = d.id
            WHERE u.role = 'warga' 
              AND LPAD(TRIM(u.rw), 2, '0') = LPAD(TRIM(?), 2, '0')
            ORDER BY u.rt ASC, u.created_at DESC
        ");
        $stmt->execute([$rw_user]);
        $warga_list = $stmt->fetchAll();
        $scope_label = "Warga RW $rw_user";

    } elseif ($role === 'rt' && $rt_user !== '') {
        // Ketua RT: filter by RT + RW (DI-FIX PAKAI LPAD)
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   d.nama AS desa_nama
            FROM users u
            LEFT JOIN desa d ON u.desa_id = d.id
            WHERE u.role = 'warga' 
              AND LPAD(TRIM(u.rt), 2, '0') = LPAD(TRIM(?), 2, '0') 
              AND LPAD(TRIM(u.rw), 2, '0') = LPAD(TRIM(?), 2, '0')
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$rt_user, $rw_user]);
        $warga_list = $stmt->fetchAll();
        $scope_label = "Warga RT $rt_user / RW $rw_user";

    } else {
        // Fallback (user.rw / user.rt kosong)
        $stmt = $pdo->query("
            SELECT u.*, 
                   d.nama AS desa_nama
            FROM users u
            LEFT JOIN desa d ON u.desa_id = d.id
            WHERE u.role = 'warga'
            ORDER BY u.created_at DESC
        ");
        $warga_list = $stmt->fetchAll();
        $scope_label = 'Semua Warga (filter belum di-set)';
    }

} catch (Exception $e) {
    $warga_list = [];
    $scope_label = '-';
    log_error('WargaController error', ['error' => $e->getMessage()]);
}

$total_warga = count($warga_list);
$page_title = 'Data Warga';

require_once __DIR__ . '/../../resources/views/warga/index.php';