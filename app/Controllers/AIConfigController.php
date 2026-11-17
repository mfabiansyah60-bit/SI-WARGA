<?php
// app/Controllers/AIConfigController.php
// Controller Konfigurasi AI — Khusus Super Admin

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../../app/Helpers/AIConfig.php';

// Proteksi Autentikasi
if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$user = auth();

// Proteksi Hak Akses: HANYA Super Admin
if (($user['role'] ?? '') !== 'super_admin') {
    $_SESSION['flash'] = ['message' => '❌ Akses ditolak. Halaman Konfigurasi AI hanya untuk Super Admin.', 'type' => 'error'];
    header('Location: /dashboard');
    exit;
}

$action = $_GET['action'] ?? 'index';

// ============================================
// 1. AJAX: FETCH DYNAMIC MODELS VIA API KEY
// ============================================
if ($action === 'fetch_models') {
    ob_clean();
    header('Content-Type: application/json');

    $inputKey = trim($_POST['api_key'] ?? '');
    $current = AIConfig::get();

    // Jika key kosong atau bertopeng, gunakan key tersimpan
    if (empty($inputKey) || AIConfig::isMasked($inputKey)) {
        $apiKeyToTest = $current['api_key'];
    } else {
        $apiKeyToTest = $inputKey;
    }

    $result = AIConfig::fetchSupportedModels($apiKeyToTest);

    // Jika berhasil dan menggunakan key tersimpan, simpan models ke cache file
    if ($result['success'] && !empty($result['models'])) {
        $current['available_models'] = $result['models'];
        AIConfig::save($current);
    }

    echo json_encode($result);
    exit;
}

// ============================================
// 2. RESET SETELAN AWAL
// ============================================
if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AIConfig::resetToDefault();
    $_SESSION['flash'] = ['message' => '✅ Konfigurasi AI berhasil direset ke setelan awal!', 'type' => 'success'];
    header('Location: /ai-config');
    exit;
}

// ============================================
// 3. SIMPAN KONFIGURASI
// ============================================
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey         = trim($_POST['api_key'] ?? '');
    $model          = trim($_POST['model'] ?? 'gemini-3.6-flash');
    $temperature    = (float)($_POST['temperature'] ?? 0.5);
    $systemPrompt   = trim($_POST['system_prompt'] ?? '');
    $fallbackMsg    = trim($_POST['fallback_message'] ?? '');

    // Backup keys
    $backupKeysRaw   = $_POST['backup_keys'] ?? [];
    $backupModelsRaw = $_POST['backup_models'] ?? [];
    $backupKeys = [];

    if (is_array($backupKeysRaw)) {
        foreach ($backupKeysRaw as $i => $bKey) {
            $bKey = trim($bKey);
            $bModel = trim($backupModelsRaw[$i] ?? $model);
            if (!empty($bKey)) {
                $backupKeys[] = [
                    'key'   => $bKey,
                    'model' => $bModel,
                ];
            }
        }
    }

    $saveData = [
        'model'            => $model,
        'temperature'      => $temperature,
        'backup_keys'      => $backupKeys,
        'system_prompt'    => $systemPrompt,
        'fallback_message' => $fallbackMsg,
    ];

    // Hanya update API key jika user menginputkan key baru (bukan masked / bukan bintang-bintang)
    if (!empty($apiKey) && !AIConfig::isMasked($apiKey)) {
        $saveData['api_key'] = $apiKey;

        // Sekaligus verifikasi dan deteksi model
        $fetchRes = AIConfig::fetchSupportedModels($apiKey);
        if ($fetchRes['success'] && !empty($fetchRes['models'])) {
            $saveData['available_models'] = $fetchRes['models'];
        }
    }

    $saved = AIConfig::save($saveData);

    if ($saved) {
        log_activity('ai_config_updated', "Model: $model, Temp: $temperature, Backups: " . count($backupKeys));
        $_SESSION['flash'] = ['message' => '✅ Konfigurasi AI berhasil disimpan!', 'type' => 'success'];
    } else {
        $_SESSION['flash'] = ['message' => '❌ Gagal menyimpan konfigurasi AI. Periksa izin folder storage.', 'type' => 'error'];
    }

    header('Location: /ai-config');
    exit;
}

// ============================================
// 4. INDEX (TAMPILAN KONFIGURASI AI)
// ============================================
$config = AIConfig::get();

// Mask key untuk tampilan
$maskedApiKey = AIConfig::maskKey($config['api_key']);
$hasRealApiKey = !empty($config['api_key']);

$maskedBackupKeys = [];
foreach ($config['backup_keys'] as $bk) {
    $maskedBackupKeys[] = [
        'key'        => AIConfig::maskKey($bk['key']),
        'real_key'   => $bk['key'],
        'model'      => $bk['model'] ?? $config['model'],
    ];
}

$page_title = 'Konfigurasi AI';
require_once __DIR__ . '/../../resources/views/admin/ai_config.php';
