<?php
// app/Controllers/SuratController.php

use Dompdf\Dompdf;
use Dompdf\Options;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; 
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../Helpers/Mailer.php';
require_once __DIR__ . '/../Services/WhatsAppNotificationService.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();
$role = $user['role'];

if (!in_array($role, ['super_admin', 'desa', 'rt', 'rw', 'warga'])) {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak!', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$isAdmin = in_array($role, ['super_admin', 'desa', 'rt', 'rw']);
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;
$debug = isset($_GET['debug']) ? true : false; // FITUR DEBUG

// ============================================
// AJUKAN SURAT DARI AI (STATUS PENDING)
// ============================================
if ($action === 'ajukan_ai' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $jenis_surat = trim($_POST['jenis_surat'] ?? 'Surat Keterangan');
        $data_mentah = $_POST['data_json'] ?? '{}';
        $data_array  = json_decode($data_mentah, true);

        if (!$data_array) {
            throw new Exception("Format data JSON tidak valid!");
        }

        $data_normalized = [];
        foreach ($data_array as $key => $value) {
            $data_normalized[strtolower(trim($key))] = trim($value);
        }

        $nama_pemohon = $data_normalized['nama'] ?? $data_normalized['nama lengkap'] ?? '';

        if (empty($nama_pemohon)) {
            throw new Exception("Data Nama Pemohon dari AI tidak ditemukan!");
        }

        $nomor_surat = sprintf("%03d/SK/AI/%s", rand(1, 999), date('Y'));

        $stmt = $pdo->prepare("INSERT INTO surat_pengajuan (user_id, jenis_surat, nomor_surat, data_json, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user['id'], $jenis_surat, $nomor_surat, json_encode($data_normalized)]);
        $newSuratId = $pdo->lastInsertId();

        // Notifikasi WhatsApp ke Petugas RT/RW/Desa/Super Admin
        try {
            WhatsAppNotificationService::sendPengajuanSuratBaru($newSuratId, 'ai', $jenis_surat, $nomor_surat, $user);
        } catch (Exception $eWa) {
            error_log('WhatsApp sendPengajuanSuratBaru error: ' . $eWa->getMessage());
        }
        
        $_SESSION['flash'] = ['message' => '✅ Surat berhasil diajukan! Menunggu persetujuan RT/RW/Admin.', 'type' => 'success'];
        header('Location: /surat');
        exit;

    } catch (Exception $e) {
        $_SESSION['flash'] = ['message' => '❌ Gagal mengajukan: ' . $e->getMessage(), 'type' => 'error'];
        header('Location: /surat'); 
        exit;
    }
}

// ============================================
// SETUJUI / TOLAK SURAT (DENGAN VALIDASI WILAYAH)
// ============================================
if (($action === 'setujui' || $action === 'tolak') && $id && $isAdmin) {
    $type = $_GET['type'] ?? 'ai';
    $status_baru = ($action === 'setujui') ? 'disetujui' : 'ditolak';
    $table = ($type === 'ai') ? 'surat_pengajuan' : 'surat_terbit';

    try {
        $sqlCheck = "SELECT u.rt, u.rw FROM $table s JOIN users u ON s.user_id = u.id WHERE s.id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $target_warga = $stmtCheck->fetch();

        if (!$target_warga) throw new Exception("Data surat tidak ditemukan!");

        $warga_rt = str_pad(trim((string) $target_warga['rt']), 2, '0', STR_PAD_LEFT);
        $warga_rw = str_pad(trim((string) $target_warga['rw']), 2, '0', STR_PAD_LEFT);
        $admin_rt = str_pad(trim((string) $user['rt']), 2, '0', STR_PAD_LEFT);
        $admin_rw = str_pad(trim((string) $user['rw']), 2, '0', STR_PAD_LEFT);

        if ($role === 'rt') {
            if ($warga_rt !== $admin_rt || $warga_rw !== $admin_rw) {
                throw new Exception("Akses Ditolak! Ini surat warga di luar wilayah RT Anda.");
            }
        }
        if ($role === 'rw') {
            if ($warga_rw !== $admin_rw) {
                throw new Exception("Akses Ditolak! Ini surat warga di luar wilayah RW Anda.");
            }
        }

        $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
        $stmt->execute([$status_baru, $id]);
        
        if ($action === 'setujui') {
            try {
                $waResult = WhatsAppNotificationService::sendSuratApprovedWithPDF($id, $type);
                if (!empty($waResult['success'])) {
                    $emailTarget = $waResult['email_target'] ?? 'email pemohon';
                    $waNote = " Berkas PDF surat resmi berhasil dikirim ke $emailTarget dan notifikasi WhatsApp telah dikirimkan ke pemohon.";
                    $_SESSION['flash'] = ['message' => "✅ Surat berhasil disetujui!$waNote", 'type' => 'success'];
                } else {
                    $failReason = $waResult['reason'] ?? ($waResult['message'] ?? 'Pengiriman email gagal');
                    $waNote = " (⚠️ Pengiriman email surat gagal: {$failReason}. Notifikasi WhatsApp berhasil tidak dikirim)";
                    $_SESSION['flash'] = ['message' => "✅ Surat berhasil disetujui!$waNote", 'type' => 'warning'];
                }
            } catch (Exception $eWa) {
                error_log('Surat approval delivery error: ' . $eWa->getMessage());
                $_SESSION['flash'] = ['message' => "✅ Surat berhasil disetujui! (⚠️ Terjadi kendala teknis pengiriman surat)", 'type' => 'warning'];
            }
        } else {
            $alasanTolak = trim($_POST['alasan'] ?? ($_GET['alasan'] ?? ''));
            try {
                WhatsAppNotificationService::sendSuratRejected($id, $type, $alasanTolak);
            } catch (Exception $eWa) {
                error_log('WhatsApp sendSuratRejected error: ' . $eWa->getMessage());
            }
            $_SESSION['flash'] = ['message' => "✅ Surat berhasil ditolak!", 'type' => 'success'];
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['message' => '❌ ' . $e->getMessage(), 'type' => 'error'];
    }
    header('Location: /surat');
    exit;
}

// ============================================
// KIRIM SURAT (EMAIL ATTACH PDF + NOTIFIKASI WA)
// ============================================
if (in_array($action, ['kirim_email', 'kirim_surat']) && $id) {
    if (!$isAdmin) {
        $_SESSION['flash'] = ['message' => '❌ Akses ditolak! Anda tidak memiliki izin untuk mengirim email surat.', 'type' => 'error'];
        header('Location: /surat');
        exit;
    }

    $tipe_surat = $_GET['type'] ?? 'ai';
    $surat_data = null;
    $table = ($tipe_surat === 'ai') ? 'surat_pengajuan' : 'surat_terbit';

    try {
        if ($tipe_surat === 'ai') {
            $stmt = $pdo->prepare("SELECT * FROM surat_pengajuan WHERE id = ?");
            $stmt->execute([$id]);
            $surat_data = $stmt->fetch();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM surat_terbit WHERE id = ?");
            $stmt->execute([$id]);
            $surat_data = $stmt->fetch();
        }

        if (!$surat_data) throw new Exception("Data surat tidak ditemukan!");
        if ($surat_data['status'] !== 'disetujui') throw new Exception("Surat belum disetujui!");

        $user_id_target = $surat_data['user_id'];
        $nomor_surat = $surat_data['nomor_surat'];
        
        $stmtUser = $pdo->prepare("SELECT name, email, rt, rw FROM users WHERE id = ?");
        $stmtUser->execute([$user_id_target]);
        $warga = $stmtUser->fetch();
        if (!$warga) throw new Exception("Data warga tidak ditemukan!");

        // Validasi wilayah teritorial petugas
        $warga_rt = str_pad(trim((string) ($warga['rt'] ?? '')), 2, '0', STR_PAD_LEFT);
        $warga_rw = str_pad(trim((string) ($warga['rw'] ?? '')), 2, '0', STR_PAD_LEFT);
        $admin_rt = str_pad(trim((string) ($user['rt'] ?? '')), 2, '0', STR_PAD_LEFT);
        $admin_rw = str_pad(trim((string) ($user['rw'] ?? '')), 2, '0', STR_PAD_LEFT);

        if ($role === 'rt') {
            if ($warga_rt !== $admin_rt || $warga_rw !== $admin_rw) {
                throw new Exception("Akses Ditolak! Ini surat warga di luar wilayah RT Anda.");
            }
        }
        if ($role === 'rw') {
            if ($warga_rw !== $admin_rw) {
                throw new Exception("Akses Ditolak! Ini surat warga di luar wilayah RW Anda.");
            }
        }
        
        $email_warga = trim($warga['email'] ?? '');
        if (empty($email_warga) || !filter_var($email_warga, FILTER_VALIDATE_EMAIL) || str_contains($email_warga, '@siwarga.local')) {
            throw new Exception("Warga ini tidak memiliki email valid.");
        }

        if ($tipe_surat === 'ai') {
            $jenis_surat = $surat_data['jenis_surat'];
            $jenis = $jenis_surat;
            $nomor = $nomor_surat;
            $data_surat = json_decode($surat_data['data_json'], true) ?: [];
            $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $ts = strtotime($surat_data['created_at']);
            $tanggal_indo = date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
        } else {
            $stmtTpl = $pdo->prepare("SELECT * FROM surat_template WHERE id = ?");
            $stmtTpl->execute([$surat_data['template_id']]);
            $tpl = $stmtTpl->fetch();
            $jenis_surat = $tpl['nama'] ?? 'Surat Resmi';
            $jenis = $jenis_surat;
            $nomor = $nomor_surat;
            
            $replacements = json_decode($surat_data['data_isi'], true) ?: [];
            $isi_surat = str_replace(array_keys($replacements), array_values($replacements), $tpl['isi_template']);
            
            $ts = time();
            $tanggal_indo = date('d F Y', $ts);
        }

        ob_start();
        require __DIR__ . '/../../resources/views/surat/pdf_template.php';
        $html_pdf = ob_get_clean();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html_pdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_output = $dompdf->output();

        $status = Mailer::sendSurat($email_warga, $warga['name'], $jenis_surat, $nomor_surat, $jenis_surat, "Sukamaju", '', $pdf_output);

        if ($status === true) {
            $stmtUpdate = $pdo->prepare("UPDATE $table SET email_sent_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$id]);

            // Setelah Gmail berhasil mengirim, Fonnte mengirim notifikasi WhatsApp bahwa surat sudah dikirim melalui email
            $notifRes = WhatsAppNotificationService::sendSuratEmailNotificationToWhatsApp($id, $tipe_surat, $email_warga, true);
            $notifNote = (!empty($notifRes['success'])) ? " dan notifikasi WhatsApp telah dikirimkan ke pemohon" : "";

            $_SESSION['flash'] = ['message' => "✅ Surat PDF berhasil dikirim ke email: $email_warga$notifNote!", 'type' => 'success'];
        } else {
            // Jika Gmail gagal, jangan kirim notifikasi berhasil
            $_SESSION['flash'] = ['message' => '❌ Gagal mengirim email: ' . $status . '. Notifikasi WhatsApp berhasil tidak dikirim.', 'type' => 'error'];
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
    }
    header('Location: /surat');
    exit;
}

// ============================================
// KIRIM NOTIFIKASI PENGIRIMAN SURAT VIA WHATSAPP
// ============================================
if ($action === 'kirim_wa' && $id) {
    if (!$isAdmin) {
        $_SESSION['flash'] = ['message' => '❌ Akses ditolak! Anda tidak memiliki wewenang untuk mengirim WhatsApp surat.', 'type' => 'error'];
        header('Location: /surat');
        exit;
    }

    $tipe_surat = $_GET['type'] ?? 'ai';
    $table = ($tipe_surat === 'ai') ? 'surat_pengajuan' : 'surat_terbit';

    try {
        $sqlCheck = "SELECT s.email_sent_at, s.user_id, u.rt, u.rw, u.email, u.no_hp, u.no_telp FROM $table s JOIN users u ON s.user_id = u.id WHERE s.id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $target_warga = $stmtCheck->fetch();

        if (!$target_warga) throw new Exception("Data surat tidak ditemukan!");

        $warga_rt = str_pad(trim((string) ($target_warga['rt'] ?? '')), 2, '0', STR_PAD_LEFT);
        $warga_rw = str_pad(trim((string) ($target_warga['rw'] ?? '')), 2, '0', STR_PAD_LEFT);
        $admin_rt = str_pad(trim((string) ($user['rt'] ?? '')), 2, '0', STR_PAD_LEFT);
        $admin_rw = str_pad(trim((string) ($user['rw'] ?? '')), 2, '0', STR_PAD_LEFT);

        if ($role === 'rt') {
            if ($warga_rt !== $admin_rt || $warga_rw !== $admin_rw) {
                throw new Exception("Akses Ditolak! Ini surat warga di luar wilayah RT Anda.");
            }
        }
        if ($role === 'rw') {
            if ($warga_rw !== $admin_rw) {
                throw new Exception("Akses Ditolak! Ini surat warga di luar wilayah RW Anda.");
            }
        }

        // Cek apakah email sudah pernah dikirim
        $emailSentAt = $target_warga['email_sent_at'] ?? null;

        if (empty($emailSentAt)) {
            // Notifikasi WhatsApp HANYA boleh dikirim setelah email berhasil terkirim!
            $_SESSION['flash'] = [
                'message' => '⚠️ Notifikasi WhatsApp belum dapat dikirim karena surat belum pernah dikirim ke email pemohon. Silakan gunakan tombol "Kirim via Email" terlebih dahulu.',
                'type' => 'warning'
            ];
            header('Location: /surat');
            exit;
        }

        // Email sudah terkirim, kirim notifikasi WhatsApp bahwa surat sudah dikirim melalui email (PENTING: tidak lagi mengirim PDF via WA)
        $wargaEmail = trim($target_warga['email'] ?? '');
        $waResult = WhatsAppNotificationService::sendSuratEmailNotificationToWhatsApp($id, $tipe_surat, $wargaEmail, true);
        if (!empty($waResult['success'])) {
            $_SESSION['flash'] = ['message' => "✅ Notifikasi pengiriman email berhasil dikirim via WhatsApp ke pemohon!", 'type' => 'success'];
        } else {
            $failReason = $waResult['reason'] ?? ($waResult['message'] ?? 'Gagal mengirim notifikasi');
            $_SESSION['flash'] = ['message' => "⚠️ Pengiriman notifikasi WhatsApp gagal: {$failReason}.", 'type' => 'error'];
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
    }
    header('Location: /surat');
    exit;
}

// ============================================
// CETAK SURAT (DARI AI)
// ============================================
if ($action === 'cetak') {
    $id_int = (int)$id;
    try {
        $stmt = $pdo->prepare("SELECT s.*, u.rt, u.rw FROM surat_pengajuan s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$id_int]);
        $surat_ai = $stmt->fetch();
        if (!$surat_ai) { throw new Exception("Surat tidak ditemukan"); }

        // Validasi wewenang role
        if ($role === 'warga' && (int)$surat_ai['user_id'] !== (int)$user['id']) {
            $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Anda hanya dapat melihat surat permohonan milik Anda sendiri.', 'type' => 'error'];
            header('Location: /surat');
            exit;
        }
        if ($role === 'rt') {
            $wRt = str_pad(trim((string)$surat_ai['rt']), 2, '0', STR_PAD_LEFT);
            $wRw = str_pad(trim((string)$surat_ai['rw']), 2, '0', STR_PAD_LEFT);
            $uRt = str_pad(trim((string)$user['rt']), 2, '0', STR_PAD_LEFT);
            $uRw = str_pad(trim((string)$user['rw']), 2, '0', STR_PAD_LEFT);
            if ($wRt !== $uRt || $wRw !== $uRw) {
                $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Surat ini di luar wilayah RT Anda.', 'type' => 'error'];
                header('Location: /surat');
                exit;
            }
        }
        if ($role === 'rw') {
            $wRw = str_pad(trim((string)$surat_ai['rw']), 2, '0', STR_PAD_LEFT);
            $uRw = str_pad(trim((string)$user['rw']), 2, '0', STR_PAD_LEFT);
            if ($wRw !== $uRw) {
                $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Surat ini di luar wilayah RW Anda.', 'type' => 'error'];
                header('Location: /surat');
                exit;
            }
        }

        $data_surat = json_decode($surat_ai['data_json'], true) ?: [];
        $jenis_surat = $surat_ai['jenis_surat'];
        $nomor_surat = $surat_ai['nomor_surat'];
        $status_surat = $surat_ai['status'];
        $tanggal_surat = $surat_ai['created_at'];

        require_once __DIR__ . '/../../resources/views/surat/cetak_ai.php'; exit;
    } catch (Exception $e) { 
        echo "<div style='padding:20px; font-family:sans-serif; color:red;'><h2>❌ Terjadi Kesalahan:</h2><p>" . $e->getMessage() . "</p><a href='/surat'>Kembali ke Daftar Surat</a></div>";
        exit; 
    }
}

// ============================================
// CETAK SURAT MANUAL
// ============================================
if ($action === 'cetak_manual' && $id) {
    try {
        $stmt = $pdo->prepare("SELECT s.*, t.nama as template_nama, t.isi_template, u.rt, u.rw FROM surat_terbit s JOIN surat_template t ON s.template_id = t.id JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $surat_manual = $stmt->fetch();
        if (!$surat_manual) { throw new Exception("Data surat manual tidak ditemukan!"); }

        // Validasi wewenang role
        if ($role === 'warga' && (int)$surat_manual['user_id'] !== (int)$user['id']) {
            $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Anda hanya dapat melihat surat permohonan milik Anda sendiri.', 'type' => 'error'];
            header('Location: /surat');
            exit;
        }
        if ($role === 'rt') {
            $wRt = str_pad(trim((string)$surat_manual['rt']), 2, '0', STR_PAD_LEFT);
            $wRw = str_pad(trim((string)$surat_manual['rw']), 2, '0', STR_PAD_LEFT);
            $uRt = str_pad(trim((string)$user['rt']), 2, '0', STR_PAD_LEFT);
            $uRw = str_pad(trim((string)$user['rw']), 2, '0', STR_PAD_LEFT);
            if ($wRt !== $uRt || $wRw !== $uRw) {
                $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Surat ini di luar wilayah RT Anda.', 'type' => 'error'];
                header('Location: /surat');
                exit;
            }
        }
        if ($role === 'rw') {
            $wRw = str_pad(trim((string)$surat_manual['rw']), 2, '0', STR_PAD_LEFT);
            $uRw = str_pad(trim((string)$user['rw']), 2, '0', STR_PAD_LEFT);
            if ($wRw !== $uRw) {
                $_SESSION['flash'] = ['message' => '❌ Akses Ditolak! Surat ini di luar wilayah RW Anda.', 'type' => 'error'];
                header('Location: /surat');
                exit;
            }
        }

        $replacements = json_decode($surat_manual['data_isi'], true) ?: [];
        $isi_surat = str_replace(array_keys($replacements), array_values($replacements), $surat_manual['isi_template']);

        $template_data = [
            'nama' => $surat_manual['template_nama'] ?? 'Surat Resmi',
            'nomor_surat' => $surat_manual['nomor_surat'] ?? '-',
            'isi_final' => $isi_surat,
            'id_surat_terbit' => $surat_manual['id'],
            'status' => $surat_manual['status'] ?? 'pending',
            'user_id_pemohon' => $surat_manual['user_id'],
            'email_sent_at' => $surat_manual['email_sent_at'] ?? null,
            'whatsapp_sent_at' => $surat_manual['whatsapp_sent_at'] ?? null,
            'replacements' => $replacements
        ];
        require_once __DIR__ . '/../../resources/views/surat/cetak.php'; 
        exit;
    } catch (Exception $e) { 
        echo "<div style='padding:20px; font-family:sans-serif; color:red;'><h2>❌ Terjadi Kesalahan:</h2><p>" . $e->getMessage() . "</p><a href='/surat'>Kembali ke Daftar Surat</a></div>";
        exit; 
    }
}

// ============================================
// CRUD TEMPLATE
// ============================================
if ($action === 'tambah') {
    if (!in_array($role, ['super_admin', 'desa'])) { header('Location: /surat'); exit; }
    $mode = 'tambah'; $template = null;
    require_once __DIR__ . '/../../resources/views/surat/form.php'; exit;
}

if ($action === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($role, ['super_admin', 'desa'])) { header('Location: /surat'); exit; }
    $stmt = $pdo->prepare("INSERT INTO surat_template (desa_id, nama, judul, isi_template, icon) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([1, $_POST['nama'] ?? '', $_POST['judul'] ?? '', $_POST['isi_template'] ?? '', 'fa-file-alt']);
    header('Location: /surat'); exit;
}

if ($action === 'edit' && $id) {
    if (!in_array($role, ['super_admin', 'desa'])) { header('Location: /surat'); exit; }
    $stmt = $pdo->prepare("SELECT * FROM surat_template WHERE id = ?");
    $stmt->execute([$id]); $template = $stmt->fetch();
    $mode = 'edit';
    require_once __DIR__ . '/../../resources/views/surat/form.php'; exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    if (!in_array($role, ['super_admin', 'desa'])) { header('Location: /surat'); exit; }
    $stmt = $pdo->prepare("UPDATE surat_template SET nama = ?, judul = ?, isi_template = ? WHERE id = ?");
    $stmt->execute([$_POST['nama'] ?? '', $_POST['judul'] ?? '', $_POST['isi_template'] ?? '', $id]);
    header('Location: /surat'); exit;
}

if ($action === 'hapus' && $id && in_array($role, ['super_admin', 'desa'])) {
    $pdo->prepare("DELETE FROM surat_template WHERE id = ?")->execute([$id]);
    header('Location: /surat'); exit;
}

// ============================================
// INDEX - DAFTAR SURAT (FILTER ROLE + DEBUG MODE)
// ============================================
if ($action === 'index') {
    try {
        $sql = "
            SELECT s.id, s.nomor_surat, s.created_at, s.status, s.email_sent_at, s.whatsapp_sent_at, 'ai' as tipe, s.jenis_surat as judul, u.name as pemohon, u.rt, u.rw, u.email, u.no_hp, u.no_telp, s.user_id
            FROM surat_pengajuan s JOIN users u ON s.user_id = u.id
            UNION ALL
            SELECT s.id, s.nomor_surat, s.created_at, s.status, s.email_sent_at, s.whatsapp_sent_at, 'manual' as tipe, t.nama as judul, u.name as pemohon, u.rt, u.rw, u.email, u.no_hp, u.no_telp, s.user_id
            FROM surat_terbit s JOIN surat_template t ON s.template_id = t.id JOIN users u ON s.user_id = u.id
        ";

        $params = []; $where = [];

        if ($role === 'warga') {
            $where[] = "user_id = ?"; 
            $params[] = $user['id']; 
        } elseif ($role === 'rt') {
            // GUNAKAN TRIM AGAR ANTI SPASI GAIB
            $where[] = "LPAD(TRIM(rt), 2, '0') = LPAD(TRIM(?), 2, '0') AND LPAD(TRIM(rw), 2, '0') = LPAD(TRIM(?), 2, '0')";
            $params[] = $user['rt']; 
            $params[] = $user['rw'];
        } elseif ($role === 'rw') {
            $where[] = "LPAD(TRIM(rw), 2, '0') = LPAD(TRIM(?), 2, '0')"; 
            $params[] = $user['rw'];
        }

        if (count($where) > 0) {
            $sql = "SELECT * FROM ($sql) as combined WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";
        } else {
            $sql = "SELECT * FROM ($sql) as combined ORDER BY created_at DESC";
        }

        $stmt = $pdo->prepare($sql); $stmt->execute($params); $surat_list = $stmt->fetchAll();
        $template_list = $pdo->query("SELECT * FROM surat_template ORDER BY id ASC")->fetchAll();
        
        // FITUR DEBUG: Nampilin status user & query kalau ada ?debug=1
        if ($debug) {
            echo "<div style='background:#f8fafc; padding:20px; border:2px dashed #3b82f6; margin:20px; font-family:monospace;'>";
            echo "<h3>🔍 DEBUG MODE</h3>";
            echo "<p><strong>Role Anda:</strong> " . htmlspecialchars($role) . "</p>";
            echo "<p><strong>RT Anda:</strong> '" . htmlspecialchars($user['rt'] ?? 'KOSONG') . "' (Hex: " . bin2hex($user['rt'] ?? '') . ")</p>";
            echo "<p><strong>RW Anda:</strong> '" . htmlspecialchars($user['rw'] ?? 'KOSONG') . "' (Hex: " . bin2hex($user['rw'] ?? '') . ")</p>";
            echo "<p><strong>Query Dieksekusi:</strong> " . htmlspecialchars($sql) . "</p>";
            echo "<p><strong>Data Ditemukan:</strong> " . count($surat_list) . " baris.</p>";
            echo "<p><em>Hapus '?debug=1' di URL untuk kembali normal.</em></p>";
            echo "</div>";
        }

    } catch (Exception $e) {
        $surat_list = []; $template_list = [];
        log_error('Index Surat Error', ['error' => $e->getMessage()]);
    }

    require_once __DIR__ . '/../../resources/views/surat/index.php'; exit;
}

// ============================================
// BUAT & UPDATE
// ============================================
if ($action === 'buat' && $id) {
    $template = null; $surat_ai = null;
    try { $stmt = $pdo->prepare("SELECT * FROM surat_template WHERE id = ?"); $stmt->execute([$id]); $template = $stmt->fetch(); } catch (Exception $e) {}
    if (!$template) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM surat_pengajuan WHERE id = ?");
            $stmt->execute([(int)$id]); $surat_ai = $stmt->fetch();
            if ($surat_ai) {
                // Jika warga, pastikan surat miliknya
                if ($role === 'warga' && (int)$surat_ai['user_id'] !== (int)$user['id']) {
                    $_SESSION['flash'] = ['message' => '❌ Akses Ditolak!', 'type' => 'error'];
                    header('Location: /surat');
                    exit;
                }
                $data = json_decode($surat_ai['data_json'], true) ?: [];
                $template = ['id' => 'ai_' . $surat_ai['id'], 'nama' => $surat_ai['jenis_surat'] . ' (dari AI)', 'judul' => $surat_ai['jenis_surat'], '_mode' => 'ai', '_ai_id' => $surat_ai['id'], '_data' => $data];
            }
        } catch (Exception $e) {}
    }
    if (!$template) { $_SESSION['flash'] = ['message' => '❌ Surat tidak ditemukan!', 'type' => 'error']; header('Location: /surat'); exit; }
    require_once __DIR__ . '/../../resources/views/surat/buat.php'; exit;
}

if ($action === 'update_ai' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai_id = (int)($_POST['ai_id'] ?? 0);
    $checkOwner = $pdo->query("SELECT user_id, data_json FROM surat_pengajuan WHERE id = $ai_id")->fetch(PDO::FETCH_ASSOC);
    if (!$checkOwner) { header('Location: /surat'); exit; }
    if ($role === 'warga' && (int)$checkOwner['user_id'] !== (int)$user['id']) {
        $_SESSION['flash'] = ['message' => '❌ Akses Ditolak!', 'type' => 'error'];
        header('Location: /surat');
        exit;
    }
    $data_lama = json_decode($checkOwner['data_json'], true) ?: [];
    $data_baru = array_merge($data_lama, ['nama' => $_POST['nama'] ?? '', 'nik' => $_POST['nik'] ?? '', 'ttl' => $_POST['ttl'] ?? '', 'jk' => $_POST['jk'] ?? '', 'pekerjaan' => $_POST['pekerjaan'] ?? '', 'rt' => $_POST['rt'] ?? '', 'rw' => $_POST['rw'] ?? '', 'alamat' => $_POST['alamat'] ?? '', 'keperluan' => $_POST['keperluan'] ?? '']);
    $stmt = $pdo->prepare("UPDATE surat_pengajuan SET data_json = ? WHERE id = ?"); $stmt->execute([json_encode($data_baru), $ai_id]);
    header('Location: /surat?action=cetak&id=' . $ai_id); exit;
}

if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_id = $_POST['template_id'] ?? null;
    $stmt = $pdo->prepare("SELECT * FROM surat_template WHERE id = ?"); $stmt->execute([$template_id]); $template = $stmt->fetch();
    if (!$template) { header('Location: /surat'); exit; }

    $replacements = [
        '{nama}' => $_POST['nama'] ?? '', '{nik}' => $_POST['nik'] ?? '', '{ttl}' => $_POST['ttl'] ?? '', '{jk}' => $_POST['jk'] ?? '', '{pekerjaan}' => $_POST['pekerjaan'] ?? '', '{alamat}' => $_POST['alamat'] ?? '', '{rt}' => $_POST['rt'] ?? '', '{rw}' => $_POST['rw'] ?? '', '{keperluan}' => $_POST['keperluan'] ?? '', '{nama_usaha}' => $_POST['nama_usaha'] ?? '', '{jenis_usaha}' => $_POST['jenis_usaha'] ?? '', '{alamat_usaha}' => $_POST['alamat_usaha'] ?? '', '{tanggal}' => date('d-m-Y'), '{desa}' => 'Desa Sukamaju'
    ];
    $isi_surat = str_replace(array_keys($replacements), array_values($replacements), $template['isi_template']);
    $nomor_surat = sprintf("%03d/SK/%s/%s", rand(1, 999), strtoupper(substr($template['nama'], 0, 3)), date('Y'));

    $stmt = $pdo->prepare("INSERT INTO surat_terbit (user_id, template_id, nomor_surat, data_isi, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user['id'], $template_id, $nomor_surat, json_encode($replacements)]);
    $id_surat_terbit = $pdo->lastInsertId();

    // Notifikasi WhatsApp ke Petugas RT/RW/Desa/Super Admin
    try {
        WhatsAppNotificationService::sendPengajuanSuratBaru($id_surat_terbit, 'manual', $template['nama'], $nomor_surat, $user);
    } catch (Exception $eWa) {
        error_log('WhatsApp sendPengajuanSuratBaru manual error: ' . $eWa->getMessage());
    }

    $template_data = $template;
    $template_data['isi_final'] = $isi_surat;
    $template_data['nomor_surat'] = $nomor_surat;
    $template_data['id_surat_terbit'] = $id_surat_terbit;
    $template_data['status'] = 'pending';
    $template_data['user_id_pemohon'] = $user['id'];
    $template_data['email_sent_at'] = null;
    $template_data['whatsapp_sent_at'] = null;

    require_once __DIR__ . '/../../resources/views/surat/cetak.php'; exit;
}

header('Location: /surat');
