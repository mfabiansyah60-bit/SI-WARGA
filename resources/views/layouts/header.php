<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = $page_title ?? 'SIWARGA';
$page_icon = $page_icon ?? 'fa-tachometer-alt';
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'warga';

$role_names = [
    'super_admin' => 'Super Admin',
    'desa'        => 'Admin Desa',
    'rw'          => 'Ketua RW',
    'rt'          => 'Ketua RT',
    'warga'       => 'Warga',
];
$role_display = $role_names[$role] ?? 'Warga';

$avatar_colors = [
    'super_admin' => 'linear-gradient(135deg, #1a56db, #60a5fa)',
    'desa'        => 'linear-gradient(135deg, #1a56db, #60a5fa)',
    'rw'          => 'linear-gradient(135deg, #8b5cf6, #a78bfa)',
    'rt'          => 'linear-gradient(135deg, #f59e0b, #fbbf24)',
    'warga'       => 'linear-gradient(135deg, #10b981, #34d399)',
];
$avatar_bg = $avatar_colors[$role] ?? $avatar_colors['warga'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?> - SIWARGA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fc; display: flex; min-height: 100vh; }

        .sidebar { width: 260px; background: linear-gradient(180deg, #0f172a, #1e293b); color: white; padding: 25px 18px; position: fixed; top:0; left:0; bottom:0; overflow-y:auto; z-index: 100; }
        .sidebar .logo { display: flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 800; padding-bottom: 20px; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar .logo span { color: #10b981; }
        .sidebar .menu-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; color: #64748b; margin: 18px 0 10px 10px; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li { margin: 3px 0; }
        .sidebar ul li a { display: flex; align-items: center; gap: 14px; padding: 12px 16px; color: #cbd5e1; text-decoration: none; border-radius: 10px; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .sidebar ul li a:hover { background: rgba(96,165,250,0.12); color: white; }
        .sidebar ul li.active a { background: rgba(96,165,250,0.15); color: white; border-left: 3px solid #60a5fa; }

        .main { margin-left: 260px; padding: 25px 30px; width: calc(100% - 260px); min-height: 100vh; }

        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 18px 26px; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin-bottom: 26px; flex-wrap: wrap; gap: 12px; }
        .header h1 { font-size: 20px; color: #0f172a; }
        .header h1 i { color: #1a56db; margin-right: 8px; }
        .user-info { display: flex; align-items: center; gap: 14px; }
        .user-info .name { font-weight: 700; font-size: 14px; color: #0f172a; text-align: right; line-height: 1.3; }
        .user-info .role { font-size: 12px; color: #64748b; text-align: right; }
        .user-info .avatar { width: 42px; height: 42px; border-radius: 50%; background: <?= $avatar_bg ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px; }
        .btn-logout { background: #ef4444; color: white; padding: 9px 18px; border-radius: 30px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-logout:hover { background: #dc2626; }

        .alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #059669; }
        .alert-error { background: #fee2e2; color: #dc2626; }

        .card { background: white; border-radius: 14px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin-bottom: 20px; }
        .card-flat { background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.04); margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 16px 20px; background: #f8fafc; color: #475569; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        table tr:hover { background: #f8fafc; }

        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .toolbar h2 { font-size: 18px; color: #0f172a; }
        .btn-add { background: #1a56db; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add:hover { background: #1e40af; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; }
        .badge-super_admin { background: #ef4444; }
        .badge-desa { background: #1a56db; }
        .badge-rw { background: #8b5cf6; }
        .badge-rt { background: #f59e0b; }
        .badge-warga { background: #10b981; }

        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; }
        .btn-primary { background: #1a56db; color: white; }
        .btn-primary:hover { background: #1e40af; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-action { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; margin: 0 3px; }
        .btn-edit { background: #fef3c7; color: #d97706; }
        .btn-edit:hover { background: #f59e0b; color: white; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #ef4444; color: white; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #334155; font-size: 14px; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1a56db; background: white; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-actions { display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; }

        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px 22px; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px; }
        .stat-card .icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; flex-shrink: 0; }
        .stat-card .icon.blue { background: linear-gradient(135deg, #1a56db, #3b82f6); }
        .stat-card .icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card .icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card .icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .stat-card .info .number { font-size: 26px; font-weight: 700; color: #0f172a; }
        .stat-card .info .label { font-size: 13px; color: #64748b; margin-top: 4px; }

        .empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty i { font-size: 60px; margin-bottom: 15px; display: block; color: #cbd5e1; }
        .empty h3 { color: #475569; margin-bottom: 8px; }

        .footer { text-align: center; font-size: 13px; color: #94a3b8; padding: 24px 0 10px; margin-top: 20px; border-top: 1px solid #e2e8f0; }

        .tabs { display: flex; gap: 8px; margin-bottom: 20px; background: white; padding: 6px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); width: fit-content; }
        .tab { padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; color: #64748b; transition: all 0.2s; }
        .tab:hover { background: #f1f5f9; color: #1a56db; }
        .tab.active { background: #1a56db; color: white; }

        @media (max-width: 1024px) { .stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; width: 100%; padding: 15px; }
            .stats { grid-template-columns: 1fr; }
            .user-info .name, .user-info .role { display: none; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
    <div class="header">
        <h1><i class="fas <?= $page_icon ?>"></i> <?= htmlspecialchars($page_title) ?></h1>
        <div class="user-info">
            <div>
                <div class="name"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                <div class="role"><i class="fas fa-user"></i> <?= $role_display ?></div>
            </div>
            <div class="avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
            <a href="/logout" class="btn-logout">Logout</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>