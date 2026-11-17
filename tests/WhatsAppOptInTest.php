<?php
// tests/WhatsAppOptInTest.php
// Script Pengujian Komprehensif Fitur Persetujuan Notifikasi WhatsApp SiWarga

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/WhatsAppConfig.php';
require_once __DIR__ . '/../app/Services/WhatsAppNotificationService.php';

echo "========================================================\n";
echo "🧪 PENGUJIAN FITUR PERSETUJUAN NOTIFIKASI WHATSAPP SIWARGA\n";
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
// TEST 1: Validator Nomor Ponsel & Deteksi Nomor Dummy
// -----------------------------------------------------------
echo "--- TEST 1: Validator Nomor Ponsel & Anti-Dummy ---\n";
// Valid Indonesian mobile numbers
assertTest(WhatsAppConfig::isValidPhoneNumber('089610008653') === true, "Nomor valid 089610008653 diterima");
assertTest(WhatsAppConfig::isValidPhoneNumber('+6281572966945') === true, "Nomor valid +6281572966945 diterima");
assertTest(WhatsAppConfig::isValidPhoneNumber('628319994300') === true, "Nomor valid 628319994300 diterima");
assertTest(WhatsAppConfig::isValidPhoneNumber('082114122927') === true, "Nomor valid 082114122927 diterima");

// Dummy / Invalid numbers
assertTest(WhatsAppConfig::isValidPhoneNumber('') === false, "Nomor kosong ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('00000000000') === false, "Nomor dummy all zeros ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('11111111111') === false, "Nomor dummy all ones ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('08123456789') === false, "Nomor urutan 123456789 ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('08000000000') === false, "Nomor dummy berakhiran >= 6 nol berturut ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('0215556667') === false, "Nomor PSTN / Landline Jakarta (021) ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('12345') === false, "Nomor terlalu pendek (<10 digit) ditolak");
assertTest(WhatsAppConfig::isValidPhoneNumber('0812345678901234567') === false, "Nomor terlalu panjang (>15 digit) ditolak");

// -----------------------------------------------------------
// TEST 2: Penyimpanan Status Persetujuan & Waktu di Database Aktual
// -----------------------------------------------------------
echo "\n--- TEST 2: Status Persetujuan & Timestamp di Database Aktual ---\n";

// Buat atau gunakan user test khusus
$testEmail = 'optin_test_user@siwarga.test';
$pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$testEmail]);
$stmtInsert = $pdo->prepare("
    INSERT INTO users (name, email, no_hp, no_telp, password, role, rt, rw, status_verifikasi, wa_opt_in, wa_opt_in_at, created_at)
    VALUES ('OptIn Tester', ?, '089612345678', '089612345678', 'hash', 'warga', '01', '01', 'active', 1, NOW(), NOW())
");
$stmtInsert->execute([$testEmail]);
$testUserId = (int)$pdo->lastInsertId();

assertTest($testUserId > 0, "User uji coba berhasil dibuat dengan ID: $testUserId");

// Ambil status awal
$details1 = WhatsAppConfig::getOptInDetails($testUserId);
assertTest($details1['opted_in'] === true, "Status default user baru adalah Opt-In (true)");
assertTest(!empty($details1['opted_at']), "Waktu persetujuan awal tercatat: " . $details1['opted_at']);
assertTest(WhatsAppConfig::isUserOptedIn($testUserId, '089612345678') === true, "isUserOptedIn() bernilai true");

// Lakukan Opt-Out (Mencabut persetujuan)
sleep(1); // Jeda 1 detik agar timestamp berbeda
$okOptOut = WhatsAppConfig::setOptIn($testUserId, false, '089612345678');
assertTest($okOptOut === true, "WhatsAppConfig::setOptIn(false) berhasil");

$details2 = WhatsAppConfig::getOptInDetails($testUserId);
assertTest($details2['opted_in'] === false, "Status setelah Opt-Out adalah false");
assertTest(!empty($details2['opted_at']) && $details2['opted_at'] !== $details1['opted_at'], "Waktu pencabutan persetujuan terupdate di DB: " . $details2['opted_at']);
assertTest(WhatsAppConfig::isUserOptedIn($testUserId, '089612345678') === false, "isUserOptedIn() kini bernilai false");

// -----------------------------------------------------------
// TEST 3: Pembatalan Otomatis Antrean Pending Saat Warga Opt-Out
// -----------------------------------------------------------
echo "\n--- TEST 3: Otomatisasi Pembatalan Antrean Pending Saat Opt-Out ---\n";

// Kembalikan ke Opt-In sementara untuk menambahkan antrean
WhatsAppConfig::setOptIn($testUserId, true, '089612345678');

$testBatch = "test_optout_batch_" . time();
$qId = WhatsAppNotificationService::enqueueMessage($testBatch, $testUserId, '6289612345678', 'OptIn Tester', 'test_notif', 'Pesan uji coba', null, 3);
assertTest($qId > 0, "Pesan uji berhasil masuk antrean whatsapp_queue (ID: $qId)");

// Verifikasi status pesan awal adalah 'pending'
$stmtQ = $pdo->prepare("SELECT status FROM whatsapp_queue WHERE id = ?");
$stmtQ->execute([$qId]);
assertTest($stmtQ->fetchColumn() === 'pending', "Status antrean sebelum opt-out adalah 'pending'");

// Sekarang user mencabut persetujuan (Opt-Out)
WhatsAppConfig::setOptIn($testUserId, false, '089612345678');

// Verifikasi pesan pending user tersebut otomatis berubah jadi 'cancelled'
$stmtQ->execute([$qId]);
$statusAfter = $stmtQ->fetchColumn();
assertTest($statusAfter === 'cancelled', "Antrean pending OTOMATIS dibatalkan (status: '$statusAfter') saat user opt-out");

// -----------------------------------------------------------
// TEST 4: Filter Antrean - Pengabaian Nomor Dummy & Nomor Belum Diverifikasi
// -----------------------------------------------------------
echo "\n--- TEST 4: Filter Antrean Pengumuman (Tolak Dummy, Belum Diverifikasi, & Opt-Out) ---\n";

// Buat 3 user pendukung untuk verifikasi:
// User A: Belum diverifikasi (status_verifikasi = 'pending')
$pdo->prepare("DELETE FROM users WHERE email IN ('pending_user@siwarga.test', 'optout_user@siwarga.test', 'dummyphone_user@siwarga.test')")->execute();

$pdo->prepare("
    INSERT INTO users (name, email, no_hp, no_telp, password, role, rt, rw, status_verifikasi, wa_opt_in, wa_opt_in_at, created_at)
    VALUES 
    ('User Pending', 'pending_user@siwarga.test', '089611112222', '089611112222', 'h', 'warga', '99', '99', 'pending', 1, NOW(), NOW()),
    ('User OptOut', 'optout_user@siwarga.test', '089633334444', '089633334444', 'h', 'warga', '99', '99', 'active', 0, NOW(), NOW()),
    ('User DummyPhone', 'dummyphone_user@siwarga.test', '000000000000', '000000000000', 'h', 'warga', '99', '99', 'active', 1, NOW(), NOW())
")->execute();

// Uji sendPengumuman untuk wilayah RT 99 / RW 99
$testPengumumanBatch = WhatsAppNotificationService::sendPengumuman(
    99999, 
    'Pengumuman Uji Filter', 
    'Isi Pengumuman Uji Filter', 
    'rt', 
    '99', 
    '99', 
    'Desa Sukamaju', 
    'Ketua RT Test'
);

// Tidak ada satupun dari ketiga user di atas yang boleh masuk antrean!
$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) FROM whatsapp_queue 
    WHERE recipient_phone IN ('6289611112222', '6289633334444', '000000000000')
");
$stmtCheck->execute();
$invalidQueueCount = (int)$stmtCheck->fetchColumn();

assertTest($invalidQueueCount === 0, "Nomor belum diverifikasi, opt-out, dan dummy 100% dicegah masuk antrean Fonnte");

// -----------------------------------------------------------
// TEST 5: Proteksi Notifikasi Surat & Pengaduan
// -----------------------------------------------------------
echo "\n--- TEST 5: Proteksi Notifikasi Surat & Pengaduan Mengikuti Persetujuan ---\n";

// Cek bahwa sendSuratEmailNotificationToWhatsApp menolak pengiriman ke user opt-out
$testSuratId = 99999;
$pdo->prepare("DELETE FROM surat_pengajuan WHERE id = ?")->execute([$testSuratId]);
$pdo->prepare("
    INSERT INTO surat_pengajuan (id, user_id, jenis_surat, nomor_surat, status, data_json, created_at)
    VALUES (?, ?, 'Surat Domisili', '999/SK/AI/2026', 'disetujui', '{}', NOW())
")->execute([$testSuratId, $testUserId]);

// User saat ini dalam kondisi Opt-Out ($testUserId wa_opt_in = 0)
$suratNotifRes = WhatsAppNotificationService::sendSuratEmailNotificationToWhatsApp($testSuratId, 'ai', 'optin_test_user@siwarga.test');
assertTest($suratNotifRes['success'] === false, "Notifikasi WhatsApp surat ke user opt-out DITOLAK");
assertTest($suratNotifRes['status'] === 'skipped', "Status pengiriman surat adalah 'skipped'");

// Uji Pengaduan ke user opt-out
$testPengaduanId = 99999;
$pdo->prepare("DELETE FROM pengaduan WHERE id = ?")->execute([$testPengaduanId]);
$pdo->prepare("
    INSERT INTO pengaduan (id, user_id, judul, isi, status, created_at)
    VALUES (?, ?, 'Laporan Lampu Jalan', 'Lampu mati di RT 01', 'proses', NOW())
")->execute([$testPengaduanId, $testUserId]);

$pengaduanNotifRes = WhatsAppNotificationService::sendPengaduanUpdate($testPengaduanId, 'proses', 'Sedang diperbaiki');
assertTest($pengaduanNotifRes['success'] === false, "Notifikasi WhatsApp pengaduan ke user opt-out DITOLAK");
assertTest($pengaduanNotifRes['status'] === 'skipped', "Status pengiriman pengaduan adalah 'skipped'");

// -----------------------------------------------------------
// TEST 6: Integritas Tampilan View Data Warga & Profil
// -----------------------------------------------------------
echo "\n--- TEST 6: Integritas File Tampilan & Kolom Persetujuan ---\n";

$wargaViewContent = file_get_contents(__DIR__ . '/../resources/views/warga/index.php');
assertTest(strpos($wargaViewContent, 'Persetujuan WA') !== false, "Kolom 'Persetujuan WA' terpasang di Data Warga");
assertTest(strpos($wargaViewContent, 'wa_opt_in') !== false, "Logika badge wa_opt_in terpasang di Data Warga");

$pengaturanViewContent = file_get_contents(__DIR__ . '/../resources/views/pengaturan/index.php');
assertTest(strpos($pengaturanViewContent, 'name="wa_opt_in"') !== false, "Checkbox input 'wa_opt_in' terpasang di Profil Pengaturan");
assertTest(strpos($pengaturanViewContent, 'Status: Menyetujui (Opt-In)') !== false, "Status badge 'Menyetujui (Opt-In)' terpasang di Profil");

$adminUserViewContent = file_get_contents(__DIR__ . '/../resources/views/admin/user_view.php');
assertTest(strpos($adminUserViewContent, 'Persetujuan WhatsApp') !== false, "Detail Persetujuan WhatsApp terpasang di Admin User View");

$adminUsersContent = file_get_contents(__DIR__ . '/../resources/views/admin/users.php');
assertTest(strpos($adminUsersContent, 'Opt-In WA') !== false, "Badge Opt-In WA terpasang di Manajemen User Admin");

$registerContent = file_get_contents(__DIR__ . '/../resources/views/auth/register.php');
assertTest(strpos($registerContent, 'name="wa_opt_in"') !== false, "Pilihan persetujuan WhatsApp terpasang di Form Registrasi Warga");
assertTest(strpos($registerContent, 'id="wa_opt_in" name="wa_opt_in" type="checkbox" value="1" checked') === false, "Checkbox persetujuan WA saat registrasi DEFAULT TIDAK DICENTANG");

// -----------------------------------------------------------
// TEST 7: OTP Tidak Otomatis Mengaktifkan Persetujuan
// -----------------------------------------------------------
echo "\n--- TEST 7: OTP Tidak Mengaktifkan Persetujuan Otomatis ---\n";
// Simulasikan pendaftaran user tanpa centang persetujuan WA
$unoptEmail = 'unopt_reg_user@siwarga.test';
$pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$unoptEmail]);
$sampleRegData = json_encode([
    'name' => 'Unopt Registrant',
    'email' => $unoptEmail,
    'no_hp' => '089699887766',
    'jenis_kelamin' => 'L',
    'password' => 'secret',
    'rw' => '01',
    'rt' => '01',
    'wa_opt_in' => 0 // User TIDAK mencentang
]);

$unoptOptIn = !empty(json_decode($sampleRegData, true)['wa_opt_in']) ? 1 : 0;
$unoptOptInAt = ($unoptOptIn === 1) ? date('Y-m-d H:i:s') : null;

$stmtRegInsert = $pdo->prepare("
    INSERT INTO users (name, email, no_hp, no_telp, jenis_kelamin, password, role, rw, rt, status_verifikasi, wa_opt_in, wa_opt_in_at, created_at)
    VALUES ('Unopt Registrant', ?, '089699887766', '089699887766', 'L', 'h', 'warga', '01', '01', 'pending', ?, ?, NOW())
");
$stmtRegInsert->execute([$unoptEmail, $unoptOptIn, $unoptOptInAt]);
$newUnoptId = (int)$pdo->lastInsertId();

$checkUnopt = WhatsAppConfig::getOptInDetails($newUnoptId);
assertTest($checkUnopt['opted_in'] === false, "User yang tidak mencentang saat registrasi tetap Opt-Out (wa_opt_in = 0)");
assertTest($checkUnopt['opted_at'] === null, "wa_opt_in_at bernilai null (OTP tidak otomatis mengaktifkan persetujuan)");
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$newUnoptId]);

// -----------------------------------------------------------
// TEST 8: Jeda Antrean (Queue Delay) Konfigurasi Super Admin
// -----------------------------------------------------------
echo "\n--- TEST 8: Jeda Antrean (Queue Delay) Konfigurasi Super Admin ---\n";
$cfg = WhatsAppConfig::get();
assertTest(isset($cfg['queue_delay_seconds']), "Parameter queue_delay_seconds tersedia di WhatsAppConfig");
assertTest((int)$cfg['queue_delay_seconds'] === 60, "Default jeda antrean adalah 60 detik (1 menit): " . $cfg['queue_delay_seconds'] . "s");

// Uji simpan jeda baru oleh Super Admin
WhatsAppConfig::save(['queue_delay_seconds' => 90]);
$cfgUpdated = WhatsAppConfig::get();
assertTest((int)$cfgUpdated['queue_delay_seconds'] === 90, "Super Admin dapat mengatur jeda antrean (diubah ke 90 detik)");
// Kembalikan ke 60 detik
WhatsAppConfig::save(['queue_delay_seconds' => 60]);

// Uji enqueueMessage menolak duplikat dan dummy
$testBatchUnique = "batch_uniq_" . time();
$enqOk1 = WhatsAppNotificationService::enqueueMessage($testBatchUnique, 11, '6289610008653', 'Agung', 'pengumuman', 'Msg 1');
assertTest($enqOk1 !== false, "Pesan pertama berhasil di-enqueue");
$enqOkDuplicate = WhatsAppNotificationService::enqueueMessage($testBatchUnique, 11, '6289610008653', 'Agung', 'pengumuman', 'Msg 1 duplicate');
assertTest($enqOkDuplicate === false, "Pesan duplikat nomor dalam batch yang sama OTOMATIS DITOLAK");
$pdo->prepare("DELETE FROM whatsapp_queue WHERE batch_id = ?")->execute([$testBatchUnique]);

// -----------------------------------------------------------
// CLEANUP DATA UJI
// -----------------------------------------------------------
$pdo->prepare("DELETE FROM users WHERE email IN (?, 'pending_user@siwarga.test', 'optout_user@siwarga.test', 'dummyphone_user@siwarga.test')")->execute([$testEmail]);
$pdo->prepare("DELETE FROM surat_pengajuan WHERE id = ?")->execute([$testSuratId]);
$pdo->prepare("DELETE FROM pengaduan WHERE id = ?")->execute([$testPengaduanId]);
$pdo->prepare("DELETE FROM whatsapp_queue WHERE batch_id IN (?, ?)")->execute([$testBatch, 'test_batch_filter']);

echo "\n========================================================\n";
echo "📊 HASIL PENGUJIAN: $passed LULUS, $failed GAGAL\n";
echo "========================================================\n";
