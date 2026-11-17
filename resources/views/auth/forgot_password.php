<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Mailer.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '⚠️ Masukkan alamat email yang valid!';
    } else {
        // Cek apakah email terdaftar di tabel users
        $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Bikin OTP 6 digit
            $otp_code = rand(100000, 999999);
            
            // Hapus OTP reset sebelumnya untuk email ini (biar gak numpuk)
            $pdo->prepare("DELETE FROM email_otps WHERE email = ? AND type = 'reset'")->execute([$email]);
            
            // Simpan OTP baru ke database (berlaku 5 menit)
            $stmt = $pdo->prepare("INSERT INTO email_otps (email, otp_code, type, expires_at) VALUES (?, ?, 'reset', DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
            $stmt->execute([$email, $otp_code]);

            // Kirim Email OTP
            if (Mailer::sendOTP($email, $user['name'], $otp_code)) {
                $_SESSION['reset_email'] = $email; // Simpan email di session buat halaman reset
                header('Location: /reset-password'); exit;
            } else {
                $error = '❌ Gagal mengirim email verifikasi. Coba lagi nanti.';
            }
        } else {
            // Demi keamanan, sebenernya kasih pesan generic aja (jangan bocorin email mana yang terdaftar)
            // Tapi buat aplikasi desa, kasih tau terang-terangan gapapa biar user gak bingung.
            $error = '⚠️ Email tidak terdaftar di sistem kami.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F8F9FA]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — SIWARGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0" />
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="w-full max-w-[420px] bg-white rounded-[24px] p-8 border border-gray-200 shadow-xl">
        
        <!-- Tombol Back -->
        <a href="/login" class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-black transition-all mb-6">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[32px]">lock_reset</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Lupa Password?</h1>
            <p class="text-[13px] text-gray-500 leading-relaxed">Jangan panik. Masukkan email terdaftar Anda, dan kami akan mengirimkan kode OTP untuk reset password.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-[13px] font-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/forgot-password" class="space-y-5">
            <div>
                <label class="block text-[13px] font-bold text-gray-700 mb-2">Alamat Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <span class="material-symbols-outlined text-[20px]">mail</span>
                    </div>
                    <input type="email" name="email" required placeholder="Masukkan email Anda" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full pl-11 pr-4 py-3.5 border border-gray-300 rounded-xl text-[14px] font-medium text-gray-900 focus:border-black outline-none transition-all">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-black hover:bg-gray-800 text-white rounded-xl font-bold text-[14px] shadow-lg transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">send</span>
                Kirim Kode OTP
            </button>
        </form>

        <p class="text-center text-[13px] text-gray-500 mt-8">
            Ingat password Anda? <a href="/login" class="font-bold text-blue-600 hover:underline">Kembali Login</a>
        </p>

    </div>
</body>
</html>