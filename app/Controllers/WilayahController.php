<?php
// app/Controllers/WilayahController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();
$role = $user['role'];

// Hanya super_admin & desa
if (!in_array($role, ['super_admin', 'desa'])) {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak!', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$action = $_GET['action'] ?? 'index';
$type   = $_GET['type'] ?? 'desa';
$id     = $_GET['id'] ?? null;

// ============================================
// TAMBAH
// ============================================
if ($action === 'tambah') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if ($type === 'desa') {
            $data = [
                'nama'    => trim($_POST['nama'] ?? ''),
                'alamat'  => trim($_POST['alamat'] ?? ''),
                'telepon' => trim($_POST['telepon'] ?? '')
            ];
            
            if (empty($data['nama'])) {
                $_SESSION['flash'] = ['message' => '⚠️ Nama Desa wajib diisi!', 'type' => 'error'];
                header('Location: /wilayah?action=tambah&type=desa');
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO desa (nama, alamat, telepon) VALUES (?, ?, ?)");
                $stmt->execute([$data['nama'], $data['alamat'], $data['telepon']]);
                $_SESSION['flash'] = ['message' => '✅ Desa berhasil ditambahkan!', 'type' => 'success'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
            }
            
        } elseif ($type === 'rw') {
            $data = [
                'desa_id' => $_POST['desa_id'] ?? null,
                'nomor'   => trim($_POST['nomor'] ?? ''),
                'ketua'   => trim($_POST['ketua'] ?? '')
            ];
            
            if (empty($data['nomor']) || empty($data['desa_id'])) {
                $_SESSION['flash'] = ['message' => '⚠️ Desa & Nomor RW wajib diisi!', 'type' => 'error'];
                header('Location: /wilayah?action=tambah&type=rw');
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO rw (desa_id, nomor, ketua) VALUES (?, ?, ?)");
                $stmt->execute([$data['desa_id'], $data['nomor'], $data['ketua']]);
                $_SESSION['flash'] = ['message' => '✅ RW berhasil ditambahkan!', 'type' => 'success'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
            }
            
        } elseif ($type === 'rt') {
            $data = [
                'rw_id' => $_POST['rw_id'] ?? null,
                'nomor' => trim($_POST['nomor'] ?? ''),
                'ketua' => trim($_POST['ketua'] ?? '')
            ];
            
            if (empty($data['nomor']) || empty($data['rw_id'])) {
                $_SESSION['flash'] = ['message' => '⚠️ RW & Nomor RT wajib diisi!', 'type' => 'error'];
                header('Location: /wilayah?action=tambah&type=rt');
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO rt (rw_id, nomor, ketua) VALUES (?, ?, ?)");
                $stmt->execute([$data['rw_id'], $data['nomor'], $data['ketua']]);
                $_SESSION['flash'] = ['message' => '✅ RT berhasil ditambahkan!', 'type' => 'success'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Gagal: ' . $e->getMessage(), 'type' => 'error'];
            }
        }
        
        header('Location: /wilayah?type=' . $type);
        exit;
    }
    
    require_once __DIR__ . '/../../resources/views/wilayah/form.php';
    exit;
}

// ============================================
// EDIT
// ============================================
if ($action === 'edit' && $id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if ($type === 'desa') {
            $data = [
                'nama'    => trim($_POST['nama'] ?? ''),
                'alamat'  => trim($_POST['alamat'] ?? ''),
                'telepon' => trim($_POST['telepon'] ?? '')
            ];
            
            try {
                $stmt = $pdo->prepare("UPDATE desa SET nama=?, alamat=?, telepon=? WHERE id=?");
                $stmt->execute([$data['nama'], $data['alamat'], $data['telepon'], $id]);
                $_SESSION['flash'] = ['message' => '✅ Desa berhasil diupdate!', 'type' => 'success'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Gagal!', 'type' => 'error'];
            }
            
        } elseif ($type === 'rw') {
            $data = [
                'desa_id' => $_POST['desa_id'] ?? null,
                'nomor'   => trim($_POST['nomor'] ?? ''),
                'ketua'   => trim($_POST['ketua'] ?? '')
            ];
            
            try {
                $stmt = $pdo->prepare("UPDATE rw SET desa_id=?, nomor=?, ketua=? WHERE id=?");
                $stmt->execute([$data['desa_id'], $data['nomor'], $data['ketua'], $id]);
                $_SESSION['flash'] = ['message' => '✅ RW berhasil diupdate!', 'type' => 'success'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Gagal!', 'type' => 'error'];
            }
            
        } elseif ($type === 'rt') {
            $data = [
                'rw_id' => $_POST['rw_id'] ?? null,
                'nomor' => trim($_POST['nomor'] ?? ''),
                'ketua' => trim($_POST['ketua'] ?? '')
            ];
            
            try {
                $stmt = $pdo->prepare("UPDATE rt SET rw_id=?, nomor=?, ketua=? WHERE id=?");
                $stmt->execute([$data['rw_id'], $data['nomor'], $data['ketua'], $id]);
                $_SESSION['flash'] = ['message' => '✅ RT berhasil diupdate!', 'type' => 'success'];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Gagal!', 'type' => 'error'];
            }
        }
        
        header('Location: /wilayah?type=' . $type);
        exit;
    }
    
    // Ambil data untuk form
    $item = null;
    try {
        if ($type === 'desa') {
            $stmt = $pdo->prepare("SELECT * FROM desa WHERE id=?");
        } elseif ($type === 'rw') {
            $stmt = $pdo->prepare("SELECT * FROM rw WHERE id=?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM rt WHERE id=?");
        }
        $stmt->execute([$id]);
        $item = $stmt->fetch();
    } catch (Exception $e) {
        $item = null;
    }
    
    if (!$item) {
        $_SESSION['flash'] = ['message' => '❌ Data tidak ditemukan!', 'type' => 'error'];
        header('Location: /wilayah?type=' . $type);
        exit;
    }
    
    // Data untuk dropdown
    try {
        $desa_list = $pdo->query("SELECT * FROM desa ORDER BY nama")->fetchAll();
        $rw_list = $pdo->query("SELECT rw.*, desa.nama as desa_nama FROM rw JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor")->fetchAll();
    } catch (Exception $e) {
        $desa_list = [];
        $rw_list = [];
    }
    
    require_once __DIR__ . '/../../resources/views/wilayah/form.php';
    exit;
}

// ============================================
// HAPUS
// ============================================
if ($action === 'hapus' && $id) {
    try {
        if ($type === 'desa') {
            $pdo->prepare("DELETE FROM desa WHERE id=?")->execute([$id]);
        } elseif ($type === 'rw') {
            $pdo->prepare("DELETE FROM rw WHERE id=?")->execute([$id]);
        } else {
            $pdo->prepare("DELETE FROM rt WHERE id=?")->execute([$id]);
        }
        $_SESSION['flash'] = ['message' => '✅ Data berhasil dihapus!', 'type' => 'success'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['message' => '❌ Gagal hapus!', 'type' => 'error'];
    }
    header('Location: /wilayah?type=' . $type);
    exit;
}

// ============================================
// INDEX - List semua
// ============================================
try {
    $desa_list = $pdo->query("SELECT * FROM desa ORDER BY nama")->fetchAll();
    $rw_list = $pdo->query("SELECT rw.*, desa.nama as desa_nama FROM rw JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor")->fetchAll();
    $rt_list = $pdo->query("SELECT rt.*, rw.nomor as rw_nomor, desa.nama as desa_nama FROM rt JOIN rw ON rt.rw_id = rw.id JOIN desa ON rw.desa_id = desa.id ORDER BY desa.nama, rw.nomor, rt.nomor")->fetchAll();
} catch (Exception $e) {
    $desa_list = [];
    $rw_list = [];
    $rt_list = [];
}

require_once __DIR__ . '/../../resources/views/wilayah/index.php';