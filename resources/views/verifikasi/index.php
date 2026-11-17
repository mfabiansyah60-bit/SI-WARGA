<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Verifikasi Warga';
include __DIR__ . '/../layouts/dashboard_layout.php';

$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'warga';

$accentMap = [
    'rt'          => ['color' => '#d97706', 'bg' => 'rgba(217,119,6,.12)', 'label' => 'KETUA RT'],
    'rw'          => ['color' => '#7c3aed', 'bg' => 'rgba(124,58,237,.12)', 'label' => 'KETUA RW'],
    'desa'        => ['color' => '#1d4ed8', 'bg' => 'rgba(29,78,216,.12)', 'label' => 'ADMIN DESA'],
    'super_admin' => ['color' => '#dc2626', 'bg' => 'rgba(220,38,38,.12)', 'label' => 'SUPER ADMIN'],
];
$accent = $accentMap[$role] ?? $accentMap['rt'];

$total_pending = count($daftar_pending ?? []);
?>

<style>
    .vf-head { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px; margin-bottom:22px; }
    .vf-badge-role {
        display:inline-flex; align-items:center; gap:5px;
        padding:4px 11px; border-radius:9999px;
        font-size:10.5px; font-weight:700; letter-spacing:.4px;
        border:1px solid; margin-bottom:8px;
    }
    .vf-title {
        font-size:24px; font-weight:800;
        color: var(--text-primary, #0f172a);
        letter-spacing:-.5px; margin:0; line-height:1.15;
    }
    .vf-sub {
        font-size:13px; color: var(--text-muted, #64748b);
        margin-top:6px; max-width:640px; line-height:1.55;
    }
    .vf-sub strong { color: var(--text-primary, #0f172a); font-weight:600; }

    /* Stats */
    .vf-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:12px; margin-bottom:20px; }
    .vf-stat {
        background: var(--bg-card, #fff);
        border:1px solid var(--border, #e8edf3);
        border-radius:12px;
        padding:16px 18px; display:flex; align-items:center; gap:14px;
        transition:all .18s;
    }
    .vf-stat:hover { border-color: #cbd5e1; box-shadow:0 2px 10px rgba(15,23,42,.06); }
    [data-theme="dark"] .vf-stat:hover { border-color:#475569; }
    .vf-stat-icon {
        width:44px; height:44px; border-radius:11px;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .vf-stat-icon .material-symbols-outlined { font-size:22px; }
    .vf-stat-body { flex:1; min-width:0; }
    .vf-stat-label {
        font-size:10.5px; font-weight:700;
        color: var(--text-faint, #94a3b8);
        text-transform:uppercase; letter-spacing:.6px; margin-bottom:3px;
    }
    .vf-stat-value {
        font-size:22px; font-weight:800;
        color: var(--text-primary, #0f172a);
        line-height:1; letter-spacing:-.5px;
    }
    .vf-stat-sub { font-size:11px; color: var(--text-faint, #94a3b8); margin-top:4px; }

    /* Info collapsible */
    .vf-info { margin-bottom:18px; }
    .vf-info summary {
        list-style:none; cursor:pointer;
        padding:11px 16px; border-radius:10px;
        background: var(--bg-soft, #f8fafc);
        border:1px solid var(--border, #e8edf3);
        display:flex; align-items:center; gap:10px;
        font-size:12.5px; font-weight:600;
        color: var(--text-secondary, #475569);
        transition:all .15s;
    }
    .vf-info summary:hover { background: var(--bg-hover, #f1f5f9); border-color:#cbd5e1; }
    [data-theme="dark"] .vf-info summary:hover { border-color:#475569; }
    .vf-info summary::-webkit-details-marker { display:none; }
    .vf-info summary .material-symbols-outlined { font-size:18px; color:#3b82f6; }
    .vf-info-arrow { margin-left:auto; transition:transform .2s; color: var(--text-faint, #94a3b8); }
    .vf-info[open] .vf-info-arrow { transform:rotate(180deg); }
    .vf-info-body {
        padding:14px 18px 16px; margin-top:8px;
        background: var(--bg-soft, #f8fafc);
        border:1px solid var(--border, #e8edf3);
        border-radius:10px;
        font-size:12.5px; color: var(--text-secondary, #475569); line-height:1.7;
    }
    .vf-info-body strong { color: var(--text-primary, #0f172a); font-weight:600; }
    .vf-info-body code {
        background: var(--bg-elevated, #e2e8f0);
        color: var(--text-primary, #1e293b);
        padding:1px 6px; border-radius:4px;
        font-size:11.5px; font-family:ui-monospace,monospace;
    }
    .vf-info-body ul { margin:6px 0 0 0; padding-left:0; list-style:none; }
    .vf-info-body ul li { padding-left:18px; position:relative; margin-bottom:5px; }
    .vf-info-body ul li::before {
        content:'•'; position:absolute; left:4px;
        color: var(--text-faint, #94a3b8); font-weight:700;
    }

    /* Card */
    .vf-card {
        background: var(--bg-card, #fff);
        border:1px solid var(--border, #e8edf3);
        border-radius:12px; overflow:hidden;
    }

    /* Empty state */
    .vf-empty { padding:64px 24px; text-align:center; }
    .vf-empty-icon {
        width:64px; height:64px; margin:0 auto 14px;
        border-radius:50%; background: rgba(16,185,129,.12);
        display:flex; align-items:center; justify-content:center;
    }
    .vf-empty-icon .material-symbols-outlined { font-size:34px; color:#10b981; }
    .vf-empty-title {
        font-size:16px; font-weight:700;
        color: var(--text-primary, #0f172a);
        margin-bottom:5px;
    }
    .vf-empty-sub { font-size:13px; color: var(--text-faint, #94a3b8); }

    /* Row warga */
    .vf-row {
        padding:18px 22px;
        display:flex; align-items:center; gap:16px;
        border-bottom:1px solid var(--border-soft, #f1f5f9);
        transition:background .15s;
    }
    .vf-row:last-child { border-bottom:none; }
    .vf-row:hover { background: var(--bg-soft, #fafbfc); }

    .vf-avatar {
        width:46px; height:46px; border-radius:12px;
        display:flex; align-items:center; justify-content:center;
        font-weight:800; font-size:17px; color:#fff;
        flex-shrink:0;
    }

    .vf-info-col { flex:1; min-width:0; }
    .vf-name {
        font-size:14px; font-weight:700;
        color: var(--text-primary, #0f172a);
        margin-bottom:2px;
    }
    .vf-email {
        font-size:12.5px; color: var(--text-muted, #64748b);
        display:flex; align-items:center; gap:5px;
        margin-bottom:7px;
    }
    .vf-email .material-symbols-outlined { font-size:14px; color: var(--text-faint, #94a3b8); }

    .vf-tags { display:flex; flex-wrap:wrap; gap:5px; align-items:center; }
    .vf-tag {
        display:inline-flex; align-items:center; gap:4px;
        font-size:10.5px; font-weight:700;
        padding:3px 9px; border-radius:6px;
        letter-spacing:.2px;
    }
    .vf-tag.rt {
        background: rgba(217,119,6,.12); color:#b45309;
        border:1px solid rgba(217,119,6,.3);
    }
    [data-theme="dark"] .vf-tag.rt { color:#fbbf24; }
    .vf-tag.rw {
        background: rgba(124,58,237,.12); color:#6d28d9;
        border:1px solid rgba(124,58,237,.3);
    }
    [data-theme="dark"] .vf-tag.rw { color:#a78bfa; }
    .vf-tag.pending {
        background: rgba(245,158,11,.12); color:#92400e;
        border:1px solid rgba(245,158,11,.3);
    }
    [data-theme="dark"] .vf-tag.pending { color:#fbbf24; }
    .vf-tag.pending .material-symbols-outlined { font-size:12px; animation:vf-spin 3s linear infinite; }
    @keyframes vf-spin {
        0%, 100% { transform:rotate(0deg); }
        50% { transform:rotate(180deg); }
    }
    .vf-date {
        font-size:11px; color: var(--text-faint, #94a3b8);
        margin-top:7px;
        display:flex; align-items:center; gap:5px;
    }
    .vf-date .material-symbols-outlined { font-size:13px; }

    /* Actions */
    .vf-actions { display:flex; gap:8px; flex-shrink:0; }
    .vf-btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:9px 15px; border-radius:9px;
        font-size:12.5px; font-weight:700;
        border:none; cursor:pointer;
        transition:all .15s; font-family:inherit;
        white-space:nowrap;
    }
    .vf-btn-approve {
        background:#0f172a; color:#fff;
        box-shadow:0 2px 6px rgba(15,23,42,.2);
    }
    .vf-btn-approve:hover {
        background:#1e293b; transform:translateY(-1px);
        box-shadow:0 4px 10px rgba(15,23,42,.3);
    }
    [data-theme="dark"] .vf-btn-approve { background:#3b82f6; }
    [data-theme="dark"] .vf-btn-approve:hover { background:#2563eb; }
    .vf-btn-reject {
        background: rgba(239,68,68,.1); color:#b91c1c;
        border:1px solid rgba(239,68,68,.3);
    }
    .vf-btn-reject:hover { background: rgba(239,68,68,.2); }
    [data-theme="dark"] .vf-btn-reject { color:#f87171; }
    .vf-btn .material-symbols-outlined { font-size:15px; }

    /* Flash */
    .vf-flash {
        padding:12px 16px; border-radius:10px;
        margin-bottom:16px;
        font-size:13px; font-weight:500;
        display:flex; align-items:center; gap:10px;
    }
    .vf-flash.success { background: rgba(16,185,129,.12); color:#15803d; border:1px solid rgba(16,185,129,.3); }
    .vf-flash.error   { background: rgba(239,68,68,.12); color:#b91c1c; border:1px solid rgba(239,68,68,.3); }
    [data-theme="dark"] .vf-flash.success { color:#34d399; }
    [data-theme="dark"] .vf-flash.error { color:#f87171; }
    .vf-flash .material-symbols-outlined { font-size:18px; }

    @media (max-width:640px){
        .vf-title { font-size:19px; }
        .vf-row { flex-direction:column; align-items:flex-start; padding:16px; }
        .vf-actions { width:100%; }
        .vf-btn { flex:1; justify-content:center; }
    }
</style>

<!-- HEADER -->
<div class="vf-head">
    <div>
        <div class="vf-badge-role" style="background: <?= $accent['bg'] ?>; border-color: <?= $accent['color'] ?>40; color: <?= $accent['color'] ?>;">
            <span class="material-symbols-outlined" style="font-size:14px;">verified_user</span>
            <?= $accent['label'] ?> · <?= htmlspecialchars($scope_label ?? '-') ?>
        </div>
        <h1 class="vf-title">Verifikasi Warga</h1>
        <p class="vf-sub">
            Setujui warga baru untuk mengaktifkan akun mereka.
            <strong>Siapa pun yang menyetujui (RT atau RW), akun warga langsung aktif.</strong>
        </p>
    </div>
</div>

<!-- FLASH -->
<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="vf-flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
        <span class="material-symbols-outlined"><?= $flash['type'] === 'success' ? 'check_circle' : 'error' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
<?php endif; ?>

<!-- STATS -->
<div class="vf-stats">
    <div class="vf-stat">
        <div class="vf-stat-icon" style="background: rgba(245,158,11,.12); color:#b45309;">
            <span class="material-symbols-outlined">hourglass_top</span>
        </div>
        <div class="vf-stat-body">
            <div class="vf-stat-label">Menunggu Verifikasi</div>
            <div class="vf-stat-value"><?= $total_pending ?></div>
            <div class="vf-stat-sub">warga pending</div>
        </div>
    </div>

    <div class="vf-stat">
        <div class="vf-stat-icon" style="background: rgba(16,185,129,.12); color:#059669;">
            <span class="material-symbols-outlined">task_alt</span>
        </div>
        <div class="vf-stat-body">
            <div class="vf-stat-label">Status Sistem</div>
            <div class="vf-stat-value" style="font-size:15px; color:#059669;">Aktif</div>
            <div class="vf-stat-sub">verifikasi otomatis</div>
        </div>
    </div>

    <div class="vf-stat">
        <div class="vf-stat-icon" style="background: <?= $accent['bg'] ?>; color: <?= $accent['color'] ?>;">
            <span class="material-symbols-outlined">location_on</span>
        </div>
        <div class="vf-stat-body">
            <div class="vf-stat-label">Wilayah Anda</div>
            <div class="vf-stat-value" style="font-size:15px; color: <?= $accent['color'] ?>;"><?= htmlspecialchars($scope_label ?? '-') ?></div>
            <div class="vf-stat-sub">cakupan verifikasi</div>
        </div>
    </div>
</div>

<!-- INFO -->
<details class="vf-info">
    <summary>
        <span class="material-symbols-outlined">help</span>
        Cara kerja verifikasi
        <span class="material-symbols-outlined vf-info-arrow">expand_more</span>
    </summary>
    <div class="vf-info-body">
        <ul>
            <li>Setelah warga mendaftar, status akun: <code>pending</code></li>
            <li>Anda (RT/RW) bisa klik <strong>"Setujui &amp; Aktifkan"</strong> → akun langsung <strong>aktif</strong></li>
            <li>Warga bisa login tanpa menunggu verifikasi dari pihak lain</li>
            <li>Cukup <strong>salah satu</strong> (RT atau RW) yang menyetujui</li>
        </ul>
    </div>
</details>

<!-- LIST -->
<div class="vf-card">
    <?php if (empty($daftar_pending)): ?>
        <div class="vf-empty">
            <div class="vf-empty-icon">
                <span class="material-symbols-outlined">task_alt</span>
            </div>
            <div class="vf-empty-title">Tidak ada warga yang menunggu</div>
            <div class="vf-empty-sub">Semua warga sudah terverifikasi. Kerja bagus! 🎉</div>
        </div>
    <?php else: ?>
        <?php foreach ($daftar_pending as $w): ?>
            <?php $inisial = strtoupper(substr($w['name'], 0, 1)); ?>
            <div class="vf-row">
                <div class="vf-avatar" style="background:linear-gradient(135deg, #f59e0b, #b45309);">
                    <?= htmlspecialchars($inisial) ?>
                </div>

                <div class="vf-info-col">
                    <div class="vf-name"><?= htmlspecialchars($w['name']) ?></div>
                    <div class="vf-email">
                        <span class="material-symbols-outlined">mail</span>
                        <?= htmlspecialchars($w['email']) ?>
                    </div>
                    <div class="vf-tags">
                        <?php if (!empty($w['rt'])): ?>
                            <span class="vf-tag rt">RT <?= htmlspecialchars($w['rt']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($w['rw'])): ?>
                            <span class="vf-tag rw">RW <?= htmlspecialchars($w['rw']) ?></span>
                        <?php endif; ?>
                        <span class="vf-tag pending">
                            <span class="material-symbols-outlined">hourglass_top</span>
                            MENUNGGU VERIFIKASI
                        </span>
                    </div>
                    <?php if (!empty($w['created_at'])): ?>
                        <div class="vf-date">
                            <span class="material-symbols-outlined">schedule</span>
                            Daftar: <?= date('d M Y, H:i', strtotime($w['created_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="vf-actions">
                    <!-- FORM HIDDEN UNTUK SUBMIT ACTION -->
                    <form action="/verifikasi?action=approve&id=<?= $w['id'] ?>" method="POST" id="form-approve-<?= $w['id'] ?>" style="display:none;"></form>
                    <form action="/verifikasi?action=reject&id=<?= $w['id'] ?>" method="POST" id="form-reject-<?= $w['id'] ?>" style="display:none;"></form>

                    <!-- TOMBOL SETUJUI PAKAI CUSTOM MODAL -->
                    <button type="button" class="vf-btn vf-btn-approve" 
                            onclick="showGlobalModal({
                                title: 'Setujui Warga?',
                                message: 'Setujui warga <b><?= htmlspecialchars(addslashes($w['name'])) ?></b>? Akun akan langsung aktif dan bisa login.',
                                type: 'success',
                                showCancel: true,
                                confirmText: 'Ya, Setujui',
                                onConfirm: function() { document.getElementById('form-approve-<?= $w['id'] ?>').submit(); }
                            })">
                        <span class="material-symbols-outlined">check</span>
                        Setujui
                    </button>

                    <!-- TOMBOL TOLAK PAKAI CUSTOM MODAL -->
                    <button type="button" class="vf-btn vf-btn-reject" 
                            onclick="showGlobalModal({
                                title: 'Tolak Warga?',
                                message: 'Yakin ingin menolak pendaftaran warga <b><?= htmlspecialchars(addslashes($w['name'])) ?></b>?',
                                type: 'danger',
                                showCancel: true,
                                confirmText: 'Ya, Tolak',
                                onConfirm: function() { document.getElementById('form-reject-<?= $w['id'] ?>').submit(); }
                            })">
                        <span class="material-symbols-outlined">close</span>
                        Tolak
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>