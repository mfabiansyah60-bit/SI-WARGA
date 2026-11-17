<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIWARGA Desa — Sistem Informasi Terpadu Pelayanan & Administrasi Warga</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { primary: '#00288e', 'primary-container': '#1e40af' },
                fontFamily: { sans: ['Inter', 'system-ui', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'] }
            } }
        }
    </script>

    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

        .hero-bg {
            background: linear-gradient(135deg, #eef4ff 0%, #dce8ff 50%, #e8f0ff 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.35; pointer-events: none; }
        .blob-1 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(30,64,175,0.35), transparent 70%); top: -150px; left: -100px; }
        .blob-2 { width: 450px; height: 450px; background: radial-gradient(circle, rgba(125,211,252,0.35), transparent 70%); top: -80px; right: -120px; }
        .blob-3 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(59,130,246,0.28), transparent 70%); bottom: -200px; left: 40%; }
        .grid-pattern {
            background-size: 32px 32px;
            background-image:
                linear-gradient(to right, rgba(100, 116, 139, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(100, 116, 139, 0.05) 1px, transparent 1px);
        }

        /* LAYER 1: AURORA galaksi */
        .aurora-host {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: 0.75;
            z-index: 1;
        }
        .aurora-host canvas { display: block; width: 100%; height: 100%; }

        /* LAYER 2: Overlay gelap tipis */
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(12,45,107,0.28) 0%,
                rgba(12,45,107,0.10) 50%,
                rgba(12,45,107,0.28) 100%
            );
            z-index: 2;
            pointer-events: none;
        }

        /* LAYER 3: STRANDS — pita cahaya (lebih halus) */
        .strands-host {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            mix-blend-mode: screen;
            opacity: 0.95;
            z-index: 3;
            filter: brightness(1.25) saturate(1.2);
        }
        .strands-host canvas { display: block; width: 100%; height: 100%; }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased bg-white">

<!-- NAVBAR -->
<header class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-700 to-blue-900 text-white flex items-center justify-center shadow-md shadow-blue-700/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-extrabold tracking-tight text-slate-900">SIWARGA</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100/90 border border-emerald-300/60 px-1.5 py-0.5 rounded">Desa Digital</span>
                </div>
            </a>
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#fitur" class="hover:text-blue-700 transition-colors">Fitur</a>
                <a href="#layanan" class="hover:text-blue-700 transition-colors">Layanan</a>
                <a href="#tentang" class="hover:text-blue-700 transition-colors">Tentang</a>
                <a href="#kontak" class="hover:text-blue-700 transition-colors">Kontak</a>
            </nav>
            <div class="flex items-center gap-2">
                <a href="/register" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-bold text-blue-700 hover:text-blue-800 transition-colors">Daftar</a>
                <a href="/login" class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-md shadow-blue-700/20 transition-all">
                    <span class="material-symbols-outlined text-[18px]">login</span>
                    <span>Login</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- HERO -->
<main class="hero-bg grid-pattern pt-16 min-h-screen flex items-center relative">

    <!-- LAYER 1: AURORA galaksi -->
    <div id="aurora-host" class="aurora-host"></div>

    <!-- LAYER 2: Overlay gelap tipis -->
    <div class="hero-overlay"></div>

    <!-- LAYER 3: STRANDS pita cahaya -->
    <div id="strands-host" class="strands-host"></div>

    <!-- LAYER 4: Blobs -->
    <div class="hero-blob blob-1"></div>
    <div class="hero-blob blob-2"></div>
    <div class="hero-blob blob-3"></div>

    <!-- LAYER 5: KONTEN -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 w-full" style="z-index: 20;">
        <div class="max-w-3xl mx-auto text-center">

            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/85 backdrop-blur-md border border-blue-200 text-blue-800 text-xs font-bold mb-6 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                </span>
                Terintegrasi Sistem Kemendagri
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight tracking-tight">
                Desa Digital,<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-600 to-sky-500">Pelayanan Lebih Cepat</span>
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-700 leading-relaxed max-w-2xl mx-auto">
                SIWARGA menghubungkan warga, RT/RW, dan pemerintah desa dalam satu platform.
                Ajukan surat, sampaikan pengaduan, dan pantau pelayanan — semua dari genggaman Anda.
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="/register" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shadow-lg shadow-blue-700/25 transition-all">
                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                    Daftar Sebagai Warga
                </a>
                <a href="/login" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-white/95 hover:bg-white backdrop-blur-md text-slate-800 border border-slate-300 text-sm font-bold shadow-md transition-all">
                    <span class="material-symbols-outlined text-[20px]">login</span>
                    Masuk ke Sistem
                </a>
            </div>

            <div class="mt-10 flex items-center justify-center gap-6 text-sm text-slate-700">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-[20px]">verified</span>
                    <span class="font-medium">Gratis untuk warga</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-[20px]">lock</span>
                    <span class="font-medium">Data terenkripsi</span>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- FITUR -->
<section id="fitur" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="text-xs font-bold uppercase tracking-widest text-blue-700 mb-2">Fitur Utama</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Semua Kebutuhan Administrasi Desa</h2>
            <p class="mt-3 text-slate-500 max-w-2xl mx-auto">Satu aplikasi untuk semua urusan warga — dari surat-menyurat sampai pengaduan.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="group p-6 rounded-2xl bg-white border border-slate-200 hover:border-blue-300 hover:shadow-xl transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"><span class="material-symbols-outlined text-[26px]">description</span></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Surat Digital</h3>
                <p class="text-sm text-slate-600 leading-relaxed">Ajukan surat domisili, SKTM, dan surat keterangan lain tanpa antri. Proses cepat & transparan.</p>
            </div>
            <div class="group p-6 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:shadow-xl transition-all">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"><span class="material-symbols-outlined text-[26px]">campaign</span></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Pengaduan Warga</h3>
                <p class="text-sm text-slate-600 leading-relaxed">Laporkan masalah infrastruktur, keamanan, atau layanan. Pantau tanggapan dari desa secara real-time.</p>
            </div>
            <div class="group p-6 rounded-2xl bg-white border border-slate-200 hover:border-purple-300 hover:shadow-xl transition-all">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"><span class="material-symbols-outlined text-[26px]">groups</span></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Data Warga Terpadu</h3>
                <p class="text-sm text-slate-600 leading-relaxed">Database warga yang terintegrasi dengan RT/RW, memudahkan pendataan & verifikasi.</p>
            </div>
            <div class="group p-6 rounded-2xl bg-white border border-slate-200 hover:border-amber-300 hover:shadow-xl transition-all">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"><span class="material-symbols-outlined text-[26px]">smart_toy</span></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Asisten AI Desa</h3>
                <p class="text-sm text-slate-600 leading-relaxed">Tanya jawab seputar prosedur administrasi dengan asisten AI yang tersedia 24/7.</p>
            </div>
            <div class="group p-6 rounded-2xl bg-white border border-slate-200 hover:border-rose-300 hover:shadow-xl transition-all">
                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"><span class="material-symbols-outlined text-[26px]">bar_chart</span></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Laporan & Statistik</h3>
                <p class="text-sm text-slate-600 leading-relaxed">Pantau tren pelayanan, pengaduan, dan aktivitas desa dalam grafik yang mudah dipahami.</p>
            </div>
            <div class="group p-6 rounded-2xl bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-xl transition-all">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"><span class="material-symbols-outlined text-[26px]">security</span></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Aman & Terenkripsi</h3>
                <p class="text-sm text-slate-600 leading-relaxed">Data warga dilindungi dengan enkripsi standar BSSN. Hak akses ketat sesuai peran.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section id="tentang" class="py-20 bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-900 relative overflow-hidden">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4">Siap Melayani Warga Lebih Baik?</h2>
        <p class="text-blue-100 text-base sm:text-lg mb-8 max-w-2xl mx-auto">Bergabung dengan ribuan warga yang sudah merasakan kemudahan layanan administrasi desa.</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="/register" class="inline-flex items-center gap-2 px-7 py-4 rounded-xl bg-white hover:bg-slate-100 text-blue-800 text-sm font-bold shadow-lg transition-all">
                <span class="material-symbols-outlined text-[20px]">person_add</span> Daftar Sekarang
            </a>
            <a href="/login" class="inline-flex items-center gap-2 px-7 py-4 rounded-xl bg-blue-900/50 hover:bg-blue-900/70 text-white border border-blue-400/40 text-sm font-bold transition-all">
                <span class="material-symbols-outlined text-[20px]">login</span> Login
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer id="kontak" class="bg-slate-900 text-slate-400 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="pt-6 border-t border-slate-800 text-center text-xs">
            © <?= date('Y') ?> <strong class="text-white">SIWARGA Desa Digital</strong>. Terintegrasi dengan Sistem Tata Kelola Pemerintahan Desa.
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="/js/aurora.js"></script>
<script src="/js/strands.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // ===== AURORA (galaksi background) =====
        function initAuroraWhenReady() {
            const host = document.getElementById('aurora-host');
            if (host && typeof window.initAurora === 'function') {
                window.initAurora(host, {
                    colorStops: ['#0C2D6B', '#7DD3FC', '#0C2D6B'],
                    blend: 0.6,
                    amplitude: 1.0,
                    speed: 0.6,
                    lightMode: false
                });
            }
        }
        if (typeof window.initAurora === 'function') {
            initAuroraWhenReady();
        } else {
            window.addEventListener('aurora-ready', initAuroraWhenReady, { once: true });
        }

        // ===== STRANDS (pita cahaya — ukuran seimbang) =====
        function initStrandsWhenReady() {
            const host = document.getElementById('strands-host');
            if (host && typeof window.initStrands === 'function') {
                window.initStrands(host, {
                    // Warna cerah: putih & cyan biar nge-pop di atas aurora
                    colors: ['#ffffff', '#7DD3FC', '#ffffff'],
                    count: 3,               // cuma 3 pita biar gak rame
                    speed: 0.5,             // kecepatan normal
                    amplitude: 1.0,         // gelombang normal
                    waviness: 1.0,
                    thickness: 0.9,         // tebal secukupnya
                    glow: 3.5,              // glow sedang
                    taper: 3,
                    spread: 1,
                    intensity: 0.85,        // intensity pas
                    saturation: 1.8,
                    opacity: 1.0,
                    scale: 1.5,
                    glass: false,
                    refraction: 1,
                    dispersion: 1,
                    glassSize: 1,
                    hueShift: 0
                });
            }
        }
        if (typeof window.initStrands === 'function') {
            initStrandsWhenReady();
        } else {
            window.addEventListener('strands-ready', initStrandsWhenReady, { once: true });
        }
    });
</script>

</body>
</html>