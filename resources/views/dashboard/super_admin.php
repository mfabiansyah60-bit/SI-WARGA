<?php
// resources/views/dashboard/super_admin.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($pdo)) {
    require_once __DIR__ . '/../../../config/database.php';
}

$user = $_SESSION['user'] ?? [];
$userName = $user['name'] ?? 'Super Admin';
$page_title = 'Dashboard Super Admin';

// ==================== STATISTIK ====================
$stats = [
    'warga'     => 0,
    'rw'        => 0,
    'rt'        => 0,
    'pengaduan' => 0,
];

try {
    $stats['warga']     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga'")->fetchColumn();
    $stats['rw']        = (int)$pdo->query("SELECT COUNT(*) FROM rw")->fetchColumn();
    $stats['rt']        = (int)$pdo->query("SELECT COUNT(*) FROM rt")->fetchColumn();
    $stats['pengaduan'] = (int)$pdo->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn();
} catch (Exception $e) {
    // silent
}

include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- ==================== HERO HEADER ==================== -->
<div class="sa-hero mb-6">
    <div class="sa-hero-bg"></div>
    <div class="sa-hero-content">
        <div class="sa-hero-left">
            <div class="sa-hero-badge">
                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;">shield_person</span>
                SUPER ADMIN
            </div>
            <h1 class="sa-hero-title">
                Selamat Datang, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! <span class="wave">👋</span>
            </h1>
            <p class="sa-hero-sub">
                Kelola seluruh sistem SIWARGA — semua desa, RT/RW, user, warga, pelayanan, pengaduan, template surat, dan laporan.
            </p>
            <div class="sa-hero-actions">
                <a href="/users" class="hero-btn hero-btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px;">manage_accounts</span>
                    Kelola User
                </a>
                <a href="/laporan" class="hero-btn hero-btn-ghost">
                    <span class="material-symbols-outlined" style="font-size:16px;">bar_chart</span>
                    Lihat Laporan
                </a>
            </div>
        </div>
        <div class="sa-hero-icon">
            <span class="material-symbols-outlined" style="font-size:56px;font-variation-settings:'FILL' 1;">shield_person</span>
        </div>
    </div>
</div>

<!-- ==================== STATS ==================== -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">

    <a href="/warga" class="stat-card stat-blue">
        <div class="stat-icon">
            <span class="material-symbols-outlined">groups</span>
        </div>
        <div class="stat-value"><?= $stats['warga'] ?></div>
        <div class="stat-label">Warga Terdaftar</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/rt_rw" class="stat-card stat-emerald">
        <div class="stat-icon">
            <span class="material-symbols-outlined">holiday_village</span>
        </div>
        <div class="stat-value"><?= $stats['rw'] ?></div>
        <div class="stat-label">Total RW</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/rt_rw" class="stat-card stat-violet">
        <div class="stat-icon">
            <span class="material-symbols-outlined">home_work</span>
        </div>
        <div class="stat-value"><?= $stats['rt'] ?></div>
        <div class="stat-label">Total RT</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/pengaduan" class="stat-card stat-rose">
        <div class="stat-icon">
            <span class="material-symbols-outlined">campaign</span>
        </div>
        <div class="stat-value"><?= $stats['pengaduan'] ?></div>
        <div class="stat-label">Pengaduan Masuk</div>
        <div class="stat-deco"></div>
    </a>

</div>

<!-- ==================== MODUL CEPAT ==================== -->
<div style="margin-bottom:24px;">
    <h3 class="text-base font-extrabold mb-4 flex items-center gap-2" style="color:var(--text-primary);">
        <span class="material-symbols-outlined" style="color:#f59e0b;font-variation-settings:'FILL' 1, 'wght' 500;">bolt</span>
        Modul Cepat
    </h3>

    <?php
    $glass_items = [
        ['icon' => 'map',             'color' => 'blue',   'label' => 'Wilayah Desa',    'href' => '/wilayah'],
        ['icon' => 'holiday_village', 'color' => 'purple', 'label' => 'Manajemen RT/RW', 'href' => '/rt_rw'],
        ['icon' => 'manage_accounts', 'color' => 'red',    'label' => 'Manajemen User',  'href' => '/users'],
        ['icon' => 'groups',          'color' => 'green',  'label' => 'Data Warga',      'href' => '/warga'],
        ['icon' => 'handyman',        'color' => 'orange', 'label' => 'Pelayanan',       'href' => '/pelayanan'],
        ['icon' => 'bar_chart',       'color' => 'indigo', 'label' => 'Laporan',         'href' => '/laporan'],
    ];
    include __DIR__ . '/../components/glass-icons.php';
    ?>
</div>

<!-- ==================== INFO CARD ==================== -->
<div class="info-card">
    <div class="info-icon">
        <span class="material-symbols-outlined" style="font-size:22px;">auto_awesome</span>
    </div>
    <div style="flex:1;">
        <h3 class="text-sm font-extrabold mb-1" style="color:var(--text-primary);">Panel Kontrol Penuh</h3>
        <p class="text-xs leading-relaxed" style="color:var(--text-muted);">
            Anda memiliki akses penuh ke seluruh modul SIWARGA. Kelola data wilayah, verifikasi pengguna, atur template surat, dan pantau semua aktivitas desa dari satu dashboard.
        </p>
    </div>
</div>

<!-- ==================== CUSTOM STYLES ==================== -->
<style>
    /* ===== HERO ===== */
    .sa-hero {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background: linear-gradient(135deg, #dc2626 0%, #e11d48 40%, #be185d 100%);
        box-shadow: 0 20px 40px -15px rgba(220,38,38,.45), 0 0 0 1px rgba(255,255,255,.1) inset;
    }
    .sa-hero-bg {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 15% 20%, rgba(255,255,255,.2) 0%, transparent 40%),
            radial-gradient(circle at 85% 80%, rgba(190,24,93,.35) 0%, transparent 50%),
            radial-gradient(circle at 50% 50%, rgba(225,29,72,.2) 0%, transparent 60%);
        pointer-events: none;
    }
    .sa-hero::after {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%);
        pointer-events: none;
    }
    .sa-hero-content {
        position: relative;
        padding: 32px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .sa-hero-left { flex: 1; min-width: 240px; }
    .sa-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: rgba(255,255,255,.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.35);
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 800;
        color: white;
        letter-spacing: .5px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }
    .sa-hero-title {
        font-size: 28px;
        font-weight: 800;
        color: white;
        letter-spacing: -.5px;
        margin: 0 0 8px;
        line-height: 1.15;
        text-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .sa-hero-title .wave {
        display: inline-block;
        animation: wave-anim 2s ease-in-out infinite;
        transform-origin: 70% 70%;
    }
    @keyframes wave-anim {
        0%, 100% { transform: rotate(0deg); }
        15% { transform: rotate(14deg); }
        30% { transform: rotate(-8deg); }
        40% { transform: rotate(14deg); }
        50% { transform: rotate(-4deg); }
        60% { transform: rotate(10deg); }
    }
    .sa-hero-sub {
        font-size: 14px;
        color: rgba(255,255,255,.9);
        max-width: 520px;
        margin: 0 0 20px;
        line-height: 1.55;
    }
    .sa-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .hero-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s cubic-bezier(.2,1.25,.4,1);
        white-space: nowrap;
    }
    .hero-btn-primary {
        background: white;
        color: #dc2626;
        box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .hero-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(0,0,0,.2);
    }
    .hero-btn-ghost {
        background: rgba(255,255,255,.15);
        color: white;
        border: 1px solid rgba(255,255,255,.35);
        backdrop-filter: blur(10px);
    }
    .hero-btn-ghost:hover {
        background: rgba(255,255,255,.25);
        transform: translateY(-2px);
    }
    .sa-hero-icon {
        width: 96px;
        height: 96px;
        border-radius: 24px;
        background: rgba(255,255,255,.15);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 10px 30px rgba(0,0,0,.15), inset 0 1px 0 rgba(255,255,255,.3);
    }
    @media (max-width: 640px) {
        .sa-hero-content { padding: 24px 20px; }
        .sa-hero-title { font-size: 22px; }
        .sa-hero-icon { display: none; }
    }

    /* ===== STAT CARDS ===== */
    .stat-card {
        position: relative;
        border-radius: 20px;
        padding: 18px;
        text-decoration: none;
        overflow: hidden;
        transition: all .25s cubic-bezier(.2,1.25,.4,1);
        border: 1px solid var(--border);
        background: var(--bg-card);
        cursor: pointer;
        display: block;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 30px -12px rgba(15,23,42,.2);
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        position: relative;
        z-index: 2;
    }
    .stat-icon .material-symbols-outlined { font-size: 24px; }
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
        letter-spacing: -.5px;
        position: relative;
        z-index: 2;
    }
    .stat-label {
        font-size: 11px;
        font-weight: 700;
        margin-top: 6px;
        color: var(--text-muted);
        letter-spacing: .3px;
        text-transform: uppercase;
        position: relative;
        z-index: 2;
    }
    .stat-deco {
        position: absolute;
        bottom: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        opacity: .15;
        transition: all .3s;
    }
    .stat-card:hover .stat-deco {
        transform: scale(1.3);
        opacity: .25;
    }

    .stat-blue    { border-top: 3px solid #3b82f6; }
    .stat-blue    .stat-icon { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
    .stat-blue    .stat-deco { background: #3b82f6; }

    .stat-rose    { border-top: 3px solid #f43f5e; }
    .stat-rose    .stat-icon { background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; }
    .stat-rose    .stat-deco { background: #f43f5e; }

    .stat-emerald { border-top: 3px solid #10b981; }
    .stat-emerald .stat-icon { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .stat-emerald .stat-deco { background: #10b981; }

    .stat-violet  { border-top: 3px solid #8b5cf6; }
    .stat-violet  .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .stat-violet  .stat-deco { background: #8b5cf6; }

    /* ===== INFO CARD ===== */
    .info-card {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(239,68,68,.08), rgba(190,24,93,.08));
        border: 1px solid rgba(239,68,68,.25);
        position: relative;
        overflow: hidden;
    }
    .info-card::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #ef4444, #be185d);
    }
    .info-icon {
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 16px -4px rgba(239,68,68,.4);
    }

    /* DARK MODE */
    [data-theme="dark"] .stat-card {
        background: var(--bg-card);
    }
    [data-theme="dark"] .stat-card:hover {
        box-shadow: 0 16px 30px -12px rgba(0,0,0,.6);
    }
    [data-theme="dark"] .info-card {
        background: linear-gradient(135deg, rgba(239,68,68,.12), rgba(190,24,93,.12));
        border-color: rgba(239,68,68,.3);
    }
</style>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>