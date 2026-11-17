<?php
// app/Controllers/UserController.php
// MANAJEMEN USER — KHUSUS SUPER ADMIN

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../../app/Helpers/WhatsAppConfig.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();

if (($user['role'] ?? '') !== 'super_admin') {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak. Hanya Super Admin.', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$action = $_GET['action'] ?? 'index';
$validRoles = ['super_admin', 'desa', 'rw', 'rt', 'warga'];

// ============================================
// INDEX
// ============================================
if ($action === 'index') {
    $search = trim($_GET['q'] ?? '');
    $roleFilter = trim($_GET['role'] ?? '');

    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];

    // FITUR BARU: Pencarian juga mencakup no_telp
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR no_telp LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($roleFilter !== '' && in_array($roleFilter, $validRoles)) {
        $sql .= " AND role = ?";
        $params[] = $roleFilter;
    }
    $sql .= " ORDER BY FIELD(role, 'super_admin','desa','rw','rt','warga'), name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $roleCounts = ['super_admin'=>0,'desa'=>0,'rw'=>0,'rt'=>0,'warga'=>0];
    try {
        $rows = $pdo->query("SELECT role, COUNT(*) AS jml FROM users GROUP BY role")->fetchAll();
        foreach ($rows as $r) $roleCounts[$r['role']] = (int)$r['jml'];
    } catch (Exception $e) {}

    require_once __DIR__ . '/../../resources/views/admin/users.php';
    exit;
}

// ============================================
// VIEW
// ============================================
if ($action === 'view') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch();

    if (!$target) {
        $_SESSION['flash'] = ['message' => '❌ User tidak ditemukan.', 'type' => 'error'];
        header('Location: /users'); exit;
    }
    require_once __DIR__ . '/../../resources/views/admin/user_view.php';
    exit;
}

// ============================================
// TAMBAH (Form)
// ============================================
if ($action === 'tambah') {
    require_once __DIR__ . '/../../resources/views/admin/user_tambah.php';
    exit;
}

// ============================================
// SIMPAN (POST Handler) + AUTO SYNC RT/RW
// ============================================
if ($action === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $noTelp         = trim($_POST['no_telp'] ?? ''); // GANTI DARI no_hp KE no_telp
    $role           = trim($_POST['role'] ?? '');
    $rt             = trim($_POST['rt'] ?? '');
    $rw             = trim($_POST['rw'] ?? '');
    $jenisKelamin   = trim($_POST['jenis_kelamin'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirmPassword= $_POST['confirm_password'] ?? '';

    if (!in_array($jenisKelamin, ['L', 'P'])) $jenisKelamin = null;
    $noTelp = preg_replace('/[^0-9+]/', '', $noTelp);

    $errors = [];
    if ($name === '') $errors[] = 'Nama wajib diisi';
    if ($email === '') $errors[] = 'Email wajib diisi';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid';
    if (!in_array($role, $validRoles)) $errors[] = 'Role tidak valid';
    if ($password === '') $errors[] = 'Password wajib diisi';
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter';
    if ($password !== $confirmPassword) $errors[] = 'Konfirmasi password tidak cocok';
    if ($noTelp !== '' && strlen($noTelp) < 9) $errors[] = 'No Telp minimal 9 digit';

    // Cek duplikat email/no_telp
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'Email sudah dipakai user lain';

    if ($noTelp !== '') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE no_telp = ? OR no_hp = ?");
        $stmt->execute([$noTelp, $noTelp]);
        if ($stmt->fetch()) $errors[] = 'No Telp sudah dipakai user lain';
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['message' => '❌ ' . implode(', ', $errors), 'type' => 'error'];
        $_SESSION['old_input'] = $_POST;
        header('Location: /users?action=tambah'); exit;
    }

    try {
        $pdo->beginTransaction(); 

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $noTelpParam = $noTelp !== '' ? $noTelp : null;

        $rt_num = $rt !== '' ? str_pad($rt, 2, '0', STR_PAD_LEFT) : null;
        $rw_num = $rw !== '' ? str_pad($rw, 2, '0', STR_PAD_LEFT) : null;

        $desa_id = 1; 
        try {
            $stmtDesa = $pdo->query("SELECT id FROM desa LIMIT 1");
            $rowDesa = $stmtDesa->fetch();
            if ($rowDesa) $desa_id = $rowDesa['id'];
        } catch (Exception $e) {}

        $rw_id = null;
        $rt_id = null;

        // AUTO-SYNC KE TABEL RW
        if ($rw_num) {
            $cekRw = $pdo->prepare("SELECT id FROM rw WHERE nomor = ?");
            $cekRw->execute([$rw_num]);
            $rwRow = $cekRw->fetch();
            
            if ($rwRow) {
                $rw_id = $rwRow['id'];
                if ($role === 'rw') {
                    $pdo->prepare("UPDATE rw SET ketua = ? WHERE id = ?")->execute([$name, $rw_id]);
                }
            } else {
                $pdo->prepare("INSERT INTO rw (nomor, ketua) VALUES (?, ?)")->execute([$rw_num, $role === 'rw' ? $name : '-']);
                $rw_id = $pdo->lastInsertId();
            }
        }

        // AUTO-SYNC KE TABEL RT
        if ($rt_num && $rw_id) {
            $cekRt = $pdo->prepare("SELECT id FROM rt WHERE nomor = ? AND rw_id = ?");
            $cekRt->execute([$rt_num, $rw_id]);
            $rtRow = $cekRt->fetch();
            
            if ($rtRow) {
                $rt_id = $rtRow['id'];
                if ($role === 'rt') {
                    $pdo->prepare("UPDATE rt SET ketua = ? WHERE id = ?")->execute([$name, $rt_id]);
                }
            } else {
                $pdo->prepare("INSERT INTO rt (nomor, rw_id, ketua) VALUES (?, ?, ?)")->execute([$rt_num, $rw_id, $role === 'rt' ? $name : '-']);
                $rt_id = $pdo->lastInsertId();
            }
        }

        // INSERT KE TABEL USERS (Pakai no_telp & no_hp)
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, no_telp, no_hp, password, role, rt, rw, jenis_kelamin, status_verifikasi, desa_id, rw_id, rt_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $name, $email, $noTelpParam, $noTelpParam, $hash, $role, $rt_num, $rw_num, $jenisKelamin, $desa_id, $rw_id, $rt_id
        ]);
        $newId = $pdo->lastInsertId();

        $pdo->commit();

        log_activity('admin_create_user', "id=$newId role=$role email=$email");
        $_SESSION['flash'] = ['message' => "✅ User \"$name\" berhasil ditambahkan & disinkronkan!", 'type' => 'success'];
    } catch (Exception $e) {
        $pdo->rollBack();
        log_error('create user error', ['error' => $e->getMessage()]);
        $_SESSION['flash'] = ['message' => '❌ Gagal menambahkan: ' . $e->getMessage(), 'type' => 'error'];
        $_SESSION['old_input'] = $_POST;
        header('Location: /users?action=tambah'); exit;
    }

    header('Location: /users'); exit;
}

// ============================================
// UPDATE STATUS CEPAT
// ============================================
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = (int)($_POST['id'] ?? 0);
    $status_baru = $_POST['status_verifikasi'] ?? 'pending';
    
    $valid_status = ['pending', 'active', 'rejected', 'rt_approved'];
    if (in_array($status_baru, $valid_status) && $id_user > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status_verifikasi = ? WHERE id = ?");
            $stmt->execute([$status_baru, $id_user]);
            $_SESSION['flash'] = ['message' => '✅ Status verifikasi berhasil diubah!', 'type' => 'success'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['message' => '❌ Gagal mengubah status: ' . $e->getMessage(), 'type' => 'error'];
        }
    } else {
        $_SESSION['flash'] = ['message' => '❌ Status tidak valid.', 'type' => 'error'];
    }
    header('Location: /users'); exit;
}

// ============================================
// EDIT
// ============================================
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch();

    if (!$target) {
        $_SESSION['flash'] = ['message' => '❌ User tidak ditemukan.', 'type' => 'error'];
        header('Location: /users'); exit;
    }
    require_once __DIR__ . '/../../resources/views/admin/user_edit.php';
    exit;
}

// ============================================
// UPDATE (POST Handler) + AUTO SYNC RT/RW
// ============================================
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = (int)($_POST['id'] ?? 0);
    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $noTelp         = trim($_POST['no_telp'] ?? ''); // GANTI DARI no_hp KE no_telp
    $role           = trim($_POST['role'] ?? '');
    $rt             = trim($_POST['rt'] ?? '');
    $rw             = trim($_POST['rw'] ?? '');
    $jenisKelamin   = trim($_POST['jenis_kelamin'] ?? '');
    $wa_opt_in      = isset($_POST['wa_opt_in']) ? (int)$_POST['wa_opt_in'] : 1;
    $newPassword    = $_POST['new_password'] ?? '';
    $confirmPassword= $_POST['confirm_password'] ?? '';

    if (!in_array($jenisKelamin, ['L', 'P'])) $jenisKelamin = null;
    $noTelp = preg_replace('/[^0-9+]/', '', $noTelp);

    $errors = [];
    if ($name === '') $errors[] = 'Nama wajib diisi';
    if ($email === '') $errors[] = 'Email wajib diisi';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid';
    if (!in_array($role, $validRoles)) $errors[] = 'Role tidak valid';
    if ($noTelp !== '' && strlen($noTelp) < 9) $errors[] = 'No Telp minimal 9 digit';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) $errors[] = 'Email sudah dipakai user lain';

    if ($noTelp !== '') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (no_telp = ? OR no_hp = ?) AND id != ?");
        $stmt->execute([$noTelp, $noTelp, $id]);
        if ($stmt->fetch()) $errors[] = 'No Telp sudah dipakai user lain';
    }

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) $errors[] = 'Password minimal 6 karakter';
        if ($newPassword !== $confirmPassword) $errors[] = 'Konfirmasi password tidak cocok';
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['message' => '❌ ' . implode(', ', $errors), 'type' => 'error'];
        header('Location: /users?action=edit&id=' . $id); exit;
    }

    try {
        $pdo->beginTransaction();

        $noTelpParam = $noTelp !== '' ? $noTelp : null;
        $rt_num = $rt !== '' ? str_pad($rt, 2, '0', STR_PAD_LEFT) : null;
        $rw_num = $rw !== '' ? str_pad($rw, 2, '0', STR_PAD_LEFT) : null;

        $desa_id = 1;
        try {
            $stmtDesa = $pdo->query("SELECT id FROM desa LIMIT 1");
            $rowDesa = $stmtDesa->fetch();
            if ($rowDesa) $desa_id = $rowDesa['id'];
        } catch (Exception $e) {}

        $rt_id = null; $rw_id = null;
        
        if ($rw_num) {
            $cekRw = $pdo->prepare("SELECT id FROM rw WHERE nomor = ?");
            $cekRw->execute([$rw_num]);
            $rwRow = $cekRw->fetch();
            if ($rwRow) {
                $rw_id = $rwRow['id'];
                if ($role === 'rw') $pdo->prepare("UPDATE rw SET ketua = ? WHERE id = ?")->execute([$name, $rw_id]);
            } else {
                $pdo->prepare("INSERT INTO rw (nomor, ketua) VALUES (?, ?)")->execute([$rw_num, $role === 'rw' ? $name : '-']);
                $rw_id = $pdo->lastInsertId();
            }
        }

        if ($rt_num && $rw_id) {
            $cekRt = $pdo->prepare("SELECT id FROM rt WHERE nomor = ? AND rw_id = ?");
            $cekRt->execute([$rt_num, $rw_id]);
            $rtRow = $cekRt->fetch();
            if ($rtRow) {
                $rt_id = $rtRow['id'];
                if ($role === 'rt') $pdo->prepare("UPDATE rt SET ketua = ? WHERE id = ?")->execute([$name, $rt_id]);
            } else {
                $pdo->prepare("INSERT INTO rt (nomor, rw_id, ketua) VALUES (?, ?, ?)")->execute([$rt_num, $rw_id, $role === 'rt' ? $name : '-']);
                $rt_id = $pdo->lastInsertId();
            }
        }

        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            // UPDATE PAKAI no_telp & no_hp & wa_opt_in
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, no_telp=?, no_hp=?, role=?, rt=?, rw=?, jenis_kelamin=?, desa_id=?, rw_id=?, rt_id=?, wa_opt_in=?, wa_opt_in_at=NOW(), password=? WHERE id=?");
            $stmt->execute([$name, $email, $noTelpParam, $noTelpParam, $role, $rt_num, $rw_num, $jenisKelamin, $desa_id, $rw_id, $rt_id, $wa_opt_in, $hash, $id]);
        } else {
            // UPDATE PAKAI no_telp & no_hp & wa_opt_in
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, no_telp=?, no_hp=?, role=?, rt=?, rw=?, jenis_kelamin=?, desa_id=?, rw_id=?, rt_id=?, wa_opt_in=?, wa_opt_in_at=NOW() WHERE id=?");
            $stmt->execute([$name, $email, $noTelpParam, $noTelpParam, $role, $rt_num, $rw_num, $jenisKelamin, $desa_id, $rw_id, $rt_id, $wa_opt_in, $id]);
        }

        // Sinkronkan status persetujuan notifikasi WhatsApp
        WhatsAppConfig::setOptIn($id, $wa_opt_in === 1, $noTelpParam);

        if ($id === (int)$user['id']) {
            $stmt2 = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt2->execute([$id]);
            $fresh = $stmt2->fetch();
            if ($fresh) {
                $_SESSION['user']    = $fresh;
                $_SESSION['user_id'] = $fresh['id'];
            }
        }

        $pdo->commit();

        log_activity('admin_update_user', "id=$id role=$role hp=" . ($noTelpParam ?? '-'));
        $_SESSION['flash'] = ['message' => '✅ User berhasil diupdate & disinkronkan!', 'type' => 'success'];
    } catch (Exception $e) {
        $pdo->rollBack();
        log_error('update user error', ['error' => $e->getMessage()]);
        $_SESSION['flash'] = ['message' => '❌ Gagal update: ' . $e->getMessage(), 'type' => 'error'];
    }
    header('Location: /users'); exit;
}
// ============================================
// DELETE + AUTO SYNC RT/RW (CASCADE DELETE)
// ============================================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)$user['id']) {
        $_SESSION['flash'] = ['message' => '❌ Tidak bisa hapus akun sendiri.', 'type' => 'error'];
        header('Location: /users'); exit;
    }

    try {
        $pdo->beginTransaction();

        // Ambil data user beserta ID relasinya (rt_id & rw_id)
        $stmt = $pdo->prepare("SELECT role, rt, rw, rt_id, rw_id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $delUser = $stmt->fetch();

        if ($delUser) {
            $rt_num = !empty($delUser['rt']) ? str_pad($delUser['rt'], 2, '0', STR_PAD_LEFT) : null;
            $rw_num = !empty($delUser['rw']) ? str_pad($delUser['rw'], 2, '0', STR_PAD_LEFT) : null;
            $rt_id  = $delUser['rt_id'];
            $rw_id  = $delUser['rw_id'];

            // Fallback: Kalau ID-nya kosong, cari berdasarkan nomor
            if ($delUser['role'] === 'rw' && !$rw_id && $rw_num) {
                $stmtIdRw = $pdo->prepare("SELECT id FROM rw WHERE nomor = ?");
                $stmtIdRw->execute([$rw_num]);
                $rwRow = $stmtIdRw->fetch();
                if ($rwRow) $rw_id = $rwRow['id'];
            }
            
            if ($delUser['role'] === 'rt') {
                if (!$rt_id && $rt_num && $rw_num) {
                    $stmtIdRw = $pdo->prepare("SELECT id FROM rw WHERE nomor = ?");
                    $stmtIdRw->execute([$rw_num]);
                    $rwRow = $stmtIdRw->fetch();
                    if ($rwRow) {
                        $stmtIdRt = $pdo->prepare("SELECT id FROM rt WHERE nomor = ? AND rw_id = ?");
                        $stmtIdRt->execute([$rt_num, $rwRow['id']]);
                        $rtRow = $stmtIdRt->fetch();
                        if ($rtRow) $rt_id = $rtRow['id'];
                    }
                }
            }

            // 1. JIKA YANG DIHAPUS ADALAH KETUA RT
            if ($delUser['role'] === 'rt' && $rt_id) {
                // Reset relasi warga lain yang ada di RT ini biar gak error
                $pdo->prepare("UPDATE users SET rt_id = NULL, rt = NULL WHERE rt_id = ? AND id != ?")->execute([$rt_id, $id]);
                
                // HAPUS PERMANEN RT DARI MASTER DATA
                $pdo->prepare("DELETE FROM rt WHERE id = ?")->execute([$rt_id]);
            }

            // 2. JIKA YANG DIHAPUS ADALAH KETUA RW
            if ($delUser['role'] === 'rw' && $rw_id) {
                // Reset relasi warga/RT lain yang ada di RW ini
                $pdo->prepare("UPDATE users SET rw_id = NULL, rw = NULL, rt_id = NULL, rt = NULL WHERE rw_id = ? AND id != ?")->execute([$rw_id, $id]);
                
                // HAPUS PERMANEN SEMUA RT DI BAWAH RW INI
                $pdo->prepare("DELETE FROM rt WHERE rw_id = ?")->execute([$rw_id]);
                
                // HAPUS PERMANEN RW DARI MASTER DATA
                $pdo->prepare("DELETE FROM rw WHERE id = ?")->execute([$rw_id]);
            }
        }

        // 3. HAPUS AKUN USER
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        
        $pdo->commit();

        log_activity('admin_delete_user', "id=$id (Cascade Delete RT/RW)");
        $_SESSION['flash'] = ['message' => '✅ User beserta data RT/RW-nya berhasil dihapus permanen!', 'type' => 'success'];
    } catch (Exception $e) {
        $pdo->rollBack();
        log_error('delete user error', ['error' => $e->getMessage()]);
        $_SESSION['flash'] = ['message' => '❌ Gagal hapus: ' . $e->getMessage(), 'type' => 'error'];
    }
    header('Location: /users'); exit;
}
header('Location: /users');