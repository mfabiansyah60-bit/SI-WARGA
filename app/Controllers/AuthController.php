<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/../Models/User.php';

class AuthController
{
    // ============ LOGIN ============
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['flash'] = ['message' => 'Email dan password wajib diisi.', 'type' => 'error'];
            header('Location: /login');
            exit;
        }

        $user = User::findByEmail($email);

        if (!$user) {
            $_SESSION['flash'] = ['message' => 'Email tidak terdaftar.', 'type' => 'error'];
            header('Location: /login');
            exit;
        }

        // Support password_hash (baru) & MD5 (lama)
        $ok = false;
        if (password_verify($password, $user['password'])) {
            $ok = true;
        } elseif (md5($password) === $user['password']) {
            $ok = true;
            // Auto-upgrade MD5 → bcrypt
            global $pdo;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
        }

        if (!$ok) {
            $_SESSION['flash'] = ['message' => 'Password salah.', 'type' => 'error'];
            header('Location: /login');
            exit;
        }

        // Sukses
        unset($user['password']);
        $_SESSION['user']    = $user;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'] ?? 'warga';

        header('Location: /dashboard');
        exit;
    }

    // ============ REGISTER ============
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit;
        }

        $nama     = trim($_POST['nama'] ?? $_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirm  = $_POST['password_confirm']
                 ?? $_POST['konfirmasi_password']
                 ?? $_POST['password_confirmation']
                 ?? '';

        if ($nama === '' || $email === '' || $password === '') {
            $_SESSION['flash'] = ['message' => 'Semua field wajib diisi.', 'type' => 'error'];
            header('Location: /register');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['message' => 'Format email tidak valid.', 'type' => 'error'];
            header('Location: /register');
            exit;
        }
        if (strlen($password) < 6) {
            $_SESSION['flash'] = ['message' => 'Password minimal 6 karakter.', 'type' => 'error'];
            header('Location: /register');
            exit;
        }
        if ($konfirm !== '' && $password !== $konfirm) {
            $_SESSION['flash'] = ['message' => 'Konfirmasi password tidak cocok.', 'type' => 'error'];
            header('Location: /register');
            exit;
        }
        if (User::findByEmail($email)) {
            $_SESSION['flash'] = ['message' => 'Email sudah terdaftar.', 'type' => 'error'];
            header('Location: /register');
            exit;
        }

        $id = User::create([
            'name'     => $nama,
            'email'    => $email,
            'password' => $password,
            'role'     => 'warga',
        ]);

        if (!$id) {
            $_SESSION['flash'] = ['message' => 'Gagal daftar, coba lagi.', 'type' => 'error'];
            header('Location: /register');
            exit;
        }

        $_SESSION['flash'] = ['message' => 'Registrasi berhasil, silakan login.', 'type' => 'success'];
        header('Location: /login');
        exit;
    }

    // ============ LOGOUT ============
    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        header('Location: /login');
        exit;
    }
}