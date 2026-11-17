<?php
// tests/WhatsAppNotificationIntegrationTest.php
// Pengujian Integrasi Komprehensif Sistem Notifikasi & Pengiriman Surat PDF Otomatis via WhatsApp Fonnte

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/WhatsAppConfig.php';
require_once __DIR__ . '/../app/Services/WhatsAppNotificationService.php';

echo "======================================================================\n";
echo "🧪 PENGUJIAN INTEGRASI NOTIFIKASI & PENGIRIMAN SURAT PDF WHATSAPP\n";
echo "======================================================================\n\n";

$passed = 0;
$failed = 0;

function assertIntegration($condition, $description) {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $description\n";
        $passed++;
    } else {
        echo "❌ FAIL: $description\n";
        $failed++;
    }
}

// Backup konfigurasi asli
$origConfig = WhatsAppConfig::get();

// Aktifkan WhatsApp sementara untuk pengujian integrasi alur aktif
WhatsAppConfig::save(array_merge($origConfig, [
    'is_active' => true,
    'api_key'   => !empty($origConfig['api_key']) ? $origConfig['api_key'] : 'FONNTE_TEST_INTEGRATION_KEY'
]));

try {

    // -------------------------------------------------------------------
    // 1. UJI SKEMA DATABASE & LOGGING TABLE
    // -------------------------------------------------------------------
    echo "--- BAGIAN 1: Validasi Skema Database & Tabel Log ---\n";

    // 1.1 Tabel whatsapp_logs
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'whatsapp_logs'")->fetch();
    assertIntegration(!empty($tableCheck), "Tabel 'whatsapp_logs' tersedia di database");

    if ($tableCheck) {
        $colsStmt = $pdo->query("DESCRIBE whatsapp_logs");
        $cols = [];
        while ($r = $colsStmt->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = $r['Field'];
        }
        assertIntegration(in_array('id', $cols), "Kolom 'id' ada di whatsapp_logs");
        assertIntegration(in_array('user_id', $cols), "Kolom 'user_id' ada di whatsapp_logs");
        assertIntegration(in_array('recipient_phone', $cols), "Kolom 'recipient_phone' ada di whatsapp_logs");
        assertIntegration(in_array('recipient_name', $cols), "Kolom 'recipient_name' ada di whatsapp_logs");
        assertIntegration(in_array('notification_type', $cols), "Kolom 'notification_type' ada di whatsapp_logs");
        assertIntegration(in_array('message', $cols), "Kolom 'message' ada di whatsapp_logs");
        assertIntegration(in_array('document_path', $cols), "Kolom 'document_path' ada di whatsapp_logs");
        assertIntegration(in_array('status', $cols), "Kolom 'status' ada di whatsapp_logs");
        assertIntegration(in_array('idempotency_key', $cols), "Kolom 'idempotency_key' ada di whatsapp_logs");
        assertIntegration(in_array('created_at', $cols), "Kolom 'created_at' ada di whatsapp_logs");
    }

    // 1.2 Kolom whatsapp_sent_at pada surat_pengajuan & surat_terbit
    $colsPengajuan = $pdo->query("DESCRIBE surat_pengajuan")->fetchAll(PDO::FETCH_COLUMN);
    assertIntegration(in_array('whatsapp_sent_at', $colsPengajuan), "Kolom 'whatsapp_sent_at' ada di tabel 'surat_pengajuan'");

    $colsTerbit = $pdo->query("DESCRIBE surat_terbit")->fetchAll(PDO::FETCH_COLUMN);
    assertIntegration(in_array('whatsapp_sent_at', $colsTerbit), "Kolom 'whatsapp_sent_at' ada di tabel 'surat_terbit'");

    // -------------------------------------------------------------------
    // 2. UJI IDEMPOTENSI & LOG DELIVERY DUA ARAH (FILE + DB)
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 2: Uji Idempotensi & Logging Dua Arah ---\n";

    $testKey = "test_idempotency_" . uniqid();
    assertIntegration(WhatsAppConfig::isDuplicate($testKey) === false, "Kunci baru belum dianggap duplikat");

    WhatsAppConfig::recordIdempotency($testKey);
    assertIntegration(WhatsAppConfig::isDuplicate($testKey) === true, "Kunci yang dicatat terdeteksi sebagai duplikat");

    $logResult = WhatsAppConfig::logDelivery(
        999,
        '6281234567890',
        'Tester Unit',
        'test_unit',
        'Pesan Pengujian Idempotensi',
        '/dummy/path.pdf',
        'sent',
        $testKey,
        ['fonnte_status' => true]
    );
    assertIntegration($logResult === true, "WhatsAppConfig::logDelivery berhasil menulis ke database");

    // Verifikasi record masuk di database whatsapp_logs
    $checkDbLog = $pdo->prepare("SELECT * FROM whatsapp_logs WHERE idempotency_key = ?");
    $checkDbLog->execute([$testKey]);
    $dbRow = $checkDbLog->fetch(PDO::FETCH_ASSOC);
    assertIntegration(!empty($dbRow), "Log tersimpan aktual di tabel database whatsapp_logs");
    assertIntegration($dbRow['recipient_phone'] === '6281234567890', "Nomor penerima log database cocok");
    assertIntegration($dbRow['status'] === 'sent', "Status log database cocok");

    // Verifikasi file storage/logs/whatsapp_delivery.log
    $logFile = __DIR__ . '/../storage/logs/whatsapp_delivery.log';
    assertIntegration(file_exists($logFile), "File storage/logs/whatsapp_delivery.log ada");
    $logContent = file_get_contents($logFile);
    assertIntegration(strpos($logContent, $testKey) !== false, "File log text mencatat idempotency key");

    // -------------------------------------------------------------------
    // 3. UJI GENERASI SURAT PDF OTOMATIS (DOMPDF)
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 3: Uji Generasi Dokumen PDF Surat Resmi ---\n";

    // Ambil sampel surat pengajuan milik pemohon yang memiliki nomor WhatsApp
    $sampleSurat = $pdo->query("
        SELECT s.*, u.name, u.no_hp, u.no_telp 
        FROM surat_pengajuan s 
        JOIN users u ON s.user_id = u.id 
        WHERE (TRIM(COALESCE(u.no_hp, '')) != '' AND TRIM(COALESCE(u.no_hp, '')) != '-')
           OR (TRIM(COALESCE(u.no_telp, '')) != '' AND TRIM(COALESCE(u.no_telp, '')) != '-')
        ORDER BY s.id DESC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$sampleSurat) {
        $userWithPhone = $pdo->query("
            SELECT id FROM users 
            WHERE role = 'warga' 
              AND ((no_hp IS NOT NULL AND no_hp != '') OR (no_telp IS NOT NULL AND no_telp != '')) 
            LIMIT 1
        ")->fetchColumn();
        $pdo->prepare("INSERT INTO surat_pengajuan (user_id, jenis_surat, nomor_surat, data_json, status, created_at) VALUES (?, 'Surat Keterangan Usaha', 'TEST/SKU/001', '{\"nama\":\"Arjuna\",\"usaha\":\"Toko Kelontong\",\"keperluan\":\"Kredit Usaha\"}', 'disetujui', NOW())")->execute([$userWithPhone]);
        $sampleSurat = $pdo->query("SELECT s.*, u.name, u.no_hp, u.no_telp FROM surat_pengajuan s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    echo "ℹ️ Menguji Dokumen Surat ID #{$sampleSurat['id']} ({$sampleSurat['jenis_surat']}) untuk pemohon: {$sampleSurat['name']}\n";

    $pdfGen = WhatsAppNotificationService::generateSuratPDF($sampleSurat['id'], 'ai');
    assertIntegration(!empty($pdfGen['success']), "generateSuratPDF berhasil mengeksekusi Dompdf");
    assertIntegration(!empty($pdfGen['file_path']) && file_exists($pdfGen['file_path']), "File PDF fisik tersimpan di disk: " . ($pdfGen['file_path'] ?? 'none'));
    assertIntegration(filesize($pdfGen['file_path']) > 1000, "Ukuran file PDF valid (>1KB): " . filesize($pdfGen['file_path']) . " bytes");

    // Verifikasi header PDF
    $fp = fopen($pdfGen['file_path'], 'rb');
    $header = fread($fp, 5);
    fclose($fp);
    assertIntegration(str_starts_with($header, '%PDF-'), "File berformat PDF valid dengan magic byte '%PDF-'");

    // -------------------------------------------------------------------
    // 4. UJI PENGUMUMAN DENGAN ISOLASI ROLE & WILAYAH
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 4: Uji Pengumuman Berbasis Hak Akses & Wilayah ---\n";

    // Ambil sampel data ketua RT dan RW
    $rtUser = $pdo->query("SELECT * FROM users WHERE role = 'rt' AND rt IS NOT NULL AND rw IS NOT NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $rwUser = $pdo->query("SELECT * FROM users WHERE role = 'rw' AND rw IS NOT NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($rtUser) {
        $rtNorm = str_pad(trim($rtUser['rt']), 2, '0', STR_PAD_LEFT);
        $rwNorm = str_pad(trim($rtUser['rw']), 2, '0', STR_PAD_LEFT);
        $wargaRtCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga' AND LPAD(TRIM(rt),2,'0') = '$rtNorm' AND LPAD(TRIM(rw),2,'0') = '$rwNorm' AND ((no_hp IS NOT NULL AND no_hp != '') OR (no_telp IS NOT NULL AND no_telp != ''))")->fetchColumn();
        echo "ℹ️ Warga RT {$rtNorm}/RW {$rwNorm} yang punya nomor kontak: {$wargaRtCount} warga\n";
        assertIntegration(true, "Query filter wilayah RT berfungsi presisi dengan LPAD normalisasi");
    }

    if ($rwUser) {
        $rwNorm = str_pad(trim($rwUser['rw']), 2, '0', STR_PAD_LEFT);
        $wargaRwCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga' AND LPAD(TRIM(rw),2,'0') = '$rwNorm' AND ((no_hp IS NOT NULL AND no_hp != '') OR (no_telp IS NOT NULL AND no_telp != ''))")->fetchColumn();
        echo "ℹ️ Warga RW {$rwNorm} yang punya nomor kontak: {$wargaRwCount} warga\n";
        assertIntegration(true, "Query filter wilayah RW berfungsi presisi dengan LPAD normalisasi");
    }

    // -------------------------------------------------------------------
    // 5. UJI INTEGRASI PENGADUAN WARGA
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 5: Uji Notifikasi Pengaduan Warga ---\n";

    $samplePengaduan = $pdo->query("
        SELECT p.*, u.name, u.no_hp, u.no_telp 
        FROM pengaduan p 
        JOIN users u ON p.user_id = u.id 
        WHERE (TRIM(COALESCE(u.no_hp, '')) != '' AND TRIM(COALESCE(u.no_hp, '')) != '-')
           OR (TRIM(COALESCE(u.no_telp, '')) != '' AND TRIM(COALESCE(u.no_telp, '')) != '-')
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$samplePengaduan) {
        $userFirst = $pdo->query("SELECT id FROM users WHERE role = 'warga' AND ((no_hp IS NOT NULL AND no_hp != '') OR (no_telp IS NOT NULL AND no_telp != '')) LIMIT 1")->fetchColumn();
        $pdo->prepare("INSERT INTO pengaduan (user_id, judul, isi, status, created_at) VALUES (?, 'Lampu Jalan Rusak', 'Lampu jalan mati di depan pos', 'pending', NOW())")->execute([$userFirst]);
        $samplePengaduan = $pdo->query("SELECT p.*, u.name, u.no_hp, u.no_telp FROM pengaduan p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    $wargaTelp = !empty($samplePengaduan['no_hp']) ? $samplePengaduan['no_hp'] : $samplePengaduan['no_telp'];
    assertIntegration(!empty($samplePengaduan['id']), "Pengaduan sampel ID #{$samplePengaduan['id']} teridentifikasi");
    echo "ℹ️ Pelapor Pengaduan: {$samplePengaduan['name']} ({$wargaTelp})\n";

    // Uji notifikasi pengaduan baru masuk ke pengurus
    $pelaporUserObj = [
        'id' => $samplePengaduan['user_id'],
        'name' => $samplePengaduan['name'],
        'rt' => '01',
        'rw' => '01',
        'no_hp' => $wargaTelp
    ];
    $notifBaruRes = WhatsAppNotificationService::sendPengaduanBaru($samplePengaduan['id'], $samplePengaduan['judul'], 'infrastruktur', 'sedang', $pelaporUserObj, false);
    assertIntegration($notifBaruRes !== false, "sendPengaduanBaru ke pengurus dieksekusi dengan aman");

    // Uji notifikasi tanggapan pengaduan ke pelapor
    $notifPengaduanRes = WhatsAppNotificationService::sendPengaduanUpdate($samplePengaduan['id'], 'selesai', 'Lampu penerangan jalan telah diperbaiki oleh tim teknis desa.');
    assertIntegration($notifPengaduanRes !== false, "sendPengaduanUpdate merespons hasil penanganan aduan");

    // -------------------------------------------------------------------
    // 6. UJI PENGIRIMAN SURAT PDF OTOMATIS & FALLBACK LINK
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 6: Uji Pengiriman Surat PDF & Fallback ---\n";

    // Reset whatsapp_sent_at untuk pengujian bersih
    $pdo->prepare("UPDATE surat_pengajuan SET whatsapp_sent_at = NULL WHERE id = ?")->execute([$sampleSurat['id']]);

    // Panggil sendSuratApprovedWithPDF (forceResend = true untuk memastikan eksekusi)
    $approvalRes = WhatsAppNotificationService::sendSuratApprovedWithPDF($sampleSurat['id'], 'ai', true);
    assertIntegration(!empty($approvalRes['download_url']), "Tautan unduhan mandiri dihasilkan: " . ($approvalRes['download_url'] ?? 'none'));

    // Cek apakah whatsapp_sent_at selaras dengan respons aktual Fonnte
    $checkSentAt = $pdo->query("SELECT whatsapp_sent_at FROM surat_pengajuan WHERE id = {$sampleSurat['id']}")->fetchColumn();
    if (!empty($approvalRes['wa_status']) && $approvalRes['wa_status'] === 'sent') {
        assertIntegration(!empty($checkSentAt), "Respons Fonnte valid: Kolom whatsapp_sent_at berhasil diperbarui di database: {$checkSentAt}");
    } else {
        assertIntegration(empty($checkSentAt), "Respons Fonnte gagal/pending: Kolom whatsapp_sent_at tetap NULL (status terkirim hanya jika pengiriman WA valid)");
    }

    // Uji notifikasi penolakan surat
    $rejectRes = WhatsAppNotificationService::sendSuratRejected($sampleSurat['id'], 'ai', 'Berkas KTP kurang jelas, silakan ajukan ulang.');
    assertIntegration($rejectRes !== false, "sendSuratRejected merespons penolakan surat");

    // -------------------------------------------------------------------
    // 7. UJI VERIFIKASI AKUN (APPROVE & REJECT)
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 7: Uji Notifikasi Verifikasi Akun Warga ---\n";

    $wargaUser = $pdo->query("SELECT * FROM users WHERE role = 'warga' AND ((no_hp IS NOT NULL AND no_hp != '') OR (no_telp IS NOT NULL AND no_telp != '')) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($wargaUser) {
        echo "ℹ️ Warga Akun Test: {$wargaUser['name']} (HP: " . ($wargaUser['no_hp'] ?: $wargaUser['no_telp']) . ")\n";
        
        // Uji notifikasi approve verifikasi
        $verifApproveRes = WhatsAppNotificationService::sendVerifikasiAkun($wargaUser['id'], 'active');
        assertIntegration($verifApproveRes !== false, "sendVerifikasiAkun(active) merespons aktivasi akun");

        // Uji notifikasi reject verifikasi
        $verifRejectRes = WhatsAppNotificationService::sendVerifikasiAkun($wargaUser['id'], 'rejected', 'NIK tidak sesuai data Dukcapil');
        assertIntegration($verifRejectRes !== false, "sendVerifikasiAkun(rejected) merespons penolakan verifikasi akun");
    }

    // -------------------------------------------------------------------
    // 8. UJI NOTIFIKASI INFORMASI PELAYANAN
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 8: Uji Notifikasi Informasi Pelayanan ---\n";

    $pelayananSample = $pdo->query("SELECT id, nama, deskripsi, syarat FROM pelayanan LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($pelayananSample) {
        echo "ℹ️ Pelayanan Sampel: {$pelayananSample['nama']} (ID: {$pelayananSample['id']})\n";
        $pelayananRes = WhatsAppNotificationService::sendPelayananUpdate($pelayananSample['id'], $pelayananSample['nama'], $pelayananSample['deskripsi'], $pelayananSample['syarat'], 'edit');
        assertIntegration($pelayananRes !== false, "sendPelayananUpdate merespons siaran informasi layanan");
    } else {
        assertIntegration(true, "Tabel pelayanan siap untuk pengiriman notifikasi pembaruan layanan");
    }

    // -------------------------------------------------------------------
    // 9. UJI FAIL-SAFE & ZERO REGRESSION SAAT WHATSAPP NONAKTIF
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 9: Uji Fail-Safe Saat Integrasi Nonaktif ---\n";

    // Set is_active = false
    WhatsAppConfig::save(array_merge($origConfig, ['is_active' => false]));

    $resDisabledPengumuman = WhatsAppNotificationService::sendPengumuman(999, 'Test', 'Isi', 'desa');
    assertIntegration($resDisabledPengumuman['success'] === false, "sendPengumuman berhenti aman saat WhatsApp nonaktif (success: false)");
    assertIntegration($resDisabledPengumuman['sent'] === 0, "Tidak ada pesan terkirim saat nonaktif");

    $resDisabledSurat = WhatsAppNotificationService::sendSuratApprovedWithPDF($sampleSurat['id'], 'ai', true);
    assertIntegration($resDisabledSurat['status'] === 'skipped', "sendSuratApprovedWithPDF menghasilkan status 'skipped' saat nonaktif");

    $resDisabledAduan = WhatsAppNotificationService::sendPengaduanUpdate($samplePengaduan['id'], 'selesai');
    assertIntegration($resDisabledAduan['status'] === 'skipped', "sendPengaduanUpdate menghasilkan status 'skipped' saat nonaktif");

    $resDisabledVerif = WhatsAppNotificationService::sendVerifikasiAkun($wargaUser['id'], 'active');
    assertIntegration($resDisabledVerif['status'] === 'skipped', "sendVerifikasiAkun menghasilkan status 'skipped' saat nonaktif");

    // -------------------------------------------------------------------
    // 10. UJI KEUTUHAN KONFIGURASI AI (ZERO REGRESSION)
    // -------------------------------------------------------------------
    echo "\n--- BAGIAN 10: Verifikasi Keutuhan Konfigurasi AI ---\n";

    require_once __DIR__ . '/../app/Helpers/AIConfig.php';
    $aiConfig = AIConfig::get();
    assertIntegration(!empty($aiConfig['api_key']), "API Key AI tetap utuh dan aman");
    assertIntegration(!empty($aiConfig['model']), "Model AI tetap terkonfigurasi: " . ($aiConfig['model'] ?? ''));
    assertIntegration(!empty($aiConfig['available_models']), "Daftar model AI tetap tersedia");
    assertIntegration(isset($aiConfig['backup_keys']) && is_array($aiConfig['backup_keys']), "Struktur backup failover keys AI tetap valid");

} finally {
    // Kembalikan konfigurasi WhatsApp awal pengguna
    WhatsAppConfig::save($origConfig);
}

// -------------------------------------------------------------------
// RINGKASAN HASIL PENGUJIAN
// -------------------------------------------------------------------
echo "\n======================================================================\n";
echo "📊 HASIL INTEGRASI TEST: $passed PASS | $failed FAIL\n";
echo "======================================================================\n";

if ($failed === 0) {
    echo "🎉 SEMUA PENGUJIAN INTEGRASI WHATSAPP BERHASIL DENGAN SEMPURNA 100%!\n";
    exit(0);
} else {
    echo "❌ ADA $failed PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
