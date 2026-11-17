<?php
// resources/views/ai/chat.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../app/AI/RoleCapabilities.php';

$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'warga';
$roleName = RoleCapabilities::label($role);
$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

$page_title = 'AI Chat Assistant';

if (!$isEmbed) {
    include __DIR__ . '/../layouts/dashboard_layout.php';
} else {
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — SIWARGA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<script>
(function(){
    try {
        var t = localStorage.getItem('siwarga-theme') || (window.parent && window.parent.document.documentElement.getAttribute('data-theme')) || 'light';
        document.documentElement.setAttribute('data-theme', t);
        if (t === 'dark') document.documentElement.classList.add('dark');
    } catch(e) {}
})();
</script>
</head>
<body class="ai-embed-body">
<?php } ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root {
    --ai-card:          var(--bg-card, #ffffff);
    --ai-border:        var(--border, #e2e8f0);
    --ai-text:          var(--text-primary, #0f172a);
    --ai-muted:         var(--text-muted, #64748b);
    --ai-faint:         var(--text-faint, #94a3b8);
    --ai-accent-1:      #3b82f6;
    --ai-accent-2:      #2563eb;
    --ai-accent-3:      #1d4ed8;
    --ai-accent-rgb:    37, 99, 235;
    --ai-bubble-ai:     var(--bg-soft, #f8fafc);
    --ai-bubble-user:   linear-gradient(135deg, #2563eb, #4f46e5);
}

[data-theme="dark"] {
    --ai-card:          var(--bg-card, #131c2e);
    --ai-border:        var(--border, #1f2c44);
    --ai-text:          var(--text-primary, #f1f5f9);
    --ai-muted:         var(--text-muted, #94a3b8);
    --ai-faint:         var(--text-faint, #64748b);
    --ai-accent-1:      #60a5fa;
    --ai-accent-2:      #3b82f6;
    --ai-accent-3:      #2563eb;
    --ai-bubble-ai:     var(--bg-soft, #1a2540);
    --ai-bubble-user:   linear-gradient(135deg, #3b82f6, #6366f1);
}

/* Base Container */
.ai-wrap {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    box-sizing: border-box;
}

/* Embed Mode Layout (100% Height & Fixed Bottom Input) */
body.ai-embed-body {
    margin: 0;
    padding: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: var(--ai-card);
    color: var(--ai-text);
}

body.ai-embed-body .ai-wrap {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

body.ai-embed-body .ai-header {
    display: none !important;
}

body.ai-embed-body .ai-chat-card {
    height: 100% !important;
    min-height: 0 !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    flex: 1;
}

/* Standalone Page Header */
.ai-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 22px;
    background: var(--ai-card);
    border: 1px solid var(--ai-border);
    border-radius: 16px 16px 0 0;
    border-bottom: none;
    transition: all .25s ease;
}

.ai-brand { display: flex; align-items: center; gap: 14px; }
.ai-brand-mark {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
}
.ai-brand-mark span { font-size: 24px; }

.ai-brand-name {
    font-size: 16px;
    font-weight: 800;
    color: var(--ai-text);
    letter-spacing: -.3px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.ai-brand-sub {
    font-size: 12px;
    color: var(--ai-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}

.ai-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 8px #22c55e;
}

.ai-role-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
}
.ai-role-badge.super_admin { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
.ai-role-badge.desa        { background: rgba(37, 99, 235, 0.15); color: #2563eb; }
.ai-role-badge.rw          { background: rgba(147, 51, 234, 0.15); color: #9333ea; }
.ai-role-badge.rt          { background: rgba(245, 158, 11, 0.15); color: #d97706; }
.ai-role-badge.warga       { background: rgba(16, 185, 129, 0.15); color: #059669; }

.ai-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    color: var(--ai-muted);
    border: 1px solid var(--ai-border);
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s ease;
    text-decoration: none;
}
.ai-btn-ghost:hover {
    background: var(--bg-soft, #f8fafc);
    color: var(--ai-text);
    border-color: var(--ai-faint, #cbd5e1);
}

/* Header Action Buttons */
.ai-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s ease;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}
.ai-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    color: #ffffff;
}

.ai-count-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 6px;
    border-radius: 9999px;
    background: rgba(37, 99, 235, 0.15);
    color: var(--ai-accent-2);
    font-size: 11px;
    font-weight: 700;
}
[data-theme="dark"] .ai-count-pill {
    background: rgba(96, 165, 250, 0.2);
    color: #93c5fd;
}

/* Embed Topbar */
.ai-embed-topbar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--ai-card);
    border-bottom: 1px solid var(--ai-border);
    flex-shrink: 0;
}
body.ai-embed-body .ai-embed-topbar {
    display: flex;
}

.ai-btn-mini {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--bg-soft, #f8fafc);
    color: var(--ai-text);
    border: 1px solid var(--ai-border);
    border-radius: 8px;
    padding: 5px 9px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s ease;
}
.ai-btn-mini:hover {
    background: var(--ai-card);
    border-color: var(--ai-accent-1);
    color: var(--ai-accent-2);
}

/* ============================================================ */
/* SLIDE-OVER DRAWER RIWAYAT CHAT */
/* ============================================================ */
.ai-drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9998;
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease;
}
.ai-drawer-backdrop.open {
    opacity: 1;
    pointer-events: auto;
}

.ai-drawer {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 390px;
    max-width: 90vw;
    background: var(--ai-card);
    border-left: 1px solid var(--ai-border);
    z-index: 9999;
    box-shadow: -8px 0 32px rgba(15, 23, 42, 0.18);
    transform: translateX(100%);
    transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}
.ai-drawer.open {
    transform: translateX(0);
}

.ai-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid var(--ai-border);
    background: var(--ai-card);
    flex-shrink: 0;
}
.ai-drawer-title-box {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ai-drawer-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(37, 99, 235, 0.12);
    color: var(--ai-accent-2);
    display: flex;
    align-items: center;
    justify-content: center;
}
.ai-drawer-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--ai-text);
    margin: 0;
}
.ai-drawer-sub {
    font-size: 11.5px;
    color: var(--ai-muted);
    margin: 0;
}
.ai-drawer-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid var(--ai-border);
    color: var(--ai-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
}
.ai-drawer-close:hover {
    background: var(--bg-soft, #f8fafc);
    color: var(--ai-text);
}

.ai-drawer-actions {
    padding: 14px 20px 10px;
    flex-shrink: 0;
}
.ai-btn-new-chat-drawer {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}
.ai-btn-new-chat-drawer:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
}

.ai-drawer-search {
    padding: 0 20px 12px;
    flex-shrink: 0;
}
.ai-search-shell {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-soft, #f8fafc);
    border: 1px solid var(--ai-border);
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
}
.ai-search-shell input {
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 12px;
    color: var(--ai-text);
    outline: none;
    width: 100%;
}

.ai-session-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    box-sizing: border-box;
}
.ai-session-list::-webkit-scrollbar { width: 5px; }
.ai-session-list::-webkit-scrollbar-thumb { background: var(--ai-border); border-radius: 4px; }

.ai-session-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    transition: all .15s ease;
    position: relative;
    text-align: left;
    width: 100%;
    box-sizing: border-box;
}
.ai-session-item:hover {
    background: var(--bg-soft, #f8fafc);
    border-color: var(--ai-border);
}
.ai-session-item.active {
    background: rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.3);
}
[data-theme="dark"] .ai-session-item.active {
    background: rgba(37, 99, 235, 0.16);
    border-color: rgba(37, 99, 235, 0.4);
}
.ai-session-item-icon {
    font-size: 18px;
    color: var(--ai-muted);
    flex-shrink: 0;
}
.ai-session-item.active .ai-session-item-icon {
    color: var(--ai-accent-2);
}
.ai-session-item-content {
    flex: 1;
    min-width: 0;
}
.ai-session-item-title {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--ai-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.35;
}
.ai-session-item-meta {
    font-size: 11px;
    color: var(--ai-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}
.ai-session-item-delete {
    opacity: 0;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: transparent;
    border: none;
    color: var(--ai-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
    flex-shrink: 0;
}
.ai-session-item:hover .ai-session-item-delete,
.ai-session-item:focus-within .ai-session-item-delete {
    opacity: 1;
}
.ai-session-item-delete:hover {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.ai-drawer-empty {
    padding: 36px 16px;
    text-align: center;
    color: var(--ai-muted);
    font-size: 12px;
}
.ai-drawer-empty .material-symbols-outlined {
    font-size: 32px;
    color: var(--ai-faint);
    margin-bottom: 8px;
    display: block;
}

.ai-drawer-footer {
    padding: 12px 20px 16px;
    border-top: 1px solid var(--ai-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: var(--ai-card);
    flex-shrink: 0;
}
.ai-btn-drawer-danger {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: transparent;
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
}
.ai-btn-drawer-danger:hover {
    background: rgba(239, 68, 68, 0.1);
}
.ai-link-drawer-full {
    font-size: 11.5px;
    color: var(--ai-accent-2);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}
.ai-link-drawer-full:hover {
    text-decoration: underline;
}

/* Chat Card Container */
.ai-chat-card {
    background: var(--ai-card);
    border: 1px solid var(--ai-border);
    border-radius: 0 0 16px 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 220px);
    min-height: 520px;
    box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
    position: relative;
    box-sizing: border-box;
}

/* Chat Body (Scrollable) */
.ai-chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: radial-gradient(circle at 10% 0%, rgba(var(--ai-accent-rgb), 0.03), transparent 45%),
                radial-gradient(circle at 90% 100%, rgba(var(--ai-accent-rgb), 0.03), transparent 45%);
    scroll-behavior: smooth;
    box-sizing: border-box;
}
.ai-chat-body::-webkit-scrollbar { width: 6px; }
.ai-chat-body::-webkit-scrollbar-thumb { background: var(--ai-border); border-radius: 4px; }
.ai-chat-body::-webkit-scrollbar-thumb:hover { background: var(--ai-faint); }

/* Messages */
.ai-msg {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    max-width: 88%;
    animation: aiMsgIn .25s cubic-bezier(.16, 1, .3, 1);
}
@keyframes aiMsgIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.ai-msg.ai { align-self: flex-start; }
.ai-msg.user { align-self: flex-end; flex-direction: row-reverse; }

.ai-msg-avatar {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
}
.ai-msg.ai .ai-msg-avatar {
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    color: #fff;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3);
}
.ai-msg.user .ai-msg-avatar {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    box-shadow: 0 3px 8px rgba(59, 130, 246, 0.3);
}

.ai-bubble {
    padding: 12px 16px;
    font-size: 14px;
    line-height: 1.6;
    color: var(--ai-text);
    word-break: break-word;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.ai-msg.ai .ai-bubble {
    background: var(--ai-bubble-ai);
    border: 1px solid var(--ai-border);
    border-top-left-radius: 4px;
}
.ai-msg.user .ai-bubble {
    background: var(--ai-bubble-user);
    color: #ffffff;
    border-top-right-radius: 4px;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.ai-msg.user .ai-bubble strong { color: #ffffff; }
.ai-msg.ai .ai-bubble strong { color: var(--ai-accent-3); font-weight: 700; }
[data-theme="dark"] .ai-msg.ai .ai-bubble strong { color: #93c5fd; }

.ai-inline-link {
    color: #2563eb;
    text-decoration: underline;
    font-weight: 600;
}
[data-theme="dark"] .ai-inline-link { color: #60a5fa; }

/* Table styling inside chat */
.ai-table-scroll {
    overflow-x: auto;
    margin: 10px 0;
    border: 1px solid var(--ai-border);
    border-radius: 8px;
}
.ai-table-scroll table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
.ai-table-scroll th {
    background: rgba(37, 99, 235, 0.08);
    padding: 8px 12px;
    font-weight: 700;
    border-bottom: 1px solid var(--ai-border);
}
.ai-table-scroll td {
    padding: 8px 12px;
    border-bottom: 1px solid var(--ai-border);
}

/* Bullet list item */
.ai-list-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 4px 0;
}
.ai-bullet {
    color: var(--ai-accent-2);
    font-weight: 800;
    line-height: 1.6;
}

/* Interactive Action Buttons inside AI response */
.btn-buat-surat {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    color: #ffffff !important;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    margin-top: 10px;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}
.btn-buat-surat:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.4);
}

/* Typing Indicator */
.ai-typing-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}
.ai-typing-dots { display: flex; gap: 4px; }
.ai-typing-dots span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--ai-accent-2);
    opacity: 0.4;
    animation: aiTyping 1.3s infinite;
}
.ai-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes aiTyping { 0%, 60%, 100% { transform: translateY(0); opacity: 0.4; } 30% { transform: translateY(-4px); opacity: 1; } }
.ai-typing-text { font-size: 12px; color: var(--ai-muted); font-style: italic; }

/* Quick Suggestion Chips */
.ai-suggestions {
    padding: 8px 18px 10px;
    background: var(--ai-card);
    border-top: 1px solid var(--ai-border);
    display: flex;
    gap: 8px;
    overflow-x: auto;
    white-space: nowrap;
    flex-shrink: 0;
    -webkit-overflow-scrolling: touch;
}
.ai-suggestions::-webkit-scrollbar { height: 4px; }
.ai-suggestions::-webkit-scrollbar-thumb { background: var(--ai-border); border-radius: 4px; }

.ai-suggestion {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--bg-soft, #f8fafc);
    border-radius: 9999px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid var(--ai-border);
    color: var(--ai-text);
    transition: all .15s ease;
    flex-shrink: 0;
}
.ai-suggestion:hover {
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    color: #fff;
    border-color: transparent;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}
.ai-suggestion:hover .material-symbols-outlined { color: #fff; }
.ai-suggestion .material-symbols-outlined { font-size: 14px; color: var(--ai-accent-2); }
[data-theme="dark"] .ai-suggestion .material-symbols-outlined { color: #60a5fa; }

/* Image Attachment Preview */
.ai-img-preview {
    display: none;
    padding: 8px 18px;
    background: var(--bg-soft, #f8fafc);
    border-top: 1px solid var(--ai-border);
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.ai-img-preview.active { display: flex; }
.ai-img-preview img {
    height: 48px;
    width: 48px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--ai-border);
}
.ai-img-remove {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: transparent;
    color: var(--ai-muted);
    border: 1px solid var(--ai-border);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all .15s;
}
.ai-img-remove:hover { color: #ef4444; border-color: #ef4444; background: rgba(239, 68, 68, .1); }

/* Input Shell (Fixed at bottom) */
.ai-input-area {
    padding: 10px 18px 14px;
    background: var(--ai-card);
    border-top: 1px solid var(--ai-border);
    flex-shrink: 0;
    box-sizing: border-box;
}

.ai-input-shell {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-soft, #f8fafc);
    border: 1.5px solid var(--ai-border);
    border-radius: 14px;
    padding: 5px 6px 5px 12px;
    transition: all .2s ease;
    box-sizing: border-box;
}
.ai-input-shell:focus-within {
    border-color: var(--ai-accent-2);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    background: var(--ai-card);
}

.ai-btn-upload {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: transparent;
    border: none;
    color: var(--ai-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .15s;
}
.ai-btn-upload:hover {
    color: var(--ai-accent-2);
    background: rgba(37, 99, 235, 0.1);
}
.ai-btn-upload .material-symbols-outlined { font-size: 20px; }

.ai-input {
    flex: 1;
    padding: 9px 4px;
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 14px;
    color: var(--ai-text);
    outline: none;
    line-height: 1.4;
    min-width: 0;
}
.ai-input::placeholder { color: var(--ai-faint); }

.ai-btn-send {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: linear-gradient(135deg, #1d4ed8, #4f46e5);
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .2s ease;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3);
}
.ai-btn-send:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(37, 99, 235, 0.45);
}
.ai-btn-send:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
.ai-btn-send .material-symbols-outlined { font-size: 20px; }

.ai-caption {
    text-align: center;
    padding: 0 0 6px 0;
    font-size: 11px;
    color: var(--ai-faint);
    background: var(--ai-card);
    flex-shrink: 0;
}

@media (max-width: 640px) {
    .ai-msg { max-width: 94%; }
    .ai-chat-body { padding: 14px; }
    .ai-input-area { padding: 8px 12px 10px; }
    .ai-suggestions { padding: 6px 12px 8px; }
}
</style>

<div class="ai-wrap">
    <?php if (!$isEmbed): ?>
    <div class="ai-header">
        <div class="ai-brand">
            <div class="ai-brand-mark">
                <span class="material-symbols-outlined">smart_toy</span>
            </div>
            <div>
                <h1 class="ai-brand-name">
                    SIWARGA AI 
                    <span class="ai-role-badge <?= htmlspecialchars($role) ?>"><?= $roleName ?></span>
                </h1>
                <div class="ai-brand-sub">
                    <span class="ai-status-dot"></span>
                    <span>Online · Siap membantu 24/7</span>
                </div>
            </div>
        </div>
        <div class="ai-header-actions">
            <button type="button" onclick="startNewChat()" class="ai-btn-primary" title="Mulai Obrolan Baru">
                <span class="material-symbols-outlined" style="font-size:18px">add_comment</span>
                <span class="ai-btn-text">Chat Baru</span>
            </button>
            <button type="button" onclick="toggleDrawer(true)" class="ai-btn-ghost" title="Buka Riwayat Percakapan">
                <span class="material-symbols-outlined">history</span>
                <span>Riwayat</span>
                <span class="ai-count-pill" id="headerHistoryCount"><?= count($daftarSesi ?? []) ?></span>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="ai-chat-card">
        <!-- Embed Mode Topbar -->
        <div class="ai-embed-topbar">
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;color:var(--ai-accent-1);">smart_toy</span>
                <span style="font-weight:700;font-size:13px;">SIWARGA AI</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <button type="button" onclick="startNewChat()" class="ai-btn-mini" title="Mulai Obrolan Baru">
                    <span class="material-symbols-outlined" style="font-size:16px;">add</span>
                    <span>Baru</span>
                </button>
                <button type="button" onclick="toggleDrawer(true)" class="ai-btn-mini" title="Riwayat Chat">
                    <span class="material-symbols-outlined" style="font-size:16px;">history</span>
                    <span id="embedHistoryCount"><?= count($daftarSesi ?? []) ?></span>
                </button>
            </div>
        </div>

        <div class="ai-chat-body" id="chatBody"></div>

        <!-- Suggestions -->
        <div class="ai-suggestions" id="quickSuggestions">
            <?php if ($role === 'super_admin'): ?>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa surat yang masih menunggu persetujuan?')"><span class="material-symbols-outlined">description</span><span>Surat Pending</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa pengaduan pending?')"><span class="material-symbols-outlined">campaign</span><span>Pengaduan Pending</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('RW mana yang warganya paling banyak?')"><span class="material-symbols-outlined">analytics</span><span>RW Terpadat</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Ada berapa RT dan RW?')"><span class="material-symbols-outlined">apartment</span><span>RT &amp; RW</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Total warga desa')"><span class="material-symbols-outlined">group</span><span>Total Warga</span></button>
            <?php elseif ($role === 'desa'): ?>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa surat yang masih menunggu persetujuan?')"><span class="material-symbols-outlined">description</span><span>Surat Pending</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa pengaduan pending?')"><span class="material-symbols-outlined">campaign</span><span>Pengaduan Pending</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('RW mana yang warganya paling banyak?')"><span class="material-symbols-outlined">analytics</span><span>Ranking RW</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Katalog pelayanan desa')"><span class="material-symbols-outlined">grid_view</span><span>Layanan Desa</span></button>
            <?php elseif ($role === 'rw'): ?>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa surat yang masih menunggu persetujuan?')"><span class="material-symbols-outlined">description</span><span>Surat RW</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa warga di RW saya?')"><span class="material-symbols-outlined">group</span><span>Warga RW</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa pengaduan di RW saya?')"><span class="material-symbols-outlined">campaign</span><span>Pengaduan</span></button>
            <?php elseif ($role === 'rt'): ?>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa surat yang masih menunggu persetujuan?')"><span class="material-symbols-outlined">description</span><span>Surat RT</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Berapa warga di RT saya?')"><span class="material-symbols-outlined">group</span><span>Warga RT</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Pengaduan warga RT saya')"><span class="material-symbols-outlined">campaign</span><span>Pengaduan</span></button>
            <?php else: ?>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Status surat saya')"><span class="material-symbols-outlined">description</span><span>Status Surat</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Status pengaduan saya')"><span class="material-symbols-outlined">campaign</span><span>Status Aduan</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Syarat buat surat domisili')"><span class="material-symbols-outlined">info</span><span>Syarat Domisili</span></button>
                <button class="ai-suggestion" type="button" onclick="sendQuick('Pelayanan apa saja yang ada di desa?')"><span class="material-symbols-outlined">grid_view</span><span>Layanan Desa</span></button>
            <?php endif; ?>
        </div>

        <!-- Image Preview -->
        <div class="ai-img-preview" id="imgPreview">
            <img id="imgPreviewEl" src="" alt="Preview">
            <span style="font-size:12px;color:var(--ai-muted);flex:1">Foto lampiran siap dianalisis</span>
            <button class="ai-img-remove" onclick="removeImage()" type="button" title="Hapus foto">×</button>
        </div>

        <!-- Form Input (Always fixed at bottom) -->
        <form class="ai-input-area" id="chatForm" onsubmit="sendMessage(event)">
            <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="handleFile(this)">
            <div class="ai-input-shell">
                <button type="button" class="ai-btn-upload" onclick="document.getElementById('fileInput').click()" title="Lampirkan foto/gambar"><span class="material-symbols-outlined">add_photo_alternate</span></button>
                <input type="text" class="ai-input" id="msgInput" placeholder="Ketik pertanyaan seputar desa..." autocomplete="off">
                <button type="submit" class="ai-btn-send" id="btnSend" title="Kirim"><span class="material-symbols-outlined">arrow_upward</span></button>
            </div>
        </form>
    </div>
    
    <div class="ai-caption">Percakapan aman terhubung langsung dengan sistem SIWARGA 🔒</div>

    <!-- Slide-over Drawer Riwayat Chat -->
    <div class="ai-drawer-backdrop" id="drawerBackdrop" onclick="toggleDrawer(false)"></div>
    <aside class="ai-drawer" id="sessionDrawer" aria-label="Riwayat Percakapan">
        <div class="ai-drawer-header">
            <div class="ai-drawer-title-box">
                <div class="ai-drawer-icon">
                    <span class="material-symbols-outlined">history</span>
                </div>
                <div>
                    <h3 class="ai-drawer-title">Riwayat Chat</h3>
                    <p class="ai-drawer-sub">Tersimpan aman di akun Anda</p>
                </div>
            </div>
            <button type="button" class="ai-drawer-close" onclick="toggleDrawer(false)" title="Tutup Riwayat">
                <span class="material-symbols-outlined" style="font-size:18px">close</span>
            </button>
        </div>

        <div class="ai-drawer-actions">
            <button type="button" class="ai-btn-new-chat-drawer" onclick="startNewChat(); toggleDrawer(false);">
                <span class="material-symbols-outlined" style="font-size:18px">add</span>
                <span>+ Obrolan Baru</span>
            </button>
        </div>

        <div class="ai-drawer-search">
            <div class="ai-search-shell">
                <span class="material-symbols-outlined" style="font-size:16px;color:var(--ai-muted)">search</span>
                <input type="text" id="sessionSearchInput" placeholder="Cari topik percakapan..." oninput="filterSessions(this.value)">
            </div>
        </div>

        <div class="ai-session-list" id="sessionListEl">
            <!-- Di-render oleh JavaScript -->
        </div>

        <div class="ai-drawer-footer">
            <button type="button" class="ai-btn-drawer-danger" onclick="deleteAllSessions()" title="Hapus semua riwayat">
                <span class="material-symbols-outlined" style="font-size:15px">delete_sweep</span>
                <span>Hapus Semua</span>
            </button>
            <a href="/ai?action=riwayat" class="ai-link-drawer-full">
                <span>Halaman Penuh</span>
                <span class="material-symbols-outlined" style="font-size:15px">arrow_forward</span>
            </a>
        </div>
    </aside>
</div>

<script>
    let pendingImage = null;
    let isSending = false;
    let lastQuery = '';
    const chatBody = document.getElementById('chatBody');
    const msgInput = document.getElementById('msgInput');
    const userName = "<?= htmlspecialchars(explode(' ', $user['name'] ?? 'User')[0]) ?>";
    const userRole = "<?= $role ?>";

    let currentSessionId = <?= json_encode($activeSessionId ?? '') ?>;
    let sessionsList = <?= json_encode($daftarSesi ?? []) ?>;
    const initialMessages = <?= json_encode($initialMessages ?? []) ?>;
    const introMessage = <?= json_encode(RoleCapabilities::intro($role)) ?>;

    const AI_AVATAR_HTML = '<span class="material-symbols-outlined" style="font-size:20px;">smart_toy</span>';

    // Inisialisasi awal pesan chat
    if (initialMessages && initialMessages.length > 0) {
        chatBody.innerHTML = '';
        initialMessages.forEach(m => {
            if (m.pertanyaan) addMessage('user', m.pertanyaan);
            if (m.jawaban) {
                const isBlocked = (m.jawaban.indexOf('[DITOLAK]') === 0);
                const raw = isBlocked ? m.jawaban.substring(9).trim() : m.jawaban;
                addMessage('ai', raw);
            }
        });
    } else {
        addMessage('ai', introMessage);
    }
    renderSessionList(sessionsList);
    highlightActiveSession(currentSessionId);

    // ============================================================
    // DRAWER & SESSION MANAGEMENT
    // ============================================================
    function toggleDrawer(open) {
        const drawer = document.getElementById('sessionDrawer');
        const backdrop = document.getElementById('drawerBackdrop');
        if (!drawer || !backdrop) return;
        if (open) {
            drawer.classList.add('open');
            backdrop.classList.add('open');
            const searchInput = document.getElementById('sessionSearchInput');
            if (searchInput) { searchInput.value = ''; filterSessions(''); }
        } else {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
        }
    }

    function startNewChat() {
        currentSessionId = '';
        window.history.pushState(null, '', '/ai');
        chatBody.innerHTML = '';
        addMessage('ai', introMessage);
        highlightActiveSession('');
        if (window.innerWidth < 768) {
            toggleDrawer(false);
        }
        msgInput.value = '';
        msgInput.focus();
    }

    async function loadSession(sessionId) {
        if (!sessionId) return;
        currentSessionId = sessionId;
        window.history.pushState(null, '', '/ai?session_id=' + encodeURIComponent(sessionId));
        highlightActiveSession(sessionId);

        chatBody.innerHTML = '';
        showLoading();

        try {
            const res = await fetch('/ai?action=get_session&session_id=' + encodeURIComponent(sessionId));
            const data = await res.json();
            removeLoading();
            if (data.success && data.messages && data.messages.length > 0) {
                chatBody.innerHTML = '';
                data.messages.forEach(m => {
                    if (m.pertanyaan) addMessage('user', m.pertanyaan);
                    if (m.jawaban) {
                        const isBlocked = (m.jawaban.indexOf('[DITOLAK]') === 0);
                        const raw = isBlocked ? m.jawaban.substring(9).trim() : m.jawaban;
                        addMessage('ai', raw);
                    }
                });
            } else {
                addMessage('ai', 'Sesi ini belum memiliki percakapan.');
            }
        } catch(e) {
            removeLoading();
            showError('Gagal memuat sesi percakapan. Silakan coba beberapa saat lagi.');
        }

        if (window.innerWidth < 768) {
            toggleDrawer(false);
        }
        msgInput.focus();
    }

    async function deleteSession(sessionId, ev) {
        if (ev) ev.stopPropagation();
        if (!confirm('Yakin ingin menghapus sesi percakapan ini?')) return;

        const fd = new FormData();
        fd.append('session_id', sessionId);
        fd.append('ajax', '1');

        try {
            const res = await fetch('/ai?action=hapus_sesi', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                sessionsList = sessionsList.filter(s => s.session_id !== sessionId);
                renderSessionList(sessionsList);
                updateHistoryCountBadge();
                if (currentSessionId === sessionId) {
                    startNewChat();
                }
            } else {
                alert('Gagal menghapus sesi percakapan.');
            }
        } catch(e) {
            alert('Terjadi kesalahan jaringan saat menghapus sesi.');
        }
    }

    async function deleteAllSessions() {
        if (!confirm('Hapus seluruh riwayat chat Anda? Tindakan ini tidak dapat dibatalkan.')) return;

        const fd = new FormData();
        fd.append('ajax', '1');

        try {
            const res = await fetch('/ai?action=hapus_riwayat', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                sessionsList = [];
                renderSessionList(sessionsList);
                updateHistoryCountBadge();
                startNewChat();
                toggleDrawer(false);
            } else {
                alert('Gagal menghapus riwayat.');
            }
        } catch(e) {
            alert('Terjadi kesalahan jaringan saat menghapus riwayat.');
        }
    }

    function renderSessionList(list) {
        const container = document.getElementById('sessionListEl');
        if (!container) return;
        if (!list || list.length === 0) {
            container.innerHTML = `
                <div class="ai-drawer-empty">
                    <span class="material-symbols-outlined">forum</span>
                    <div>Belum ada riwayat percakapan</div>
                    <div style="font-size:11px;color:var(--ai-faint);margin-top:4px;">Obrolan baru Anda akan tersimpan otomatis di sini.</div>
                </div>
            `;
            return;
        }

        let html = '';
        list.forEach(s => {
            const isActive = (s.session_id === currentSessionId);
            const title = escapeHtml(s.title || 'Percakapan');
            const timeStr = formatSessionTime(s.updated_at || s.created_at);
            const countStr = (s.total_messages || 1) + ' pesan';
            html += `
                <div class="ai-session-item ${isActive ? 'active' : ''}" id="session-item-${escapeHtml(s.session_id)}" onclick="loadSession('${escapeHtml(s.session_id)}')">
                    <span class="material-symbols-outlined ai-session-item-icon">chat_bubble_outline</span>
                    <div class="ai-session-item-content">
                        <div class="ai-session-item-title" title="${title}">${title}</div>
                        <div class="ai-session-item-meta">
                            <span>${timeStr}</span>
                            <span>•</span>
                            <span>${countStr}</span>
                        </div>
                    </div>
                    <button type="button" class="ai-session-item-delete" onclick="deleteSession('${escapeHtml(s.session_id)}', event)" title="Hapus percakapan ini">
                        <span class="material-symbols-outlined" style="font-size:17px;">delete</span>
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function formatSessionTime(dateStr) {
        if (!dateStr || dateStr === 'Baru saja') return 'Baru saja';
        try {
            const d = new Date(dateStr.replace(/-/g, '/'));
            if (isNaN(d.getTime())) return dateStr;
            const now = new Date();
            const isToday = d.toDateString() === now.toDateString();
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            if (isToday) return `Hari ini ${hours}:${minutes}`;
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${d.getDate()} ${months[d.getMonth()]}, ${hours}:${minutes}`;
        } catch(e) {
            return dateStr;
        }
    }

    function filterSessions(keyword) {
        const q = (keyword || '').toLowerCase().trim();
        if (!q) {
            renderSessionList(sessionsList);
            highlightActiveSession(currentSessionId);
            return;
        }
        const filtered = sessionsList.filter(s => (s.title || '').toLowerCase().includes(q));
        renderSessionList(filtered);
        highlightActiveSession(currentSessionId);
    }

    function upsertSessionItem(sessionId, title, timeStr, totalMsgs) {
        const idx = sessionsList.findIndex(s => s.session_id === sessionId);
        if (idx !== -1) {
            sessionsList[idx].title = title || sessionsList[idx].title;
            sessionsList[idx].updated_at = timeStr || 'Baru saja';
            sessionsList[idx].total_messages = (sessionsList[idx].total_messages || 1) + 1;
            const item = sessionsList.splice(idx, 1)[0];
            sessionsList.unshift(item);
        } else {
            sessionsList.unshift({
                session_id: sessionId,
                title: title || 'Percakapan Baru',
                created_at: new Date().toISOString(),
                updated_at: timeStr || 'Baru saja',
                total_messages: totalMsgs || 1
            });
        }
        renderSessionList(sessionsList);
        highlightActiveSession(sessionId);
        updateHistoryCountBadge();
    }

    function highlightActiveSession(sessionId) {
        document.querySelectorAll('.ai-session-item').forEach(el => {
            el.classList.remove('active');
        });
        if (sessionId) {
            const activeEl = document.getElementById('session-item-' + sessionId);
            if (activeEl) activeEl.classList.add('active');
        }
    }

    function updateHistoryCountBadge() {
        const cnt = sessionsList.length;
        const hBadge = document.getElementById('headerHistoryCount');
        const eBadge = document.getElementById('embedHistoryCount');
        if (hBadge) hBadge.textContent = cnt;
        if (eBadge) eBadge.textContent = cnt;
    }

    // ============================================================
    // MESSAGE RENDERING & HELPERS
    // ============================================================
    function addMessage(role, content) {
        const msg = document.createElement('div');
        msg.className = 'ai-msg ' + role;
        const avatar = role === 'ai' ? AI_AVATAR_HTML : '<span>' + userName.charAt(0).toUpperCase() + '</span>';
        const formatted = role === 'ai' ? formatAIResponse(content) : escapeHtml(content);
        msg.innerHTML = `<div class="ai-msg-avatar">${avatar}</div><div class="ai-bubble">${formatted}</div>`;
        chatBody.appendChild(msg);
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
        return msg;
    }

    function formatAIResponse(text) {
        if (!text) return '';
        let html = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        
        // Action buttons
        html = html.replace(/\[\[DOWNLOAD_SURAT:(\d+):([^\]]*)\]\]/g, (m, id) => {
            return `<br><a href="/surat?action=cetak&id=${id}" target="_blank" class="btn-buat-surat"><span class="material-symbols-outlined" style="font-size:15px">download</span>Download Surat</a><br>`;
        });
        html = html.replace(/\[\[BUAT_SURAT:(\d+):([^\]]+)\]\]/g, (m, id, nama) => {
            return `<br><a href="/surat?action=buat&id=${id}" class="btn-buat-surat"><span class="material-symbols-outlined" style="font-size:15px">description</span>Buat ${nama}</a><br>`;
        });
        
        // Markdown Bold & Italic
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        
        // Markdown Tables
        html = renderMarkdownTables(html);

        // Bullet points clean styling
        html = html.replace(/^[•\*\-]\s+(.+)$/gm, '<div class="ai-list-item"><span class="ai-bullet">•</span><span>$1</span></div>');

        // Legitimate Web Links & App Routes (Hanya rute sistem, jangan tangkap RT/RW atau dan/atau)
        html = html.replace(/(^|[\s])(https?:\/\/[^\s<"']+)/g, (m, pre, url) => `${pre}<a href="${url}" target="_blank" class="ai-inline-link">${url}</a>`);
        html = html.replace(/(^|\s)(\/(?:surat|pengaduan|pengumuman|pelayanan|ai|dashboard|users|warga|laporan|ai-config)[a-zA-Z0-9_\-?=&;%.]*)/g, (m, pre, url) => `${pre}<a href="${url}" target="_blank" class="ai-inline-link">${url}</a>`);

        // Line breaks (hindari double br berlebih)
        html = html.replace(/\n/g, '<br>').replace(/(<br>){3,}/g, '<br><br>');
        return html;
    }

    function renderMarkdownTables(text) {
        const lines = text.split('\n');
        let output = [];
        let i = 0;
        while (i < lines.length) {
            const line = lines[i];
            if (line.trim().match(/^\|.+\|$/) && i + 1 < lines.length && lines[i + 1].trim().match(/^\|[\s\-:|]+\|$/)) {
                const tableLines = [];
                while (i < lines.length && lines[i].trim().match(/^\|.+\|$/)) {
                    tableLines.push(lines[i].trim());
                    i++;
                }
                output.push(renderTable(tableLines));
                continue;
            }
            output.push(line);
            i++;
        }
        return output.join('\n');
    }

    function renderTable(lines) {
        let html = '<div class="ai-table-scroll"><table>';
        lines.forEach((line, idx) => {
            if (idx === 1 && line.match(/^\|[\s\-:|]+\|$/)) return;
            const cells = line.split('|').slice(1, -1).map(c => c.trim());
            const tag = idx === 0 ? 'th' : 'td';
            html += '<tr>';
            cells.forEach(cell => { html += `<${tag}>${cell}</${tag}>`; });
            html += '</tr>';
        });
        html += '</table></div>';
        return html;
    }

    function showLoading() {
        const typing = document.createElement('div');
        typing.className = 'ai-msg ai';
        typing.id = 'typingIndicator';
        typing.innerHTML = `
            <div class="ai-msg-avatar">${AI_AVATAR_HTML}</div>
            <div class="ai-bubble">
                <div class="ai-typing-box">
                    <div class="ai-typing-dots"><span></span><span></span><span></span></div>
                    <span class="ai-typing-text">SIWARGA AI sedang menganalisis data...</span>
                </div>
            </div>
        `;
        chatBody.appendChild(typing);
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
    }

    function removeLoading() {
        const typing = document.getElementById('typingIndicator');
        if (typing) typing.remove();
    }

    function showError(message, retryText) {
        removeLoading();
        const msg = document.createElement('div');
        msg.className = 'ai-msg ai';
        msg.innerHTML = `
            <div class="ai-msg-avatar" style="background:#fee2e2;color:#dc2626;">
                <span class="material-symbols-outlined" style="font-size:18px;">error</span>
            </div>
            <div class="ai-bubble" style="border-color:#fecaca;background:rgba(254,242,242,0.6);">
                <div style="font-weight:700;color:#b91c1c;margin-bottom:4px;">Gagal Memproses Permintaan</div>
                <div style="font-size:13px;color:#7f1d1d;line-height:1.5;">${escapeHtml(message)}</div>
                ${retryText ? `<button type="button" onclick="retryQuestion('${escapeHtml(retryText)}')" style="margin-top:8px;padding:6px 12px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"><span class="material-symbols-outlined" style="font-size:15px;">refresh</span>Coba Lagi</button>` : ''}
            </div>
        `;
        chatBody.appendChild(msg);
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
    }

    function retryQuestion(text) {
        msgInput.value = text;
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }

    async function sendMessage(e) {
        e.preventDefault();
        if (isSending) return;
        const text = msgInput.value.trim();
        if (!text && !pendingImage) return;

        lastQuery = text;
        isSending = true;
        document.getElementById('btnSend').disabled = true;

        if (pendingImage) {
            addMessage('user', '🖼️ [Lampiran Foto] ' + (text || 'Analisis foto/dokumen ini'));
        } else {
            addMessage('user', text);
        }
        msgInput.value = '';

        showLoading();

        try {
            let response;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 25000); // 25s timeout

            if (pendingImage) {
                const fd = new FormData();
                fd.append('pertanyaan', text || 'Coba jelaskan gambar/dokumen ini secara lengkap');
                fd.append('image', pendingImage);
                if (currentSessionId) fd.append('session_id', currentSessionId);
                response = await fetch('/ai?action=chat_image', {
                    method: 'POST',
                    body: fd,
                    signal: controller.signal
                });
            } else {
                const fd = new FormData();
                fd.append('pertanyaan', text);
                if (currentSessionId) fd.append('session_id', currentSessionId);
                response = await fetch('/ai?action=chat', {
                    method: 'POST',
                    body: fd,
                    signal: controller.signal
                });
            }

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`Server merespon dengan status HTTP ${response.status}`);
            }

            const data = await response.json();
            removeLoading();

            // Tangani session_id dari response
            if (data.session_id) {
                currentSessionId = data.session_id;
                window.history.pushState(null, '', '/ai?session_id=' + encodeURIComponent(data.session_id));
                upsertSessionItem(data.session_id, data.title || text, 'Baru saja', 1);
            }

            const replyText = data.jawaban || '';

            if (replyText.includes('DATA_SURAT_LENGKAP:')) {
                const lines = replyText.split('\n').map(l => l.trim()).filter(l => l);
                const idxLabel = lines.findIndex(l => l.includes('DATA_SURAT_LENGKAP'));
                const jenisSurat = (lines[idxLabel + 1] || 'Surat').trim();

                const suratData = {};
                for (let i = idxLabel + 2; i < lines.length; i++) {
                    const line = lines[i];
                    if (line.includes(':')) {
                        const pos = line.indexOf(':');
                        const key = line.substring(0, pos).trim().toLowerCase(); 
                        const val = line.substring(pos + 1).trim();
                        if (key && val) suratData[key] = val;
                    }
                }

                const msg = document.createElement('div');
                msg.className = 'ai-msg ai';
                msg.innerHTML = `
                    <div class="ai-msg-avatar">${AI_AVATAR_HTML}</div>
                    <div class="ai-bubble">
                        <strong>📝 Formulir Pembuatan Surat ${escapeHtml(jenisSurat)} Telah Lengkap</strong><br><br>
                        Data permohonan surat kamu sudah lengkap tervalidasi. Klik tombol di bawah ini untuk langsung mengajukan ke RT/RW/Desa:
                        
                        <form action="/surat?action=ajukan_ai" method="POST" style="margin-top: 14px;">
                            <input type="hidden" name="jenis_surat" value="${escapeHtml(jenisSurat)}">
                            <input type="hidden" name="data_json" value='${escapeHtml(JSON.stringify(suratData))}'>
                            <button type="submit" class="btn-buat-surat" style="width: 100%; justify-content: center; padding: 10px;">
                                <span class="material-symbols-outlined" style="font-size:18px">send</span>
                                Ajukan Pembuatan Surat Sekarang
                            </button>
                        </form>
                    </div>
                `;
                chatBody.appendChild(msg);
                chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });

            } else {
                addMessage('ai', replyText || 'Maaf, saya tidak dapat menemukan jawaban yang tepat.');
            }

            removeImage();

        } catch (err) {
            console.error('AI Chat Error:', err);
            let errMsg = 'Koneksi ke asisten AI terputus atau melebihi batas waktu (timeout). Silakan periksa jaringan Anda atau coba beberapa saat lagi.';
            if (err.name === 'AbortError') {
                errMsg = 'Permintaan membutuhkan waktu terlalu lama. Silakan coba ajukan kembali.';
            }
            showError(errMsg, lastQuery);
        }

        isSending = false;
        document.getElementById('btnSend').disabled = false;
        msgInput.focus();
    }

    function sendQuick(text) {
        msgInput.value = text;
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }

    function handleFile(input) {
        const file = input.files[0];
        if (!file) return;
        if (file.size > 4 * 1024 * 1024) {
            alert('Ukuran foto terlalu besar! Maksimal 4MB.');
            input.value = '';
            return;
        }
        pendingImage = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('imgPreviewEl').src = e.target.result;
            document.getElementById('imgPreview').classList.add('active');
        };
        reader.readAsDataURL(file);
    }

    function removeImage() {
        pendingImage = null;
        document.getElementById('fileInput').value = '';
        document.getElementById('imgPreview').classList.remove('active');
    }

    function escapeHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    msgInput.focus();
</script>

<?php 
if (!$isEmbed) {
    include __DIR__ . '/../layouts/dashboard_footer.php';
} else {
    echo "</body></html>";
}
?>