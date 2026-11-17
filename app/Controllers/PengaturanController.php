<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../../app/Helpers/WhatsAppConfig.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // UPDATE PROFIL
    if ($action === 'update_profil') {
        $name = trim($_POST['name'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $wa_opt_in = isset($_POST['wa_opt_in']) ? 1 : 0;

        if (empty($name)) {
            $_SESSION['flash'] = ['message' => '⚠️ Nama tidak boleh kosong!', 'type' => 'error'];
        } else {
            $currentOptIn = isset($user['wa_opt_in']) ? (int)$user['wa_opt_in'] : 1;

            if ($currentOptIn !== $wa_opt_in) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, no_hp = ?, no_telp = ?, wa_opt_in = ?, wa_opt_in_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $no_hp, $no_hp, $wa_opt_in, $user['id']]);
                // Sinkronkan ke helper WhatsAppConfig (otomatis membatalkan antrean pending jika opt-out)
                WhatsAppConfig::setOptIn($user['id'], $wa_opt_in === 1, $no_hp);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, no_hp = ?, no_telp = ? WHERE id = ?");
                $stmt->execute([$name, $no_hp, $no_hp, $user['id']]);
            }
            
            // Refresh session
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['user'] = $stmt->fetch();

            $_SESSION['flash'] = ['message' => '✅ Profil berhasil diperbarui!', 'type' => 'success'];
        }
        header('Location: /pengaturan'); exit;
    }

    // UPDATE PASSWORD
    if ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $db_pass = $stmt->fetchColumn();

        if (!password_verify($current, $db_pass)) {
            $_SESSION['flash'] = ['message' => '❌ Password saat ini salah!', 'type' => 'error'];
        } elseif (strlen($new) < 6) {
            $_SESSION['flash'] = ['message' => '⚠️ Password baru minimal 6 karakter!', 'type' => 'error'];
        } elseif ($new !== $confirm) {
            $_SESSION['flash'] = ['message' => '⚠️ Konfirmasi password tidak cocok!', 'type' => 'error'];
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);
            $_SESSION['flash'] = ['message' => '✅ Password berhasil diubah!', 'type' => 'success'];
        }
        header('Location: /pengaturan'); exit;
    }
}

require_once __DIR__ . '/../../resources/views/pengaturan/index.php';