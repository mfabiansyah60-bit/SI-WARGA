<?php
require_once __DIR__ . '/../config/database.php';

$surat = $pdo->query("SELECT id, user_id, judul, nomor_surat, status, created_at FROM surat_pengajuan ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "=== SURAT PENGAJUAN SAMPLE ===\n";
print_r($surat);

$pengaduan = $pdo->query("SELECT id, user_id, judul, kategori, status, created_at FROM pengaduan ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "=== PENGADUAN SAMPLE ===\n";
print_r($pengaduan);

