<?php
// tests/AIConfigTest.php
// Script Pengujian Komprehensif Konfigurasi AI & Auto Failover

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/AIConfig.php';

echo "========================================================\n";
echo "🧪 PENGUJIAN KOMPREHENSIF FITUR KONFIGURASI AI SIWARGA\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name\n";
        $failed++;
    }
}

// -----------------------------------------------------------
// TEST 1: Helper AIConfig Loading & Default Values
// -----------------------------------------------------------
echo "--- TEST 1: AIConfig Loader & Masking ---\n";
$config = AIConfig::get();
assertTest(is_array($config), "AIConfig::get() mengembalikan array");
assertTest(isset($config['model']), "Model default tersedia: " . ($config['model'] ?? 'none'));
assertTest(isset($config['temperature']), "Temperature default tersedia: " . ($config['temperature'] ?? 'none'));

$masked = AIConfig::maskKey("");
assertTest(strpos($masked, '••••') !== false, "API Key berhasil dimask: $masked");
assertTest(AIConfig::isMasked($masked) === true, "AIConfig::isMasked() mendeteksi string yang disensor");
assertTest(AIConfig::isMasked("AIzaSyB1234567890abcdef") === false, "AIConfig::isMasked() mendeteksi string asli");

// -----------------------------------------------------------
// TEST 2: Dynamic Model Fetching dari Google Gemini API
// -----------------------------------------------------------
echo "\n--- TEST 2: Dynamic Model Fetching ---\n";
$fetchRes = AIConfig::fetchSupportedModels();
assertTest($fetchRes['success'] === true, "Fetch models berhasil menghubungi Google Gemini API");
assertTest(is_array($fetchRes['models']) && count($fetchRes['models']) > 0, "Mendapatkan " . count($fetchRes['models'] ?? []) . " model yang didukung");
assertTest(in_array('gemini-3.6-flash', $fetchRes['models'] ?? []), "Model 'gemini-3.6-flash' tersedia dalam daftar model");

// -----------------------------------------------------------
// TEST 3: Penyimpanan Konfigurasi & Pencegahan Overwrite Masked Key
// -----------------------------------------------------------
echo "\n--- TEST 3: Penyimpanan Konfigurasi & Safe Masking ---\n";
$originalKey = $config['api_key'];
$testData = [
    'model'            => 'gemini-3.6-flash',
    'temperature'      => 0.35,
    'api_key'          => $masked, // Kirim key yang sudah disensor
    'backup_keys'      => [
        ['key' => '', 'model' => 'gemini-3.5-flash']
    ],
    'system_prompt'    => 'System Prompt Uji Coba SiWarga',
    'fallback_message' => 'Pesan Fallback Uji Coba SiWarga',
];

$saveSuccess = AIConfig::save($testData);
assertTest($saveSuccess === true, "AIConfig::save() berhasil menyimpan ke storage/ai_config.json");

$reloaded = AIConfig::get();
assertTest($reloaded['model'] === 'gemini-3.6-flash', "Model tersimpan dengan benar: " . $reloaded['model']);
assertTest((float)$reloaded['temperature'] === 0.35, "Temperature tersimpan dengan benar: " . $reloaded['temperature']);
assertTest($reloaded['api_key'] === $originalKey, "Real API Key TIDAK tertimpa oleh masked string");
assertTest(count($reloaded['backup_keys']) === 1, "Backup key tersimpan: " . count($reloaded['backup_keys']));
assertTest($reloaded['system_prompt'] === 'System Prompt Uji Coba SiWarga', "System prompt tersimpan");

// -----------------------------------------------------------
// TEST 4: Hak Akses Role (Backend Guard Verification)
// -----------------------------------------------------------
echo "\n--- TEST 4: Hak Akses Role (Backend & Sidebar) ---\n";
assertTest(canAccess('ai-config') === false, "canAccess('ai-config') menolak guest (belum login)");

$_SESSION['user'] = ['id' => 999, 'name' => 'Warga Test', 'role' => 'warga'];
assertTest(canAccess('ai-config') === false, "canAccess('ai-config') menolak role 'warga'");

$_SESSION['user'] = ['id' => 998, 'name' => 'Desa Test', 'role' => 'desa'];
assertTest(canAccess('ai-config') === false, "canAccess('ai-config') menolak role 'desa'");

$_SESSION['user'] = ['id' => 997, 'name' => 'Admin Test', 'role' => 'super_admin'];
assertTest(canAccess('ai-config') === true, "canAccess('ai-config') mengizinkan role 'super_admin'");

// Periksa sidebar.php
$sidebarContent = file_get_contents(__DIR__ . '/../resources/views/layouts/sidebar.php');
assertTest(strpos($sidebarContent, "['label'=>'Konfigurasi AI',   'href'=>'/ai-config'") !== false, "Menu 'Konfigurasi AI' terpasang di sidebar.php");
// Pastikan tidak ada Konfigurasi AI di blok desa
$desaBlockStart = strpos($sidebarContent, "} elseif (\$role === 'desa') {");
$rwBlockStart   = strpos($sidebarContent, "} elseif (\$role === 'rw') {");
$desaBlock = substr($sidebarContent, $desaBlockStart, $rwBlockStart - $desaBlockStart);
assertTest(strpos($desaBlock, "'/ai-config'") === false, "Menu 'Konfigurasi AI' TIDAK muncul untuk role 'desa'");

// -----------------------------------------------------------
// TEST 5: Auto Failover Engine (Multi-Model & Multi-Key)
// -----------------------------------------------------------
echo "\n--- TEST 5: Auto Failover Engine ---\n";
$_SESSION['user'] = ['id' => 1, 'name' => 'Super Admin', 'role' => 'super_admin'];
$_GET['action'] = 'test_skip_view';
require_once __DIR__ . '/../app/Controllers/AIController.php';

// Simulasi user super admin
$testUser = [
    'id' => 1,
    'name' => 'Super Admin',
    'role' => 'super_admin',
    'rt' => '01',
    'rw' => '02'
];

// Test pemanggilan dengan rantai model failover (model pertama sengaja model 503, harus auto failover ke 3.6-flash)
$keyChains = [
    [
        'type'   => 'Utama',
        'key'    => $originalKey,
        'models' => ['gemini-3.8-flash', 'gemini-3.6-flash'] // 3.8-flash mengalami 503, akan failover ke 3.6-flash
    ]
];

$response = tanyaGeminiFailover("Berapa 1 + 1? Jawab hanya angka.", $testUser, $keyChains, 0.2);
echo "Jawaban AI: $response\n";
assertTest(!empty($response) && strpos($response, '🚨') === false, "Auto Model Failover berhasil merespons melalui fallback model!");

// -----------------------------------------------------------
// TEST 6: Multi-API Key Failover (Invalid Key -> Backup Key)
// -----------------------------------------------------------
echo "\n--- TEST 6: Multi-API Key Failover ---\n";
$keyChainsWithBadFirstKey = [
    [
        'type'   => 'Kunci Rusak Palsu',
        'key'    => 'INVALID_FAKE_KEY_1234567890',
        'models' => ['gemini-3.6-flash']
    ],
    [
        'type'   => 'Kunci Cadangan Valid',
        'key'    => $originalKey,
        'models' => ['gemini-3.6-flash']
    ]
];

$failoverResponse = tanyaGeminiFailover("Siapakah nama desa kita?", $testUser, $keyChainsWithBadFirstKey, 0.2);
echo "Jawaban Failover Key: $failoverResponse\n";
assertTest(!empty($failoverResponse) && strpos($failoverResponse, '🚨') === false, "Multi-API Key Failover berhasil beralih dari kunci rusak ke kunci cadangan valid!");

// Kembalikan konfigurasi normal
AIConfig::resetToDefault();

echo "\n========================================================\n";
echo "📊 RINGKASAN HASIL PENGUJIAN: $passed LULUS, $failed GAGAL\n";
echo "========================================================\n";

if ($failed === 0) {
    echo "🎉 SELURUH PENGUJIAN BERHASIL SEMPURNA!\n";
    exit(0);
} else {
    echo "⚠️ ADA PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
