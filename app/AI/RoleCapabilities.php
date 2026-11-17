<?php
// app/AI/RoleCapabilities.php
// Matriks kemampuan AI per role

class RoleCapabilities
{
    // ============================================
    // MATRIKS CAPABILITIES PER ROLE
    // ============================================
    private static $caps = [
        'super_admin' => [
            'db_schema'          => true,
            'db_count'           => true,
            'system_log'         => true,
            'internal_system'    => true,
            'all_users'          => true,
            'user_stats'         => true,
            'role_info'          => true,
            'statistik_global'   => true,
            'data_warga_global'  => true,
            'data_warga_rw'      => true,
            'data_warga_rt'      => true,
            'pengaduan_global'   => true,
            'surat_global'       => true,
            'layanan_desa'       => true,
            'info_desa'          => true,
            'info_rt_rw_self'    => true,
            'bikin_surat'        => true,
            'verifikasi_warga'   => true,
            'notifikasi'         => true,
        ],
        'desa' => [
            'statistik_global'   => true,
            'data_warga_global'  => true,
            'data_warga_rw'      => true,
            'data_warga_rt'      => true,
            'pengaduan_global'   => true,
            'surat_global'       => true,
            'layanan_desa'       => true,
            'info_desa'          => true,
            'info_rt_rw_self'    => true,
            'bikin_surat'        => true,
            'verifikasi_warga'   => true,
            'notifikasi'         => true,
        ],
        'rw' => [
            'statistik_rw'       => true,
            'data_warga_rw'      => true,
            'data_rt'            => true,
            'pengaduan_rw'       => true,
            'surat_rw'           => true,
            'layanan_desa'       => true,
            'info_desa'          => true,
            'info_rt_rw_self'    => true,
            'bikin_surat'        => true,
            'verifikasi_warga'   => true,
            'notifikasi'         => true,
        ],
        'rt' => [
            'statistik_rt'       => true,
            'data_warga_rt'      => true,
            'pengaduan_rt'       => true,
            'surat_rt'           => true,
            'layanan_desa'       => true,
            'info_desa'          => true,
            'info_rt_rw_self'    => true,
            'bikin_surat'        => true,
            'verifikasi_warga'   => true,
            'notifikasi'         => true,
        ],
        'warga' => [
            'info_pribadi'       => true,
            'layanan_desa'       => true,
            'info_desa'          => true,
            'bikin_surat'        => true,
            'info_rt_rw_self'    => true,
            'pengaduan_saya'     => true,
            'surat_saya'         => true,
            'notifikasi'         => true,
        ],
    ];

    public static function get($role)
    {
        return self::$caps[$role] ?? [];
    }

    public static function can($role, $cap)
    {
        return !empty(self::$caps[$role][$cap]);
    }

    public static function label($role)
    {
        return [
            'super_admin' => 'Super Admin',
            'desa'        => 'Admin Desa',
            'rw'          => 'Ketua RW',
            'rt'          => 'Ketua RT',
            'warga'       => 'Warga',
        ][$role] ?? 'Pengguna';
    }

    public static function intro($role)
    {
        $intros = [
            'super_admin' =>
                "Aku SIWARGA AI — Mode **Super Admin** 🛡️\n\n" .
                "Yang bisa aku bantu:\n" .
                "• 🗄️ **Struktur database** (daftar tabel, kolom, baris)\n" .
                "• 👥 **Semua user & role** di sistem\n" .
                "• 📊 **Statistik global** (kependudukan, RT/RW, surat, aduan)\n" .
                "• 🔍 **Pencarian warga** seluruh desa\n" .
                "• 📢 **Pengumuman & Layanan**\n" .
                "• 📄 **Bikin surat otomatis**\n\n" .
                "Coba tanya: *\"RW mana warganya paling banyak?\"* atau *\"statistik desa\"*",

            'desa' =>
                "Aku SIWARGA AI — Mode **Admin Desa** 🏛️\n\n" .
                "Yang bisa aku bantu:\n" .
                "• 📊 **Statistik desa** (warga, RT/RW, gender, aduan)\n" .
                "• 👥 **Data seluruh warga desa**\n" .
                "• 🏘️ **Rekap & Ranking RT/RW**\n" .
                "• 📢 **Pengaduan & Surat desa**\n" .
                "• 📄 **Bikin surat otomatis**\n" .
                "• 🏛️ **Informasi & Layanan desa**",

            'rw' =>
                "Aku SIWARGA AI — Mode **Ketua RW** 🏘️\n\n" .
                "Yang bisa aku bantu:\n" .
                "• 📊 **Statistik wilayah RW Anda**\n" .
                "• 👥 **Data warga di RW Anda**\n" .
                "• 🏠 **Data RT di lingkungan RW Anda**\n" .
                "• 📢 **Pengaduan warga RW Anda**\n" .
                "• 📄 **Bikin surat otomatis**",

            'rt' =>
                "Aku SIWARGA AI — Mode **Ketua RT** 🏠\n\n" .
                "Yang bisa aku bantu:\n" .
                "• 📊 **Statistik wilayah RT Anda**\n" .
                "• 👥 **Data warga di RT Anda**\n" .
                "• 📢 **Pengaduan warga RT Anda**\n" .
                "• 📄 **Bikin surat otomatis**",

            'warga' =>
                "Aku SIWARGA AI — Mode **Warga** 👤\n\n" .
                "Yang bisa aku bantu:\n" .
                "• 📍 **Info RT/RW kamu** (nama ketua & kontak)\n" .
                "• 📄 **Status pengajuan surat kamu**\n" .
                "• ⚠️ **Status pengaduan kamu**\n" .
                "• 📄 **Bikin surat otomatis** (Domisili, SKTM, Usaha, KTP)\n" .
                "• 📋 **Katalog layanan & syarat desa**\n" .
                "• 📊 **Jumlah warga di wilayah kamu**\n" .
                "• 🏛️ **Info kantor desa & jam buka**",
        ];
        return $intros[$role] ?? "Aku SIWARGA AI, asisten desa.";
    }

    // ============================================
    // DETEKSI CAPABILITY DARI PERTANYAAN
    // ============================================
    public static function detectCapability($q, $user = null)
    {
        $q = strtolower(trim($q));

        // 1. Konfigurasi internal, token, API keys, dan kredensial (Super Admin only)
        if (preg_match('/\b(konfigurasi\s+ai|konfigurasi\s+whatsapp|konfigurasi\s+wa|api\s*key|backup\s*keys|token\s+wa|token\s+api|fonnte|gemini\s+api|kredensial|secret|password\s+admin|mailer\s+config|server\s+config|\.env|setting\s+sistem)\b/i', $q)) {
            return 'internal_system';
        }

        // 2. Struktur database / tabel / kolom (Super Admin only)
        if (preg_match('/\b(tabel|table|kolom|column|schema|struktur\s+db|struktur\s+database|daftar\s+tabel|list\s+tabel|info\s+schema)\b/i', $q)) {
            return 'db_schema';
        }

        // 3. Jumlah baris per tabel (Super Admin only)
        if (preg_match('/\b(berapa\s+(?:baris|row|data)\s+di|isi\s+tabel|count\s+tabel)\b/i', $q)) {
            return 'db_count';
        }

        // 4. Log sistem (Super Admin only)
        if (preg_match('/\b(log|aktivitas\s+sistem|riwayat\s+sistem|error\s+log|log\s+aktivitas)\b/i', $q)) {
            return 'system_log';
        }

        // 5. Role sistem & Rincian per role (Super Admin only)
        if (
            preg_match('/\b(ada\s+berapa\s+(?:jenis\s+)?(?:role|peran)|berapa\s+(?:jenis\s+)?(?:role|peran)|(?:role|peran)\s+apa\s+saja|daftar\s+role|list\s+role|jenis\s+role|sebaran\s+role|rincian\s+role|distribusi\s+role)\b/i', $q) ||
            preg_match('/\b(?:role|peran)\s+(?:apa\s+saja|ada\s+berapa)\b/i', $q) ||
            preg_match('/\b(setiap|tiap|per|masing[- ]masing)\s+(?:role|peran)\b/i', $q) ||
            (preg_match('/\b(?:role|peran)\b/i', $q) && preg_match('/\b(tersedia|ada\s+di\s+sistem|di\s+siwarga)\b/i', $q) && !preg_match('/\b(saya|kamu|menu|fitur)\b/i', $q)) ||
            (preg_match('/\b(berapa|jumlah|total)\s+(?:akun|user|pengguna)\s+(?:super\s*admin|admin\s*desa|ketua\s*rw|ketua\s*rt|warga)\b/i', $q))
        ) {
            return 'role_info';
        }

        // 6. User Stats & Daftar Semua User Sistem (Super Admin only)
        // Mengecualikan pertanyaan jumlah layanan, fitur, menu, notifikasi, pengaduan, atau surat
        if (
            (preg_match('/\b(berapa\s+total\s+(?:user|akun|pengguna)|ada\s+berapa\s+(?:user|akun|pengguna)|berapa\s+(?:user|akun|pengguna)|jumlah\s+(?:user|akun|pengguna)|total\s+(?:user|akun|pengguna)|hitung\s+(?:user|akun|pengguna)|akun\s+terdaftar|user\s+terdaftar|pengguna\s+terdaftar)\b/i', $q) ||
             preg_match('/\b(daftar\s+user|list\s+user|daftar\s+akun|list\s+akun|semua\s+user|siapa\s+saja\s+user|siapa\s+saja\s+admin|daftar\s+admin|semua\s+akun)\b/i', $q)) &&
            !preg_match('/\b(layanan|pelayanan|fitur|menu|surat|pengaduan|notifikasi|warga\s+desa|penduduk)\b/i', $q)
        ) {
            return 'user_stats';
        }

        // 7. Daftar nama warga global desa (Hanya Super Admin & Admin Desa)
        if (preg_match('/\b(daftar\s+warga\s+desa|list\s+nama\s+warga|semua\s+nama\s+warga|siapa\s+saja\s+warga\s+desa|daftar\s+semua\s+warga|daftar\s+penduduk\s+desa)\b/i', $q)) {
            return 'data_warga_global';
        }

        // 8. Verifikasi akun / warga (Hanya petugas: super_admin, desa, rw, rt)
        // Kecualikan jika user menanyakan status verifikasi akun/profil pribadinya sendiri
        if (!preg_match('/\b(akun\s+saya|profil\s+saya|data\s+saya|status\s+saya|saya\s+sudah|diri\s+saya)\b/i', $q)) {
            if (preg_match('/\b(verifikasi\s+(?:warga|akun|user|pengguna)|(?:akun|user|pengguna)\s+pending|nunggu\s+verifikasi|menunggu\s+verifikasi|(?:butuh|membutuhkan|perlu)\s+verifikasi|daftar\s+verifikasi|(?:belum|blm)\s+di?verifikasi)\b/i', $q) && !preg_match('/\b(surat|pengaduan|laporan|email|otp|api)\b/i', $q)) {
                return 'verifikasi_warga';
            }
        }

        // 9. Pengaduan seluruh desa (Hanya Super Admin & Desa)
        if (!preg_match('/\b(pengaduan\s+saya|aduan\s+saya|laporan\s+saya|komplain\s+saya)\b/i', $q)) {
            if (preg_match('/\b(semua\s+pengaduan|daftar\s+pengaduan\s+desa|rekap\s+pengaduan\s+desa|pengaduan\s+seluruh\s+desa)\b/i', $q)) {
                return 'pengaduan_global';
            }
        }

        // 10. Surat seluruh desa (Hanya Super Admin & Desa)
        if (!preg_match('/\b(surat\s+saya|pengajuan\s+saya|riwayat\s+surat\s+saya)\b/i', $q)) {
            if (preg_match('/\b(semua\s+surat\s+desa|daftar\s+surat\s+desa|rekap\s+surat\s+desa|surat\s+seluruh\s+desa)\b/i', $q)) {
                return 'surat_global';
            }
        }

        // 11. Bikin surat
        if (preg_match('/\b(buat|bikin|ajukan|urus)\s+(surat|domisili|sktm|skck|ktp|usaha)\b/i', $q)) {
            return 'bikin_surat';
        }

        // 12. Pengecekan Batas Wilayah Teritorial (RT & RW & Warga) jika $user tersedia
        if ($user && is_array($user)) {
            $userRole = $user['role'] ?? 'warga';
            $uRt = !empty($user['rt']) ? str_pad($user['rt'], 2, '0', STR_PAD_LEFT) : null;
            $uRw = !empty($user['rw']) ? str_pad($user['rw'], 2, '0', STR_PAD_LEFT) : null;

            // Jika role RT menanyakan RT lain atau RW lain
            if ($userRole === 'rt') {
                if (preg_match('/\brt\s*0?(\d+)\b/i', $q, $mTargetRt)) {
                    $tgtRt = str_pad($mTargetRt[1], 2, '0', STR_PAD_LEFT);
                    if ($uRt !== null && $tgtRt !== $uRt && preg_match('/\b(warga|penduduk|orang|jiwa|pengaduan|surat)\b/i', $q) && !preg_match('/\b(ketua|kontak|telp|no_hp|siapa)\b/i', $q)) {
                        return 'wilayah_luar_rt';
                    }
                }
                if (preg_match('/\brw\s*0?(\d+)\b/i', $q, $mTargetRw)) {
                    $tgtRw = str_pad($mTargetRw[1], 2, '0', STR_PAD_LEFT);
                    if ($uRw !== null && $tgtRw !== $uRw && preg_match('/\b(warga|penduduk|orang|jiwa|pengaduan|surat)\b/i', $q) && !preg_match('/\b(ketua|kontak|telp|no_hp|siapa)\b/i', $q)) {
                        return 'wilayah_luar_rt';
                    }
                }
            }

            // Jika role RW menanyakan RW lain
            if ($userRole === 'rw') {
                if (preg_match('/\brw\s*0?(\d+)\b/i', $q, $mTargetRw)) {
                    $tgtRw = str_pad($mTargetRw[1], 2, '0', STR_PAD_LEFT);
                    if ($uRw !== null && $tgtRw !== $uRw && preg_match('/\b(warga|penduduk|orang|jiwa|pengaduan|surat)\b/i', $q) && !preg_match('/\b(ketua|kontak|telp|no_hp|siapa)\b/i', $q)) {
                        return 'wilayah_luar_rw';
                    }
                }
            }

            // Jika role Warga menanyakan perbandingan ranking seluruh RW/RT atau wilayah di luar RT/RW-nya
            if ($userRole === 'warga') {
                if (preg_match('/\brt\s*0?(\d+)\b/i', $q, $mTargetRt)) {
                    $tgtRt = str_pad($mTargetRt[1], 2, '0', STR_PAD_LEFT);
                    if ($uRt !== null && $tgtRt !== $uRt && preg_match('/\b(warga|penduduk|orang|jiwa|pengaduan|surat)\b/i', $q)) {
                        return 'wilayah_luar_warga';
                    }
                }
                if (preg_match('/\brw\s*0?(\d+)\b/i', $q, $mTargetRw)) {
                    $tgtRw = str_pad($mTargetRw[1], 2, '0', STR_PAD_LEFT);
                    if ($uRw !== null && $tgtRw !== $uRw && preg_match('/\b(warga|penduduk|orang|jiwa|pengaduan|surat)\b/i', $q) && !preg_match('/\b(ketua|kontak|telp|no_hp|siapa)\b/i', $q)) {
                        return 'wilayah_luar_warga';
                    }
                }
            }
        }

        return null;
    }

    // ============================================
    // CEK AKSES
    // ============================================
    public static function checkAccess($role, $q, $user = null)
    {
        if (is_array($role) && $user === null) {
            $user = $role;
            $role = $user['role'] ?? 'warga';
        }

        $cap = self::detectCapability($q, $user);
        if ($cap === null) {
            return ['allowed' => true];
        }

        // Wilayah luar langsung tolak
        if (in_array($cap, ['wilayah_luar_rt', 'wilayah_luar_rw', 'wilayah_luar_warga'])) {
            return [
                'allowed' => false,
                'capability' => $cap,
                'reason' => "Permintaan data berada di luar kewenangan wilayah teritorial role $role",
            ];
        }

        if (self::can($role, $cap)) {
            return ['allowed' => true, 'capability' => $cap];
        }

        return [
            'allowed' => false,
            'capability' => $cap,
            'reason' => "Role " . self::label($role) . " tidak punya akses ke '$cap'",
        ];
    }

    // ============================================
    // PESAN PENOLAKAN
    // ============================================
    public static function rejectMessage($role, $cap, $user = null)
    {
        $roleName = self::label($role);
        $userRt = !empty($user['rt']) ? str_pad($user['rt'], 2, '0', STR_PAD_LEFT) : '-';
        $userRw = !empty($user['rw']) ? str_pad($user['rw'], 2, '0', STR_PAD_LEFT) : '-';

        $messages = [
            'db_schema' => "🙏 Maaf, informasi mengenai **struktur database teknis** (tabel, kolom, dan relasi) hanya dapat diakses oleh **Super Admin**.",
            'db_count'  => "🙏 Maaf, informasi mengenai **jumlah baris tabel database** hanya dapat diakses oleh **Super Admin**.",
            'system_log' => "🙏 Maaf, akses ke **log aktivitas dan riwayat teknis sistem** hanya diperuntukkan bagi **Super Admin**.",
            'internal_system' => "🙏 Maaf, informasi **konfigurasi internal sistem, kredensial, dan API Key** bersifat rahasia dan hanya dapat diakses oleh **Super Admin**.",
            'all_users' => ($role === 'warga')
                ? "🙏 Maaf, informasi mengenai akun sistem, rincian role, dan manajemen pengguna hanya dapat diakses oleh Administrator. Sebagai Warga, Anda dapat menanyakan informasi profil pribadi, pengajuan surat, pengaduan, atau syarat layanan desa. 😊"
                : "🙏 Maaf, daftar seluruh akun pengguna sistem dikelola secara terpusat oleh **Super Admin** pada menu Manajemen User.",
            'user_stats' => ($role === 'warga')
                ? "🙏 Maaf, informasi mengenai akun sistem, rincian role, dan manajemen pengguna hanya dapat diakses oleh Administrator. Sebagai Warga, Anda dapat menanyakan informasi profil pribadi, pengajuan surat, pengaduan, atau syarat layanan desa. 😊"
                : "🙏 Maaf, rincian statistik akun pengguna login sistem dan Manajemen User dikelola secara khusus oleh **Super Admin**.",
            'role_info' => ($role === 'warga')
                ? "🙏 Maaf, informasi mengenai pembagian role dan akun sistem hanya dapat diakses oleh Administrator. Sebagai Warga, Anda dapat menanyakan informasi profil pribadi, pengajuan surat, pengaduan, atau syarat layanan desa. 😊"
                : "🙏 Maaf, informasi rincian seluruh role dan pembagian akun sistem dikelola secara khusus oleh **Super Admin**.",
            'data_warga_global' => ($role === 'warga')
                ? "🙏 Maaf, demi menjaga kerahasiaan dan privasi data kependudukan, akun Warga hanya dapat mengakses data profil pribadi Anda sendiri. 😊"
                : "🙏 Maaf, akses data warga seluruh desa hanya diperuntukkan bagi Admin Desa dan Super Admin.",
            'pengaduan_global' => ($role === 'warga')
                ? "🙏 Maaf, sebagai Warga Anda hanya dapat melihat status dan riwayat pengaduan milik Anda sendiri demi menjaga privasi warga. 😊"
                : "🙏 Maaf, rekapitulasi pengaduan seluruh desa hanya untuk Admin Desa dan Super Admin.",
            'surat_global' => ($role === 'warga')
                ? "🙏 Maaf, sebagai Warga Anda hanya dapat melihat riwayat pengajuan surat milik Anda sendiri demi menjaga privasi pemohon. 😊"
                : "🙏 Maaf, akses data surat seluruh desa hanya untuk Admin Desa dan Super Admin.",
            'verifikasi_warga' => "🙏 Maaf, informasi **Verifikasi Akun** hanya dapat diakses oleh perangkat desa yang berwenang (RT/RW/Admin Desa/Super Admin).\n\nRole Anda: **$roleName**\n\n💡 *Untuk memeriksa status verifikasi akun Anda sendiri, Anda dapat melihatnya di menu Profil.*",
            'wilayah_luar_rt' => "🙏 Maaf, wewenang Anda sebagai Ketua RT terbatas pada data di wilayah **RT $userRt / RW $userRw**. Anda tidak memiliki akses untuk melihat data di luar wilayah tersebut. 😊",
            'wilayah_luar_rw' => "🙏 Maaf, wewenang Anda sebagai Ketua RW terbatas pada data di wilayah **RW $userRw**. Anda tidak memiliki akses untuk melihat data di luar wilayah tersebut. 😊",
            'wilayah_luar_warga' => "🙏 Maaf, sebagai Warga Anda hanya dapat mengakses data di lingkungan **RT $userRt / RW $userRw** domisili Anda. 😊",
        ];

        return $messages[$cap] ?? ("🙏 Maaf, sebagai **$roleName**, Anda tidak memiliki akses untuk melihat informasi tersebut.\n\n" .
               "Anda dapat menanyakan hal lain sesuai kewenangan Anda:\n" .
               "• Layanan dan syarat persuratan desa\n" .
               "• Status pengajuan surat dan pengaduan pribadi\n" .
               "• Jam kerja dan kontak resmi kantor desa");
    }
}

