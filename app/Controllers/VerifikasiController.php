<?php
// app/Controllers/VerifikasiController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../Services/WhatsAppNotificationService.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }

$user = auth();
$role = $user['role'];

// ============================================
// NORMALISASI FORMAT RT/RW (BIAR "1" == "01")
// ============================================
$rw_user = str_pad(trim($user['rw'] ?? ''), 2, '0', STR_PAD_LEFT);
$rt_user = str_pad(trim($user['rt'] ?? ''), 2, '0', STR_PAD_LEFT);

// HANYA ROLE INI YANG BISA MASUK
if (!in_array($role, ['rt', 'rw', 'desa', 'super_admin'])) {
    $_SESSION['flash'] = ['message' => '❌ Anda tidak memiliki akses.', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$action = $_GET['action'] ?? 'index';
$id     = (int)($_GET['id'] ?? 0);

// ============================================
// FUNGSI BANTU: CEK APAKAH BOLEH AKSES WARGA INI
// ============================================
function bolehAksesWarga($role, $target, $rt_user, $rw_user) {
    // Normalisasi data target juga
    $target_rt = str_pad(trim($target['rt'] ?? ''), 2, '0', STR_PAD_LEFT);
    $target_rw = str_pad(trim($target['rw'] ?? ''), 2, '0', STR_PAD_LEFT);

    if ($role === 'rt') {
        // RT harus sama persis RT dan RW-nya
        return ($target_rt === $rt_user && $target_rw === $rw_user);
    } elseif ($role === 'rw') {
        // RW harus sama RW-nya (RT bebas)
        return ($target_rw === $rw_user);
    } 
    // Desa & Super Admin bebas akses semua
    return true; 
}

// ============================================
// PROSES APPROVE (SETUJUI)
// ============================================
if ($action === 'approve' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Ambil data target
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'warga'");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if (!$target) {
            $_SESSION['flash'] = ['message' => '❌ Warga tidak ditemukan.', 'type' => 'error'];
            header('Location: /verifikasi'); exit;
        }

        // 2. Cek otorisasi (Apakah RT/RW ini berhak memverifikasi warga tersebut?)
        if (!bolehAksesWarga($role, $target, $rt_user, $rw_user)) {
            $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Anda tidak berhak memverifikasi warga dari wilayah lain.', 'type' => 'error'];
            header('Location: /verifikasi'); exit;
        }

        // 3. Lakukan Approve
        $stmt = $pdo->prepare("UPDATE users SET status_verifikasi = 'active' WHERE id = ?");
        $stmt->execute([$id]);

        // Notifikasi WhatsApp Akun Disetujui
        try {
            WhatsAppNotificationService::sendVerifikasiAkun($id, 'active');
        } catch (Exception $eWa) {
            error_log('WhatsApp sendVerifikasiAkun error: ' . $eWa->getMessage());
        }

        log_activity('verifikasi_approve', "user_id=$id oleh $role (Wilayah: RT $rt_user / RW $rw_user)");
        $_SESSION['flash'] = ['message' => "✅ Warga {$target['name']} berhasil diaktifkan!", 'type' => 'success'];

    } catch (Exception $e) {
        log_error('Verifikasi approve error', ['error' => $e->getMessage()]);
        $_SESSION['flash'] = ['message' => '❌ Gagal memverifikasi.', 'type' => 'error'];
    }

    header('Location: /verifikasi'); exit;
}

// ============================================
// PROSES REJECT (TOLAK)
// ============================================
if ($action === 'reject' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Ambil data target
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'warga'");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if (!$target) {
            $_SESSION['flash'] = ['message' => '❌ Warga tidak ditemukan.', 'type' => 'error'];
            header('Location: /verifikasi'); exit;
        }

        // 2. Cek otorisasi
        if (!bolehAksesWarga($role, $target, $rt_user, $rw_user)) {
            $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Anda tidak berhak menolak warga dari wilayah lain.', 'type' => 'error'];
            header('Location: /verifikasi'); exit;
        }

        // 3. Lakukan Reject (Ubah status jadi rejected)
        $stmt = $pdo->prepare("UPDATE users SET status_verifikasi = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        
        $alasanTolak = trim($_POST['alasan'] ?? ($_POST['catatan'] ?? ''));
        // Notifikasi WhatsApp Akun Ditolak
        try {
            WhatsAppNotificationService::sendVerifikasiAkun($id, 'rejected', $alasanTolak);
        } catch (Exception $eWa) {
            error_log('WhatsApp sendVerifikasiAkun reject error: ' . $eWa->getMessage());
        }

        log_activity('verifikasi_reject', "user_id=$id oleh $role (Wilayah: RT $rt_user / RW $rw_user)");
        $_SESSION['flash'] = ['message' => "❌ Pendaftaran warga {$target['name']} telah ditolak.", 'type' => 'success'];

    } catch (Exception $e) {
        log_error('Verifikasi reject error', ['error' => $e->getMessage()]);
        $_SESSION['flash'] = ['message' => '❌ Gagal menolak warga.', 'type' => 'error'];
    }
    header('Location: /verifikasi'); exit;
}

// ============================================
// AMBIL DAFTAR WARGA PENDING (HANYA YANG BERHAK DILIHAT)
// ============================================
try {
    $params = [];
    
    if ($role === 'rt') {
        // Query pakai LPAD biar DB yang isinya '1' tetep dianggap sama dengan '01'
        $sql = "SELECT * FROM users WHERE role = 'warga' AND status_verifikasi = 'pending' AND LPAD(rt, 2, '0') = ? AND LPAD(rw, 2, '0') = ?";
        $params = [$rt_user, $rw_user];
        $scope_label = "RT $rt_user / RW $rw_user";
    } 
    elseif ($role === 'rw') {
        // Query pakai LPAD
        $sql = "SELECT * FROM users WHERE role = 'warga' AND status_verifikasi = 'pending' AND LPAD(rw, 2, '0') = ?";
        $params = [$rw_user];
        $scope_label = "RW $rw_user";
    } 
    else {
        // Desa & Super Admin bisa lihat semua pending
        $sql = "SELECT * FROM users WHERE role = 'warga' AND status_verifikasi = 'pending'";
        $params = [];
        $scope_label = 'Semua Desa';
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftar_pending = $stmt->fetchAll();

} catch (Exception $e) {
    $daftar_pending = [];
    $scope_label = '-';
    log_error('VerifikasiController error', ['error' => $e->getMessage()]);
}

require_once __DIR__ . '/../../resources/views/verifikasi/index.php'; 