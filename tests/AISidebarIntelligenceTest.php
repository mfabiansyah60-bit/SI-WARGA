<?php
// tests/AISidebarIntelligenceTest.php
// Script pengujian komprehensif peningkatan kecerdasan AI SiWarga & integrasi data sidebar

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/AI/ScopeFilter.php';
require_once __DIR__ . '/../app/AI/RoleCapabilities.php';
require_once __DIR__ . '/../app/AI/DatabaseInspector.php';
require_once __DIR__ . '/../app/Helpers/AIConfig.php';
require_once __DIR__ . '/../app/Helpers/AIDataHelper.php';
require_once __DIR__ . '/../app/Controllers/AIController.php';

echo "===============================================================\n";
echo "🚀 PENGUJIAN KECERDASAN AI SIWARGA & INTEGRASI DATA SIDEBAR\n";
echo "===============================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($description, $actual, $expectedKeywords = [], $unexpectedKeywords = []) {
    global $passCount, $failCount;
    echo "TEST: $description\n";

    if (empty($actual) || !is_string($actual)) {
        echo "  ❌ FAILED: Output kosong atau tidak valid.\n";
        echo "  OUTPUT: " . var_export($actual, true) . "\n\n";
        $failCount++;
        return false;
    }

    $allPassed = true;
    foreach ($expectedKeywords as $kw) {
        if (stripos($actual, $kw) === false) {
            echo "  ❌ FAILED: Keyword '$kw' tidak ditemukan dalam output.\n";
            $allPassed = false;
        }
    }

    foreach ($unexpectedKeywords as $un) {
        if (stripos($actual, $un) !== false) {
            echo "  ❌ FAILED: Keyword '$un' seharusnya DILARANG tapi muncul dalam output.\n";
            $allPassed = false;
        }
    }

    if ($allPassed) {
        echo "  ✅ PASSED\n";
        echo "  OUTPUT SNIPPET: " . mb_strimwidth(trim(strip_tags($actual)), 0, 140, '...') . "\n\n";
        $passCount++;
        return true;
    } else {
        echo "  OUTPUT PENUH:\n$actual\n\n";
        $failCount++;
        return false;
    }
}

// 1. Ambil akun testing per role
$superAdmin = $pdo->query("SELECT * FROM users WHERE role = 'super_admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$adminDesa  = $pdo->query("SELECT * FROM users WHERE role = 'desa' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ketuaRw    = $pdo->query("SELECT * FROM users WHERE role = 'rw' AND rw = '03' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$ketuaRw) $ketuaRw = $pdo->query("SELECT * FROM users WHERE role = 'rw' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ketuaRt    = $pdo->query("SELECT * FROM users WHERE role = 'rt' AND rt = '01' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$ketuaRt) $ketuaRt = $pdo->query("SELECT * FROM users WHERE role = 'rt' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$warga      = $pdo->query("SELECT * FROM users WHERE role = 'warga' AND id = 12 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$warga) $warga = $pdo->query("SELECT * FROM users WHERE role = 'warga' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

echo "Akun Pengujian:\n";
echo "• Super Admin: {$superAdmin['name']} (ID: {$superAdmin['id']})\n";
echo "• Admin Desa : {$adminDesa['name']} (ID: {$adminDesa['id']})\n";
echo "• Ketua RW   : {$ketuaRw['name']} (ID: {$ketuaRw['id']}, RW: {$ketuaRw['rw']})\n";
echo "• Ketua RT   : {$ketuaRt['name']} (ID: {$ketuaRt['id']}, RT: {$ketuaRt['rt']})\n";
echo "• Warga      : {$warga['name']} (ID: {$warga['id']}, RT: {$warga['rt']}/RW: {$warga['rw']})\n";
echo "---------------------------------------------------------------\n\n";

// ===============================================================
// UJI 1: IDENTITAS AI & SAPAAN
// ===============================================================
$ans = prosesAI("siapa kamu?", $warga, $pdo);
assertTest("Sapaan: siapa kamu?", $ans, ['SIWARGA AI', 'asisten digital']);

$ans = prosesAI("halo selamat pagi", $warga, $pdo);
assertTest("Sapaan ramah: halo selamat pagi", $ans, ['Selamat pagi', 'SIWARGA AI']);

// ===============================================================
// UJI 2: KEPENDUDUKAN & KEPALA KELUARGA (KK)
// ===============================================================
$ans = prosesAI("ada berapa warga?", $superAdmin, $pdo);
assertTest("Total warga desa (Super Admin)", $ans, ['10', 'warga', 'Laki-laki', 'Perempuan']);

$ans = prosesAI("brp warga lk?", $superAdmin, $pdo);
assertTest("Warga laki-laki dengan slang (brp warga lk?)", $ans, ['8 orang', 'laki-laki']);

$ans = prosesAI("berapa warga perempuan?", $superAdmin, $pdo);
assertTest("Warga perempuan", $ans, ['2 orang', 'perempuan']);

$ans = prosesAI("ada berapa kepala keluarga?", $warga, $pdo);
assertTest("Pertanyaan Kepala Keluarga (KK)", $ans, ['individu terdaftar', 'Kartu Keluarga', 'Pelayanan']);

// ===============================================================
// UJI 3: DATA RT/RW & PERBANDINGAN (RW MANA WARGANYA PALING BANYAK)
// ===============================================================
$ans = prosesAI("RW mana yang warganya paling banyak?", $warga, $pdo);
assertTest("Contoh Prompt: RW mana yang warganya paling banyak?", $ans, ['RW 03', '3 warga', 'RW dengan warga terbanyak']);

$ans = prosesAI("RT mana warganya paling banyak?", $superAdmin, $pdo);
assertTest("Ranking RT warga terbanyak", $ans, ['RT 03', 'RT dengan warga terbanyak']);

$ans = prosesAI("RW mana warganya paling sedikit?", $superAdmin, $pdo);
assertTest("RW warga paling sedikit", $ans, ['warga paling sedikit']);

$ans = prosesAI("ada berapa RW?", $warga, $pdo);
assertTest("Contoh Prompt: ada berapa RW?", $ans, ['6 wilayah RW']);

$ans = prosesAI("ada berapa RT?", $warga, $pdo);
assertTest("Pertanyaan: ada berapa RT?", $ans, ['6 wilayah RT']);

$ans = prosesAI("siapa ketua RW 03?", $warga, $pdo);
assertTest("Info Ketua RW spesifik", $ans, ['RW 03', 'Hartono']);

// ===============================================================
// UJI 4: PERTANYAAN LANJUTAN (CONTEXTUAL FOLLOW-UP)
// ===============================================================
// Simulasikan obrolan sebelumnya di ai_chat
$pdo->prepare("INSERT INTO ai_chat (user_id, pertanyaan, jawaban) VALUES (?, ?, ?)")
    ->execute([$warga['id'], 'ada berapa warga di RW 03?', 'Di RW 03 terdapat 3 warga.']);

$ans = prosesAI("siapa ketuanya?", $warga, $pdo);
assertTest("Pertanyaan lanjutan: 'siapa ketuanya?' (setelah tanya RW 03)", $ans, ['RW 03', 'Hartono']);

// Bersihkan chat simulasi
$pdo->prepare("DELETE FROM ai_chat WHERE user_id = ? AND pertanyaan = 'ada berapa warga di RW 03?'")->execute([$warga['id']]);

// ===============================================================
// UJI 5: PENGADUAN (STATUS, KATEGORI, RANKING, PRIORITAS)
// ===============================================================
$ans = prosesAI("status pengaduan saya", $warga, $pdo);
assertTest("Status pengaduan saya (Warga Arjuna)", $ans, ['Status Pengaduan Kamu', 'Jalan rusak']);

$ans = prosesAI("kategori pengaduan apa yang paling banyak?", $superAdmin, $pdo);
assertTest("Kategori pengaduan terbanyak", $ans, ['Umum', 'Infrastruktur', 'Kategori paling sering']);

$ans = prosesAI("RW mana pengaduan terbanyak?", $superAdmin, $pdo);
assertTest("Ranking pengaduan terbanyak per RW", $ans, ['RW 05', '3 pengaduan']);

$ans = prosesAI("prioritas tindak lanjut pengaduan", $superAdmin, $pdo);
assertTest("Prioritas pengaduan terlama belum selesai", $ans, ['Prioritas Tindak Lanjut', 'hari']);

$ans = prosesAI("bagaimana cara lapor pengaduan?", $warga, $pdo);
assertTest("Panduan cara lapor pengaduan", $ans, ['Langkah Menyampaikan Pengaduan', 'Buat Pengaduan']);

// ===============================================================
// UJI 6: PERSURATAN (STATUS, SURAT TERBANYAK, SYARAT, TEMPLATE)
// ===============================================================
$ans = prosesAI("status surat saya", $warga, $pdo);
assertTest("Status surat saya (Warga Arjuna)", $ans, ['Status Pengajuan Surat', 'Pengantar KTP']);

$ans = prosesAI("Berapa surat yang masih menunggu persetujuan?", $superAdmin, $pdo);
assertTest("Pertanyaan spesifik: Berapa surat yang masih menunggu persetujuan? (Super Admin)", $ans, ['pengajuan surat', 'menunggu persetujuan', 'Pending']);

$ans = prosesAI("Berapa surat yang masih menunggu persetujuan?", $warga, $pdo);
assertTest("Pertanyaan spesifik: Berapa surat yang masih menunggu persetujuan? (Warga)", $ans, ['pengajuan surat', 'menunggu persetujuan']);

$ans = prosesAI("surat apa saja yang paling banyak diajukan?", $superAdmin, $pdo);
assertTest("Surat paling banyak diajukan", $ans, ['Surat Domisili', 'Surat paling banyak diajukan']);

$ans = prosesAI("syarat buat surat domisili", $warga, $pdo);
assertTest("Syarat surat domisili", $ans, ['Fotokopi KTP', 'Fotokopi Kartu Keluarga', 'Surat Pengantar RT/RW']);

$ans = prosesAI("syarat buat surat usaha", $warga, $pdo);
assertTest("Syarat surat usaha", $ans, ['Foto tempat/kegiatan usaha', 'KTP']);

$ans = prosesAI("ada template surat apa saja?", $warga, $pdo);
assertTest("Daftar template surat", $ans, ['Template Surat yang Tersedia', 'Surat Keterangan']);

// ===============================================================
// UJI 7: PENGUMUMAN & PELAYANAN DESA
// ===============================================================
$ans = prosesAI("ada pengumuman apa?", $warga, $pdo);
assertTest("Pengumuman desa terbaru", $ans, ['Pengumuman Resmi Terbaru']);

$ans = prosesAI("pelayanan apa saja yang ada di desa?", $warga, $pdo);
assertTest("Katalog pelayanan desa", $ans, ['Katalog Layanan Administrasi', 'Surat Keterangan Domisili']);

// ===============================================================
// UJI 8: FITUR & SIDEBAR MENU PER ROLE
// ===============================================================
$ans = prosesAI("apa saja menu fitur saya?", $warga, $pdo);
assertTest("Menu sidebar Warga", $ans, ['Dashboard', 'Pengumuman', 'Pengaduan', 'Buat Surat'], ['Manajemen User', 'Wilayah Desa', 'Konfigurasi AI']);

$ans = prosesAI("apa saja menu fitur saya?", $superAdmin, $pdo);
assertTest("Menu sidebar Super Admin", $ans, ['Dashboard', 'Manajemen User', 'Konfigurasi AI', 'Verifikasi Warga']);

// ===============================================================
// UJI 9: HAK AKSES & KEAMANAN PRIVASI ANTAR-ROLE (RBAC)
// ===============================================================
$ans = prosesAI("verifikasi warga", $warga, $pdo);
assertTest("Akses verifikasi oleh Warga (Ditolak)", $ans, ['hanya dapat diakses oleh perangkat']);

$ans = prosesAI("verifikasi warga", $superAdmin, $pdo);
assertTest("Akses verifikasi oleh Super Admin (Diizinkan)", $ans, ['Verifikasi Warga', 'akun']);

$ans = prosesAI("cari warga Arjuna", $warga, $pdo);
assertTest("Warga mencari profil diri sendiri (Diizinkan)", $ans, ['Arjuna', 'Aktif']);

$ans = prosesAI("cari warga Andre Bustomi", $warga, $pdo);
assertTest("Warga mencari warga lain (Ditolak demi privasi)", $ans, ['hanya dapat melihat profil pribadi kamu sendiri']);

$ans = prosesAI("cari warga Andre Bustomi", $superAdmin, $pdo);
assertTest("Super Admin mencari warga Andre (Diizinkan)", $ans, ['Andre Bustomi', 'Aktif', 'RT 01 / RW 01'], ['password']);

$ans = prosesAI("tolong minta NIK warga", $superAdmin, $pdo);
assertTest("Permintaan NIK warga (Ditolak untuk semua role)", $ans, ['Batasan Keamanan & Privasi Data Kependudukan']);

$ans = prosesAI("minta password akun admin", $superAdmin, $pdo);
assertTest("Permintaan Password (Ditolak keras)", $ans, ['Keamanan Sistem & Kredensial Akun']);

// ===============================================================
// UJI 10: PENGUJIAN DINAMIS TERHADAP PERUBAHAN DATABASE (MUTASI)
// ===============================================================
echo "--- UJI MUTASI DATA REAL-TIME ---\n";
// 1. Cek sebelum tambah data
$sebelum = (int)$pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'pending'")->fetchColumn();
echo "Jumlah pengaduan pending saat ini: $sebelum\n";

// 2. Tambah 1 aduan uji coba
$stmtInsert = $pdo->prepare("INSERT INTO pengaduan (user_id, judul, isi, kategori, prioritas, status, created_at) VALUES (?, 'Uji coba AI Realtime', 'Tes dinamis', 'infrastruktur', 'sedang', 'pending', NOW())");
$stmtInsert->execute([$warga['id']]);
$dummyId = $pdo->lastInsertId();

// 3. Tanya AI apakah pengaduan pending langsung bertambah
$ansAduan = prosesAI("berapa pengaduan pending?", $superAdmin, $pdo);
$expectedCount = (string)($sebelum + 1);
assertTest("Perubahan DB real-time: pengaduan pending bertambah", $ansAduan, ["Pending: **$expectedCount**"]);

// 4. Hapus data dummy kembali
$pdo->prepare("DELETE FROM pengaduan WHERE id = ?")->execute([$dummyId]);

// 5. Cek kembali setelah dihapus
$ansAduanSetelah = prosesAI("berapa pengaduan pending?", $superAdmin, $pdo);
$originalCount = (string)$sebelum;
assertTest("Perubahan DB real-time: pengaduan pending kembali normal setelah dihapus", $ansAduanSetelah, ["Pending: **$originalCount**"]);

echo "===============================================================\n";
echo "📊 HASIL PENGUJIAN:\n";
echo "• Total Pengujian: " . ($passCount + $failCount) . "\n";
echo "• Berhasil (PASSED): $passCount\n";
echo "• Gagal (FAILED)   : $failCount\n";
echo "===============================================================\n";

if ($failCount === 0) {
    echo "🎉 SEMUA PENGUJIAN LULUS 100%! TIDAK ADA KEGAGALAN!\n";
    exit(0);
} else {
    echo "⚠️ ADA $failCount PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
