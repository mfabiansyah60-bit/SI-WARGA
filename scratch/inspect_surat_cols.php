<?php
require_once __DIR__ . '/../config/database.php';

$colsSurat = $pdo->query("DESCRIBE surat_pengajuan")->fetchAll(PDO::FETCH_ASSOC);
echo "=== COLUMNS surat_pengajuan ===\n";
foreach ($colsSurat as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

$colsPengaduan = $pdo->query("DESCRIBE pengaduan")->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== COLUMNS pengaduan ===\n";
foreach ($colsPengaduan as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

