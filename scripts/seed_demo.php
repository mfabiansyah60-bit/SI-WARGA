<?php
// scripts/seed_demo.php
// Jalankan: php scripts/seed_demo.php
// Fungsi: buat 5 user demo + data contoh

require_once __DIR__ . '/../config/database.php';

echo "🌱 Mulai seeding data demo...\n\n";

// ============================================
// 1. BUAT USER DEMO
// ============================================
$users = [
    ['Super Admin',  'super@admin.com',  'admin123', 'super_admin'],
    ['Admin Desa',   'desa@demo.com',    'demo123',  'desa'],
    ['Ketua RW 01',  'rw@demo.com',      'demo123',  'rw'],
    ['Ketua RT 02',  'rt@demo.com',      'demo123',  'rt'],
    ['Budi Santoso', 'warga@demo.com',   'demo123',  'warga'],
    ['Siti Aminah',  'siti@demo.com',    'demo123',  'warga'],
    ['Ahmad Fauzi',  'ahmad@demo.com',   'demo123',  'warga'],
];

foreach ($users as [$name, $email, $pwd, $role]) {
    // Cek sudah ada atau belum
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->fetch()) {
        echo "⏭️  Skip: $email (sudah ada)\n";
        continue;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $name,
        $email,
        password_hash($pwd, PASSWORD_DEFAULT),
        $role
    ]);
    echo "✅ User dibuat: $email ($role)\n";
}

// ============================================
// 2. BUAT CONTOH PENGADUAN (opsional)
// ============================================
try {
    $warga = $pdo->query("SELECT id FROM users WHERE email = 'warga@demo.com'")->fetch();
    if ($warga) {
        $pengaduan = [
            ['Lampu Jalan Mati di RW 03', 'Lampu jalan di depan gang masuk perumahan sudah mati 3 hari. Mohon segera diperbaiki karena jalan jadi gelap.', 'infrastruktur'],
            ['Sampah Menumpuk di Pasar', 'Sampah di area pasar desa sudah menumpuk sejak seminggu. Bau tidak sedap mengganggu warga sekitar.', 'lingkungan'],
            ['Fasilitas Posyandu Kurang', 'Posyandu di RT 04 kekurangan alat timbangan bayi. Mohon diadakan pengadaan.', 'kesehatan'],
        ];
        
        foreach ($pengaduan as [$judul, $isi, $kategori]) {
            $check = $pdo->prepare("SELECT id FROM pengaduan WHERE judul = ?");
            $check->execute([$judul]);
            if ($check->fetch()) continue;
            
            $stmt = $pdo->prepare("
                INSERT INTO pengaduan (user_id, judul, isi, kategori, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$warga['id'], $judul, $isi, $kategori]);
            echo "✅ Pengaduan dibuat: $judul\n";
        }
    }
} catch (Exception $e) {
    echo "⚠️  Skip pengaduan: " . $e->getMessage() . "\n";
}

echo "\n🎉 Seeding selesai!\n\n";
echo "📋 Akun demo yang bisa dipakai login:\n";
echo "   ┌──────────────────────────────────────────────────┐\n";
echo "   │ Role         │ Email              │ Password     │\n";
echo "   ├──────────────────────────────────────────────────┤\n";
echo "   │ Super Admin  │ super@admin.com    │ admin123     │\n";
echo "   │ Admin Desa   │ desa@demo.com      │ demo123      │\n";
echo "   │ Ketua RW     │ rw@demo.com        │ demo123      │\n";
echo "   │ Ketua RT     │ rt@demo.com        │ demo123      │\n";
echo "   │ Warga        │ warga@demo.com     │ demo123      │\n";
echo "   └──────────────────────────────────────────────────┘\n";