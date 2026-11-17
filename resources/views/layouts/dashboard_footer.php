</div>
</main>

<!-- ============================================ -->
<!-- FLOATING AI WIDGET (MODERN, RESPONSIVE & SLEEK) -->
<!-- ============================================ -->
<div id="ai-floating-wrapper" style="position: fixed; bottom: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; align-items: flex-end; font-family: 'Inter', system-ui, sans-serif;">
    
    <!-- Jendela Chat -->
    <div id="ai-chat-window" style="display: none; flex-direction: column; background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(226, 232, 240, 0.6); overflow: hidden; width: 420px; height: 620px; max-width: calc(100vw - 32px); max-height: calc(100vh - 100px); margin-bottom: 16px; box-sizing: border-box; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Header Jendela -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: linear-gradient(135deg, #1d4ed8, #4f46e5); color: #fff; flex-shrink: 0; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <!-- Icon Chat -->
                <div style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.18); border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                    <span class="material-symbols-outlined" style="font-size: 22px; color: #fff;">smart_toy</span>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin: 0; line-height: 1.3; letter-spacing: 0.2px;">SIWARGA AI Assistant</h3>
                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                        <span style="font-size: 10px; background: rgba(255,255,255,0.22); padding: 2px 7px; border-radius: 6px; font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase;">
                            <?= htmlspecialchars($roleName ?? 'Warga') ?>
                        </span>
                        <span style="font-size: 10px; color: #bfdbfe; display: flex; align-items: center; gap: 4px;">
                            <span style="width: 6px; height: 6px; background: #4ade80; border-radius: 50%; display: inline-block; box-shadow: 0 0 6px #4ade80;"></span> Online
                        </span>
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <!-- Tombol Clear -->
                <button type="button" id="btn-ai-clear" title="Bersihkan & Muat Ulang Chat" style="padding: 6px; background: transparent; border: none; cursor: pointer; color: #bfdbfe; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.15)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='#bfdbfe'">
                    <span class="material-symbols-outlined" style="font-size: 18px;">refresh</span>
                </button>
                <!-- Tombol Maximize / Resize -->
                <button type="button" id="btn-ai-maximize" title="Perbesar Tampilan" style="padding: 6px; background: transparent; border: none; cursor: pointer; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                    <span class="material-symbols-outlined" id="ai-max-icon" style="font-size: 20px;">open_in_full</span>
                </button>
                <!-- Tombol Tutup -->
                <button type="button" id="btn-ai-close" title="Tutup Chat" style="padding: 6px; background: transparent; border: none; cursor: pointer; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                    <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
                </button>
            </div>
        </div>

        <!-- Isi Iframe (Clean Embed Mode) -->
        <div style="flex: 1; position: relative; background: #f8fafc; overflow: hidden; width: 100%;">
            <iframe id="ai-chat-iframe" data-src="/ai?embed=1" style="width: 100%; height: 100%; border: none; position: absolute; inset: 0;" loading="lazy"></iframe>
        </div>
    </div>

    <!-- Tombol Melayang (FAB Luar) -->
    <button type="button" id="btn-ai-fab" title="Tanya SIWARGA AI" style="width: 58px; height: 58px; background: linear-gradient(135deg, #1d4ed8, #4f46e5); color: #fff; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 10px 25px -5px rgba(37,99,235,0.5), 0 0 0 3px rgba(255,255,255,0.8); transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
        <span class="material-symbols-outlined" style="font-size: 28px;">smart_toy</span>
    </button>
</div>

<!-- CSS Tambahan Untuk Mode Maximize (Resize Modern) -->
<style>
    #ai-chat-window.is-maximized {
        position: fixed !important;
        bottom: 24px !important;
        right: 24px !important;
        top: auto !important;
        left: auto !important;
        width: 760px !important;
        height: calc(100vh - 48px) !important;
        max-width: calc(100vw - 48px) !important;
        max-height: calc(100vh - 48px) !important;
        border-radius: 20px !important;
        z-index: 100000 !important;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.8) !important;
    }
    @media (max-width: 768px) {
        #ai-chat-window {
            width: calc(100vw - 32px) !important;
            height: calc(100vh - 90px) !important;
            bottom: 16px !important;
            right: 16px !important;
        }
        #ai-chat-window.is-maximized {
            width: calc(100vw - 24px) !important;
            height: calc(100vh - 24px) !important;
            bottom: 12px !important;
            right: 12px !important;
            border-radius: 16px !important;
        }
    }
</style>

<!-- ============================================ -->
<!-- GLOBAL CUSTOM MODAL (PENGGANTI ALERT/CONFIRM) -->
<!-- ============================================ -->
<div id="globalModal" class="hidden fixed inset-0 z-[999999] items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-opacity">
    <div id="globalModalCard" class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-[420px] w-full p-6 sm:p-8 transition-all duration-200 scale-95 opacity-0 transform text-center">
        
        <div id="globalModalIcon" class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 flex items-center justify-center mx-auto mb-5">
            <span class="material-symbols-outlined text-[32px]">info</span>
        </div>
        
        <h3 id="globalModalTitle" class="text-xl font-extrabold text-slate-900 dark:text-white mb-2">Informasi</h3>
        <p id="globalModalMessage" class="text-[14px] text-slate-500 dark:text-slate-300 leading-relaxed mb-6">Pesan di sini...</p>
        
        <div class="flex gap-3">
            <button type="button" id="globalModalCancelBtn" onclick="closeGlobalModal()" class="flex-1 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-white font-bold text-sm transition-all hidden">
                Batal
            </button>
            <button type="button" id="globalModalConfirmBtn" class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg transition-all">
                OK
            </button>
        </div>
    </div>
</div>

<script>
// ============================================================
// 1. THEME TOGGLE (DARK MODE) - TIDAK DIUBAH
// ============================================================
(function(){
    console.log('🚀 Init theme system');
    var darkVars = {
        '--bg-app':'#0b1220','--bg-card':'#131c2e','--bg-elevated':'#1a2540',
        '--bg-soft':'#0f1829','--bg-hover':'#1a2540','--border':'#1f2c44',
        '--border-soft':'#182338','--text-primary':'#f1f5f9',
        '--text-secondary':'#cbd5e1','--text-muted':'#94a3b8','--text-faint':'#64748b'
    };
    var lightVars = {
        '--bg-app':'#f1f5f9','--bg-card':'#ffffff','--bg-elevated':'#ffffff',
        '--bg-soft':'#f8fafc','--bg-hover':'#f1f5f9','--border':'#e8edf3',
        '--border-soft':'#f1f5f9','--text-primary':'#0f172a',
        '--text-secondary':'#475569','--text-muted':'#64748b','--text-faint':'#94a3b8'
    };

    function paint(t) {
        var isDark = (t === 'dark');
        var html = document.documentElement;
        var body = document.body;
        var vars = isDark ? darkVars : lightVars;

        html.setAttribute('data-theme', t);
        if (isDark) html.classList.add('dark');
        else html.classList.remove('dark');

        for (var k in vars) html.style.setProperty(k, vars[k]);

        body.style.setProperty('background-color', vars['--bg-app'], 'important');
        body.style.setProperty('color', vars['--text-primary'], 'important');

        function setBg(sel, color) {
            document.querySelectorAll(sel).forEach(function(el){ el.style.setProperty('background-color', color, 'important'); });
        }
        function setColor(sel, color) {
            document.querySelectorAll(sel).forEach(function(el){ el.style.setProperty('color', color, 'important'); });
        }
        function setBorder(sel, color) {
            document.querySelectorAll(sel).forEach(function(el){ el.style.setProperty('border-color', color, 'important'); });
        }

        var cardBg  = vars['--bg-card'];
        var softBg  = vars['--bg-soft'];
        var border  = vars['--border'];
        var textP   = vars['--text-primary'];
        var textM   = vars['--text-muted'];
        var textS   = vars['--text-secondary'];

        setBg('.app-topbar', cardBg); setBorder('.app-topbar', border); setColor('.app-topbar-title', textP); setColor('.app-topbar-sub', textM);
        setBg('.glass-card, .stat-card, .info-card', cardBg); setBorder('.glass-card, .stat-card', border);
        setColor('h1, h2, h3, h4, h5, .app-topbar-title, .stat-value', textP);
        setColor('.stat-label, .app-topbar-sub, p', textM);
        setColor('.text-slate-900, .text-slate-800, .text-slate-700', textP);
        setColor('.text-slate-600, .text-slate-500, .text-slate-400', textM);

        setBg('table, thead, tbody, tr, th, td', 'transparent');
        setBg('thead', isDark ? 'rgba(255,255,255,.03)' : softBg);
        setColor('th', textM); setColor('td', textS); setBorder('th, td', border); setBorder('tr', border);

        document.querySelectorAll('input, select, textarea').forEach(function(el){
            el.style.setProperty('background-color', softBg, 'important');
            el.style.setProperty('color', textP, 'important');
            el.style.setProperty('border-color', border, 'important');
        });

        setBg('.notif-dropdown, .notif-header, .notif-footer', cardBg);
        setBg('.notif-item', 'transparent');
        setBorder('.notif-dropdown, .notif-header, .notif-footer, .notif-item', border);
        setColor('.notif-title', textP); setColor('.notif-message', textM);

        setBg('.app-icon-btn', 'transparent'); setColor('.app-icon-btn', textM);
        setBg('#appThemeToggle', 'transparent'); setColor('#appThemeToggle', textM);

        setBg('.app-sidebar, .sidebar', cardBg); setBorder('.app-sidebar, .sidebar', border); setColor('.app-sidebar a, .sidebar a', textS);

        document.querySelectorAll('.bg-white, .bg-slate-50, .bg-slate-100, .bg-slate-200').forEach(function(el){
            el.style.setProperty('background-color', isDark ? softBg : '', 'important');
        });

        try { localStorage.setItem('siwarga-theme', t); } catch(e){}
    }

    window.toggleTheme = function() {
        var cur = document.documentElement.getAttribute('data-theme') || 'light';
        var next = (cur === 'dark') ? 'light' : 'dark';
        paint(next);
    };

    var saved = 'light';
    try { saved = localStorage.getItem('siwarga-theme') || 'light'; } catch(e){}
    paint(saved);

    var btn = document.getElementById('appThemeToggle');
    if (btn) {
        btn.addEventListener('click', function(e){
            e.preventDefault(); e.stopPropagation(); window.toggleTheme();
        });
    }
    setTimeout(function(){ paint(localStorage.getItem('siwarga-theme') || 'light'); }, 100);
})();

// ============================================================
// 2. LOGIKA GLOBAL MODAL - TIDAK DIUBAH
// ============================================================
let globalModalCallback = null;

function showGlobalModal(options) {
    const modal = document.getElementById('globalModal');
    const card = document.getElementById('globalModalCard');
    const iconBox = document.getElementById('globalModalIcon');
    const title = document.getElementById('globalModalTitle');
    const message = document.getElementById('globalModalMessage');
    const cancelBtn = document.getElementById('globalModalCancelBtn');
    const confirmBtn = document.getElementById('globalModalConfirmBtn');

    title.textContent = options.title || 'Informasi';
    message.innerHTML = options.message || ''; 
    confirmBtn.textContent = options.confirmText || 'OK';

    if (options.type === 'danger') {
        iconBox.className = 'w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-500 flex items-center justify-center mx-auto mb-5';
        iconBox.innerHTML = '<span class="material-symbols-outlined text-[32px]">warning</span>';
        confirmBtn.className = 'flex-1 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-lg shadow-rose-600/30 transition-all';
    } else if (options.type === 'success') {
        iconBox.className = 'w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 flex items-center justify-center mx-auto mb-5';
        iconBox.innerHTML = '<span class="material-symbols-outlined text-[32px]">check_circle</span>';
        confirmBtn.className = 'flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition-all';
    } else {
        iconBox.className = 'w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-500 flex items-center justify-center mx-auto mb-5';
        iconBox.innerHTML = '<span class="material-symbols-outlined text-[32px]">info</span>';
        confirmBtn.className = 'flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition-all';
    }

    if (options.showCancel) cancelBtn.classList.remove('hidden');
    else cancelBtn.classList.add('hidden');

    globalModalCallback = options.onConfirm || null;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeGlobalModal() {
    const modal = document.getElementById('globalModal');
    const card = document.getElementById('globalModalCard');
    
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 200);
}

document.getElementById('globalModalConfirmBtn').addEventListener('click', function() {
    if (typeof globalModalCallback === 'function') globalModalCallback();
    closeGlobalModal();
});

document.addEventListener('click', function(e) {
    const target = e.target.closest('[onclick]');
    if (!target) return;

    const onclickCode = target.getAttribute('onclick');
    if (!onclickCode || (!onclickCode.includes('confirm(') && !onclickCode.includes('alert('))) return;
    if (target.dataset.bypass === "true") return;

    e.preventDefault();
    e.stopImmediatePropagation();

    if (onclickCode.includes('confirm(')) {
        const match = onclickCode.match(/confirm\(['"](.*?)['"]\)/);
        const message = match ? match[1] : 'Apakah Anda yakin?';
        const isLink = target.tagName.toLowerCase() === 'a';
        const href = isLink ? target.getAttribute('href') : null;

        showGlobalModal({
            title: 'Konfirmasi Tindakan',
            message: message,
            type: 'danger',
            showCancel: true,
            confirmText: 'Ya, Lanjutkan',
            onConfirm: () => {
                target.dataset.bypass = "true";
                if (isLink && href) {
                    window.location.href = href;
                } else {
                    let cleanCode = onclickCode.replace(/return\s+confirm\(.*?\);?/g, '').replace(/confirm\(.*?\);?/g, '');
                    if (cleanCode.trim()) {
                        try { eval(cleanCode); } catch(err) {}
                    }
                }
                setTimeout(() => delete target.dataset.bypass, 500);
            }
        });
    } else if (onclickCode.includes('alert(')) {
        const match = onclickCode.match(/alert\(['"](.*?)['"]\)/);
        const message = match ? match[1] : 'Informasi';

        showGlobalModal({
            title: 'Informasi',
            message: message,
            type: 'info',
            showCancel: false,
            confirmText: 'Mengerti'
        });
    }
}, true);

// ============================================================
// 3. NOTIFIKASI LONCENG - TIDAK DIUBAH
// ============================================================
(function(){
    try {
        var bell        = document.getElementById('notifBell');
        var wrapper     = document.getElementById('notifWrapper');
        var dropdown    = document.getElementById('notifDropdown');
        var badge       = document.getElementById('notifBadge');
        var list        = document.getElementById('notifList');
        var unreadText  = document.getElementById('notifUnreadText');
        var btnReadAll  = document.getElementById('notifReadAll');
        var btnClearAll = document.getElementById('notifClearAll');

        if (!bell || !wrapper || !dropdown) return;
        var isOpen = false;

        function timeAgo(dateStr) {
            var d = new Date(String(dateStr).replace(' ', 'T'));
            var s = Math.floor((Date.now() - d.getTime()) / 1000);
            if (s < 60) return 'Baru saja';
            if (s < 3600) return Math.floor(s/60) + ' mnt lalu';
            if (s < 86400) return Math.floor(s/3600) + ' jam lalu';
            if (s < 604800) return Math.floor(s/86400) + ' hari lalu';
            return d.toLocaleDateString('id-ID', { day:'numeric', month:'short' });
        }
        function esc(str) {
            var d = document.createElement('div');
            d.textContent = str || '';
            return d.innerHTML;
        }
        function updateBadge(n) {
            if (!badge) return;
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : n;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        function renderList(notifs) {
            if (!list) return;
            if (!notifs.length) {
                list.innerHTML = '<div class="notif-empty"><span class="material-symbols-outlined">notifications_off</span>Belum ada notifikasi</div>';
                return;
            }
            list.innerHTML = notifs.map(function(n){
                let iconName = 'check_circle';
                if (n.type === 'pengaduan_baru') iconName = 'campaign';
                else if (n.type === 'pengumuman_baru') iconName = 'notifications_active';

                return '<a href="' + esc(n.link || '#') + '" class="notif-item ' + (n.is_read == 0 ? 'unread' : '') + '" data-id="' + n.id + '">' +
                    '<div class="notif-icon type-' + esc(n.type) + '">' +
                    '<span class="material-symbols-outlined">' + iconName + '</span>' +
                    '</div>' +
                    '<div class="notif-body">' +
                    '<h4 class="notif-title">' + esc(n.title) + '</h4>' +
                    '<p class="notif-message">' + esc(n.message) + '</p>' +
                    '<div class="notif-time">' + timeAgo(n.created_at) + '</div>' +
                    '</div></a>';
            }).join('');
        }
        function fetchCount() {
            fetch('/notifications?action=count')
                .then(function(r){ return r.json(); })
                .then(function(d){ updateBadge(d.unread || 0); })
                .catch(function(){});
        }
        function fetchList() {
            if (list) list.innerHTML = '<div class="notif-empty">Memuat...</div>';
            fetch('/notifications?action=list')
                .then(function(r){ return r.json(); })
                .then(function(d){
                    renderList(d.notifications || []);
                    updateBadge(d.unread || 0);
                    if (unreadText) unreadText.textContent = (d.unread || 0) + ' belum dibaca';
                })
                .catch(function(){ if (list) list.innerHTML = '<div class="notif-empty">Gagal memuat</div>'; });
        }

        bell.onclick = function(e) {
            e.stopPropagation();
            isOpen = !isOpen;
            if (isOpen) {
                dropdown.classList.add('open');
                fetchList();
            } else {
                dropdown.classList.remove('open');
            }
        };
        document.addEventListener('click', function(e){
            if (isOpen && !wrapper.contains(e.target)) {
                isOpen = false;
                dropdown.classList.remove('open');
            }
        });
        if (list) {
            list.onclick = function(e) {
                var item = e.target.closest('.notif-item');
                if (!item) return;
                var id = item.dataset.id;
                if (item.classList.contains('unread')) {
                    fetch('/notifications?action=read&id=' + id).catch(function(){});
                    item.classList.remove('unread');
                    fetchCount();
                }
            };
        }
        if (btnReadAll) {
            btnReadAll.onclick = function(e) {
                e.stopPropagation();
                fetch('/notifications?action=read_all').then(fetchList).catch(function(){});
            };
        }
        
        if (btnClearAll) {
            btnClearAll.onclick = function(e) {
                e.stopPropagation();
                showGlobalModal({
                    title: 'Hapus Semua Notifikasi',
                    message: 'Apakah Anda yakin ingin menghapus semua riwayat notifikasi?',
                    type: 'danger',
                    showCancel: true,
                    confirmText: 'Ya, Hapus',
                    onConfirm: function() {
                        fetch('/notifications?action=clear').then(function(){ fetchList(); updateBadge(0); }).catch(function(){});
                    }
                });
            };
        }

        fetchCount();
        setInterval(fetchCount, 30000);
    } catch(err) {
        console.error('Notif error:', err);
    }
})();

// ============================================================
// 4. LOGIKA FLOATING AI WIDGET & RESIZE (CLEAN & MODERN)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const btnFab    = document.getElementById('btn-ai-fab');
    const btnClose  = document.getElementById('btn-ai-close');
    const btnMax    = document.getElementById('btn-ai-maximize');
    const btnClear  = document.getElementById('btn-ai-clear');
    const container = document.getElementById('ai-chat-window');
    const iframe    = document.getElementById('ai-chat-iframe');
    const iconMax   = document.getElementById('ai-max-icon');

    if (!btnFab || !container || !iframe) return;

    // Sinkronisasi tema ke iframe
    function syncIframeTheme() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow?.document;
            if (!doc || !doc.documentElement) return;
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            doc.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'dark') {
                doc.documentElement.classList.add('dark');
            } else {
                doc.documentElement.classList.remove('dark');
            }
        } catch(e) {}
    }

    // Toggle Maximize / Resize
    function toggleMaximize() {
        container.classList.toggle('is-maximized');
        const isMax = container.classList.contains('is-maximized');
        iconMax.textContent = isMax ? 'close_fullscreen' : 'open_in_full';
        btnMax.title = isMax ? 'Perkecil Tampilan' : 'Perbesar Tampilan';
        syncIframeTheme();
    }

    // Buka Chat
    function openChat() {
        container.style.display = 'flex';
        btnFab.style.display = 'none';
        
        if (!iframe.src || iframe.src === 'about:blank' || iframe.src.indexOf('/ai') === -1) {
            iframe.src = iframe.getAttribute('data-src') || '/ai?embed=1';
        }

        setTimeout(() => {
            syncIframeTheme();
            try {
                const doc = iframe.contentDocument || iframe.contentWindow?.document;
                const input = doc?.getElementById('msgInput');
                if (input) input.focus();
            } catch(e) {}
        }, 400);
    }

    // Tutup Chat
    function closeChat() {
        if (container.classList.contains('is-maximized')) {
            container.classList.remove('is-maximized');
            iconMax.textContent = 'open_in_full';
            btnMax.title = 'Perbesar Tampilan';
        }
        container.style.display = 'none';
        btnFab.style.display = 'flex';
    }

    // Clear & Reload Chat
    function clearChat() {
        try {
            if (iframe.contentWindow) {
                iframe.contentWindow.location.reload();
            } else {
                iframe.src = iframe.getAttribute('data-src') || '/ai?embed=1';
            }
        } catch(e) {
            iframe.src = iframe.getAttribute('data-src') || '/ai?embed=1';
        }
    }

    // Event Listeners
    iframe.addEventListener('load', syncIframeTheme);

    btnFab.addEventListener('click', function(e) {
        e.preventDefault();
        openChat();
    });

    btnClose.addEventListener('click', function(e) {
        e.preventDefault();
        closeChat();
    });

    btnMax.addEventListener('click', function(e) {
        e.preventDefault();
        toggleMaximize();
    });

    if (btnClear) {
        btnClear.addEventListener('click', function(e) {
            e.preventDefault();
            clearChat();
        });
    }

    // Monitor theme toggle dari tombol parent
    const observer = new MutationObserver(syncIframeTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class'] });
});
</script>

</body>
</html>