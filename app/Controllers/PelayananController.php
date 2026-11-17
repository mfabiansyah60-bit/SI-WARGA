<?php
// app/Controllers/PelayananController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/functions.php';
require_once __DIR__ . '/../Models/Pelayanan.php';
require_once __DIR__ . '/../Models/Desa.php';
require_once __DIR__ . '/../Services/WhatsAppNotificationService.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = auth();
$role = $user['role'];

// Ambil action dari URL
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// ============================================
// HAK AKSES
// ============================================
// - super_admin: kelola semua
// - desa: kelola pelayanan desanya
// - warga: hanya lihat

switch ($action) {

    // ============================================
    // INDEX – List semua pelayanan
    // ============================================
    case 'index':
        if ($role === 'super_admin') {
            $pelayanan_list = Pelayanan::all();
        } elseif ($role === 'desa') {
            $desa_id = $user['desa_id'] ?? 1;
            $pelayanan_list = Pelayanan::findByDesa($desa_id);
        } else {
            $pelayanan_list = Pelayanan::all();
        }

        require_once __DIR__ . '/../../resources/views/pelayanan/index.php';
        break;

    // ============================================
    // TAMBAH – Form tambah pelayanan
    // ============================================
    case 'tambah':
        if (!in_array($role, ['super_admin', 'desa'])) {
            header('Location: /pelayanan');
            exit;
        }

        $desa_list = Desa::all();
        $mode = 'tambah';
        $pelayanan = null;

        require_once __DIR__ . '/../../resources/views/pelayanan/form.php';
        break;

    // ============================================
    // SIMPAN – Proses tambah pelayanan
    // ============================================
    case 'simpan':
        if (!in_array($role, ['super_admin', 'desa'])) {
            header('Location: /pelayanan');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'desa_id'   => $_POST['desa_id'] ?? null,
                'nama'      => trim($_POST['nama'] ?? ''),
                'deskripsi' => trim($_POST['deskripsi'] ?? ''),
                'syarat'    => trim($_POST['syarat'] ?? '')
            ];

            if (empty($data['nama']) || empty($data['desa_id'])) {
                $_SESSION['flash'] = [
                    'message' => '⚠️ Nama dan Desa wajib diisi!',
                    'type' => 'error'
                ];
                header('Location: /pelayanan?action=tambah');
                exit;
            }

            if ($role === 'desa') {
                $data['desa_id'] = $user['desa_id'] ?? 1;
            }

            Pelayanan::create($data);
            $newPelayananId = $pdo->lastInsertId();

            // Broadcast info pelayanan baru via WhatsApp ke Warga
            try {
                WhatsAppNotificationService::sendPelayananUpdate($newPelayananId, $data['nama'], $data['deskripsi'], $data['syarat'], 'tambah');
            } catch (Exception $eWa) {
                error_log('WhatsApp sendPelayananUpdate tambah error: ' . $eWa->getMessage());
            }

            $_SESSION['flash'] = [
                'message' => '✅ Pelayanan berhasil ditambahkan!',
                'type' => 'success'
            ];
            header('Location: /pelayanan');
            exit;
        }
        break;

    // ============================================
    // EDIT – Form edit pelayanan
    // ============================================
    case 'edit':
        if (!in_array($role, ['super_admin', 'desa'])) {
            header('Location: /pelayanan');
            exit;
        }

        if (!$id) {
            header('Location: /pelayanan');
            exit;
        }

        $pelayanan = Pelayanan::find($id);
        if (!$pelayanan) {
            $_SESSION['flash'] = [
                'message' => '❌ Pelayanan tidak ditemukan!',
                'type' => 'error'
            ];
            header('Location: /pelayanan');
            exit;
        }

        if ($role === 'desa' && $pelayanan['desa_id'] != ($user['desa_id'] ?? 1)) {
            $_SESSION['flash'] = [
                'message' => '❌ Anda tidak berhak mengedit pelayanan ini!',
                'type' => 'error'
            ];
            header('Location: /pelayanan');
            exit;
        }

        $desa_list = Desa::all();
        $mode = 'edit';

        require_once __DIR__ . '/../../resources/views/pelayanan/form.php';
        break;

    // ============================================
    // UPDATE – Proses update pelayanan
    // ============================================
    case 'update':
        if (!in_array($role, ['super_admin', 'desa'])) {
            header('Location: /pelayanan');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            $data = [
                'desa_id'   => $_POST['desa_id'] ?? null,
                'nama'      => trim($_POST['nama'] ?? ''),
                'deskripsi' => trim($_POST['deskripsi'] ?? ''),
                'syarat'    => trim($_POST['syarat'] ?? '')
            ];

            if (empty($data['nama']) || empty($data['desa_id'])) {
                $_SESSION['flash'] = [
                    'message' => '⚠️ Nama dan Desa wajib diisi!',
                    'type' => 'error'
                ];
                header('Location: /pelayanan?action=edit&id=' . $id);
                exit;
            }

            if ($role === 'desa') {
                $data['desa_id'] = $user['desa_id'] ?? 1;
            }

            Pelayanan::update($id, $data);

            // Broadcast info pembaruan pelayanan via WhatsApp ke Warga
            try {
                WhatsAppNotificationService::sendPelayananUpdate($id, $data['nama'], $data['deskripsi'], $data['syarat'], 'edit');
            } catch (Exception $eWa) {
                error_log('WhatsApp sendPelayananUpdate edit error: ' . $eWa->getMessage());
            }

            $_SESSION['flash'] = [
                'message' => '✅ Pelayanan berhasil diperbarui!',
                'type' => 'success'
            ];
            header('Location: /pelayanan');
            exit;
        }
        break;

    // ============================================
    // HAPUS – Hapus pelayanan
    // ============================================
    case 'hapus':
        if (!in_array($role, ['super_admin', 'desa'])) {
            header('Location: /pelayanan');
            exit;
        }

        if ($id) {
            $pelayanan = Pelayanan::find($id);
            
            if ($role === 'desa' && $pelayanan['desa_id'] != ($user['desa_id'] ?? 1)) {
                $_SESSION['flash'] = [
                    'message' => '❌ Anda tidak berhak menghapus pelayanan ini!',
                    'type' => 'error'
                ];
                header('Location: /pelayanan');
                exit;
            }

            Pelayanan::delete($id);

            $_SESSION['flash'] = [
                'message' => '✅ Pelayanan berhasil dihapus!',
                'type' => 'success'
            ];
        }
        header('Location: /pelayanan');
        break;

    // ============================================
    // DEFAULT
    // ============================================
    default:
        header('Location: /pelayanan');
        break;
}