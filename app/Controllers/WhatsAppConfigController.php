<?php
// app/Controllers/WhatsAppConfigController.php
// Controller Konfigurasi WhatsApp Gateway Fonnte — Khusus Super Admin

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../../app/Helpers/WhatsAppConfig.php';

// Proteksi Autentikasi
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = auth();

// Proteksi Hak Akses: HANYA Super Admin
if (($user['role'] ?? '') !== 'super_admin') {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak. Halaman Konfigurasi WhatsApp hanya untuk Super Admin.', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$action = $_GET['action'] ?? 'index';

// ============================================
// 1. AJAX: CEK STATUS KONEKSI & VALIDASI API KEY
// ============================================
if ($action === 'check_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');

    $inputKey = trim($_POST['api_key'] ?? '');
    $current = WhatsAppConfig::get();

    // Jika inputKey kosong atau bertopeng (masked), gunakan key yang tersimpan di sistem
    if (empty($inputKey) || WhatsAppConfig::isMasked($inputKey)) {
        $apiKeyToTest = $current['api_key'];
    } else {
        $apiKeyToTest = $inputKey;
    }

    $result = WhatsAppConfig::checkStatus($apiKeyToTest);

    // Amankan output JSON agar tidak membocorkan real API key
    unset($result['api_key']);

    echo json_encode($result);
    exit;
}

// ============================================
// 2. AJAX: TES PENGIRIMAN PESAN WHATSAPP
// ============================================
if ($action === 'send_test' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');

    $targetNumber = trim($_POST['target_phone'] ?? '');
    $customMessage = trim($_POST['test_message'] ?? '');
    $inputKey = trim($_POST['api_key'] ?? '');
    $current = WhatsAppConfig::get();

    if (empty($inputKey) || WhatsAppConfig::isMasked($inputKey)) {
        $apiKeyToUse = $current['api_key'];
    } else {
        $apiKeyToUse = $inputKey;
    }

    if (empty($targetNumber)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nomor WhatsApp penerima tes wajib diisi.',
        ]);
        exit;
    }

    $result = WhatsAppConfig::sendTestMessage($targetNumber, $customMessage, $apiKeyToUse);

    // Catat log pengiriman tes tanpa membocorkan kredensial
    $maskedTarget = substr($targetNumber, 0, 4) . '****' . substr($targetNumber, -3);
    log_activity('whatsapp_test_sent', "Tes kirim WhatsApp ke $maskedTarget: " . ($result['success'] ? 'Sukses' : 'Gagal'));

    echo json_encode($result);
    exit;
}

// ============================================
// 3. RESET SETELAN AWAL
// ============================================
if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    WhatsAppConfig::resetToDefault();
    $_SESSION['flash'] = ['message' => '✅ Konfigurasi WhatsApp berhasil direset ke setelan awal!', 'type' => 'success'];
    header('Location: /whatsapp-config');
    exit;
}

// ============================================
// 4. SIMPAN KONFIGURASI
// ============================================
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $isActive     = isset($_POST['is_active']) ? 1 : 0;
    $apiKey       = trim($_POST['api_key'] ?? '');
    $senderNumber = trim($_POST['sender_number'] ?? '');
    $countryCode  = trim($_POST['country_code'] ?? '62');
    $queueDelay   = isset($_POST['queue_delay_seconds']) ? max(1, (int)$_POST['queue_delay_seconds']) : 60;

    $saveData = [
        'is_active'            => $isActive,
        'sender_number'        => $senderNumber,
        'country_code'         => $countryCode,
        'queue_delay_seconds'  => $queueDelay,
    ];

    // Hanya update API Key jika user mengisi key baru yang bukan masked (bintang-bintang/bullet)
    if (!empty($apiKey) && !WhatsAppConfig::isMasked($apiKey)) {
        $saveData['api_key'] = $apiKey;

        // Sekaligus verifikasi status perangkat
        $statusRes = WhatsAppConfig::checkStatus($apiKey);
        $saveData['last_status'] = $statusRes;
        $saveData['last_checked_at'] = date('Y-m-d H:i:s');
        if (empty($senderNumber) && !empty($statusRes['device']) && $statusRes['device'] !== '-') {
            $saveData['sender_number'] = $statusRes['device'];
        }
    }

    $saved = WhatsAppConfig::save($saveData);

    if ($saved) {
        $statusStr = $isActive ? 'Aktif' : 'Nonaktif';
        log_activity('whatsapp_config_updated', "Status: $statusStr, Pengirim: " . ($senderNumber ?: 'Auto'));
        $_SESSION['flash'] = ['message' => '✅ Konfigurasi WhatsApp berhasil disimpan!', 'type' => 'success'];
    } else {
        $_SESSION['flash'] = ['message' => '❌ Gagal menyimpan konfigurasi WhatsApp. Periksa izin folder storage.', 'type' => 'error'];
    }

    header('Location: /whatsapp-config');
    exit;
}

// ============================================
// 5. INDEX (TAMPILAN KONFIGURASI WHATSAPP)
// ============================================
$config = WhatsAppConfig::get();

// Masking API key untuk keamanan tampilan frontend
$maskedApiKey = WhatsAppConfig::maskKey($config['api_key']);
$hasRealApiKey = !empty($config['api_key']);

$page_title = 'Konfigurasi WhatsApp';
require_once __DIR__ . '/../../resources/views/admin/whatsapp_config.php';

