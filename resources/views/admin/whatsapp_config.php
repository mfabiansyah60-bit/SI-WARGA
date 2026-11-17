<?php
// resources/views/admin/whatsapp_config.php
// Halaman Konfigurasi WhatsApp Gateway Fonnte SiWarga (Super Admin)

if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = 'Konfigurasi WhatsApp';
include __DIR__ . '/../layouts/dashboard_layout.php';

$flash = flash();
$isActive = !empty($config['is_active']);
$senderNumber = $config['sender_number'] ?? '';
$countryCode = $config['country_code'] ?? '62';
$queueDelaySeconds = (int)($config['queue_delay_seconds'] ?? 60);
$lastStatus = $config['last_status'] ?? null;
$lastCheckedAt = $config['last_checked_at'] ?? null;
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
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-800 dark:from-emerald-500 dark:to-teal-700 flex items-center justify-center text-white shadow-lg shadow-emerald-600/20 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl">chat</span>
            </div>
            <div>
                <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    WHATSAPP GATEWAY & FONNTE CONFIGURATION
                </h2>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                    Atur API Key Fonnte, Nomor Pengirim WhatsApp, Validasi Koneksi Device, dan Tes Pengiriman Pesan.
                </p>
            </div>
        </div>

        <form action="/whatsapp-config?action=reset" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset konfigurasi WhatsApp ke setelan awal?');">
            <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 rounded-xl transition border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined text-base">restart_alt</span>
                Reset Setelan Awal
            </button>
        </form>
    </div>

    <!-- MAIN CONFIG FORM -->
    <form action="/whatsapp-config?action=save" method="POST" id="formWaConfig" class="space-y-6">

        <!-- CARD 1: STATUS INTEGRASI & NOMOR PENGIRIM -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">toggle_on</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            STATUS INTEGRASI & NOMOR PENGIRIM
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-normal">
                            Aktifkan integrasi WhatsApp dan atur nomor pengirim resmi sistem SiWarga.
                        </p>
                    </div>
                </div>

                <!-- Toggle Switch Live -->
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="isActiveToggle" value="1" class="sr-only peer" <?= $isActive ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-emerald-600"></div>
                    <span id="activeStatusLabel" class="ml-3 text-xs font-bold <?= $isActive ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' ?>">
                        <?= $isActive ? 'AKTIF' : 'NONAKTIF' ?>
                    </span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Dropdown Provider Gateway -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                        WhatsApp Gateway Provider
                    </label>
                    <div class="relative">
                        <select name="provider" class="w-full h-12 pl-4 pr-10 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500 transition cursor-pointer appearance-none">
                            <option value="fonnte" selected>Fonnte WhatsApp API (Official Gateway)</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-slate-400 pointer-events-none text-xl">expand_more</span>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5">
                        Layanan gateway pesan WhatsApp berbasis token Fonnte.
                    </p>
                </div>

                <!-- Input Nomor WhatsApp Pengirim -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                        Nomor WhatsApp Pengirim (Sender Number)
                    </label>
                    <div class="relative">
                        <input type="text" 
                               name="sender_number" 
                               id="senderNumberInput"
                               value="<?= htmlspecialchars($senderNumber) ?>" 
                               placeholder="Contoh: 081234567890 atau 6281234567890" 
                               class="w-full h-12 px-4 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-emerald-500 transition placeholder:text-slate-400">
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">phonelink_ring</span>
                        Bisa diganti sewaktu-waktu tanpa mengubah kode program. Pastikan nomor telah terhubung di Fonnte.
                    </p>
                </div>

                <!-- Input Jeda Antrean Antar Pesan -->
                <div class="md:col-span-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                        Jeda Antrean Pengiriman Antar Pesan (Queue Delay)
                    </label>
                    <div class="flex items-center gap-3">
                        <div class="relative w-48">
                            <input type="number" 
                                   name="queue_delay_seconds" 
                                   id="queueDelayInput"
                                   min="1" 
                                   max="600"
                                   value="<?= htmlspecialchars((string)$queueDelaySeconds) ?>" 
                                   class="w-full h-12 px-4 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            <span class="absolute right-4 top-3.5 text-xs font-bold text-slate-400">Detik</span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                            Setara dengan <span class="font-bold text-emerald-600 dark:text-emerald-400" id="queueDelayMinLabel"><?= round($queueDelaySeconds / 60, 1) ?> menit</span> antar pesan.
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">schedule</span>
                        Default: <strong>60 detik (1 menit)</strong> per pesan. Pesan diproses satu per satu dengan jeda teratur untuk mengurangi risiko pembatasan atau banned gateway Fonnte.
                    </p>
                </div>
            </div>
        </div>

        <!-- CARD 2: KREDENSIAL API KEY FONNTE SECARA AMAN -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">vpn_key</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            KREDENSIAL & VALIDASI API KEY FONNTE
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-normal">
                            Kredensial disimpan secara terenkripsi/masking demi mencegah kebocoran data.
                        </p>
                    </div>
                </div>

                <button type="button" id="btnCheckStatus" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded-xl transition border border-emerald-200 dark:border-emerald-800 cursor-pointer">
                    <span class="material-symbols-outlined text-base" id="iconCheckStatus">sync</span>
                    <span>Validasi & Cek Koneksi</span>
                </button>
            </div>

            <!-- API Key Input -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                        Fonnte API Key / Token Perangkat
                    </label>
                    <button type="button" id="toggleKeyVisibility" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
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
                           placeholder="Paste Token API Key Fonnte di sini..." 
                           class="w-full h-12 px-4 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-emerald-500 transition placeholder:text-slate-400">
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">lock</span>
                    API Key disimpan aman di file <code>storage/whatsapp_config.json</code> dan di-mask agar tidak bocor ke tampilan.
                </p>
            </div>

            <!-- STATUS KONEKSI DEVICE RESULT BOX -->
            <div id="deviceStatusBox" class="mt-4 p-4 rounded-xl border <?= (!empty($lastStatus['connected'])) ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800' : 'bg-slate-50 border-slate-200 dark:bg-slate-800/50 dark:border-slate-700' ?>">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-3.5 w-3.5">
                            <span class="<?= (!empty($lastStatus['connected'])) ? 'animate-ping bg-emerald-400' : 'bg-slate-400' ?> absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                            <span class="<?= (!empty($lastStatus['connected'])) ? 'bg-emerald-500' : 'bg-slate-400' ?> relative inline-flex rounded-full h-3.5 w-3.5" id="statusDot"></span>
                        </span>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200" id="deviceStatusTitle">
                                <?= !empty($lastStatus['connected']) ? '🟢 WHATSAPP TERHUBUNG (CONNECTED)' : (!empty($lastStatus['success']) ? '🟡 TERPUTUS (DISCONNECTED)' : '⚪ STATUS: BELUM DIVALIDASI') ?>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5" id="deviceStatusDesc">
                                <?= htmlspecialchars($lastStatus['message'] ?? 'Klik tombol "Validasi & Cek Koneksi" untuk memeriksa status perangkat WhatsApp di Fonnte.') ?>
                            </p>
                        </div>
                    </div>
                    <?php if (!empty($lastCheckedAt)): ?>
                        <span class="text-[11px] text-slate-400 font-mono" id="lastCheckedLabel">
                            Cek: <?= date('d/m/Y H:i', strtotime($lastCheckedAt)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Detail Device Info Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-3 border-t border-slate-200/60 dark:border-slate-700/60 text-xs" id="deviceDetailsGrid">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Nomor Device</span>
                        <span class="font-mono font-semibold text-slate-700 dark:text-slate-300" id="detailDeviceNum">
                            <?= htmlspecialchars($lastStatus['device'] ?? '-') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Nama Device</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300" id="detailDeviceName">
                            <?= htmlspecialchars($lastStatus['name'] ?? '-') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Sisa Kuota</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300" id="detailDeviceQuota">
                            <?= htmlspecialchars((string)($lastStatus['quota'] ?? '-')) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Masa Aktif</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300" id="detailDeviceExpired">
                            <?= htmlspecialchars($lastStatus['expired'] ?? '-') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: PENGUJIAN PENGIRIMAN PESAN WHATSAPP (TEST SEND) -->
        <div class="bg-white dark:bg-slate-900/90 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">send</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            UJI COBA PENGIRIMAN PESAN WHATSAPP (TEST SEND)
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-normal">
                            Kirim pesan tes langsung untuk memastikan API Key dan koneksi nomor WhatsApp berfungsi nyata.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                            Nomor WhatsApp Tujuan Tes
                        </label>
                        <input type="text" 
                               id="testTargetPhone" 
                               placeholder="Contoh: 081234567890" 
                               class="w-full h-11 px-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 transition placeholder:text-slate-400">
                        <p class="text-[11px] text-slate-400 mt-1">
                            Format lokal (08xx) atau internasional (628xx).
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                            Isi Pesan Uji Coba (Opsional)
                        </label>
                        <input type="text" 
                               id="testCustomMessage" 
                               placeholder="Pesan tes otomatis default akan digunakan jika dikosongkan..." 
                               class="w-full h-11 px-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition placeholder:text-slate-400">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" id="btnSendTest" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer">
                        <span class="material-symbols-outlined text-base" id="iconSendTest">forward_to_inbox</span>
                        <span>Kirim Pesan Tes Sekarang</span>
                    </button>
                    <span id="testSendResult" class="text-xs font-semibold"></span>
                </div>
            </div>
        </div>

        <!-- CARD 4: PANDUAN PENGATURAN & LOKASI PENYIMPANAN API KEY -->
        <div class="bg-gradient-to-br from-slate-50 to-blue-50/30 dark:from-slate-900/90 dark:to-slate-800/40 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm transition">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl flex-shrink-0 mt-0.5">help_outline</span>
                <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">
                        📌 Di Mana Menaruh & Menyimpan API Key Fonnte?
                    </h4>
                    <p>
                        Anda dapat menaruh dan mengatur API Key WhatsApp Fonnte melalui dua cara fleksibel:
                    </p>
                    <ul class="list-disc pl-4 space-y-1.5 mt-2">
                        <li>
                            <strong>Cara 1: Melalui Formulir Web Ini (Sangat Direkomendasikan)</strong><br>
                            Cukup masukkan token API Key pada kolom <em>"Fonnte API Key"</em> di atas, lalu klik <strong>"Simpan Konfigurasi WhatsApp"</strong>. Nilai disimpan secara aman di file terisolasi <code>storage/whatsapp_config.json</code> tanpa perlu menyentuh server atau kode program.
                        </li>
                        <li>
                            <strong>Cara 2: Melalui File Lingkungan (<code>.env</code>)</strong><br>
                            Anda juga dapat mendefinisikan kunci cadangan/default pada file <code>.env</code> proyek:
                            <pre class="mt-1 p-2 bg-slate-800 text-slate-100 rounded-lg font-mono text-[11px] overflow-x-auto">FONNTE_API_KEY=token_fonnte_anda_disini
FONNTE_SENDER=6281234567890</pre>
                        </li>
                        <li>
                            <strong>Cara Mendapatkan API Key & Menghubungkan Nomor WhatsApp:</strong><br>
                            1. Masuk ke dashboard <a href="https://fonnte.com" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 underline font-semibold">fonnte.com</a>.<br>
                            2. Tambahkan perangkat WhatsApp pada menu <em>Device</em>, lalu klik <em>Connect / Scan QR</em> menggunakan aplikasi WhatsApp di HP pengirim.<br>
                            3. Salin <strong>Token</strong> perangkat tersebut dan tempelkan ke kolom API Key di halaman ini.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TOMBOL SIMPAN KONFIGURASI -->
        <div class="pt-2">
            <button type="submit" id="btnSubmitForm" class="w-full md:w-auto px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer">
                <span class="material-symbols-outlined text-lg">save</span>
                Simpan Konfigurasi WhatsApp
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
    const isActiveToggle = document.getElementById('isActiveToggle');
    const activeStatusLabel = document.getElementById('activeStatusLabel');

    const btnCheckStatus = document.getElementById('btnCheckStatus');
    const iconCheckStatus = document.getElementById('iconCheckStatus');
    const deviceStatusBox = document.getElementById('deviceStatusBox');
    const deviceStatusTitle = document.getElementById('deviceStatusTitle');
    const deviceStatusDesc = document.getElementById('deviceStatusDesc');
    const statusDot = document.getElementById('statusDot');
    const detailDeviceNum = document.getElementById('detailDeviceNum');
    const detailDeviceName = document.getElementById('detailDeviceName');
    const detailDeviceQuota = document.getElementById('detailDeviceQuota');
    const detailDeviceExpired = document.getElementById('detailDeviceExpired');

    const btnSendTest = document.getElementById('btnSendTest');
    const iconSendTest = document.getElementById('iconSendTest');
    const testTargetPhone = document.getElementById('testTargetPhone');
    const testCustomMessage = document.getElementById('testCustomMessage');
    const testSendResult = document.getElementById('testSendResult');
    const senderNumberInput = document.getElementById('senderNumberInput');

    // 1. Toggle Mask/Visibility Key Fonnte
    let isKeyVisible = false;
    toggleBtn.addEventListener('click', function() {
        isKeyVisible = !isKeyVisible;
        if (isKeyVisible) {
            apiKeyInput.type = 'text';
            keyIcon.textContent = 'visibility_off';
            keyText.textContent = 'Sembunyikan';
            if (apiKeyInput.value.includes('•')) {
                apiKeyInput.dataset.savedMasked = apiKeyInput.value;
            }
        } else {
            apiKeyInput.type = 'password';
            keyIcon.textContent = 'visibility';
            keyText.textContent = 'Tampilkan';
        }
    });

    // Auto-select text on focus if masked
    apiKeyInput.addEventListener('focus', function() {
        if (this.value.includes('•')) {
            this.select();
        }
    });

    // 2. Toggle Status Aktif Label
    isActiveToggle.addEventListener('change', function() {
        if (this.checked) {
            activeStatusLabel.textContent = 'AKTIF';
            activeStatusLabel.className = 'ml-3 text-xs font-bold text-emerald-600 dark:text-emerald-400';
        } else {
            activeStatusLabel.textContent = 'NONAKTIF';
            activeStatusLabel.className = 'ml-3 text-xs font-bold text-slate-400 dark:text-slate-500';
        }
    });

    // 3. Validasi & Cek Status Koneksi Device via AJAX
    btnCheckStatus.addEventListener('click', async function() {
        iconCheckStatus.classList.add('animate-spin');
        deviceStatusTitle.textContent = '⏳ MEMERIKSA STATUS KE FONNTE...';
        deviceStatusDesc.textContent = 'Menghubungi server Fonnte untuk memvalidasi token dan status perangkat...';

        try {
            const fd = new FormData();
            const inputKey = apiKeyInput.value.trim();
            if (inputKey) {
                fd.append('api_key', inputKey);
            }

            const res = await fetch('/whatsapp-config?action=check_status', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();

            if (data.connected) {
                deviceStatusBox.className = 'mt-4 p-4 rounded-xl border bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800';
                deviceStatusTitle.textContent = '🟢 WHATSAPP TERHUBUNG (CONNECTED)';
                deviceStatusTitle.className = 'text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300';
                statusDot.className = 'bg-emerald-500 relative inline-flex rounded-full h-3.5 w-3.5';
                deviceStatusDesc.textContent = data.message || 'Perangkat WhatsApp berhasil terhubung aktif dengan Fonnte.';
            } else if (data.success) {
                deviceStatusBox.className = 'mt-4 p-4 rounded-xl border bg-amber-50/70 border-amber-200 dark:bg-amber-950/30 dark:border-amber-800';
                deviceStatusTitle.textContent = '🟡 TERPUTUS (DISCONNECTED)';
                deviceStatusTitle.className = 'text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300';
                statusDot.className = 'bg-amber-500 relative inline-flex rounded-full h-3.5 w-3.5';
                deviceStatusDesc.textContent = data.message || 'API Key valid, namun perangkat WhatsApp belum terhubung (Scan QR).';
            } else {
                deviceStatusBox.className = 'mt-4 p-4 rounded-xl border bg-rose-50/70 border-rose-200 dark:bg-rose-950/30 dark:border-rose-800';
                deviceStatusTitle.textContent = '🔴 GAGAL / TIDAK VALID';
                deviceStatusTitle.className = 'text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300';
                statusDot.className = 'bg-rose-500 relative inline-flex rounded-full h-3.5 w-3.5';
                deviceStatusDesc.textContent = data.message || 'Validasi API Key Fonnte gagal.';
            }

            detailDeviceNum.textContent = data.device || '-';
            detailDeviceName.textContent = data.name || '-';
            detailDeviceQuota.textContent = data.quota || '0';
            detailDeviceExpired.textContent = data.expired || '-';

            // Jika senderNumberInput masih kosong dan nomor device tersedia, otomatis isi
            if (!senderNumberInput.value && data.device && data.device !== '-') {
                senderNumberInput.value = data.device;
            }

        } catch (err) {
            deviceStatusBox.className = 'mt-4 p-4 rounded-xl border bg-rose-50/70 border-rose-200 dark:bg-rose-950/30 dark:border-rose-800';
            deviceStatusTitle.textContent = '❌ KESALAHAN JARINGAN';
            deviceStatusDesc.textContent = 'Gagal menghubungi server aplikasi: ' + err.message;
        } finally {
            iconCheckStatus.classList.remove('animate-spin');
        }
    });

    // 4. AJAX Kirim Pesan Tes WhatsApp
    btnSendTest.addEventListener('click', async function() {
        const target = testTargetPhone.value.trim();
        if (!target) {
            alert('Silakan masukkan nomor WhatsApp tujuan tes terlebih dahulu!');
            testTargetPhone.focus();
            return;
        }

        iconSendTest.textContent = 'hourglass_top';
        btnSendTest.disabled = true;
        btnSendTest.classList.add('opacity-75');
        testSendResult.textContent = '⏳ Sedang mengirim pesan via Fonnte...';
        testSendResult.className = 'text-xs font-semibold text-blue-600 dark:text-blue-400';

        try {
            const fd = new FormData();
            fd.append('target_phone', target);
            if (testCustomMessage.value.trim()) {
                fd.append('test_message', testCustomMessage.value.trim());
            }
            if (apiKeyInput.value.trim()) {
                fd.append('api_key', apiKeyInput.value.trim());
            }

            const res = await fetch('/whatsapp-config?action=send_test', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();

            if (data.success) {
                testSendResult.textContent = '✅ ' + (data.message || 'Pesan tes berhasil dikirim!');
                testSendResult.className = 'text-xs font-bold text-emerald-600 dark:text-emerald-400';
            } else {
                testSendResult.textContent = '❌ ' + (data.message || 'Gagal mengirim pesan.');
                testSendResult.className = 'text-xs font-bold text-rose-600 dark:text-rose-400';
            }
        } catch (err) {
            testSendResult.textContent = '❌ Error: ' + err.message;
            testSendResult.className = 'text-xs font-bold text-rose-600 dark:text-rose-400';
        } finally {
            iconSendTest.textContent = 'forward_to_inbox';
            btnSendTest.disabled = false;
            btnSendTest.classList.remove('opacity-75');
        }
    });

    // 5. Update display menit untuk jeda antrean
    const queueDelayInput = document.getElementById('queueDelayInput');
    const queueDelayMinLabel = document.getElementById('queueDelayMinLabel');
    if (queueDelayInput && queueDelayMinLabel) {
        queueDelayInput.addEventListener('input', function() {
            const sec = parseInt(this.value) || 0;
            const min = (sec / 60).toFixed(1);
            queueDelayMinLabel.textContent = min + ' menit';
        });
    }
});
</script>

