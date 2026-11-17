<?php
// resources/views/dashboard/desa.php
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? [];
$userName = $user['name'] ?? 'Admin Desa';

// ============================================================
// AMBIL DATA DARI DATABASE
// ============================================================
try {
    $total_warga = $pdo->query("SELECT COUNT(*) FROM users WHERE role='warga'")->fetchColumn();
    $total_rw = $pdo->query("SELECT COUNT(*) FROM rw")->fetchColumn();
    $total_rt = $pdo->query("SELECT COUNT(*) FROM rt")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $total_pengaduan   = $pdo->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn();
    $pengaduan_pending = $pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status='pending'")->fetchColumn();
    $pengaduan_proses  = $pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status='proses'")->fetchColumn();
    $pengaduan_selesai = $pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status='selesai'")->fetchColumn();

    try { $total_pelayanan = $pdo->query("SELECT COUNT(*) FROM pelayanan")->fetchColumn(); }
    catch (Exception $e) { $total_pelayanan = 0; }

    try { $total_template = $pdo->query("SELECT COUNT(*) FROM surat_template")->fetchColumn(); }
    catch (Exception $e) { $total_template = 0; }

    try {
        $ringkasan_rw = $pdo->query("
            SELECT rw.nomor, rw.ketua, COUNT(u.id) AS jumlah_warga
            FROM rw
            LEFT JOIN users u ON u.rw = rw.nomor AND u.role = 'warga'
            GROUP BY rw.nomor, rw.ketua
            ORDER BY rw.nomor
        ")->fetchAll();
    } catch (Exception $e) { $ringkasan_rw = []; }

    $pengaduan_terbaru = $pdo->query("
        SELECT p.id, p.judul, p.status, p.created_at, p.kategori,
               u.name AS user_name, u.rt, u.rw
        FROM pengaduan p
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC LIMIT 5
    ")->fetchAll();

} catch (Exception $e) {
    $total_warga = $total_rw = $total_rt = $total_users = 0;
    $total_pengaduan = $pengaduan_pending = $pengaduan_proses = $pengaduan_selesai = 0;
    $total_pelayanan = $total_template = 0;
    $ringkasan_rw = $pengaduan_terbaru = [];
    log_error('Dashboard desa error', ['error' => $e->getMessage()]);
}

$page_title = 'Dashboard Admin Desa';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- ==================== HERO HEADER ==================== -->
<div class="desa-hero mb-6">
    <div class="desa-hero-bg"></div>
    <div class="desa-hero-content">
        <div class="desa-hero-left">
            <div class="desa-hero-badge">
                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;">account_balance</span>
                ADMIN DESA
            </div>
            <h1 class="desa-hero-title">
                Selamat Datang, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! <span class="wave">👋</span>
            </h1>
            <p class="desa-hero-sub">
                Kelola pelayanan, data warga, pengaduan, dan template surat di <strong>Desa Sukamaju</strong>.
            </p>
            <div class="desa-hero-actions">
                <a href="/pelayanan" class="hero-btn hero-btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px;">handyman</span>
                    Kelola Pelayanan
                </a>
                <a href="/laporan" class="hero-btn hero-btn-ghost">
                    <span class="material-symbols-outlined" style="font-size:16px;">bar_chart</span>
                    Lihat Laporan
                </a>
            </div>
        </div>
        <div class="desa-hero-icon">
            <span class="material-symbols-outlined" style="font-size:56px;font-variation-settings:'FILL' 1;">account_balance</span>
        </div>
    </div>
</div>

<!-- ==================== STATS ==================== -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">

    <a href="/warga" class="stat-card stat-blue">
        <div class="stat-icon">
            <span class="material-symbols-outlined">groups</span>
        </div>
        <div class="stat-value"><?= number_format($total_warga, 0, ',', '.') ?></div>
        <div class="stat-label">Total Warga Desa</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/rt_rw" class="stat-card stat-emerald">
        <div class="stat-icon">
            <span class="material-symbols-outlined">apartment</span>
        </div>
        <div class="stat-value"><?= number_format($total_rw, 0, ',', '.') ?></div>
        <div class="stat-label">Total RW</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/rt_rw" class="stat-card stat-violet">
        <div class="stat-icon">
            <span class="material-symbols-outlined">home_work</span>
        </div>
        <div class="stat-value"><?= number_format($total_rt, 0, ',', '.') ?></div>
        <div class="stat-label">Total RT</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/pengaduan" class="stat-card stat-amber">
        <div class="stat-icon">
            <span class="material-symbols-outlined">campaign</span>
        </div>
        <div class="stat-value"><?= number_format($total_pengaduan, 0, ',', '.') ?></div>
        <div class="stat-label">Pengaduan Masuk</div>
        <div class="stat-deco"></div>
    </a>

</div>

<!-- ==================== STATUS PENGADUAN ==================== -->
<div class="section-card mb-6">
    <h3 class="section-heading">
        <span class="material-symbols-outlined" style="color:#0d9488;">donut_small</span>
        Status Pengaduan
    </h3>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="status-box status-box-pending">
            <div class="status-box-value"><?= $pengaduan_pending ?></div>
            <div class="status-box-label">Pending</div>
        </div>
        <div class="status-box status-box-proses">
            <div class="status-box-value"><?= $pengaduan_proses ?></div>
            <div class="status-box-label">Proses</div>
        </div>
        <div class="status-box status-box-selesai">
            <div class="status-box-value"><?= $pengaduan_selesai ?></div>
            <div class="status-box-label">Selesai</div>
        </div>
        <div class="status-box status-box-total">
            <div class="status-box-value"><?= $total_pengaduan ?></div>
            <div class="status-box-label">Total</div>
        </div>
    </div>
</div>

<!-- ==================== RINGKASAN PER RW ==================== -->
<div class="section-card mb-6">
    <div class="section-head">
        <h3 class="section-heading" style="margin-bottom:0;">
            <span class="material-symbols-outlined" style="color:#0d9488;">pie_chart</span>
            Ringkasan per RW
        </h3>
        <a href="/rt_rw" class="link-more">
            Kelola RT/RW
            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
    </div>

    <?php if (empty($ringkasan_rw)): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined empty-icon">home_work</span>
            <p class="empty-title">Belum ada data RW</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($ringkasan_rw as $r): ?>
                <div class="rw-box">
                    <div class="rw-box-head">
                        <div class="rw-box-title">RW <?= htmlspecialchars($r['nomor']) ?></div>
                        <span class="rw-box-count"><?= $r['jumlah_warga'] ?> warga</span>
                    </div>
                    <div class="rw-box-ketua">
                        <span class="material-symbols-outlined text-[14px]">person</span>
                        <?= htmlspecialchars($r['ketua'] ?? '-') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ==================== PENGADUAN TERBARU ==================== -->
<div class="section-card mb-6">
    <div class="section-head">
        <h3 class="section-heading" style="margin-bottom:0;">
            <span class="material-symbols-outlined" style="color:#0d9488;">campaign</span>
            Pengaduan Terbaru
        </h3>
        <a href="/pengaduan" class="link-more">
            Lihat Semua
            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
    </div>

    <?php if (empty($pengaduan_terbaru)): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined empty-icon">inbox</span>
            <p class="empty-title">Belum ada pengaduan masuk</p>
        </div>
    <?php else: ?>
        <div class="list-stack">
            <?php
            $statusMap = [
                'pending' => ['label' => 'PENDING',  'bg' => 'rgba(245,158,11,.12)', 'color' => '#d97706'],
                'proses'  => ['label' => 'PROSES',   'bg' => 'rgba(59,130,246,.12)', 'color' => '#2563eb'],
                'selesai' => ['label' => 'SELESAI',  'bg' => 'rgba(16,185,129,.12)', 'color' => '#059669'],
                'ditolak' => ['label' => 'DITOLAK',  'bg' => 'rgba(239,68,68,.12)',  'color' => '#dc2626'],
            ];
            foreach ($pengaduan_terbaru as $p):
                $st = $statusMap[$p['status'] ?? 'pending'] ?? $statusMap['pending'];
                $inisial = strtoupper(substr($p['user_name'] ?? 'U', 0, 1));
            ?>
                <a href="/pengaduan?action=detail&id=<?= $p['id'] ?>" class="list-row list-row-link">
                    <div class="avatar" style="background:linear-gradient(135deg, #0d9488, #0f766e);">
                        <?= htmlspecialchars($inisial) ?>
                    </div>
                    <div class="list-body">
                        <div class="list-title"><?= htmlspecialchars($p['judul']) ?></div>
                        <div class="list-sub">
                            <?= htmlspecialchars($p['user_name'] ?? 'User') ?>
                            · RT <?= htmlspecialchars($p['rt'] ?? '-') ?>
                            · <?= !empty($p['created_at']) ? waktu_lalu($p['created_at']) : '-' ?>
                        </div>
                    </div>
                    <span class="status-pill" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;">
                        <?= $st['label'] ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ==================== MODUL CEPAT ==================== -->
<div style="margin-bottom:24px;">
    <h3 class="text-base font-extrabold mb-4 flex items-center gap-2" style="color:var(--text-primary);">
        <span class="material-symbols-outlined" style="color:#f59e0b;font-variation-settings:'FILL' 1, 'wght' 500;">bolt</span>
        Modul Cepat
    </h3>

    <?php
    $glass_items = [
        ['icon' => 'groups',          'color' => 'blue',   'label' => 'Data Warga',      'href' => '/warga'],
        ['icon' => 'holiday_village', 'color' => 'purple', 'label' => 'Kelola RT/RW',    'href' => '/rt_rw'],
        ['icon' => 'handyman',        'color' => 'green',  'label' => 'Pelayanan',       'href' => '/pelayanan'],
        ['icon' => 'campaign',        'color' => 'orange', 'label' => 'Pengaduan',       'href' => '/pengaduan'],
        ['icon' => 'description',     'color' => 'teal',   'label' => 'Template Surat',  'href' => '/surat'],
        ['icon' => 'bar_chart',       'color' => 'graphite', 'label' => 'Laporan Desa',  'href' => '/laporan'],
    ];
    include __DIR__ . '/../components/glass-icons.php';
    ?>
</div>

<!-- ==================== CUSTOM STYLES ==================== -->
<style>
    /* ===== HERO ===== */
    .desa-hero {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background: linear-gradient(135deg, #0d9488 0%, #0ea5e9 40%, #0284c7 100%);
        box-shadow: 0 20px 40px -15px rgba(13,148,136,.45), 0 0 0 1px rgba(255,255,255,.1) inset;
    }
    .desa-hero-bg {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 15% 20%, rgba(255,255,255,.2) 0%, transparent 40%),
            radial-gradient(circle at 85% 80%, rgba(2,132,199,.35) 0%, transparent 50%),
            radial-gradient(circle at 50% 50%, rgba(14,165,233,.2) 0%, transparent 60%);
        pointer-events: none;
    }
    .desa-hero::after {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%);
        pointer-events: none;
    }
    .desa-hero-content {
        position: relative;
        padding: 32px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .desa-hero-left { flex: 1; min-width: 240px; }
    .desa-hero-badge {
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
    .desa-hero-title {
        font-size: 28px;
        font-weight: 800;
        color: white;
        letter-spacing: -.5px;
        margin: 0 0 8px;
        line-height: 1.15;
        text-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .desa-hero-title .wave {
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
    .desa-hero-sub {
        font-size: 14px;
        color: rgba(255,255,255,.9);
        max-width: 560px;
        margin: 0 0 20px;
        line-height: 1.55;
    }
    .desa-hero-sub strong {
        color: #ffffff;
        font-weight: 700;
    }
    .desa-hero-actions {
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
        color: #0d9488;
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
    .desa-hero-icon {
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
        .desa-hero-content { padding: 24px 20px; }
        .desa-hero-title { font-size: 22px; }
        .desa-hero-icon { display: none; }
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

    .stat-emerald { border-top: 3px solid #10b981; }
    .stat-emerald .stat-icon { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .stat-emerald .stat-deco { background: #10b981; }

    .stat-violet  { border-top: 3px solid #8b5cf6; }
    .stat-violet  .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .stat-violet  .stat-deco { background: #8b5cf6; }

    .stat-amber   { border-top: 3px solid #f59e0b; }
    .stat-amber   .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .stat-amber   .stat-deco { background: #f59e0b; }

    /* ===== SECTION CARD ===== */
    .section-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }
    .section-heading {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0 0 18px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        letter-spacing: -.3px;
    }
    .section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* ===== STATUS BOX ===== */
    .status-box {
        padding: 18px 12px;
        border-radius: 14px;
        text-align: center;
        border: 1px solid;
        transition: all .2s;
    }
    .status-box:hover {
        transform: translateY(-2px);
    }
    .status-box-value {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -.5px;
        line-height: 1;
    }
    .status-box-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-top: 6px;
        opacity: .85;
    }
    .status-box-pending { background: rgba(245,158,11,.1); border-color: rgba(245,158,11,.25); color: #d97706; }
    .status-box-proses  { background: rgba(59,130,246,.1);  border-color: rgba(59,130,246,.25);  color: #2563eb; }
    .status-box-selesai { background: rgba(16,185,129,.1);  border-color: rgba(16,185,129,.25);  color: #059669; }
    .status-box-total   { background: var(--bg-soft);       border-color: var(--border);        color: var(--text-primary); }

    /* ===== RW BOX ===== */
    .rw-box {
        padding: 16px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(13,148,136,.08), rgba(13,148,136,.03));
        border: 1px solid rgba(13,148,136,.25);
        transition: all .2s;
    }
    .rw-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -8px rgba(13,148,136,.35);
    }
    .rw-box-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .rw-box-title {
        font-size: 14px;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -.2px;
    }
    .rw-box-count {
        font-size: 10.5px;
        font-weight: 800;
        padding: 3px 9px;
        border-radius: 9999px;
        background: rgba(13,148,136,.15);
        color: #0d9488;
    }
    .rw-box-ketua {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    [data-theme="dark"] .rw-box-count { background: rgba(13,148,136,.25); color: #5eead4; }

    /* ===== PILL & LINK ===== */
    .pill-count {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        background: rgba(13,148,136,.12);
        color: #0d9488;
        border-radius: 9999px;
        margin-left: 4px;
    }
    .link-more {
        font-size: 12px;
        font-weight: 700;
        color: #0d9488;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all .15s;
    }
    .link-more:hover { gap: 8px; }

    /* ===== LIST ROW ===== */
    .list-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .list-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        background: var(--bg-soft);
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: all .18s;
    }
    .list-row-link:hover {
        transform: translateX(3px);
        border-color: #0d9488;
    }
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 800;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
    }
    .list-body {
        flex: 1;
        min-width: 0;
    }
    .list-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .list-sub {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ===== STATUS PILL ===== */
    .status-pill {
        font-size: 10px;
        font-weight: 800;
        padding: 5px 10px;
        border-radius: 8px;
        letter-spacing: .4px;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 32px 16px;
    }
    .empty-icon {
        font-size: 52px;
        color: var(--text-faint);
        display: block;
        margin: 0 auto 8px;
    }
    .empty-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        margin: 0;
    }
</style>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>