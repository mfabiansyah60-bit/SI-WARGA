<?php
// app/AI/ScopeFilter.php
// Filter pertanyaan — tolak yang di luar topik desa & lindungi data pribadi sensitif (NIK, password)

class ScopeFilter
{
    // ============================================
    // KEYWORD TERLARANG — kalau ketemu, langsung tolak
    // ============================================
    private static $blockedKeywords = [
        // Programming / coding
        'python', 'javascript', ' java ', 'html', 'css', ' php', 'sql',
        'coding', 'koding', 'program', 'script', 'framework', 'laravel',
        'react', 'vue', 'node js', 'database query',
        'algoritma', 'kode program', 'debug',

        // Matematika & sains
        'integral', 'turunan', 'persamaan', 'rumus matematika', 'algebra',
        'trigonometri', 'kalkulus', 'fisika', 'kimia', 'biologi molekuler',

        // Politik praktis
        'partai ', 'capres', 'caleg', 'kampanye', 'pemilu 2024',
        'prabowo', 'anies', 'ganjar', 'koalisi', 'oposisi',

        // SARA & sensitif
        'agama yang benar', 'kafir', 'sesat', 'ras ', 'suku ',

        // Umum di luar topik
        'resep ', 'masak ', 'cara bikin nasi', 'cara bikin kue',
        'pacaran', 'mantan', 'gebetan', 'jodoh', 'ramalan',
        'prediksi bola', 'skor ', 'jadwal bola', 'liga ',
        'lirik lagu', 'chord gitar', 'film ', 'anime', 'game online',
        'saham', 'crypto', 'bitcoin', 'trading', 'judi',
        'diet ', 'gym ', 'otot ',

        // Terjemahan
        'translate', 'terjemahkan ke', 'artinya dalam bahasa',
    ];

    // ============================================
    // KEYWORD TOPIK DESA — kalau ketemu, pasti boleh
    // ============================================
    private static $allowedKeywords = [
        'surat', 'domisili', 'sktm', 'skck', 'usaha', 'pindah',
        'ktp', 'kk', 'kartu keluarga', 'akta', 'lahir', 'kematian',
        'pengaduan', 'lapor', 'keluhan', 'aduan',
        'desa', 'kelurahan', 'kantor desa', 'kades', 'lurah', 'kepala desa',
        'rt', 'rw', 'warga', 'penduduk', 'kependudukan',
        'bantuan', 'blt', 'pkh', 'bpnt', 'bansos', 'sembako',
        'posyandu', 'puskesmas', 'kesehatan', 'vaksin', 'imunisasi',
        'pelayanan', 'layanan', 'administrasi', 'prosedur', 'syarat',
        'siwarga', 'aplikasi desa', 'sistem', 'database',
        'musyawarah', 'gotong royong', 'kerja bakti', 'rapat',
        'umkm', 'usaha kecil', 'izin usaha',
        'nikah', 'pernikahan', 'cerai', 'waris',
        'tanah', 'sertifikat', 'warisan',
        'pajak', 'retribusi', 'iuran',
        'beasiswa', 'sekolah', 'pendidikan',
        'program desa', 'kegiatan desa', 'anggaran desa',
        // Statistik & jumlah
        'berapa', 'jumlah', 'total', 'statistik', 'ada berapa',
        // Sapaan & terima kasih (biar tetap bisa balas ramah)
        'halo', 'hai', 'hello', 'assalam', 'salam', 'pagi', 'siang',
        'sore', 'malam', 'permisi',
        'terima kasih', 'makasih', 'thanks', 'tq', 'thx',
        // Info umum desa
        'jam buka', 'jam kerja', 'kontak', 'telepon', 'alamat',
        // Notifikasi & lonceng
        'notifikasi', 'notif', 'lonceng', 'pemberitahuan', 'pesan masuk',
        // User & Akun
        'user', 'akun', 'role', 'pengguna', 'peran',
    ];

    /**
     * Deteksi apakah user sedang mengisi form surat (bukan minta NIK orang lain).
     * Ciri: ada keyword field form surat ≥3 kali.
     */
    public static function isFormSuratRequest($message)
    {
        $msg = strtolower($message);

        // Harus ada 'nik' dan minimal 3 field form lain
        if (strpos($msg, 'nik') === false) return false;

        $fieldKeywords = [
            'nama lengkap', 'nama:',
            'tempat/tanggal lahir', 'tempat lahir', 'tanggal lahir', 'ttl',
            'jenis kelamin', 'jk',
            'pekerjaan',
            'alamat',
            'keperluan',
            'jenis surat',
        ];

        $count = 0;
        foreach ($fieldKeywords as $kw) {
            if (strpos($msg, $kw) !== false) $count++;
        }

        return $count >= 3;
    }

    /**
     * Cek apakah pesan adalah permintaan NIK / data NIK warga
     */
    public static function isNikRequest($message)
    {
        $msg = ' ' . strtolower(trim($message)) . ' ';

        // Jika bukan terkait NIK sama sekali, lewatkan
        if (strpos($msg, 'nik') === false && strpos($msg, 'nomor induk kependudukan') === false) {
            return false;
        }

        // 🆕 PENGECUALIAN 1: user sedang mengisi form surat
        // Contoh: "Jenis Surat: Domisili, Nama Lengkap: X, NIK: 123..., Pekerjaan: ..."
        if (self::isFormSuratRequest($message)) {
            return false;
        }

        // PENGECUALIAN 2: User sedang mengisi NIK sendiri (diikuti digit 6-16)
        // Contoh: "nik saya 3201012345678901" atau "NIK: 3201..."
        if (preg_match('/nik\s*(saya|ku|milik saya)?\s*[:=]?\s*\d{6,16}/i', $msg) && 
            !preg_match('/\b(semua|setiap|sebutkan|tampilkan|daftar|minta|kasih|cari|bocorkan|orang lain)\b/i', $msg)) {
            return false;
        }

        // Pola permintaan NIK warga
        $actionVerbs = '(kasih|kasitau|tau|tahu|minta|tampil|tampilkan|lihat|sebut|sebutkan|berapa|brp|apa|mana|bocor|bocorkan|daftar|list|cari|cek|bagi|bagikan|bocoran|semua|setiap|database|punya|siapa)';
        $targetTerms = '(warga|penduduk|orang|masyarakat|mereka|user|semua|setiap|si\s+\w+|[\w]+)';

        if (preg_match('/\b' . $actionVerbs . '\b.*\b(nik|nomor induk kependudukan)\b/i', $msg) ||
            preg_match('/\b(nik|nomor induk kependudukan)\b.*\b' . $actionVerbs . '\b/i', $msg) ||
            preg_match('/\b(nik|nomor induk kependudukan)\s+' . $targetTerms . '\b/i', $msg) ||
            preg_match('/\b(daftar|list|tabel|kumpulan|data)\s+nik\b/i', $msg)) {
            return true;
        }

        // Pertanyaan langsung soal NIK
        if (preg_match('/\b(nik|nomor induk kependudukan)\b/i', $msg) && 
            preg_match('/\b(warga|penduduk|orang|sistem|database)\b/i', $msg)) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah pesan meminta password / kredensial akun / database injection
     */
    public static function isCredentialRequest($message)
    {
        $msg = ' ' . strtolower(trim($message)) . ' ';
        $patterns = [
            '/\b(password|kata sandi|passwd|pwd)\b/i',
            '/\b(token|pin|kredensial|rahasia sistem)\b/i',
            '/\b(dump|drop table|select \* from users)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $msg)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah pertanyaan boleh dijawab
     */
    public static function check($message)
    {
        $msg = ' ' . strtolower(trim($message)) . ' ';

        if (strlen(trim($msg)) < 2) {
            return [
                'allowed' => false,
                'type'    => 'general',
                'reason'  => 'Pesan terlalu pendek'
            ];
        }

        // 1. CEK PRIVASI NIK
        if (self::isNikRequest($msg)) {
            return [
                'allowed' => false,
                'type'    => 'privacy_nik',
                'reason'  => 'Permintaan data pribadi NIK dilarang demi privasi'
            ];
        }

        // 2. CEK PRIVASI KREDENSIAL
        if (self::isCredentialRequest($msg)) {
            return [
                'allowed' => false,
                'type'    => 'privacy_cred',
                'reason'  => 'Permintaan kata sandi atau kredensial sistem dilarang'
            ];
        }

        // 3. Cek keyword topik desa (prioritas layanan & statistik kependudukan)
        foreach (self::$allowedKeywords as $keyword) {
            if (strpos($msg, $keyword) !== false) {
                return [
                    'allowed' => true,
                    'type'    => 'allowed',
                    'reason'  => 'topik desa terdeteksi'
                ];
            }
        }

        // 4. Cek keyword terlarang
        foreach (self::$blockedKeywords as $keyword) {
            if (strpos($msg, $keyword) !== false) {
                return [
                    'allowed' => false,
                    'type'    => 'general',
                    'reason'  => 'topik di luar layanan desa'
                ];
            }
        }

        // 5. Default: izinkan
        return [
            'allowed' => true,
            'type'    => 'allowed',
            'reason'  => 'default allow'
        ];
    }

    /**
     * Pesan penolakan khusus privasi NIK
     */
    public static function rejectNikMessage()
    {
        return "🔒 **Batasan Keamanan & Privasi Data Kependudukan**\n\n"
             . "Mohon maaf, **NIK (Nomor Induk Kependudukan)** adalah data identitas kependudukan yang bersifat **pribadi & rahasia** serta dilindungi oleh peraturan perundang-undangan perlindungan data pribadi.\n\n"
             . "🚫 **Batasan AI SIWARGA:**\n"
             . "• AI **DILARANG KERAS** menampilkan, membagikan, atau mencari NIK warga kepada siapa pun.\n"
             . "• Data pribadi sensitif seperti NIK, nomor telepon pribadi, dan kata sandi akun tidak akan pernah dibuka demi keamanan seluruh warga.\n\n"
             . "💡 **Pertanyaan yang diperbolehkan:**\n"
             . "• 📊 Menanyakan jumlah/statistik warga (contoh: *\"ada berapa warga di sistem\"*)\n"
             . "• 📍 Informasi RT/RW dan pengurus wilayah\n"
             . "• 📄 Pembuatan surat resmi (menggunakan NIK pribadi Anda sendiri)\n"
             . "• 📋 Persyaratan dan prosedur layanan kependudukan";
    }

    /**
     * Pesan penolakan khusus kredensial & password
     */
    public static function rejectCredentialMessage()
    {
        return "🔒 **Keamanan Sistem & Kredensial Akun**\n\n"
             . "Mohon maaf, informasi kata sandi, token, atau kredensial akun bersifat rahasia dan tidak dapat diakses oleh siapa pun termasuk melalui AI demi menjaga integritas dan keamanan sistem SIWARGA.";
    }

    /**
     * Pesan penolakan standar untuk topik di luar desa
     */
    public static function rejectMessage()
    {
        return "🙏 Maaf, aku cuma bisa bantu soal **layanan, administrasi & informasi desa** aja.\n\n"
             . "Contoh pertanyaan yang bisa dijawab:\n"
             . "• 📊 *\"Ada berapa warga yang ada di sistem?\"*\n"
             . "• 📄 *\"Cara buat Surat Domisili atau SKTM\"*\n"
             . "• 🪪 *\"Syarat pengurusan KTP atau KK\"*\n"
             . "• ⚠️ *\"Cara lapor pengaduan warga\"*";
    }
}