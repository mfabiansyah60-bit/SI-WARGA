<?php
// scripts/backup_db.php
// Jalankan: php scripts/backup_db.php
// Fungsi: backup database ke file .sql

$host = '127.0.0.1';
$db   = 'siwarga';
$user = 'root';
$pass = '';

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$folder   = __DIR__ . '/../storage/backups/';

if (!is_dir($folder)) {
    mkdir($folder, 0755, true);
}

$filepath = $folder . $filename;

// Cari mysqldump di Laragon
$mysqldumpPaths = [
    'mysqldump',
    'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe',
    'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
];

$mysqldump = 'mysqldump';
foreach ($mysqldumpPaths as $path) {
    if ($path === 'mysqldump' || file_exists($path)) {
        $mysqldump = $path;
        break;
    }
}

$passwordPart = $pass ? "-p{$pass}" : "";
$command = "\"{$mysqldump}\" -h {$host} -u {$user} {$passwordPart} {$db} > \"{$filepath}\" 2>&1";

exec($command, $output, $returnCode);

if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
    $size = round(filesize($filepath) / 1024, 2);
    echo "✅ Backup berhasil!\n";
    echo "📁 File    : {$filename}\n";
    echo "📊 Ukuran  : {$size} KB\n";
    echo "📍 Lokasi  : {$filepath}\n";
} else {
    echo "❌ Backup gagal!\n";
    echo "Exit code: {$returnCode}\n";
    if (!empty($output)) {
        echo "Output: " . implode("\n", $output) . "\n";
    }
}

// ============================================
// Hapus backup lama (lebih dari 30 hari)
// ============================================
$files = glob($folder . 'backup_*.sql');
$deleted = 0;
foreach ($files as $file) {
    if (time() - filemtime($file) > 30 * 86400) {
        unlink($file);
        $deleted++;
    }
}
if ($deleted > 0) {
    echo "🗑️  Hapus {$deleted} backup lama (>30 hari)\n";
}