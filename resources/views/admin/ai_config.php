<?php
// resources/views/admin/ai_config.php
// Halaman Konfigurasi AI SiWarga (Super Admin)

if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Konfigurasi AI';
include __DIR__ . '/../layouts/dashboard_layout.php';

$flash = flash();
$availableModels = $config['available_models'] ?? ['gemini-3.6-flash', 'gemini-3.5-flash', 'gemini-3.7-flash', 'gemini-3.8-flash', 'gemini-flash-latest'];
$currentModel = $config['model'] ?? 'gemini-3.6-flash';
$temperature = $config['temperature'] ?? 0.5;
$systemPrompt = $config['system_prompt'] ?? '';
$fallbackMessage = $config['fallback_message'] ?? '';
?>

<div class="max-w-5xl mx-auto py-2 px-1 font-['Inter',sans-serif]">

    <!-- NOTIFIKASI FLASH -->
    <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded-2xl flex items-center gap-3 <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' ?>">
            <span class="material-symbols-outlined text-2xl"><?= $flash['type'] === 'success' ? 'check_circle' : 'error' ?></span>
            <div class="text-sm font-semibold flex-1"><?= htmlspecialchars($flash['message']) ?></div>
        </div>
    <?php endif; ?>

    <!-- HEADER TITLE & RESET -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-900 to-indigo-950 dark:from-blue-600 dark:to-indigo-700 flex items-center justify-center text-blue-300 dark:text-white shadow-lg shadow-blue-900/20 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl">tune</span>
            </div>
            <div>
                <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    AI ENGINE & KNOWLEDGE BASE CONFIGURATION
                </h2>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                    Atur LLM Provider, Dynamic Model Fetching, Multi-API Key Failover, dan System Prompt.
                </p>
            </div>
        </div>

        <form action="/ai-config?action=reset" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset seluruh konfigurasi AI ke setelan awal?');">
            <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 rounded-xl transition border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined text-base">restart_alt</span>
                Reset Setelan Awal
            </button>
        </form>
    </div>

    <!-- MAIN CONFIG FORM -->
    <form action="/ai-config?action=save" method="POST" id="formAiConfig" class="space-y-6">

        <!-- CARD 1: PROVIDER & API KEY UTAMA -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Dropdown Provider -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                        Pilih LLM API Provider
                    </label>
                    <div class="relative">
                        <select name="provider" class="w-full h-12 pl-4 pr-10 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer appearance-none">
                            <option value="gemini" selected>Google Gemini AI</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-slate-400 pointer-events-none text-xl">expand_more</span>
                    </div>
                </div>

                <!-- API Key Utama -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                            LLM API Key Utama (Real Credential)
                        </label>
                        <button type="button" id="toggleKeyVisibility" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                            <span class="material-symbols-outlined text-sm" id="keyIcon">visibility</span>
                            <span id="keyText">Tampilkan</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input type="password" 
                               name="api_key" 
                               id="apiKeyInput"
                               value="<?= htmlspecialchars($maskedApiKey) ?>"
                               data-masked="<?= htmlspecialchars($maskedApiKey) ?>"
                               placeholder="Paste Google AI Studio API Key..." 
                               class="w-full h-12 px-4 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-500 transition placeholder:text-slate-400">
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">lock</span>
                        API Key disimpan secara aman dan di-mask demi keamanan kredensial.
                    </p>
                </div>
            </div>

            <!-- ROW 2: MODEL AUTO-DETECTED -->
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                        Pilih Model (Auto-Detected via API Key)
                    </label>
                    <button type="button" id="btnRefreshModels" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" id="iconRefresh">refresh</span>
                        <span>Deteksi Ulang Model</span>
                    </button>
                </div>

                <div class="relative">
                    <select name="model" id="modelSelect" class="w-full h-12 pl-4 pr-10 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer appearance-none">
                        <?php foreach ($availableModels as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $m === $currentModel ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-3.5 text-slate-400 pointer-events-none text-xl">expand_more</span>
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 italic" id="modelStatusMsg">
                    *Daftar model terisi otomatis saat API Key valid berhasil dimasukkan.
                </p>
            </div>

            <!-- ROW 3: TEMPERATURE / TINGKAT AKURASI -->
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                        Tingkat Akurasi / Temperature AI
                    </label>
                    <span id="tempBadge" class="px-2.5 py-0.5 rounded-md text-xs font-black bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                        <?= number_format((float)$temperature, 2) ?>
                    </span>
                </div>

                <input type="range" 
                       name="temperature" 
                       id="tempRange" 
                       min="0.0" 
                       max="1.0" 
                       step="0.05" 
                       value="<?= (float)$temperature ?>"
                       class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-blue-600">

                <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 font-medium mt-2">
                    <span>0.0 (Presisi & Kaku — Cocok Surat/Data)</span>
                    <span>1.0 (Kreatif & Fleksibel — Chat Sosial)</span>
                </div>
            </div>
        </div>

        <!-- CARD 2: MULTI-API KEY FAILOVER MANAGEMENT -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">swap_horiz</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            MULTI-API KEY FAILOVER MANAGEMENT (AUTO-SWITCH CADANGAN)
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-normal">
                            Jika token/kuota pada API Key Utama habis, Chat AI akan otomatis beralih ke daftar API Key cadangan.
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700" id="backupCountBadge">
                    <?= count($maskedBackupKeys) ?> Key Cadangan
                </span>
            </div>

            <!-- Form Tambah Key Cadangan -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <div class="md:col-span-6">
                    <input type="password" 
                           id="inputNewBackupKey" 
                           placeholder="Paste API Key Cadangan..." 
                           class="w-full h-11 px-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 transition placeholder:text-slate-400">
                </div>
                <div class="md:col-span-3">
                    <div class="relative">
                        <select id="selectNewBackupModel" class="w-full h-11 pl-3 pr-8 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 transition cursor-pointer appearance-none">
                            <?php foreach ($availableModels as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-3 text-slate-400 pointer-events-none text-base">expand_more</span>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <button type="button" id="btnAddBackupKey" class="w-full h-11 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-base">add</span>
                        Tambah Key Cadangan
                    </button>
                </div>
            </div>

            <!-- List Backup Keys Terdaftar -->
            <div id="backupKeysList" class="space-y-2">
                <?php if (empty($maskedBackupKeys)): ?>
                    <div id="emptyBackupNotice" class="text-center py-6 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-400">
                        Belum ada API Key cadangan yang didaftarkan.
                    </div>
                <?php endif; ?>

                <?php foreach ($maskedBackupKeys as $idx => $bk): ?>
                    <div class="backup-item flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="material-symbols-outlined text-slate-400 text-base">key</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300 truncate"><?= htmlspecialchars($bk['key']) ?></span>
                            <span class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-semibold text-[10px]">
                                <?= htmlspecialchars($bk['model']) ?>
                            </span>
                        </div>
                        <input type="hidden" name="backup_keys[]" value="<?= htmlspecialchars($bk['real_key']) ?>">
                        <input type="hidden" name="backup_models[]" value="<?= htmlspecialchars($bk['model']) ?>">
                        <button type="button" class="btn-delete-backup text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CARD 3: RAG CONTEXT INDICATOR -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                Vector Database & Knowledge Retrieval (RAG)
            </label>
            <div class="relative">
                <input type="text" 
                       value="Local Vector DB / Pinecone" 
                       readonly 
                       class="w-full h-12 px-4 rounded-xl bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold cursor-not-allowed">
                <span class="absolute right-3 top-3 px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 text-[11px] font-bold border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Aktif (Local Context)
                </span>
            </div>
        </div>

        <!-- CARD 4: SYSTEM PROMPT & FALLBACK MESSAGE -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition space-y-6">
            <!-- System Prompt -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                    System Prompt Utama AI Chatbot
                </label>
                <textarea name="system_prompt" 
                          rows="4" 
                          class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs leading-relaxed font-sans focus:outline-none focus:ring-2 focus:ring-blue-500 transition placeholder:text-slate-400"><?= htmlspecialchars($systemPrompt) ?></textarea>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                    Mengatur kepribadian, batas wewenang, dan gaya bahasa SIWARGA AI saat merespons pertanyaan warga.
                </p>
            </div>

            <!-- Pesan Fallback -->
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                    Pesan Fallback (Saat Informasi Tidak Ditemukan di Database Desa)
                </label>
                <textarea name="fallback_message" 
                          rows="3" 
                          class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs leading-relaxed font-sans focus:outline-none focus:ring-2 focus:ring-blue-500 transition placeholder:text-slate-400"><?= htmlspecialchars($fallbackMessage) ?></textarea>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                    Pesan ramah yang akan ditampilkan jika query tidak memiliki data atau saat seluruh server AI cadangan sedang mengalami gangguan.
                </p>
            </div>
        </div>

        <!-- TOMBOL SIMPAN KONFIGURASI -->
        <div class="pt-2">
            <button type="submit" id="btnSubmitForm" class="w-full md:w-auto px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30">
                <span class="material-symbols-outlined text-lg">save</span>
                Simpan Konfigurasi AI
            </button>
        </div>

    </form>
</div>

<!-- JAVASCRIPT LOGIC -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('apiKeyInput');
    const toggleBtn = document.getElementById('toggleKeyVisibility');
    const keyIcon = document.getElementById('keyIcon');
    const keyText = document.getElementById('keyText');
    const tempRange = document.getElementById('tempRange');
    const tempBadge = document.getElementById('tempBadge');
    const btnRefresh = document.getElementById('btnRefreshModels');
    const iconRefresh = document.getElementById('iconRefresh');
    const modelSelect = document.getElementById('modelSelect');
    const modelStatusMsg = document.getElementById('modelStatusMsg');

    // 1. Toggle Mask/Visibility Key Utama
    let isKeyVisible = false;
    toggleBtn.addEventListener('click', function() {
        isKeyVisible = !isKeyVisible;
        if (isKeyVisible) {
            apiKeyInput.type = 'text';
            keyIcon.textContent = 'visibility_off';
            keyText.textContent = 'Sembunyikan';
            // Jika isi saat ini masih masked, biarkan kosong agar user bisa input baru jika mau
            if (apiKeyInput.value.includes('•')) {
                apiKeyInput.dataset.savedMasked = apiKeyInput.value;
            }
        } else {
            apiKeyInput.type = 'password';
            keyIcon.textContent = 'visibility';
            keyText.textContent = 'Tampilkan';
        }
    });

    // Auto-select teks saat fokus jika masih bertopeng agar mudah di-paste/diganti
    apiKeyInput.addEventListener('focus', function() {
        if (this.value.includes('•')) {
            this.select();
        }
    });

    // 2. Slider Temperature Live Update
    tempRange.addEventListener('input', function() {
        const val = parseFloat(tempRange.value).toFixed(2);
        tempBadge.textContent = val;
    });

    // 3. Dynamic Model Fetching via API Key
    async function fetchModels(inputKey) {
        iconRefresh.classList.add('animate-spin');
        modelStatusMsg.textContent = '⏳ Memeriksa API key dan mengambil model yang didukung...';
        modelStatusMsg.className = 'text-[11px] text-blue-500 font-medium mt-1.5';

        try {
            const fd = new FormData();
            if (inputKey) {
                fd.append('api_key', inputKey);
            }

            const res = await fetch('/ai-config?action=fetch_models', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();

            if (data.success && Array.isArray(data.models) && data.models.length > 0) {
                const currentVal = modelSelect.value;
                modelSelect.innerHTML = '';
                data.models.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    if (m === currentVal) opt.selected = true;
                    modelSelect.appendChild(opt);
                });
                modelStatusMsg.textContent = '✅ ' + data.message;
                modelStatusMsg.className = 'text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1.5';
            } else {
                modelStatusMsg.textContent = '⚠️ ' + (data.message || 'Gagal mendeteksi model');
                modelStatusMsg.className = 'text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-1.5';
            }
        } catch (err) {
            modelStatusMsg.textContent = '❌ Gagal menghubungi server: ' + err.message;
            modelStatusMsg.className = 'text-[11px] text-rose-500 font-medium mt-1.5';
        } finally {
            iconRefresh.classList.remove('animate-spin');
        }
    }

    btnRefresh.addEventListener('click', function(e) {
        e.preventDefault();
        const key = apiKeyInput.value.trim();
        fetchModels(key);
    });

    // Otomatis deteksi jika user selesai mengetik API Key baru
    let debounceTimer = null;
    apiKeyInput.addEventListener('input', function() {
        const val = apiKeyInput.value.trim();
        if (val.length > 20 && !val.includes('•')) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchModels(val);
            }, 800);
        }
    });

    // 4. Tambah Key Cadangan
    const btnAddBackup = document.getElementById('btnAddBackupKey');
    const inputNewBackupKey = document.getElementById('inputNewBackupKey');
    const selectNewBackupModel = document.getElementById('selectNewBackupModel');
    const backupKeysList = document.getElementById('backupKeysList');
    const emptyNotice = document.getElementById('emptyBackupNotice');
    const backupCountBadge = document.getElementById('backupCountBadge');

    function updateBadgeCount() {
        const items = backupKeysList.querySelectorAll('.backup-item');
        backupCountBadge.textContent = items.length + ' Key Cadangan';
        if (items.length === 0) {
            if (emptyNotice) emptyNotice.style.display = 'block';
        } else {
            if (emptyNotice) emptyNotice.style.display = 'none';
        }
    }

    btnAddBackup.addEventListener('click', function() {
        const keyVal = inputNewBackupKey.value.trim();
        const modelVal = selectNewBackupModel.value;

        if (!keyVal) {
            alert('Silakan masukkan API Key cadangan terlebih dahulu.');
            inputNewBackupKey.focus();
            return;
        }

        const masked = keyVal.length > 8 ? keyVal.substring(0, 4) + '••••••••••••••••' + keyVal.substring(keyVal.length - 4) : '••••••••••••';

        const item = document.createElement('div');
        item.className = 'backup-item flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-xs';
        item.innerHTML = `
            <div class="flex items-center gap-3 min-w-0">
                <span class="material-symbols-outlined text-slate-400 text-base">key</span>
                <span class="font-mono text-slate-700 dark:text-slate-300 truncate">${masked}</span>
                <span class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-semibold text-[10px]">
                    ${modelVal}
                </span>
            </div>
            <input type="hidden" name="backup_keys[]" value="${keyVal}">
            <input type="hidden" name="backup_models[]" value="${modelVal}">
            <button type="button" class="btn-delete-backup text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition">
                <span class="material-symbols-outlined text-base">delete</span>
            </button>
        `;

        backupKeysList.appendChild(item);
        inputNewBackupKey.value = '';
        updateBadgeCount();
    });

    // Hapus Key Cadangan
    backupKeysList.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete-backup');
        if (btn) {
            const item = btn.closest('.backup-item');
            if (item) {
                item.remove();
                updateBadgeCount();
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/dashboard_footer.php'; ?>
