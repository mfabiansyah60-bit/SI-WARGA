<?php
// app/Controllers/LaporanController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();
$role = $user['role'];

// Cek akses: Warga gak boleh masuk
if ($role === 'warga') {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak!', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

// =======================================================
// HELPER: Eksekusi query (Sekarang pakai parameter biar aman)
// =======================================================
function safeCount($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

// =======================================================
// TENTUKAN FILTER BERDASARKAN ROLE YANG LOGIN
// =======================================================
$filter_users  = ""; // Filter buat tabel `users`
$params_users  = [];

$filter_relasi = ""; // Filter buat tabel yang punya relasi ke `users` (misal: pengaduan, surat)
$params_relasi = [];

if ($role === 'rw') {
    // Kalau RW, filter berdasarkan RW dia
    $filter_users = " AND rw = ?";
    $params_users = [$user['rw']];

    $filter_relasi = " AND u.rw = ?";
    $params_relasi = [$user['rw']];

} elseif ($role === 'rt') {
    // Kalau RT, filter berdasarkan RT & RW dia
    $filter_users = " AND rw = ? AND rt = ?";
    $params_users = [$user['rw'], $user['rt']];

    $filter_relasi = " AND u.rw = ? AND u.rt = ?";
    $params_relasi = [$user['rw'], $user['rt']];
}
// Jika Super Admin / Desa, filter_users & filter_relasi dibiarkan kosong (artinya ambil semua data)

// =======================================================
// HITUNG STATISTIK
// =======================================================

// 1. Total User & Warga (Sesuai Wilayah)
$total_users = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE 1=1 $filter_users", $params_users);
$total_warga = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'warga' $filter_users", $params_users);

// 2. Total RT & RW
if ($role === 'rt') {
    $total_rt = 1; // RT cuma punya 1 RT
    $total_rw = 1; // RT cuma ada di 1 RW
} elseif ($role === 'rw') {
    // RW ngitung ada berapa RT unik yang terdaftar di wilayahnya
    $total_rt = safeCount($pdo, "SELECT COUNT(DISTINCT rt) FROM users WHERE role='warga' AND rw = ?", [$user['rw']]);
    $total_rw = 1; // RW cuma punya 1 RW
} else {
    // Super Admin / Desa
    $total_rt = safeCount($pdo, "SELECT COUNT(*) FROM rt");
    $total_rw = safeCount($pdo, "SELECT COUNT(*) FROM rw");
}

$total_desa = safeCount($pdo, "SELECT COUNT(*) FROM desa");

// 3. Pengaduan (Butuh JOIN ke tabel users biar bisa difilter wilayahnya)
$total_pengaduan = safeCount($pdo, "SELECT COUNT(*) FROM pengaduan p JOIN users u ON p.user_id = u.id WHERE 1=1 $filter_relasi", $params_relasi);
$pengaduan_selesai = safeCount($pdo, "SELECT COUNT(*) FROM pengaduan p JOIN users u ON p.user_id = u.id WHERE p.status = 'selesai' $filter_relasi", $params_relasi);

// 4. Layanan (Biasanya global buat semua, gak difilter per wilayah)
$total_layanan = safeCount($pdo, "SELECT COUNT(*) FROM pelayanan");

// 5. Surat (Kombinasi surat_terbit & surat_pengajuan dengan filter wilayah relasi)
$count_terbit = (int)safeCount($pdo, "SELECT COUNT(*) FROM surat_terbit s JOIN users u ON s.user_id = u.id WHERE 1=1 $filter_relasi", $params_relasi);
$count_pengajuan = (int)safeCount($pdo, "SELECT COUNT(*) FROM surat_pengajuan s JOIN users u ON s.user_id = u.id WHERE 1=1 $filter_relasi", $params_relasi);
$total_surat = $count_terbit + $count_pengajuan;

// =======================================================
// USER PER ROLE (Tabel di bawah)
// =======================================================
try {
    $stmt = $pdo->prepare("SELECT role, COUNT(*) as total FROM users WHERE 1=1 $filter_users GROUP BY role");
    $stmt->execute($params_users);
    $users_by_role = $stmt->fetchAll();
} catch (Exception $e) {
    $users_by_role = [];
}

require_once __DIR__ . '/../../resources/views/laporan/index.php';