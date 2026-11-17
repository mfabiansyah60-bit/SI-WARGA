<?php
// resources/views/ai/riwayat.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../app/AI/RoleCapabilities.php';

$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'warga';
$roleName = RoleCapabilities::label($role);

$page_title = 'Riwayat Chat AI';
include __DIR__ . '/../layouts/dashboard_layout.php';

$daftarSesi = $daftarSesi ?? [];
$totalSesi = count($daftarSesi);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root {
    --rw-card:      var(--bg-card, #ffffff);
    --rw-border:    var(--border, #e2e8f0);
    --rw-text:      var(--text-primary, #0f172a);
    --rw-muted:     var(--text-muted, #64748b);
    --rw-faint:     var(--text-faint, #94a3b8);
    --rw-accent-1:  #3b82f6;
    --rw-accent-2:  #2563eb;
    --rw-accent-3:  #1d4ed8;
    --rw-accent-rgb: 37, 99, 235;
    --rw-soft:      var(--bg-soft, #f8fafc);
}

[data-theme="dark"] {
    --rw-card:      var(--bg-card, #131c2e);
    --rw-border:    var(--border, #1f2c44);
    --rw-text:      var(--text-primary, #f1f5f9);
    --rw-muted:     var(--text-muted, #94a3b8);
    --rw-faint:     var(--text-faint, #64748b);
    --rw-soft:      var(--bg-soft, #1a2540);
}

.rw-wrap {
    width: 100%;
    max-width: 960px;
    margin: 0 auto;
    padding: 0;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ============ HEADER ============ */
.rw-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px;
    background: var(--rw-card);
    border: 1px solid var(--rw-border);
    border-radius: 16px 16px 0 0;
    border-bottom: none;
    transition: all .25s ease;
}

.rw-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}

.rw-brand-mark {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
}
.rw-brand-mark .material-symbols-outlined {
    font-size: 24px;
}

.rw-brand-name {
    font-size: 17px;
    font-weight: 800;
    color: var(--rw-text);
    letter-spacing: -.3px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.rw-brand-sub {
    font-size: 12px;
    color: var(--rw-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 3px;
}

.rw-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 9999px;
    background: rgba(37, 99, 235, 0.12);
    color: var(--rw-accent-2);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .3px;
}
[data-theme="dark"] .rw-count-badge {
    background: rgba(96, 165, 250, 0.2);
    color: #93c5fd;
}

.rw-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.rw-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: all .18s ease;
    border: 1px solid transparent;
}
.rw-btn .material-symbols-outlined { font-size: 18px; }

.rw-btn-primary {
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    color: #fff;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}
.rw-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
    color: #fff;
}

.rw-btn-outline {
    background: transparent;
    color: var(--rw-muted);
    border-color: var(--rw-border);
}
.rw-btn-outline:hover {
    background: var(--rw-soft);
    color: var(--rw-text);
    border-color: var(--rw-faint);
}

.rw-btn-danger {
    background: rgba(239, 68, 68, .08);
    color: #dc2626;
    border-color: rgba(239, 68, 68, .2);
}
.rw-btn-danger:hover {
    background: rgba(239, 68, 68, .16);
    color: #b91c1c;
}
[data-theme="dark"] .rw-btn-danger { color: #f87171; }

/* ============ CARD ============ */
.rw-card {
    background: var(--rw-card);
    border: 1px solid var(--rw-border);
    border-radius: 0 0 16px 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
    transition: all .25s ease;
}

/* ============ SESSION LIST ============ */
.rw-session-grid {
    display: flex;
    flex-direction: column;
}

.rw-session-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px;
    border-bottom: 1px solid var(--rw-border);
    transition: background .15s ease;
}
.rw-session-row:last-child {
    border-bottom: none;
}
.rw-session-row:hover {
    background: var(--rw-soft);
}

.rw-session-main {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.rw-session-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(37, 99, 235, 0.1);
    color: var(--rw-accent-2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.rw-session-avatar .material-symbols-outlined {
    font-size: 22px;
}

.rw-session-info {
    flex: 1;
    min-width: 0;
}

.rw-session-title {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--rw-text);
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.35;
}

.rw-session-meta {
    font-size: 12px;
    color: var(--rw-muted);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.rw-session-meta .material-symbols-outlined {
    font-size: 14px;
    vertical-align: middle;
}

.rw-session-btns {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.rw-btn-open {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 9px;
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    color: #fff;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}
.rw-btn-open:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    color: #fff;
}
.rw-btn-open .material-symbols-outlined { font-size: 16px; }

.rw-btn-del-item {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid var(--rw-border);
    color: var(--rw-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
}
.rw-btn-del-item:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.3);
}
.rw-btn-del-item .material-symbols-outlined { font-size: 18px; }

/* ============ EMPTY ============ */
.rw-empty {
    padding: 72px 24px;
    text-align: center;
}
.rw-empty-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: rgba(37, 99, 235, 0.1);
    color: var(--rw-accent-2);
    display: flex;
    align-items: center;
    justify-content: center;
}
.rw-empty-icon .material-symbols-outlined {
    font-size: 38px;
}
.rw-empty-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--rw-text);
    margin-bottom: 6px;
}
.rw-empty-sub {
    font-size: 13.5px;
    color: var(--rw-muted);
    margin-bottom: 22px;
    max-width: 440px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

@media (max-width: 640px) {
    .rw-header { flex-direction: column; align-items: flex-start; padding: 14px 16px; }
    .rw-actions { width: 100%; }
    .rw-btn { flex: 1; justify-content: center; }
    .rw-session-row { flex-direction: column; align-items: flex-start; padding: 14px 16px; }
    .rw-session-btns { width: 100%; justify-content: flex-end; }
    .rw-session-title { white-space: normal; }
}
</style>

<div class="rw-wrap">

    <!-- HEADER -->
    <div class="rw-header">
        <div class="rw-brand">
            <div class="rw-brand-mark">
                <span class="material-symbols-outlined">forum</span>
            </div>
            <div>
                <h1 class="rw-brand-name">
                    Daftar Percakapan AI
                    <span class="rw-count-badge"><?= $totalSesi ?> Sesi</span>
                </h1>
                <div class="rw-brand-sub">
                    <span class="material-symbols-outlined" style="font-size:14px;color:#22c55e;">lock</span>
                    <span>Tersimpan aman di akun Anda · Terisolasi per pengguna</span>
                </div>
            </div>
        </div>
        <div class="rw-actions">
            <a href="/ai" class="rw-btn rw-btn-primary">
                <span class="material-symbols-outlined">add_comment</span>
                <span>Chat Baru</span>
            </a>
            <?php if (!empty($daftarSesi)): ?>
                <a href="/ai?action=hapus_riwayat"
                   onclick="return confirm('Hapus SEMUA riwayat percakapan Anda? Tindakan ini permanen dan tidak bisa dibatalkan.');"
                   class="rw-btn rw-btn-danger">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    <span>Hapus Semua</span>
                </a>
            <?php endif; ?>
            <a href="/dashboard" class="rw-btn rw-btn-outline">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- CARD -->
    <div class="rw-card">
        <?php if (empty($daftarSesi)): ?>
            <div class="rw-empty">
                <div class="rw-empty-icon">
                    <span class="material-symbols-outlined">chat_bubble_outline</span>
                </div>
                <div class="rw-empty-title">Belum ada riwayat percakapan</div>
                <div class="rw-empty-sub">Ajukan pertanyaan tentang surat, kependudukan, atau layanan desa ke SIWARGA AI. Seluruh riwayat obrolan Anda akan tersimpan otomatis di sini.</div>
                <a href="/ai" class="rw-btn rw-btn-primary" style="padding:10px 20px;">
                    <span class="material-symbols-outlined">smart_toy</span>
                    <span>Mulai Chat Sekarang</span>
                </a>
            </div>
        <?php else: ?>
            <div class="rw-session-grid">
                <?php foreach ($daftarSesi as $s): 
                    $sessionId = $s['session_id'];
                    $title     = htmlspecialchars($s['title'] ?: 'Percakapan');
                    $dateStr   = !empty($s['updated_at']) ? $s['updated_at'] : $s['created_at'];
                    $tglFormatted = date('d M Y, H:i', strtotime($dateStr));
                    $totalMsg  = (int)($s['total_messages'] ?? 1);
                ?>
                    <div class="rw-session-row">
                        <div class="rw-session-main">
                            <div class="rw-session-avatar">
                                <span class="material-symbols-outlined">chat</span>
                            </div>
                            <div class="rw-session-info">
                                <h3 class="rw-session-title" title="<?= $title ?>"><?= $title ?></h3>
                                <div class="rw-session-meta">
                                    <span>
                                        <span class="material-symbols-outlined">schedule</span>
                                        <?= $tglFormatted ?> WIB
                                    </span>
                                    <span>•</span>
                                    <span>
                                        <span class="material-symbols-outlined">chat_bubble</span>
                                        <?= $totalMsg ?> pesan
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rw-session-btns">
                            <a href="/ai?session_id=<?= urlencode($sessionId) ?>" class="rw-btn-open" title="Buka & Lanjutkan Chat">
                                <span class="material-symbols-outlined">arrow_outward</span>
                                <span>Buka Chat</span>
                            </a>
                            <form action="/ai?action=hapus_sesi&redirect_riwayat=1" method="POST" onsubmit="return confirm('Hapus percakapan: <?= addslashes($title) ?>?');" style="margin:0;">
                                <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                                <button type="submit" class="rw-btn-del-item" title="Hapus percakapan ini">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER INFO -->
    <div style="padding: 16px 4px; text-align: center; font-size: 12px; color: var(--rw-faint);">
        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">security</span>
        Riwayat percakapan tersimpan secara privat · Hanya Anda yang dapat mengakses dan mengelolanya
    </div>

</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>