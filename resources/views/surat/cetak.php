<?php
// resources/views/surat/cetak.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: /login'); exit; }

require_once __DIR__ . '/../../../config/database.php';

$user = $_SESSION['user'];

$jenis = $template_data['nama'] ?? 'Surat Resmi';
$nomor = $template_data['nomor_surat'] ?? '-';
$isi_surat = $template_data['isi_final'] ?? '';
$id_surat_terbit = $template_data['id_surat_terbit'] ?? 0;
$status_surat = $template_data['status'] ?? 'pending';
$user_id_target = $template_data['user_id_pemohon'] ?? $user['id']; 
$email_sent_at = $template_data['email_sent_at'] ?? null;
$whatsapp_sent_at = $template_data['whatsapp_sent_at'] ?? null; 

$email_warga = '';
$no_hp_warga = '';
$punya_email_asli = false;
$punya_wa = false;

$stmtUser = $pdo->prepare("SELECT name, email, no_hp, no_telp FROM users WHERE id = ?");
$stmtUser->execute([$user_id_target]); 
$targetUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if ($targetUser) {
    $email_warga = trim($targetUser['email'] ?? '');
    $no_hp_warga = trim($targetUser['no_hp'] ?? ($targetUser['no_telp'] ?? ''));

    if (!empty($email_warga) && filter_var($email_warga, FILTER_VALIDATE_EMAIL)) {
        if (!str_contains($email_warga, '@siwarga.local')) {
            $punya_email_asli = true;
        }
    }
    if (!empty($no_hp_warga)) {
        $punya_wa = true;
    }
}

$namaPemohon = !empty($targetUser['name']) ? $targetUser['name'] : ($user['name'] ?? 'Warga');
$namaFileSafe = preg_replace('/[^A-Za-z0-9]+/', '_', $jenis . '_' . $namaPemohon);
$fileTitle = 'Surat ' . $jenis . ' - ' . $namaPemohon;

$bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$ts = time(); 
$tanggal_indo = date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);

$logoPng = dirname(__DIR__, 3) . '/public/images/logo_desa.png';
$logoJpg = dirname(__DIR__, 3) . '/public/images/logo_desa.jpg';
$logoSrc = '';
if (file_exists($logoPng)) {
    $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPng));
} elseif (file_exists($logoJpg)) {
    $logoSrc = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoJpg));
} else {
    $logoSrc = '/public/images/logo_desa.png';
}

// Ekstrak & Normalisasi Data Pemohon
$replacements = $template_data['replacements'] ?? [];
if (empty($replacements) && !empty($template_data['data_isi'])) {
    $replacements = json_decode($template_data['data_isi'], true) ?: [];
}

$p_nama = $replacements['{nama}'] ?? ($targetUser['name'] ?? ($user['name'] ?? ''));
$p_nik = $replacements['{nik}'] ?? '-';
$p_ttl = $replacements['{ttl}'] ?? '-';
$p_jk = $replacements['{jk}'] ?? 'Laki-laki';
$p_agama = $replacements['{agama}'] ?? 'Islam';
$p_warga_negara = $replacements['{kewarganegaraan}'] ?? ($replacements['{warga_negara}'] ?? 'WNI');
$p_pekerjaan = $replacements['{pekerjaan}'] ?? '-';
$p_alamat = $replacements['{alamat}'] ?? '';
$p_rt = $replacements['{rt}'] ?? ($targetUser['rt'] ?? ($user['rt'] ?? ''));
$p_rw = $replacements['{rw}'] ?? ($targetUser['rw'] ?? ($user['rw'] ?? ''));
$p_keperluan = $replacements['{keperluan}'] ?? '';
$p_nama_usaha = $replacements['{nama_usaha}'] ?? '';
$p_jenis_usaha = $replacements['{jenis_usaha}'] ?? '';
$p_alamat_usaha = $replacements['{alamat_usaha}'] ?? '';

$alamat_lengkap = trim($p_alamat);
if (!empty($p_rt) || !empty($p_rw)) {
    $rtPad = str_pad(trim((string)$p_rt), 2, '0', STR_PAD_LEFT);
    $rwPad = str_pad(trim((string)$p_rw), 2, '0', STR_PAD_LEFT);
    if (!str_contains($alamat_lengkap, 'RT')) {
        $alamat_lengkap .= ($alamat_lengkap ? ', ' : '') . "RT $rtPad / RW $rwPad";
    }
}
if (!str_contains(strtolower($alamat_lengkap), 'sukamaju')) {
    $alamat_lengkap .= ($alamat_lengkap ? ', ' : '') . "Desa Sukamaju, Kec. Sukamaju, Kab. Cirebon";
}

$jenisRaw = $jenis ?? 'Surat Resmi';
$jenisLower = strtolower($jenisRaw);
$kategori = 'umum';
$judul_resmi = strtoupper($jenisRaw);

if (str_contains($jenisLower, 'usaha') || str_contains($jenisLower, 'sku')) {
    $kategori = 'usaha';
    $judul_resmi = 'SURAT KETERANGAN USAHA';
} elseif (str_contains($jenisLower, 'tidak mampu') || str_contains($jenisLower, 'sktm')) {
    $kategori = 'sktm';
    $judul_resmi = 'SURAT KETERANGAN TIDAK MAMPU (SKTM)';
} elseif (str_contains($jenisLower, 'domisili') || str_contains($jenisLower, 'tempat tinggal')) {
    $kategori = 'domisili';
    $judul_resmi = 'SURAT KETERANGAN DOMISILI';
} elseif (str_contains($jenisLower, 'ktp')) {
    $kategori = 'ktp';
    $judul_resmi = 'SURAT PENGANTAR KTP';
} elseif (str_contains($jenisLower, 'skck')) {
    $kategori = 'skck';
    $judul_resmi = 'SURAT PENGANTAR SKCK';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($fileTitle) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; background: #e5e7eb; padding: 20px; font-size: 12pt; line-height: 1.5; }
        .toolbar-wrap { max-width: 21cm; margin: 0 auto 20px; font-family: system-ui, sans-serif; }
        .toolbar { display: flex; gap: 10px; justify-content: space-between; align-items: center; flex-wrap: wrap; background: white; padding: 14px 18px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .toolbar-left { display: flex; gap: 8px; align-items: center; }
        .toolbar-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .toolbar-title { font-size: 13px; font-weight: 600; color: #1e293b; }
        .toolbar-title small { display: block; font-weight: 400; color: #64748b; font-size: 11px; margin-top: 2px; }
        .btn { padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; font-family: inherit; }
        .btn-primary { background: #2563eb; color: white; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3); }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-kirim { background: #16a34a; color: white; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3); }
        .btn-kirim:hover { background: #15803d; }
        .btn-success { background: #10b981; color: white; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3); }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .badge-pending { background: #fef3c7; color: #d97706; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: bold; }
        .hint { text-align: center; font-size: 12px; color: #64748b; margin-top: 10px; font-family: system-ui, sans-serif; }
        .hint strong { color: #2563eb; }
        
        .surat { max-width: 21cm; margin: 0 auto; background: white; padding: 2cm 2.5cm; box-shadow: 0 4px 20px rgba(0,0,0,0.1); min-height: 29.7cm; }
        .kop { display: flex; align-items: center; gap: 20px; padding-bottom: 10px; border-bottom: 3px solid #000; margin-bottom: 5px; }
        .kop .logo { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 48px; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text h1 { font-size: 16pt; font-weight: 700; letter-spacing: 1px; }
        .kop-text h2 { font-size: 14pt; font-weight: 700; }
        .kop-text p { font-size: 10pt; margin-top: 2px; }
        .kop-line-2 { border-bottom: 1px solid #000; margin-bottom: 30px; }
        
        .judul { text-align: center; margin: 30px 0 8px; }
        .judul h3 { font-size: 14pt; font-weight: 700; text-decoration: underline; letter-spacing: 1px; }
        .judul p { font-size: 11pt; margin-top: 3px; }
        .isi { margin-top: 25px; text-align: justify; font-size: 12pt; line-height: 1.6; }
        
        .ttd { margin-top: 50px; display: flex; justify-content: flex-end; }
        .ttd-box { text-align: center; width: 250px; }
        .ttd-box .nama { margin-top: 70px; font-weight: 700; text-decoration: underline; }
        
        /* ===== CUSTOM MODAL CSS ===== */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; z-index: 9999;
            opacity: 0; pointer-events: none; transition: opacity 0.3s ease; font-family: system-ui, sans-serif;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-box {
            background: white; border-radius: 16px; padding: 24px; max-width: 420px; width: 90%;
            transform: scale(0.95); transition: transform 0.3s ease; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .modal-overlay.active .modal-box { transform: scale(1); }
        .modal-icon {
            width: 48px; height: 48px; border-radius: 50%; background: #eff6ff; color: #2563eb;
            display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px;
        }
        .modal-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .modal-text { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.5; }
        .modal-text strong { color: #0f172a; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .modal-btn {
            padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: all .2s;
        }
        .modal-btn-cancel { background: #f1f5f9; color: #475569; }
        .modal-btn-cancel:hover { background: #e2e8f0; }
        .modal-btn-confirm { background: #2563eb; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .modal-btn-confirm:hover { background: #1d4ed8; transform: translateY(-1px); }

        @media print { 
            body { background: white; padding: 0; } 
            .toolbar-wrap, .hint, .modal-overlay { display: none !important; } 
            .surat { box-shadow: none; margin: 0; padding: 1.5cm 2cm; } 
            @page { size: A4; margin: 0; } 
        }
    </style>
</head>
<body>

<!-- CUSTOM MODAL KIRIM SURAT (GMAIL + NOTIFIKASI WHATSAPP) -->
<div class="modal-overlay" id="kirimSuratModal">
    <div class="modal-box">
        <div class="modal-icon" style="background:#ecfdf5;color:#16a34a;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </div>
        <h3 class="modal-title" id="kirimSuratModalTitle">Kirim Surat Resmi</h3>
        <div class="modal-text" id="kirimSuratModalBody">
            <p style="margin-bottom:12px;">Sistem akan memproses pengiriman surat resmi dengan alur terpadu:</p>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:12px; font-size:13px; text-align:left;">
                <div style="margin-bottom:6px;"><strong>1. Gmail:</strong> Berkas PDF fisik surat akan dikirim ke <span id="modalKirimEmail" style="color:#2563eb;font-weight:600;"></span></div>
                <div><strong>2. WhatsApp:</strong> Notifikasi konfirmasi pengiriman akan dikirim via Fonnte ke <span id="modalKirimWa" style="color:#16a34a;font-weight:600;"></span> (setelah email berhasil terkirim)</div>
            </div>
            <p style="font-size:12px;color:#64748b;"><em>Catatan: Jika pengiriman email gagal, notifikasi WhatsApp otomatis tidak akan dikirimkan.</em></p>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="closeKirimSuratModal()">Batal</button>
            <button class="modal-btn modal-btn-confirm" id="btnConfirmKirimSurat" style="background:#16a34a;box-shadow:0 4px 12px rgba(22,163,74,0.3);">Ya, Kirim Sekarang!</button>
        </div>
    </div>
</div>

<div class="toolbar-wrap">
    <div class="toolbar">
        <div class="toolbar-left">
            <a href="/surat" class="btn btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Kembali
            </a>
            <div class="toolbar-title">
                <?= htmlspecialchars($jenis) ?>
                <small>Nomor: <?= htmlspecialchars($nomor) ?></small>
            </div>
        </div>
        
        <div class="toolbar-actions">
            <?php if ($status_surat === 'disetujui'): ?>
                <?php if (in_array($user['role'], ['super_admin', 'desa', 'rt', 'rw']) && $id_surat_terbit > 0): ?>
                    <button onclick="openKirimSuratModal('/surat?action=kirim_surat&id=<?= $id_surat_terbit ?>&type=manual', '<?= htmlspecialchars($email_warga) ?>', '<?= htmlspecialchars($no_hp_warga) ?>', <?= $punya_email_asli ? 'true' : 'false' ?>, <?= $punya_wa ? 'true' : 'false' ?>)" class="btn" style="background:#16a34a;color:white;box-shadow:0 2px 6px rgba(22,163,74,0.3);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg> Kirim Surat
                    </button>
                <?php endif; ?>
                <button onclick="downloadPDF()" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download PDF
                </button>
            <?php else: ?>
                <div class="badge-pending">⏳ Menunggu Persetujuan Admin/RT</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="hint">💡 Klik <strong>Download PDF</strong> → pilih <strong>"Save as PDF"</strong></div>
</div>

<!-- KERTAS SURAT RESMI A4 -->
<div class="surat">
    <!-- KOP SURAT RESMI PEMERINTAH DESA -->
    <table class="kop-table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 85px; vertical-align: middle; text-align: left;">
                <img src="<?= $logoSrc ?>" alt="Logo Desa Sukamaju" style="width: 75px; height: auto; display: block;">
            </td>
            <td style="vertical-align: middle; text-align: center;">
                <div style="font-size: 14pt; font-weight: bold; line-height: 1.15; text-transform: uppercase; letter-spacing: 0.5px;">PEMERINTAH KABUPATEN CIREBON</div>
                <div style="font-size: 13pt; font-weight: bold; line-height: 1.15; text-transform: uppercase; letter-spacing: 0.5px;">KECAMATAN SUKAMAJU</div>
                <div style="font-size: 15pt; font-weight: bold; line-height: 1.2; letter-spacing: 1px; text-transform: uppercase;">PEMERINTAH DESA SUKAMAJU</div>
                <div style="font-size: 8.5pt; margin-top: 3px; line-height: 1.3;">
                    Jl. Raya Desa No. 1 - Kode Pos 45153<br>
                    Website: http://sukamaju.desa.id &middot; Email: pemdes@sukamaju.desa.id
                </div>
            </td>
            <td style="width: 85px;"></td>
        </tr>
    </table>
    <div style="border-top: 3px solid #000; margin-top: 6px;"></div>
    <div style="border-top: 1px solid #000; margin-top: 2px; margin-bottom: 18px;"></div>

    <!-- JUDUL DAN NOMOR SURAT -->
    <div style="text-align: center; margin-bottom: 16px;">
        <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; letter-spacing: 0.5px;"><?= htmlspecialchars($judul_resmi) ?></div>
        <div style="font-size: 10.5pt; margin-top: 3px;">Nomor: <?= htmlspecialchars($nomor) ?></div>
    </div>

    <!-- PARAGRAF PEMBUKA -->
    <p style="text-align: justify; margin-bottom: 10px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
        Yang bertanda tangan di bawah ini Kepala Desa Sukamaju, Kecamatan Sukamaju, Kabupaten Cirebon menerangkan dengan sebenarnya, bahwa:
    </p>

    <!-- IDENTITAS PEMOHON -->
    <table style="width: 100%; border-collapse: collapse; margin-left: 20px; margin-top: 6px; margin-bottom: 10px; font-size: 11pt;">
        <tr>
            <td style="width: 170px; padding: 2px 0; vertical-align: top;">Nama</td>
            <td style="width: 15px; padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top; font-weight: bold;"><?= htmlspecialchars(strtoupper($p_nama)) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top;">NIK</td>
            <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_nik) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top;">Jenis Kelamin</td>
            <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_jk) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top;">Tempat, Tanggal Lahir</td>
            <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_ttl) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top;">Warganegara / Agama</td>
            <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_warga_negara) ?> / <?= htmlspecialchars($p_agama) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top;">Pekerjaan</td>
            <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_pekerjaan) ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top;">Alamat</td>
            <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($alamat_lengkap) ?></td>
        </tr>
    </table>

    <!-- KETERANGAN SPESIFIK BERDASARKAN JENIS SURAT -->
    <?php if ($kategori === 'usaha'): ?>
        <p style="text-align: justify; margin-bottom: 8px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Sesuai dengan keterangan yang bersangkutan benar nama tersebut di atas mempunyai usaha sebagai berikut:
        </p>
        <table style="width: 100%; border-collapse: collapse; margin-left: 20px; margin-top: 4px; margin-bottom: 10px; font-size: 11pt;">
            <tr>
                <td style="width: 170px; padding: 2px 0; vertical-align: top;">Nama Usaha</td>
                <td style="width: 15px; padding: 2px 0; vertical-align: top; text-align: center;">:</td>
                <td style="padding: 2px 0; vertical-align: top; font-weight: bold;"><?= htmlspecialchars($p_nama_usaha ?: 'Usaha Perorangan') ?></td>
            </tr>
            <tr>
                <td style="padding: 2px 0; vertical-align: top;">Jenis Usaha / Bidang</td>
                <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
                <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_jenis_usaha ?: 'Perdagangan / Jasa') ?></td>
            </tr>
            <tr>
                <td style="padding: 2px 0; vertical-align: top;">Alamat Usaha</td>
                <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
                <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_alamat_usaha ?: 'Desa Sukamaju, Kec. Sukamaju') ?></td>
            </tr>
            <tr>
                <td style="padding: 2px 0; vertical-align: top;">Tujuan / Keperluan</td>
                <td style="padding: 2px 0; vertical-align: top; text-align: center;">:</td>
                <td style="padding: 2px 0; vertical-align: top;"><?= htmlspecialchars($p_keperluan ?: 'Persyaratan Administrasi') ?></td>
            </tr>
        </table>
    <?php elseif ($kategori === 'sktm'): ?>
        <p style="text-align: justify; margin-bottom: 8px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Berdasarkan data kependudukan dan peninjauan lapangan yang ada pada kami, bahwa nama tersebut di atas adalah benar-benar warga masyarakat Desa Sukamaju yang tergolong dalam keluarga <strong>Kurang Mampu / Tidak Mampu (Ekonomi Lemah)</strong>.
        </p>
        <p style="text-align: justify; margin-bottom: 10px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Surat keterangan ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Pengajuan Bantuan / Keringanan Biaya Administrasi') ?></strong>.
        </p>
    <?php elseif ($kategori === 'domisili'): ?>
        <p style="text-align: justify; margin-bottom: 8px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Berdasarkan catatan administrasi kependudukan desa kami, bahwa nama tersebut di atas adalah benar warga Desa Sukamaju yang berdomisili dan bertempat tinggal pada alamat tersebut di atas.
        </p>
        <p style="text-align: justify; margin-bottom: 10px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Surat keterangan domisili ini diberikan kepada yang bersangkutan untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Persyaratan Administrasi Kependudukan') ?></strong>.
        </p>
    <?php elseif ($kategori === 'ktp'): ?>
        <p style="text-align: justify; margin-bottom: 10px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Adalah benar yang bersangkutan merupakan warga Desa Sukamaju yang terdaftar dalam data kependudukan kami. Surat pengantar ini diberikan sebagai kelengkapan berkas administrasi dalam permohonan <strong>Penerbitan / Penggantian Kartu Tanda Penduduk Elektronik (KTP-el)</strong> di Kantor Pelayanan Kependudukan Kecamatan Sukamaju / Disdukcapil Kabupaten Cirebon.
        </p>
    <?php elseif ($kategori === 'skck'): ?>
        <p style="text-align: justify; margin-bottom: 8px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Adalah benar yang bersangkutan merupakan warga Desa Sukamaju yang bertempat tinggal di lingkungan kami dan sepanjang pengetahuan kami berkelakuan baik serta tidak sedang tersangkut perkara kriminal/hukum.
        </p>
        <p style="text-align: justify; margin-bottom: 10px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
            Surat pengantar ini diberikan untuk memenuhi persyaratan permohonan penerbitan <strong>Surat Keterangan Catatan Kepolisian (SKCK)</strong> di Kepolisian Sektor setempat untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Melamar Pekerjaan / Persyaratan Administrasi') ?></strong>.
        </p>
    <?php else: ?>
        <?php if (!empty($isi_surat)): ?>
            <div style="margin-top: 6px; margin-bottom: 10px; font-size: 11pt; line-height: 1.4;">
                <?= nl2br(htmlspecialchars($isi_surat)) ?>
            </div>
        <?php else: ?>
            <p style="text-align: justify; margin-bottom: 10px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
                Adalah benar yang bersangkutan merupakan warga Desa Sukamaju yang tercatat dalam registrasi kami. Surat keterangan ini dibuat untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Sebagaimana mestinya') ?></strong>.
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <!-- PARAGRAF PENUTUP -->
    <p style="text-align: justify; margin-bottom: 12px; text-indent: 28px; font-size: 11pt; line-height: 1.4;">
        Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
    </p>

    <!-- TANDA TANGAN KEPALA DESA -->
    <table style="width: 100%; margin-top: 20px; border-collapse: collapse; font-size: 11pt;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%; text-align: center; vertical-align: top;">
                <div>Sukamaju, <?= htmlspecialchars($tanggal_indo) ?></div>
                <div style="font-weight: bold; margin-top: 2px;">KEPALA DESA SUKAMAJU</div>
                <div style="height: 65px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">H. SUPARMAN, S.Sos</div>
                <div style="font-size: 9.5pt; margin-top: 2px;">NIP. 19650101 199003 1 001</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER SIWARGA SEDERHANA -->
    <div style="margin-top: 40px; border-top: 0.5px solid #cbd5e1; padding-top: 6px; text-align: center; font-size: 8pt; color: #64748b; line-height: 1.3;">
        Berkas ini diproses & dicetak melalui Sistem Informasi & Pelayanan Warga (SIWARGA)<br>
        Pemerintah Desa Sukamaju &middot; Kecamatan Sukamaju &middot; Kabupaten Cirebon
    </div>
</div>

<script>
function downloadPDF() {
    var originalTitle = document.title;
    document.title = <?= json_encode($namaFileSafe) ?>;
    window.print();
    setTimeout(function() { document.title = originalTitle; }, 1000);
}

function openKirimSuratModal(url, email, phone, hasEmail, hasWa) {
    var modal = document.getElementById('kirimSuratModal');
    var btnConfirm = document.getElementById('btnConfirmKirimSurat');
    
    if (!hasEmail) {
        document.getElementById('kirimSuratModalTitle').textContent = 'Email Pemohon Tidak Ditemukan';
        document.getElementById('kirimSuratModalBody').innerHTML = '<p style="color:#dc2626;font-size:14px;line-height:1.5;">⚠️ Pemohon belum memiliki alamat email yang valid di sistem.<br><br>Berkas PDF surat resmi wajib dikirimkan ke email pemohon terlebih dahulu sebelum notifikasi WhatsApp dikirimkan. Harap lengkapi email pada data kependudukan warga.</p>';
        btnConfirm.style.display = 'none';
        modal.classList.add('active');
        return;
    }

    document.getElementById('kirimSuratModalTitle').textContent = 'Kirim Surat Resmi';
    document.getElementById('modalKirimEmail').textContent = email;
    document.getElementById('modalKirimWa').textContent = hasWa ? phone : '(Nomor WhatsApp belum terdaftar)';
    btnConfirm.style.display = 'inline-block';
    btnConfirm.onclick = function() {
        btnConfirm.disabled = true;
        btnConfirm.textContent = 'Memproses Pengiriman...';
        window.location.href = url;
    };
    modal.classList.add('active');
}

function closeKirimSuratModal() {
    document.getElementById('kirimSuratModal').classList.remove('active');
}

// Kompatibilitas fungsi modal lama
function openEmailModal(url, email) { openKirimSuratModal(url, email, '', true, false); }
function closeEmailModal() { closeKirimSuratModal(); }
function openWaModal(url, phone) { openKirimSuratModal(url, '', phone, false, true); }
function closeWaModal() { closeKirimSuratModal(); }
</script>

</body>
</html>
