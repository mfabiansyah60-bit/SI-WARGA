<?php
// ============================================
// PROSES LOGIN (FIXED: STRICT ACTIVE CHECK)
// ============================================
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $ok = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $ok = true;
                } elseif (md5($password) === $user['password']) {
                    $ok = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $up->execute([$newHash, $user['id']]);
                }
            }

            if ($ok) {
                $status = $user['status_verifikasi'] ?? 'active';

                // ============================================
                // FIX LOGIKA: HANYA BOLEH LOGIN KALAU STATUSNYA 'active'
                // ============================================
                if ($status !== 'active') {
                    if ($status === 'pending') {
                        $error = '⏳ Akun Anda masih menunggu verifikasi Ketua RT. Silakan hubungi RT setempat.';
                    } elseif ($status === 'rt_approved') {
                        $error = '⏳ Akun Anda sudah diverifikasi RT, menunggu konfirmasi Ketua RW.';
                    } elseif ($status === 'rejected') {
                        $error = '❌ Maaf, pendaftaran akun Anda ditolak oleh Petugas. Silakan hubungi Ketua RT/RW untuk info lebih lanjut.';
                    } else {
                        $error = '❌ Status akun Anda tidak valid atau diblokir. Hubungi Admin Desa.';
                    }
                } else {
                    // Jika status 'active', baru boleh masuk
                    session_unset();
                    $_SESSION['user']    = $user;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role']    = $user['role'];
                    header('Location: /dashboard');
                    exit;
                }
            } else {
                $error = '❌ Email atau password salah!';
            }
        } catch (Exception $e) {
            $error = '❌ Error: ' . $e->getMessage();
        }
    } else {
        $error = '⚠️ Harap isi email dan password!';
    }
}

// ============================================
// DEMO LOGIN (Buat testing by URL)
// ============================================
if (isset($_GET['demo'])) {
    $demo = $_GET['demo'];
    $demoEmails = [
        'super_admin' => 'super@admin.com',
        'desa'        => 'desa@demo.com',
        'rw'          => 'rw@demo.com',
        'rt'          => 'rt@demo.com',
        'warga'       => 'warga@demo.com'
    ];
    if (isset($demoEmails[$demo])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$demoEmails[$demo]]);
            $user = $stmt->fetch();
            if ($user) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['user']    = $user;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                header('Location: /dashboard');
                exit;
            }
        } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html class="h-full" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Login — SIWARGA Desa</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0" rel="stylesheet">

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: { extend: {
                colors: {
                    "primary": "#00288e",
                    "primary-container": "#1e40af",
                },
                fontFamily: {
                    sans: ['Inter', 'system-ui', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'],
                    body: ['Inter', 'system-ui', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif']
                }
            } }
        }
    </script>

    <style>
        html, body { height: auto; min-height: 100%; }
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .civic-mesh-bg {
            background: linear-gradient(135deg, #eef4ff 0%, #dce8ff 50%, #e8f0ff 100%);
            position: relative;
            overflow-x: hidden;
        }
        .civic-grid-pattern {
            background-size: 32px 32px;
            background-image:
                linear-gradient(to right, rgba(100, 116, 139, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(100, 116, 139, 0.05) 1px, transparent 1px);
        }

        .aurora-host {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: 0.6;
            z-index: 0;
        }
        .aurora-host canvas { display: block; width: 100%; height: 100%; }

        .hero-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(12,45,107,0.20) 0%, rgba(12,45,107,0.08) 50%, rgba(12,45,107,0.20) 100%);
            z-index: 1;
            pointer-events: none;
        }

        .strands-host {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            mix-blend-mode: screen;
            opacity: 0.85;
            z-index: 2;
            filter: brightness(1.2) saturate(1.15);
        }
        .strands-host canvas { display: block; width: 100%; height: 100%; }

        .content-layer { position: relative; z-index: 10; }
    </style>
</head>
<body class="min-h-full font-sans text-slate-800 antialiased civic-mesh-bg civic-grid-pattern flex flex-col selection:bg-blue-600 selection:text-white">

<!-- EFEK BACKGROUND -->
<div id="aurora-host" class="aurora-host"></div>
<div class="hero-overlay"></div>
<div id="strands-host" class="strands-host"></div>

<!-- KONTEN -->
<div class="content-layer flex flex-col min-h-screen w-full">

    <!-- HEADER -->
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 pt-6 pb-2 flex items-center justify-between">
        <a href="/" class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-700 text-white flex items-center justify-center shadow-lg shadow-blue-700/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
            </div>
            <div>
                <span class="text-xl font-extrabold tracking-tight text-slate-900">SIWARGA</span>
                <span class="ml-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100/90 border border-emerald-300/60 px-2 py-0.5 rounded shadow-sm">Desa Digital</span>
            </div>
        </a>
        <div class="flex items-center gap-4 text-xs font-medium text-slate-600">
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/90 backdrop-blur-sm border border-slate-200/80 shadow-sm">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="text-[11.5px] text-slate-700 font-bold">Live Sync Kemendagri</span>
            </div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-8 sm:py-12">
        <div class="w-full max-w-[440px]">

            <div class="bg-white/95 backdrop-blur-xl border border-slate-200/80 shadow-[0_20px_50px_-12px_rgba(12,45,107,0.15)] rounded-[28px] p-7 sm:p-10 transition-all">

                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-[20px] bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-900 shadow-xl shadow-blue-700/30 text-white mb-4 ring-4 ring-blue-50/50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-[24px] sm:text-[28px] font-extrabold text-slate-900 tracking-tight mb-2 leading-tight">
                        Selamat Datang di Portal<br>SIWARGA Desa
                    </h1>
                    <p class="text-slate-500 text-[13px] sm:text-[14px] max-w-sm mx-auto leading-relaxed">
                        Sistem Informasi Terpadu Pelayanan &amp; Administrasi Warga
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[13px] flex items-start gap-2.5 shadow-sm">
                        <span class="material-symbols-outlined text-rose-600 text-[18px] mt-0.5">error</span>
                        <span class="font-medium leading-relaxed"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- TRIK ANTI-AUTOFILL CHROME -->
                <form action="" class="space-y-5" id="loginForm" method="POST" autocomplete="off">
                    <!-- Dummy input buat nipu Chrome -->
                    <input type="text" name="fakeusernameremembered" style="display:none">
                    <input type="password" name="fakepasswordremembered" style="display:none">

                    <div>
                        <label class="block text-[13px] font-bold text-slate-700 mb-2" for="email">
                            Alamat Email Resmi / NIK <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[20px]">mail</span>
                            </div>
                            <input autocomplete="off" class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-[15px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all shadow-sm"
                                   id="email" name="email" placeholder="nama@domain.com atau nik@warga.id" required type="text">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-[13px] font-bold text-slate-700" for="password">
                                Kata Sandi <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[20px]">lock</span>
                            </div>
                            <!-- new-password ngalangin Chrome nyaranin password lama -->
                            <input autocomplete="new-password" class="block w-full pl-11 pr-12 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-[15px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all shadow-sm"
                                   id="password" name="password" placeholder="Masukkan kata sandi akun" required type="password">
                            <button class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 transition-colors focus:outline-none"
                                    id="togglePasswordBtn" onclick="togglePasswordVisibility()" type="button">
                                <span class="material-symbols-outlined text-[20px]" id="eyeIcon">visibility</span>
                                <span class="material-symbols-outlined text-[20px] hidden" id="eyeOffIcon">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input class="w-[18px] h-[18px] rounded border-slate-300 text-blue-700 focus:ring-blue-600 cursor-pointer transition-all" id="remember" name="remember" type="checkbox">
                            <span class="text-[13px] text-slate-600 font-medium">Ingat saya</span>
                        </label>
                        <a href="/forgot-password" class="text-[13px] font-bold text-blue-700 hover:text-blue-800 hover:underline transition-colors">
                            Lupa kata sandi?
                        </a>
                    </div>

                    <button class="w-full py-3.5 px-4 bg-blue-700 hover:bg-blue-800 active:bg-blue-900 text-white font-bold text-[15px] rounded-xl shadow-lg shadow-blue-700/25 hover:shadow-blue-700/40 transition-all duration-200 flex items-center justify-center space-x-2 mt-2"
                            id="loginSubmitBtn" type="submit">
                        <span class="flex items-center space-x-2" id="loginBtnText">
                            <span>Masuk ke Sistem</span>
                            <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                        </span>
                    </button>
                </form>

                <!-- ============ DIVIDER & GOOGLE ============ -->
                <div class="flex items-center gap-4 my-6">
                    <div class="flex-1 h-px bg-slate-200"></div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Atau</span>
                    <div class="flex-1 h-px bg-slate-200"></div>
                </div>

                <a href="/auth/google" class="w-full py-3.5 px-4 bg-white border-2 border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-800 font-bold text-[14.5px] rounded-xl transition-all duration-200 flex items-center justify-center space-x-3 shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Lanjut dengan Google</span>
                </a>
                <!-- ===================================== -->

                <p class="mt-8 text-center text-[13.5px] text-slate-600">
                    Belum punya akun?
                    <a class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline inline-flex items-center gap-1 ml-1" href="/register">
                        Registrasi Mandiri Warga
                        <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                    </a>
                </p>

            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-[12px] text-slate-500 font-medium">
        <div>
            © <?= date('Y') ?> <strong class="text-slate-700 font-bold">SIWARGA Desa Digital</strong>. Terintegrasi dengan Sistem Tata Kelola Pemerintahan Desa.
        </div>
        <div class="flex items-center space-x-4">
            <a class="hover:text-blue-700 transition-colors inline-flex items-center gap-1.5" href="javascript:void(0)" onclick="alert('Layanan Bantuan & Call Center SIWARGA: 1500-DESA / WhatsApp 0812-3456-7890')">
                <span class="material-symbols-outlined text-[16px]">support_agent</span>
                Call Center
            </a>
            <span class="text-slate-300">•</span>
            <a class="hover:text-slate-800 transition-colors" href="#">Kebijakan Privasi</a>
        </div>
    </footer>

</div>

<script>
    function togglePasswordVisibility() {
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeOffIcon = document.getElementById('eyeOffIcon');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.classList.add('hidden');
            eyeOffIcon.classList.remove('hidden');
        } else {
            pwdInput.type = 'password';
            eyeIcon.classList.remove('hidden');
            eyeOffIcon.classList.add('hidden');
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginSubmitBtn');
        const btnText = document.getElementById('loginBtnText');
        btn.disabled = true;
        btnText.innerHTML = `
            <span class="animate-spin inline-block h-5 w-5 border-2 border-white border-t-transparent rounded-full mr-2"></span>
            <span>Memverifikasi...</span>
        `;
    });
</script>

<script src="/js/aurora.js"></script>
<script src="/js/strands.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function initAuroraWhenReady() {
            const host = document.getElementById('aurora-host');
            if (host && typeof window.initAurora === 'function') {
                window.initAurora(host, {
                    colorStops: ['#0C2D6B', '#7DD3FC', '#0C2D6B'],
                    blend: 0.6,
                    amplitude: 1.0,
                    speed: 0.5,
                    lightMode: false
                });
            }
        }
        if (typeof window.initAurora === 'function') initAuroraWhenReady();
        else window.addEventListener('aurora-ready', initAuroraWhenReady, { once: true });

        function initStrandsWhenReady() {
            const host = document.getElementById('strands-host');
            if (host && typeof window.initStrands === 'function') {
                window.initStrands(host, {
                    colors: ['#ffffff', '#7DD3FC', '#ffffff'],
                    count: 3,
                    speed: 0.5,
                    amplitude: 1.0,
                    waviness: 1.0,
                    thickness: 0.9,
                    glow: 3.5,
                    taper: 3,
                    spread: 1,
                    intensity: 0.85,
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
        if (typeof window.initStrands === 'function') initStrandsWhenReady();
        else window.addEventListener('strands-ready', initStrandsWhenReady, { once: true });
    });
</script>

</body>
</html>