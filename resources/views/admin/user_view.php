<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: /login'); exit; }

$page_title = 'Detail User';
include __DIR__ . '/../layouts/dashboard_layout.php';

$roleInfo = [
    'super_admin' => ['label' => 'Super Admin', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'desa'        => ['label' => 'Admin Desa',  'color' => '#2563eb', 'bg' => '#eff6ff'],
    'rw'          => ['label' => 'Ketua RW',    'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'rt'          => ['label' => 'Ketua RT',    'color' => '#d97706', 'bg' => '#fffbeb'],
    'warga'       => ['label' => 'Warga',       'color' => '#059669', 'bg' => '#ecfdf5'],
];
$info = $roleInfo[$target['role']] ?? ['label'=>$target['role'],'color'=>'#64748b','bg'=>'#f1f5f9'];
$initial = strtoupper(substr($target['name'] ?? '?', 0, 1));
?>

<style>
.uv-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
.uv-title { font-size:22px; font-weight:700; letter-spacing:-.3px; color:#0f172a; }
.uv-sub { font-size:13px; color:#64748b; margin-top:3px; }

.uv-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; border:1px solid transparent; cursor:pointer; transition:all .15s; font-family:inherit; }
.uv-btn-outline { background:#fff; color:#334155; border-color:#e2e8f0; }
.uv-btn-outline:hover { background:#f8fafc; border-color:#cbd5e1; }
.uv-btn-primary { background:#0f172a; color:#fff; }
.uv-btn-primary:hover { background:#1e293b; }
.uv-btn svg { width:14px; height:14px; }

.uv-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; max-width:720px; }
.uv-profile { padding:24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:16px; }
.uv-avatar-lg { width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700; color:#fff; flex-shrink:0; }
.uv-profile-info h2 { font-size:17px; font-weight:600; letter-spacing:-.2px; color:#0f172a; }
.uv-profile-info p { font-size:13px; color:#64748b; margin-top:2px; }
.uv-badge { display:inline-block; padding:3px 9px; border-radius:5px; font-size:11px; font-weight:600; margin-top:8px; }

.uv-fields { padding:8px 24px 20px; }
.uv-field { display:flex; padding:14px 0; border-bottom:1px solid #f1f5f9; align-items:center; }
.uv-field:last-child { border-bottom:none; }
.uv-field-label { width:160px; color:#64748b; font-size:12.5px; font-weight:500; flex-shrink:0; }
.uv-field-value { flex:1; color:#0f172a; font-size:13.5px; font-weight:500; }
.uv-field-value.muted { color:#94a3b8; font-weight:400; }
.uv-mono { font-family:ui-monospace,'SF Mono',monospace; background:#f1f5f9; padding:3px 8px; border-radius:5px; font-size:12px; margin-right:4px; display:inline-block; }
</style>

<div class="uv-head">
    <div>
        <div class="uv-title">Detail User</div>
        <div class="uv-sub">Informasi akun dan hak akses pengguna</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/users" class="uv-btn uv-btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <a href="/users?action=edit&id=<?= $target['id'] ?>" class="uv-btn uv-btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
        </a>
    </div>
</div>

<div class="uv-card">
    <div class="uv-profile">
        <div class="uv-avatar-lg" style="background:<?= $info['color'] ?>"><?= $initial ?></div>
        <div class="uv-profile-info">
            <h2><?= htmlspecialchars($target['name']) ?></h2>
            <p><?= htmlspecialchars($target['email']) ?></p>
            <span class="uv-badge" style="background:<?= $info['bg'] ?>;color:<?= $info['color'] ?>"><?= htmlspecialchars($info['label']) ?></span>
        </div>
    </div>

    <div class="uv-fields">
        <div class="uv-field">
            <div class="uv-field-label">ID User</div>
            <div class="uv-field-value">#<?= $target['id'] ?></div>
        </div>
        <div class="uv-field">
            <div class="uv-field-label">Email</div>
            <div class="uv-field-value"><?= htmlspecialchars($target['email']) ?></div>
        </div>
        <div class="uv-field">
            <div class="uv-field-label">Role</div>
            <div class="uv-field-value"><?= htmlspecialchars($info['label']) ?></div>
        </div>
        <div class="uv-field">
            <div class="uv-field-label">Wilayah</div>
            <div class="uv-field-value">
                <?php if (!empty($target['rt']) || !empty($target['rw'])): ?>
                    <span class="uv-mono">RT <?= htmlspecialchars($target['rt'] ?: '-') ?></span>
                    <span class="uv-mono">RW <?= htmlspecialchars($target['rw'] ?: '-') ?></span>
                <?php else: ?>
                    <span class="uv-field-value muted">Belum diatur</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="uv-field">
            <div class="uv-field-label">Persetujuan WhatsApp</div>
            <div class="uv-field-value">
                <?php if (!isset($target['wa_opt_in']) || (int)$target['wa_opt_in'] === 1): ?>
                    <span class="uv-badge" style="background:#ecfdf5;color:#059669;margin-top:0;">
                        ✅ Menyetujui Notifikasi (Opt-In)
                    </span>
                    <?php if (!empty($target['wa_opt_in_at'])): ?>
                        <span style="font-size:12px;color:#64748b;margin-left:8px;">
                            sejak <?= date('d M Y, H:i', strtotime($target['wa_opt_in_at'])) ?> WIB
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="uv-badge" style="background:#fef2f2;color:#dc2626;margin-top:0;">
                        ❌ Menolak Notifikasi (Opt-Out)
                    </span>
                    <?php if (!empty($target['wa_opt_in_at'])): ?>
                        <span style="font-size:12px;color:#64748b;margin-left:8px;">
                            sejak <?= date('d M Y, H:i', strtotime($target['wa_opt_in_at'])) ?> WIB
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="uv-field">
            <div class="uv-field-label">Terdaftar</div>
            <div class="uv-field-value <?= empty($target['created_at'])?'muted':'' ?>">
                <?= !empty($target['created_at']) ? date('d M Y, H:i', strtotime($target['created_at'])) : '-' ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>