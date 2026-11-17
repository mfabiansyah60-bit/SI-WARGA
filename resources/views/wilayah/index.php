<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Manajemen Wilayah';
include __DIR__ . '/../layouts/dashboard_layout.php';

$current_type = $_GET['type'] ?? 'desa';
?>

<style>
.wl-wrap { font-family: 'Inter', system-ui, sans-serif; }
.wl-page-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
.wl-page-title { font-size:22px; font-weight:700; letter-spacing:-.3px; color:var(--text-primary, #0f172a); }
.wl-page-sub { font-size:13px; color:var(--text-muted, #64748b); margin-top:4px; }

/* TABS */
.wl-tabs {
    display:inline-flex;
    gap:4px;
    padding:5px;
    background:var(--bg-card, #fff);
    border:1px solid var(--border, #e2e8f0);
    border-radius:12px;
    margin-bottom:24px;
}
.wl-tab {
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:9px 20px;
    border-radius:8px;
    font-size:13.5px;
    font-weight:600;
    color:var(--text-secondary, #475569);
    text-decoration:none;
    transition:all .15s;
}
.wl-tab:hover { background:var(--bg-soft, #f8fafc); color:var(--text-primary, #0f172a); }
.wl-tab.active {
    background:linear-gradient(135deg, #3b82f6, #1d4ed8);
    color:#fff;
    box-shadow:0 4px 12px rgba(59,130,246,.3);
}
.wl-tab svg { width:16px; height:16px; stroke-width:2; }

/* HEADER + BUTTON */
.wl-section-head {
    display:flex; justify-content:space-between; align-items:center;
    flex-wrap:wrap; gap:12px; margin-bottom:16px;
}
.wl-section-title {
    font-size:16px; font-weight:800; color:var(--text-primary, #0f172a);
    display:flex; align-items:center; gap:10px; letter-spacing:-.2px;
}
.wl-count-badge {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:26px; height:24px; padding:0 9px;
    background:rgba(59,130,246,.12); color:#2563eb;
    border-radius:8px; font-size:12px; font-weight:700;
}
[data-theme="dark"] .wl-count-badge { color:#60a5fa; }

.wl-btn-add {
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 16px; border-radius:8px;
    background:#0f172a; color:#fff;
    text-decoration:none; font-size:13px; font-weight:600;
    border:none; cursor:pointer; transition:all .15s;
    font-family:inherit;
}
.wl-btn-add:hover { background:#1e293b; transform:translateY(-1px); }
[data-theme="dark"] .wl-btn-add { background:#3b82f6; }
[data-theme="dark"] .wl-btn-add:hover { background:#2563eb; }
.wl-btn-add svg { width:14px; height:14px; stroke-width:2.2; }

/* CARD */
.wl-card {
    background:var(--bg-card, #fff);
    border:1px solid var(--border, #e2e8f0);
    border-radius:12px;
    overflow:hidden;
}

/* TABLE */
.wl-table { width:100%; border-collapse:collapse; }
.wl-table thead { background:var(--bg-soft, #fafafa); border-bottom:1px solid var(--border, #e2e8f0); }
[data-theme="dark"] .wl-table thead { background:rgba(255,255,255,.03); }
.wl-table th {
    padding:12px 16px; text-align:left;
    font-size:11px; font-weight:600;
    color:var(--text-faint, #64748b);
    text-transform:uppercase; letter-spacing:.5px;
    white-space:nowrap;
}
.wl-table td {
    padding:14px 16px;
    border-bottom:1px solid var(--border-soft, #f1f5f9);
    font-size:13.5px;
    vertical-align:middle;
    color:var(--text-secondary, #475569);
}
.wl-table tbody tr { transition:background .12s; }
.wl-table tbody tr:hover { background:var(--bg-soft, #fafafa); }
[data-theme="dark"] .wl-table tbody tr:hover { background:rgba(255,255,255,.03); }
.wl-table tbody tr:last-child td { border-bottom:none; }

.wl-cell-num { color:var(--text-faint, #94a3b8); font-weight:500; }
.wl-cell-name { font-weight:600; color:var(--text-primary, #0f172a); }

.wl-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 11px; border-radius:6px;
    font-size:11.5px; font-weight:700; letter-spacing:.2px;
    text-transform:uppercase;
}
.wl-badge.rw { background:rgba(139,92,246,.12); color:#7c3aed; }
.wl-badge.rt { background:rgba(245,158,11,.12); color:#d97706; }
[data-theme="dark"] .wl-badge.rw { color:#a78bfa; }
[data-theme="dark"] .wl-badge.rt { color:#fbbf24; }

/* Empty state */
.wl-empty { padding:64px 24px; text-align:center; }
.wl-empty-icon {
    width:64px; height:64px; margin:0 auto 14px;
    border-radius:16px;
    background:var(--bg-soft, #f8fafc);
    border:1px solid var(--border, #e2e8f0);
    display:flex; align-items:center; justify-content:center;
}
.wl-empty-icon svg { width:28px; height:28px; color:var(--text-faint, #94a3b8); }
.wl-empty-title { font-size:15px; font-weight:700; color:var(--text-primary, #0f172a); margin-bottom:5px; }
.wl-empty-sub { font-size:13px; color:var(--text-faint, #94a3b8); }

@media (max-width:768px){
    .wl-table thead { display:none; }
    .wl-table tbody tr { display:block; padding:14px; border-bottom:1px solid var(--border-soft, #f1f5f9); }
    .wl-table td { display:block; padding:5px 0; border:none; }
    .wl-table td::before { content:attr(data-label); display:block; font-size:10px; font-weight:600; color:var(--text-faint, #94a3b8); text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; }
    .wl-table td[data-label="#"] { display:none; }
    .wl-table td[data-label="Aksi"] .dock-actions { justify-content:flex-start; margin-top:6px; }
    .wl-tabs { width:100%; display:flex; }
    .wl-tab { flex:1; justify-content:center; }
}
</style>

<div class="wl-wrap">

    <!-- HEADER -->
    <div class="wl-page-head">
        <div>
            <div class="wl-page-title">Manajemen Wilayah</div>
            <div class="wl-page-sub">Kelola data Desa, RW, dan RT</div>
        </div>
        <a href="/dashboard" class="wl-btn-add" style="background:var(--bg-card, #fff);color:var(--text-secondary, #475569);border:1px solid var(--border, #e2e8f0);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Dashboard
        </a>
    </div>

    <!-- TABS -->
    <div class="wl-tabs">
        <a href="/wilayah?type=desa" class="wl-tab <?= $current_type === 'desa' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
            Desa
        </a>
        <a href="/wilayah?type=rw" class="wl-tab <?= $current_type === 'rw' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            RW
        </a>
        <a href="/wilayah?type=rt" class="wl-tab <?= $current_type === 'rt' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            RT
        </a>
    </div>

<?php if ($current_type === 'desa'): ?>

    <!-- DESA -->
    <div class="wl-section-head">
        <h2 class="wl-section-title">
            Daftar Desa
            <span class="wl-count-badge"><?= count($desa_list ?? []) ?></span>
        </h2>
        <a href="/wilayah?action=tambah&type=desa" class="wl-btn-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Desa
        </a>
    </div>

    <div class="wl-card">
        <?php if (empty($desa_list)): ?>
            <div class="wl-empty">
                <div class="wl-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
                </div>
                <div class="wl-empty-title">Belum Ada Data Desa</div>
                <div class="wl-empty-sub">Klik "Tambah Desa" untuk memulai</div>
            </div>
        <?php else: ?>
            <table class="wl-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Nama Desa</th>
                        <th>Alamat</th>
                        <th>Telepon</th>
                        <th style="text-align:right;width:130px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($desa_list as $i => $d): ?>
                        <tr>
                            <td data-label="#" class="wl-cell-num"><?= $i + 1 ?></td>
                            <td data-label="Nama Desa" class="wl-cell-name"><?= htmlspecialchars($d['nama']) ?></td>
                            <td data-label="Alamat"><?= htmlspecialchars($d['alamat'] ?? '-') ?></td>
                            <td data-label="Telepon"><?= htmlspecialchars($d['telepon'] ?? '-') ?></td>
                            <td data-label="Aksi">
                                <div class="dock-actions">
                                    <a href="/wilayah?action=edit&type=desa&id=<?= $d['id'] ?>" class="dock-btn dock-edit" title="Edit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <a href="/wilayah?action=hapus&type=desa&id=<?= $d['id'] ?>" onclick="return confirm('Yakin hapus desa <?= htmlspecialchars($d['nama'], ENT_QUOTES) ?>?')" class="dock-btn dock-del" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($current_type === 'rw'): ?>

    <!-- RW -->
    <div class="wl-section-head">
        <h2 class="wl-section-title">
            Daftar RW
            <span class="wl-count-badge"><?= count($rw_list ?? []) ?></span>
        </h2>
        <a href="/wilayah?action=tambah&type=rw" class="wl-btn-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah RW
        </a>
    </div>

    <div class="wl-card">
        <?php if (empty($rw_list)): ?>
            <div class="wl-empty">
                <div class="wl-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <div class="wl-empty-title">Belum Ada Data RW</div>
                <div class="wl-empty-sub">Klik "Tambah RW" untuk memulai</div>
            </div>
        <?php else: ?>
            <table class="wl-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Desa</th>
                        <th style="width:120px">Nomor RW</th>
                        <th>Ketua</th>
                        <th style="text-align:right;width:130px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rw_list as $i => $r): ?>
                        <tr>
                            <td data-label="#" class="wl-cell-num"><?= $i + 1 ?></td>
                            <td data-label="Desa"><?= htmlspecialchars($r['desa_nama'] ?? '-') ?></td>
                            <td data-label="Nomor RW">
                                <span class="wl-badge rw">RW <?= htmlspecialchars($r['nomor']) ?></span>
                            </td>
                            <td data-label="Ketua" class="wl-cell-name"><?= htmlspecialchars($r['ketua'] ?? '-') ?></td>
                            <td data-label="Aksi">
                                <div class="dock-actions">
                                    <a href="/wilayah?action=edit&type=rw&id=<?= $r['id'] ?>" class="dock-btn dock-edit" title="Edit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <a href="/wilayah?action=hapus&type=rw&id=<?= $r['id'] ?>" onclick="return confirm('Yakin hapus RW <?= htmlspecialchars($r['nomor'], ENT_QUOTES) ?>?')" class="dock-btn dock-del" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php else: ?>

    <!-- RT -->
    <div class="wl-section-head">
        <h2 class="wl-section-title">
            Daftar RT
            <span class="wl-count-badge"><?= count($rt_list ?? []) ?></span>
        </h2>
        <a href="/wilayah?action=tambah&type=rt" class="wl-btn-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah RT
        </a>
    </div>

    <div class="wl-card">
        <?php if (empty($rt_list)): ?>
            <div class="wl-empty">
                <div class="wl-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </div>
                <div class="wl-empty-title">Belum Ada Data RT</div>
                <div class="wl-empty-sub">Klik "Tambah RT" untuk memulai</div>
            </div>
        <?php else: ?>
            <table class="wl-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Desa</th>
                        <th style="width:100px">RW</th>
                        <th style="width:100px">Nomor RT</th>
                        <th>Ketua</th>
                        <th style="text-align:right;width:130px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rt_list as $i => $r): ?>
                        <tr>
                            <td data-label="#" class="wl-cell-num"><?= $i + 1 ?></td>
                            <td data-label="Desa"><?= htmlspecialchars($r['desa_nama'] ?? '-') ?></td>
                            <td data-label="RW">
                                <span class="wl-badge rw">RW <?= htmlspecialchars($r['rw_nomor'] ?? '-') ?></span>
                            </td>
                            <td data-label="Nomor RT">
                                <span class="wl-badge rt">RT <?= htmlspecialchars($r['nomor']) ?></span>
                            </td>
                            <td data-label="Ketua" class="wl-cell-name"><?= htmlspecialchars($r['ketua'] ?? '-') ?></td>
                            <td data-label="Aksi">
                                <div class="dock-actions">
                                    <a href="/wilayah?action=edit&type=rt&id=<?= $r['id'] ?>" class="dock-btn dock-edit" title="Edit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <a href="/wilayah?action=hapus&type=rt&id=<?= $r['id'] ?>" onclick="return confirm('Yakin hapus RT <?= htmlspecialchars($r['nomor'], ENT_QUOTES) ?>?')" class="dock-btn dock-del" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>