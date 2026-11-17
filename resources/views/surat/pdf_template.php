<?php
// resources/views/surat/pdf_template.php
// Template PDF Resmi Surat Pemerintah Desa Sukamaju, Kabupaten Cirebon
// Sesuai Standar Tata Naskah Dinas Pemerintahan Desa di Indonesia

$logoPng = dirname(__DIR__, 3) . '/public/images/logo_desa.png';
$logoJpg = dirname(__DIR__, 3) . '/public/images/logo_desa.jpg';
$logoBase64 = '';
if (file_exists($logoPng)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPng));
} elseif (file_exists($logoJpg)) {
    $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoJpg));
}

// Normalisasi Data Pemohon
$p_nama = '';
$p_nik = '';
$p_ttl = '';
$p_jk = '';
$p_agama = 'Islam';
$p_warga_negara = 'WNI';
$p_pekerjaan = '';
$p_alamat = '';
$p_rt = '';
$p_rw = '';
$p_keperluan = '';
$p_nama_usaha = '';
$p_jenis_usaha = '';
$p_alamat_usaha = '';

if (isset($data_surat) && is_array($data_surat)) {
    // Mode Surat AI
    $p_nama = $data_surat['nama'] ?? ($data_surat['Nama'] ?? ($surat['name'] ?? ''));
    $p_nik = $data_surat['nik'] ?? ($data_surat['NIK'] ?? '-');
    $p_ttl = $data_surat['ttl'] ?? ($data_surat['tempat_tanggal_lahir'] ?? '-');
    $p_jk = $data_surat['jk'] ?? ($data_surat['jenis_kelamin'] ?? ($surat['jenis_kelamin'] ?? 'Laki-laki'));
    $p_agama = $data_surat['agama'] ?? 'Islam';
    $p_warga_negara = $data_surat['kewarganegaraan'] ?? ($data_surat['warga_negara'] ?? 'WNI');
    $p_pekerjaan = $data_surat['pekerjaan'] ?? ($data_surat['Pekerjaan'] ?? '-');
    $p_alamat = $data_surat['alamat'] ?? ($data_surat['Alamat'] ?? '');
    $p_rt = $data_surat['rt'] ?? ($surat['rt'] ?? '');
    $p_rw = $data_surat['rw'] ?? ($surat['rw'] ?? '');
    $p_keperluan = $data_surat['keperluan'] ?? ($data_surat['Keperluan'] ?? ($data_surat['tujuan'] ?? ''));
    $p_nama_usaha = $data_surat['nama_usaha'] ?? ($data_surat['Nama Usaha'] ?? '');
    $p_jenis_usaha = $data_surat['jenis_usaha'] ?? ($data_surat['Jenis Usaha'] ?? '');
    $p_alamat_usaha = $data_surat['alamat_usaha'] ?? ($data_surat['Alamat Usaha'] ?? '');
} elseif (isset($replacements) && is_array($replacements)) {
    // Mode Surat Manual dengan Replacements
    $p_nama = $replacements['{nama}'] ?? ($surat['name'] ?? '');
    $p_nik = $replacements['{nik}'] ?? '-';
    $p_ttl = $replacements['{ttl}'] ?? '-';
    $p_jk = $replacements['{jk}'] ?? ($surat['jenis_kelamin'] ?? 'Laki-laki');
    $p_agama = $replacements['{agama}'] ?? 'Islam';
    $p_warga_negara = $replacements['{kewarganegaraan}'] ?? ($replacements['{warga_negara}'] ?? 'WNI');
    $p_pekerjaan = $replacements['{pekerjaan}'] ?? '-';
    $p_alamat = $replacements['{alamat}'] ?? '';
    $p_rt = $replacements['{rt}'] ?? ($surat['rt'] ?? '');
    $p_rw = $replacements['{rw}'] ?? ($surat['rw'] ?? '');
    $p_keperluan = $replacements['{keperluan}'] ?? '';
    $p_nama_usaha = $replacements['{nama_usaha}'] ?? '';
    $p_jenis_usaha = $replacements['{jenis_usaha}'] ?? '';
    $p_alamat_usaha = $replacements['{alamat_usaha}'] ?? '';
}

// Fallback nama dari record pemohon jika kosong
if (empty($p_nama) && !empty($surat['name'])) {
    $p_nama = $surat['name'];
}

// Susun Alamat Lengkap
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

// Penentuan Judul & Kategori Surat
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

$nomor_tampil = $nomor ?? '-';
$tanggal_tampil = $tanggal_indo ?? date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($judul_resmi) ?> - <?= htmlspecialchars($nomor_tampil) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm 2cm 1.5cm 2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
            margin: 0;
            padding: 0;
        }
        table.kop {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-logo {
            width: 85px;
            vertical-align: middle;
            text-align: left;
        }
        .kop-logo img {
            width: 75px;
            height: auto;
            display: block;
        }
        .kop-text {
            vertical-align: middle;
            text-align: center;
        }
        .kop-spacer {
            width: 85px;
        }
        .kop-kabupaten {
            font-size: 13.5pt;
            font-weight: bold;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop-kecamatan {
            font-size: 12.5pt;
            font-weight: bold;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop-desa {
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.2;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .kop-alamat {
            font-size: 8.5pt;
            margin-top: 3px;
            line-height: 1.25;
        }
        .line-bold {
            border-top: 3px solid #000;
            margin-top: 5px;
        }
        .line-thin {
            border-top: 1px solid #000;
            margin-top: 2px;
            margin-bottom: 14px;
        }
        .judul-section {
            text-align: center;
            margin-bottom: 14px;
        }
        .judul-surat {
            font-size: 12.5pt;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 0.5px;
        }
        .nomor-surat {
            font-size: 10.5pt;
            margin-top: 2px;
        }
        .paragraf {
            text-align: justify;
            margin-bottom: 8px;
            text-indent: 28px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-left: 20px;
            margin-top: 4px;
            margin-bottom: 8px;
        }
        table.data-table td {
            padding: 1.5px 0;
            vertical-align: top;
            font-size: 10.5pt;
        }
        table.data-table td.label {
            width: 165px;
        }
        table.data-table td.colon {
            width: 15px;
            text-align: center;
        }
        table.data-table td.val {
            font-weight: normal;
        }
        table.data-table td.val-bold {
            font-weight: bold;
        }
        table.ttd-table {
            width: 100%;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .footer-siwarga {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            border-top: 0.5px solid #cbd5e1;
            padding-top: 3px;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <!-- FOOTER SIWARGA SEDERHANA -->
    <div class="footer-siwarga">
        Berkas ini diproses & dicetak melalui Sistem Informasi & Pelayanan Warga (SIWARGA)<br>
        Pemerintah Desa Sukamaju &middot; Kecamatan Sukamaju &middot; Kabupaten Cirebon
    </div>

    <!-- KOP SURAT RESMI PEMERINTAH DESA -->
    <table class="kop">
        <tr>
            <td class="kop-logo">
                <?php if (!empty($logoBase64)): ?>
                    <img src="<?= $logoBase64 ?>" alt="Logo Desa Sukamaju">
                <?php endif; ?>
            </td>
            <td class="kop-text">
                <div class="kop-kabupaten">PEMERINTAH KABUPATEN CIREBON</div>
                <div class="kop-kecamatan">KECAMATAN SUKAMAJU</div>
                <div class="kop-desa">PEMERINTAH DESA SUKAMAJU</div>
                <div class="kop-alamat">
                    Jl. Raya Desa No. 1 - Kode Pos 45153<br>
                    Website: http://sukamaju.desa.id &middot; Email: pemdes@sukamaju.desa.id
                </div>
            </td>
            <td class="kop-spacer"></td>
        </tr>
    </table>
    <div class="line-bold"></div>
    <div class="line-thin"></div>

    <!-- JUDUL DAN NOMOR SURAT -->
    <div class="judul-section">
        <div class="judul-surat"><?= htmlspecialchars($judul_resmi) ?></div>
        <div class="nomor-surat">Nomor: <?= htmlspecialchars($nomor_tampil) ?></div>
    </div>

    <!-- PARAGRAF PEMBUKA -->
    <div class="paragraf">
        Yang bertanda tangan di bawah ini Kepala Desa Sukamaju, Kecamatan Sukamaju, Kabupaten Cirebon menerangkan dengan sebenarnya, bahwa:
    </div>

    <!-- IDENTITAS PEMOHON -->
    <table class="data-table">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="val-bold"><?= htmlspecialchars(strtoupper($p_nama)) ?></td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="colon">:</td>
            <td class="val"><?= htmlspecialchars($p_nik) ?></td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="colon">:</td>
            <td class="val"><?= htmlspecialchars($p_jk) ?></td>
        </tr>
        <tr>
            <td class="label">Tempat, Tanggal Lahir</td>
            <td class="colon">:</td>
            <td class="val"><?= htmlspecialchars($p_ttl) ?></td>
        </tr>
        <tr>
            <td class="label">Warganegara / Agama</td>
            <td class="colon">:</td>
            <td class="val"><?= htmlspecialchars($p_warga_negara) ?> / <?= htmlspecialchars($p_agama) ?></td>
        </tr>
        <tr>
            <td class="label">Pekerjaan</td>
            <td class="colon">:</td>
            <td class="val"><?= htmlspecialchars($p_pekerjaan) ?></td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td class="val"><?= htmlspecialchars($alamat_lengkap) ?></td>
        </tr>
    </table>

    <!-- KETERANGAN SPESIFIK BERDASARKAN JENIS SURAT -->
    <?php if ($kategori === 'usaha'): ?>
        <div class="paragraf">
            Sesuai dengan keterangan yang bersangkutan benar nama tersebut di atas mempunyai usaha sebagai berikut:
        </div>
        <table class="data-table">
            <tr>
                <td class="label">Nama Usaha</td>
                <td class="colon">:</td>
                <td class="val-bold"><?= htmlspecialchars($p_nama_usaha ?: 'Usaha Perorangan') ?></td>
            </tr>
            <tr>
                <td class="label">Jenis Usaha / Bidang</td>
                <td class="colon">:</td>
                <td class="val"><?= htmlspecialchars($p_jenis_usaha ?: 'Perdagangan / Jasa') ?></td>
            </tr>
            <tr>
                <td class="label">Alamat Usaha</td>
                <td class="colon">:</td>
                <td class="val"><?= htmlspecialchars($p_alamat_usaha ?: 'Desa Sukamaju, Kec. Sukamaju') ?></td>
            </tr>
            <tr>
                <td class="label">Tujuan / Keperluan</td>
                <td class="colon">:</td>
                <td class="val"><?= htmlspecialchars($p_keperluan ?: 'Persyaratan Administrasi') ?></td>
            </tr>
        </table>
    <?php elseif ($kategori === 'sktm'): ?>
        <div class="paragraf">
            Berdasarkan data kependudukan dan peninjauan lapangan yang ada pada kami, bahwa nama tersebut di atas adalah benar-benar warga masyarakat Desa Sukamaju yang tergolong dalam keluarga <strong>Kurang Mampu / Tidak Mampu (Ekonomi Lemah)</strong>.
        </div>
        <div class="paragraf">
            Surat keterangan ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Pengajuan Bantuan / Keringanan Biaya Administrasi') ?></strong>.
        </div>
    <?php elseif ($kategori === 'domisili'): ?>
        <div class="paragraf">
            Berdasarkan catatan administrasi kependudukan desa kami, bahwa nama tersebut di atas adalah benar warga Desa Sukamaju yang berdomisili dan bertempat tinggal pada alamat tersebut di atas.
        </div>
        <div class="paragraf">
            Surat keterangan domisili ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Persyaratan Administrasi Kependudukan') ?></strong>.
        </div>
    <?php elseif ($kategori === 'ktp'): ?>
        <div class="paragraf">
            Adalah benar yang bersangkutan merupakan warga Desa Sukamaju yang terdaftar dalam data kependudukan kami. Surat pengantar ini diberikan sebagai kelengkapan berkas administrasi dalam permohonan <strong>Penerbitan / Penggantian Kartu Tanda Penduduk Elektronik (KTP-el)</strong> di Kantor Pelayanan Kependudukan Kecamatan Sukamaju / Disdukcapil Kabupaten Cirebon.
        </div>
    <?php elseif ($kategori === 'skck'): ?>
        <div class="paragraf">
            Adalah benar yang bersangkutan merupakan warga Desa Sukamaju yang bertempat tinggal di lingkungan kami dan sepanjang pengetahuan kami berkelakuan baik serta tidak sedang tersangkut perkara kriminal/hukum.
        </div>
        <div class="paragraf">
            Surat pengantar ini diberikan untuk memenuhi persyaratan permohonan penerbitan <strong>Surat Keterangan Catatan Kepolisian (SKCK)</strong> di Kepolisian Sektor setempat untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Melamar Pekerjaan / Persyaratan Administrasi') ?></strong>.
        </div>
    <?php else: ?>
        <?php if (!empty($isi_surat)): ?>
            <div style="margin-top: 6px; margin-bottom: 8px;">
                <?= nl2br(htmlspecialchars($isi_surat)) ?>
            </div>
        <?php else: ?>
            <div class="paragraf">
                Adalah benar yang bersangkutan merupakan warga Desa Sukamaju yang tercatat dalam registrasi kami. Surat keterangan ini dibuat untuk keperluan: <strong><?= htmlspecialchars($p_keperluan ?: 'Sebagaimana mestinya') ?></strong>.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- PARAGRAF PENUTUP -->
    <div class="paragraf">
        Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
    </div>

    <!-- TANDA TANGAN KEPALA DESA -->
    <table class="ttd-table">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%; text-align: center; vertical-align: top;">
                <div>Sukamaju, <?= htmlspecialchars($tanggal_tampil) ?></div>
                <div style="font-weight: bold; margin-top: 2px;">KEPALA DESA SUKAMAJU</div>
                <div style="height: 60px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">H. SUPARMAN, S.Sos</div>
                <div style="font-size: 9.5pt; margin-top: 2px;">NIP. 19650101 199003 1 001</div>
            </td>
        </tr>
    </table>
</body>
</html>
