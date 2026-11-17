<?php
// ============================================================
// DEBUG
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// SESSION — simpan di storage/sessions (lebih rapi & terisolasi)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath)) @mkdir($sessionPath, 0755, true);
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    session_start();
}

// ============================================================
// LOAD HELPERS
// ============================================================
require_once __DIR__ . '/../app/Helpers/functions.php';

// ============================================================
// LOAD DATABASE (biar $pdo global tersedia)
// ============================================================
require_once __DIR__ . '/../config/database.php';

// ============================================================
// AUTO-REFRESH SESSION USER DARI DB
// Biar nama / role / email / rt / rw selalu sinkron dengan DB
// ============================================================
if (isset($_SESSION['user']['id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user']['id']]);
        $freshUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($freshUser) {
            $_SESSION['user']    = $freshUser;
            $_SESSION['user_id'] = $freshUser['id'];
        } else {
            $_SESSION = [];
            session_destroy();
            header('Location: /login');
            exit;
        }
    } catch (Exception $e) {}
}

// ============================================================
// NORMALISASI PATH
// ============================================================
$path = $_SERVER['REQUEST_URI'];
$path = str_replace('/Rpl', '', $path);
$path = str_replace('/public', '', $path);
$path = parse_url($path, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// PUBLIC ROUTES — LANDING PAGE, LOGIN, REGISTER, OTP, DLL
// ============================================================
if ($path === '/') {
    require_once __DIR__ . '/../resources/views/landing.php';
    exit;
}

if ($path === '/login') {
    require_once __DIR__ . '/../resources/views/auth/login.php';
    exit;
}

if ($path === '/register') {
    require_once __DIR__ . '/../resources/views/auth/register.php';
    exit;
}

if ($path === '/otp') {
    header('Location: /verify-email');
    exit;
}

if ($path === '/verify-email') {
    require_once __DIR__ . '/../resources/views/auth/verify_email.php';
    exit;
}

if ($path === '/forgot-password') {
    require_once __DIR__ . '/../resources/views/auth/forgot_password.php';
    exit;
}

if ($path === '/reset-password') {
    require_once __DIR__ . '/../resources/views/auth/reset_password.php';
    exit;
}

// ============================================================
// PROTEKSI LOGIN (Semua rute di bawah sini wajib login)
// ============================================================
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// ============================================================
// LOGOUT
// ============================================================
if ($path === '/logout') {
    log_activity('logout', 'User logout dari sistem');
    $_SESSION = [];
    session_destroy();
    header('Location: /login');
    exit;
}

// ============================================================
// DASHBOARD — redirect sesuai role
// ============================================================
if ($path === '/dashboard') {
    $user = $_SESSION['user'] ?? [];
    $role = $user['role'] ?? 'warga';

    switch ($role) {
        case 'super_admin':
            require_once __DIR__ . '/../resources/views/dashboard/super_admin.php';
            break;
        case 'desa':
            require_once __DIR__ . '/../resources/views/dashboard/desa.php';
            break;
        case 'rw':
            require_once __DIR__ . '/../resources/views/dashboard/rw.php';
            break;
        case 'rt':
            require_once __DIR__ . '/../resources/views/dashboard/rt.php';
            break;
        default:
            require_once __DIR__ . '/../resources/views/dashboard/warga.php';
            break;
    }
    exit;
}

// ============================================================
// MODUL ROUTES
// ============================================================
$parts = explode('/', ltrim($path, '/'));
$modul = $parts[0] ?? '';

$controllerMap = [
    'wilayah'       => 'WilayahController',
    'rt_rw'         => 'RtRwController',
    'pelayanan'     => 'PelayananController',
    'pengaduan'     => 'PengaduanController',
    'surat'         => 'SuratController',
    'ai'            => 'AIController',
    'users'         => 'UserController',
    'warga'         => 'WargaController',
    'laporan'       => 'LaporanController',
    'verifikasi'    => 'VerifikasiController',
    'notifications' => 'NotificationController',
    'pengumuman'    => 'PengumumanController',
    'pengaturan'    => 'PengaturanController',
    'ai-config'     => 'AIConfigController',
    'whatsapp-config' => 'WhatsAppConfigController',
];

// ============================================================
// LOAD CONTROLLER (BLOK CANACCESS SUDAH DIHAPUS)
// ============================================================
if (isset($controllerMap[$modul])) {
    $file = __DIR__ . '/../app/Controllers/' . $controllerMap[$modul] . '.php';
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
    echo "<h1>🔧 Modul <code>" . htmlspecialchars($modul) . "</code> belum siap</h1>";
    echo "<a href='/dashboard'>← Dashboard</a>";
    exit;
}

// ============================================================
// 404
// ============================================================
http_response_code(404);
log_error('404 Not Found', ['path' => $path, 'method' => $method]);
echo "<h1>404 Not Found</h1>";
echo "<p>Path: <code>" . htmlspecialchars($path) . "</code></p>";
echo "<a href='/dashboard'>← Dashboard</a>";