<?php
// ============================================
// PROSES REGISTRASI (DENGAN OTP EMAIL)
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Mailer.php'; // TAMBAHAN: Panggil Helper Email

$error = '';

// ============================================
// AMBIL DAFTAR RW & RT
// ============================================
try {
    $daftar_rw = $pdo->query("SELECT id, nomor, ketua FROM rw ORDER BY nomor")->fetchAll();
    $daftar_rt = $pdo->query("SELECT id, nomor, ketua FROM rt ORDER BY nomor")->fetchAll();
} catch (Exception $e) {
    $daftar_rw = $daftar_rt = [];
}

// ============================================
// PROSES REGISTRASI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $rw = trim($_POST['rw'] ?? '');
    $rt = trim($_POST['rt'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($confirm) || empty($no_hp) || empty($jenis_kelamin)) {
        $error = '⚠️ Semua field harus diisi!';
    } elseif (empty($rw) || empty($rt)) {
        $error = '⚠️ Pilih RW dan RT terlebih dahulu!';
    } elseif (!in_array($jenis_kelamin, ['L', 'P'])) {
        $error = '⚠️ Jenis kelamin tidak valid!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '⚠️ Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = '⚠️ Password minimal 6 karakter!';
    } elseif ($password !== $confirm) {
        $error = '⚠️ Password dan konfirmasi password tidak sama!';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = '⚠️ Email sudah terdaftar! Silakan login.';
        } else {
            // ============================================
            // SIMPAN SEMENTARA & KIRIM OTP
            // ============================================
            $otp_code = rand(100000, 999999);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Simpan semua data form ke dalam JSON untuk sementara (termasuk persetujuan WhatsApp)
            $wa_opt_in = !empty($_POST['wa_opt_in']) ? 1 : 0;
            $data_sementara = json_encode([
                'name' => $name, 'email' => $email, 'no_hp' => $no_hp, 
                'jenis_kelamin' => $jenis_kelamin, 'password' => $hash,
                'rw' => $rw, 'rt' => $rt, 'wa_opt_in' => $wa_opt_in
            ]);
            
            // Hapus OTP lama (kalau ada) untuk email ini
            $pdo->prepare("DELETE FROM email_otps WHERE email = ? AND type = 'register'")->execute([$email]);
            
            // Simpan OTP baru (berlaku 5 menit)
            $stmt = $pdo->prepare("INSERT INTO email_otps (email, otp_code, type, data, expires_at) VALUES (?, ?, 'register', ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
            $stmt->execute([$email, $otp_code, $data_sementara]);

            // Kirim Email OTP
            if (Mailer::sendOTP($email, $name, $otp_code)) {
                $_SESSION['verify_email'] = $email; // Simpan email di session buat halaman verifikasi
                header('Location: /verify-email'); exit;
            } else {
                $error = '❌ Gagal mengirim email verifikasi. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="h-full" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Daftar Akun - SIWARGA Desa</title>

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
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 pt-5 pb-2 flex items-center justify-between">
        <a href="/" class="flex items-center space-x-2.5">
            <div class="w-9 h-9 rounded-xl bg-blue-700 text-white flex items-center justify-center shadow-md shadow-blue-700/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
            </div>
            <div>
                <span class="text-lg font-extrabold tracking-tight text-slate-900">SIWARGA</span>
                <span class="ml-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100/90 border border-emerald-300/60 px-1.5 py-0.5 rounded">Desa Digital</span>
            </div>
        </a>
        <div class="flex items-center gap-3 text-xs font-medium text-slate-600">
            <span class="hidden sm:inline text-slate-600">Sudah punya akun?</span>
            <a href="/login" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs shadow-md shadow-blue-700/20 transition-all">
                <span class="material-symbols-outlined text-[16px]">login</span>
                <span>Login</span>
            </a>
        </div>
    </header>

    <!-- MAIN -->
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-6 sm:py-10">
        <div class="w-full max-w-xl">

            <div class="bg-white border border-slate-200/90 shadow-2xl shadow-blue-950/10 rounded-3xl p-6 sm:p-9 transition-all">

                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 shadow-lg shadow-emerald-700/25 text-white mb-3 ring-4 ring-emerald-50">
                        <span class="material-symbols-outlined text-[28px]">person_add</span>
                    </div>
                    <h1 class="text-2xl sm:text-[26px] font-extrabold text-slate-900 tracking-tight mb-1">
                        Daftar Akun Warga
                    </h1>
                    <p class="text-slate-500 text-xs sm:text-sm max-w-md mx-auto">
                        Buat akun untuk mengakses layanan administrasi desa
                    </p>
                    <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold">
                        <span class="material-symbols-outlined text-[15px]">verified_user</span>
                        <span>Data Anda Terlindungi &amp; Terenkripsi</span>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="material-symbols-outlined text-rose-600 text-base">error</span>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- TAMBAHAN AUTOCOMPLETE="OFF" DI FORM -->
                <form method="POST" action="" class="space-y-4" id="registerForm" autocomplete="off">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="name">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">badge</span>
                            </div>
                            <!-- AUTOCOMPLETE="OFF" DI INPUT -->
                            <input autocomplete="off" class="block w-full pl-10 pr-4 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs"
                                   id="name" name="name" type="text" placeholder="Contoh: Budi Santoso"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="email">
                            Email Aktif <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">mail</span>
                            </div>
                            <!-- AUTOCOMPLETE="OFF" DI INPUT -->
                            <input autocomplete="off" class="block w-full pl-10 pr-4 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs"
                                   id="email" name="email" type="email" placeholder="nama@domain.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="no_hp">
                            No. WhatsApp <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">smartphone</span>
                            </div>
                            <!-- AUTOCOMPLETE="OFF" DI INPUT -->
                            <input autocomplete="off" class="block w-full pl-10 pr-4 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs"
                                   id="no_hp" name="no_hp" type="text" placeholder="08xxxxxxxxxx"
                                   value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="jenis_kelamin">
                            Jenis Kelamin <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">wc</span>
                            </div>
                            <select name="jenis_kelamin" id="jenis_kelamin" required
                                    class="block w-full pl-10 pr-4 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs">
                                <option value="">-- Pilih L / P --</option>
                                <option value="L" <?= (($_POST['jenis_kelamin'] ?? '') === 'L') ? 'selected' : '' ?>>Laki-laki (L)</option>
                                <option value="P" <?= (($_POST['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan (P)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="rw">
                            RW <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">holiday_village</span>
                            </div>
                            <select name="rw" id="selectRw" required
                                    class="block w-full pl-10 pr-4 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs">
                                <option value="">-- Pilih RW --</option>
                                <?php foreach ($daftar_rw as $r): ?>
                                    <option value="<?= htmlspecialchars($r['nomor']) ?>" <?= (($_POST['rw'] ?? '') === $r['nomor']) ? 'selected' : '' ?>>
                                        RW <?= htmlspecialchars($r['nomor']) ?> — <?= htmlspecialchars($r['ketua'] ?? '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="rt">
                            RT <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">home</span>
                            </div>
                            <select name="rt" id="selectRt" required
                                    class="block w-full pl-10 pr-4 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs">
                                <option value="">-- Pilih RT --</option>
                                <?php foreach ($daftar_rt as $t): ?>
                                    <option value="<?= htmlspecialchars($t['nomor']) ?>" <?= (($_POST['rt'] ?? '') === $t['nomor']) ? 'selected' : '' ?>>
                                        RT <?= htmlspecialchars($t['nomor']) ?> — <?= htmlspecialchars($t['ketua'] ?? '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="password">
                            Password <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">lock</span>
                            </div>
                            <!-- GUNAKAN NEW-PASSWORD BIAR GAK MUNCUL SARAN PASSWORD LAMA -->
                            <input autocomplete="new-password" class="block w-full pl-10 pr-11 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs"
                                   id="password" name="password" type="password" placeholder="Minimal 6 karakter" required minlength="6">
                            <button class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 transition-colors focus:outline-none"
                                    id="togglePwd1" onclick="togglePassword('password','eyeIcon1','eyeOffIcon1')" type="button">
                                <span class="material-symbols-outlined text-[19px]" id="eyeIcon1">visibility</span>
                                <span class="material-symbols-outlined text-[19px] hidden" id="eyeOffIcon1">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" for="confirm_password">
                            Konfirmasi Password <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <span class="material-symbols-outlined text-[19px]">lock_reset</span>
                            </div>
                            <!-- GUNAKAN NEW-PASSWORD DI KONFIRMASI -->
                            <input autocomplete="new-password" class="block w-full pl-10 pr-11 py-2.5 sm:py-3 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent transition-all shadow-xs"
                                   id="confirm_password" name="confirm_password" type="password" placeholder="Ulangi password" required>
                            <button class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 transition-colors focus:outline-none"
                                    id="togglePwd2" onclick="togglePassword('confirm_password','eyeIcon2','eyeOffIcon2')" type="button">
                                <span class="material-symbols-outlined text-[19px]" id="eyeIcon2">visibility</span>
                                <span class="material-symbols-outlined text-[19px] hidden" id="eyeOffIcon2">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-start gap-2 pt-1">
                        <input class="mt-0.5 w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600 cursor-pointer" id="wa_opt_in" name="wa_opt_in" type="checkbox" value="1">
                        <label class="text-xs text-slate-600 leading-relaxed cursor-pointer" for="wa_opt_in">
                            Saya setuju menerima informasi penting, pengumuman desa, dan update layanan melalui WhatsApp.
                        </label>
                    </div>

                    <div class="flex items-start gap-2 pt-1">
                        <input class="mt-0.5 w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600 cursor-pointer" id="terms" name="terms" type="checkbox" required>
                        <label class="text-xs text-slate-600 leading-relaxed cursor-pointer" for="terms">
                            Saya menyetujui <a href="#" class="text-blue-700 font-semibold hover:underline">Syarat &amp; Ketentuan</a> serta <a href="#" class="text-blue-700 font-semibold hover:underline">Kebijakan Privasi</a> SIWARGA Desa.
                        </label>
                    </div>

                    <button class="w-full py-3 sm:py-3.5 px-4 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white font-bold text-sm sm:text-base rounded-xl shadow-lg shadow-emerald-700/20 hover:shadow-emerald-700/35 transition-all duration-200 flex items-center justify-center space-x-2"
                            id="registerBtn" type="submit">
                        <span class="flex items-center space-x-2" id="registerBtnText">
                            <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
                            <span>Daftar Sekarang</span>
                        </span>
                    </button>
                </form>

                <!-- DIVIDER -->
                <div class="flex items-center gap-4 my-5">
                    <div class="flex-1 h-px bg-slate-200"></div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Atau</span>
                    <div class="flex-1 h-px bg-slate-200"></div>
                </div>

                <!-- GOOGLE -->
                <a href="/auth/google" class="w-full py-3 sm:py-3.5 px-4 bg-white border-2 border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-sm rounded-xl transition-all duration-200 flex items-center justify-center space-x-3 shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Daftar dengan Google</span>
                </a>

                <p class="mt-5 text-center text-xs text-slate-600">
                    Sudah punya akun?
                    <a class="font-bold text-blue-700 hover:text-blue-800 hover:underline inline-flex items-center gap-0.5" href="/login">
                        Login di sini
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </p>

                <div class="mt-3 text-center">
                    <a href="/" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-blue-700 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function togglePassword(inputId, eyeOnId, eyeOffId) {
        const input = document.getElementById(inputId);
        const eyeOn = document.getElementById(eyeOnId);
        const eyeOff = document.getElementById(eyeOffId);
        if (input.type === 'password') {
            input.type = 'text';
            eyeOn.classList.add('hidden');
            eyeOff.classList.remove('hidden');
        } else {
            input.type = 'password';
            eyeOn.classList.remove('hidden');
            eyeOff.classList.add('hidden');
        }
    }

    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = document.getElementById('registerBtn');
        const btnText = document.getElementById('registerBtnText');
        btn.disabled = true;
        btnText.innerHTML = `
            <span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
            <span>Mengirim OTP ke Email...</span>
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
                window.initAurora(host, { colorStops: ['#0C2D6B', '#7DD3FC', '#0C2D6B'], blend: 0.6, amplitude: 1.0, speed: 0.5, lightMode: false });
            }
        }
        if (typeof window.initAurora === 'function') initAuroraWhenReady();
        else window.addEventListener('aurora-ready', initAuroraWhenReady, { once: true });

        function initStrandsWhenReady() {
            const host = document.getElementById('strands-host');
            if (host && typeof window.initStrands === 'function') {
                window.initStrands(host, { colors: ['#ffffff', '#7DD3FC', '#ffffff'], count: 3, speed: 0.5, amplitude: 1.0, waviness: 1.0, thickness: 0.9, glow: 3.5, taper: 3, spread: 1, intensity: 0.85, saturation: 1.8, opacity: 1.0, scale: 1.5, glass: false, refraction: 1, dispersion: 1, glassSize: 1, hueShift: 0 });
            }
        }
        if (typeof window.initStrands === 'function') initStrandsWhenReady();
        else window.addEventListener('strands-ready', initStrandsWhenReady, { once: true });
    });
</script>

</body>
</html>