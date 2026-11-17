<?php
// app/Controllers/PengaduanController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../Models/Pengaduan.php';
require_once __DIR__ . '/../Models/Notification.php';
require_once __DIR__ . '/../Services/WhatsAppNotificationService.php';

if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = auth();
$role = $user['role'];
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// ============================================
// NORMALISASI WILAYAH
// ============================================
$user_rt = str_pad(trim($user['rt'] ?? ''), 2, '0', STR_PAD_LEFT);
$user_rw = str_pad(trim($user['rw'] ?? ''), 2, '0', STR_PAD_LEFT);

// ============================================
// ROLE YANG BISA TANGGAPI / HAPUS
// ============================================
$rolePenanggap = ['super_admin', 'desa', 'rt', 'rw'];

// ============================================
// HELPER UPLOAD BUKTI GAMBAR
// ============================================
function uploadBukti($file, $prefix = 'bukti') {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 4 * 1024 * 1024) return null; // max 4MB

    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return null;

    $extMap = ['image/jpeg'=>'jpg', 'image/jpg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    $ext = $extMap[$mime] ?? 'jpg';

    $filename  = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../../public/uploads/pengaduan/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) return null;

    return 'uploads/pengaduan/' . $filename;
}

// ============================================
// BUAT PENGADUAN (Warga)
// ============================================
if ($action === 'buat') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'user_id'      => $user['id'],
            'judul'        => trim($_POST['judul'] ?? ''),
            'isi'          => trim($_POST['isi'] ?? ''),
            'kategori'     => $_POST['kategori'] ?? 'umum',
            'prioritas'    => $_POST['prioritas'] ?? 'sedang',
            'lokasi'       => trim($_POST['lokasi'] ?? ''),
            'kontak'       => trim($_POST['kontak'] ?? ''),
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
        ];

        if (empty($data['judul']) || empty($data['isi'])) {
            $_SESSION['flash'] = ['message' => '⚠️ Judul dan isi pengaduan wajib diisi!', 'type' => 'error'];
            header('Location: /pengaduan?action=buat');
            exit;
        }

        $data['bukti_gambar']   = uploadBukti($_FILES['bukti_gambar']   ?? null, 'bukti1');
        $data['bukti_gambar_2'] = uploadBukti($_FILES['bukti_gambar_2'] ?? null, 'bukti2');

        $newId = Pengaduan::create($data);

        // ============================================
        // NOTIFIKASI SPESIFIK WILAYAH
        // ============================================
        $prioritasEmoji = [
            'rendah'  => '🟢',
            'sedang'  => '🟡',
            'tinggi'  => '🟠',
            'darurat' => '🔴',
        ][$data['prioritas']] ?? '🟡';

        $pelaporNama = $data['is_anonymous'] ? 'Warga (anonim)' : ($user['name'] ?? 'Warga');
        $linkNotif = '/pengaduan?action=detail&id=' . $newId;
        $titleNotif = $prioritasEmoji . ' Pengaduan Baru';
        $msgNotif = $pelaporNama . ' melaporkan: ' . $data['judul'];

        // 1. Kirim ke Super Admin & Desa (Global)
        Notification::notifyByRole(
            ['super_admin', 'desa'],
            'pengaduan_baru',
            $titleNotif,
            $msgNotif,
            $linkNotif,
            $user['id']
        );

        // 2. Kirim ke RT setempat (Cari akun RT di wilayah yang sama)
        try {
            $stmt_rt = $pdo->prepare("SELECT id FROM users WHERE role = 'rt' AND LPAD(rt, 2, '0') = ? AND LPAD(rw, 2, '0') = ? AND id != ?");
            $stmt_rt->execute([$user_rt, $user_rw, $user['id']]);
            while ($target_rt = $stmt_rt->fetch()) {
                Notification::create($target_rt['id'], 'pengaduan_baru', $titleNotif, $msgNotif, $linkNotif);
            }
        } catch (Exception $e) {}

        // 3. Kirim ke RW setempat (Cari akun RW di wilayah yang sama)
        try {
            $stmt_rw = $pdo->prepare("SELECT id FROM users WHERE role = 'rw' AND LPAD(rw, 2, '0') = ? AND id != ?");
            $stmt_rw->execute([$user_rw, $user['id']]);
            while ($target_rw = $stmt_rw->fetch()) {
                Notification::create($target_rw['id'], 'pengaduan_baru', $titleNotif, $msgNotif, $linkNotif);
            }
        } catch (Exception $e) {}

        // Notifikasi Pengaduan Baru via WhatsApp ke Pengurus (Super Admin, Desa, RT, RW)
        try {
            WhatsAppNotificationService::sendPengaduanBaru($newId, $data['judul'], $data['kategori'] ?? 'umum', $data['prioritas'] ?? 'sedang', $user, !empty($data['is_anonymous']));
        } catch (Exception $eWa) {
            error_log('WhatsApp sendPengaduanBaru error: ' . $eWa->getMessage());
        }

        $_SESSION['flash'] = ['message' => '✅ Pengaduan berhasil dikirim!', 'type' => 'success'];
        header('Location: /pengaduan');
        exit;
    }
    require_once __DIR__ . '/../../resources/views/pengaduan/buat.php';
    exit;
}

// ============================================
// DETAIL PENGADUAN
// ============================================
if ($action === 'detail' && $id) {
    $detail = Pengaduan::find($id);
    if (!$detail) {
        $_SESSION['flash'] = ['message' => '❌ Pengaduan tidak ditemukan!', 'type' => 'error'];
        header('Location: /pengaduan');
        exit;
    }

    // Warga hanya bisa lihat pengaduan sendiri
    if ($role === 'warga' && $detail['user_id'] != $user['id']) {
        $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak melihat pengaduan ini!', 'type' => 'error'];
        header('Location: /pengaduan');
        exit;
    }

    // Cek Wilayah RT/RW
    $target_rt = str_pad(trim($detail['rt'] ?? ''), 2, '0', STR_PAD_LEFT);
    $target_rw = str_pad(trim($detail['rw'] ?? ''), 2, '0', STR_PAD_LEFT);
    
    if ($role === 'rt' && ($target_rt !== $user_rt || $target_rw !== $user_rw)) {
        $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak melihat pengaduan dari wilayah lain!', 'type' => 'error'];
        header('Location: /pengaduan'); exit;
    }
    if ($role === 'rw' && $target_rw !== $user_rw) {
        $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak melihat pengaduan dari RW lain!', 'type' => 'error'];
        header('Location: /pengaduan'); exit;
    }

    require_once __DIR__ . '/../../resources/views/pengaduan/detail.php';
    exit;
}

// ============================================
// TANGGAPI (Admin/Super Admin/RT/RW)
// ============================================
if ($action === 'tanggapi' && $id && in_array($role, $rolePenanggap)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $target_pengaduan = Pengaduan::find($id);
        $target_rt = str_pad(trim($target_pengaduan['rt'] ?? ''), 2, '0', STR_PAD_LEFT);
        $target_rw = str_pad(trim($target_pengaduan['rw'] ?? ''), 2, '0', STR_PAD_LEFT);

        if ($role === 'rt' && ($target_rt !== $user_rt || $target_rw !== $user_rw)) {
            $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak menanggapi pengaduan ini!', 'type' => 'error'];
            header('Location: /pengaduan'); exit;
        }
        if ($role === 'rw' && $target_rw !== $user_rw) {
            $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak menanggapi pengaduan ini!', 'type' => 'error'];
            header('Location: /pengaduan'); exit;
        }

        $status = $_POST['status'] ?? 'proses';
        $tanggapan = trim($_POST['tanggapan'] ?? '');
        $bukti1 = uploadBukti($_FILES['bukti_tanggapan_1'] ?? null, 'tanggapan1');
        $bukti2 = uploadBukti($_FILES['bukti_tanggapan_2'] ?? null, 'tanggapan2');

        Pengaduan::updateStatus($id, $status, $tanggapan, $bukti1, $bukti2);

        // Notif Balik ke Warga
        $pengaduan = Pengaduan::find($id);
        if ($pengaduan && $pengaduan['user_id'] != $user['id']) {
            $statusLabel = ['pending' => 'Menunggu', 'proses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'][$status] ?? $status;
            $statusEmoji = ['pending' => '⏳', 'proses' => '🔄', 'selesai' => '✅', 'ditolak' => '❌'][$status] ?? '🔔';
            $msgBody = $tanggapan ? 'Pengaduan "' . $pengaduan['judul'] . '" mendapat tanggapan baru.' : 'Status pengaduan "' . $pengaduan['judul'] . '" diperbarui.';

            Notification::create($pengaduan['user_id'], 'pengaduan_update', $statusEmoji . ' Pengaduan Anda: ' . $statusLabel, $msgBody, '/pengaduan?action=detail&id=' . $id);

            // Notifikasi WhatsApp Update Tanggapan ke Warga Pelapor
            try {
                WhatsAppNotificationService::sendPengaduanUpdate($id, $status, $tanggapan);
            } catch (Exception $eWa) {
                error_log('WhatsApp sendPengaduanUpdate error: ' . $eWa->getMessage());
            }
        }

        $_SESSION['flash'] = ['message' => '✅ Tanggapan berhasil dikirim!', 'type' => 'success'];
        header('Location: /pengaduan?action=detail&id=' . $id);
        exit;
    }
}

// ============================================
// HAPUS (Admin/Super Admin/RT/RW)
// ============================================
if ($action === 'hapus' && $id && in_array($role, $rolePenanggap)) {
    $target_pengaduan = Pengaduan::find($id);
    $target_rt = str_pad(trim($target_pengaduan['rt'] ?? ''), 2, '0', STR_PAD_LEFT);
    $target_rw = str_pad(trim($target_pengaduan['rw'] ?? ''), 2, '0', STR_PAD_LEFT);

    if ($role === 'rt' && ($target_rt !== $user_rt || $target_rw !== $user_rw)) {
        $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak menghapus pengaduan ini!', 'type' => 'error'];
        header('Location: /pengaduan'); exit;
    }
    if ($role === 'rw' && $target_rw !== $user_rw) {
        $_SESSION['flash'] = ['message' => '❌ Anda tidak berhak menghapus pengaduan ini!', 'type' => 'error'];
        header('Location: /pengaduan'); exit;
    }

    Pengaduan::delete($id);
    $_SESSION['flash'] = ['message' => '✅ Pengaduan berhasil dihapus!', 'type' => 'success'];
    header('Location: /pengaduan');
    exit;
}

// ============================================
// INDEX – List Pengaduan
// ============================================
if ($role === 'warga') {
    $pengaduan_list = Pengaduan::findByUser($user['id']);
} else {
    $pengaduan_list = Pengaduan::all($role, $user_rt, $user_rw);
}

require_once __DIR__ . '/../../resources/views/pengaduan/index.php';