<?php
// home.php - TANPA SESSION CHECK (langsung tampil)

$user = [
    'name' => 'Bapak Suparman',
    'role' => 'kades',
    'role_name' => 'Kepala Desa',
    'region' => 'Desa Sukamaju'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - SIWARGA DESA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f0f4f8; display: flex; }
        .sidebar {
            width: 220px;
            min-height: 100vh;
            background: #0f172a;
            color: white;
            padding: 20px;
        }
        .sidebar .logo { color: #60a5fa; font-size: 22px; font-weight: bold; }
        .sidebar .menu {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        .sidebar .menu li {
            padding: 12px 16px;
            border-radius: 10px;
            margin: 4px 0;
            cursor: pointer;
            color: #94a3b8;
        }
        .sidebar .menu li.active {
            background: rgba(96,165,250,0.15);
            color: white;
            border-left: 3px solid #60a5fa;
        }
        .main-content {
            margin-left: 220px;
            padding: 30px;
            width: 100%;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-logout {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-logout:hover { background: #dc2626; }
        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        .card-desa {
            background: linear-gradient(135deg, #1a56db, #1e40af);
            color: white;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #1a56db; }
        .stat-card .label { color: #6b7280; }
        .badge { background: #10b981; color: white; padding: 3px 12px; border-radius: 20px; font-size: 12px; }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1a56db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .success-box {
            background: #d1fae5;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 20px;
            margin-top: 10px;
        }
        .success-box h3 { color: #059669; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🏛️ SIWARGA</div>
    <ul class="menu">
        <li class="active">📊 Dashboard</li>
        <li>📋 Layanan</li>
        <li>⚠️ Pengaduan</li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <h2>📊 Dashboard</h2>
        <div class="user-info">
            <div>
                <div><?= htmlspecialchars($user['name']) ?></div>
                <span class="badge"><?= htmlspecialchars($user['role_name']) ?></span>
            </div>
            <div class="avatar"><?= substr($user['name'], 0, 1) ?></div>
            <a href="/login" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="card card-desa">
        <h2>📍 <?= htmlspecialchars($user['region']) ?></h2>
        <p>Selamat datang, <?= htmlspecialchars($user['name']) ?>!</p>
    </div>

    <div class="stats">
        <div class="stat-card"><div class="number">1.234</div><div class="label">Total Warga</div></div>
        <div class="stat-card"><div class="number">3</div><div class="label">RW</div></div>
        <div class="stat-card"><div class="number">8</div><div class="label">RT</div></div>
        <div class="stat-card"><div class="number">15</div><div class="label">Pengaduan</div></div>
    </div>

    <div class="card success-box">
        <h3>✅ SELAMAT BOS!</h3>
        <p style="color:#374151;">Kamu berhasil login sebagai <strong><?= htmlspecialchars($user['name']) ?></strong></p>
        <p style="color:#374151;">Role: <strong><?= htmlspecialchars($user['role_name']) ?></strong></p>
        <p style="color:#374151;">Wilayah: <strong><?= htmlspecialchars($user['region']) ?></strong></p>
        <p style="color:#6b7280;margin-top:10px;font-size:13px;">
            📌 Sekarang akses langsung: <strong>http://localhost:8000/home.php</strong>
        </p>
    </div>
</div>

</body>
</html>