<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Mailer.php'; // Panggil helper untuk kirim email

$email = $_SESSION['verify_email'] ?? '';
if (empty($email) && !isset($_GET['success'])) { header('Location: /register'); exit; }

$error = '';
$success_msg = '';
$success = false;

// ============================================
// LOGIKA KIRIM ULANG OTP (RESEND)
// ============================================
if (isset($_GET['resend']) && !empty($email)) {
    try {
        // 1. Ambil nama user dari data sementara di email_otps
        $stmt = $pdo->prepare("SELECT data FROM email_otps WHERE email = ? AND type = 'register' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        
        if ($row) {
            $data = json_decode($row['data'], true);
            $nama_user = $data['name'] ?? 'Warga';
            
            // 2. Bikin kode OTP baru
            $otp_baru = rand(100000, 999999);
            
            // 3. Update kode OTP & perpanjang waktu 5 menit
            $stmt = $pdo->prepare("UPDATE email_otps SET otp_code = ?, expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE email = ? AND type = 'register'");
            $stmt->execute([$otp_baru, $email]);
            
            // 4. Kirim ke Email
            if (Mailer::sendOTP($email, $nama_user, $otp_baru)) {
                $success_msg = '✅ Kode OTP baru telah dikirim ke email Anda.';
            } else {
                $error = '❌ Gagal mengirim ulang kode. Coba lagi nanti.';
            }
        } else {
            $error = '❌ Data pendaftaran tidak ditemukan. Silakan daftar ulang.';
        }
    } catch (Exception $e) {
        $error = '❌ Terjadi kesalahan sistem.';
    }
}

// ============================================
// PROSES VERIFIKASI
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = '';
    for ($i = 1; $i <= 6; $i++) { $otp_input .= $_POST['otp' . $i] ?? ''; }

    if (strlen($otp_input) !== 6) {
        $error = 'Masukkan 6 digit kode OTP!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM email_otps WHERE email = ? AND otp_code = ? AND type = 'register' AND expires_at > NOW()");
        $stmt->execute([$email, $otp_input]);
        $otpData = $stmt->fetch();

        if ($otpData) {
            $data = json_decode($otpData['data'], true);
            // OTP tidak otomatis mengaktifkan persetujuan: hanya aktif jika dicentang eksplisit saat pendaftaran
            $optIn = !empty($data['wa_opt_in']) ? 1 : 0;
            $optInAt = ($optIn === 1) ? date('Y-m-d H:i:s') : null;
            
            // Simpan ke tabel users dengan status pending (sinkron no_hp, no_telp, dan wa_opt_in)
            $stmt = $pdo->prepare("INSERT INTO users (name, email, no_hp, no_telp, jenis_kelamin, password, role, rw, rt, status_verifikasi, wa_opt_in, wa_opt_in_at) VALUES (?, ?, ?, ?, ?, ?, 'warga', ?, ?, 'pending', ?, ?)");
            $stmt->execute([$data['name'], $data['email'], $data['no_hp'], $data['no_hp'], $data['jenis_kelamin'], $data['password'], $data['rw'], $data['rt'], $optIn, $optInAt]);

            // Hapus OTP & Session
            $pdo->prepare("DELETE FROM email_otps WHERE email = ? AND type = 'register'")->execute([$email]);
            unset($_SESSION['verify_email']);

            $success = true;
        } else {
            $error = 'Kode OTP Salah atau sudah kadaluarsa. Silakan klik "Kirim Ulang Kode".';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F8F9FA]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email — SIWARGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0" />
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="h-full flex items-center justify-center p-4">

    <!-- TOAST NOTIFIKASI ERROR -->
    <?php if ($error): ?>
        <div id="toast-error" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 bg-rose-600 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 transition-all duration-300 transform -translate-y-20 opacity-0">
            <span class="material-symbols-outlined text-[24px]">error</span>
            <div>
                <div class="font-extrabold text-sm">Verifikasi Gagal</div>
                <div class="text-xs font-medium text-rose-100"><?= htmlspecialchars($error) ?></div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-error');
                toast.classList.remove('-translate-y-20', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
                setTimeout(() => { toast.classList.add('-translate-y-20', 'opacity-0'); }, 4000);
            }, 100);
        </script>
    <?php endif; ?>

    <!-- TOAST NOTIFIKASI SUKSES KIRIM ULANG -->
    <?php if ($success_msg): ?>
        <div id="toast-success" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 bg-emerald-600 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 transition-all duration-300 transform -translate-y-20 opacity-0">
            <span class="material-symbols-outlined text-[24px]">mark_email_read</span>
            <div>
                <div class="font-extrabold text-sm">Email Terkirim</div>
                <div class="text-xs font-medium text-emerald-100"><?= htmlspecialchars($success_msg) ?></div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-success');
                toast.classList.remove('-translate-y-20', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
                setTimeout(() => { toast.classList.add('-translate-y-20', 'opacity-0'); }, 4000);
            }, 100);
        </script>
    <?php endif; ?>

    <!-- KONDISI 1: SUKSES VERIFIKASI -->
    <?php if ($success): ?>
        <div class="w-full max-w-[420px] bg-white rounded-[24px] p-8 border border-gray-200 shadow-xl text-center transition-all duration-500">
            
            <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner animate-bounce">
                <span class="material-symbols-outlined text-[40px]">check_circle</span>
            </div>
            
            <h1 class="text-2xl font-extrabold text-gray-900 mb-3">Kode OTP Benar!</h1>
            <p class="text-[14px] text-gray-500 mb-8 leading-relaxed">
                Silakan menunggu verifikasi akun Anda. <br>
                <span class="font-semibold text-amber-600 mt-2 inline-block">⏳ Akun Anda sedang ditinjau oleh Ketua RT/RW setempat. Anda baru bisa login setelah akun disetujui.</span>
            </p>

            <a href="/login" class="w-full inline-flex items-center justify-center py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-600/30 transition-all">
                Lanjut ke Halaman Login
                <span class="material-symbols-outlined text-[18px] ml-2">arrow_forward</span>
            </a>
        </div>

    <!-- KONDISI 2: FORM INPUT OTP -->
    <?php else: ?>
        <div class="w-full max-w-[420px] bg-white rounded-[24px] p-8 border border-gray-200 shadow-xl text-center">
            
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                <span class="material-symbols-outlined text-[32px]">mark_email_unread</span>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Cek Email Anda</h1>
            <p class="text-[13px] text-gray-500 mb-6 leading-relaxed">
                Kami telah mengirimkan 6 digit kode verifikasi ke <br>
                <b class="text-gray-800"><?= htmlspecialchars($email) ?></b>
            </p>

            <form method="POST" action="/verify-email">
                <!-- 6 Kotak Input OTP -->
                <div class="flex justify-center gap-2 mb-6">
                    <?php for($i=1; $i<=6; $i++): ?>
                        <input type="number" name="otp<?= $i ?>" maxlength="1" required 
                               class="w-12 h-14 text-center text-xl font-extrabold border border-gray-300 rounded-xl focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition-all otp-input bg-gray-50 focus:bg-white text-gray-900 placeholder-gray-300">
                    <?php endfor; ?>
                </div>
                
                <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-600/30 transition-all">
                    Verifikasi Akun
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-gray-100">
                <p class="text-[13px] text-gray-500">
                    Belum menerima kode? 
                    <!-- TOMBOL KIRIM ULANG -->
                    <a href="/verify-email?resend=1" class="font-bold text-blue-600 hover:text-blue-800 hover:underline transition-all">
                        Kirim Ulang Kode
                    </a>
                </p>
                <div class="mt-3">
                    <a href="/register" class="text-[11px] text-gray-400 hover:text-gray-600 underline">Kembali ke halaman daftar</a>
                </div>
            </div>

        </div>
    <?php endif; ?>

    <script>
        const inputs = document.querySelectorAll('.otp-input');
        inputs.forEach((input, index) => {
            input.addEventListener('input', () => { 
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus(); 
                }
            });
            input.addEventListener('keydown', (e) => { 
                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                    inputs[index - 1].focus(); 
                }
            });
        });
    </script>
</body>
</html>