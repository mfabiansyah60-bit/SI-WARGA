<?php
// app/Controllers/RtRwController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();
$role = $user['role'];

// Cek akses: super_admin, desa, rw bisa akses
if (!in_array($role, ['super_admin', 'desa', 'rw'])) {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak!', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$action = $_GET['action'] ?? 'index';
$type   = $_GET['type'] ?? 'rw';
$id     = $_GET['id'] ?? null;

// ============================================================
// HELPER: SYNC EDIT RT/RW KE TABEL USERS
// ============================================================
function syncRtToUsers($pdo, $oldRt, $newRt) {
    try {
        $oldNomor = str_pad($oldRt['nomor'] ?? '', 2, '0', STR_PAD_LEFT);
        $newNomor = str_pad($newRt['nomor'] ?? '', 2, '0', STR_PAD_LEFT);
        $oldRw    = str_pad($oldRt['rw_nomor'] ?? '', 2, '0', STR_PAD_LEFT);
        $newRw    = str_pad($newRt['rw_nomor'] ?? '', 2, '0', STR_PAD_LEFT);

        // 1. Update data user (Nama, Email, No Telp)
        if ($oldNomor !== '') {
            $pdo->prepare("UPDATE users SET name = ?, email = ?, no_telp = ? WHERE role = 'rt' AND LPAD(TRIM(rt), 2, '0') = ? AND LPAD(TRIM(rw), 2, '0') = ?")
                ->execute([$newRt['ketua'], $newRt['email'], $newRt['no_telp'], $oldNomor, $oldRw]);
        }

        // 2. Update nomor RT/RW di semua user terkait jika ada perubahan nomor
        if ($oldNomor !== '' && ($oldNomor !== $newNomor || $oldRw !== $newRw)) {
            $pdo->prepare("UPDATE users SET rt = ?, rw = ? WHERE (role = 'rt' OR role = 'warga') AND LPAD(TRIM(rt), 2, '0') = ? AND LPAD(TRIM(rw), 2, '0') = ?")
                ->execute([$newNomor, $newRw, $oldNomor, $oldRw]);
        }
        return true;
    } catch (Exception $e) { return false; }
}

function syncRwToUsers($pdo, $oldRw, $newRw) {
    try {
        $oldNomor = str_pad($oldRw['nomor'] ?? '', 2, '0', STR_PAD_LEFT);
        $newNomor = str_pad($newRw['nomor'] ?? '', 2, '0', STR_PAD_LEFT);

        // 1. Update data user (Nama, Email, No Telp)
        if ($oldNomor !== '') {
            $pdo->prepare("UPDATE users SET name = ?, email = ?, no_telp = ? WHERE role = 'rw' AND LPAD(TRIM(rw), 2, '0') = ?")
                ->execute([$newRw['ketua'], $newRw['email'], $newRw['no_telp'], $oldNomor]);
        }

        // 2. Update nomor RW di semua user terkait jika ada perubahan nomor
        if ($oldNomor !== '' && $oldNomor !== $newNomor) {
            $pdo->prepare("UPDATE users SET rw = ? WHERE (role = 'rt' OR role = 'warga') AND LPAD(TRIM(rw), 2, '0') = ?")
                ->execute([$newNomor, $oldNomor]);
        }
        return true;
    } catch (Exception $e) { return false; }
}

// ============================================
// TAMBAH (DENGAN SINKRONISASI AKUN USER)
// ============================================
if ($action === 'tambah' && in_array($role, ['super_admin', 'desa'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();

            if ($type === 'rw') {
                $data = [
                    'desa_id' => $_POST['desa_id'] ?? null,
                    'nomor'   => trim($_POST['nomor'] ?? ''),
                    'ketua'   => trim($_POST['ketua'] ?? ''),
                    'email'   => trim($_POST['email'] ?? ''),
                    'no_telp' => trim($_POST['no_telp'] ?? '')
                ];

                if (empty($data['nomor']) || empty($data['desa_id'])) {
                    throw new Exception("Desa & Nomor RW wajib diisi!");
                }

                $nomorRw = str_pad($data['nomor'], 2, '0', STR_PAD_LEFT);

                // VALIDASI ANTI DOBEL RW
                $cekDup = $pdo->prepare("SELECT id FROM rw WHERE desa_id = ? AND nomor = ?");
                $cekDup->execute([$data['desa_id'], $nomorRw]);
                if ($cekDup->fetch()) {
                    throw new Exception("RW Nomor $nomorRw sudah ada di desa ini!");
                }

                // 1. Insert RW
                $stmt = $pdo->prepare("INSERT INTO rw (desa_id, nomor, ketua) VALUES (?, ?, ?)");
                $stmt->execute([$data['desa_id'], $nomorRw, $data['ketua']]);
                $newRwId = $pdo->lastInsertId();

                // 2. Bikin Akun User Otomatis
                $cekUser = $pdo->prepare("SELECT id FROM users WHERE role = 'rw' AND rw = ?");
                $cekUser->execute([$nomorRw]);
                if (!$cekUser->fetch()) {
                    $email_auto = !empty($data['email']) ? $data['email'] : "rw{$nomorRw}@siwarga.local";
                    $pass_auto  = password_hash('rw12345', PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, no_telp, password, role, rw, rw_id, desa_id, status_verifikasi, created_at) VALUES (?, ?, ?, ?, 'rw', ?, ?, ?, 'active', NOW())");
                    $stmtUser->execute([$data['ketua'], $email_auto, $data['no_telp'], $pass_auto, $nomorRw, $newRwId, $data['desa_id']]);
                }

                $pdo->commit();
                $_SESSION['flash'] = ['message' => '✅ RW berhasil ditambahkan & Akun otomatis dibuat!', 'type' => 'success'];

            } else {
                $data = [
                    'rw_id'   => $_POST['rw_id'] ?? null,
                    'nomor'   => trim($_POST['nomor'] ?? ''),
                    'ketua'   => trim($_POST['ketua'] ?? ''),
                    'email'   => trim($_POST['email'] ?? ''),
                    'no_telp' => trim($_POST['no_telp'] ?? '')
                ];

                if (empty($data['nomor']) || empty($data['rw_id'])) {
                    throw new Exception("RW & Nomor RT wajib diisi!");
                }

                $nomorRt = str_pad($data['nomor'], 2, '0', STR_PAD_LEFT);

                // VALIDASI ANTI DOBEL RT
                $cekDup = $pdo->prepare("SELECT id FROM rt WHERE rw_id = ? AND nomor = ?");
                $cekDup->execute([$data['rw_id'], $nomorRt]);
                if ($cekDup->fetch()) {
                    throw new Exception("RT Nomor $nomorRt sudah ada di RW ini!");
                }

                // Ambil info RW
                $stmtRw = $pdo->prepare("SELECT nomor, desa_id FROM rw WHERE id = ?");
                $stmtRw->execute([$data['rw_id']]);
                $rwData = $stmtRw->fetch();
                $nomorRw = str_pad($rwData['nomor'], 2, '0', STR_PAD_LEFT);
                $desaId = $rwData['desa_id'];

                // 1. Insert RT
                $stmt = $pdo->prepare("INSERT INTO rt (rw_id, nomor, ketua) VALUES (?, ?, ?)");
                $stmt->execute([$data['rw_id'], $nomorRt, $data['ketua']]);
                $newRtId = $pdo->lastInsertId();

                // 2. Bikin Akun User Otomatis
                $cekUser = $pdo->prepare("SELECT id FROM users WHERE role = 'rt' AND rt = ? AND rw = ?");
                $cekUser->execute([$nomorRt, $nomorRw]);
                if (!$cekUser->fetch()) {
                    $email_auto = !empty($data['email']) ? $data['email'] : "rt{$nomorRt}rw{$nomorRw}@siwarga.local";
                    $pass_auto  = password_hash('rt12345', PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, no_telp, password, role, rt, rw, rt_id, rw_id, desa_id, status_verifikasi, created_at) VALUES (?, ?, ?, ?, 'rt', ?, ?, ?, ?, ?, 'active', NOW())");
                    $stmtUser->execute([$data['ketua'], $email_auto, $data['no_telp'], $pass_auto, $nomorRt, $nomorRw, $newRtId, $data['rw_id'], $desaId]);
                }

                $pdo->commit();
                $_SESSION['flash'] = ['message' => '✅ RT berhasil ditambahkan & Akun otomatis dibuat!', 'type' => 'success'];
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
        }

        header('Location: /rt_rw?type=' . $type);
        exit;
    }

    try {
        $desa_list = $pdo->query("SELECT * FROM desa ORDER BY nama")->fetchAll();
        $rw_list = $pdo->query("SELECT rw.*, desa.nama as desa_nama FROM rw JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor")->fetchAll();
    } catch (Exception $e) {
        $desa_list = []; $rw_list = [];
    }

    $mode = 'tambah';
    $item = null;
    require_once __DIR__ . '/../../resources/views/rt_rw/form.php';
    exit;
}

// ============================================
// EDIT + AUTO SYNC
// ============================================
if ($action === 'edit' && $id && in_array($role, ['super_admin', 'desa'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();
            
            if ($type === 'rw') {
                $data = [
                    'desa_id' => $_POST['desa_id'] ?? null,
                    'nomor'   => trim($_POST['nomor'] ?? ''),
                    'ketua'   => trim($_POST['ketua'] ?? ''),
                    'email'   => trim($_POST['email'] ?? ''),
                    'no_telp' => trim($_POST['no_telp'] ?? '')
                ];

                $stmt = $pdo->prepare("SELECT * FROM rw WHERE id = ?");
                $stmt->execute([$id]);
                $oldRw = $stmt->fetch();

                if (!$oldRw) throw new Exception("RW tidak ditemukan!");

                $stmt = $pdo->prepare("UPDATE rw SET desa_id=?, nomor=?, ketua=? WHERE id=?");
                $stmt->execute([$data['desa_id'], $data['nomor'], $data['ketua'], $id]);

                // Kirim email dan no_telp ke fungsi sync
                syncRwToUsers($pdo, $oldRw, [
                    'nomor' => $data['nomor'], 
                    'ketua' => $data['ketua'],
                    'email' => $data['email'],
                    'no_telp' => $data['no_telp']
                ]);
                
                $pdo->commit();
                $_SESSION['flash'] = ['message' => '✅ RW berhasil diupdate & disinkronkan!', 'type' => 'success'];

            } else {
                $data = [
                    'rw_id' => $_POST['rw_id'] ?? null,
                    'nomor' => trim($_POST['nomor'] ?? ''),
                    'ketua' => trim($_POST['ketua'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'no_telp' => trim($_POST['no_telp'] ?? '')
                ];

                $stmt = $pdo->prepare("SELECT rt.*, rw.nomor AS rw_nomor FROM rt JOIN rw ON rt.rw_id = rw.id WHERE rt.id = ?");
                $stmt->execute([$id]);
                $oldRt = $stmt->fetch();

                if (!$oldRt) throw new Exception("RT tidak ditemukan!");

                $stmt = $pdo->prepare("UPDATE rt SET rw_id=?, nomor=?, ketua=? WHERE id=?");
                $stmt->execute([$data['rw_id'], $data['nomor'], $data['ketua'], $id]);

                $stmt = $pdo->prepare("SELECT nomor FROM rw WHERE id = ?");
                $stmt->execute([$data['rw_id']]);
                $newRwNomor = $stmt->fetchColumn() ?: '';

                // Kirim email dan no_telp ke fungsi sync
                syncRtToUsers($pdo, $oldRt, [
                    'nomor' => $data['nomor'], 
                    'ketua' => $data['ketua'], 
                    'rw_nomor' => $newRwNomor,
                    'email' => $data['email'],
                    'no_telp' => $data['no_telp']
                ]);
                
                $pdo->commit();
                $_SESSION['flash'] = ['message' => '✅ RT berhasil diupdate & disinkronkan!', 'type' => 'success'];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
        }
        header('Location: /rt_rw?type=' . $type);
        exit;
    }

    try {
        // TAMBAHKAN QUERY JOIN KE USERS UNTUK FORM EDIT
        if ($type === 'rw') {
            $stmt = $pdo->prepare("SELECT rw.*, u.email, u.no_telp FROM rw LEFT JOIN users u ON u.rw = rw.nomor AND u.role = 'rw' WHERE rw.id=?");
        } else {
            $stmt = $pdo->prepare("SELECT rt.*, u.email, u.no_telp FROM rt LEFT JOIN users u ON u.rt = rt.nomor AND u.rw = (SELECT nomor FROM rw WHERE id = rt.rw_id) AND u.role = 'rt' WHERE rt.id=?");
        }
        $stmt->execute([$id]);
        $item = $stmt->fetch();
    } catch (Exception $e) { $item = null; }

    if (!$item) {
        $_SESSION['flash'] = ['message' => '❌ Data tidak ditemukan!', 'type' => 'error'];
        header('Location: /rt_rw?type=' . $type);
        exit;
    }

    try {
        $desa_list = $pdo->query("SELECT * FROM desa ORDER BY nama")->fetchAll();
        $rw_list = $pdo->query("SELECT rw.*, desa.nama as desa_nama FROM rw JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor")->fetchAll();
    } catch (Exception $e) { $desa_list = []; $rw_list = []; }

    $mode = 'edit';
    require_once __DIR__ . '/../../resources/views/rt_rw/form.php';
    exit;
}

// ============================================
// HAPUS + AUTO RESET RELASI WARGA
// ============================================
if ($action === 'hapus' && $id && in_array($role, ['super_admin', 'desa'])) {
    try {
        $pdo->beginTransaction();

        if ($type === 'rw') {
            $stmt = $pdo->prepare("SELECT nomor FROM rw WHERE id = ?");
            $stmt->execute([$id]);
            $nomorRw = str_pad($stmt->fetchColumn(), 2, '0', STR_PAD_LEFT);

            if (!$nomorRw) throw new Exception("RW tidak ditemukan!");

            // 1. Hapus Akun Ketua RW
            $pdo->prepare("DELETE FROM users WHERE role='rw' AND LPAD(TRIM(rw), 2, '0') = ?")->execute([$nomorRw]);
            
            // 2. Lepas relasi RT & Warga (jangan dihapus, cuma di-NULL-kan biar gak error FK)
            $pdo->prepare("UPDATE users SET rw_id = NULL, rw = NULL, rt_id = NULL, rt = NULL WHERE (role='rt' OR role='warga') AND LPAD(TRIM(rw), 2, '0') = ?")->execute([$nomorRw]);

            // 3. Hapus semua RT di bawah RW ini
            $pdo->prepare("DELETE FROM rt WHERE rw_id=?")->execute([$id]);
            
            // 4. Hapus RW
            $pdo->prepare("DELETE FROM rw WHERE id=?")->execute([$id]);
            
            $_SESSION['flash'] = ['message' => '✅ RW & Akun terkait berhasil dihapus permanen!', 'type' => 'success'];

        } else {
            $stmt = $pdo->prepare("SELECT rt.nomor, rw.nomor AS rw_nomor FROM rt JOIN rw ON rt.rw_id = rw.id WHERE rt.id = ?");
            $stmt->execute([$id]);
            $rtData = $stmt->fetch();

            if (!$rtData) throw new Exception("RT tidak ditemukan!");

            $rt_num = str_pad($rtData['nomor'], 2, '0', STR_PAD_LEFT);
            $rw_num = str_pad($rtData['rw_nomor'], 2, '0', STR_PAD_LEFT);

            // 1. Hapus Akun Ketua RT
            $pdo->prepare("DELETE FROM users WHERE role='rt' AND LPAD(TRIM(rt), 2, '0') = ? AND LPAD(TRIM(rw), 2, '0') = ?")->execute([$rt_num, $rw_num]);
            
            // 2. Lepas relasi Warga (jangan dihapus)
            $pdo->prepare("UPDATE users SET rt_id = NULL, rt = NULL WHERE role='warga' AND LPAD(TRIM(rt), 2, '0') = ? AND LPAD(TRIM(rw), 2, '0') = ?")->execute([$rt_num, $rw_num]);
            
            // 3. Hapus RT
            $pdo->prepare("DELETE FROM rt WHERE id=?")->execute([$id]);
            
            $_SESSION['flash'] = ['message' => '✅ RT & Akun terkait berhasil dihapus permanen!', 'type' => 'success'];
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['message' => '❌ Gagal hapus: ' . $e->getMessage(), 'type' => 'error'];
    }
    header('Location: /rt_rw?type=' . $type);
    exit;
}
// ============================================
// INDEX (PERBAIKAN FILTER DATA KETUA RW)
// ============================================
try {
    if ($role === 'rw') {
        // Ambil RW ID dari user. Jika kosong, cari berdasarkan string RW (misal '03')
        $rw_id = $user['rw_id'] ?? null;
        $rw_nomor = $user['rw'] ?? '';

        if (!$rw_id && $rw_nomor) {
            // Jika rw_id di session kosong, cari ID-nya berdasarkan nomor RW
            $stmtCariRw = $pdo->prepare("SELECT id FROM rw WHERE nomor = ?");
            $stmtCariRw->execute([$rw_nomor]);
            $rw_id = $stmtCariRw->fetchColumn();
        }

        if ($rw_id) {
            // KETUA RW: Hanya tarik data RT yang rw_id-nya sama dengan dia
            $stmt = $pdo->prepare("
                SELECT rt.*, rw.nomor as rw_nomor, desa.nama as desa_nama, u.email, u.no_telp
                FROM rt 
                JOIN rw ON rt.rw_id = rw.id 
                JOIN desa ON rw.desa_id = desa.id 
                LEFT JOIN users u ON u.rt = rt.nomor AND u.rw = rw.nomor AND u.role = 'rt'
                WHERE rt.rw_id = ?
                ORDER BY rt.nomor
            ");
            $stmt->execute([$rw_id]);
            $rt_list = $stmt->fetchAll();

            // Ambil data RW-nya sendiri
            $stmtRw = $pdo->prepare("
                SELECT rw.*, desa.nama as desa_nama, u.email, u.no_telp
                FROM rw 
                JOIN desa ON rw.desa_id = desa.id 
                LEFT JOIN users u ON u.rw = rw.nomor AND u.role = 'rw'
                WHERE rw.id = ?
            ");
            $stmtRw->execute([$rw_id]);
            $rw_list = $stmtRw->fetchAll();
        } else {
            // Fallback (jika rw_id dan nomor rw benar-benar tidak valid)
            $rt_list = [];
            $rw_list = [];
        }
    } else {
        // ADMIN DESA / SUPER ADMIN: Tarik semua data
        $rt_list = $pdo->query("
            SELECT rt.*, rw.nomor as rw_nomor, desa.nama as desa_nama, u.email, u.no_telp
            FROM rt 
            JOIN rw ON rt.rw_id = rw.id 
            JOIN desa ON rw.desa_id = desa.id 
            LEFT JOIN users u ON u.rt = rt.nomor AND u.rw = rw.nomor AND u.role = 'rt'
            ORDER BY desa.nama, rw.nomor, rt.nomor
        ")->fetchAll();

        $rw_list = $pdo->query("
            SELECT rw.*, desa.nama as desa_nama, u.email, u.no_telp
            FROM rw 
            JOIN desa ON rw.desa_id = desa.id 
            LEFT JOIN users u ON u.rw = rw.nomor AND u.role = 'rw'
            ORDER BY desa.nama, rw.nomor
        ")->fetchAll();
    }

    $desa_list = $pdo->query("SELECT * FROM desa ORDER BY nama")->fetchAll();
} catch (Exception $e) {
    $rt_list = []; $rw_list = []; $desa_list = [];
}

require_once __DIR__ . '/../../resources/views/rt_rw/index.php';