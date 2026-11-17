<?php
// app/AI/DatabaseInspector.php
// Khusus Super Admin — cek struktur database

class DatabaseInspector
{
    // Whitelist tabel yang boleh dilihat (keamanan)
    private static $allowed_tables = [
        'users', 'rw', 'rt', 'desa', 'pengaduan',
        'surat_template', 'surat_pengajuan', 'surat_terbit',
        'pelayanan', 'ai_chat', 'log_aktivitas',
    ];

    // ============================================
    // LIST SEMUA TABEL
    // ============================================
    public static function listTables($pdo, $dbname = 'siwarga')
    {
        try {
            $stmt = $pdo->prepare("
                SELECT TABLE_NAME AS nama, TABLE_ROWS AS baris, 
                       ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024), 2) AS ukuran_kb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ORDER BY TABLE_NAME ASC
            ");
            $stmt->execute([$dbname]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $output = "🗄️ **Struktur Database `$dbname`**\n\n";
            $output .= "Total: **" . count($tables) . " tabel**\n\n";
            $output .= "| # | Nama Tabel | Baris | Ukuran |\n";
            $output .= "|---|---|---|---|\n";

            foreach ($tables as $i => $t) {
                $baris = $t['baris'] !== null ? number_format($t['baris']) : '?';
                $output .= "| " . ($i + 1) . " | `{$t['nama']}` | $baris | {$t['ukuran_kb']} KB |\n";
            }

            $output .= "\n💡 Ketik *\"kolom tabel users\"* untuk lihat struktur kolomnya.";
            return $output;

        } catch (Exception $e) {
            log_error('DatabaseInspector listTables', ['error' => $e->getMessage()]);
            return "❌ Gagal ambil daftar tabel: " . $e->getMessage();
        }
    }

    // ============================================
    // DESCRIBE / LIHAT KOLOM TABEL
    // ============================================
    public static function describeTable($pdo, $table)
    {
        // Keamanan: whitelist
        if (!in_array($table, self::$allowed_tables)) {
            return "❌ Tabel `$table` tidak ditemukan atau tidak diizinkan.";
        }

        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($cols)) {
                return "❌ Tabel `$table` tidak ditemukan.";
            }

            $output = "🗄️ **Struktur Tabel `$table`**\n\n";
            $output .= "Total: **" . count($cols) . " kolom**\n\n";
            $output .= "| # | Kolom | Tipe | Null | Key |\n";
            $output .= "|---|---|---|---|---|\n";

            foreach ($cols as $i => $c) {
                $key = $c['Key'] ?? '-';
                $null = $c['Null'] === 'YES' ? '✓' : '✗';
                $output .= "| " . ($i + 1) . " | `{$c['Field']}` | {$c['Type']} | $null | $key |\n";
            }

            // Cek jumlah baris
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $output .= "\n📊 Total baris: **" . number_format($count) . "**";

            return $output;

        } catch (Exception $e) {
            return "❌ Gagal describe tabel: " . $e->getMessage();
        }
    }

    // ============================================
    // STATISTIK SISTEM (SUPER ADMIN)
    // ============================================
    public static function systemStats($pdo)
    {
        try {
            $stats = [];

            // User per role
            $stmt = $pdo->query("
                SELECT role, COUNT(*) AS jumlah 
                FROM users 
                GROUP BY role 
                ORDER BY jumlah DESC
            ");
            $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Total per tabel
            $tables = ['users', 'rw', 'rt', 'pengaduan', 'surat_template', 'surat_pengajuan', 'ai_chat', 'pelayanan'];
            $stats['table_counts'] = [];
            foreach ($tables as $t) {
                try {
                    $stats['table_counts'][$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                } catch (Exception $e) {
                    $stats['table_counts'][$t] = 0;
                }
            }

            // Output
            $output = "📊 **Statistik Sistem SIWARGA**\n\n";

            $output .= "**👥 User per Role:**\n";
            foreach ($stats['users_by_role'] as $r) {
                $output .= "• {$r['role']}: **{$r['jumlah']}**\n";
            }

            $output .= "\n**📦 Data per Tabel:**\n";
            foreach ($stats['table_counts'] as $t => $c) {
                $output .= "• `$t`: **" . number_format($c) . "** baris\n";
            }

            return $output;

        } catch (Exception $e) {
            return "❌ Gagal ambil statistik sistem: " . $e->getMessage();
        }
    }
}