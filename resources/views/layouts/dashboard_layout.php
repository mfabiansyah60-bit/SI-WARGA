<?php
// resources/views/layouts/dashboard_layout.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($pdo)) {
    require_once __DIR__ . '/../../../config/database.php';
}

$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'warga';
$roleName = [
    'super_admin' => 'Super Admin',
    'desa'        => 'Admin Desa',
    'rw'          => 'Ketua RW',
    'rt'          => 'Ketua RT',
    'warga'       => 'Warga',
][$role] ?? 'User';

$page_title = $page_title ?? 'Dashboard';
$userName   = $user['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — SIWARGA</title>

<script>
(function(){
    try {
        var t = localStorage.getItem('siwarga-theme');
        if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', t);
        if (t === 'dark') document.documentElement.classList.add('dark');
    } catch(e) {}
})();
</script>

<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' };</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

<style>
    /* ============ VARIABLES ============ */
    :root {
        --bg-app:         #f1f5f9;
        --bg-card:        #ffffff;
        --bg-elevated:    #ffffff;
        --bg-soft:        #f8fafc;
        --bg-hover:       #f1f5f9;
        --border:         #e8edf3;
        --border-soft:    #f1f5f9;
        --text-primary:   #0f172a;
        --text-secondary: #475569;
        --text-muted:     #64748b;
        --text-faint:     #94a3b8;
        --shadow-sm:      0 1px 3px rgba(15,23,42,.04);
        --shadow-md:      0 4px 12px rgba(15,23,42,.08);
    }
    [data-theme="dark"] {
        --bg-app:         #0b1220;
        --bg-card:        #131c2e;
        --bg-elevated:    #1a2540;
        --bg-soft:        #0f1829;
        --bg-hover:       #1a2540;
        --border:         #1f2c44;
        --border-soft:    #182338;
        --text-primary:   #f1f5f9;
        --text-secondary: #cbd5e1;
        --text-muted:     #94a3b8;
        --text-faint:     #64748b;
        --shadow-sm:      0 1px 3px rgba(0,0,0,.4);
        --shadow-md:      0 4px 12px rgba(0,0,0,.5);
    }

    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
        margin: 0;
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        background: var(--bg-app);
        color: var(--text-primary);
        -webkit-font-smoothing: antialiased;
        transition: background .3s ease, color .3s ease;
        position: relative;
    }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        font-size: 20px;
        line-height: 1;
        vertical-align: middle;
    }
    .glass-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    /* ============================================================
       BACKGROUND DECORATION — mesh gradient + subtle dots
    ============================================================ */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image:
            radial-gradient(circle at 12% 18%, rgba(99,102,241,.10) 0%, transparent 35%),
            radial-gradient(circle at 88% 12%, rgba(236,72,153,.08) 0%, transparent 35%),
            radial-gradient(circle at 78% 82%, rgba(16,185,129,.08) 0%, transparent 40%),
            radial-gradient(circle at 20% 88%, rgba(245,158,11,.06) 0%, transparent 35%);
        transition: opacity .3s ease;
    }
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image: radial-gradient(circle, rgba(15,23,42,.06) 1px, transparent 1px);
        background-size: 24px 24px;
        opacity: .5;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
        -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }
    [data-theme="dark"] body::before {
        background-image:
            radial-gradient(circle at 12% 18%, rgba(99,102,241,.18) 0%, transparent 40%),
            radial-gradient(circle at 88% 12%, rgba(236,72,153,.12) 0%, transparent 40%),
            radial-gradient(circle at 78% 82%, rgba(16,185,129,.12) 0%, transparent 45%),
            radial-gradient(circle at 20% 88%, rgba(245,158,11,.10) 0%, transparent 40%);
        opacity: .6;
    }
    [data-theme="dark"] body::after {
        background-image: radial-gradient(circle, rgba(255,255,255,.05) 1px, transparent 1px);
        opacity: .4;
    }

    /* ============ GLOBAL DARK MODE OVERRIDES ============ */
    [data-theme="dark"] .bg-white,
    [data-theme="dark"] .bg-\[#fff\],
    [data-theme="dark"] .bg-\[#ffffff\] { background-color: var(--bg-card) !important; }
    [data-theme="dark"] .bg-slate-50,
    [data-theme="dark"] .bg-slate-50\/50,
    [data-theme="dark"] .bg-slate-50\/70,
    [data-theme="dark"] .bg-slate-50\/80,
    [data-theme="dark"] .bg-gray-50,
    [data-theme="dark"] .bg-zinc-50 { background-color: var(--bg-soft) !important; }
    [data-theme="dark"] .bg-slate-100,
    [data-theme="dark"] .bg-gray-100 { background-color: var(--bg-elevated) !important; }
    [data-theme="dark"] .bg-slate-200 { background-color: #22304a !important; }

    [data-theme="dark"] .bg-blue-50,
    [data-theme="dark"] .bg-blue-100 { background-color: rgba(59,130,246,.15) !important; }
    [data-theme="dark"] .bg-emerald-50,
    [data-theme="dark"] .bg-emerald-100,
    [data-theme="dark"] .bg-green-50,
    [data-theme="dark"] .bg-green-100 { background-color: rgba(16,185,129,.15) !important; }
    [data-theme="dark"] .bg-rose-50,
    [data-theme="dark"] .bg-rose-100,
    [data-theme="dark"] .bg-red-50,
    [data-theme="dark"] .bg-red-100 { background-color: rgba(239,68,68,.15) !important; }
    [data-theme="dark"] .bg-amber-50,
    [data-theme="dark"] .bg-amber-100,
    [data-theme="dark"] .bg-yellow-50,
    [data-theme="dark"] .bg-yellow-100 { background-color: rgba(245,158,11,.15) !important; }
    [data-theme="dark"] .bg-purple-50,
    [data-theme="dark"] .bg-purple-100,
    [data-theme="dark"] .bg-violet-50,
    [data-theme="dark"] .bg-violet-100 { background-color: rgba(139,92,246,.15) !important; }
    [data-theme="dark"] .bg-indigo-50,
    [data-theme="dark"] .bg-indigo-100 { background-color: rgba(99,102,241,.15) !important; }
    [data-theme="dark"] .bg-pink-50,
    [data-theme="dark"] .bg-pink-100 { background-color: rgba(236,72,153,.15) !important; }
    [data-theme="dark"] .bg-cyan-50,
    [data-theme="dark"] .bg-cyan-100 { background-color: rgba(6,182,212,.15) !important; }

    [data-theme="dark"] .text-slate-900,
    [data-theme="dark"] .text-slate-800,
    [data-theme="dark"] .text-zinc-900,
    [data-theme="dark"] .text-zinc-800,
    [data-theme="dark"] .text-gray-900,
    [data-theme="dark"] .text-gray-800 { color: var(--text-primary) !important; }
    [data-theme="dark"] .text-slate-700,
    [data-theme="dark"] .text-gray-700 { color: var(--text-secondary) !important; }
    [data-theme="dark"] .text-slate-600,
    [data-theme="dark"] .text-gray-600 { color: var(--text-muted) !important; }
    [data-theme="dark"] .text-slate-500,
    [data-theme="dark"] .text-gray-500 { color: var(--text-muted) !important; }
    [data-theme="dark"] .text-slate-400,
    [data-theme="dark"] .text-gray-400 { color: var(--text-faint) !important; }
    [data-theme="dark"] .text-slate-300 { color: #64748b !important; }

    [data-theme="dark"] .text-blue-700,
    [data-theme="dark"] .text-blue-800,
    [data-theme="dark"] .text-blue-900 { color: #60a5fa !important; }
    [data-theme="dark"] .text-emerald-700,
    [data-theme="dark"] .text-emerald-800,
    [data-theme="dark"] .text-green-700,
    [data-theme="dark"] .text-green-800 { color: #34d399 !important; }
    [data-theme="dark"] .text-rose-700,
    [data-theme="dark"] .text-rose-800,
    [data-theme="dark"] .text-red-700,
    [data-theme="dark"] .text-red-800 { color: #f87171 !important; }
    [data-theme="dark"] .text-amber-700,
    [data-theme="dark"] .text-amber-800,
    [data-theme="dark"] .text-yellow-700 { color: #fbbf24 !important; }
    [data-theme="dark"] .text-purple-700,
    [data-theme="dark"] .text-purple-800,
    [data-theme="dark"] .text-violet-700,
    [data-theme="dark"] .text-violet-800 { color: #a78bfa !important; }
    [data-theme="dark"] .text-indigo-700,
    [data-theme="dark"] .text-indigo-800 { color: #818cf8 !important; }
    [data-theme="dark"] .text-pink-700 { color: #f472b6 !important; }
    [data-theme="dark"] .text-cyan-700 { color: #22d3ee !important; }

    [data-theme="dark"] .border-slate-100 { border-color: var(--border-soft) !important; }
    [data-theme="dark"] .border-slate-200,
    [data-theme="dark"] .border-gray-200 { border-color: var(--border) !important; }
    [data-theme="dark"] .border-slate-300 { border-color: var(--border) !important; }
    [data-theme="dark"] .divide-slate-100 > * + * { border-color: var(--border-soft) !important; }
    [data-theme="dark"] .divide-slate-200 > * + * { border-color: var(--border) !important; }

    [data-theme="dark"] .border-blue-200,
    [data-theme="dark"] .border-blue-100 { border-color: rgba(59,130,246,.3) !important; }
    [data-theme="dark"] .border-emerald-200,
    [data-theme="dark"] .border-emerald-100 { border-color: rgba(16,185,129,.3) !important; }
    [data-theme="dark"] .border-rose-200,
    [data-theme="dark"] .border-rose-100 { border-color: rgba(239,68,68,.3) !important; }
    [data-theme="dark"] .border-amber-200,
    [data-theme="dark"] .border-amber-100 { border-color: rgba(245,158,11,.3) !important; }
    [data-theme="dark"] .border-purple-200,
    [data-theme="dark"] .border-purple-100 { border-color: rgba(139,92,246,.3) !important; }
    [data-theme="dark"] .border-cyan-200 { border-color: rgba(6,182,212,.3) !important; }

    [data-theme="dark"] input,
    [data-theme="dark"] select,
    [data-theme="dark"] textarea {
        background-color: var(--bg-soft) !important;
        color: var(--text-primary) !important;
        border-color: var(--border) !important;
    }
    [data-theme="dark"] input:focus,
    [data-theme="dark"] select:focus,
    [data-theme="dark"] textarea:focus {
        background-color: var(--bg-elevated) !important;
        border-color: #3b82f6 !important;
    }
    [data-theme="dark"] input::placeholder,
    [data-theme="dark"] textarea::placeholder { color: var(--text-faint) !important; }
    [data-theme="dark"] select option {
        background: var(--bg-card) !important;
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] table { color: var(--text-secondary); }
    [data-theme="dark"] thead { background-color: rgba(255,255,255,.03) !important; }
    [data-theme="dark"] thead th { color: var(--text-muted) !important; border-color: var(--border) !important; }
    [data-theme="dark"] tbody td { border-color: var(--border-soft) !important; color: var(--text-secondary); }
    [data-theme="dark"] tbody tr:hover { background-color: rgba(255,255,255,.03) !important; }

    [data-theme="dark"] .shadow,
    [data-theme="dark"] .shadow-sm,
    [data-theme="dark"] .shadow-md { box-shadow: 0 2px 8px rgba(0,0,0,.4) !important; }
    [data-theme="dark"] .shadow-lg,
    [data-theme="dark"] .shadow-xl { box-shadow: 0 8px 24px rgba(0,0,0,.5) !important; }

    [data-theme="dark"] .backdrop-blur-xl,
    [data-theme="dark"] .backdrop-blur-lg { background-color: rgba(255,255,255,.05) !important; }

    [data-theme="dark"] .text-black { color: var(--text-primary) !important; }
    [data-theme="dark"] .bg-black { background-color: var(--bg-card) !important; }

    [data-theme="dark"] ::-webkit-scrollbar { width: 10px; height: 10px; }
    [data-theme="dark"] ::-webkit-scrollbar-track { background: var(--bg-soft); }
    [data-theme="dark"] ::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 5px;
        border: 2px solid var(--bg-soft);
    }
    [data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background: #475569; }

    /* ============ TOPBAR ============ */
    .app-topbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 68px;
        background: var(--bg-card);
        border-bottom: 1px solid var(--border);
        z-index: 100;
        display: flex;
        align-items: center;
        padding: 0 24px 0 130px;
        gap: 16px;
        transition: background .3s ease, border-color .3s ease;
    }
    .app-topbar-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -.3px;
        margin: 0;
        line-height: 1.2;
    }
    .app-topbar-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .app-topbar-sub strong { color: var(--text-primary); }
    .app-topbar-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }

    .app-status {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 11px;
        background: rgba(34,197,94,.1);
        border: 1px solid rgba(34,197,94,.3);
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 600;
        color: #15803d;
    }
    [data-theme="dark"] .app-status { color: #4ade80; }
    .app-status-dot {
        width: 6px; height: 6px;
        background: #22c55e;
        border-radius: 50%;
        animation: app-pulse 2s infinite;
    }
    @keyframes app-pulse { 0%,100%{opacity:1;} 50%{opacity:.5;} }

    .app-role-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 11px;
        border-radius: 9999px;
        font-size: 11px; font-weight: 700;
        letter-spacing: .3px;
        text-transform: uppercase;
    }
    .app-role-badge.super_admin { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .app-role-badge.desa        { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .app-role-badge.rw          { background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; }
    .app-role-badge.rt          { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
    .app-role-badge.warga       { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    [data-theme="dark"] .app-role-badge.super_admin { background:rgba(239,68,68,.15); color:#f87171; border-color:rgba(239,68,68,.35); }
    [data-theme="dark"] .app-role-badge.desa        { background:rgba(59,130,246,.15); color:#60a5fa; border-color:rgba(59,130,246,.35); }
    [data-theme="dark"] .app-role-badge.rw          { background:rgba(139,92,246,.15); color:#a78bfa; border-color:rgba(139,92,246,.35); }
    [data-theme="dark"] .app-role-badge.rt          { background:rgba(245,158,11,.15); color:#fbbf24; border-color:rgba(245,158,11,.35); }
    [data-theme="dark"] .app-role-badge.warga       { background:rgba(16,185,129,.15); color:#34d399; border-color:rgba(16,185,129,.35); }

    .app-icon-btn {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: transparent;
        border: 1px solid transparent;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer;
        color: var(--text-muted);
        transition: all .18s;
        position: relative;
        text-decoration: none;
        font-family: inherit;
    }
    .app-icon-btn:hover {
        background: var(--bg-soft);
        border-color: var(--border);
        color: var(--text-primary);
    }
    .app-icon-btn .notif-dot {
        position: absolute;
        top: 8px; right: 8px;
        width: 8px; height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid var(--bg-card);
    }

    #appThemeToggle {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: transparent;
        border: 1px solid transparent;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer;
        color: var(--text-muted);
        transition: all .18s;
        font-family: inherit;
        position: relative;
        overflow: hidden;
    }
    #appThemeToggle:hover {
        background: var(--bg-soft);
        border-color: var(--border);
        color: var(--text-primary);
    }
    #appThemeToggle .theme-icon-wrap {
        position: relative;
        width: 20px; height: 20px;
        display: flex; align-items: center; justify-content: center;
    }
    #appThemeToggle .theme-icon {
        position: absolute;
        font-size: 20px;
        transition: transform .4s cubic-bezier(.4,0,.2,1), opacity .3s;
    }
    #appThemeToggle .icon-sun { opacity: 0; transform: rotate(-90deg) scale(.5); }
    #appThemeToggle .icon-moon { opacity: 1; transform: rotate(0) scale(1); }
    [data-theme="dark"] #appThemeToggle .icon-sun { opacity: 1; transform: rotate(0) scale(1); }
    [data-theme="dark"] #appThemeToggle .icon-moon { opacity: 0; transform: rotate(90deg) scale(.5); }

    .app-shell { padding-top: 68px; min-height: 100vh; position: relative; z-index: 1; }
    .app-content { padding: 24px; max-width: 1400px; margin: 0 auto; }

    @media (max-width: 640px) {
        .app-topbar { padding-left: 110px; padding-right: 16px; }
        .app-topbar-title { font-size: 14px; }
        .app-topbar-sub { font-size: 11px; }
        .app-content { padding: 16px; }
    }

    /* ============================================================
       DOCK-STYLE ACTION BUTTONS
    ============================================================ */
    .dock-actions {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
        align-items: center;
    }
    .dock-btn {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(180deg, var(--bg-card, #ffffff) 0%, var(--bg-soft, #f6f6f8) 100%);
        box-shadow:
            inset 0 0 0 1px var(--border, rgba(24,24,27,.08)),
            inset 0 1px 0 rgba(255, 255, 255, .9),
            0 2px 6px -2px rgba(15, 23, 42, .15);
        color: var(--text-muted, #64748b);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        font-family: inherit;
        transition:
            transform .18s cubic-bezier(.2, 1.25, .4, 1),
            box-shadow .18s ease,
            color .18s ease,
            background .18s ease;
        -webkit-tap-highlight-color: transparent;
        will-change: transform;
    }
    .dock-btn svg {
        width: 16px;
        height: 16px;
        stroke-width: 1.8;
        transition: transform .18s cubic-bezier(.2, 1.25, .4, 1);
    }
    .dock-btn:hover {
        transform: translateY(-2px) scale(1.06);
        color: var(--text-primary, #0f172a);
        box-shadow:
            inset 0 0 0 1px var(--border, rgba(24,24,27,.1)),
            inset 0 1px 0 rgba(255, 255, 255, .95),
            0 6px 14px -4px rgba(15, 23, 42, .2);
    }
    .dock-btn:hover svg { transform: scale(1.12); }
    .dock-btn:active { transform: translateY(-1px) scale(1.02); transition-duration: .08s; }
    .dock-btn:focus-visible { outline: 2px solid #3b82f6; outline-offset: 2px; }

    .dock-btn.dock-view:hover {
        color: #2563eb;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        box-shadow:
            inset 0 0 0 1px rgba(59, 130, 246, .3),
            inset 0 1px 0 rgba(255, 255, 255, .95),
            0 6px 14px -4px rgba(37, 99, 235, .35);
    }
    .dock-btn.dock-edit:hover {
        color: #d97706;
        background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
        box-shadow:
            inset 0 0 0 1px rgba(245, 158, 11, .3),
            inset 0 1px 0 rgba(255, 255, 255, .95),
            0 6px 14px -4px rgba(217, 119, 6, .35);
    }
    .dock-btn.dock-del:hover {
        color: #dc2626;
        background: linear-gradient(180deg, #fef2f2 0%, #fee2e2 100%);
        box-shadow:
            inset 0 0 0 1px rgba(239, 68, 68, .3),
            inset 0 1px 0 rgba(255, 255, 255, .95),
            0 6px 14px -4px rgba(220, 38, 38, .35);
    }
    .dock-btn.dock-btn-disabled,
    .dock-btn[disabled] {
        opacity: .45;
        cursor: not-allowed;
        transform: none !important;
    }
    .dock-btn.dock-btn-disabled:hover,
    .dock-btn[disabled]:hover {
        transform: none;
        color: var(--text-faint, #94a3b8);
        background: linear-gradient(180deg, var(--bg-card, #ffffff) 0%, var(--bg-soft, #f6f6f8) 100%);
        box-shadow:
            inset 0 0 0 1px var(--border, rgba(24,24,27,.08)),
            inset 0 1px 0 rgba(255, 255, 255, .9),
            0 2px 6px -2px rgba(15, 23, 42, .15);
    }

    [data-theme="dark"] .dock-btn,
    .dark .dock-btn {
        background: linear-gradient(180deg, rgba(58, 54, 68, .8) 0%, rgba(39, 36, 47, .9) 100%);
        box-shadow:
            inset 0 0 0 1px rgba(255, 255, 255, .07),
            inset 0 1px 0 rgba(255, 255, 255, .1),
            0 2px 6px -2px rgba(0, 0, 0, .5);
        color: #94a3b8;
    }
    [data-theme="dark"] .dock-btn:hover,
    .dark .dock-btn:hover {
        color: #f1f5f9;
        background: linear-gradient(180deg, rgba(70, 65, 82, .9) 0%, rgba(48, 44, 58, .95) 100%);
        box-shadow:
            inset 0 0 0 1px rgba(255, 255, 255, .12),
            inset 0 1px 0 rgba(255, 255, 255, .15),
            0 6px 14px -4px rgba(0, 0, 0, .6);
    }
    [data-theme="dark"] .dock-btn.dock-view:hover,
    .dark .dock-btn.dock-view:hover {
        color: #60a5fa;
        background: linear-gradient(180deg, rgba(59, 130, 246, .2) 0%, rgba(37, 99, 235, .25) 100%);
    }
    [data-theme="dark"] .dock-btn.dock-edit:hover,
    .dark .dock-btn.dock-edit:hover {
        color: #fbbf24;
        background: linear-gradient(180deg, rgba(245, 158, 11, .2) 0%, rgba(217, 119, 6, .25) 100%);
    }
    [data-theme="dark"] .dock-btn.dock-del:hover,
    .dark .dock-btn.dock-del:hover {
        color: #f87171;
        background: linear-gradient(180deg, rgba(239, 68, 68, .2) 0%, rgba(220, 38, 38, .25) 100%);
    }

    /* ============================================================
       NOTIFICATION DROPDOWN
    ============================================================ */
    .notif-wrapper { position: relative; }
    .notif-badge {
        position: absolute;
        top: 4px; right: 4px;
        min-width: 18px; height: 18px;
        padding: 0 5px;
        background: #ef4444;
        color: white;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--bg-card);
        box-shadow: 0 0 0 2px rgba(239,68,68,.2);
        animation: notif-pop .3s ease;
    }
    @keyframes notif-pop {
        0% { transform: scale(0); }
        60% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    .notif-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 380px;
        max-width: calc(100vw - 32px);
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 20px 40px -12px rgba(15,23,42,.25), 0 0 0 1px rgba(15,23,42,.04);
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px) scale(.96);
        transform-origin: top right;
        transition: all .2s cubic-bezier(.2,1.25,.4,1);
        z-index: 200;
    }
    .notif-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }
    .notif-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-soft);
    }
    .notif-header h3 { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .notif-header p { font-size: 11px; color: var(--text-muted); margin: 2px 0 0; }
    .notif-action-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--bg-card);
        color: var(--text-muted);
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .15s;
    }
    .notif-action-btn:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .notif-action-btn .material-symbols-outlined { font-size: 16px; }
    .notif-list { max-height: 400px; overflow-y: auto; }
    .notif-item {
        display: flex;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        text-decoration: none;
        color: inherit;
        transition: background .15s;
        cursor: pointer;
        position: relative;
    }
    .notif-item:hover { background: var(--bg-soft); }
    .notif-item.unread { background: rgba(59,130,246,.05); }
    .notif-item.unread:hover { background: rgba(59,130,246,.1); }
    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        background: #3b82f6;
    }
    .notif-icon {
        flex-shrink: 0;
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: var(--bg-soft);
        color: var(--text-muted);
    }
    .notif-icon .material-symbols-outlined { font-size: 18px; }
    .notif-icon.type-pengaduan_baru { background: rgba(59,130,246,.15); color: #2563eb; }
    .notif-icon.type-pengaduan_update { background: rgba(16,185,129,.15); color: #059669; }
    .notif-body { flex: 1; min-width: 0; }
    .notif-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin: 0 0 2px; line-height: 1.3; }
    .notif-message {
        font-size: 12px; color: var(--text-muted); line-height: 1.4;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .notif-time { font-size: 10px; color: var(--text-faint); margin-top: 4px; font-weight: 600; }
    .notif-empty { padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 13px; }
    .notif-empty .material-symbols-outlined { font-size: 40px; color: var(--text-faint); display: block; margin: 0 auto 8px; }
    .notif-footer { padding: 10px; border-top: 1px solid var(--border); text-align: center; background: var(--bg-soft); }
    .notif-footer button {
        width: 100%; padding: 8px; border: none; background: transparent;
        color: #ef4444; font-size: 12px; font-weight: 700; cursor: pointer;
        border-radius: 8px; font-family: inherit; transition: all .15s;
    }
    .notif-footer button:hover { background: rgba(239,68,68,.1); }
    @media (max-width: 480px) {
        .notif-dropdown { width: calc(100vw - 24px); right: -8px; }
    }
</style>
</head>
<body>

<header class="app-topbar">
    <div>
        <h1 class="app-topbar-title"><?= htmlspecialchars($page_title) ?></h1>
        <div class="app-topbar-sub">Selamat datang, <strong><?= htmlspecialchars(explode(' ', $userName)[0]) ?></strong> 👋</div>
    </div>
    <div class="app-topbar-right">
        <span class="app-status">
            <span class="app-status-dot"></span>
            Online
        </span>
        <span class="app-role-badge <?= htmlspecialchars($role) ?>">
            <span class="material-symbols-outlined" style="font-size:14px">shield_person</span>
            <span><?= htmlspecialchars($roleName) ?></span>
        </span>

        <button id="appThemeToggle" type="button" title="Ganti Tema" aria-label="Ganti Tema" onclick="window.toggleTheme && window.toggleTheme()" style="position:relative;z-index:9999;cursor:pointer;">
            <span class="theme-icon-wrap">
                <span class="material-symbols-outlined theme-icon icon-moon">dark_mode</span>
                <span class="material-symbols-outlined theme-icon icon-sun">light_mode</span>
            </span>
        </button>

        <!-- ============ NOTIFIKASI ============ -->
        <div class="notif-wrapper" id="notifWrapper">
            <button type="button" class="app-icon-btn" id="notifBell" title="Notifikasi">
                <span class="material-symbols-outlined">notifications</span>
                <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <div>
                        <h3>Notifikasi</h3>
                        <p id="notifUnreadText">0 belum dibaca</p>
                    </div>
                    <button type="button" id="notifReadAll" class="notif-action-btn" title="Tandai semua dibaca">
                        <span class="material-symbols-outlined">done_all</span>
                    </button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Memuat...</div>
                </div>
                <div class="notif-footer">
                    <button type="button" id="notifClearAll">Hapus Semua</button>
                </div>
            </div>
        </div>
        <!-- ============ /NOTIFIKASI ============ -->
    </div>
</header>

<?php include __DIR__ . '/sidebar.php'; ?>

<main class="app-shell">
    <div class="app-content">