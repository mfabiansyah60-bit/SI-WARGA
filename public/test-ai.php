<?php
// Test debug AI - HAPUS setelah selesai!

require_once __DIR__ . '/../config/database.php';

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;line-height:1.7'>";
echo "=== TEST DEBUG AI ===\n\n";

// 1. Cek koneksi DB
echo "1) Koneksi DB: " . ($pdo ? "✅ OK" : "❌ Gagal") . "\n\n";

// 2. Cek tabel-tabel
$tables = ['users', 'rw', 'rt', 'pengaduan', 'desa', 'surat_template', 'surat_pengajuan'];

foreach ($tables as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "   Tabel $t: ✅ $count baris\n";
    } catch (Exception $e) {
        echo "   Tabel $t: ❌ " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 3. Test query statistik (yang dipakai AI)
echo "2) TEST QUERY STATISTIK:\n";

try {
    $total_warga = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga'")->fetchColumn();
    echo "   Total Warga: $total_warga\n";
} catch (Exception $e) {
    echo "   Total Warga: ❌ " . $e->getMessage() . "\n";
}

try {
    $total_rw = $pdo->query("SELECT COUNT(*) FROM rw")->fetchColumn();
    echo "   Total RW: $total_rw\n";
} catch (Exception $e) {
    echo "   Total RW: ❌ " . $e->getMessage() . "\n";
}

try {
    $total_rt = $pdo->query("SELECT COUNT(*) FROM rt")->fetchColumn();
    echo "   Total RT: $total_rt\n";
} catch (Exception $e) {
    echo "   Total RT: ❌ " . $e->getMessage() . "\n";
}

try {
    $total_pengaduan = $pdo->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn();
    echo "   Total Pengaduan: $total_pengaduan\n";
} catch (Exception $e) {
    echo "   Total Pengaduan: ❌ " . $e->getMessage() . "\n";
}

try {
    $desa = $pdo->query("SELECT nama FROM desa LIMIT 1")->fetchColumn();
    echo "   Nama Desa: " . ($desa ?: '-') . "\n";
} catch (Exception $e) {
    echo "   Nama Desa: ❌ " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Cek function getStatistikDesa ada atau tidak
echo "3) CEK FUNCTION DI AIController:\n";
$file = __DIR__ . '/../app/Controllers/AIController.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    echo "   AIController.php: ✅ ada (" . number_format(strlen($content)) . " bytes)\n";
    echo "   getStatistikDesa: " . (strpos($content, 'function getStatistikDesa') !== false ? "✅ ada" : "❌ TIDAK ADA") . "\n";
    echo "   Handler statistik: " . (strpos($content, 'PRIORITAS 1: STATISTIK') !== false ? "✅ ada" : "❌ TIDAK ADA") . "\n";
    echo "   Filter statistik regex: " . (strpos($content, 'statistik|berapa|jumlah|total') !== false ? "✅ ada" : "❌ TIDAK ADA") . "\n";
} else {
    echo "   ❌ File AIController.php tidak ditemukan\n";
}

echo "\n";

// 5. Test ScopeFilter
echo "4) TEST SCOPE FILTER:\n";
$scopeFile = __DIR__ . '/../app/AI/ScopeFilter.php';
if (file_exists($scopeFile)) {
    require_once $scopeFile;
    
    $test_q = [
        'berapa warga yang sudah daftar',
        'statistik desa',
        'berapa RW dan RT',
        'info desa',
        'RT saya berapa',
    ];
    
    foreach ($test_q as $q) {
        $result = ScopeFilter::check($q);
        $status = $result['allowed'] ? '✅ ALLOW' : '❌ BLOCK';
        echo "   $status | '$q' | reason: " . $result['reason'] . "\n";
    }
} else {
    echo "   ❌ ScopeFilter.php tidak ditemukan\n";
}

echo "\n";

// 6. Test regex match di AI
echo "5) TEST REGEX MATCH:\n";

$tests = [
    'berapa warga yang sudah daftar'  => '/\b(statistik|berapa|jumlah|total|data)\b/', 
    'statistik desa'                   => '/\b(statistik|berapa|jumlah|total|data)\b/',
    'berapa RW dan RT'                 => '/\b(statistik|berapa|jumlah|total|data)\b/',
    'info desa'                        => '/\b(statistik|berapa|jumlah|total|data)\b/',
    'RT saya berapa'                   => '/\b(statistik|berapa|jumlah|total|data)\b/',
];

foreach ($tests as $q => $regex) {
    $match = preg_match($regex, strtolower($q)) ? '✅ MATCH' : '❌ NO MATCH';
    echo "   $match | '$q'\n";
}

echo "\n=== SELESAI ===\n";
echo "</pre>";