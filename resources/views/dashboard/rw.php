<?php
// resources/views/dashboard/rw.php
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? [];
$userName = $user['name'] ?? 'Ketua RW';
$rw_user = trim($user['rw'] ?? '');

try {
    if ($rw_user !== '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND rw = ?");
        $stmt->execute([$rw_user]);
        $total_warga = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT id, name, email, rt, rw, created_at
            FROM users WHERE role='warga' AND rw = ?
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->execute([$rw_user]);
        $daftar_warga = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT u.rt AS nomor_rt, COUNT(u.id) AS jumlah_warga
            FROM users u
            WHERE u.role='warga' AND u.rw = ?
            GROUP BY u.rt
            ORDER BY u.rt
        ");
        $stmt->execute([$rw_user]);
        $ringkasan_rt = $stmt->fetchAll();

        $total_rt = count($ringkasan_rt);

        // ========================================================
        // TAMBAHAN BARU: Ambil daftar nomor RT untuk teks sambutan
        // ========================================================
        $stmt_rt_list = $pdo->prepare("
            SELECT rt.nomor 
            FROM rt 
            JOIN rw ON rt.rw_id = rw.id 
            WHERE rw.nomor = ? 
            ORDER BY rt.nomor
        ");
        $stmt_rt_list->execute([$rw_user]);
        $rt_numbers = $stmt_rt_list->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($rt_numbers) > 0) {
            $teks_rt = "RT " . implode(", RT ", $rt_numbers);
        } else {
            $teks_rt = "0 RT";
        }
        // ========================================================

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM pengaduan p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE u.rw = ?
        ");
        $stmt->execute([$rw_user]);
        $total_pengaduan = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM pengaduan p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE u.rw = ? AND p.status = 'pending'
        ");
        $stmt->execute([$rw_user]);
        $pengaduan_pending = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT p.id, p.judul, p.status, p.created_at, 
                   u.name AS user_name, u.rt
            FROM pengaduan p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE u.rw = ?
            ORDER BY p.created_at DESC LIMIT 5
        ");
        $stmt->execute([$rw_user]);
        $aktivitas_pengaduan = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT ketua FROM rw WHERE nomor = ? LIMIT 1");
        $stmt->execute([$rw_user]);
        $nama_ketua_rw = $stmt->fetchColumn() ?: $userName;

        $label_scope = "RW $rw_user";
        $filter_aktif = true;
    } else {
        $total_warga    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='warga'")->fetchColumn();
        $total_rt       = $pdo->query("SELECT COUNT(*) FROM rt")->fetchColumn();
        $total_pengaduan = $pdo->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn();
        $pengaduan_pending = $pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status='pending'")->fetchColumn();

        $daftar_warga = $pdo->query("
            SELECT id, name, email, rt, rw, created_at
            FROM users WHERE role='warga'
            ORDER BY created_at DESC LIMIT 10
        ")->fetchAll();

        $aktivitas_pengaduan = $pdo->query("
            SELECT p.id, p.judul, p.status, p.created_at, u.name AS user_name, u.rt
            FROM pengaduan p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC LIMIT 5
        ")->fetchAll();

        $ringkasan_rt = [];
        $teks_rt = $total_rt . " RT"; // Fallback
        $nama_ketua_rw = $userName;
        $label_scope = "Semua (RW belum di-set)";
        $filter_aktif = false;
    }

    $total_rw_desa = $pdo->query("SELECT COUNT(*) FROM rw")->fetchColumn();

} catch (Exception $e) {
    $total_warga = $total_rt = $total_rw_desa = 0;
    $total_pengaduan = $pengaduan_pending = 0;
    $daftar_warga = $aktivitas_pengaduan = $ringkasan_rt = [];
    $teks_rt = "0 RT"; // Fallback kalau error
    $label_scope = '-';
    $nama_ketua_rw = $userName;
    $filter_aktif = false;
    log_error('Dashboard RW error', ['error' => $e->getMessage()]);
}

$page_title = 'Dashboard Ketua RW';
include __DIR__ . '/../layouts/dashboard_layout.php';
?>

<!-- ==================== HERO HEADER ==================== -->
<div class="rw-hero mb-6">
    <div class="rw-hero-bg"></div>
    <div class="rw-hero-content">
        <div class="rw-hero-left">
            <div class="rw-hero-badge">
                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;">holiday_village</span>
                KETUA RW<?= $rw_user ? " · RW $rw_user" : '' ?>
            </div>
            <h1 class="rw-hero-title">
                Selamat Datang, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! <span class="wave">👋</span>
            </h1>
            <!-- ============================================== -->
            <!-- BAGIAN INI YANG KITA UBAH JADI $teks_rt -->
            <!-- ============================================== -->
            <p class="rw-hero-sub">
                Wilayah Anda: <strong><?= htmlspecialchars($label_scope) ?></strong>. Anda membawahi <strong><?= htmlspecialchars($teks_rt) ?></strong> dengan total <strong><?= number_format($total_warga, 0, ',', '.') ?> warga</strong> terdaftar.
            </p>
            <div class="rw-hero-actions">
                <a href="/verifikasi" class="hero-btn hero-btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px;">verified_user</span>
                    Verifikasi Warga
                </a>
                <a href="/warga" class="hero-btn hero-btn-ghost">
                    <span class="material-symbols-outlined" style="font-size:16px;">groups</span>
                    Lihat Data Warga
                </a>
            </div>
        </div>
        <div class="rw-hero-icon">
            <span class="material-symbols-outlined" style="font-size:56px;font-variation-settings:'FILL' 1;">holiday_village</span>
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
        <div class="stat-label">Warga <?= htmlspecialchars($label_scope) ?></div>
        <div class="stat-deco"></div>
    </a>

    <a href="/rt_rw" class="stat-card stat-violet">
        <div class="stat-icon">
            <span class="material-symbols-outlined">home_work</span>
        </div>
        <div class="stat-value"><?= number_format($total_rt, 0, ',', '.') ?></div>
        <div class="stat-label">RT di <?= htmlspecialchars($label_scope) ?></div>
        <div class="stat-deco"></div>
    </a>

        <a href="/rt_rw" class="stat-card stat-emerald">
        <div class="stat-icon">
            <span class="material-symbols-outlined">apartment</span>
        </div>
        <div class="stat-value"><?= number_format($total_rw_desa, 0, ',', '.') ?></div>
        <div class="stat-label">Total RW Desa</div>
        <div class="stat-deco"></div>
    </a>

    <a href="/pengaduan" class="stat-card stat-amber">
        <div class="stat-icon">
            <span class="material-symbols-outlined">campaign</span>
        </div>
        <div class="stat-value"><?= number_format($total_pengaduan, 0, ',', '.') ?></div>
        <div class="stat-label">Pengaduan <?= htmlspecialchars($label_scope) ?></div>
        <div class="stat-deco"></div>
    </a>

</div>

<!-- ==================== RINGKASAN PER RT ==================== -->
<?php if ($filter_aktif && !empty($ringkasan_rt)): ?>
<div class="section-card mb-6">
    <h3 class="section-heading">
        <span class="material-symbols-outlined" style="color:#8b5cf6;">pie_chart</span>
        Ringkasan per RT di <?= htmlspecialchars($label_scope) ?>
    </h3>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <?php foreach ($ringkasan_rt as $r): ?>
            <div class="rt-box">
                <div class="rt-box-head">
                    <div class="rt-box-title">RT <?= htmlspecialchars($r['nomor_rt'] ?? '-') ?></div>
                    <span class="material-symbols-outlined text-[18px]" style="color:#8b5cf6;">home_work</span>
                </div>
                <div class="rt-box-value"><?= $r['jumlah_warga'] ?></div>
                <div class="rt-box-label">warga terdaftar</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ==================== DAFTAR WARGA ==================== -->
<div class="section-card mb-6">
    <div class="section-head">
        <h3 class="section-heading" style="margin-bottom:0;">
            <span class="material-symbols-outlined" style="color:#8b5cf6;">groups</span>
            Warga <?= htmlspecialchars($label_scope) ?>
            <span class="pill-count"><?= number_format($total_warga, 0, ',', '.') ?></span>
        </h3>
        <a href="/warga" class="link-more">
            Lihat Semua
            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
    </div>

    <?php if (empty($daftar_warga)): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined empty-icon">person_off</span>
            <p class="empty-title">Belum ada warga di wilayah <?= htmlspecialchars($label_scope) ?></p>
            <?php if (!$filter_aktif): ?>
                <p class="empty-hint">⚠️ Akun Anda belum di-set RW-nya. Hubungi Admin Desa.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="list-stack">
            <?php foreach ($daftar_warga as $w): ?>
                <?php
                    $inisial = strtoupper(substr($w['name'], 0, 1));
                    $colors = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#ef4444'];
                    $bgColor = $colors[ord($inisial) % count($colors)];
                ?>
                <div class="list-row">
                    <div class="avatar" style="background:<?= $bgColor ?>;">
                        <?= htmlspecialchars($inisial) ?>
                    </div>
                    <div class="list-body">
                        <div class="list-title"><?= htmlspecialchars($w['name']) ?></div>
                        <div class="list-sub">
                            <span class="material-symbols-outlined text-[14px]">mail</span>
                            <?= htmlspecialchars($w['email']) ?>
                        </div>
                    </div>
                    <div class="list-meta">
                        <div class="pill-rt">RT <?= htmlspecialchars($w['rt'] ?? '-') ?> · RW <?= htmlspecialchars($w['rw'] ?? '-') ?></div>
                        <div class="list-time">
                            <?= !empty($w['created_at']) ? waktu_lalu($w['created_at']) : '-' ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ==================== PENGADUAN TERBARU ==================== -->
<div class="section-card mb-6">
    <h3 class="section-heading">
        <span class="material-symbols-outlined" style="color:#8b5cf6;">history</span>
        Pengaduan Terbaru <?= htmlspecialchars($label_scope) ?>
    </h3>

    <?php if (empty($aktivitas_pengaduan)): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined empty-icon">inbox</span>
            <p class="empty-title">Belum ada pengaduan di wilayah <?= htmlspecialchars($label_scope) ?></p>
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
            foreach ($aktivitas_pengaduan as $a):
                $st = $statusMap[$a['status'] ?? 'pending'] ?? $statusMap['pending'];
                $inisial = strtoupper(substr($a['user_name'] ?? 'U', 0, 1));
            ?>
                <a href="/pengaduan?action=detail&id=<?= $a['id'] ?>" class="list-row list-row-link">
                    <div class="avatar" style="background:linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <?= htmlspecialchars($inisial) ?>
                    </div>
                    <div class="list-body">
                        <div class="list-title"><?= htmlspecialchars($a['judul'] ?? '-') ?></div>
                        <div class="list-sub">
                            <?= htmlspecialchars($a['user_name'] ?? 'User') ?>
                            · RT <?= htmlspecialchars($a['rt'] ?? '-') ?>
                            · <?= !empty($a['created_at']) ? waktu_lalu($a['created_at']) : '-' ?>
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
        ['icon' => 'verified_user',   'color' => 'orange', 'label' => 'Verifikasi Warga', 'href' => '/verifikasi'],
        ['icon' => 'groups',          'color' => 'blue',   'label' => 'Warga RW Saya',    'href' => '/warga'],
        ['icon' => 'holiday_village', 'color' => 'purple', 'label' => 'Info RT',          'href' => '/rt_rw'],
        ['icon' => 'campaign',        'color' => 'red',    'label' => 'Pengaduan RW',     'href' => '/pengaduan'],
        ['icon' => 'handyman',        'color' => 'green',  'label' => 'Info Layanan',     'href' => '/pelayanan'],
        ['icon' => 'bar_chart',       'color' => 'indigo', 'label' => 'Laporan RW',       'href' => '/laporan'],
    ];
    include __DIR__ . '/../components/glass-icons.php';
    ?>
</div>

<!-- ==================== CUSTOM STYLES ==================== -->
<style>
    /* ===== HERO ===== */
    .rw-hero {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 40%, #6366f1 100%);
        box-shadow: 0 20px 40px -15px rgba(124,58,237,.45), 0 0 0 1px rgba(255,255,255,.1) inset;
    }
    .rw-hero-bg {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 15% 20%, rgba(255,255,255,.2) 0%, transparent 40%),
            radial-gradient(circle at 85% 80%, rgba(99,102,241,.35) 0%, transparent 50%),
            radial-gradient(circle at 50% 50%, rgba(139,92,246,.2) 0%, transparent 60%);
        pointer-events: none;
    }
    .rw-hero::after {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%);
        pointer-events: none;
    }
    .rw-hero-content {
        position: relative;
        padding: 32px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .rw-hero-left { flex: 1; min-width: 240px; }
    .rw-hero-badge {
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
    .rw-hero-title {
        font-size: 28px;
        font-weight: 800;
        color: white;
        letter-spacing: -.5px;
        margin: 0 0 8px;
        line-height: 1.15;
        text-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .rw-hero-title .wave {
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
    .rw-hero-sub {
        font-size: 14px;
        color: rgba(255,255,255,.9);
        max-width: 520px;
        margin: 0 0 20px;
        line-height: 1.55;
    }
    .rw-hero-sub strong {
        color: #ffffff;
        font-weight: 700;
    }
    .rw-hero-actions {
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
        color: #7c3aed;
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
    .rw-hero-icon {
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
        .rw-hero-content { padding: 24px 20px; }
        .rw-hero-title { font-size: 22px; }
        .rw-hero-icon { display: none; }
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

    .stat-violet  { border-top: 3px solid #8b5cf6; }
    .stat-violet  .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .stat-violet  .stat-deco { background: #8b5cf6; }

    .stat-emerald { border-top: 3px solid #10b981; }
    .stat-emerald .stat-icon { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .stat-emerald .stat-deco { background: #10b981; }

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

    /* ===== RT BOX ===== */
    .rt-box {
        padding: 16px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(139,92,246,.08), rgba(139,92,246,.03));
        border: 1px solid rgba(139,92,246,.25);
        transition: all .2s;
    }
    .rt-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -8px rgba(139,92,246,.35);
    }
    .rt-box-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .rt-box-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
    }
    .rt-box-value {
        font-size: 24px;
        font-weight: 800;
        color: #7c3aed;
        letter-spacing: -.5px;
        line-height: 1;
    }
    .rt-box-label {
        font-size: 11px;
        font-weight: 700;
        margin-top: 6px;
        color: var(--text-muted);
        letter-spacing: .3px;
        text-transform: uppercase;
    }
    [data-theme="dark"] .rt-box-value { color: #a78bfa; }

    /* ===== PILL & LINK ===== */
    .pill-count {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        background: rgba(139,92,246,.12);
        color: #7c3aed;
        border-radius: 9999px;
        margin-left: 4px;
    }
    .link-more {
        font-size: 12px;
        font-weight: 700;
        color: #7c3aed;
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
        border-color: #8b5cf6;
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
    .list-meta {
        text-align: right;
        flex-shrink: 0;
    }
    .pill-rt {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 9999px;
        background: rgba(139,92,246,.12);
        color: #7c3aed;
        display: inline-block;
    }
    .list-time {
        font-size: 10px;
        color: var(--text-faint);
        margin-top: 4px;
    }
    @media (max-width: 640px) {
        .list-meta { display: none; }
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
    .empty-hint {
        font-size: 12px;
        color: #d97706;
        margin-top: 8px;
    }
</style>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>