<?php
// tests/WhatsAppConfigTest.php
// Script Pengujian Komprehensif Konfigurasi WhatsApp Gateway Fonnte & RBAC

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/AIConfig.php';
require_once __DIR__ . '/../app/Helpers/WhatsAppConfig.php';

echo "========================================================\n";
echo "🧪 PENGUJIAN KOMPREHENSIF KONFIGURASI WHATSAPP SIWARGA\n";
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
// TEST 1: Helper WhatsAppConfig Loading & Default Values
// -----------------------------------------------------------
echo "--- TEST 1: WhatsAppConfig Loader & Masking ---\n";
$config = WhatsAppConfig::get();
assertTest(is_array($config), "WhatsAppConfig::get() mengembalikan array");
assertTest(isset($config['is_active']), "Status is_active tersedia: " . ($config['is_active'] ? 'true' : 'false'));
assertTest(isset($config['provider']) && $config['provider'] === 'fonnte', "Provider default Fonnte: " . ($config['provider'] ?? 'none'));

$testKey = "FONNTE_SAMPLE_TOKEN_SECRET_XYZ123456789";
$masked = WhatsAppConfig::maskKey($testKey);
assertTest(strpos($masked, '••••') !== false, "API Key berhasil dimask: $masked");
assertTest(substr($masked, 0, 4) === 'FONN', "Prefix 4 karakter awal dipertahankan: " . substr($masked, 0, 4));
assertTest(substr($masked, -4) === '6789', "Suffix 4 karakter akhir dipertahankan: " . substr($masked, -4));
assertTest(WhatsAppConfig::isMasked($masked) === true, "WhatsAppConfig::isMasked() mendeteksi string yang disensor");
assertTest(WhatsAppConfig::isMasked("fonnte_token_1234567890") === false, "WhatsAppConfig::isMasked() mendeteksi string asli");

// -----------------------------------------------------------
// TEST 2: Formatting Nomor Telepon Pengirim
// -----------------------------------------------------------
echo "\n--- TEST 2: Format Nomor WhatsApp Pengirim ---\n";
assertTest(WhatsAppConfig::formatPhoneNumber("081234567890") === "6281234567890", "Format 08xx -> 628xx");
assertTest(WhatsAppConfig::formatPhoneNumber("6281234567890") === "6281234567890", "Format 628xx tetap 628xx");
assertTest(WhatsAppConfig::formatPhoneNumber("81234567890") === "6281234567890", "Format 8xx -> 628xx");
assertTest(WhatsAppConfig::formatPhoneNumber("+62 812-3456-7890") === "6281234567890", "Pembersihan karakter spasi, plus, strip");

// -----------------------------------------------------------
// TEST 3: Penyimpanan Konfigurasi & Safe Masking (Tidak Menimpa Key Asli)
// -----------------------------------------------------------
$originalConfigBackup = WhatsAppConfig::get();
echo "\n--- TEST 3: Penyimpanan Konfigurasi & Safe Masking ---\n";
$saveData1 = [
    'is_active'     => 1,
    'api_key'       => 'FONNTE_TEST_REAL_KEY_SECURE_9999',
    'sender_number' => '085712345678',
    'country_code'  => '62',
];
$ok1 = WhatsAppConfig::save($saveData1);
assertTest($ok1 === true, "WhatsAppConfig::save() berhasil menyimpan konfigurasi baru");

$reloaded1 = WhatsAppConfig::get();
assertTest($reloaded1['is_active'] === true, "is_active tersimpan sebagai boolean true");
assertTest($reloaded1['sender_number'] === '6285712345678', "Nomor pengirim terformat 6285712345678: " . $reloaded1['sender_number']);
assertTest($reloaded1['api_key'] === 'FONNTE_TEST_REAL_KEY_SECURE_9999', "Real API Key tersimpan aman");

// Uji simpan dengan input MASKED key (seperti saat user klik Simpan tanpa ubah key)
$maskedInput = WhatsAppConfig::maskKey('FONNTE_TEST_REAL_KEY_SECURE_9999');
$saveData2 = [
    'is_active'     => 0,
    'api_key'       => $maskedInput, // Kirim string bertopeng
    'sender_number' => '089988776655',
];
$ok2 = WhatsAppConfig::save($saveData2);
assertTest($ok2 === true, "WhatsAppConfig::save() dengan masked key berhasil diproses");

$reloaded2 = WhatsAppConfig::get();
assertTest($reloaded2['is_active'] === false, "is_active berhasil diubah menjadi false (nonaktif)");
assertTest($reloaded2['sender_number'] === '6289988776655', "Nomor pengirim berhasil diperbarui: " . $reloaded2['sender_number']);
assertTest($reloaded2['api_key'] === 'FONNTE_TEST_REAL_KEY_SECURE_9999', "Real API Key TIDAK tertimpa oleh masked string (Aman dari kebocoran/korupsi)");

// -----------------------------------------------------------
// TEST 4: Hak Akses Role (Super Admin Only)
// -----------------------------------------------------------
echo "\n--- TEST 4: Hak Akses Role (Super Admin Only) ---\n";
unset($_SESSION['user']);
assertTest(canAccess('whatsapp-config') === false, "canAccess('whatsapp-config') menolak guest");

$_SESSION['user'] = ['id' => 101, 'name' => 'Warga Test', 'role' => 'warga'];
assertTest(canAccess('whatsapp-config') === false, "canAccess('whatsapp-config') menolak role 'warga'");

$_SESSION['user'] = ['id' => 102, 'name' => 'RT Test', 'role' => 'rt'];
assertTest(canAccess('whatsapp-config') === false, "canAccess('whatsapp-config') menolak role 'rt'");

$_SESSION['user'] = ['id' => 103, 'name' => 'RW Test', 'role' => 'rw'];
assertTest(canAccess('whatsapp-config') === false, "canAccess('whatsapp-config') menolak role 'rw'");

$_SESSION['user'] = ['id' => 104, 'name' => 'Desa Test', 'role' => 'desa'];
assertTest(canAccess('whatsapp-config') === false, "canAccess('whatsapp-config') menolak role 'desa'");

$_SESSION['user'] = ['id' => 105, 'name' => 'Super Admin Test', 'role' => 'super_admin'];
assertTest(canAccess('whatsapp-config') === true, "canAccess('whatsapp-config') mengizinkan role 'super_admin'");

// -----------------------------------------------------------
// TEST 5: Validasi Integrasi Sidebar
// -----------------------------------------------------------
echo "\n--- TEST 5: Validasi Sidebar Navigation ---\n";
$sidebarContent = file_get_contents(__DIR__ . '/../resources/views/layouts/sidebar.php');
assertTest(strpos($sidebarContent, "['label'=>'Konfigurasi WhatsApp', 'href'=>'/whatsapp-config'") !== false, "Menu 'Konfigurasi WhatsApp' terpasang pada super_admin di sidebar.php");

// Pastikan menu ini TIDAK ada pada blok role lain di sidebar.php
$menuSectionStart = strpos($sidebarContent, '$menus = [];');
$desaBlockStart = strpos($sidebarContent, "} elseif (\$role === 'desa') {", $menuSectionStart);
$rwBlockStart   = strpos($sidebarContent, "} elseif (\$role === 'rw') {", $menuSectionStart);
$rtBlockStart   = strpos($sidebarContent, "} elseif (\$role === 'rt') {", $menuSectionStart);
$wargaBlockStart= strpos($sidebarContent, "} else {", $menuSectionStart);

$desaBlock = substr($sidebarContent, $desaBlockStart, $rwBlockStart - $desaBlockStart);
assertTest(strpos($desaBlock, "'/whatsapp-config'") === false, "Menu WhatsApp TIDAK muncul pada role 'desa'");

$rwBlock = substr($sidebarContent, $rwBlockStart, $rtBlockStart - $rwBlockStart);
assertTest(strpos($rwBlock, "'/whatsapp-config'") === false, "Menu WhatsApp TIDAK muncul pada role 'rw'");

$rtBlock = substr($sidebarContent, $rtBlockStart, $wargaBlockStart - $rtBlockStart);
assertTest(strpos($rtBlock, "'/whatsapp-config'") === false, "Menu WhatsApp TIDAK muncul pada role 'rt'");

$wargaBlock = substr($sidebarContent, $wargaBlockStart);
assertTest(strpos($wargaBlock, "'/whatsapp-config'") === false, "Menu WhatsApp TIDAK muncul pada role 'warga'");

// -----------------------------------------------------------
// TEST 6: Isolasi & Keutuhan Konfigurasi AI Lama (Zero Regression)
// -----------------------------------------------------------
echo "\n--- TEST 6: Verifikasi Konfigurasi AI Lama Tetap Utuh ---\n";
$aiConfig = AIConfig::get();
assertTest(!empty($aiConfig['api_key']), "API Key AI lama masih ada & tidak terhapus");
assertTest(!empty($aiConfig['model']), "Model AI lama masih aktif: " . ($aiConfig['model'] ?? 'none'));
assertTest(!empty($aiConfig['available_models']), "Daftar model AI lama masih utuh: " . count($aiConfig['available_models']) . " models");
assertTest(is_array($aiConfig['backup_keys']), "Backup failover keys AI lama masih utuh: " . count($aiConfig['backup_keys']) . " keys");

// -----------------------------------------------------------
// TEST 7: Respon API Gateway Fonnte Handlers
// -----------------------------------------------------------
echo "\n--- TEST 7: Respon Handler Status & Send Test ---\n";
// Uji checkStatus dengan dummy key
$statusCheck = WhatsAppConfig::checkStatus("DUMMY_INVALID_KEY_TEST");
assertTest(isset($statusCheck['connected']), "checkStatus mengembalikan field 'connected'");
assertTest(isset($statusCheck['device_status']), "checkStatus mengembalikan field 'device_status'");
assertTest($statusCheck['connected'] === false, "checkStatus mendeteksi key dummy sebagai disconnected/invalid");

// Uji sendTestMessage saat target kosong atau key dummy
$sendTest1 = WhatsAppConfig::sendTestMessage("", "Halo", "DUMMY_KEY");
assertTest($sendTest1['success'] === false, "sendTestMessage menolak nomor tujuan kosong");

$sendTest2 = WhatsAppConfig::sendTestMessage("08123456789", "Halo", "DUMMY_KEY");
assertTest(isset($sendTest2['success']), "sendTestMessage merespons secara terstruktur tanpa fatal error");

WhatsAppConfig::save($originalConfigBackup);
echo "\n========================================================\n";
echo "📊 HASIL PENGUJIAN: $passed PASS | $failed FAIL\n";
echo "========================================================\n";

if ($failed === 0) {
    echo "🎉 SEMPURNA! SELURUH PENGUJIAN WHATSAPP CONFIG BERHASIL 100%!\n";
    exit(0);
} else {
    echo "❌ ADA PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
