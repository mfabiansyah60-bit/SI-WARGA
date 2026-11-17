<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/Mailer.php'; // PENTING: Load class Mailer

// Cek apakah user beneran baru dari halaman forgot-password
$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) { header('Location: /login'); exit; }

$error = '';
$success_msg = '';

// ============================================
// LOGIKA KIRIM ULANG OTP (FITUR BARU)
// ============================================
if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    try {
        // 1. Generate OTP Baru (6 digit)
        $otp_baru = sprintf("%06d", mt_rand(100000, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // 2. Hapus OTP Lama biar gak bentrok
        $pdo->prepare("DELETE FROM email_otps WHERE email = ? AND type = 'reset'")->execute([$email]);

        // 3. Simpan OTP Baru ke Database
        $stmt = $pdo->prepare("INSERT INTO email_otps (email, otp_code, type, expires_at) VALUES (?, ?, 'reset', ?)");
        $stmt->execute([$email, $otp_baru, $expires_at]);

        // 4. Ambil nama user buat sapaan di email
        $stmtUser = $pdo->prepare("SELECT name FROM users WHERE email = ?");
        $stmtUser->execute([$email]);
        $nama_user = $stmtUser->fetchColumn() ?: 'Warga';

        // 5. Kirim Email via Mailer
        $result = Mailer::sendOTP($email, $nama_user, $otp_baru);

        if ($result['success']) {
            $success_msg = '✅ Kode OTP baru berhasil dikirim ulang ke email Anda.';
        } else {
            $error = '❌ ' . $result['message'];
        }
    } catch (Exception $e) {
        $error = '❌ Gagal generate OTP: ' . $e->getMessage();
    }
}

// ============================================
// LOGIKA SUBMIT FORM PASSWORD BARU
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gabungin 6 kotak OTP jadi 1 string
    $otp_input = '';
    for ($i = 1; $i <= 6; $i++) { $otp_input .= $_POST['otp' . $i] ?? ''; }
    
    $new_pass = $_POST['password'] ?? '';

    if (strlen($otp_input) !== 6) {
        $error = 'Masukkan 6 digit kode OTP!';
    } elseif (strlen($new_pass) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } else {
        // Cek OTP valid atau nggak
        $stmt = $pdo->prepare("SELECT id FROM email_otps WHERE email = ? AND otp_code = ? AND type = 'reset' AND expires_at > NOW()");
        $stmt->execute([$email, $otp_input]);

        if ($stmt->fetch()) {
            // OTP BENAR! Hash password baru
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // Update password di tabel users
            $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $email]);
            
            // Hapus OTP dari database
            $pdo->prepare("DELETE FROM email_otps WHERE email = ? AND type = 'reset'")->execute([$email]);
            
            // Hapus session reset_email
            unset($_SESSION['reset_email']);

            // Redirect ke login dengan pesan sukses
            $_SESSION['flash'] = ['message' => '✅ Password berhasil diubah! Silakan login dengan password baru.', 'type' => 'success'];
            header('Location: /login'); exit;
        } else {
            $error = 'Kode OTP salah atau sudah kadaluarsa. Silakan minta ulang.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F8F9FA]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — SIWARGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0" />
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="w-full max-w-[420px] bg-white rounded-[24px] p-8 border border-gray-200 shadow-xl text-center">
        
        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
            <span class="material-symbols-outlined text-[32px]">key</span>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Password Baru</h1>
        <p class="text-[13px] text-gray-500 mb-6 leading-relaxed">
            Masukkan kode OTP yang dikirim ke <b class="text-gray-800"><?= htmlspecialchars($email) ?></b> dan buat password baru Anda.
        </p>

        <!-- TAMPILKAN PESAN SUKSES / ERROR -->
        <?php if ($success_msg): ?>
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium text-left">
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs font-medium text-left">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/reset-password" class="space-y-6">
            
            <!-- 6 Kotak Input OTP -->
            <div>
                <label class="block text-[13px] font-bold text-gray-700 mb-3 text-left">Kode OTP</label>
                <div class="flex justify-center gap-2">
                    <?php for($i=1; $i<=6; $i++): ?>
                        <input type="number" name="otp<?= $i ?>" maxlength="1" required 
                               class="w-12 h-14 text-center text-xl font-extrabold border border-gray-300 rounded-xl focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition-all otp-input bg-gray-50 focus:bg-white text-gray-900">
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Input Password Baru -->
            <div class="text-left">
                <label class="block text-[13px] font-bold text-gray-700 mb-2">Password Baru</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <span class="material-symbols-outlined text-[20px]">lock</span>
                    </div>
                    <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" 
                           class="w-full pl-11 pr-4 py-3.5 border border-gray-300 rounded-xl text-[14px] font-medium text-gray-900 focus:border-black outline-none transition-all">
                </div>
            </div>
            
            <button type="submit" class="w-full py-3.5 bg-black hover:bg-gray-800 text-white rounded-xl font-bold text-[14px] shadow-lg transition-all">
                Simpan Password Baru
            </button>
        </form>

        <!-- TOMBOL KIRIM ULANG OTP -->
        <div class="mt-6 pt-5 border-t border-gray-100">
            <p class="text-[13px] text-gray-500">
                Belum menerima kode? 
                <a href="/reset-password?resend=1" class="text-blue-600 font-bold hover:underline ml-1">
                    Kirim Ulang OTP
                </a>
            </p>
        </div>

    </div>

    <script>
        // Auto-focus kotak OTP
        const inputs = document.querySelectorAll('.otp-input');
        inputs.forEach((input, index) => {
            input.addEventListener('input', () => { 
                if (input.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus(); 
            });
            input.addEventListener('keydown', (e) => { 
                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) inputs[index - 1].focus(); 
            });
        });
    </script>
</body>
</html>