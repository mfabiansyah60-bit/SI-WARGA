<?php
// resources/views/layouts/sidebar.php — GSAP + Dark Mode + Electric Neon AI

if (session_status() === PHP_SESSION_NONE) session_start();

$user     = $_SESSION['user'] ?? [];
$role     = $user['role'] ?? 'warga';
$userName = $user['name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));

$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = str_replace(['/Rpl', '/public'], '', $currentPath);
$currentPath = parse_url($currentPath, PHP_URL_PATH);
$currentPath = rtrim($currentPath, '/') ?: '/';
$currentSeg  = explode('/', ltrim($currentPath, '/'))[0] ?? '';

// Hitung pending verifikasi
$pendingVerifikasi = 0;
if (in_array($role, ['super_admin', 'desa', 'rw', 'rt']) && isset($pdo)) {
    try {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'warga' AND status_verifikasi = 'pending'";
        $params = [];
        if ($role === 'rt') {
            $sql .= " AND rt = ? AND rw = ?";
            $params = [$user['rt'] ?? '', $user['rw'] ?? ''];
        } elseif ($role === 'rw') {
            $sql .= " AND rw = ?";
            $params = [$user['rw'] ?? ''];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pendingVerifikasi = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
}

$accentByRole = [
    'super_admin' => '#ef4444',
    'desa'        => '#3b82f6',
    'rw'          => '#8b5cf6',
    'rt'          => '#f59e0b',
    'warga'       => '#10b981',
];
$accent = $accentByRole[$role] ?? '#3b82f6';

$roleLabel = [
    'super_admin' => 'Super Admin',
    'desa'        => 'Admin Desa',
    'rw'          => 'Ketua RW',
    'rt'          => 'Ketua RT',
    'warga'       => 'Warga',
][$role] ?? 'User';

// MENU
$menus = [];
if ($role === 'super_admin') {
    $menus = [
        ['label'=>'Dashboard',        'href'=>'/dashboard',  'icon'=>'dashboard',       'section'=>'UTAMA'],
        ['label'=>'Pengumuman',       'href'=>'/pengumuman', 'icon'=>'notifications',   'section'=>'UTAMA'],
        ['label'=>'Verifikasi Warga', 'href'=>'/verifikasi', 'icon'=>'verified_user',   'section'=>'MANAJEMEN', 'badge'=>$pendingVerifikasi],
        ['label'=>'Manajemen User',   'href'=>'/users',      'icon'=>'manage_accounts', 'section'=>'MANAJEMEN'],
        ['label'=>'Wilayah Desa',     'href'=>'/wilayah',    'icon'=>'map',             'section'=>'MANAJEMEN'],
        ['label'=>'Manajemen RT/RW',  'href'=>'/rt_rw',      'icon'=>'holiday_village', 'section'=>'MANAJEMEN'],
        ['label'=>'Data Warga',       'href'=>'/warga',      'icon'=>'groups',          'section'=>'MANAJEMEN'],
        ['label'=>'Pelayanan',        'href'=>'/pelayanan',  'icon'=>'handyman',        'section'=>'LAYANAN'],
        ['label'=>'Pengaduan',        'href'=>'/pengaduan',  'icon'=>'campaign',        'section'=>'LAYANAN'],
        ['label'=>'Template Surat',   'href'=>'/surat',      'icon'=>'mail',            'section'=>'LAYANAN'],
        ['label'=>'Laporan',          'href'=>'/laporan',    'icon'=>'bar_chart',       'section'=>'LAINNYA'],
        ['label'=>'Konfigurasi AI',   'href'=>'/ai-config',  'icon'=>'tune',            'section'=>'LAINNYA'],
        ['label'=>'Konfigurasi WhatsApp', 'href'=>'/whatsapp-config', 'icon'=>'chat', 'section'=>'LAINNYA'],
        ['label'=>'Pengaturan',       'href'=>'/pengaturan', 'icon'=>'settings',        'section'=>'LAINNYA'],
    ];
} elseif ($role === 'desa') {
    $menus = [
        ['label'=>'Dashboard',        'href'=>'/dashboard',  'icon'=>'dashboard',       'section'=>'UTAMA'],
        ['label'=>'Pengumuman',       'href'=>'/pengumuman', 'icon'=>'notifications',   'section'=>'UTAMA'],
        ['label'=>'Verifikasi Warga', 'href'=>'/verifikasi', 'icon'=>'verified_user',   'section'=>'MANAJEMEN', 'badge'=>$pendingVerifikasi],
        ['label'=>'Wilayah Desa',     'href'=>'/wilayah',    'icon'=>'map',             'section'=>'MANAJEMEN'],
        ['label'=>'Manajemen RT/RW',  'href'=>'/rt_rw',      'icon'=>'holiday_village', 'section'=>'MANAJEMEN'],
        ['label'=>'Data Warga',       'href'=>'/warga',      'icon'=>'groups',          'section'=>'MANAJEMEN'],
        ['label'=>'Pelayanan',        'href'=>'/pelayanan',  'icon'=>'handyman',        'section'=>'LAYANAN'],
        ['label'=>'Pengaduan',        'href'=>'/pengaduan',  'icon'=>'campaign',        'section'=>'LAYANAN'],
        ['label'=>'Template Surat',   'href'=>'/surat',      'icon'=>'mail',            'section'=>'LAYANAN'],
        ['label'=>'Laporan',          'href'=>'/laporan',    'icon'=>'bar_chart',       'section'=>'LAINNYA'],
        // MENU KONFIGURASI AI SUDAH DIHAPUS
        ['label'=>'Pengaturan',       'href'=>'/pengaturan', 'icon'=>'settings',        'section'=>'LAINNYA'],
    ];
} elseif ($role === 'rw') {
    $menus = [
        ['label'=>'Dashboard',        'href'=>'/dashboard',  'icon'=>'dashboard',       'section'=>'UTAMA'],
        ['label'=>'Pengumuman',       'href'=>'/pengumuman', 'icon'=>'notifications',   'section'=>'UTAMA'],
        ['label'=>'Verifikasi Warga', 'href'=>'/verifikasi', 'icon'=>'verified_user',   'section'=>'MANAJEMEN', 'badge'=>$pendingVerifikasi],
        ['label'=>'Warga RW Saya',    'href'=>'/warga',      'icon'=>'groups',          'section'=>'MANAJEMEN'],
        ['label'=>'Info RT',          'href'=>'/rt_rw',      'icon'=>'holiday_village', 'section'=>'MANAJEMEN'],
        ['label'=>'Pengaduan RW',     'href'=>'/pengaduan',  'icon'=>'campaign',        'section'=>'LAYANAN'],
        ['label'=>'Info Layanan',     'href'=>'/pelayanan',  'icon'=>'handyman',        'section'=>'LAYANAN'],
        ['label'=>'Buat Surat',       'href'=>'/surat',      'icon'=>'mail',            'section'=>'LAYANAN'],
        ['label'=>'Laporan RW',       'href'=>'/laporan',    'icon'=>'bar_chart',       'section'=>'LAINNYA'],
        ['label'=>'Pengaturan',       'href'=>'/pengaturan', 'icon'=>'settings',        'section'=>'LAINNYA'],
    ];
} elseif ($role === 'rt') {
    $menus = [
        ['label'=>'Dashboard',        'href'=>'/dashboard',  'icon'=>'dashboard',       'section'=>'UTAMA'],
        ['label'=>'Pengumuman',       'href'=>'/pengumuman', 'icon'=>'notifications',   'section'=>'UTAMA'],
        ['label'=>'Verifikasi Warga', 'href'=>'/verifikasi', 'icon'=>'verified_user',   'section'=>'MANAJEMEN', 'badge'=>$pendingVerifikasi],
        ['label'=>'Warga RT Saya',    'href'=>'/warga',      'icon'=>'groups',          'section'=>'MANAJEMEN'],
        ['label'=>'Pengaduan RT',     'href'=>'/pengaduan',  'icon'=>'campaign',        'section'=>'LAYANAN'],
        ['label'=>'Info Layanan',     'href'=>'/pelayanan',  'icon'=>'handyman',        'section'=>'LAYANAN'],
        ['label'=>'Buat Surat',       'href'=>'/surat',      'icon'=>'mail',            'section'=>'LAYANAN'],
        ['label'=>'Laporan RT',       'href'=>'/laporan',    'icon'=>'bar_chart',       'section'=>'LAINNYA'],
        ['label'=>'Pengaturan',       'href'=>'/pengaturan', 'icon'=>'settings',        'section'=>'LAINNYA'],
    ];
} else {
    $menus = [
        ['label'=>'Dashboard',    'href'=>'/dashboard',  'icon'=>'dashboard',       'section'=>'UTAMA'],
        ['label'=>'Pengumuman',   'href'=>'/pengumuman', 'icon'=>'notifications',   'section'=>'UTAMA'],
        ['label'=>'Pengaduan',    'href'=>'/pengaduan',  'icon'=>'campaign',        'section'=>'LAYANAN'],
        ['label'=>'Info Layanan', 'href'=>'/pelayanan',  'icon'=>'handyman',        'section'=>'LAYANAN'],
        ['label'=>'Buat Surat',   'href'=>'/surat',      'icon'=>'mail',            'section'=>'LAYANAN'],
        ['label'=>'Pengaturan',   'href'=>'/pengaturan', 'icon'=>'settings',        'section'=>'LAINNYA'],
    ];
}

$sections = [];
foreach ($menus as $m) $sections[$m['section']][] = $m;
?>

<style>
/* ============ TOGGLE BUTTON ============ */
#sbToggle {
    position: fixed;
    top: 16px; left: 16px;
    z-index: 10001;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 10px;
    cursor: pointer;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary, #0f172a);
    box-shadow: 0 2px 8px rgba(15,23,42,.08);
    transition: all .18s;
}
#sbToggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(15,23,42,.12);
    border-color: var(--text-faint, #cbd5e1);
}

.sb-toggle-text {
    position: relative;
    overflow: hidden;
    height: 18px;
    width: 46px;
    display: block;
}
.sb-toggle-text-inner {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    will-change: transform;
}
.sb-toggle-text-line {
    display: block;
    height: 18px;
    line-height: 18px;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary, #0f172a);
}

.sb-toggle-icon {
    position: relative;
    width: 14px;
    height: 14px;
    display: inline-block;
    flex-shrink: 0;
    will-change: transform;
}
.sb-toggle-icon-line {
    position: absolute;
    top: 50%;
    left: 0;
    width: 100%;
    height: 2px;
    background: currentColor;
    border-radius: 2px;
    transform: translateY(-50%);
}
.sb-toggle-icon-line-v {
    transform: translateY(-50%) rotate(90deg);
}

/* ============ BACKDROP ============ */
#sbBackdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    opacity: 0;
    pointer-events: none;
    z-index: 9999;
    backdrop-filter: blur(2px);
    transition: opacity .32s ease;
}
#sbBackdrop.open {
    opacity: 1;
    pointer-events: auto;
}

/* ============ PRE-LAYERS ============ */
#sbPrelayers {
    position: fixed;
    top: 0; left: 0;
    width: 340px;
    max-width: 88vw;
    height: 100vh;
    z-index: 10000;
    pointer-events: none;
    visibility: hidden;
}
.sb-prelayer {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    will-change: transform;
}

/* ============ PANEL ============ */
#sbPanel {
    position: fixed;
    top: 0; left: 0;
    width: 340px;
    max-width: 88vw;
    height: 100vh;
    background: var(--bg-card, #ffffff);
    color: var(--text-primary, #0f172a);
    z-index: 10001;
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 24px rgba(0,0,0,.15);
    font-family: 'Inter', system-ui, sans-serif;
    will-change: transform;
    overflow: hidden;
    visibility: hidden;
    transition: background .3s ease, color .3s ease;
}

.sb-brand {
    padding: 20px 22px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border-soft, #f1f5f9);
    flex-shrink: 0;
}
.sb-brand-logo {
    width: 42px; height: 42px;
    border-radius: 11px;
    background: linear-gradient(135deg, #1e40af, #3b82f6);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 16px;
    box-shadow: 0 4px 12px rgba(30,64,175,.3);
    flex-shrink: 0;
}
.sb-brand-name { font-size: 15px; font-weight: 800; color: var(--text-primary, #0f172a); letter-spacing: -.3px; }
.sb-brand-sub { font-size: 10.5px; color: var(--text-faint, #94a3b8); font-weight: 500; margin-top: 2px; letter-spacing: .3px; text-transform: uppercase; }

.sb-user {
    padding: 14px 22px;
    border-bottom: 1px solid var(--border-soft, #f1f5f9);
    display: flex;
    align-items: center;
    gap: 11px;
    flex-shrink: 0;
}
.sb-avatar {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-weight: 700; font-size: 15px;
    flex-shrink: 0;
}
.sb-user-name { font-size: 13px; font-weight: 600; color: var(--text-primary, #0f172a); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-user-role {
    display: inline-flex;
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 5px;
    margin-top: 3px;
    letter-spacing: .3px;
    text-transform: uppercase;
}

.sb-nav {
    flex: 1;
    overflow-y: auto;
    padding: 10px 12px 20px;
}
.sb-nav::-webkit-scrollbar { width: 4px; }
.sb-nav::-webkit-scrollbar-thumb { background: var(--border, #e2e8f0); border-radius: 2px; }

.sb-section {
    font-size: 10px;
    font-weight: 700;
    color: var(--text-faint, #94a3b8);
    text-transform: uppercase;
    letter-spacing: .8px;
    padding: 14px 12px 6px;
    will-change: transform, opacity;
}

.sb-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    border-radius: 10px;
    color: var(--text-secondary, #475569);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 2px;
    position: relative;
    overflow: hidden;
    transition: background .15s, color .15s;
}
.sb-item:hover {
    background: var(--bg-hover, #f8fafc);
    color: var(--text-primary, #0f172a);
}
.sb-item.active {
    background: linear-gradient(90deg, var(--accent-bg, #eff6ff) 0%, transparent 100%);
    color: var(--accent, #1e40af);
    font-weight: 600;
}
.sb-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 3px; height: 20px;
    background: var(--accent, #1e40af);
    border-radius: 0 3px 3px 0;
}
.sb-item-icon {
    font-size: 20px;
    color: var(--text-faint, #94a3b8);
    flex-shrink: 0;
    transition: color .15s;
}
.sb-item:hover .sb-item-icon { color: var(--text-muted, #64748b); }
.sb-item.active .sb-item-icon {
    color: var(--accent, #1e40af);
    font-variation-settings: 'FILL' 1, 'wght' 500;
}
.sb-item-label {
    flex: 1;
    display: inline-block;
    will-change: transform;
    transform-origin: 50% 100%;
}

.sb-badge {
    min-width: 20px; height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: #ef4444;
    color: #fff;
    font-size: 10.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(239,68,68,.35);
}

.sb-footer {
    padding: 12px 12px;
    border-top: 1px solid var(--border-soft, #f1f5f9);
    flex-shrink: 0;
}
.sb-logout {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 12px;
    border-radius: 10px;
    color: #dc2626;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    transition: background .15s;
}
.sb-logout:hover { background: rgba(239,68,68,.1); }
.sb-logout .material-symbols-outlined { font-size: 19px; }

/* ============ AI FLOATING BUTTON — ELECTRIC NEON (Clean) ============ */
#sbAiFloat {
    position: fixed;
    bottom: 22px;
    right: 22px;
    z-index: 9998;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #0b1220;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    cursor: pointer;
    overflow: visible;
    transition: transform .25s ease, box-shadow .25s ease;
    box-shadow:
        0 0 10px rgba(245, 158, 11, .35),
        0 0 24px rgba(234, 88, 12, .2),
        0 0 42px rgba(234, 88, 12, .1);
}

.sb-ai-svg {
    width: 36px;
    height: 36px;
    display: block;
    filter:
        drop-shadow(0 0 3px rgba(251, 191, 36, .9))
        drop-shadow(0 0 8px rgba(245, 158, 11, .65))
        drop-shadow(0 0 16px rgba(234, 88, 12, .4));
    transition: filter .25s ease, transform .25s ease;
    position: relative;
    z-index: 2;
}

/* Hover: subtle glow naik */
#sbAiFloat:hover {
    transform: scale(1.04);
    box-shadow:
        0 0 14px rgba(245, 158, 11, .5),
        0 0 32px rgba(234, 88, 12, .3),
        0 0 56px rgba(234, 88, 12, .15);
}
#sbAiFloat:hover .sb-ai-svg {
    filter:
        drop-shadow(0 0 4px rgba(251, 191, 36, 1))
        drop-shadow(0 0 10px rgba(245, 158, 11, .8))
        drop-shadow(0 0 20px rgba(234, 88, 12, .55));
}

/* Tooltip */
#sbAiFloat .sb-ai-tip {
    position: absolute;
    right: 76px;
    top: 50%;
    transform: translateY(-50%) translateX(8px);
    background: #0b1220;
    color: #fbbf24;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all .2s;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .4);
    border: 1px solid rgba(245, 158, 11, .35);
}
#sbAiFloat:hover .sb-ai-tip {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
}

[data-theme="dark"] #sbAiFloat,
.dark #sbAiFloat {
    background: #060b14;
}

@media (max-width: 640px) {
    #sbAiFloat { width: 58px; height: 58px; bottom: 18px; right: 18px; }
    .sb-ai-svg { width: 32px; height: 32px; }
}

@media (max-width: 640px) {
    #sbAiFloat { width: 60px; height: 60px; bottom: 18px; right: 18px; }
    .sb-ai-svg { width: 34px; height: 34px; }
}
</style>

<!-- TOGGLE BUTTON -->
<button id="sbToggle" type="button" aria-label="Menu" aria-expanded="false">
    <span class="sb-toggle-text" aria-hidden="true">
        <span class="sb-toggle-text-inner" id="sbToggleTextInner">
            <span class="sb-toggle-text-line">Menu</span>
            <span class="sb-toggle-text-line">Tutup</span>
        </span>
    </span>
    <span class="sb-toggle-icon" aria-hidden="true" id="sbToggleIcon">
        <span class="sb-toggle-icon-line"></span>
        <span class="sb-toggle-icon-line sb-toggle-icon-line-v"></span>
    </span>
</button>

<!-- BACKDROP -->
<div id="sbBackdrop"></div>

<!-- PRE-LAYERS (orange electric) -->
<div id="sbPrelayers" aria-hidden="true">
    <div class="sb-prelayer" style="background:#7c2d12;"></div>
    <div class="sb-prelayer" style="background:#ea580c;"></div>
    <div class="sb-prelayer" style="background:#fbbf24;"></div>
</div>

<!-- PANEL -->
<aside id="sbPanel">

    <div class="sb-brand">
        <div class="sb-brand-logo">SW</div>
        <div>
            <div class="sb-brand-name">SIWARGA</div>
            <div class="sb-brand-sub">Desa Digital</div>
        </div>
    </div>

    <div class="sb-user">
        <div class="sb-avatar" style="background: <?= $accent ?>"><?= $userInitial ?></div>
        <div style="flex:1;min-width:0">
            <div class="sb-user-name"><?= htmlspecialchars($userName) ?></div>
            <span class="sb-user-role" style="background:<?= $accent ?>15;color:<?= $accent ?>">
                <?= htmlspecialchars($roleLabel) ?>
            </span>
        </div>
    </div>

    <nav class="sb-nav">
        <?php foreach ($sections as $sectionName => $items): ?>
            <div class="sb-section"><?= htmlspecialchars($sectionName) ?></div>
            <?php foreach ($items as $item):
                $hrefSeg = ltrim(parse_url($item['href'], PHP_URL_PATH), '/');
                $isActive = ($currentSeg === $hrefSeg);
                $badge = $item['badge'] ?? 0;
            ?>
                <a href="<?= htmlspecialchars($item['href']) ?>"
                   class="sb-item <?= $isActive ? 'active' : '' ?>"
                   style="--accent:<?= $accent ?>; --accent-bg:<?= $accent ?>10;">
                    <span class="material-symbols-outlined sb-item-icon"><?= htmlspecialchars($item['icon']) ?></span>
                    <span class="sb-item-label"><?= htmlspecialchars($item['label']) ?></span>
                    <?php if ($badge > 0): ?>
                        <span class="sb-badge"><?= $badge > 99 ? '99+' : $badge ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sb-footer">
        <a href="/logout" class="sb-logout">
            <span class="material-symbols-outlined">logout</span>
            <span>Keluar</span>
        </a>
    </div>

</aside>

<!-- AI FLOATING BUTTON — ELECTRIC NEON -->
<a href="/ai" id="sbAiFloat" title="SIWARGA AI">
    <svg class="sb-ai-svg" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M16 4 C8 4 2 9 2 15.5 C2 19 3.5 22 6 24 L6 29 L11 26 C12.5 26.5 14.2 27 16 27 C24 27 30 22 30 15.5 C30 9 24 4 16 4 Z"
              stroke="url(#aiStroke)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        <circle cx="11" cy="15.5" r="1.4" fill="#fbbf24"/>
        <circle cx="16" cy="15.5" r="1.4" fill="#fbbf24"/>
        <circle cx="21" cy="15.5" r="1.4" fill="#fbbf24"/>
        <defs>
            <linearGradient id="aiStroke" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#fbbf24"/>
                <stop offset="50%" stop-color="#f59e0b"/>
                <stop offset="100%" stop-color="#ea580c"/>
            </linearGradient>
        </defs>
    </svg>
    <span class="sb-ai-tip">Tanya SIWARGA AI</span>
</a>

<!-- GSAP -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
(function() {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP gagal load');
        return;
    }

    var toggle        = document.getElementById('sbToggle');
    var toggleText    = document.getElementById('sbToggleTextInner');
    var toggleIcon    = document.getElementById('sbToggleIcon');
    var panel         = document.getElementById('sbPanel');
    var prelayersWrap = document.getElementById('sbPrelayers');
    var prelayers     = prelayersWrap.querySelectorAll('.sb-prelayer');
    var backdrop      = document.getElementById('sbBackdrop');
    var itemLabels    = panel.querySelectorAll('.sb-item-label');
    var sectionTitles = panel.querySelectorAll('.sb-section');
    var brandEl       = panel.querySelector('.sb-brand');
    var userEl        = panel.querySelector('.sb-user');
    var footerEl      = panel.querySelector('.sb-footer');

    var isOpen = false;
    var busy = false;
    var openTl = null;
    var closeTween = null;

    gsap.set([panel, prelayersWrap], { xPercent: -100, visibility: 'visible' });
    gsap.set(prelayers, { xPercent: -100 });
    gsap.set(itemLabels, { yPercent: 140, rotate: 8 });
    gsap.set(sectionTitles, { opacity: 0, x: -20 });
    gsap.set([brandEl, userEl], { opacity: 0, y: -12 });
    gsap.set(footerEl, { opacity: 0, y: 12 });
    gsap.set(toggleIcon, { rotate: 0 });
    gsap.set(toggleText, { yPercent: 0 });

    function buildOpenTimeline() {
        if (openTl) openTl.kill();
        if (closeTween) { closeTween.kill(); closeTween = null; }

        gsap.set(prelayers, { xPercent: -100 });
        gsap.set(itemLabels, { yPercent: 140, rotate: 8 });
        gsap.set(sectionTitles, { opacity: 0, x: -20 });
        gsap.set([brandEl, userEl], { opacity: 0, y: -12 });
        gsap.set(footerEl, { opacity: 0, y: 12 });

        var tl = gsap.timeline({ paused: true });

        tl.to(prelayers, {
            xPercent: 0,
            duration: 0.5,
            ease: 'power4.out',
            stagger: 0.07
        }, 0);

        var panelStart = 0.1 + (prelayers.length - 1) * 0.07;
        tl.to(panel, {
            xPercent: 0,
            duration: 0.6,
            ease: 'power4.out'
        }, panelStart);

        tl.to([brandEl, userEl], {
            opacity: 1, y: 0,
            duration: 0.4,
            ease: 'power2.out',
            stagger: 0.06
        }, panelStart + 0.3);

        tl.to(sectionTitles, {
            opacity: 1, x: 0,
            duration: 0.4,
            ease: 'power3.out',
            stagger: 0.08
        }, panelStart + 0.4);

        tl.to(itemLabels, {
            yPercent: 0,
            rotate: 0,
            duration: 0.7,
            ease: 'power4.out',
            stagger: { each: 0.05, from: 'start' }
        }, panelStart + 0.42);

        tl.to(footerEl, {
            opacity: 1, y: 0,
            duration: 0.35,
            ease: 'power2.out'
        }, panelStart + 0.6);

        return tl;
    }

    function playOpen() {
        if (busy) return;
        busy = true;
        openTl = buildOpenTimeline();
        if (openTl) {
            openTl.eventCallback('onComplete', function() { busy = false; });
            openTl.play(0);
        } else {
            busy = false;
        }
    }

    function playClose() {
        if (openTl) { openTl.kill(); openTl = null; }
        if (closeTween) closeTween.kill();

        closeTween = gsap.to([panel, prelayers], {
            xPercent: -100,
            duration: 0.36,
            ease: 'power3.in',
            overwrite: 'auto',
            onComplete: function() {
                gsap.set(itemLabels, { yPercent: 140, rotate: 8 });
                gsap.set(sectionTitles, { opacity: 0, x: -20 });
                gsap.set([brandEl, userEl], { opacity: 0, y: -12 });
                gsap.set(footerEl, { opacity: 0, y: 12 });
                busy = false;
            }
        });
    }

    function animateIcon(opening) {
        gsap.to(toggleIcon, {
            rotate: opening ? 225 : 0,
            duration: opening ? 0.8 : 0.35,
            ease: opening ? 'power4.out' : 'power3.inOut',
            overwrite: 'auto'
        });
    }

    function animateText(opening) {
        if (!toggleText) return;
        gsap.to(toggleText, {
            yPercent: opening ? -50 : 0,
            duration: 0.4,
            ease: 'power3.out',
            overwrite: 'auto'
        });
    }

    function toggleMenu() {
        isOpen = !isOpen;
        toggle.setAttribute('aria-expanded', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';

        if (isOpen) {
            backdrop.classList.add('open');
            playOpen();
        } else {
            backdrop.classList.remove('open');
            playClose();
        }
        animateIcon(isOpen);
        animateText(isOpen);
    }

    function closeMenu() {
        if (!isOpen) return;
        isOpen = false;
        toggle.setAttribute('aria-expanded', 'false');
        backdrop.classList.remove('open');
        document.body.style.overflow = '';
        playClose();
        animateIcon(false);
        animateText(false);
    }

    toggle.addEventListener('click', toggleMenu);
    backdrop.addEventListener('click', closeMenu);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) closeMenu();
    });

    panel.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            document.body.style.overflow = '';
        });
    });
})();

// Dark mode toggle
(function() {
    var btn = document.getElementById('appThemeToggle');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var html = document.documentElement;
        var current = html.getAttribute('data-theme') || 'light';
        var next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        html.classList.toggle('dark', next === 'dark');
        try { localStorage.setItem('siwarga-theme', next); } catch(e) {}
    });
})();
</script>