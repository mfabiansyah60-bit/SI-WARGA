<?php
// app/Controllers/AIController.php

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') session_start();

// ==========================================
// 🔥 FUNGSI PEMBACA FILE .env 🔥
// ==========================================
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) continue;
        list($name, $value) = explode('=', $trimmed, 2);
        $name = trim($name); $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnv(__DIR__ . '/../../.env');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../AI/ScopeFilter.php';
require_once __DIR__ . '/../AI/RoleCapabilities.php';
require_once __DIR__ . '/../AI/DatabaseInspector.php';
require_once __DIR__ . '/../Helpers/AIConfig.php';
require_once __DIR__ . '/../Helpers/AIDataHelper.php';
require_once __DIR__ . '/../Helpers/AIKeyCooldown.php';

if (php_sapi_name() !== 'cli') {
    if (!isLoggedIn()) { header('Location: /login'); exit; }
    $user = auth();
    $validRoles = ['super_admin', 'desa', 'rw', 'rt', 'warga'];
    if (!in_array($user['role'] ?? '', $validRoles)) {
        $_SESSION['flash'] = ['message' => '❌ Anda tidak memiliki akses ke AI Asisten.', 'type' => 'error'];
        header('Location: /dashboard'); exit;
    }
    $action = $_GET['action'] ?? 'index';

// ============================================
// CHAT (TEXT ONLY)
// ============================================
if ($action === 'chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    @set_time_limit(60);
    ob_start(); 
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $sessionId  = trim($_POST['session_id'] ?? '');

    if (empty($pertanyaan)) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['jawaban' => 'Ketik dulu dong pertanyaannya 😊']); exit;
    }

    $isNewSession = false;
    if (empty($sessionId)) {
        $isNewSession = true;
        $sessionId = 'sess_' . $user['id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $cleanQ = preg_replace('/\s+/', ' ', trim($pertanyaan));
        $title  = mb_substr($cleanQ, 0, 50, 'UTF-8');
    } else {
        try {
            $stmtTitle = $pdo->prepare("SELECT title FROM ai_chat WHERE user_id = ? AND session_id = ? AND title IS NOT NULL AND title != '' LIMIT 1");
            $stmtTitle->execute([$user['id'], $sessionId]);
            $existingTitle = $stmtTitle->fetchColumn();
            if ($existingTitle) {
                $title = $existingTitle;
            } else {
                $cleanQ = preg_replace('/\s+/', ' ', trim($pertanyaan));
                $title  = mb_substr($cleanQ, 0, 50, 'UTF-8');
            }
        } catch (Exception $e) {
            $cleanQ = preg_replace('/\s+/', ' ', trim($pertanyaan));
            $title  = mb_substr($cleanQ, 0, 50, 'UTF-8');
        }
    }

    $check = ScopeFilter::check($pertanyaan);
    if (!$check['allowed']) {
        if (($check['type'] ?? '') === 'privacy_nik') $jawaban = ScopeFilter::rejectNikMessage();
        elseif (($check['type'] ?? '') === 'privacy_cred') $jawaban = ScopeFilter::rejectCredentialMessage();
        else $jawaban = ScopeFilter::rejectMessage();
        try { 
            $stmt = $pdo->prepare("INSERT INTO ai_chat (user_id, session_id, title, pertanyaan, jawaban) VALUES (?, ?, ?, ?, ?)"); 
            $stmt->execute([$user['id'], $sessionId, $title, $pertanyaan, '[DITOLAK] ' . $jawaban]); 
        } catch (Exception $e) {}
        log_activity('ai_blocked', 'reason=' . $check['reason'] . ' pesan=' . substr($pertanyaan, 0, 50));
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['jawaban' => $jawaban, 'session_id' => $sessionId, 'title' => $title, 'is_new' => $isNewSession]); exit;
    }

    $jawaban = prosesAI($pertanyaan, $user, $pdo, $sessionId);
    try { 
        $stmt = $pdo->prepare("INSERT INTO ai_chat (user_id, session_id, title, pertanyaan, jawaban) VALUES (?, ?, ?, ?, ?)"); 
        $stmt->execute([$user['id'], $sessionId, $title, $pertanyaan, $jawaban]); 
    } catch (Exception $e) {}
    ob_clean(); header('Content-Type: application/json');
    echo json_encode(['jawaban' => $jawaban, 'session_id' => $sessionId, 'title' => $title, 'is_new' => $isNewSession]); exit;
}

// ============================================
// CHAT WITH IMAGE
// ============================================
if ($action === 'chat_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $sessionId  = trim($_POST['session_id'] ?? '');

    if (empty($pertanyaan)) $pertanyaan = 'Coba jelasin gambar ini dong secara lengkap';

    $isNewSession = false;
    if (empty($sessionId)) {
        $isNewSession = true;
        $sessionId = 'sess_' . $user['id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $cleanQ = preg_replace('/\s+/', ' ', trim($pertanyaan));
        $title  = mb_substr($cleanQ, 0, 50, 'UTF-8');
    } else {
        try {
            $stmtTitle = $pdo->prepare("SELECT title FROM ai_chat WHERE user_id = ? AND session_id = ? AND title IS NOT NULL AND title != '' LIMIT 1");
            $stmtTitle->execute([$user['id'], $sessionId]);
            $existingTitle = $stmtTitle->fetchColumn();
            $title = $existingTitle ?: mb_substr(preg_replace('/\s+/', ' ', trim($pertanyaan)), 0, 50, 'UTF-8');
        } catch (Exception $e) {
            $title = mb_substr(preg_replace('/\s+/', ' ', trim($pertanyaan)), 0, 50, 'UTF-8');
        }
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['jawaban' => '❌ Waduh, gagal upload gambarnya. Coba lagi ya!', 'session_id' => $sessionId, 'title' => $title]); exit;
    }
    $file = $_FILES['image'];
    if ($file['size'] > 4 * 1024 * 1024) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['jawaban' => '❌ Gambarnya kegedean! Max 4MB ya 😅', 'session_id' => $sessionId, 'title' => $title]); exit;
    }
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['jawaban' => '❌ Formatnya gak didukung. Pakai JPG, PNG, atau WEBP ya!', 'session_id' => $sessionId, 'title' => $title]); exit;
    }
    $image_data = file_get_contents($file['tmp_name']);
    $base64_image = base64_encode($image_data);
    $jawaban = analisisGambarGemini($pertanyaan, $base64_image, $mime, $user);
    if ($jawaban === null) $jawaban = "❌ Waduh, aku gak bisa baca gambarnya nih. Coba lagi ya!";
    try { 
        $stmt = $pdo->prepare("INSERT INTO ai_chat (user_id, session_id, title, pertanyaan, jawaban) VALUES (?, ?, ?, ?, ?)"); 
        $stmt->execute([$user['id'], $sessionId, $title, '[🖼️ Gambar] ' . $pertanyaan, $jawaban]); 
    } catch (Exception $e) {}
    ob_clean(); header('Content-Type: application/json');
    echo json_encode(['jawaban' => $jawaban, 'session_id' => $sessionId, 'title' => $title, 'is_new' => $isNewSession]); exit;
}

// ============================================
// SIMPAN SURAT DARI AI
// ============================================
if ($action === 'simpan_surat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    $input = json_decode(file_get_contents('php://input'), true);
    $jenis_surat = trim($input['jenis_surat'] ?? '');
    $data = $input['data'] ?? [];
    if (empty($jenis_surat) || empty($data)) {
        ob_clean(); header('Content-Type: application/json'); echo json_encode(['error' => 'Data tidak lengkap']); exit;
    }
    try {
        $nomor = 'AI/' . date('Ymd') . '/' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO surat_pengajuan (user_id, jenis_surat, data_json, nomor_surat, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$user['id'], $jenis_surat, json_encode($data), $nomor]);
        $id = $pdo->lastInsertId();
        log_activity('ai_buat_surat', "id=$id jenis=$jenis_surat");
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id, 'nomor' => $nomor, 'download' => '/surat?action=cetak&id=' . $id]);
    } catch (Exception $e) {
        ob_clean(); header('Content-Type: application/json');
        log_error('AI simpan surat error', ['error' => $e->getMessage()]);
        echo json_encode(['error' => 'Gagal simpan surat: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// API: DAFTAR SESI RIWAYAT (JSON)
// ============================================
if ($action === 'list_sessions') {
    ob_clean(); header('Content-Type: application/json');
    $sessions = AIDataHelper::getDaftarSesi($pdo, $user['id'], 60);
    echo json_encode(['success' => true, 'sessions' => $sessions]);
    exit;
}

// ============================================
// API: DETAIL PESAN DALAM SESI (JSON)
// ============================================
if ($action === 'get_session') {
    ob_clean(); header('Content-Type: application/json');
    $sessionId = trim($_GET['session_id'] ?? ($_POST['session_id'] ?? ''));
    if (empty($sessionId)) {
        echo json_encode(['success' => false, 'error' => 'Session ID tidak valid']); exit;
    }
    $messages = AIDataHelper::getPesanSesi($pdo, $user['id'], $sessionId);
    echo json_encode(['success' => true, 'session_id' => $sessionId, 'messages' => $messages]);
    exit;
}

// ============================================
// HAPUS SATU SESI PERCAKAPAN
// ============================================
if ($action === 'hapus_sesi') {
    $sessionId = trim($_POST['session_id'] ?? ($_GET['session_id'] ?? ''));
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
              || isset($_POST['ajax']);
    $success = false;
    if (!empty($sessionId)) {
        $success = AIDataHelper::hapusSesi($pdo, $user['id'], $sessionId);
    }
    if ($isAjax) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['success' => $success]); exit;
    }
    $_SESSION['flash'] = [
        'message' => $success ? '✅ Sesi percakapan berhasil dihapus!' : '❌ Gagal menghapus sesi percakapan.',
        'type' => $success ? 'success' : 'error'
    ];
    header('Location: ' . (!empty($_GET['redirect_riwayat']) ? '/ai?action=riwayat' : '/ai')); exit;
}

// ============================================
// HAPUS SEMUA RIWAYAT CHAT
// ============================================
if ($action === 'hapus_riwayat') {
    $success = AIDataHelper::hapusSemuaRiwayat($pdo, $user['id']);
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
              || isset($_POST['ajax']);
    if ($isAjax) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['success' => true]); exit;
    }
    $_SESSION['flash'] = ['message' => '✅ Riwayat chat udah dihapus!', 'type' => 'success'];
    header('Location: /ai'); exit;
}

// ============================================
// HALAMAN RIWAYAT
// ============================================
if ($action === 'riwayat') {
    $daftarSesi = AIDataHelper::getDaftarSesi($pdo, $user['id'], 60);
    $stmt = $pdo->prepare("SELECT * FROM ai_chat WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user['id']]); $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    require_once __DIR__ . '/../../resources/views/ai/riwayat.php'; exit;
}

// ============================================
// HALAMAN UTAMA CHAT
// ============================================
if ($action === 'index') {
    $daftarSesi = AIDataHelper::getDaftarSesi($pdo, $user['id'], 50);
    $activeSessionId = trim($_GET['session_id'] ?? '');
    $initialMessages = [];
    if (!empty($activeSessionId)) {
        $initialMessages = AIDataHelper::getPesanSesi($pdo, $user['id'], $activeSessionId);
    }
    require_once __DIR__ . '/../../resources/views/ai/chat.php';
    exit;
}
} // end if (php_sapi_name() !== 'cli')

// ============================================================
// HELPER: AMBIL DATA RT/RW USER
// ============================================================
function getWilayahUser($user, $pdo) {
    $data = ['rt_nomor'=>'-','rw_nomor'=>'-','rt_ketua'=>'-','rw_ketua'=>'-','rt_telp'=>'-','rt_email'=>'-','rw_telp'=>'-','rw_email'=>'-','desa_nama'=>'Desa Sukamaju','desa_telp'=>'081234567890','desa_email'=>'desa.sukamaju@mail.go.id'];
    $rtNomor = ''; $rwNomor = '';
    try {
        $stmtUser = $pdo->prepare("SELECT rt, rw FROM users WHERE id = ?"); $stmtUser->execute([$user['id']]); $freshUser = $stmtUser->fetch();
        if ($freshUser) { $rtNomor = trim($freshUser['rt'] ?? ''); $rwNomor = trim($freshUser['rw'] ?? ''); }
    } catch (Exception $e) { $rtNomor = trim($user['rt'] ?? ''); $rwNomor = trim($user['rw'] ?? ''); }
    if ($rtNomor !== '') $data['rt_nomor'] = str_pad($rtNomor, 2, '0', STR_PAD_LEFT);
    if ($rwNomor !== '') $data['rw_nomor'] = str_pad($rwNomor, 2, '0', STR_PAD_LEFT);
    try {
        if ($data['rt_nomor'] !== '-') {
            $rtInfo = null;
            if ($data['rw_nomor'] !== '-') {
                $stmt = $pdo->prepare("SELECT rt.ketua, u.no_telp, u.email, u.no_hp FROM rt JOIN rw ON rt.rw_id = rw.id LEFT JOIN users u ON u.rt = rt.nomor AND u.rw = rw.nomor AND u.role = 'rt' WHERE LPAD(TRIM(rt.nomor), 2, '0') = LPAD(TRIM(?), 2, '0') AND LPAD(TRIM(rw.nomor), 2, '0') = LPAD(TRIM(?), 2, '0') LIMIT 1");
                $stmt->execute([$data['rt_nomor'], $data['rw_nomor']]);
                $rtInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$rtInfo) {
                $stmt = $pdo->prepare("SELECT rt.ketua, u.no_telp, u.email, u.no_hp FROM rt LEFT JOIN users u ON u.rt = rt.nomor AND u.role = 'rt' WHERE LPAD(TRIM(rt.nomor), 2, '0') = LPAD(TRIM(?), 2, '0') LIMIT 1");
                $stmt->execute([$data['rt_nomor']]);
                $rtInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if ($rtInfo) {
                $data['rt_ketua'] = !empty($rtInfo['ketua']) ? $rtInfo['ketua'] : '-';
                $data['rt_telp'] = !empty($rtInfo['no_telp']) ? $rtInfo['no_telp'] : (!empty($rtInfo['no_hp']) ? $rtInfo['no_hp'] : '-');
                $data['rt_email'] = !empty($rtInfo['email']) ? $rtInfo['email'] : '-';
            }
        }
        if ($data['rw_nomor'] !== '-') {
            $stmt = $pdo->prepare("SELECT rw.ketua, u.no_telp, u.email, u.no_hp FROM rw LEFT JOIN users u ON u.rw = rw.nomor AND u.role = 'rw' WHERE LPAD(TRIM(rw.nomor), 2, '0') = LPAD(TRIM(?), 2, '0') LIMIT 1");
            $stmt->execute([$data['rw_nomor']]); $rwInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rwInfo) { $data['rw_ketua'] = !empty($rwInfo['ketua']) ? $rwInfo['ketua'] : '-'; $data['rw_telp'] = !empty($rwInfo['no_telp']) ? $rwInfo['no_telp'] : (!empty($rwInfo['no_hp']) ? $rwInfo['no_hp'] : '-'); $data['rw_email'] = !empty($rwInfo['email']) ? $rwInfo['email'] : '-'; }
        }
        $desa = $pdo->query("SELECT * FROM desa LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($desa) { if (!empty($desa['nama'])) $data['desa_nama'] = $desa['nama']; if (!empty($desa['telepon'])) $data['desa_telp'] = $desa['telepon']; if (!empty($desa['email'])) $data['desa_email'] = $desa['email']; }
    } catch (Exception $e) { log_error('getWilayahUser error', ['error' => $e->getMessage()]); }
    return $data;
}

// ============================================================
// HELPER: STATISTIK DESA
// ============================================================
function getStatistikDesa($pdo, $user = null) {
    $stat = ['total_warga'=>0,'total_users'=>0,'total_rw'=>0,'total_rt'=>0,'total_pengaduan'=>0,'pengaduan_pending'=>0,'pengaduan_proses'=>0,'pengaduan_selesai'=>0,'total_surat'=>0,'total_surat_ai'=>0,'warga_rt_saya'=>0,'warga_rw_saya'=>0,'warga_laki'=>0,'warga_perempuan'=>0];
    try {
        $stat['total_warga'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga'")->fetchColumn();
        $stat['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stat['total_rw'] = (int)$pdo->query("SELECT COUNT(DISTINCT nomor) FROM rw")->fetchColumn();
        $stat['total_rt'] = (int)$pdo->query("SELECT COUNT(DISTINCT nomor) FROM rt")->fetchColumn();
        $stat['total_pengaduan'] = (int)$pdo->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn();
        $stat['pengaduan_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'pending'")->fetchColumn();
        $stat['pengaduan_proses'] = (int)$pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'proses'")->fetchColumn();
        $stat['pengaduan_selesai'] = (int)$pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'selesai'")->fetchColumn();
        try { 
            $stat['total_surat'] = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM surat_pengajuan) + (SELECT COUNT(*) FROM surat_terbit)")->fetchColumn(); 
        } catch (Exception $e) {}
        try { $stat['total_surat_ai'] = (int)$pdo->query("SELECT COUNT(*) FROM surat_pengajuan")->fetchColumn(); } catch (Exception $e) {}
        try { $stat['warga_laki'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga' AND jenis_kelamin = 'L'")->fetchColumn(); } catch (Exception $e) {}
        try { $stat['warga_perempuan'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga' AND jenis_kelamin = 'P'")->fetchColumn(); } catch (Exception $e) {}
        if ($user) {
            $rtUser = trim($user['rt'] ?? ''); $rwUser = trim($user['rw'] ?? '');
            if ($rtUser !== '' && $rwUser !== '') { $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt), 2, '0') = LPAD(TRIM(?), 2, '0') AND LPAD(TRIM(rw), 2, '0') = LPAD(TRIM(?), 2, '0')"); $stmt->execute([$rtUser, $rwUser]); $stat['warga_rt_saya'] = (int)$stmt->fetchColumn(); }
            if ($rwUser !== '') { $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw), 2, '0') = LPAD(TRIM(?), 2, '0')"); $stmt->execute([$rwUser]); $stat['warga_rw_saya'] = (int)$stmt->fetchColumn(); }
        }
    } catch (Exception $e) { log_error('getStatistikDesa error', ['error' => $e->getMessage()]); }
    return $stat;
}

// ============================================================
// HELPER: DAFTAR WARGA & SURAT
// ============================================================
function getDaftarWarga($pdo, $filter = []) {
    try {
        $sql = "SELECT id, name, email, rt, rw, jenis_kelamin, created_at FROM users WHERE role = 'warga'"; $params = [];
        if (!empty($filter['rt'])) { $sql .= " AND LPAD(TRIM(rt), 2, '0') = LPAD(TRIM(?), 2, '0')"; $params[] = $filter['rt']; }
        if (!empty($filter['rw'])) { $sql .= " AND LPAD(TRIM(rw), 2, '0') = LPAD(TRIM(?), 2, '0')"; $params[] = $filter['rw']; }
        if (!empty($filter['jk'])) { $sql .= " AND jenis_kelamin = ?"; $params[] = $filter['jk']; }
        $sql .= (!empty($filter['rt']) || !empty($filter['rw'])) ? " ORDER BY rt ASC, name ASC" : " ORDER BY rw ASC, rt ASC, name ASC";
        if (!empty($filter['limit'])) { $sql .= " LIMIT " . (int)$filter['limit']; }
        $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { log_error('getDaftarWarga error', ['error' => $e->getMessage()]); return []; }
}

function parseSuratFields($text) {
    $fields = [];
    $hasLabels = preg_match_all('/\b(nama|nik|ttl|jk|pekerjaan|alamat|keperluan|jenis\s+kelamin|tempat)\b\s*[:\-]/i', $text) >= 3;
    if ($hasLabels) {
        $patterns = ['nama'=>'/(?:nama\s+lengkap|nama)\s*[:\-]\s*([^\n•,]+?)(?=•|\bnik\b|\btempat\b|\bpekerjaan\b|\balamat\b|\bjenis\b|\bkeperluan\b|$)/i', 'nik'=>'/\bnik\s*[:\-]\s*([0-9]{4,20})/i', 'ttl'=>'/(?:tempat\/tanggal\s+lahir|tempat\s+lahir|tanggal\s+lahir|ttl)\s*[:\-]\s*([^\n•]+?)(?=•|\bjenis\s+kelamin\b|\bjk\b|\bpekerjaan\b|\balamat\b|\bkeperluan\b|$)/i', 'jk'=>'/(?:jenis\s+kelamin|jk)(?:\s*\([^)]*\))?\s*[:\-]\s*(laki[\s\-]?laki|perempuan|[lLpP])\b/i', 'pekerjaan'=>'/pekerjaan\s*[:\-]\s*([^\n•,]+?)(?=•|\balamat\b|\bkeperluan\b|$)/i', 'alamat'=>'/alamat\s*[:\-]\s*([^\n•]+?)(?=•|\bkeperluan\b|$)/i', 'keperluan'=>'/keperluan(?:\s+surat)?\s*[:\-]\s*([^\n•]+)/i'];
        foreach ($patterns as $key => $pat) { if (preg_match($pat, $text, $m)) { $fields[$key] = trim($m[1]); } }
    } else {
        if (substr_count($text, ',') < 3) { return []; }
        $clean = preg_replace('/^(?:buat|bikin|mau)\s+(?:surat\s+)?\w+\s*[,\-:]?\s*/i', '', $text); $clean = preg_replace('/^(?:domisili|usaha|sktm|skck|pengantar\s+ktp|ktp)\s*[,\-:]?\s*/i', '', $clean);
        $parts = preg_split('/\s*[,\n]\s*/', trim($clean)); $parts = array_values(array_filter($parts, fn($p) => trim($p) !== ''));
        $keys = ['nama', 'nik', 'ttl', 'jk', 'pekerjaan', 'alamat', 'keperluan']; foreach ($parts as $i => $val) { if (isset($keys[$i])) { $fields[$keys[$i]] = trim($val); } }
    }
    if (!empty($fields['jk'])) { $jk = strtolower(trim($fields['jk'])); if ($jk === 'l' || strpos($jk, 'laki') !== false) $fields['jk'] = 'Laki-laki'; elseif ($jk === 'p' || strpos($jk, 'perempuan') !== false) $fields['jk'] = 'Perempuan'; else $fields['jk'] = ''; }
    if (!empty($fields['keperluan'])) $fields['keperluan'] = trim(preg_replace('/^(?:surat|keperluan)\s*[:\-]?\s*/i', '', $fields['keperluan']));
    if (!empty($fields['nik']) && !preg_match('/^\d{4,20}$/', $fields['nik'])) $fields['nik'] = '';
    return $fields;
}

// ============================================================
// FAST-TRACK: SAPAAN RAMAH & MULTI-TURN BENCHMARKS (SKENARIO A, B, C)
// ============================================================
function prosesAIFastTrack($pertanyaan, $user, $pdo, $sessionId = null) {
    $q = AIDataHelper::normalizeText($pertanyaan);
    $nama_depan = explode(' ', $user['name'])[0];
    $role = $user['role'] ?? 'warga';

    // 0. FAST-TRACK: SAPAAN RAMAH & TERIMA KASIH (RESPONS INSTAN TANPA LOAD DATABASE)
    $greetingClean = trim(strtolower($pertanyaan));
    $greetingCollapsed = preg_replace('/([a-zA-Z])\1{2,}/', '$1', $greetingClean);
    $greetingPattern = '/^(?:assalamu[\'\s]?alaikum(?:\s+wr\s+wb)?|halo+|helo+|hello+|hai+|hei+|hey+|hi+|selamat\s+(?:pagi|siang|sore|malam)|pagi|siang|sore|malam|permisi|sampurasun|kulonuwun)(?:\s+(?:ai|siwarga|bot|admin|min|kak|mas|mbak|pak|bu|semua))?\s*[\?!.]*$/i';

    if (preg_match($greetingPattern, $greetingClean) || preg_match($greetingPattern, $greetingCollapsed)) {
        $jam = (int)date('H');
        if (preg_match('/\bpagi\b/i', $greetingClean)) $sapaan = "Selamat pagi";
        elseif (preg_match('/\bsiang\b/i', $greetingClean)) $sapaan = "Selamat siang";
        elseif (preg_match('/\bsore\b/i', $greetingClean)) $sapaan = "Selamat sore";
        elseif (preg_match('/\bmalam\b/i', $greetingClean)) $sapaan = "Selamat malam";
        elseif (preg_match('/assalam/i', $greetingClean)) $sapaan = "Wa'alaikumussalam";
        else {
            $sapaan = ($jam < 11) ? "Selamat pagi" : (($jam < 15) ? "Selamat siang" : (($jam < 19) ? "Selamat sore" : "Selamat malam"));
        }
        return "$sapaan, **$nama_depan**! 👋\n\nAku SIWARGA AI siap membantu kebutuhan informasi dan administrasi desa kamu. Ada yang bisa aku bantu hari ini? 😊";
    }

    if (preg_match('/^(?:terima\s+kasih|makasih|thanks|thank\s+you|tq|thx|syukron|matur\s+nuwun)(?:\s+(?:ai|siwarga|bot|admin|min|kak|mas|mbak|pak|bu))?\s*[\?!.]*$/i', $greetingClean) || preg_match('/^(?:terima\s+kasih|makasih|thanks|thank\s+you|tq|thx|syukron|matur\s+nuwun)(?:\s+(?:ai|siwarga|bot|admin|min|kak|mas|mbak|pak|bu))?\s*[\?!.]*$/i', $greetingCollapsed)) {
        return "Sama-sama, senang bisa membantu! Jika ada hal lain seputar administrasi desa yang ingin ditanyakan, jangan ragu untuk bertanya lagi ya. 😊";
    }

    $wilayah = AIDataHelper::getWilayahUser($pdo, $user);
    $ctx = AIDataHelper::resolveConversationContext($pdo, $user['id'], $sessionId, $pertanyaan);
    $lastQ = $ctx['last_q'] ?? '';
    $lastAns = $ctx['last_ans'] ?? '';

    // ============================================================
    // FAST-TRACK: PERTANYAAN KHUSUS / SERING DIAJUKAN WARGA (FAQ & DATA MANDIRI)
    // ============================================================

    // 1. SIAPA KETUA RW SAYA?
    if (
        preg_match('/\b(ketua\s+rw\s*(saya|aku|kami|tempat\s+saya|domisili\s+saya|wilayah\s+saya)?|siapa\s+(?:nama\s+)?ketua\s+rw(?:\s+(?:saya|aku))?|nama\s+ketua\s+rw(?:\s+(?:saya|aku))?|rw\s+saya\s+ketuanya\s+siapa)\b/i', $q) &&
        !preg_match('/\brw\s*0?([1-9]\d*)\b/i', $q)
    ) {
        $rwNomor = $wilayah['rw_nomor'];
        $rwKetua = $wilayah['rw_ketua'];
        if ($rwNomor !== '-' && $rwKetua !== '-') {
            $kontak = ($wilayah['rw_telp'] !== '-') ? $wilayah['rw_telp'] : '-';
            $email  = ($wilayah['rw_email'] !== '-') ? $wilayah['rw_email'] : '-';
            return "Ketua **RW {$rwNomor}** di wilayah domisili kamu adalah **{$rwKetua}**.\n\n" .
                   "📋 **Informasi Wilayah RW {$rwNomor}**:\n" .
                   "• **Desa**: {$wilayah['desa_nama']}\n" .
                   "• **Ketua RW**: **{$rwKetua}**\n" .
                   "• **Kontak / No. HP**: {$kontak}\n" .
                   "• **Email**: {$email}\n\n" .
                   "Jika kamu membutuhkan informasi koordinasi lingkungan tingkat RW, kamu bisa menghubungi beliau ya! 😊";
        } elseif (in_array($role, ['super_admin', 'desa'])) {
            return "Sebagai **Administrator Desa**, Anda menaungi seluruh wilayah RW di Desa {$wilayah['desa_nama']}. Untuk melihat rincian ketua RW tertentu, Anda dapat bertanya: *\"Siapa ketua RW 01?\"* atau *\"Sebutkan seluruh nama ketua RW\"*. 😊";
        } else {
            return "Data RW kamu belum terdaftar di profil akun. Silakan perbarui data RT/RW kamu melalui menu **Pengaturan** ya! 😊";
        }
    }

    // 2. SIAPA KETUA RT SAYA?
    if (
        preg_match('/\b(ketua\s+rt\s*(saya|aku|kami|tempat\s+saya|domisili\s+saya|wilayah\s+saya)?|siapa\s+(?:nama\s+)?ketua\s+rt(?:\s+(?:saya|aku))?|nama\s+ketua\s+rt(?:\s+(?:saya|aku))?|rt\s+saya\s+ketuanya\s+siapa)\b/i', $q) &&
        !preg_match('/\brt\s*0?([1-9]\d*)\b/i', $q)
    ) {
        $rtNomor = $wilayah['rt_nomor'];
        $rwNomor = $wilayah['rw_nomor'];
        $rtKetua = $wilayah['rt_ketua'];
        if ($rtNomor !== '-' && $rtKetua !== '-') {
            $kontak = ($wilayah['rt_telp'] !== '-') ? $wilayah['rt_telp'] : '-';
            $email  = ($wilayah['rt_email'] !== '-') ? $wilayah['rt_email'] : '-';
            $rwLabel = ($rwNomor !== '-') ? " / RW {$rwNomor}" : "";
            return "Ketua **RT {$rtNomor}{$rwLabel}** di lingkungan tempat tinggal kamu adalah **{$rtKetua}**.\n\n" .
                   "📋 **Informasi Lingkungan RT {$rtNomor}**:\n" .
                   "• **Desa**: {$wilayah['desa_nama']}\n" .
                   "• **Ketua RT**: **{$rtKetua}**\n" .
                   "• **Kontak / No. HP**: {$kontak}\n" .
                   "• **Email**: {$email}\n\n" .
                   "Jika kamu memerlukan surat pengantar RT atau ada keperluan warga di lingkungan RT, kamu bisa menghubungi beliau ya! 😊";
        } elseif (in_array($role, ['super_admin', 'desa', 'rw'])) {
            return "Sebagai **Perangkat Desa**, Anda menaungi beberapa RT di wilayah Anda. Untuk melihat ketua RT tertentu, silakan tanyakan spesifik, misalnya: *\"Siapa ketua RT 01?\"* atau *\"Daftar ketua RT di RW 01\"*. 😊";
        } else {
            return "Data RT kamu belum terdaftar di profil akun. Silakan perbarui data RT/RW kamu melalui menu **Pengaturan** ya! 😊";
        }
    }

    // 3. PANDUAN CARA MENGAJUKAN SURAT
    if (
        preg_match('/\b(cara|langkah|panduan|bagaimana|gimana|prosedur|alur)\b.*\b(mengajukan|buat|bikin|ajukan|minta|urus|dapat|permohonan)\s+(surat|persuratan)\b/i', $q) ||
        preg_match('/\b(cara\s+(?:mengajukan|buat|bikin|ajukan)\s+surat|pengajuan\s+surat\s+bagaimana)\b/i', $q)
    ) {
        return "📌 **Panduan Cara Mengajukan Surat di SIWARGA**:\n\n" .
               "1. Buka menu **Pelayanan** di sidebar (atau menu **Buat Surat**).\n" .
               "2. Pilih jenis layanan surat yang kamu perlukan (misal: Surat Domisili, SKU, SKTM, Pengantar KTP/SKCK, dll).\n" .
               "3. Lengkapi formulir pengajuan dengan data yang benar serta unggah berkas persyaratan yang diminta.\n" .
               "4. Klik tombol **Kirim Permohonan**.\n" .
               "5. Suratmu akan langsung ditinjau oleh Ketua RT dan Petugas Desa untuk diverifikasi dan disetujui.\n\n" .
               "💡 *Setelah disetujui, file surat resmi (PDF) dapat langsung diunduh atau otomatis dikirimkan ke email kamu. Seluruh pengurusan surat di SIWARGA bersifat **GRATIS (Rp 0)**.* 😊";
    }

    // 4. PERSYARATAN SURAT TERTENTU (DOMISILI, SKU, SKTM, SKCK, DLL)
    if (
        preg_match('/\b(syarat|persyaratan|berkas|dokumen|kelengkapan)\b.*\b(surat|keterangan|pengantar)?\s*(domisili|usaha|sku|sktm|tidak\s+mampu|skck|ktp|kk|kelahiran|kematian)\b/i', $q) ||
        preg_match('/\b(surat\s+(?:keterangan\s+)?(domisili|usaha|sku|sktm|tidak\s+mampu|skck|pengantar\s+ktp))\b.*\b(syarat|persyaratan|dokumen)\b/i', $q)
    ) {
        $kw = 'domisili';
        if (preg_match('/\b(usaha|sku)\b/i', $q)) $kw = 'usaha';
        elseif (preg_match('/\b(sktm|tidak\s+mampu)\b/i', $q)) $kw = 'tidak mampu';
        elseif (preg_match('/\b(skck)\b/i', $q)) $kw = 'skck';
        elseif (preg_match('/\b(ktp)\b/i', $q)) $kw = 'ktp';
        elseif (preg_match('/\b(kk|kartu\s+keluarga)\b/i', $q)) $kw = 'kartu keluarga';
        elseif (preg_match('/\b(kelahiran)\b/i', $q)) $kw = 'kelahiran';
        elseif (preg_match('/\b(kematian)\b/i', $q)) $kw = 'kematian';

        $layanan = AIDataHelper::getPelayananDesa($pdo, $kw);
        if ($layanan) {
            $syaratText = !empty($layanan['syarat']) ? $layanan['syarat'] : "• Fotokopi KTP Pemohon\n• Fotokopi Kartu Keluarga (KK)\n• Surat Pengantar RT/RW";
            $deskripsi = !empty($layanan['deskripsi']) ? "_{$layanan['deskripsi']}_\n\n" : "";
            return "📄 **Persyaratan Layanan: {$layanan['nama']}**\n\n" .
                   $deskripsi .
                   "**Dokumen & Berkas Persyaratan:**\n" .
                   $syaratText . "\n\n" .
                   "💰 **Biaya / Tarif**: **GRATIS (Rp 0)**\n" .
                   "⏱️ **Proses**: Online via menu **Pelayanan** di sidebar.\n\n" .
                   "Kamu bisa langsung mengajukannya secara mandiri melalui menu **Pelayanan** ya! 😊";
        }
    }

    // 5. STATUS SURAT SAYA (WARGA)
    if (
        preg_match('/\b(surat|pengajuan)\b/i', $q) &&
        preg_match('/\b(status|bagaimana|gimana|cek|pantau|posisi)\b/i', $q) &&
        preg_match('/\b(saya|aku|ku|gw|gue|sy|suratku|milik\s+saya)\b/i', $q) &&
        !preg_match('/\b(pengaduan|aduan|laporan|keluhan)\b/i', $q)
    ) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if (empty($surat['surat_saya'])) {
            return "Halo Pak/Bu **{$nama_depan}**! Saat ini kamu belum memiliki pengajuan surat di sistem SIWARGA. Kamu bisa membuat permohonan surat baru di menu **Pelayanan** ya! 😊";
        }
        $out = "📄 **Status Pengajuan Surat Kamu**:\n\n";
        foreach ($surat['surat_saya'] as $i => $s) {
            $st = strtolower(trim($s['status'] ?? 'pending'));
            $badgeSurat = ($st === 'pending') ? '⏳ PENDING (Menunggu Persetujuan)' : (($st === 'disetujui') ? '✅ DISETUJUI' : '❌ DITOLAK');
            $statusEmail = !empty($s['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($s['email_sent_at'])) . ')' : '📭 Belum dikirim';
            $tgl = date('d M Y', strtotime($s['created_at']));
            $out .= ($i + 1) . ". **{$s['judul']}**\n" .
                   "   • Nomor Surat: `{$s['nomor_surat']}`\n" .
                   "   • Status Surat: $badgeSurat\n" .
                   "   • Status Email: $statusEmail\n" .
                   "   • Tanggal Pengajuan: $tgl\n\n";
        }
        $out .= "💡 *Catatan: File surat PDF resmi dapat diunduh atau dikirim ke email kamu setelah disetujui petugas desa. Pantau selengkapnya di menu **Pelayanan** ya!*";
        return $out;
    }

    // 6. STATUS PENGADUAN SAYA (WARGA)
    if (
        preg_match('/\b(pengaduan|aduan|laporan|keluhan|komplain)\b/i', $q) &&
        preg_match('/\b(status|bagaimana|gimana|cek|pantau|posisi|riwayat)\b/i', $q) &&
        preg_match('/\b(saya|aku|ku|gw|gue|sy|aduanku|pengaduanku|milik\s+saya)\b/i', $q)
    ) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if (empty($pengaduan['pengaduan_saya'])) {
            return "Halo Pak/Bu **{$nama_depan}**! Saat ini kamu belum memiliki riwayat pengaduan yang diajukan di sistem SIWARGA. Jika ada kendala lingkungan atau fasilitas umum, silakan sampaikan melalui menu **Pengaduan** ya! 😊";
        }
        $out = "⚠️ **Status Pengaduan Kamu**:\n\n";
        foreach ($pengaduan['pengaduan_saya'] as $i => $ps) {
            $stBadge = ($ps['status'] === 'selesai') ? '✅ Selesai' : (($ps['status'] === 'proses') ? '🔄 Sedang Diproses' : (($ps['status'] === 'ditolak') ? '❌ Ditolak' : '⏳ Pending (Menunggu Tindak Lanjut)'));
            $out .= ($i + 1) . ". **{$ps['judul']}**\n" .
                   "   • Kategori: {$ps['kategori']}\n" .
                   "   • Status: $stBadge\n" .
                   "   • Tanggal Lapor: " . date('d M Y', strtotime($ps['created_at'])) . "\n\n";
        }
        $out .= "💡 *Petugas RT/RW dan Admin Desa akan memperbarui status penanganan laporanmu secara berkala. Pantau selengkapnya di menu **Pengaduan** ya!*";
        return $out;
    }

    // 7. PANDUAN CARA MEMBUAT PENGADUAN
    if (
        (preg_match('/\b(cara|langkah|panduan|bagaimana|gimana|prosedur)\b.*\b(buat|bikin|lapor|mengadu|mengajukan|kirim)\b.*\b(pengaduan|keluhan|aduan|komplain)\b/i', $q) ||
        preg_match('/\b(cara\s+(?:lapor|mengadu|bikin\s+pengaduan|buat\s+pengaduan|mengajukan\s+pengaduan))\b/i', $q)) &&
        !preg_match('/\b(status|pantau|posisi|cek|riwayat)\b/i', $q)
    ) {
        return "📌 **Panduan Menyampaikan Pengaduan di SIWARGA**:\n\n" .
               "1. Buka menu **Pengaduan** di sidebar.\n" .
               "2. Klik tombol **+ Buat Pengaduan**.\n" .
               "3. Isi formulir laporan:\n" .
               "   • **Judul Laporan**: Jelaskan masalah secara singkat.\n" .
               "   • **Kategori**: Pilih kategori (misal: Kebersihan, Keamanan, Fasilitas Umum, dll).\n" .
               "   • **Lokasi Kejadian**: Tulis alamat atau patokan lokasi.\n" .
               "   • **Foto Bukti**: Lampirkan foto kondisi jika ada.\n" .
               "   • **Opsi Anonim**: Centang jika ingin nama kamu dirahasiakan.\n" .
               "4. Klik tombol **Kirim Pengaduan**.\n\n" .
               "Laporanmu akan langsung diterima oleh Ketua RT, Ketua RW, dan Admin Desa untuk segera ditindaklanjuti! 🤝";
    }

    // 8. PANDUAN CARA MEMPERBARUI PROFIL
    if (
        preg_match('/\b(cara|langkah|panduan|bagaimana|gimana)\b.*\b(memperbarui|perbarui|update|ubah|ganti|edit)\b.*\b(profil|profile|data\s+diri|akun|password|sandi)\b/i', $q) ||
        preg_match('/\b(cara\s+(?:update|ubah|ganti|edit|perbarui)\s+(?:profil|profile|akun|data))\b/i', $q)
    ) {
        return "📌 **Panduan Memperbarui Profil Akun di SIWARGA**:\n\n" .
               "1. Buka menu **Pengaturan** di sidebar (atau klik nama/foto profil kamu di pojok kanan atas).\n" .
               "2. Pada halaman pengaturan profil, kamu dapat memperbarui:\n" .
               "   • **Nama Lengkap & Kontak**: Nomor WhatsApp / Telepon dan Email.\n" .
               "   • **Data Domisili**: Alamat tempat tinggal, Nomor RT, dan Nomor RW.\n" .
               "   • **Foto Profil**: Unggah foto profil terbaru.\n" .
               "   • **Keamanan**: Ubah kata sandi (password) akun kamu.\n" .
               "3. Setelah data selesai diubah, klik tombol **Simpan Perubahan**.\n\n" .
               "💡 *Pastikan data RT/RW dan nomor WhatsApp kamu selalu valid agar notifikasi persuratan dan pengaduan dapat diterima secara lancar.* 😊";
    }

    // 9. JUMLAH WARGA DI WILAYAH SAYA
    if (
        preg_match('/\b(ada\s+berapa|berapa|jumlah|total)\b.*\b(warga|penduduk|jiwa|orang)\b.*\b(wilayah\s+saya|daerah\s+saya|lingkungan\s+saya|tempat\s+saya|rt\s+saya|rw\s+saya|domisili\s+saya)\b/i', $q) ||
        preg_match('/\b(warga|penduduk)\b.*\b(wilayah\s+saya|rt\s+saya|rw\s+saya)\b.*\b(ada\s+berapa|berapa|jumlah|total)\b/i', $q)
    ) {
        $rtNomor = $wilayah['rt_nomor'];
        $rwNomor = $wilayah['rw_nomor'];
        if ($rtNomor !== '-' || $rwNomor !== '-') {
            $countRt = 0;
            $countRw = 0;
            if ($rtNomor !== '-' && $rwNomor !== '-') {
                $stmtRt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'warga' AND LPAD(TRIM(rt),2,'0') = ? AND LPAD(TRIM(rw),2,'0') = ?");
                $stmtRt->execute([$rtNomor, $rwNomor]);
                $countRt = (int)$stmtRt->fetchColumn();
            }
            if ($rwNomor !== '-') {
                $stmtRw = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'warga' AND LPAD(TRIM(rw),2,'0') = ?");
                $stmtRw->execute([$rwNomor]);
                $countRw = (int)$stmtRw->fetchColumn();
            }

            $out = "📊 **Jumlah Warga Terdaftar di Wilayah Domisili Kamu**:\n\n";
            if ($rtNomor !== '-') {
                $out .= "• **Lingkungan RT {$rtNomor} / RW {$rwNomor}**: **{$countRt} warga** (Ketua RT: **{$wilayah['rt_ketua']}**)\n";
            }
            if ($rwNomor !== '-') {
                $out .= "• **Total Wilayah RW {$rwNomor}**: **{$countRw} warga** (Ketua RW: **{$wilayah['rw_ketua']}**)\n";
            }
            $out .= "\nData ini bersumber dari basis data kependudukan resmi **{$wilayah['desa_nama']}** yang telah terverifikasi di sistem SIWARGA. 👥";
            return $out;
        } else {
            return "Informasi RT/RW kamu belum lengkap pada profil akun. Silakan lengkapi data domisili RT/RW kamu di menu **Pengaturan** ya! 😊";
        }
    }

    // 1A. Skenario A: Pertanyaan & Follow-Up Wilayah RW/RT
    if ($ctx['intent'] === 'rw_count' || (preg_match('/\b(?:ada\s+)?(?:berapa\s+(?:banyak\s+)?(?:jumlah\s+)?(?:wilayah\s+)?rw|desa\s+ini\s+punya\s+berapa\s+rw|total\s+(?:ketua\s+)?(?:wilayah\s+)?rw|jumlah\s+wilayah\s+rw|total\s+rw)\b/i', $q) && !preg_match('/\b(warga|penduduk|jiwa|aduan|pengaduan|surat)\b/i', $q))) {
        $rwList = AIDataHelper::getDaftarRwLengkap($pdo);
        $totalRw = count($rwList);
        $totalRt = (int)$pdo->query("SELECT COUNT(DISTINCT nomor) FROM rt")->fetchColumn();
        return "Di desa **{$wilayah['desa_nama']}**, saat ini terdapat **{$totalRw} wilayah RW** (dengan total {$totalRt} RT).\n\n💡 *Ketik **\"Siapa saja ketuanya?\"** untuk melihat daftar nama ketua RW.*";
    }

    if ($ctx['intent'] === 'list_ketua_rw') {
        $rwList = AIDataHelper::getDaftarRwLengkap($pdo);
        $out = "📋 **Daftar Seluruh Ketua RW di Desa {$wilayah['desa_nama']}**:\n\n";
        foreach ($rwList as $i => $rw) {
            $out .= ($i + 1) . ". **RW {$rw['nomor']}**: {$rw['ketua']}\n";
        }
        $out .= "\n💡 *Ketik **\"Jelaskan RW nomor 3\"** untuk melihat informasi lengkap tentang wilayah tertentu.*";
        return trim($out);
    }

    if ($ctx['intent'] === 'rw_detail') {
        $targetRw = $ctx['target_rw'] ?? '01';
        $stmt = $pdo->prepare("
            SELECT rw.nomor, rw.ketua, u.no_telp, u.no_hp, u.email,
                   (SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = LPAD(TRIM(rw.nomor),2,'0')) AS jumlah_warga,
                   (SELECT COUNT(DISTINCT nomor) FROM rt WHERE rw_id = rw.id) AS jumlah_rt
            FROM rw 
            LEFT JOIN users u ON LPAD(TRIM(u.rw),2,'0') = LPAD(TRIM(rw.nomor),2,'0') AND u.role = 'rw'
            WHERE LPAD(TRIM(rw.nomor),2,'0') = ? 
            LIMIT 1
        ");
        $stmt->execute([$targetRw]);
        $rw_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rw_data) {
            $kontak = !empty($rw_data['no_telp']) ? $rw_data['no_telp'] : (!empty($rw_data['no_hp']) ? $rw_data['no_hp'] : '-');
            
            // Cek kondisi aduan di RW tersebut
            $stmtAduan = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status='proses' THEN 1 ELSE 0 END) as proses FROM pengaduan p JOIN users u ON p.user_id = u.id WHERE LPAD(TRIM(u.rw),2,'0') = ?");
            $stmtAduan->execute([$targetRw]);
            $aduanInfo = $stmtAduan->fetch(PDO::FETCH_ASSOC);
            $totalAduan = (int)($aduanInfo['total'] ?? 0);
            $pendingAduan = (int)($aduanInfo['pending'] ?? 0);
            $prosesAduan = (int)($aduanInfo['proses'] ?? 0);

            $kondisiStr = "Kondisi wilayah tertib dan kondusif.";
            if ($totalAduan > 0) {
                $kondisiStr .= " Terdapat riwayat {$totalAduan} pengaduan warga";
                if ($pendingAduan > 0 || $prosesAduan > 0) {
                    $kondisiStr .= " (" . ($pendingAduan + $prosesAduan) . " masih dalam penanganan).";
                } else {
                    $kondisiStr .= " (seluruhnya telah tuntas diselesaikan).";
                }
            }

            $out = "📋 **Informasi Wilayah RW {$rw_data['nomor']}**:\n\n" .
                   "• **Ketua RW**: **{$rw_data['ketua']}**\n" .
                   "• **Kontak**: {$kontak}\n" .
                   "• **Jumlah RT**: **{$rw_data['jumlah_rt']} RT**\n" .
                   "• **Jumlah Warga Terdaftar**: **{$rw_data['jumlah_warga']} orang**\n" .
                   "• **Kondisi Wilayah**: {$kondisiStr}\n\n" .
                   "💡 *Ketik **\"Berapa jumlah warganya?\"** untuk rincian kependudukan RW {$rw_data['nomor']}.*";
            return $out;
        }
        return "Data RW {$targetRw} tidak ditemukan di database. 🙏";
    }

    if ($ctx['intent'] === 'rw_warga_count') {
        $targetRw = $ctx['target_rw'];
        if ($targetRw !== null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = ?");
            $stmt->execute([$targetRw]);
            $count = (int)$stmt->fetchColumn();

            $stmtK = $pdo->prepare("SELECT ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ?");
            $stmtK->execute([$targetRw]);
            $ketua = $stmtK->fetchColumn();
            $infoKetua = $ketua ? " (Ketua RW: **{$ketua}**)" : "";

            return "Di **RW {$targetRw}**, saat ini terdapat **{$count} warga** yang terdaftar{$infoKetua}.";
        }
        if ($ctx['target_rt'] !== null) {
            $targetRt = $ctx['target_rt'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt),2,'0') = ?");
            $stmt->execute([$targetRt]);
            $count = (int)$stmt->fetchColumn();
            return "Di **RT {$targetRt}**, saat ini terdapat **{$count} warga** yang terdaftar.";
        }
    }

    // 1B. Skenario B: Pertanyaan & Follow-Up Surat (Pending, Pemohon, Verifikasi RT)
    if ($ctx['intent'] === 'surat_pending_count') {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if ($role === 'warga') {
            $pendingCount = count($surat['pending_list'] ?? []);
            if ($pendingCount === 0) {
                return "Saat ini Anda tidak memiliki pengajuan surat yang berstatus pending. Seluruh permohonan surat Anda telah selesai diproses! 😊";
            }
            return "Saat ini Anda memiliki **{$pendingCount} pengajuan surat** yang masih berstatus **Pending** (menunggu persetujuan).\n\n💡 *Ketik **\"Siapa pemohonnya?\"** atau periksa menu Surat untuk rincian.*";
        }
        return "Saat ini terdapat **{$surat['pengajuan_pending']} pengajuan surat** yang masih menunggu persetujuan (status *Pending*) di wilayah **{$surat['scope']}**.\n\n💡 *Ketik **\"Siapa pemohonnya?\"** untuk melihat rincian pemohon surat pending.*";
    }

    if ($ctx['intent'] === 'list_applicants') {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if ($role === 'warga') {
            if (empty($surat['pending_list'])) {
                return "Sebagai **Warga**, demi perlindungan privasi data warga lain, Anda hanya dapat melihat permohonan surat milik Anda sendiri. Saat ini Anda tidak memiliki pengajuan surat yang pending. 😊";
            }
            $out = "Berikut pengajuan surat milik Anda yang masih berstatus **Pending** 📄:\n\n";
            foreach ($surat['pending_list'] as $i => $p) {
                $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim' : '📭 Belum dikirim';
                $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . "\n   • **Nomor Surat**: `{$p['nomor_surat']}`\n   • **Jenis Surat**: {$p['judul']}\n   • **Status Surat**: ⏳ **PENDING**\n   • **Status Email**: $statusEmail\n\n";
            }
            return trim($out);
        }

        if (empty($surat['pending_list'])) {
            return "Kabar baik! Di wilayah **{$surat['scope']}**, saat ini **tidak ada pengajuan surat yang berstatus Pending**. Semua pengajuan telah selesai diproses! 🎉";
        }
        $out = "Daftar pemohon pengajuan surat berstatus **Pending** di wilayah **{$surat['scope']}** (" . count($surat['pending_list']) . " surat):\n\n";
        foreach ($surat['pending_list'] as $i => $p) {
            $wilayahStr = (!empty($p['rt']) || !empty($p['rw'])) ? " (RT " . ($p['rt'] ?: '-') . " / RW " . ($p['rw'] ?: '-') . ")" : '';
            $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($p['email_sent_at'])) . ')' : '📭 Belum dikirim';
            $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . $wilayahStr . "\n" .
                   "   • **Nomor Surat**: `{$p['nomor_surat']}`\n" .
                   "   • **Jenis Surat**: {$p['judul']}\n" .
                   "   • **Status Surat**: ⏳ **PENDING**\n" .
                   "   • **Status Email**: {$statusEmail}\n\n";
        }
        $out .= "💡 *Ketik **\"Sudah diverifikasi RT belum?\"** untuk memeriksa status persetujuannya.*";
        return trim($out);
    }

    if ($ctx['intent'] === 'surat_verification_status') {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        $totalPending = $surat['pengajuan_pending'] ?? count($surat['pending_list'] ?? []);
        return "Pengajuan surat tersebut saat ini masih berstatus **Pending** ({$totalPending} surat), yang artinya **belum diverifikasi atau disetujui oleh Ketua RT maupun Admin Desa**.\n\nSurat-surat tersebut masih dalam antrean peninjauan pada sistem persuratan. Pengurus yang berwenang dapat membuka menu **Surat** untuk melakukan peninjauan berkas dan memberikan persetujuan (Approve) secara resmi.";
    }

    // Follow-up spesifik pemohon surat pending (misal: "Yang Kirana berasal dari RT berapa?", "Surat apa yang dia ajukan?")
    if (
        preg_match('/\b(kirana|fatur)\b/i', $q) ||
        (
            ($ctx['topic'] === 'surat' || preg_match('/\b(surat|pengajuan|pemohon)\b/i', $lastQ . ' ' . $lastAns)) &&
            (preg_match('/\b(dia|pemohon|orang tersebut|beliau)\b/i', $q) || preg_match('/\b(dari\s+rt|rt\s+berapa|asal|wilayah|tinggal|surat\s+apa|jenis\s+surat|mengajukan\s+apa)\b/i', $q))
        )
    ) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        $pendingList = $surat['pending_list'] ?? [];
        
        $targetApplicant = null;
        if (preg_match('/\bkirana\b/i', $q)) $targetApplicant = 'kirana';
        elseif (preg_match('/\bfatur\b/i', $q)) $targetApplicant = 'fatur';
        elseif (!empty($lastQ) && preg_match('/\bkirana\b/i', $lastQ)) $targetApplicant = 'kirana';
        elseif (!empty($lastQ) && preg_match('/\bfatur\b/i', $lastQ)) $targetApplicant = 'fatur';
        elseif (!empty($lastAns) && preg_match('/\bkirana\b/i', $lastAns)) $targetApplicant = 'kirana';
        elseif (!empty($lastAns) && preg_match('/\bfatur\b/i', $lastAns)) $targetApplicant = 'fatur';

        $found = null;
        if ($targetApplicant !== null) {
            foreach ($pendingList as $p) {
                if (stripos($p['pemohon'], $targetApplicant) !== false) {
                    $found = $p;
                    break;
                }
            }
        } elseif (!empty($pendingList)) {
            $found = $pendingList[0];
        }

        if ($found) {
            $wilStr = (!empty($found['rt']) || !empty($found['rw'])) ? "**RT {$found['rt']} / RW {$found['rw']}**" : "wilayah desa (data RT belum dispesifikasi)";
            if (preg_match('/\b(rt|rw|wilayah|asal|tinggal|berasal)\b/i', $q)) {
                return "Pemohon **{$found['pemohon']}** tercatat berasal dari wilayah {$wilStr}.\n\nSurat yang diajukan adalah **Surat Keterangan {$found['judul']}** (No: `{$found['nomor_surat']}`).";
            }
            if (preg_match('/\b(surat\s+apa|jenis|diajukan|mengajukan|keperluan|permohonan)\b/i', $q)) {
                return "**{$found['pemohon']}** mengajukan permohonan **Surat Keterangan {$found['judul']}** (Nomor Surat: `{$found['nomor_surat']}`) yang saat ini masih berstatus **Pending** (menunggu persetujuan RT/Desa).";
            }
        }
    }

    // 1C. Skenario C: Pertanyaan & Follow-Up Pengaduan (Count, Status, Unresolved)
    if ($ctx['intent'] === 'pengaduan_count') {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if ($role === 'warga') {
            return "Saat ini Anda telah mengajukan total **{$pengaduan['total']} pengaduan** (Pending: {$pengaduan['pending']}, Proses: {$pengaduan['proses']}, Selesai: {$pengaduan['selesai']}).\n\n💡 *Ketik **\"Bagaimana statusnya?\"** untuk rincian status.*";
        }
        return "Saat ini terdapat total **{$pengaduan['total']} pengaduan** warga di wilayah **{$pengaduan['scope']}**:\n" .
               "• ⏳ **Pending**: {$pengaduan['pending']} aduan\n" .
               "• 🔄 **Proses**: {$pengaduan['proses']} aduan\n" .
               "• ✅ **Selesai**: {$pengaduan['selesai']} aduan\n" .
               "• ❌ **Ditolak**: {$pengaduan['ditolak']} aduan\n\n" .
               "💡 *Ketik **\"Bagaimana statusnya?\"** atau **\"Yang belum selesai siapa saja?\"** untuk melihat rincian.*";
    }

    if ($ctx['intent'] === 'pengaduan_status') {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if ($role === 'warga') {
            if (empty($pengaduan['pengaduan_saya'])) {
                return "Anda belum memiliki riwayat pengaduan yang diajukan di sistem saat ini. 😊";
            }
            $out = "📊 **Status Pengaduan Milik Anda**:\n\n";
            foreach ($pengaduan['pengaduan_saya'] as $i => $ps) {
                $stBadge = ($ps['status'] === 'selesai') ? '✅ Selesai' : (($ps['status'] === 'proses') ? '🔄 Proses' : (($ps['status'] === 'ditolak') ? '❌ Ditolak' : '⏳ Pending'));
                $out .= ($i + 1) . ". **{$ps['judul']}** (Kategori: {$ps['kategori']})\n   • Status: $stBadge\n   • Tanggal: " . date('d M Y', strtotime($ps['created_at'])) . "\n\n";
            }
            return trim($out);
        }
        $out = "📊 **Rincian Status Pengaduan Warga di Wilayah {$pengaduan['scope']}** (Total {$pengaduan['total']} Aduan):\n\n" .
               "• ⏳ **Pending (Menunggu Tindak Lanjut)**: **{$pengaduan['pending']} aduan**\n" .
               "• 🔄 **Proses (Sedang Ditangani)**: **{$pengaduan['proses']} aduan**\n" .
               "• ✅ **Selesai (Sudah Tuntas)**: **{$pengaduan['selesai']} aduan**\n" .
               "• ❌ **Ditolak**: **{$pengaduan['ditolak']} aduan**\n\n" .
               "💡 *Ketik **\"Yang belum selesai siapa saja?\"** untuk melihat daftar pengaduan yang masih aktif.*";
        return $out;
    }

    if ($ctx['intent'] === 'unresolved_list') {
        if ($role === 'warga') {
            $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
            $unresolvedSaya = array_filter($pengaduan['pengaduan_saya'] ?? [], fn($ps) => in_array($ps['status'], ['pending', 'proses']));
            if (empty($unresolvedSaya)) {
                return "Seluruh pengaduan milik Anda sudah berstatus selesai atau belum ada aduan pending. 🎉";
            }
            $out = "Berikut daftar pengaduan milik Anda yang belum selesai:\n\n";
            foreach ($unresolvedSaya as $i => $ps) {
                $stBadge = ($ps['status'] === 'pending') ? '⏳ Pending' : '🔄 Proses';
                $out .= ($i + 1) . ". **{$ps['judul']}** ($stBadge)\n   • Kategori: {$ps['kategori']}\n   • Tanggal: " . date('d M Y', strtotime($ps['created_at'])) . "\n\n";
            }
            return trim($out);
        }

        $unresolved = AIDataHelper::getPengaduanBelumSelesaiDetail($pdo, $user);
        if (empty($unresolved)) {
            return "🎉 Kabar baik! Saat ini **tidak ada pengajuan pengaduan yang belum selesai** di wilayah kewenangan Anda. Seluruh pengaduan telah terselesaikan!";
        }
        $out = "📋 **Daftar Pengaduan Warga yang Belum Selesai (" . count($unresolved) . " Aduan)**:\n\n";
        foreach ($unresolved as $i => $u) {
            $stBadge = ($u['status'] === 'pending') ? '⏳ **Pending** (Menunggu tindak lanjut)' : '🔄 **Proses** (Sedang ditangani)';
            $wilStr = (!empty($u['rt']) || !empty($u['rw'])) ? " (RT " . ($u['rt'] ?: '-') . " / RW " . ($u['rw'] ?: '-') . ")" : '';
            $tgl = date('d M Y, H:i', strtotime($u['created_at']));
            $out .= ($i + 1) . ". **" . htmlspecialchars($u['pelapor']) . "**{$wilStr}\n" .
                   "   • **Judul**: *\"" . htmlspecialchars($u['judul']) . "\"*\n" .
                   "   • **Kategori**: " . htmlspecialchars($u['kategori']) . "\n" .
                   "   • **Status**: {$stBadge}\n" .
                   "   • **Waktu**: {$tgl}\n\n";
        }
        $out .= "💡 *Silakan buka menu **Pengaduan** untuk memberikan tanggapan atau memperbarui status penanganan.*";
        return trim($out);
    }

    // ------------------------------------------------------------
    // MENU SIDEBAR: DAFTAR MENU DAN JUMLAH MENU BERDASARKAN ROLE
    // ------------------------------------------------------------
    if (
        (preg_match('/\b(menu|sidebar|navigasi)\b/i', $q) && preg_match('/\b(apa\s+saja|ada\s+berapa|daftar|list|tampilkan|sebutkan|bisa\s+akses|hak\s+akses|tersedia|punya)\b/i', $q)) ||
        preg_match('/^(?:ada\s+)?berapa\s+(?:jumlah\s+)?menu(?:\s+(?:di\s+)?sidebar)?\??$/i', $q) ||
        preg_match('/^(?:menu|daftar\s+menu)\s+(?:sidebar\s+)?(?:apa\s+saja|ada\s+apa\s+saja)\??$/i', $q)
    ) {
        $menus = AIDataHelper::getMenuSidebarByRole($role);
        $roleLabel = RoleCapabilities::label($role);
        $out = "📋 **Menu Sidebar untuk Role {$roleLabel} (" . count($menus) . " Menu)**:\n\n";
        foreach ($menus as $i => $m) {
            $out .= ($i + 1) . ". **{$m}**\n";
        }
        $out .= "\n💡 *Anda dapat menanyakan informasi, statistik, atau panduan seputar menu-menu di atas kepada saya!* 😊";
        return $out;
    }

    // ------------------------------------------------------------
    // MENU LAPORAN: REKAPITULASI DATA DESA / WILAYAH
    // ------------------------------------------------------------
    if (
        (preg_match('/\blaporan\b/i', $q) && preg_match('/\b(isi|rekap|rekapitulasi|ringkasan|data|desa|rt|rw|semua|saat\s+ini)\b/i', $q) && !preg_match('/\b(buat|bikin|kirim|tulis)\b/i', $q)) ||
        preg_match('/\b(rekapitulasi\s+data|rekap\s+data\s+desa|rekap\s+desa|ringkasan\s+laporan)\b/i', $q)
    ) {
        if ($role === 'warga') {
            return "Maaf, menu **Laporan & Rekapitulasi Data** hanya dapat diakses oleh perangkat desa (RT, RW, Admin Desa, dan Super Admin). Sebagai Warga, Anda dapat melihat riwayat permohonan surat dan pengaduan milik Anda sendiri di menu terkait. 😊";
        }
        $lap = AIDataHelper::getLaporanRekapitulasi($pdo, $user);
        if (!empty($lap['allowed'])) {
            $out = "📊 **Ringkasan Laporan & Rekapitulasi Sistem ({$lap['scope']})**:\n\n";
            $out .= "• 👥 **Kependudukan**: Total **{$lap['total_warga']} warga** (dari total {$lap['total_users']} akun terdaftar)\n";
            $out .= "• 🏘️ **Wilayah**: **{$lap['total_rt']} RT** dan **{$lap['total_rw']} RW**\n";
            $out .= "• 📢 **Pengaduan**: Total **{$lap['total_pengaduan']} aduan** (✅ Selesai: {$lap['pengaduan_selesai']}, ⏳ Masih Aktif: {$lap['pengaduan_aktif']})\n";
            $out .= "• 📄 **Persuratan**: Total **{$lap['total_surat']} pengajuan surat** masuk\n";
            $out .= "• 📋 **Katalog Layanan**: **{$lap['total_layanan']} jenis pelayanan** aktif\n\n";
            $out .= "👥 **Rincian Akun Pengguna per Role**:\n";
            foreach ($lap['user_roles'] as $rKey => $cnt) {
                $lbl = RoleCapabilities::label($rKey);
                $out .= "• {$lbl}: **{$cnt} akun**\n";
            }
            $out .= "\n💡 *Buka menu **Laporan** di sidebar untuk mencetak atau melihat grafik rincian.*";
            return $out;
        }
    }

    // ------------------------------------------------------------
    // MENU TEMPLATE SURAT RESMI DESA
    // ------------------------------------------------------------
    if (
        (preg_match('/\b(template|format)\b/i', $q) && preg_match('/\bsurat\b/i', $q) && !preg_match('/\b(buat|bikin|ajukan)\b/i', $q)) ||
        preg_match('/\b(daftar\s+template\s+surat|template\s+surat\s+apa\s+saja)\b/i', $q)
    ) {
        $templates = AIDataHelper::getSuratTemplateList($pdo);
        if (!empty($templates)) {
            $out = "📄 **Daftar Template Surat Resmi Tersedia (" . count($templates) . " Template)**:\n\n";
            foreach ($templates as $i => $t) {
                $out .= ($i + 1) . ". **{$t['nama']}** (Format: `{$t['format_nomor']}`)\n";
            }
            $out .= "\n💡 *Anda dapat mengajukan permohonan surat melalui menu **Buat Surat** atau cukup katakan: \"Buatkan surat [nama surat]\".*";
            return $out;
        }
    }

    // ------------------------------------------------------------
    // MENU KONFIGURASI AI & WHATSAPP GATEWAY (SUPER ADMIN)
    // ------------------------------------------------------------
    if (preg_match('/\b(konfigurasi\s+ai|setelan\s+ai|model\s+ai|status\s+ai)\b/i', $q)) {
        if ($role !== 'super_admin') {
            return "Maaf, menu **Konfigurasi AI** hanya dapat diakses dan dikelola oleh Super Admin sistem. 🙏";
        }
        $cfg = AIDataHelper::getKonfigurasiSistemSummary($user);
        if (!empty($cfg['allowed'])) {
            $ai = $cfg['ai'];
            return "🤖 **Status Konfigurasi AI SiWarga (Khusus Super Admin)**:\n\n" .
                   "• **Model Utama**: `{$ai['model']}`\n" .
                   "• **Temperature**: `{$ai['temperature']}`\n" .
                   "• **Status API Key**: {$ai['api_key_masked']} (Aktif)\n" .
                   "• **Failover Backup Keys**: **{$ai['backup_keys']} API Key cadangan** tersimpan & aktif\n" .
                   "• **Kondisi Sistem**: {$ai['status']}\n\n" .
                   "💡 *Untuk mengubah API Key atau beralih model, silakan buka menu **Konfigurasi AI** di sidebar.*";
        }
    }

    if (preg_match('/\b(konfigurasi\s+whatsapp|wa\s+gateway|status\s+whatsapp|whatsapp\s+gateway|fonnte)\b/i', $q)) {
        if ($role !== 'super_admin') {
            return "Maaf, menu **Konfigurasi WhatsApp** hanya dapat diakses dan dikelola oleh Super Admin sistem. 🙏";
        }
        $cfg = AIDataHelper::getKonfigurasiSistemSummary($user);
        if (!empty($cfg['allowed'])) {
            $wa = $cfg['whatsapp'];
            return "📲 **Status Konfigurasi WhatsApp Gateway (Khusus Super Admin)**:\n\n" .
                   "• **Gateway Penyedia**: {$wa['gateway']}\n" .
                   "• **Status Koneksi**: **{$wa['status']}**\n" .
                   "• **Nomor Sender Desa**: `{$wa['sender_phone']}`\n" .
                   "• **API Token**: `{$wa['api_key_masked']}`\n" .
                   "• **Fungsi Notifikasi**: Otomatis mengirimkan kabar pengumuman warga, verifikasi akun baru, dan pembaruan pengaduan.\n\n" .
                   "💡 *Untuk mengetes pengiriman pesan atau menghubungkan device WhatsApp baru, buka menu **Konfigurasi WhatsApp**.*";
        }
    }

    // ------------------------------------------------------------
    // MENU PENGATURAN / PROFIL SAYA & NOTIFIKASI WA
    // ------------------------------------------------------------
    if (
        preg_match('/\b(profil\s+saya|data\s+akun\s+saya|informasi\s+akun\s+saya|nomor\s+(?:hp|wa|telepon)\s+saya|notifikasi\s+wa\s+saya|status\s+langganan\s+wa)\b/i', $q) ||
        (preg_match('/\b(profil|akun)\b/i', $q) && preg_match('/\b(saya|milik saya|terdaftar)\b/i', $q) && !preg_match('/\b(hapus|bikin|buat)\b/i', $q))
    ) {
        $p = AIDataHelper::getProfilUserSummary($pdo, $user);
        if ($p) {
            $roleLabel = RoleCapabilities::label($p['role']);
            return "👤 **Profil Akun Anda di SIWARGA**:\n\n" .
                   "• **Nama Lengkap**: **{$p['nama']}**\n" .
                   "• **Peran / Role**: {$roleLabel}\n" .
                   "• **Alamat Email**: `{$p['email']}`\n" .
                   "• **Wilayah**: {$p['wilayah']}\n" .
                   "• **Nomor WhatsApp**: `{$p['telepon']}`\n" .
                   "• **Notifikasi WhatsApp**: {$p['wa_opt_in']}\n" .
                   "• **Status Akun**: ✅ " . ucfirst($p['status_verifikasi']) . "\n" .
                   "• **Terdaftar Sejak**: {$p['terdaftar_sejak']}\n\n" .
                   "💡 *Anda dapat memperbarui nama, nomor telepon, dan preferensi notifikasi di menu **Pengaturan**.* 😊";
        }
    }

    return null;
}

// ============================================================
// FORM SURAT: DETEKSI & PARSER PENGAJUAN SURAT VIA CHAT AI
// ============================================================
function prosesAIFormSurat($pertanyaan, $user, $pdo, $sessionId = null) {
    $q = AIDataHelper::normalizeText($pertanyaan);

    // Jangan bajak jika pengguna hanya menanyakan informasi syarat, biaya, alur, atau konsultasi
    $isInquiryOnly = preg_match('/\b(syarat|persyaratan|biaya|tarif|ongkos|berapa|bagaimana|gimana|kapan|apakah|info|informasi|penjelasan|tanya|apa saja|bisa bikin apa|ada apa saja|maksud|fungsi|kegunaan)\b/i', $q);
    $isExplicitCreate = preg_match('/\b(pembuatan surat|buat(?:kan)?\s+surat|bikin(?:kan)?\s+surat|mau\s+(?:bikin|buat)\s+surat|ajukan\s+surat|mohon\s+surat|minta\s+surat)\b/i', $q);
    $hasSuratKeyword = preg_match('/\b(domisili|usaha|sktm|skck|pengantar ktp)\b/i', $q);

    // Cek apakah ada format data pengisian surat langsung (comma-separated nama, nik, dll)
    $parsed = parseSuratFields($pertanyaan);
    $hasCompleteData = (!empty($parsed['nama']) && !empty($parsed['nik']) && !empty($parsed['alamat']) && !empty($parsed['keperluan']));

    if ($hasCompleteData || ($isExplicitCreate || ($hasSuratKeyword && !$isInquiryOnly))) {
        $jenis = null;
        if (preg_match('/domisili/', $q)) $jenis = 'Domisili';
        elseif (preg_match('/pengantar\s+ktp|\bktp\b/', $q)) $jenis = 'Pengantar KTP';
        elseif (preg_match('/usaha/', $q)) $jenis = 'Usaha';
        elseif (preg_match('/sktm|tidak mampu/', $q)) $jenis = 'SKTM';
        elseif (preg_match('/skck/', $q)) $jenis = 'SKCK';

        if ($jenis) {
            if ($hasCompleteData) {
                return "DATA_SURAT_LENGKAP:\n{$jenis}\nNama: {$parsed['nama']}\nNIK: {$parsed['nik']}\nTTL: " . ($parsed['ttl'] ?? '-') . "\nJK: " . ($parsed['jk'] ?? '-') . "\nPekerjaan: " . ($parsed['pekerjaan'] ?? '-') . "\nAlamat: {$parsed['alamat']}\nKeperluan: {$parsed['keperluan']}";
            }
            $wajib = ['nama'=>'Nama Lengkap','nik'=>'NIK','ttl'=>'Tempat/Tanggal Lahir','jk'=>'Jenis Kelamin (L/P)','pekerjaan'=>'Pekerjaan','alamat'=>'Alamat','keperluan'=>'Keperluan Surat'];
            $adaIsi = false;
            foreach ($wajib as $key => $label) {
                if (!empty($parsed[$key])) { $adaIsi = true; break; }
            }
            $out = $adaIsi ? "Hampir selesai! Tapi datanya masih ada yang **kurang lengkap** nih ⚠️\n\n" : "Siap! Aku bantuin bikin **Surat $jenis** ya 📄\n\n";
            $out .= "**Tolong isi data berikut** (kirim semuanya sekaligus biar gampang):\n\n• **Jenis Surat**: $jenis\n";
            foreach ($wajib as $key => $label) {
                $out .= "• **$label**: " . (!empty($parsed[$key]) ? $parsed[$key] : '') . "\n";
            }
            return $out . "\n💡 *Contoh format pengiriman:*\nBudi Santoso, 3201234567890001, Jakarta 01 Januari 1990, L, Karyawan, Jl. Merdeka No. 10, Melamar kerja";
        }
    }
    return null;
}

// ============================================================
// PROSES AI (DUAL CONFIG & AUTO-FALLBACK)
// ============================================================
function prosesAI($pertanyaan, $user, $pdo, $sessionId = null) {
    $check = ScopeFilter::check($pertanyaan);
    if (!$check['allowed']) {
        log_activity('ai_blocked', 'reason=' . $check['reason']);
        if (($check['type'] ?? '') === 'privacy_nik') return ScopeFilter::rejectNikMessage();
        if (($check['type'] ?? '') === 'privacy_cred') return ScopeFilter::rejectCredentialMessage();
        return ScopeFilter::rejectMessage();
    }
    $role = $user['role'] ?? 'warga';
    $access = RoleCapabilities::checkAccess($role, $pertanyaan, $user);
    if (!$access['allowed']) { 
        log_activity('ai_role_blocked', "role=$role cap={$access['capability']}"); 
        return RoleCapabilities::rejectMessage($role, $access['capability'], $user); 
    }

    // 1. FAST-TRACK INSTAN: Sapaan, Terima Kasih, & Skenario Kunci A, B, C
    $fastAnswer = prosesAIFastTrack($pertanyaan, $user, $pdo, $sessionId);
    if ($fastAnswer !== null) return $fastAnswer;

    // 2. FORM SURAT: Jika user secara spesifik berniat membuat/mengisi formulir pengajuan surat
    $formAnswer = prosesAIFormSurat($pertanyaan, $user, $pdo, $sessionId);
    if ($formAnswer !== null) return $formAnswer;

    // 3. GEMINI AI: OTAK PENALARAN UTAMA & PEMAHAMAN SEMANTIK BEBAS
    // Memahami pertanyaan bebas, variasi kalimat, typo, perhitungan, analisis, dan fakta database
    $aiCfg = AIConfig::get();
    $primaryKey = trim($aiCfg['api_key'] ?: (getenv('GEMINI_API_KEY') ?: ''));
    $selectedModel = trim($aiCfg['model'] ?: (getenv('GEMINI_MODEL') ? explode(',', getenv('GEMINI_MODEL'))[0] : 'gemini-3.6-flash'));
    $temperature = (float)($aiCfg['temperature'] ?? 0.5);
    $customPrompt = $aiCfg['system_prompt'] ?? '';
    $fallbackMsg = $aiCfg['fallback_message'] ?? '';

    $configuredModels = $aiCfg['available_models'] ?? [];
    $hardcodedFallbacks = ['gemini-3.6-flash', 'gemini-3.5-flash', 'gemini-3-flash-preview', 'gemini-flash-lite-latest', 'gemini-3.1-flash-lite', 'gemini-flash-latest'];
    $allModels = array_values(array_unique(array_merge([$selectedModel], $hardcodedFallbacks, $configuredModels)));

    $keyChains = [];
    $seenKeys = [];

    // Baca Key Utama (dari storage/ai_config.json atau .env)
    if (!empty($primaryKey)) {
        if (strpos($primaryKey, ',') !== false) {
            $envKeyList = explode(',', $primaryKey);
            $primaryKey = trim($envKeyList[0]);
            for ($k = 1; $k < count($envKeyList); $k++) {
                $extraKey = trim($envKeyList[$k]);
                if (!empty($extraKey) && !isset($seenKeys[$extraKey]) && !AIConfig::isMasked($extraKey)) {
                    $keyChains[] = [
                        'type'   => 'Cadangan (ENV #' . $k . ')',
                        'key'    => $extraKey,
                        'model'  => $selectedModel,
                        'models' => $allModels,
                    ];
                    $seenKeys[$extraKey] = true;
                }
            }
        }
        if (!empty($primaryKey) && !AIConfig::isMasked($primaryKey) && !isset($seenKeys[$primaryKey])) {
            $keyChains[] = [
                'type'   => 'Utama',
                'key'    => $primaryKey,
                'model'  => $selectedModel,
                'models' => $allModels,
            ];
            $seenKeys[$primaryKey] = true;
        }
    }

    // Baca seluruh Key Cadangan yang tersimpan di Konfigurasi AI
    if (!empty($aiCfg['backup_keys']) && is_array($aiCfg['backup_keys'])) {
        foreach ($aiCfg['backup_keys'] as $idx => $bk) {
            $bKey = trim(is_array($bk) ? ($bk['key'] ?? '') : (is_string($bk) ? $bk : ''));
            if (!empty($bKey) && !isset($seenKeys[$bKey]) && !AIConfig::isMasked($bKey)) {
                $bModel = trim(is_array($bk) ? ($bk['model'] ?? $selectedModel) : $selectedModel);
                $bModels = array_values(array_unique(array_merge([$bModel], $allModels)));
                $keyChains[] = [
                    'type'   => 'Cadangan #' . ($idx + 1),
                    'key'    => $bKey,
                    'model'  => $bModel,
                    'models' => $bModels,
                ];
                $seenKeys[$bKey] = true;
            }
        }
    }

    $geminiResponse = null;
    if (!empty($keyChains)) {
        $geminiResponse = tanyaGeminiFailover($pertanyaan, $user, $keyChains, $temperature, $customPrompt, $fallbackMsg, $sessionId);
        
        $isError = false;
        if ($geminiResponse === null) {
            $isError = true;
        } elseif (
            strpos($geminiResponse, 'kuota AI (Gemini) saat ini sedang habis') !== false ||
            strpos($geminiResponse, 'asisten AI sedang tidak dapat terhubung') !== false ||
            strpos($geminiResponse, 'server AI sedang mengalami antrean beban tinggi') !== false ||
            strpos($geminiResponse, 'API Key AI belum dikonfigurasi') !== false
        ) {
            $isError = true;
        }

        if (!$isError) {
            return $geminiResponse;
        }
    }

    // 4. SAFETY NET: LOGIKA ATURAN LOKAL (Jika Gemini offline / kuota habis)
    $local_answer = prosesAILokal($pertanyaan, $user, $pdo, $sessionId);
    if ($local_answer !== null) return $local_answer;

    if ($geminiResponse !== null) return $geminiResponse;
    if (!empty($fallbackMsg)) return $fallbackMsg;
    return "Hmm, aku lagi belum bisa jawab itu nih 😅\n\nCoba tanya seputar layanan atau data desa ya:\n• 📊 *\"Statistik desa\"*\n• 📍 Info RT/RW kamu\n• 👥 Daftar warga\n• 📄 Bikin surat online\n• ⚠️ Cara lapor pengaduan";
}

// ============================================================
// LAPIS 1: ATURAN LOKAL (FUZZY MATCHING / FALLBACK ENGINE)
// ============================================================
function prosesAILokal($pertanyaan, $user, $pdo, $sessionId = null) {
    $q_raw = $pertanyaan;
    $q = AIDataHelper::normalizeText($pertanyaan);
    
    if (ScopeFilter::isNikRequest($pertanyaan) || ScopeFilter::isNikRequest($q)) return ScopeFilter::rejectNikMessage();
    if (ScopeFilter::isCredentialRequest($pertanyaan) || ScopeFilter::isCredentialRequest($q)) return ScopeFilter::rejectCredentialMessage();

    // Jalankan Fast-Track jika dipanggil langsung
    $fastAnswer = prosesAIFastTrack($pertanyaan, $user, $pdo, $sessionId);
    if ($fastAnswer !== null) return $fastAnswer;

    $formAnswer = prosesAIFormSurat($pertanyaan, $user, $pdo, $sessionId);
    if ($formAnswer !== null) return $formAnswer;

    $nama_depan = explode(' ', $user['name'])[0];
    $role = $user['role'] ?? 'warga';
    $wilayah = AIDataHelper::getWilayahUser($pdo, $user);
    $ctx = AIDataHelper::resolveConversationContext($pdo, $user['id'], $sessionId, $pertanyaan);
    $lastQ = $ctx['last_q'] ?? '';
    $lastAns = $ctx['last_ans'] ?? '';

    // ============================================================
    // FAST-TRACK: PERTANYAAN KHUSUS / SERING DIAJUKAN WARGA (FAQ & DATA MANDIRI)
    // ============================================================

    // 1. SIAPA KETUA RW SAYA?
    if (
        preg_match('/\b(ketua\s+rw\s*(saya|aku|kami|tempat\s+saya|domisili\s+saya|wilayah\s+saya)?|siapa\s+(?:nama\s+)?ketua\s+rw(?:\s+(?:saya|aku))?|nama\s+ketua\s+rw(?:\s+(?:saya|aku))?|rw\s+saya\s+ketuanya\s+siapa)\b/i', $q) &&
        !preg_match('/\brw\s*0?([1-9]\d*)\b/i', $q)
    ) {
        $rwNomor = $wilayah['rw_nomor'];
        $rwKetua = $wilayah['rw_ketua'];
        if ($rwNomor !== '-' && $rwKetua !== '-') {
            $kontak = ($wilayah['rw_telp'] !== '-') ? $wilayah['rw_telp'] : '-';
            $email  = ($wilayah['rw_email'] !== '-') ? $wilayah['rw_email'] : '-';
            return "Ketua **RW {$rwNomor}** di wilayah domisili kamu adalah **{$rwKetua}**.\n\n" .
                   "📋 **Informasi Wilayah RW {$rwNomor}**:\n" .
                   "• **Desa**: {$wilayah['desa_nama']}\n" .
                   "• **Ketua RW**: **{$rwKetua}**\n" .
                   "• **Kontak / No. HP**: {$kontak}\n" .
                   "• **Email**: {$email}\n\n" .
                   "Jika kamu membutuhkan informasi koordinasi lingkungan tingkat RW, kamu bisa menghubungi beliau ya! 😊";
        } elseif (in_array($role, ['super_admin', 'desa'])) {
            return "Sebagai **Administrator Desa**, Anda menaungi seluruh wilayah RW di Desa {$wilayah['desa_nama']}. Untuk melihat rincian ketua RW tertentu, Anda dapat bertanya: *\"Siapa ketua RW 01?\"* atau *\"Sebutkan seluruh nama ketua RW\"*. 😊";
        } else {
            return "Data RW kamu belum terdaftar di profil akun. Silakan perbarui data RT/RW kamu melalui menu **Pengaturan** ya! 😊";
        }
    }

    // 2. SIAPA KETUA RT SAYA?
    if (
        preg_match('/\b(ketua\s+rt\s*(saya|aku|kami|tempat\s+saya|domisili\s+saya|wilayah\s+saya)?|siapa\s+(?:nama\s+)?ketua\s+rt(?:\s+(?:saya|aku))?|nama\s+ketua\s+rt(?:\s+(?:saya|aku))?|rt\s+saya\s+ketuanya\s+siapa)\b/i', $q) &&
        !preg_match('/\brt\s*0?([1-9]\d*)\b/i', $q)
    ) {
        $rtNomor = $wilayah['rt_nomor'];
        $rwNomor = $wilayah['rw_nomor'];
        $rtKetua = $wilayah['rt_ketua'];
        if ($rtNomor !== '-' && $rtKetua !== '-') {
            $kontak = ($wilayah['rt_telp'] !== '-') ? $wilayah['rt_telp'] : '-';
            $email  = ($wilayah['rt_email'] !== '-') ? $wilayah['rt_email'] : '-';
            $rwLabel = ($rwNomor !== '-') ? " / RW {$rwNomor}" : "";
            return "Ketua **RT {$rtNomor}{$rwLabel}** di lingkungan tempat tinggal kamu adalah **{$rtKetua}**.\n\n" .
                   "📋 **Informasi Lingkungan RT {$rtNomor}**:\n" .
                   "• **Desa**: {$wilayah['desa_nama']}\n" .
                   "• **Ketua RT**: **{$rtKetua}**\n" .
                   "• **Kontak / No. HP**: {$kontak}\n" .
                   "• **Email**: {$email}\n\n" .
                   "Jika kamu memerlukan surat pengantar RT atau ada keperluan warga di lingkungan RT, kamu bisa menghubungi beliau ya! 😊";
        } elseif (in_array($role, ['super_admin', 'desa', 'rw'])) {
            return "Sebagai **Perangkat Desa**, Anda menaungi beberapa RT di wilayah Anda. Untuk melihat ketua RT tertentu, silakan tanyakan spesifik, misalnya: *\"Siapa ketua RT 01?\"* atau *\"Daftar ketua RT di RW 01\"*. 😊";
        } else {
            return "Data RT kamu belum terdaftar di profil akun. Silakan perbarui data RT/RW kamu melalui menu **Pengaturan** ya! 😊";
        }
    }

    // 3. PANDUAN CARA MENGAJUKAN SURAT
    if (
        preg_match('/\b(cara|langkah|panduan|bagaimana|gimana|prosedur|alur)\b.*\b(mengajukan|buat|bikin|ajukan|minta|urus|dapat|permohonan)\s+(surat|persuratan)\b/i', $q) ||
        preg_match('/\b(cara\s+(?:mengajukan|buat|bikin|ajukan)\s+surat|pengajuan\s+surat\s+bagaimana)\b/i', $q)
    ) {
        return "📌 **Panduan Cara Mengajukan Surat di SIWARGA**:\n\n" .
               "1. Buka menu **Pelayanan** di sidebar (atau menu **Buat Surat**).\n" .
               "2. Pilih jenis layanan surat yang kamu perlukan (misal: Surat Domisili, SKU, SKTM, Pengantar KTP/SKCK, dll).\n" .
               "3. Lengkapi formulir pengajuan dengan data yang benar serta unggah berkas persyaratan yang diminta.\n" .
               "4. Klik tombol **Kirim Permohonan**.\n" .
               "5. Suratmu akan langsung ditinjau oleh Ketua RT dan Petugas Desa untuk diverifikasi dan disetujui.\n\n" .
               "💡 *Setelah disetujui, file surat resmi (PDF) dapat langsung diunduh atau otomatis dikirimkan ke email kamu. Seluruh pengurusan surat di SIWARGA bersifat **GRATIS (Rp 0)**.* 😊";
    }

    // 4. PERSYARATAN SURAT TERTENTU (DOMISILI, SKU, SKTM, SKCK, DLL)
    if (
        preg_match('/\b(syarat|persyaratan|berkas|dokumen|kelengkapan)\b.*\b(surat|keterangan|pengantar)?\s*(domisili|usaha|sku|sktm|tidak\s+mampu|skck|ktp|kk|kelahiran|kematian)\b/i', $q) ||
        preg_match('/\b(surat\s+(?:keterangan\s+)?(domisili|usaha|sku|sktm|tidak\s+mampu|skck|pengantar\s+ktp))\b.*\b(syarat|persyaratan|dokumen)\b/i', $q)
    ) {
        $kw = 'domisili';
        if (preg_match('/\b(usaha|sku)\b/i', $q)) $kw = 'usaha';
        elseif (preg_match('/\b(sktm|tidak\s+mampu)\b/i', $q)) $kw = 'tidak mampu';
        elseif (preg_match('/\b(skck)\b/i', $q)) $kw = 'skck';
        elseif (preg_match('/\b(ktp)\b/i', $q)) $kw = 'ktp';
        elseif (preg_match('/\b(kk|kartu\s+keluarga)\b/i', $q)) $kw = 'kartu keluarga';
        elseif (preg_match('/\b(kelahiran)\b/i', $q)) $kw = 'kelahiran';
        elseif (preg_match('/\b(kematian)\b/i', $q)) $kw = 'kematian';

        $layanan = AIDataHelper::getPelayananDesa($pdo, $kw);
        if ($layanan) {
            $syaratText = !empty($layanan['syarat']) ? $layanan['syarat'] : "• Fotokopi KTP Pemohon\n• Fotokopi Kartu Keluarga (KK)\n• Surat Pengantar RT/RW";
            $deskripsi = !empty($layanan['deskripsi']) ? "_{$layanan['deskripsi']}_\n\n" : "";
            return "📄 **Persyaratan Layanan: {$layanan['nama']}**\n\n" .
                   $deskripsi .
                   "**Dokumen & Berkas Persyaratan:**\n" .
                   $syaratText . "\n\n" .
                   "💰 **Biaya / Tarif**: **GRATIS (Rp 0)**\n" .
                   "⏱️ **Proses**: Online via menu **Pelayanan** di sidebar.\n\n" .
                   "Kamu bisa langsung mengajukannya secara mandiri melalui menu **Pelayanan** ya! 😊";
        }
    }

    // 5. STATUS SURAT SAYA (WARGA)
    if (
        preg_match('/\b(surat|pengajuan)\b/i', $q) &&
        preg_match('/\b(status|bagaimana|gimana|cek|pantau|posisi)\b/i', $q) &&
        preg_match('/\b(saya|aku|ku|gw|gue|sy|suratku|milik\s+saya)\b/i', $q) &&
        !preg_match('/\b(pengaduan|aduan|laporan|keluhan)\b/i', $q)
    ) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if (empty($surat['surat_saya'])) {
            return "Halo Pak/Bu **{$nama_depan}**! Saat ini kamu belum memiliki pengajuan surat di sistem SIWARGA. Kamu bisa membuat permohonan surat baru di menu **Pelayanan** ya! 😊";
        }
        $out = "📄 **Status Pengajuan Surat Kamu**:\n\n";
        foreach ($surat['surat_saya'] as $i => $s) {
            $st = strtolower(trim($s['status'] ?? 'pending'));
            $badgeSurat = ($st === 'pending') ? '⏳ PENDING (Menunggu Persetujuan)' : (($st === 'disetujui') ? '✅ DISETUJUI' : '❌ DITOLAK');
            $statusEmail = !empty($s['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($s['email_sent_at'])) . ')' : '📭 Belum dikirim';
            $tgl = date('d M Y', strtotime($s['created_at']));
            $out .= ($i + 1) . ". **{$s['judul']}**\n" .
                   "   • Nomor Surat: `{$s['nomor_surat']}`\n" .
                   "   • Status Surat: $badgeSurat\n" .
                   "   • Status Email: $statusEmail\n" .
                   "   • Tanggal Pengajuan: $tgl\n\n";
        }
        $out .= "💡 *Catatan: File surat PDF resmi dapat diunduh atau dikirim ke email kamu setelah disetujui petugas desa. Pantau selengkapnya di menu **Pelayanan** ya!*";
        return $out;
    }

    // 6. STATUS PENGADUAN SAYA (WARGA)
    if (
        preg_match('/\b(pengaduan|aduan|laporan|keluhan|komplain)\b/i', $q) &&
        preg_match('/\b(status|bagaimana|gimana|cek|pantau|posisi|riwayat)\b/i', $q) &&
        preg_match('/\b(saya|aku|ku|gw|gue|sy|aduanku|pengaduanku|milik\s+saya)\b/i', $q)
    ) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if (empty($pengaduan['pengaduan_saya'])) {
            return "Halo Pak/Bu **{$nama_depan}**! Saat ini kamu belum memiliki riwayat pengaduan yang diajukan di sistem SIWARGA. Jika ada kendala lingkungan atau fasilitas umum, silakan sampaikan melalui menu **Pengaduan** ya! 😊";
        }
        $out = "⚠️ **Status Pengaduan Kamu**:\n\n";
        foreach ($pengaduan['pengaduan_saya'] as $i => $ps) {
            $stBadge = ($ps['status'] === 'selesai') ? '✅ Selesai' : (($ps['status'] === 'proses') ? '🔄 Sedang Diproses' : (($ps['status'] === 'ditolak') ? '❌ Ditolak' : '⏳ Pending (Menunggu Tindak Lanjut)'));
            $out .= ($i + 1) . ". **{$ps['judul']}**\n" .
                   "   • Kategori: {$ps['kategori']}\n" .
                   "   • Status: $stBadge\n" .
                   "   • Tanggal Lapor: " . date('d M Y', strtotime($ps['created_at'])) . "\n\n";
        }
        $out .= "💡 *Petugas RT/RW dan Admin Desa akan memperbarui status penanganan laporanmu secara berkala. Pantau selengkapnya di menu **Pengaduan** ya!*";
        return $out;
    }

    // 7. PANDUAN CARA MEMBUAT PENGADUAN
    if (
        (preg_match('/\b(cara|langkah|panduan|bagaimana|gimana|prosedur)\b.*\b(buat|bikin|lapor|mengadu|mengajukan|kirim)\b.*\b(pengaduan|keluhan|aduan|komplain)\b/i', $q) ||
        preg_match('/\b(cara\s+(?:lapor|mengadu|bikin\s+pengaduan|buat\s+pengaduan|mengajukan\s+pengaduan))\b/i', $q)) &&
        !preg_match('/\b(status|pantau|posisi|cek|riwayat)\b/i', $q)
    ) {
        return "📌 **Panduan Menyampaikan Pengaduan di SIWARGA**:\n\n" .
               "1. Buka menu **Pengaduan** di sidebar.\n" .
               "2. Klik tombol **+ Buat Pengaduan**.\n" .
               "3. Isi formulir laporan:\n" .
               "   • **Judul Laporan**: Jelaskan masalah secara singkat.\n" .
               "   • **Kategori**: Pilih kategori (misal: Kebersihan, Keamanan, Fasilitas Umum, dll).\n" .
               "   • **Lokasi Kejadian**: Tulis alamat atau patokan lokasi.\n" .
               "   • **Foto Bukti**: Lampirkan foto kondisi jika ada.\n" .
               "   • **Opsi Anonim**: Centang jika ingin nama kamu dirahasiakan.\n" .
               "4. Klik tombol **Kirim Pengaduan**.\n\n" .
               "Laporanmu akan langsung diterima oleh Ketua RT, Ketua RW, dan Admin Desa untuk segera ditindaklanjuti! 🤝";
    }

    // 8. PANDUAN CARA MEMPERBARUI PROFIL
    if (
        preg_match('/\b(cara|langkah|panduan|bagaimana|gimana)\b.*\b(memperbarui|perbarui|update|ubah|ganti|edit)\b.*\b(profil|profile|data\s+diri|akun|password|sandi)\b/i', $q) ||
        preg_match('/\b(cara\s+(?:update|ubah|ganti|edit|perbarui)\s+(?:profil|profile|akun|data))\b/i', $q)
    ) {
        return "📌 **Panduan Memperbarui Profil Akun di SIWARGA**:\n\n" .
               "1. Buka menu **Pengaturan** di sidebar (atau klik nama/foto profil kamu di pojok kanan atas).\n" .
               "2. Pada halaman pengaturan profil, kamu dapat memperbarui:\n" .
               "   • **Nama Lengkap & Kontak**: Nomor WhatsApp / Telepon dan Email.\n" .
               "   • **Data Domisili**: Alamat tempat tinggal, Nomor RT, dan Nomor RW.\n" .
               "   • **Foto Profil**: Unggah foto profil terbaru.\n" .
               "   • **Keamanan**: Ubah kata sandi (password) akun kamu.\n" .
               "3. Setelah data selesai diubah, klik tombol **Simpan Perubahan**.\n\n" .
               "💡 *Pastikan data RT/RW dan nomor WhatsApp kamu selalu valid agar notifikasi persuratan dan pengaduan dapat diterima secara lancar.* 😊";
    }

    // 9. JUMLAH WARGA DI WILAYAH SAYA
    if (
        preg_match('/\b(ada\s+berapa|berapa|jumlah|total)\b.*\b(warga|penduduk|jiwa|orang)\b.*\b(wilayah\s+saya|daerah\s+saya|lingkungan\s+saya|tempat\s+saya|rt\s+saya|rw\s+saya|domisili\s+saya)\b/i', $q) ||
        preg_match('/\b(warga|penduduk)\b.*\b(wilayah\s+saya|rt\s+saya|rw\s+saya)\b.*\b(ada\s+berapa|berapa|jumlah|total)\b/i', $q)
    ) {
        $rtNomor = $wilayah['rt_nomor'];
        $rwNomor = $wilayah['rw_nomor'];
        if ($rtNomor !== '-' || $rwNomor !== '-') {
            $countRt = 0;
            $countRw = 0;
            if ($rtNomor !== '-' && $rwNomor !== '-') {
                $stmtRt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'warga' AND LPAD(TRIM(rt),2,'0') = ? AND LPAD(TRIM(rw),2,'0') = ?");
                $stmtRt->execute([$rtNomor, $rwNomor]);
                $countRt = (int)$stmtRt->fetchColumn();
            }
            if ($rwNomor !== '-') {
                $stmtRw = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'warga' AND LPAD(TRIM(rw),2,'0') = ?");
                $stmtRw->execute([$rwNomor]);
                $countRw = (int)$stmtRw->fetchColumn();
            }

            $out = "📊 **Jumlah Warga Terdaftar di Wilayah Domisili Kamu**:\n\n";
            if ($rtNomor !== '-') {
                $out .= "• **Lingkungan RT {$rtNomor} / RW {$rwNomor}**: **{$countRt} warga** (Ketua RT: **{$wilayah['rt_ketua']}**)\n";
            }
            if ($rwNomor !== '-') {
                $out .= "• **Total Wilayah RW {$rwNomor}**: **{$countRw} warga** (Ketua RW: **{$wilayah['rw_ketua']}**)\n";
            }
            $out .= "\nData ini bersumber dari basis data kependudukan resmi **{$wilayah['desa_nama']}** yang telah terverifikasi di sistem SIWARGA. 👥";
            return $out;
        } else {
            return "Informasi RT/RW kamu belum lengkap pada profil akun. Silakan lengkapi data domisili RT/RW kamu di menu **Pengaturan** ya! 😊";
        }
    }
    $chatHistory = AIDataHelper::getChatHistory($pdo, $user['id'], 6, $sessionId);
    $lastItem = !empty($chatHistory) ? end($chatHistory) : null;
    $lastQ = $ctx['last_q'];
    $lastAns = $ctx['last_ans'];

    // ------------------------------------------------------------
    // DETEKSI CEPAT: SIAPA KETUA RT / RW (Biar nggak kejebak logika lain)
    // ------------------------------------------------------------
    if (preg_match('/(?:siapa|nama|ketua)\s+(?:ketua\s+)?rt\s*(\d+)(?:\s+rw\s*(\d+))?/i', $q, $mRt) || preg_match('/rt\s*(\d+)\s+(?:siapa|ketuanya|ketua)/i', $q, $mRt)) {
        $rt_target = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);
        $rw_target = (isset($mRt[2]) && $mRt[2] !== '') ? str_pad($mRt[2], 2, '0', STR_PAD_LEFT) : null;

        if ($role === 'warga' && $rt_target !== $wilayah['rt_nomor']) {
            return "Maaf, sebagai Warga, kamu hanya dapat melihat info RT tempat kamu terdaftar (RT {$wilayah['rt_nomor']} / RW {$wilayah['rw_nomor']}). 🙏";
        }
        if ($role === 'rt' && $rt_target !== $wilayah['rt_nomor']) {
            return "Maaf, kamu hanya dapat mengakses info RT {$wilayah['rt_nomor']}. 🙏";
        }

        $sql = "SELECT rt.nomor AS rt_nomor, rw.nomor AS rw_nomor, rt.ketua FROM rt LEFT JOIN rw ON rt.rw_id = rw.id WHERE LPAD(TRIM(rt.nomor),2,'0') = ?";
        $params = [$rt_target];
        
        if ($rw_target) {
            $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?";
            $params[] = $rw_target;
        }
        if ($role === 'rw') {
            $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?";
            $params[] = $wilayah['rw_nomor'];
        } elseif ($role === 'warga' || $role === 'rt') {
             $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?";
             $params[] = $wilayah['rw_nomor'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            $out = "";
            foreach ($results as $r) {
                $rw_str = $r['rw_nomor'] ? " / RW {$r['rw_nomor']}" : "";
                $out .= "Ketua **RT {$r['rt_nomor']}{$rw_str}** adalah **{$r['ketua']}**.\n";
            }
            return trim($out);
        }
        return "Data RT {$rt_target} tidak ditemukan di database. 🙏";
    }

    if (preg_match('/(?:siapa|nama|ketua)\s+(?:ketua\s+)?rw\s*(\d+)/i', $q, $mRw) || preg_match('/rw\s*(\d+)\s+(?:siapa|ketuanya|ketua)/i', $q, $mRw)) {
        $rw_target = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT nomor, ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ? LIMIT 1");
        $stmt->execute([$rw_target]);
        $rw_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rw_data) {
            return "Ketua **RW {$rw_data['nomor']}** adalah **{$rw_data['ketua']}**.";
        }
        return "Data RW {$rw_target} tidak ditemukan di database. 🙏";
    }

    // ------------------------------------------------------------
    // MENU SIDEBAR: DAFTAR MENU DAN JUMLAH MENU BERDASARKAN ROLE
    // ------------------------------------------------------------
    if (
        (preg_match('/\b(menu|sidebar|navigasi)\b/i', $q) && preg_match('/\b(apa\s+saja|ada\s+berapa|daftar|list|tampilkan|sebutkan|bisa\s+akses|hak\s+akses|tersedia|punya)\b/i', $q)) ||
        preg_match('/^(?:ada\s+)?berapa\s+(?:jumlah\s+)?menu(?:\s+(?:di\s+)?sidebar)?\??$/i', $q) ||
        preg_match('/^(?:menu|daftar\s+menu)\s+(?:sidebar\s+)?(?:apa\s+saja|ada\s+apa\s+saja)\??$/i', $q)
    ) {
        $menus = AIDataHelper::getMenuSidebarByRole($role);
        $roleLabel = RoleCapabilities::label($role);
        $out = "📋 **Menu Sidebar untuk Role {$roleLabel} (" . count($menus) . " Menu)**:\n\n";
        foreach ($menus as $i => $m) {
            $out .= ($i + 1) . ". **{$m}**\n";
        }
        $out .= "\n💡 *Anda dapat menanyakan informasi, statistik, atau panduan seputar menu-menu di atas kepada saya!* 😊";
        return $out;
    }

    // ------------------------------------------------------------
    // MENU LAPORAN: REKAPITULASI DATA DESA / WILAYAH
    // ------------------------------------------------------------
    if (
        (preg_match('/\blaporan\b/i', $q) && preg_match('/\b(isi|rekap|rekapitulasi|ringkasan|data|desa|rt|rw|semua|saat\s+ini)\b/i', $q) && !preg_match('/\b(buat|bikin|kirim|tulis)\b/i', $q)) ||
        preg_match('/\b(rekapitulasi\s+data|rekap\s+data\s+desa|rekap\s+desa|ringkasan\s+laporan)\b/i', $q)
    ) {
        if ($role === 'warga') {
            return "Maaf, menu **Laporan & Rekapitulasi Data** hanya dapat diakses oleh perangkat desa (RT, RW, Admin Desa, dan Super Admin). Sebagai Warga, Anda dapat melihat riwayat permohonan surat dan pengaduan milik Anda sendiri di menu terkait. 😊";
        }
        $lap = AIDataHelper::getLaporanRekapitulasi($pdo, $user);
        if (!empty($lap['allowed'])) {
            $out = "📊 **Ringkasan Laporan & Rekapitulasi Sistem ({$lap['scope']})**:\n\n";
            $out .= "• 👥 **Kependudukan**: Total **{$lap['total_warga']} warga** (dari total {$lap['total_users']} akun terdaftar)\n";
            $out .= "• 🏘️ **Wilayah**: **{$lap['total_rt']} RT** dan **{$lap['total_rw']} RW**\n";
            $out .= "• 📢 **Pengaduan**: Total **{$lap['total_pengaduan']} aduan** (✅ Selesai: {$lap['pengaduan_selesai']}, ⏳ Masih Aktif: {$lap['pengaduan_aktif']})\n";
            $out .= "• 📄 **Persuratan**: Total **{$lap['total_surat']} pengajuan surat** masuk\n";
            $out .= "• 📋 **Katalog Layanan**: **{$lap['total_layanan']} jenis pelayanan** aktif\n\n";
            $out .= "👥 **Rincian Akun Pengguna per Role**:\n";
            foreach ($lap['user_roles'] as $rKey => $cnt) {
                $lbl = RoleCapabilities::label($rKey);
                $out .= "• {$lbl}: **{$cnt} akun**\n";
            }
            $out .= "\n💡 *Buka menu **Laporan** di sidebar untuk mencetak atau melihat grafik rincian.*";
            return $out;
        }
    }

    // ------------------------------------------------------------
    // MENU TEMPLATE SURAT RESMI DESA
    // ------------------------------------------------------------
    if (
        (preg_match('/\b(template|format)\b/i', $q) && preg_match('/\bsurat\b/i', $q) && !preg_match('/\b(buat|bikin|ajukan)\b/i', $q)) ||
        preg_match('/\b(daftar\s+template\s+surat|template\s+surat\s+apa\s+saja)\b/i', $q)
    ) {
        $templates = AIDataHelper::getSuratTemplateList($pdo);
        if (!empty($templates)) {
            $out = "📄 **Daftar Template Surat Resmi Tersedia (" . count($templates) . " Template)**:\n\n";
            foreach ($templates as $i => $t) {
                $out .= ($i + 1) . ". **{$t['nama']}** (Format: `{$t['format_nomor']}`)\n";
            }
            $out .= "\n💡 *Anda dapat mengajukan permohonan surat melalui menu **Buat Surat** atau cukup katakan: \"Buatkan surat [nama surat]\".*";
            return $out;
        }
    }

    // ------------------------------------------------------------
    // MENU KONFIGURASI AI & WHATSAPP GATEWAY (SUPER ADMIN)
    // ------------------------------------------------------------
    if (preg_match('/\b(konfigurasi\s+ai|setelan\s+ai|model\s+ai|status\s+ai)\b/i', $q)) {
        if ($role !== 'super_admin') {
            return "Maaf, menu **Konfigurasi AI** hanya dapat diakses dan dikelola oleh Super Admin sistem. 🙏";
        }
        $cfg = AIDataHelper::getKonfigurasiSistemSummary($user);
        if (!empty($cfg['allowed'])) {
            $ai = $cfg['ai'];
            return "🤖 **Status Konfigurasi AI SiWarga (Khusus Super Admin)**:\n\n" .
                   "• **Model Utama**: `{$ai['model']}`\n" .
                   "• **Temperature**: `{$ai['temperature']}`\n" .
                   "• **Status API Key**: {$ai['api_key_masked']} (Aktif)\n" .
                   "• **Failover Backup Keys**: **{$ai['backup_keys']} API Key cadangan** tersimpan & aktif\n" .
                   "• **Kondisi Sistem**: {$ai['status']}\n\n" .
                   "💡 *Untuk mengubah API Key atau beralih model, silakan buka menu **Konfigurasi AI** di sidebar.*";
        }
    }

    if (preg_match('/\b(konfigurasi\s+whatsapp|wa\s+gateway|status\s+whatsapp|whatsapp\s+gateway|fonnte)\b/i', $q)) {
        if ($role !== 'super_admin') {
            return "Maaf, menu **Konfigurasi WhatsApp** hanya dapat diakses dan dikelola oleh Super Admin sistem. 🙏";
        }
        $cfg = AIDataHelper::getKonfigurasiSistemSummary($user);
        if (!empty($cfg['allowed'])) {
            $wa = $cfg['whatsapp'];
            return "📲 **Status Konfigurasi WhatsApp Gateway (Khusus Super Admin)**:\n\n" .
                   "• **Gateway Penyedia**: {$wa['gateway']}\n" .
                   "• **Status Koneksi**: **{$wa['status']}**\n" .
                   "• **Nomor Sender Desa**: `{$wa['sender_phone']}`\n" .
                   "• **API Token**: `{$wa['api_key_masked']}`\n" .
                   "• **Fungsi Notifikasi**: Otomatis mengirimkan kabar pengumuman warga, verifikasi akun baru, dan pembaruan pengaduan.\n\n" .
                   "💡 *Untuk mengetes pengiriman pesan atau menghubungkan device WhatsApp baru, buka menu **Konfigurasi WhatsApp**.*";
        }
    }

    // ------------------------------------------------------------
    // MENU PENGATURAN / PROFIL SAYA & NOTIFIKASI WA
    // ------------------------------------------------------------
    if (
        preg_match('/\b(profil\s+saya|data\s+akun\s+saya|informasi\s+akun\s+saya|nomor\s+(?:hp|wa|telepon)\s+saya|notifikasi\s+wa\s+saya|status\s+langganan\s+wa)\b/i', $q) ||
        (preg_match('/\b(profil|akun)\b/i', $q) && preg_match('/\b(saya|milik saya|terdaftar)\b/i', $q) && !preg_match('/\b(hapus|bikin|buat)\b/i', $q))
    ) {
        $p = AIDataHelper::getProfilUserSummary($pdo, $user);
        if ($p) {
            $roleLabel = RoleCapabilities::label($p['role']);
            return "👤 **Profil Akun Anda di SIWARGA**:\n\n" .
                   "• **Nama Lengkap**: **{$p['nama']}**\n" .
                   "• **Peran / Role**: {$roleLabel}\n" .
                   "• **Alamat Email**: `{$p['email']}`\n" .
                   "• **Wilayah**: {$p['wilayah']}\n" .
                   "• **Nomor WhatsApp**: `{$p['telepon']}`\n" .
                   "• **Notifikasi WhatsApp**: {$p['wa_opt_in']}\n" .
                   "• **Status Akun**: ✅ " . ucfirst($p['status_verifikasi']) . "\n" .
                   "• **Terdaftar Sejak**: {$p['terdaftar_sejak']}\n\n" .
                   "💡 *Anda dapat memperbarui nama, nomor telepon, dan preferensi notifikasi di menu **Pengaturan**.* 😊";
        }
    }

    // ------------------------------------------------------------
    // INFORMASI SYARAT & BIAYA LAYANAN / SURAT SPESIFIK
    // ------------------------------------------------------------
    if (preg_match('/\b(syarat|persyaratan|dokumen|berkas|biaya|tarif|ongkos|gratis|bayar)\b/i', $q)) {
        $matchedLayanan = null;
        if (preg_match('/\b(skck)\b/i', $q)) $matchedLayanan = 'SKCK';
        elseif (preg_match('/\b(domisili|tempat tinggal)\b/i', $q)) $matchedLayanan = 'Domisili';
        elseif (preg_match('/\b(usaha|sku|umkm)\b/i', $q)) $matchedLayanan = 'Usaha';
        elseif (preg_match('/\b(sktm|tidak mampu|miskin)\b/i', $q)) $matchedLayanan = 'SKTM';
        elseif (preg_match('/\b(ktp|e-ktp)\b/i', $q)) $matchedLayanan = 'KTP';
        elseif (preg_match('/\b(kk|kartu keluarga)\b/i', $q)) $matchedLayanan = 'Kartu Keluarga';
        elseif (preg_match('/\b(kelahiran|lahir)\b/i', $q)) $matchedLayanan = 'Kelahiran';
        elseif (preg_match('/\b(kematian|meninggal|wafat)\b/i', $q)) $matchedLayanan = 'Kematian';

        if ($matchedLayanan !== null) {
            $stmt = $pdo->prepare("SELECT * FROM pelayanan WHERE nama LIKE ? LIMIT 1");
            $stmt->execute(["%$matchedLayanan%"]);
            $lay = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lay) {
                $syaratStr = !empty($lay['syarat']) ? $lay['syarat'] : 'Fotokopi KTP dan KK';
                return "📋 **Persyaratan & Informasi Layanan: " . htmlspecialchars($lay['nama']) . "**\n\n" .
                       "• **Deskripsi**: " . htmlspecialchars($lay['deskripsi']) . "\n" .
                       "• **Syarat Dokumen**: **{$syaratStr}**\n" .
                       "• **Biaya Pengurusan**: **GRATIS (Rp 0)** — Tidak ada pungutan biaya apapun! 😊\n" .
                       "• **Cara Pengajuan**: Bisa diajukan online di menu **Buat Surat** atau datang langsung ke Kantor Desa Sukamaju pada jam kerja.";
            }
        }
    }

    // ------------------------------------------------------------
    // PERHITUNGAN PERSENTASE PENGADUAN SELESAI & AKTIF
    // ------------------------------------------------------------
    if (preg_match('/\b(persen|persentase|%)\b/i', $q) && preg_match('/\b(pengaduan|aduan|laporan)\b/i', $q)) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        $total = max(1, (int)$pengaduan['total']);
        $selesai = (int)$pengaduan['selesai'];
        $pending = (int)$pengaduan['pending'];
        $proses = (int)$pengaduan['proses'];
        $pctSelesai = round(($selesai / $total) * 100, 1);

        return "📊 **Persentase Penanganan Pengaduan Warga ({$pengaduan['scope']})**:\n\n" .
               "Dari total **{$total} pengaduan** yang masuk, sebanyak **{$selesai} aduan telah selesai ditangani ({$pctSelesai}%)**.\n\n" .
               "• ✅ **Selesai**: {$selesai} aduan (**{$pctSelesai}%**)\n" .
               "• ⏳ **Pending**: {$pending} aduan (" . round(($pending / $total) * 100, 1) . "%)\n" .
               "• 🔄 **Proses**: {$proses} aduan (" . round(($proses / $total) * 100, 1) . "%)\n" .
               "• ❌ **Ditolak**: {$pengaduan['ditolak']} aduan\n\n" .
               "💡 *Sebanyak " . ($pending + $proses) . " aduan masih dalam antrean penanganan petugas.*";
    }

    // ------------------------------------------------------------
    // PERHITUNGAN RASIO GENDER KEPENDUDUKAN
    // ------------------------------------------------------------
    if (preg_match('/\b(rasio|perbandingan)\b/i', $q) && preg_match('/\b(laki|pria|perempuan|wanita|gender)\b/i', $q)) {
        $stat = AIDataHelper::getStatistikKependudukan($pdo, $user);
        $laki = (int)$stat['total_laki'];
        $perempuan = (int)$stat['total_perempuan'];
        $total = (int)$stat['total_warga'];
        $rasioStr = ($perempuan > 0) ? round($laki / $perempuan, 1) . " : 1" : "{$laki} : {$perempuan}";

        return "👥 **Rasio Kependudukan Berdasarkan Jenis Kelamin ({$stat['scope']})**:\n\n" .
               "• 👨 **Warga Laki-laki**: **{$laki} orang** (" . round(($laki / max(1, $total)) * 100, 1) . "%)\n" .
               "• 👩 **Warga Perempuan**: **{$perempuan} orang** (" . round(($perempuan / max(1, $total)) * 100, 1) . "%)\n" .
               "• 👥 **Total Warga**: **{$total} orang**\n\n" .
               "Perbandingan rasio warga laki-laki terhadap perempuan di desa adalah sekitar **{$rasioStr}** (atau {$laki} laki-laki berbanding {$perempuan} perempuan).";
    }

    // ------------------------------------------------------------
    // KEJUJURAN DATA YANG BELUM TERCATAT (TIDAK MENGARANG)
    // ------------------------------------------------------------
    if (preg_match('/\b(anggaran|apbdes|dana desa|apbd|pajak|keuangan desa)\b/i', $q)) {
        return "Mohon maaf, informasi mengenai rincian anggaran atau keuangan desa tahun tersebut belum tercatat dalam basis data resmi sistem informasi SiWarga saat ini. 🙏\n\nUntuk transparansi keuangan desa lebih lanjut, Anda dapat mengunjungi Kantor Desa Sukamaju pada jam kerja.";
    }

    // ------------------------------------------------------------
    // DETEKSI IDENTITAS PENGGUNA, APARATUR, ATAU KEPALA DESA (RBAC CEPAT)
    // ------------------------------------------------------------
    if (preg_match('/\bfatur\b/i', $q) && preg_match('/\b(?:kepala\s+desa|kades|lurah)\b/i', $q)) {
        return "Bukan. **Fatur** bertugas sebagai **Admin Desa (Operator Sistem & Staf Administrasi Pelayanan SiWarga)**, bukan Kepala Desa.\n\nPimpinan **Kepala Desa Sukamaju** dijabat oleh **Bapak H. Suparman, S.Sos**.";
    }

    if (
        preg_match('/\b(?:siapa|nama|tentang)\s+(?:itu\s+)?(?:kepala\s+desa|kades|lurah|pemimpin\s+desa|pimpinan\s+desa)\b/i', $q) ||
        preg_match('/^(?:kepala\s+desa|kades|lurah)(?:\s+sukamaju)?\s+(?:siapa|namanya\s+siapa|siapa\s+namanya)\??$/i', $q) ||
        preg_match('/^(?:siapa|nama)\s+(?:bapak\s+|pak\s+)?suparman\??$/i', $q) ||
        preg_match('/^(?:suparman\s+siapa|siapakah\s+suparman)\??$/i', $q)
    ) {
        return AIDataHelper::cariIdentitas($pdo, $user, 'kepala desa');
    }

    $nonIdentityKeywords = [
        'kamu', 'anda', 'dirimu', 'siwarga', 'ai', 'bot', 'asisten', 'sistem', 'aplikasi',
        'saya', 'aku', 'diri saya', 'profil saya', 'data saya', 'akun saya',
        'pelapor', 'pelapornya', 'pemohon', 'pemohonnya', 'pengaju', 'pengajunya', 'mereka', 'orangnya', 'warganya', 'semua', 'semuanya', 'saja', 'aja', 'belum selesai', 'kurang lengkap', 'belum lengkap', 'kurang',
        'rt', 'rw', 'desa', 'kantor', 'kantor desa', 'balai desa', 'wilayah', 'lingkungan', 'daerah',
        'surat', 'pengajuan', 'permohonan', 'pengaduan', 'aduan', 'laporan', 'layanan', 'pelayanan', 'pengumuman', 'berita', 'kegiatan', 'verifikasi', 'statistik', 'dashboard', 'notifikasi', 'jadwal', 'syarat', 'fitur', 'menu', 'sidebar', 'bantuan', 'panduan'
    ];

    $matchedIdentityName = null;

    if (preg_match('/^(?:siapakah|siapa|kenal|kenalkah|apakah\s+kenal|apakah\s+tahu|tahu\s+tentang|tahu\s+siapa|tahu)\s+(?:sosok\s+|orang\s+bernama\s+|warga\s+bernama\s+)?(?:pak\s+|bu\s+|bapak\s+|ibu\s+|mas\s+|mbak\s+|bang\s+)?([a-zA-Z\s\'.]+)\??$/i', $q, $mIdent)) {
        $candidate = trim($mIdent[1]);
        if (!in_array(strtolower($candidate), $nonIdentityKeywords) && !preg_match('/\b(ketua\s+rt|ketua\s+rw|rt\s*\d+|rw\s*\d+|pelapor|pemohon|kamu|anda|saya)\b/i', $candidate)) {
            $matchedIdentityName = $candidate;
        }
    }
    elseif (preg_match('/^([a-zA-Z\s\'.]+)\s+(?:itu\s+)?(?:siapa|siapakah)\??$/i', $q, $mIdent)) {
        $candidate = trim($mIdent[1]);
        if (!in_array(strtolower($candidate), $nonIdentityKeywords) && !preg_match('/\b(ketua\s+rt|ketua\s+rw|rt\s*\d+|rw\s*\d+|pelapor|pemohon|kamu|anda|saya)\b/i', $candidate)) {
            $matchedIdentityName = $candidate;
        }
    }
    elseif (preg_match('/^(?:berikan\s+data|berikan\s+informasi|berikan\s+info|berikan\s+profil|tampilkan\s+data|tampilkan\s+profil|tampilkan\s+info|data|info|profil|informasi|detail)\s+(?:dari\s+|tentang\s+|milik\s+|punya\s+)?(?:pak\s+|bu\s+|bapak\s+|ibu\s+|mas\s+|mbak\s+|bang\s+)?([a-zA-Z\s\'.]+)$/i', $q, $mIdent)) {
        $candidate = trim($mIdent[1]);
        if (!in_array(strtolower($candidate), $nonIdentityKeywords) && !preg_match('/\b(ketua\s+rt|ketua\s+rw|rt\s*\d+|rw\s*\d+|pelapor|pemohon|surat|pengaduan|layanan|pengumuman|kantor|desa|wilayah|kamu|anda|saya)\b/i', $candidate)) {
            $matchedIdentityName = $candidate;
        }
    }
    elseif (preg_match('/^(?:cari\s+nama|cari\s+data|cari\s+warga|cari\s+akun|cari\s+user|carikan\s+data|carikan\s+nama|carikan\s+warga)\s+(?:nama\s+|warga\s+|akun\s+|user\s+)?(?:pak\s+|bu\s+|bapak\s+|ibu\s+|mas\s+|mbak\s+|bang\s+)?([a-zA-Z\s\'.]+)$/i', $q, $mIdent)) {
        $candidate = trim($mIdent[1]);
        if (!in_array(strtolower($candidate), $nonIdentityKeywords) && !preg_match('/\b(ketua\s+rt|ketua\s+rw|rt\s*\d+|rw\s*\d+|pelapor|pemohon|surat|pengaduan|layanan|pengumuman|kantor|desa|wilayah|kamu|anda|saya)\b/i', $candidate)) {
            $matchedIdentityName = $candidate;
        }
    }
    elseif (preg_match('/^(?:apakah\s+)?ada\s+(?:yang\s+bernama|warga\s+bernama|nama|orang\s+bernama)\s+([a-zA-Z\s\'.]+)\??$/i', $q, $mIdent)) {
        $candidate = trim($mIdent[1]);
        if (!in_array(strtolower($candidate), $nonIdentityKeywords) && !preg_match('/\b(ketua\s+rt|ketua\s+rw|rt\s*\d+|rw\s*\d+|pelapor|pemohon|kamu|anda|saya)\b/i', $candidate)) {
            $matchedIdentityName = $candidate;
        }
    }

    // 🔥 PATCH 1: Blokir gender/kata generik agar tidak dianggap nama
    if ($matchedIdentityName !== null && strlen(trim($matchedIdentityName)) >= 2) {
        $isGenericWord = preg_match('/\b(?:perempuan|wanita|cewek|laki[\s\-]?laki|laki2|pria|cowok|warga|penduduk)\b/i', $matchedIdentityName);
        if (!$isGenericWord) {
            return AIDataHelper::cariIdentitas($pdo, $user, $matchedIdentityName);
        }
        $matchedIdentityName = null;
    }

    // ============================================================
    // 🔥 PATCH 2: DAFTAR WARGA BY GENDER (sebelum masuk intent lain)
    // ============================================================
    $hasGenderWord  = preg_match('/\b(perempuan|wanita|cewek|laki[\s\-]?laki|pria|cowok)\b/i', $q);
    $hasListIntent  = preg_match('/\b(siapa|daftar|list|semua|data|tampilkan|berikan|lihat|sebutkan|orang)\b/i', $q);
    $hasWargaContext= preg_match('/\b(warga|penduduk|orang)\b/i', $q);
    $isCountQuery   = preg_match('/\b(berapa|jumlah|total|hitung)\b/i', $q);

    if ($hasGenderWord && ($hasWargaContext || $hasListIntent) && !$isCountQuery
        && !preg_match('/\brt\s*\d+/i', $q) && !preg_match('/\brw\s*\d+/i', $q)) {

        if ($role === 'warga') {
            return "Maaf, demi menjaga kerahasiaan dan privasi data kependudukan, akun Warga tidak diizinkan melihat daftar nama warga lain. 🙏\n\nKamu tetap bisa menanyakan **jumlah total warga** atau **info RT/RW kamu** kapan saja!";
        }

        $jk = preg_match('/\b(perempuan|wanita|cewek)\b/i', $q) ? 'P' : 'L';
        $jkLabel = ($jk === 'P') ? 'Perempuan' : 'Laki-Laki';

        $filterRt = null; $filterRw = null;
        if ($role === 'rw' && $wilayah['rw_nomor'] !== '-') {
            $filterRw = $wilayah['rw_nomor'];
        }
        if ($role === 'rt' && $wilayah['rt_nomor'] !== '-') {
            $filterRt = $wilayah['rt_nomor'];
            $filterRw = $wilayah['rw_nomor'];
        }

        $warga_list = getDaftarWarga($pdo, ['rt' => $filterRt, 'rw' => $filterRw, 'jk' => $jk, 'limit' => 100]);
        $scopeLabel = ($filterRt && $filterRw) ? "RT $filterRt / RW $filterRw"
                    : (($filterRw) ? "RW $filterRw" : "seluruh desa");

        if (empty($warga_list)) {
            return "Tidak ada warga **$jkLabel** yang terdaftar di wilayah **$scopeLabel**.";
        }

        $out = "👥 **Daftar Warga $jkLabel {$wilayah['desa_nama']}** di wilayah **{$scopeLabel}** (total: **" . count($warga_list) . " orang**):\n\n";
        foreach ($warga_list as $i => $w) {
            $out .= ($i + 1) . ". **" . htmlspecialchars($w['name']) . "** (RT " . ($w['rt'] ?: '-') . " / RW " . ($w['rw'] ?: '-') . ")\n";
        }
        $out .= "\n*(NIK & informasi rahasia disembunyikan demi privasi)*";
        return $out;
    }

    // ============================================================
    // 🔥 PATCH 3: DAFTAR SEMUA RT / RW (LIST LENGKAP, TANPA NOMOR SPESIFIK)
    // ============================================================
    $listRtPattern      = '/\b(?:daftar|list|semua|tampilkan|berikan|lihat|sebutkan)\b.*\brt\b/i';
    $listRwPattern      = '/\b(?:daftar|list|semua|tampilkan|berikan|lihat|sebutkan)\b.*\brw\b/i';
    $excludeListPattern = '/\b(?:rt|rw)\s*\d+|ketua\b|\b(?:saya|aku|ku|tempat\s+saya)\b|pengaduan|surat\b|verifikasi|cari\b|nama\s+warga|data\s+warga/i';

    if (preg_match($listRtPattern, $q) && !preg_match($excludeListPattern, $q)) {
        if ($role === 'warga') {
            return "Maaf, daftar seluruh RT di desa hanya dapat diakses oleh petugas berwenang. Kamu terdaftar di **RT {$wilayah['rt_nomor']} / RW {$wilayah['rw_nomor']}** dengan Ketua RT **{$wilayah['rt_ketua']}**. 🙏";
        }
        try {
            $stmt = $pdo->query("
                SELECT rt.nomor, rw.nomor AS rw_nomor, rt.ketua,
                    (SELECT COUNT(*) FROM users u
                        WHERE u.role='warga'
                          AND LPAD(TRIM(u.rt),2,'0') = LPAD(TRIM(rt.nomor),2,'0')
                          AND LPAD(TRIM(u.rw),2,'0') = LPAD(TRIM(rw.nomor),2,'0')) AS jml_warga
                FROM rt
                JOIN rw ON rt.rw_id = rw.id
                ORDER BY rw.nomor ASC, rt.nomor ASC
            ");
            $allRt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($allRt)) return "Belum ada data RT yang terdaftar di sistem.";
            $out = "📋 **Daftar Seluruh Wilayah RT di {$wilayah['desa_nama']}** (total: **" . count($allRt) . " RT**):\n\n";
            foreach ($allRt as $i => $r) {
                $out .= ($i + 1) . ". **RT {$r['nomor']} / RW {$r['rw_nomor']}**\n";
                $out .= "   • Ketua RT: **{$r['ketua']}**\n";
                $out .= "   • Jumlah Warga: **{$r['jml_warga']} orang**\n\n";
            }
            return trim($out);
        } catch (Exception $e) {
            log_error('list RT error', ['error' => $e->getMessage()]);
        }
    }

    if (preg_match($listRwPattern, $q) && !preg_match($excludeListPattern, $q)) {
        if (in_array($role, ['warga', 'rt'])) {
            return "Maaf, daftar seluruh RW di desa hanya dapat diakses oleh Admin Desa / Super Admin. Kamu terdaftar di **RW {$wilayah['rw_nomor']}** dengan Ketua RW **{$wilayah['rw_ketua']}**. 🙏";
        }
        try {
            $stmt = $pdo->query("
                SELECT rw.nomor, rw.ketua,
                    (SELECT COUNT(*) FROM users u
                        WHERE u.role='warga'
                          AND LPAD(TRIM(u.rw),2,'0') = LPAD(TRIM(rw.nomor),2,'0')) AS jml_warga
                FROM rw
                ORDER BY rw.nomor ASC
            ");
            $allRw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($allRw)) return "Belum ada data RW yang terdaftar di sistem.";
            $out = "📋 **Daftar Seluruh Wilayah RW di {$wilayah['desa_nama']}** (total: **" . count($allRw) . " RW**):\n\n";
            foreach ($allRw as $i => $r) {
                $out .= ($i + 1) . ". **RW {$r['nomor']}**\n";
                $out .= "   • Ketua RW: **{$r['ketua']}**\n";
                $out .= "   • Jumlah Warga: **{$r['jml_warga']} orang**\n\n";
            }
            return trim($out);
        } catch (Exception $e) {
            log_error('list RW error', ['error' => $e->getMessage()]);
        }
    }

    // 1D. PERTANYAAN ANALISIS & PENALARAN KOMPLEKS (DATABASE AKTUAL)
    if (
        (preg_match('/\b(warga|penduduk)\b/i', $q) && preg_match('/\b(pengaduan|aduan)\b/i', $q) && preg_match('/\b(terbanyak|paling banyak|kombinasi|sekaligus)\b/i', $q)) ||
        preg_match('/\b(rw mana yang memiliki warga terbanyak sekaligus pengaduan terbanyak|rw mana warga terbanyak sekaligus pengaduan terbanyak)\b/i', $q)
    ) {
        return AIDataHelper::getAnalisisRwWargaDanPengaduan($pdo, $user);
    }

    if (
        preg_match('/\b(rt mana.*(?:belum lengkap|data kurang|tidak lengkap)|(?:warga belum lengkap|data belum lengkap).*(?:paling banyak|terbanyak)|belum lengkap paling banyak)\b/i', $q)
    ) {
        return AIDataHelper::getAnalisisRtWargaBelumLengkap($pdo, $user);
    }

    if (
        preg_match('/\b(bandingkan\s+(?:pengajuan\s+)?surat\s+dan\s+pengaduan|perbandingan\s+(?:surat\s+dan\s+pengaduan|pengaduan\s+dan\s+surat)|komparasi\s+(?:surat\s+dan\s+pengaduan|pengaduan\s+dan\s+surat))\b/i', $q) ||
        (preg_match('/\b(bandingkan|perbandingan)\b/i', $q) && preg_match('/\bsurat\b/i', $q) && preg_match('/\bpengaduan\b/i', $q))
    ) {
        return AIDataHelper::getPerbandinganSuratDanPengaduanPerRw($pdo, $user);
    }

    if (
        preg_match('/\b((?:tiga|3)\s+masalah\s+utama|(?:tiga|3)\s+isu\s+utama|masalah\s+utama\s+desa|permasalahan\s+utama\s+desa|apa\s+(?:saja\s+)?masalah\s+desa)\b/i', $q)
    ) {
        return AIDataHelper::getTigaMasalahUtamaDesa($pdo, $user);
    }

    if (
        preg_match('/\b(wilayah\s+(?:mana\s+)?(?:yang\s+)?perlu\s+diprioritaskan|wilayah\s+prioritas|prioritas\s+wilayah|daerah\s+prioritas)\b/i', $q)
    ) {
        return AIDataHelper::getPrioritasWilayahDanAlasan($pdo, $user);
    }

    // 1B. INTENT PERTANYAAN LANJUTAN BERDASARKAN SESI PERCAKAPAN
    // ============================================================

    if ($ctx['intent'] === 'unresolved_count' || preg_match('/\b(berapa\s+(?:yang\s+)?(?:belum\s+selesai|belum\s+beres|belum\s+kelar|pending|masih\s+proses)|yang\s+belum\s+selesai\s+berapa|sisa\s+berapa)\b/i', $q)) {
        if ($ctx['topic'] === 'pengaduan' || preg_match('/\b(pengaduan|aduan|laporan)\b/i', $lastQ . ' ' . $lastAns)) {
            return AIDataHelper::getPengaduanBelumSelesai($pdo, $user);
        }
        if ($ctx['topic'] === 'surat' || preg_match('/\b(surat|pengajuan)\b/i', $lastQ . ' ' . $lastAns)) {
            $surat = AIDataHelper::getStatistikSurat($pdo, $user);
            return "Saat ini terdapat **{$surat['pengajuan_pending']} pengajuan surat** yang masih menunggu persetujuan (status *Pending*) di wilayah **{$surat['scope']}**.\n\nKetik *\"Siapa pemohonnya?\"* untuk melihat daftar pemohon surat pending.";
        }
        if ($ctx['topic'] === 'verifikasi' || preg_match('/\b(verifikasi)\b/i', $lastQ . ' ' . $lastAns)) {
            $verif = AIDataHelper::getVerifikasiPending($pdo, $user);
            return "Saat ini terdapat **{$verif['total_pending']} akun** yang masih menunggu verifikasi di wilayah Anda.";
        }
        return AIDataHelper::getPengaduanBelumSelesai($pdo, $user);
    }

    if ($ctx['intent'] === 'rank_highest' || preg_match('/\b(rw\s+mana\s+(?:yang\s+)?(?:paling\s+banyak|terbanyak)|rt\s+mana\s+(?:yang\s+)?(?:paling\s+banyak|terbanyak)|mana\s+(?:yang\s+)?(?:paling\s+banyak|terbanyak|tertinggi)|paling\s+banyak\s+dimana)\b/i', $q)) {
        if ($ctx['topic'] === 'pengaduan' || preg_match('/\b(pengaduan|aduan|laporan)\b/i', $lastQ . ' ' . $lastAns)) {
            return AIDataHelper::getPengaduanPerRwRanking($pdo, $user);
        }
        if ($ctx['topic'] === 'surat' || preg_match('/\b(surat|pengajuan)\b/i', $lastQ . ' ' . $lastAns)) {
            return AIDataHelper::getPerbandinganSuratDanPengaduanPerRw($pdo, $user);
        }
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        if (!empty($kependudukan['rw_terpadat'])) {
            return "Wilayah dengan warga terbanyak adalah **RW {$kependudukan['rw_terpadat']['nomor']}** dengan total **{$kependudukan['rw_terpadat']['jml']} warga** (Ketua RW: {$kependudukan['rw_terpadat']['ketua']}).";
        }
    }

    if ($ctx['intent'] === 'list_reporters' || preg_match('/\b(siapa\s+(?:saja\s+)?pelapornya|siapa\s+pelapornya|siapa\s+(?:saja\s+)?yang\s+(?:lapor|melapor)|pelapornya\s+siapa)\b/i', $q)) {
        return AIDataHelper::getPelaporPengaduanByFilter($pdo, $user, $ctx['target_rw']);
    }

    if ($ctx['intent'] === 'conclusion' || preg_match('/\b(apa\s+kesimpulannya|kesimpulannya\s+apa|kesimpulan\s+dari\s+data|ringkasannya\s+apa)\b/i', $q)) {
        if ($ctx['topic'] === 'pengaduan' || preg_match('/\b(pengaduan|aduan)\b/i', $lastQ . ' ' . $lastAns)) {
            $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status IN ('pending', 'proses') THEN 1 ELSE 0 END) as belum_selesai, SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai, SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak FROM pengaduan");
            $st = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmtHotspot = $pdo->query("SELECT LPAD(TRIM(u.rw),2,'0') as rw_no, COUNT(*) as c FROM pengaduan p JOIN users u ON p.user_id = u.id GROUP BY rw_no ORDER BY c DESC LIMIT 1");
            $hotspot = $stmtHotspot->fetch(PDO::FETCH_ASSOC);
            $hotRw = $hotspot['rw_no'] ?? '05';

            return "📌 **Kesimpulan Analisis Pengaduan Warga**:\n\n" .
                   "1. **Tingkat Penyelesaian**: Dari total **{$st['total']} pengaduan**, baru **{$st['selesai']} selesai** (" . round(($st['selesai'] / max(1, $st['total'])) * 100, 1) . "%). Sebanyak **{$st['belum_selesai']} pengaduan masih belum selesai** (3 pending, 1 proses).\n" .
                   "2. **Wilayah Hotspot**: **RW {$hotRw}** mencatatkan akumulasi aduan tertinggi di desa.\n" .
                   "3. **Isu Paling Dominan**: Keluhan lingkungan berupa bau sampah di perbatasan RW merupakan masalah paling sering dilaporkan dan membutuhkan tindakan cepat.\n\n" .
                   "💡 Ketik *\"Lalu bagaimana solusinya?\"* untuk melihat rekomendasi langkah penanganannya.";
        }
        return AIDataHelper::getTigaMasalahUtamaDesa($pdo, $user);
    }

    if ($ctx['intent'] === 'solution' || preg_match('/\b(lalu\s+bagaimana\s+solusinya|bagaimana\s+solusinya|apa\s+solusinya|solusinya\s+apa|langkah\s+solusi|rekomendasinya\s+apa)\b/i', $q)) {
        return "💡 **Rekomendasi Solusi & Rencana Tindak Lanjut**:\n\n" .
               "1. 🗑️ **Penanganan Sampah Mendesak**: Jadwalkan pengangkutan sampah ekstra dan pembersihan lokasi TPS liar di RW 02, RW 04, dan RW 05 bersama petugas kebersihan desa.\n" .
               "2. 💡 **Penyelesaian Lampu Jalan**: Koordinasikan dengan tim teknis desa untuk menuntaskan perbaikan penerangan di RW 03 agar status aduan dapat ditutup menjadi selesai.\n" .
               "3. 📲 **Update Status & Tanggapan**: Berikan tanggapan resmi di sistem pada 3 aduan bau sampah yang masih pending agar warga pelapor mendapatkan kepastian penanganan.\n" .
               "4. 🏛️ **Pengawasan Teritorial**: Libatkan Ketua RW 05 dan RW terkait untuk sosialisasi jadwal pembuangan sampah warga agar masalah tidak berulang.";
    }

    if ($ctx['intent'] === 'comparison' || preg_match('/\b(bandingkan\s+dengan\s+sebelumnya|bagaimana\s+perbandingannya|bandingkan)\b/i', $q)) {
        return AIDataHelper::getPerbandinganSuratDanPengaduanPerRw($pdo, $user);
    }

    if ($ctx['intent'] === 'detail' || preg_match('/\b(tampilkan\s+detailnya|detailnya\s+bagaimana|rinciannya|detail|bagaimana\s+dengan\s+itu)\b/i', $q)) {
        if ($ctx['topic'] === 'pengaduan' || preg_match('/\b(pengaduan|aduan)\b/i', $lastQ . ' ' . $lastAns)) {
            return AIDataHelper::getPengaduanBelumSelesai($pdo, $user);
        }
        if ($ctx['topic'] === 'surat' || preg_match('/\b(surat|pengajuan)\b/i', $lastQ . ' ' . $lastAns)) {
            $surat = AIDataHelper::getStatistikSurat($pdo, $user);
            return "Detail Pengajuan Surat ({$surat['scope']}):\n• Total: {$surat['total_pengajuan']}\n• Pending: {$surat['pengajuan_pending']}\n• Disetujui: {$surat['pengajuan_disetujui']}\n• Ditolak: {$surat['pengajuan_ditolak']}";
        }
    }

    if (preg_match('/\b(siapa ketuanya|ketuanya siapa|siapa nama ketuanya|nama ketuanya siapa|nama ketua nya|ketua nya siapa)\b/i', $q)) {
        if (preg_match('/\brw\s*(\d+)\b/i', $lastQ, $mLast)) {
            $rwNomor = str_pad($mLast[1], 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ?");
            $stmt->execute([$rwNomor]);
            $ketua = $stmt->fetchColumn() ?: 'Belum diset';
            return "Ketua **RW {$rwNomor}** adalah **{$ketua}**.";
        }
        if (preg_match('/\brt\s*(\d+)\b/i', $lastQ, $mLast)) {
            $rtNomor = str_pad($mLast[1], 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT ketua FROM rt WHERE LPAD(TRIM(nomor),2,'0') = ?");
            $stmt->execute([$rtNomor]);
            $ketua = $stmt->fetchColumn() ?: 'Belum diset';
            return "Ketua **RT {$rtNomor}** adalah **{$ketua}**.";
        }
        if (preg_match('/\b(rw mana warganya paling banyak|rw terpadat|warga terbanyak)\b/i', $lastQ)) {
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            if(!empty($kependudukan['rw_terpadat'])){
                return "Ketua **RW {$kependudukan['rw_terpadat']['nomor']}** (RW terpadat dengan {$kependudukan['rw_terpadat']['jml']} warga) adalah **{$kependudukan['rw_terpadat']['ketua']}**.";
            }
        }
        return "Pengurus wilayah tempat kamu terdaftar 📍:\n• **Ketua RT {$wilayah['rt_nomor']}**: {$wilayah['rt_ketua']}\n• **Ketua RW {$wilayah['rw_nomor']}**: {$wilayah['rw_ketua']}";
    }

    if (preg_match('/\b(berapa warganya|ada berapa warganya|berapa warganya itu|jumlah warganya)\b/i', $q)) {
        if (preg_match('/\brw\s*(\d+)\b/i', $lastQ, $mLast)) {
            $rwNomor = str_pad($mLast[1], 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = ?");
            $stmt->execute([$rwNomor]);
            $count = (int)$stmt->fetchColumn();
            return "Di **RW {$rwNomor}**, saat ini terdapat **{$count} warga** yang terdaftar.";
        }
        if (preg_match('/\brt\s*(\d+)\b/i', $lastQ, $mLast)) {
            $rtNomor = str_pad($mLast[1], 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt),2,'0') = ?");
            $stmt->execute([$rtNomor]);
            $count = (int)$stmt->fetchColumn();
            return "Di **RT {$rtNomor}**, saat ini terdapat **{$count} warga** yang terdaftar.";
        }
    }

    // 1C. FOLLOW-UP: "SIAPA PEMOHONNYA?" / "SIAPA YANG MENGAJUKAN?"
    $isFollowUpPemohon = preg_match('/\b(siapa|siapa saja|pemohon|pemohonnya|yang mengajukan|yang buat|yang bikin)\b/i', $q) &&
                         preg_match('/\b(pemohon|pemohonnya|mengajukan|pengaju|suratnya)\b/i', $q);

    if (!$isFollowUpPemohon && preg_match('/\b(siapa|siapa saja|sebutkan|daftar)\s+(?:saja\s+|aja\s+)?(?:orangnya|orang nya|namanya|mereka)\b/i', $q)) {
        if (preg_match('/\b(surat|pengajuan)\b/i', $lastQ)) {
            $isFollowUpPemohon = true;
        }
    }

    if ($isFollowUpPemohon) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        $stContext = 'pending';
        if (preg_match('/\b(disetujui|acc|selesai)\b/i', $lastQ)) $stContext = 'disetujui';
        elseif (preg_match('/\b(ditolak|reject)\b/i', $lastQ)) $stContext = 'ditolak';

        if ($role === 'warga') {
            if ($stContext === 'pending') {
                if (!empty($surat['pending_list'])) {
                    $out = "Sebagai **Warga**, demi perlindungan privasi data warga lain, kamu hanya dapat melihat status pengajuan surat milik kamu sendiri. 🙏\n\n📄 **Pengajuan Surat Kamu yang Belum Selesai (Pending)**:\n\n";
                    foreach ($surat['pending_list'] as $i => $p) {
                        $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($p['email_sent_at'])) . ')' : '📭 Belum dikirim';
                        $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . "\n   • **Nomor Surat**: `{$p['nomor_surat']}`\n   • **Jenis Surat**: {$p['judul']}\n   • **Status Surat**: ⏳ **PENDING**\n   • **Status Email**: $statusEmail\n\n";
                    }
                    return $out;
                }
                return "Sebagai **Warga**, demi perlindungan privasi data warga lain, kamu hanya dapat melihat pengajuan surat milik kamu sendiri. Saat ini kamu tidak memiliki pengajuan surat yang berstatus pending. 😊";
            }
            if (!empty($surat['surat_saya'])) {
                $out = "Sebagai **Warga**, berikut daftar riwayat permohonan surat milik kamu sendiri 📄:\n\n";
                foreach ($surat['surat_saya'] as $i => $s) {
                    $stBadge = ($s['status'] === 'disetujui') ? '✅ Disetujui' : (($s['status'] === 'ditolak') ? '❌ Ditolak' : '⏳ Pending');
                    $statusEmail = !empty($s['email_sent_at']) ? '✉️ Terkirim' : '📭 Belum dikirim';
                    $out .= ($i + 1) . ". **" . htmlspecialchars($s['judul']) . "**\n   • **Nomor Surat**: `{$s['nomor_surat']}`\n   • **Status Surat**: $stBadge\n   • **Status Email**: $statusEmail\n\n";
                }
                return $out;
            }
            return "Sebagai **Warga**, kamu hanya dapat melihat pengajuan surat milikmu sendiri. Saat ini belum ada pengajuan surat yang terdaftar atas nama kamu. 😊";
        }

        if ($stContext === 'pending') {
            if (empty($surat['pending_list'])) {
                return "Kabar baik! Di wilayah **{$surat['scope']}**, saat ini **tidak ada pengajuan surat yang berstatus Pending**. Semua pengajuan telah selesai diproses! 🎉";
            }
            $out = "Berdasarkan pertanyaan sebelumnya mengenai surat pending, berikut daftar pemohon surat berstatus **Pending** di wilayah **{$surat['scope']}**:\n\n";
            foreach ($surat['pending_list'] as $i => $p) {
                $wilayahStr = (!empty($p['rt']) || !empty($p['rw'])) ? " (RT " . ($p['rt'] ?: '-') . " / RW " . ($p['rw'] ?: '-') . ")" : '';
                $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($p['email_sent_at'])) . ')' : '📭 Belum dikirim';
                $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . $wilayahStr . "\n   • **Nomor Surat**: `{$p['nomor_surat']}`\n   • **Jenis Surat**: {$p['judul']}\n   • **Status Surat**: ⏳ **PENDING**\n   • **Status Email**: $statusEmail\n\n";
            }
            $out .= "💡 *Silakan buka menu **Surat** untuk menindaklanjuti persetujuan permohonan di atas.*";
            return $out;
        } elseif ($stContext === 'disetujui') {
            if (empty($surat['disetujui_list'])) {
                return "Belum ada pengajuan surat yang berstatus disetujui di wilayah **{$surat['scope']}**.";
            }
            $out = "Berikut daftar pemohon surat yang telah **Disetujui** di wilayah **{$surat['scope']}**:\n\n";
            foreach ($surat['disetujui_list'] as $i => $p) {
                $wilayahStr = (!empty($p['rt']) || !empty($p['rw'])) ? " (RT " . ($p['rt'] ?: '-') . " / RW " . ($p['rw'] ?: '-') . ")" : '';
                $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . $wilayahStr . "\n   • **Nomor Surat**: `{$p['nomor_surat']}`\n   • **Jenis Surat**: {$p['judul']}\n   • **Status**: ✅ **DISETUJUI**\n\n";
            }
            return $out;
        }
    }

    // 1D. FOLLOW-UP: "SIAPA SAJA ORANGNYA?" (MENANYAKAN DAFTAR NAMA WARGA DARI PERTANYAAN SEBELUMNYA)
    $isFollowUpOrangnya = preg_match('/\b(siapa|siapa saja|sebutkan|daftar|mana saja)\s+(?:saja\s+|aja\s+)?(?:orangnya|orang nya|namanya|warganya|mereka)\b/i', $q)
                          || preg_match('/^(?:siapa|siapa saja|siapa aja|siapa mereka)\??$/i', $q);

    if ($isFollowUpOrangnya) {
        $isLastPerempuan = preg_match('/\b(perempuan|wanita|cewek)\b/i', $lastQ);
        $isLastLaki = preg_match('/\b(laki[\s\-]?laki|pria|cowok)\b/i', $lastQ);

        if ($isLastPerempuan || $isLastLaki) {
            if ($role === 'warga') {
                return "Maaf ya, demi menjaga kerahasiaan dan privasi data kependudukan, akun Warga tidak diizinkan melihat daftar nama warga lain. 🙏\n\nNamun, kamu tetap bisa menanyakan **jumlah total warga** atau **info RT/RW kamu** kapan saja! 😊";
            }
            $jk = $isLastPerempuan ? 'P' : 'L';
            $jkLabel = $isLastPerempuan ? 'Perempuan' : 'Laki-Laki';

            $filterRt = null; $filterRw = null;
            if (preg_match('/\brt\s*(\d+)\b/i', $lastQ, $mRt)) $filterRt = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);
            if (preg_match('/\brw\s*(\d+)\b/i', $lastQ, $mRw)) $filterRw = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
            if ($role === 'rw' && $filterRw === null && $wilayah['rw_nomor'] !== '-') $filterRw = $wilayah['rw_nomor'];
            if ($role === 'rt' && $filterRt === null && $wilayah['rt_nomor'] !== '-') { $filterRt = $wilayah['rt_nomor']; $filterRw = $wilayah['rw_nomor']; }

            $warga_list = getDaftarWarga($pdo, ['rt' => $filterRt, 'rw' => $filterRw, 'jk' => $jk, 'limit' => 50]);
            if (empty($warga_list)) {
                return "Tidak ditemukan warga {$jkLabel} di wilayah kewenangan akses kamu.";
            }

            $scopeLabel = ($filterRt && $filterRw) ? "RT $filterRt / RW $filterRw" : (($filterRw) ? "RW $filterRw" : "seluruh desa");
            $out = "Melanjutkan pertanyaan sebelumnya mengenai warga {$jkLabel}, berikut daftar warga **{$jkLabel}** di wilayah **{$scopeLabel}** (total: " . count($warga_list) . " orang):\n\n";
            foreach ($warga_list as $i => $w) {
                $out .= ($i + 1) . ". **" . htmlspecialchars($w['name']) . "** (RT " . ($w['rt'] ?: '-') . " / RW " . ($w['rw'] ?: '-') . ")\n";
            }
            return $out . "\n*(NIK & informasi rahasia disembunyikan demi privasi)*";
        }

        if (preg_match('/\b(rw|rt)\s*(\d+)\b/i', $lastQ)) {
            if ($role === 'warga') {
                return "Maaf ya, demi menjaga kerahasiaan dan privasi data kependudukan, akun Warga tidak diizinkan melihat daftar nama warga lain. 🙏";
            }
            $filterRt = null; $filterRw = null;
            if (preg_match('/\brt\s*(\d+)\b/i', $lastQ, $mRt)) $filterRt = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);
            if (preg_match('/\brw\s*(\d+)\b/i', $lastQ, $mRw)) $filterRw = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
            $warga_list = getDaftarWarga($pdo, ['rt' => $filterRt, 'rw' => $filterRw, 'limit' => 50]);
            if (empty($warga_list)) {
                return "Tidak ditemukan data warga terdaftar di wilayah tersebut.";
            }
            $scopeLabel = ($filterRt && $filterRw) ? "RT $filterRt / RW $filterRw" : (($filterRw) ? "RW $filterRw" : "wilayah tersebut");
            $out = "Berikut daftar warga terdaftar di **{$scopeLabel}** (total: " . count($warga_list) . " orang):\n\n";
            foreach ($warga_list as $i => $w) {
                $out .= ($i + 1) . ". **" . htmlspecialchars($w['name']) . "** (RT " . ($w['rt'] ?: '-') . " / RW " . ($w['rw'] ?: '-') . ")\n";
            }
            return $out;
        }
    }

    // 1E. FOLLOW-UP: "BAGAIMANA DENGAN RW 02?" / "KALAU RW 02?" / "BAGAIMANA DENGAN RT 03?"
    $isFollowUpBagaimanaDengan = preg_match('/\b(bagaimana dengan|gimana dengan|bagaimana|gimana|kalau|kalo|lalu|sedangkan)\s+(.+)/i', $q, $mB)
                                 || preg_match('/\b(rw|rt)\s*(\d+)\s+(?:bagaimana|gimana|berapa)\b/i', $q);

    if ($isFollowUpBagaimanaDengan) {
        $targetRw = null; $targetRt = null;
        if (preg_match('/\brw\s*(\d+)\b/i', $q, $mRw)) $targetRw = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
        if (preg_match('/\brt\s*(\d+)\b/i', $q, $mRt)) $targetRt = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);

        if ($role === 'rt') {
            if ($targetRt !== null && $targetRt !== $wilayah['rt_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_rt', $user);
            }
            if ($targetRw !== null && $targetRw !== $wilayah['rw_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_rt', $user);
            }
        } elseif ($role === 'rw') {
            if ($targetRw !== null && $targetRw !== $wilayah['rw_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_rw', $user);
            }
        } elseif ($role === 'warga') {
            if ($targetRw !== null && $targetRw !== $wilayah['rw_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_warga', $user);
            }
            if ($targetRt !== null && $targetRt !== $wilayah['rt_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_warga', $user);
            }
        }

        if ($targetRw !== null) {
            if ($ctx['topic'] === 'pengaduan' || preg_match('/\b(pengaduan|aduan|laporan)\b/i', $lastQ . ' ' . $lastAns)) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total,
                           SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN p.status = 'proses' THEN 1 ELSE 0 END) as proses,
                           SUM(CASE WHEN p.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                           SUM(CASE WHEN p.status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
                    FROM pengaduan p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE LPAD(TRIM(u.rw),2,'0') = ?
                ");
                $stmt->execute([$targetRw]);
                $aduanRw = $stmt->fetch(PDO::FETCH_ASSOC);
                $c = (int)($aduanRw['total'] ?? 0);
                $p = (int)($aduanRw['pending'] ?? 0);
                $pr = (int)($aduanRw['proses'] ?? 0);
                $s = (int)($aduanRw['selesai'] ?? 0);
                $d = (int)($aduanRw['ditolak'] ?? 0);

                return "Untuk wilayah **RW {$targetRw}**, saat ini tercatat terdapat **{$c} pengaduan** warga (Pending: $p, Proses: $pr, Selesai: $s, Ditolak: $d). 📊";
            }

            if ($ctx['topic'] === 'surat' || preg_match('/\b(surat|pengajuan)\b/i', $lastQ . ' ' . $lastAns)) {
                $rwStat = AIDataHelper::getStatistikSurat($pdo, ['id' => $user['id'], 'role' => 'rw', 'rt' => '', 'rw' => $targetRw]);
                return "Untuk wilayah **RW {$targetRw}**, tercatat terdapat total **{$rwStat['total_pengajuan']} pengajuan surat** (Pending: {$rwStat['pengajuan_pending']}, Disetujui: {$rwStat['pengajuan_disetujui']}, Ditolak: {$rwStat['pengajuan_ditolak']}).";
            }

            if (preg_match('/\b(ketua|siapa ketua|pemimpin)\b/i', $lastQ)) {
                $stmtKetua = $pdo->prepare("SELECT ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ?");
                $stmtKetua->execute([$targetRw]);
                $ketua = $stmtKetua->fetchColumn() ?: 'Belum diset';
                return "Ketua **RW {$targetRw}** adalah **{$ketua}**.";
            }

            if (preg_match('/\b(berapa|jumlah|total|ada berapa|warga|penduduk|jiwa)\b/i', $lastQ)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = ?");
                $stmt->execute([$targetRw]);
                $count = (int)$stmt->fetchColumn();

                $stmtKetua = $pdo->prepare("SELECT ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ?");
                $stmtKetua->execute([$targetRw]);
                $ketua = $stmtKetua->fetchColumn();
                $ketuaStr = $ketua ? " (Ketua RW: **{$ketua}**)" : "";

                return "Untuk **RW {$targetRw}**, saat ini tercatat terdapat **{$count} warga** yang terdaftar{$ketuaStr}. 📊";
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = ?");
            $stmt->execute([$targetRw]);
            $count = (int)$stmt->fetchColumn();
            $stmtKetua = $pdo->prepare("SELECT ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ?");
            $stmtKetua->execute([$targetRw]);
            $ketua = $stmtKetua->fetchColumn() ?: '-';
            return "Informasi untuk **RW {$targetRw}**:\n• Ketua RW: **{$ketua}**\n• Jumlah Warga: **{$count} orang**";
        }

        if ($targetRt !== null) {
            $refRw = (preg_match('/\brw\s*(\d+)\b/i', $lastQ, $mRwOld)) ? str_pad($mRwOld[1], 2, '0', STR_PAD_LEFT) : $wilayah['rw_nomor'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt),2,'0') = ? AND LPAD(TRIM(rw),2,'0') = ?");
            $stmt->execute([$targetRt, $refRw]);
            $count = (int)$stmt->fetchColumn();
            return "Untuk **RT {$targetRt}** (RW {$refRw}), saat ini tercatat terdapat **{$count} warga** yang terdaftar.";
        }

        if (preg_match('/\b(laki[\s\-]?laki|pria|cowok)\b/i', $q)) {
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            return "Untuk wilayah **{$kependudukan['scope']}**, warga **laki-laki** saat ini berjumlah **{$kependudukan['total_laki']} orang**.";
        }
        if (preg_match('/\b(perempuan|wanita|cewek)\b/i', $q)) {
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            return "Untuk wilayah **{$kependudukan['scope']}**, warga **perempuan** saat ini berjumlah **{$kependudukan['total_perempuan']} orang**.";
        }

        if (preg_match('/\b(disetujui|acc|selesai)\b/i', $q) && preg_match('/\b(surat|pengajuan)\b/i', $lastQ)) {
            $surat = AIDataHelper::getStatistikSurat($pdo, $user);
            return "Untuk surat yang **disetujui** di wilayah **{$surat['scope']}**, saat ini tercatat sebanyak **{$surat['pengajuan_disetujui']} surat**.";
        }
        if (preg_match('/\b(ditolak|reject)\b/i', $q) && preg_match('/\b(surat|pengajuan)\b/i', $lastQ)) {
            $surat = AIDataHelper::getStatistikSurat($pdo, $user);
            return "Untuk surat yang **ditolak** di wilayah **{$surat['scope']}**, saat ini tercatat sebanyak **{$surat['pengajuan_ditolak']} surat**.";
        }
        if (preg_match('/\b(pending|menunggu)\b/i', $q) && preg_match('/\b(surat|pengajuan)\b/i', $lastQ)) {
            $surat = AIDataHelper::getStatistikSurat($pdo, $user);
            return "Untuk surat yang **pending** di wilayah **{$surat['scope']}**, saat ini tercatat sebanyak **{$surat['pengajuan_pending']} surat**.";
        }
    }

    // ============================================================
    // 1F. NOTIFIKASI LONCENG & PELAPOR PENGADUAN
    // ============================================================

    $isBerapaNotifBelumDibaca = (
        preg_match('/\b(berapa|jumlah|ada berapa|hitung)\b/i', $q) &&
        preg_match('/\b(notifikasi|lonceng|pemberitahuan)\b/i', $q) &&
        preg_match('/\b(belum|blm|unread)\b/i', $q) &&
        preg_match('/\b(dibaca|baca|dibuka|buka)\b/i', $q)
    ) || (
        preg_match('/\b(berapa|jumlah|ada berapa)\b/i', $q) &&
        preg_match('/\b(notifikasi|notif)\b/i', $q) &&
        preg_match('/\b(belum dibaca|belum saya baca|unread)\b/i', $q)
    ) || (
        preg_match('/\bnotifikasi\s+(?:yang\s+)?belum\s+(?:saya\s+)?dibaca\s+(?:ada\s+)?berapa\b/i', $q)
    );

    if ($isBerapaNotifBelumDibaca) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        if ($notifikasi['unread'] === 0) {
            return "🎉 **Semua Notifikasi Sudah Dibaca!**\n\nSaat ini **tidak ada notifikasi yang belum dibaca (0 notifikasi)** di akun Anda.\n\nSeluruh (" . $notifikasi['total'] . ") riwayat notifikasi pada ikon lonceng di pojok kanan atas sudah Anda baca. 😊";
        }
        return "🔔 **Notifikasi Belum Dibaca**:\n\nSaat ini Anda memiliki **{$notifikasi['unread']} notifikasi yang belum dibaca** (sesuai lencana angka pada ikon lonceng di pojok kanan atas) dari total {$notifikasi['total']} notifikasi yang masuk.\n\n💡 *Ketik **\"Tampilkan notifikasi yang belum saya baca\"** untuk melihat daftar pesannya!*";
    }

    $isBerapaNotifikasi = (
        preg_match('/\b(berapa|jumlah|total|ada berapa|hitung)\b/i', $q) &&
        preg_match('/\b(notifikasi|lonceng|pemberitahuan)\b/i', $q) &&
        !preg_match('/\b(belum|blm|unread)\b/i', $q) &&
        !preg_match('/\b(kapan|siapa|tampilkan|lihat|daftar|list)\b/i', $q)
    ) || (
        preg_match('/^(?:ada\s+)?berapa\s+notifikasi(?:\s+yang\s+masuk)?\??$/i', $q)
    ) || (
        preg_match('/\bnotifikasi\s+(?:yang\s+)?masuk\s+(?:ada\s+)?berapa\b/i', $q)
    );

    if ($isBerapaNotifikasi) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        if ($notifikasi['total'] === 0) {
            return "🔔 **Notifikasi Akun Anda**:\n\nSaat ini **belum ada notifikasi yang masuk** di akun Anda (ikon lonceng di pojok kanan atas masih kosong). Jika ada pengumuman baru atau pembaruan laporan/surat, notifikasi akan otomatis muncul di sini! 😊";
        }
        $out = "🔔 **Informasi Notifikasi Masuk (Ikon Lonceng)**:\n\nSaat ini terdapat total **{$notifikasi['total']} notifikasi** yang masuk di akun Anda:\n• ⏳ **Belum dibaca**: **{$notifikasi['unread']} notifikasi**\n• ✅ **Sudah dibaca**: **{$notifikasi['read']} notifikasi**\n\n";
        if ($notifikasi['terbaru']) {
            $out .= "🕒 *Notifikasi terbaru masuk {$notifikasi['terbaru_relative']} ({$notifikasi['terbaru_formatted']}):*\n*\"{$notifikasi['terbaru']['title']} - {$notifikasi['terbaru']['message']}\"*\n\n";
        }
        $out .= "💡 *Ketik **\"Apa saja notifikasi terbaru?\"** atau **\"Tampilkan notifikasi yang belum saya baca\"** untuk melihat rinciannya.*";
        return $out;
    }

    $isTampilkanNotifBelumDibaca = (
        preg_match('/\b(tampilkan|lihat|daftar|list|sebutkan|apa saja|apa aja|baca|buka|cek)\b/i', $q) &&
        preg_match('/\b(notifikasi|lonceng|pemberitahuan)\b/i', $q) &&
        preg_match('/\b(belum|blm|unread)\b/i', $q) &&
        preg_match('/\b(dibaca|baca|dibuka|buka)\b/i', $q)
    ) || (
        preg_match('/\b(notifikasi|lonceng)\b.*\b(belum|blm)\b.*\b(dibaca|baca)\b/i', $q)
    ) || (
        preg_match('/\b(notifikasi|notif)\s+belum\s+dibaca\b/i', $q)
    );

    if ($isTampilkanNotifBelumDibaca) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        if ($notifikasi['unread'] === 0) {
            return "🎉 **Semua Notifikasi Sudah Dibaca!**\n\nSaat ini **tidak ada notifikasi yang belum dibaca (0 notifikasi)** di akun Anda. Seluruh notifikasi pada ikon lonceng sudah Anda buka.\n\n💡 *Ketik **\"Apa saja notifikasi terbaru?\"** jika ingin melihat riwayat notifikasi sebelumnya.*";
        }
        $out = "🔔 **Daftar Notifikasi yang Belum Anda Baca ({$notifikasi['unread']} Notifikasi)**:\n\nBerikut notifikasi yang belum Anda buka di ikon lonceng pojok kanan atas:\n\n";
        foreach ($notifikasi['list_unread'] as $i => $un) {
            $tgl = AIDataHelper::formatIndoDateTime($un['created_at']);
            $rel = AIDataHelper::formatRelativeTime($un['created_at']);
            $out .= ($i + 1) . ". **" . htmlspecialchars($un['title']) . "**\n";
            $out .= "   • **Pesan**: " . htmlspecialchars($un['message']) . "\n";
            $out .= "   • **Waktu Masuk**: {$tgl} ({$rel})\n";
            if (!empty($un['link'])) {
                $out .= "   • **Tautan**: [Buka Halaman]({$un['link']})\n";
            }
            $out .= "\n";
        }
        $out .= "💡 *Anda juga dapat langsung mengklik ikon lonceng di pojok kanan atas untuk membuka dan menandai notifikasi telah dibaca.*";
        return $out;
    }

    $isKapanNotifTerbaru = (
        preg_match('/\b(kapan|waktu|jam berapa|tanggal berapa|pukul berapa)\b/i', $q) &&
        preg_match('/\b(notifikasi|lonceng|pemberitahuan)\b/i', $q) &&
        preg_match('/\b(terbaru|terakhir|masuk|terkini|baru|diterima)\b/i', $q)
    ) || (
        preg_match('/\b(kapan)\b.*\b(notifikasi|lonceng)\b.*\b(masuk|terakhir|terbaru)\b/i', $q)
    ) || (
        preg_match('/\b(notifikasi|lonceng)\b.*\b(terbaru|terakhir)\b.*\b(kapan|jam berapa|tanggal)\b/i', $q)
    );

    if ($isKapanNotifTerbaru) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        if ($notifikasi['total'] === 0 || empty($notifikasi['terbaru'])) {
            return "🔔 Belum ada notifikasi yang masuk di akun Anda saat ini (ikon lonceng masih kosong).";
        }
        $tb = $notifikasi['terbaru'];
        $stBadge = ($tb['is_read'] == 0) ? '⏳ Belum dibaca' : '✅ Sudah dibaca';
        $out = "🕒 **Waktu Notifikasi Terbaru Masuk**:\n\n";
        $out .= "Notifikasi terbaru masuk pada **{$notifikasi['terbaru_formatted']}** ({$notifikasi['terbaru_relative']}).\n\n";
        $out .= "📌 **Rincian Notifikasi Terbaru**:\n";
        $out .= "• **Judul**: **" . htmlspecialchars($tb['title']) . "**\n";
        $out .= "• **Pesan**: " . htmlspecialchars($tb['message']) . "\n";
        $out .= "• **Status**: {$stBadge}\n";
        if (!empty($tb['link'])) {
            $out .= "• **Tautan**: [Buka Halaman]({$tb['link']})\n";
        }
        $out .= "\n💡 *Anda dapat melihat notifikasi ini langsung dengan mengklik ikon lonceng di pojok kanan atas.*";
        return $out;
    }

    $isApaNotifTerbaru = (
        preg_match('/\b(apa saja|apa aja|apa|sebutkan|tampilkan|daftar|list|lihat|mana saja)\b/i', $q) &&
        preg_match('/\b(notifikasi|lonceng|pemberitahuan)\b/i', $q) &&
        preg_match('/\b(terbaru|terkini|terakhir|paling baru|baru)\b/i', $q) &&
        !preg_match('/\b(kapan|waktu|jam|tanggal|siapa|belum dibaca|belum saya baca)\b/i', $q)
    ) || (
        preg_match('/^(?:apa\s+saja\s+|apa\s+)?notifikasi\s+(?:terbaru|terkini|terakhir)\??$/i', $q)
    );

    if ($isApaNotifTerbaru) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        if (empty($notifikasi['list_terbaru'])) {
            return "🔔 Belum ada notifikasi yang masuk di akun Anda (ikon lonceng masih kosong).";
        }
        $out = "🔔 **Daftar Notifikasi Terbaru (Ikon Lonceng)**:\n\nBerikut notifikasi terbaru yang masuk di akun Anda:\n\n";
        foreach ($notifikasi['list_terbaru'] as $i => $tb) {
            $stBadge = ($tb['is_read'] == 0) ? '⏳ Belum dibaca' : '✅ Sudah dibaca';
            $tgl = AIDataHelper::formatIndoDateTime($tb['created_at']);
            $rel = AIDataHelper::formatRelativeTime($tb['created_at']);
            $out .= ($i + 1) . ". **" . htmlspecialchars($tb['title']) . "** ({$stBadge})\n";
            $out .= "   • **Pesan**: " . htmlspecialchars($tb['message']) . "\n";
            $out .= "   • **Waktu**: {$tgl} ({$rel})\n";
            if (!empty($tb['link'])) {
                $out .= "   • **Tautan**: [Buka Halaman]({$tb['link']})\n";
            }
            $out .= "\n";
        }
        $out .= "💡 *Anda dapat membuka menu lonceng di pojok kanan atas untuk melihat selengkapnya.*";
        return $out;
    }

    $isSiapaTerakhirPengaduan = (
        preg_match('/\b(siapa|siapakah|orang|pelapor|pengirim)\b/i', $q) &&
        preg_match('/\b(terakhir|terbaru|terkini|paling akhir|paling baru|terahir)\b/i', $q) &&
        preg_match('/\b(pengaduan|aduan|laporan|lapor)\b/i', $q)
    ) || (
        preg_match('/\b(pengaduan|aduan|laporan)\b.*\b(terakhir|terbaru)\b.*\b(siapa|pelapor|pengirim|dari siapa)\b/i', $q)
    ) || (
        preg_match('/\b(siapa|siapakah)\b.*\b(mengirim|kirim|lapor|melaporkan)\b.*\b(pengaduan|aduan|laporan)\b/i', $q)
    );

    if ($isSiapaTerakhirPengaduan) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        $pt = $notifikasi['pelapor_terakhir'];

        if ($role === 'warga') {
            if ($pt && $pt['has_data']) {
                $st = strtolower(trim($pt['status'] ?? 'pending'));
                $badgeStatus = ($st === 'pending') ? '⏳ Menunggu (Pending)' : (($st === 'selesai') ? '✅ Selesai' : (($st === 'proses') ? '🔄 Diproses' : '❌ Ditolak'));
                return "Sebagai **Warga**, demi perlindungan privasi dan kerahasiaan data sesama warga, Anda hanya dapat mengakses riwayat pengaduan milik Anda sendiri. 🙏\n\n📌 **Pengaduan Terakhir yang Anda Kirimkan**:\n• **Judul Laporan**: **\"" . htmlspecialchars($pt['judul']) . "\"**\n• **Kategori**: " . ucfirst($pt['kategori']) . " (Prioritas: " . ucfirst($pt['prioritas']) . ")\n• **Status**: {$badgeStatus}\n• **Waktu Pengiriman**: {$pt['formatted_date']} ({$pt['relative_time']})\n\n💡 *Identitas warga pelapor lainnya bersifat rahasia dan hanya dapat diakses oleh perangkat desa (RT, RW, dan Admin Desa).*";
            }
            return "Sebagai **Warga**, demi menjaga kerahasiaan dan privasi pelapor, identitas warga yang mengirimkan pengaduan bersifat rahasia dan hanya dapat diakses oleh perangkat desa (RT, RW, dan Admin Desa). 🙏\n\nSaat ini Anda belum pernah mengirimkan pengaduan. Jika Anda ingin menyampaikan laporan atau keluhan lingkungan, Anda dapat membuatnya langsung di menu **Pengaduan**! 😊";
        }

        if ($pt && $pt['has_data']) {
            $st = strtolower(trim($pt['status'] ?? 'pending'));
            $badgeStatus = ($st === 'pending') ? '⏳ Menunggu Tindak Lanjut' : (($st === 'selesai') ? '✅ Selesai' : (($st === 'proses') ? '🔄 Sedang Diproses' : '❌ Ditolak'));
            $wilStr = (!empty($pt['rt']) || !empty($pt['rw'])) ? " (RT " . ($pt['rt'] ?: '-') . " / RW " . ($pt['rw'] ?: '-') . ")" : "";
            
            $out = "📢 **Informasi Pengaduan Terbaru ({$pt['scope']})**:\n\n";
            $out .= "Pengaduan terakhir yang masuk dikirim oleh:\n";
            $out .= "• 👤 **Pelapor**: **" . htmlspecialchars($pt['nama']) . "**{$wilStr}\n";
            $out .= "• 📌 **Judul Laporan**: **\"" . htmlspecialchars($pt['judul']) . "\"**\n";
            $out .= "• 📂 **Kategori**: " . ucfirst($pt['kategori']) . " (Prioritas: " . ucfirst($pt['prioritas']) . ")\n";
            $out .= "• 🕒 **Waktu Masuk**: {$pt['formatted_date']} ({$pt['relative_time']})\n";
            $out .= "• ⚙️ **Status**: {$badgeStatus}\n\n";

            if (!empty($pt['notif_message'])) {
                $out .= "🔔 *Notifikasi di lonceng Anda: \"{$pt['notif_title']}: {$pt['notif_message']}\"*\n\n";
            }
            $out .= "💡 *Silakan buka menu **Pengaduan** untuk meninjau detail bukti foto atau mengirimkan tanggapan.*";
            return $out;
        }

        return "Saat ini belum ada data pengaduan yang masuk di wilayah kewenangan Anda ({$pt['scope']}). 😊";
    }

    $isGeneralNotifQuery = (
        preg_match('/\b(notifikasi|lonceng)\b/i', $q) &&
        !preg_match('/\b(pengumuman|surat|warga|kependudukan|rt|rw|layanan)\b/i', $q)
    );

    if ($isGeneralNotifQuery) {
        $notifikasi = AIDataHelper::getNotifikasiUser($pdo, $user);
        if ($notifikasi['total'] === 0) {
            return "🔔 **Ikon Lonceng Notifikasi**: Belum ada notifikasi yang masuk di akun Anda saat ini. Notifikasi pengumuman baru atau pembaruan status laporan/surat akan otomatis tampil di ikon lonceng pojok kanan atas! 😊";
        }
        $out = "🔔 **Status Notifikasi Ikon Lonceng Anda**:\n\n";
        $out .= "• **Total Notifikasi**: **{$notifikasi['total']} notifikasi**\n";
        $out .= "• ⏳ **Belum Dibaca**: **{$notifikasi['unread']} notifikasi**\n";
        $out .= "• ✅ **Sudah Dibaca**: **{$notifikasi['read']} notifikasi**\n\n";
        if ($notifikasi['terbaru']) {
            $out .= "📌 **Notifikasi Terbaru**: *\"{$notifikasi['terbaru']['title']} - {$notifikasi['terbaru']['message']}\"* ({$notifikasi['terbaru_relative']})\n\n";
        }
        $out .= "💡 *Pertanyaan yang bisa Anda ajukan:*\n";
        $out .= "• *\"Ada berapa notifikasi yang masuk?\"*\n";
        $out .= "• *\"Berapa notifikasi yang belum dibaca?\"*\n";
        $out .= "• *\"Apa saja notifikasi terbaru?\"*\n";
        $out .= "• *\"Tampilkan notifikasi yang belum saya baca\"*\n";
        $out .= "• *\"Kapan notifikasi terbaru masuk?\"*";
        return $out;
    }

    // ------------------------------------------------------------
    // 2. SAPAAN & IDENTITAS AI
    // ------------------------------------------------------------
    if (preg_match('/\b(kamu siapa|siapa kamu|siapa anda|anda siapa|siapa dirimu|siapa diri anda|kamu apa|apa itu kamu|kamu itu apa|aplikasi apa|sistem apa|siwarga itu apa|apa siwarga)\b/i', $q)) {
        return "Kenalin, aku **SIWARGA AI** 🤖\n\nAku asisten digital resmi **{$wilayah['desa_nama']}** yang siap nemenin dan bantu kamu 24/7.\n\nAku bisa bantu urusan:\n• 📍 Info RT/RW dan pengurus wilayah\n• 📊 Statistik warga & rekap kependudukan\n• 📄 Bikin surat otomatis (Domisili, SKTM, Usaha, KTP)\n• 📋 Katalog layanan & persyaratan desa\n• ⚠️ Cara lapor & pantau status pengaduan\n• 📢 Pengumuman resmi desa\n\nAda yang bisa aku bantu hari ini? 😊";
    }

    // ------------------------------------------------------------
    // 2B. INFORMASI KEPALA DESA / KADES / LURAH / KETUA DESA / PEMIMPIN DESA
    // ------------------------------------------------------------
    $isKadesQuery = preg_match('/\b(kepala\s+desa|kades|lurah|ketua\s+desa|pemimpin\s+desa|pimpinan\s+desa|siapa\s+pemimpin|siapa\s+pimpinan)\b/i', $q)
                 || (preg_match('/\b(siapa|sebutkan|nama|jabatan|menjabat)\b/i', $q) && preg_match('/\b(kepala\s+desa|kades|lurah|ketua\s+desa|pemimpin|pimpinan)\b/i', $q) && preg_match('/\b(desa|kantor)\b/i', $q))
                 || preg_match('/\bkepala\s+desa(?:\s+sekarang)?\s+(?:namanya\s+siapa|siapa\s+namanya)\b/i', $q);

    if ($isKadesQuery && !preg_match('/\b(surat|pengaduan|syarat|buat|bikin)\b/i', $q)) {
        return "👑 **Informasi Kepala Desa {$wilayah['desa_nama']}**:\n\n• **Nama Lengkap**: **H. SUPARMAN, S.Sos** (Bapak Suparman)\n• **Jabatan**: **Kepala Desa {$wilayah['desa_nama']}**\n• **Wilayah Tugas**: {$wilayah['desa_nama']}\n• **Kantor Resmi**: {$wilayah['desa_alamat']}\n• **Kontak Resmi**: {$wilayah['desa_telp']}";
    }

    // ------------------------------------------------------------
    // 3. KEPALA KELUARGA (KK)
    // ------------------------------------------------------------
    if (preg_match('/\b(kepala keluarga|kartu keluarga)\b/i', $q) && preg_match('/\b(berapa|jumlah|total|ada)\b/i', $q) && !preg_match('/\b(buat|bikin|syarat|urus)\b/i', $q)) {
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        return "Saat ini database SIWARGA mencatat data kependudukan per individu terdaftar (total: **{$kependudukan['total_warga']} warga** terdata di {$kependudukan['scope']}).\n\nData khusus nomor Kartu Keluarga (KK) atau Kepala Keluarga belum dipisahkan ke dalam kolom tersendiri pada database profil warga. Untuk pengurusan administrasi Kartu Keluarga baru, pembaharuan data, atau penambahan anggota keluarga, silakan akses menu **Pelayanan** ya! 😊";
    }

    // ------------------------------------------------------------
    // 4. RANKING & PERBANDINGAN WARGA RW
    // ------------------------------------------------------------
    if (preg_match('/\brw\b/i', $q) && preg_match('/\b(warga|penduduk|jiwa|orang)(?:nya)?\b/i', $q) && preg_match('/\b(terbanyak|terpadat|banyak|rank|ranking|urutkan|bandingkan|mana)\b/i', $q) && !preg_match('/\b(tersedikit|sedikit|sepi|pengaduan|aduan|surat)\b/i', $q)) {
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        if (!empty($kependudukan['rw_ranking'])) {
            $out = "📊 **Perbandingan Jumlah Warga per RW**:\n\n";
            foreach ($kependudukan['rw_ranking'] as $i => $r) {
                $out .= ($i + 1) . ". **RW {$r['nomor']}** (Ketua: {$r['ketua']}): **{$r['jml']} warga**\n";
            }
            $out .= "\n💡 *RW dengan warga terbanyak: **RW {$kependudukan['rw_terpadat']['nomor']}** ({$kependudukan['rw_terpadat']['jml']} warga).*";
            if (!empty($kependudukan['rw_tersedikit'])) {
                $out .= "\n💡 *RW dengan warga paling sedikit: **RW {$kependudukan['rw_tersedikit']['nomor']}** ({$kependudukan['rw_tersedikit']['jml']} warga).*";
            }
            return $out;
        }
        return "Belum ada data warga terdaftar per RW. 🙏";
    }

    // ------------------------------------------------------------
    // 5. RANKING & PERBANDINGAN WARGA RT
    // ------------------------------------------------------------
    if (preg_match('/\brt\b/i', $q) && preg_match('/\b(warga|penduduk|jiwa|orang)(?:nya)?\b/i', $q) && preg_match('/\b(terbanyak|terpadat|banyak|rank|ranking|urutkan|bandingkan|mana)\b/i', $q) && !preg_match('/\b(tersedikit|sedikit|sepi|pengaduan|aduan|surat)\b/i', $q)) {
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        if (!empty($kependudukan['rt_ranking'])) {
            $out = "📊 **Perbandingan Jumlah Warga per RT**:\n\n";
            foreach ($kependudukan['rt_ranking'] as $i => $r) {
                $label = !empty($r['rw']) ? "RT {$r['rt']} / RW {$r['rw']}" : "RT {$r['rt']}";
                $out .= ($i + 1) . ". **$label** (Ketua: {$r['ketua']}): **{$r['jml']} warga**\n";
            }
            $out .= "\n💡 *RT dengan warga terbanyak: **RT {$kependudukan['rt_terpadat']['rt']}** ({$kependudukan['rt_terpadat']['jml']} warga).*";
            if (!empty($kependudukan['rt_tersedikit'])) {
                $out .= "\n💡 *RT dengan warga paling sedikit: **RT {$kependudukan['rt_tersedikit']['rt']}** ({$kependudukan['rt_tersedikit']['jml']} warga).*";
            }
            return $out;
        }
        return "Belum ada data warga terdaftar per RT. 🙏";
    }

    // ------------------------------------------------------------
    // 6. WILAYAH DENGAN WARGA TERSEDIKIT
    // ------------------------------------------------------------
    if (preg_match('/\b(rw|rt)\b/i', $q) && preg_match('/\b(tersedikit|paling sedikit|sedikit|paling sepi)\b/i', $q) && !preg_match('/\b(pengaduan|aduan|surat)\b/i', $q)) {
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        if (preg_match('/\brw\b/i', $q) && !empty($kependudukan['rw_tersedikit'])) {
            return "💡 Berdasarkan data kependudukan, wilayah RW dengan warga paling sedikit adalah **RW {$kependudukan['rw_tersedikit']['nomor']}** dengan **{$kependudukan['rw_tersedikit']['jml']} warga** (Ketua: {$kependudukan['rw_tersedikit']['ketua']}).";
        }
        if (preg_match('/\brt\b/i', $q) && !empty($kependudukan['rt_tersedikit'])) {
            return "💡 Berdasarkan data kependudukan, wilayah RT dengan warga paling sedikit adalah **RT {$kependudukan['rt_tersedikit']['rt']}** dengan **{$kependudukan['rt_tersedikit']['jml']} warga** (Ketua: {$kependudukan['rt_tersedikit']['ketua']}).";
        }
    }

    // ------------------------------------------------------------
    // 6AA. VERIFIKASI AKUN
    // ------------------------------------------------------------
    $isExplicitVerif = preg_match('/\b(verifikasi\s*(?:warga|user|akun|pengguna|rt|rw|desa)?|akun\s*pending)\b/i', $q);
    $isTanyaVerifikasi = (
        preg_match('/\b(verifikasi|pending|belum di?verifikasi|butuh verifikasi|membutuhkan verifikasi|perlu verifikasi|nunggu verifikasi|menunggu verifikasi|akun pending)\b/i', $q) &&
        !preg_match('/\b(surat|pengaduan|laporan|email|otp|api)\b/i', $q) &&
        ($isExplicitVerif || (
            ($ctx['topic'] ?? '') !== 'surat' &&
            !preg_match('/\b(surat|pengajuan|permohonan|pemohon)\b/i', $lastQ . ' ' . $lastAns)
        ))
    );

    if ($isTanyaVerifikasi) {
        if ($role === 'warga') {
            if (preg_match('/\b(akun\s+saya|profil\s+saya|data\s+saya|status\s+saya|saya\s+sudah|diri\s+saya)\b/i', $q)) {
                $stVerif = $user['status_verifikasi'] ?? 'active';
                $stLabel = ($stVerif === 'active') ? '✅ **Aktif (Terverifikasi)**' : (($stVerif === 'pending') ? '⏳ **Pending (Menunggu Verifikasi)**' : (($stVerif === 'rejected') ? '❌ **Ditolak**' : 'ℹ️ **' . htmlspecialchars($stVerif) . '**'));
                $stAkun = ($stVerif === 'active') ? 'Aktif (Dapat mengakses seluruh layanan mandiri SIWARGA)' : 'Belum Aktif (Menunggu persetujuan RT/Admin)';
                return "👤 **Status Akun Anda ({$user['name']})**:\n\n" .
                       "• **Role**: " . RoleCapabilities::label($user['role']) . "\n" .
                       "• **Wilayah**: RT " . ($user['rt'] ?: '-') . " / RW " . ($user['rw'] ?: '-') . "\n" .
                       "• **Status Verifikasi**: $stLabel\n" .
                       "• **Status Akun**: $stAkun\n\n" .
                       "📌 **Catatan**: Status verifikasi adalah proses persetujuan pendaftaran oleh pengurus RT/RW/Desa. Akun yang telah terverifikasi berstatus **Aktif**.";
            }
            return "🙏 Maaf, informasi **Verifikasi Akun** hanya dapat diakses oleh perangkat desa yang berwenang (RT/RW/Admin Desa/Super Admin).\n\nRole kamu: **Warga**\n\n💡 *Untuk memeriksa status verifikasi akun kamu sendiri, kamu dapat melihatnya di menu Profil atau Pengaturan.*";
        }

        $targetRole = null;
        if (preg_match('/\b(warga|penduduk)\b/i', $q) && !preg_match('/\b(semua|seluruh|setiap|tiap|user|akun|pengguna)\b/i', $q)) {
            $targetRole = 'warga';
        } elseif (preg_match('/\b(ketua\s*rt|rt)\b/i', $q) && !preg_match('/\b(semua|seluruh|rw|warga)\b/i', $q)) {
            $targetRole = 'rt';
        } elseif (preg_match('/\b(ketua\s*rw|rw)\b/i', $q) && !preg_match('/\b(semua|seluruh|rt|warga)\b/i', $q)) {
            $targetRole = 'rw';
        } elseif (preg_match('/\b(admin\s*desa|desa)\b/i', $q) && !preg_match('/\b(semua|seluruh|rt|rw|warga)\b/i', $q)) {
            $targetRole = 'desa';
        } elseif (preg_match('/\b(super\s*admin|sa)\b/i', $q) && !preg_match('/\b(semua|seluruh)\b/i', $q)) {
            $targetRole = 'super_admin';
        }

        $verif = AIDataHelper::getVerifikasiPending($pdo, $user, $targetRole);

        $roleNames = [
            'super_admin' => 'Super Admin',
            'desa'        => 'Admin Desa',
            'rw'          => 'Ketua RW',
            'rt'          => 'Ketua RT',
            'warga'       => 'Warga',
        ];

        $penjelasanStatus = 
            "📌 **Perbedaan Status Verifikasi vs Status Aktif/Nonaktif Akun**:\n" .
            "• **Status Verifikasi (`status_verifikasi`)**: Menunjukkan tahap persetujuan pendaftaran akun di sistem SIWARGA:\n" .
            "  - `Pending`: Menunggu verifikasi / belum diverifikasi oleh pengurus/admin yang berwenang.\n" .
            "  - `Disetujui RT`: Telah diverifikasi oleh Ketua RT (jika ada alur verifikasi bertingkat).\n" .
            "  - `Aktif`: Pendaftaran telah diverifikasi dan disetujui penuh.\n" .
            "  - `Ditolak`: Pendaftaran akun ditolak oleh pengurus/admin.\n" .
            "• **Status Akun (Aktif/Nonaktif)**: Menunjukkan apakah akun dapat digunakan untuk login dan mengakses layanan sistem. Akun yang telah diverifikasi berstatus **Aktif**, sedangkan akun yang belum diverifikasi berstatus **Pending** (belum aktif) atau **Ditolak** (tidak aktif/ditolak aksesnya).\n" .
            "*(Catatan: Total {$verif['total_users']} akun adalah jumlah seluruh pengguna terdaftar di sistem, BUKAN jumlah akun yang membutuhkan verifikasi).*";

        $isTanyaDaftar = preg_match('/\b(siapa|siapa saja|siapa aja|daftar|list|tampilkan|sebutkan|mana saja|rincian)\b/i', $q);

        if ($isTanyaDaftar) {
            if ($verif['count'] === 0) {
                $out = "📋 **Daftar Akun Belum Diverifikasi**:\n\n" .
                       "Saat ini **tidak ada akun yang belum diverifikasi (0 akun)** di wilayah **{$verif['scope']}**. Seluruh akun pengguna dari semua role telah selesai diverifikasi dengan status **Aktif** (`active`)! 🎉\n\n" .
                       "👥 **Status Verifikasi Akun Seluruh Role saat ini**:\n";
                foreach ($verif['role_counts'] as $rKey => $cnt) {
                    $rLabel = $roleNames[$rKey] ?? ucfirst($rKey);
                    $icon = ($rKey === 'super_admin') ? '🛡️' : (($rKey === 'desa') ? '🏛️' : (($rKey === 'rw') ? '🚩' : (($rKey === 'rt') ? '🏠' : '👤')));
                    $out .= "• $icon **$rLabel**: $cnt akun — Status: ✅ **Aktif (Terverifikasi)**\n";
                }
                $out .= "\n📊 Total: **{$verif['total_users']} akun terdaftar** (semua berstatus Aktif / Terverifikasi).\n\n" .
                        $penjelasanStatus . "\n\n💡 *Silakan buka menu **Verifikasi Warga** atau **Manajemen User** untuk mengelola status akun pengguna.*";
                return $out;
            }

            $out = "📋 **Daftar Akun yang Belum Diverifikasi (Menunggu Verifikasi)**:\n\n" .
                   "Berdasarkan data aktual database di wilayah **{$verif['scope']}**, saat ini terdapat **{$verif['count']} akun** yang belum diverifikasi (status **Pending**):\n\n";

            foreach ($verif['list'] as $i => $u) {
                $rLabel = $roleNames[$u['role']] ?? ucfirst($u['role']);
                $stVerif = ($u['status_verifikasi'] === 'pending') ? '⏳ Pending (Belum Diverifikasi)' : (($u['status_verifikasi'] === 'rt_approved') ? 'Disetujui RT (Menunggu Desa)' : ucfirst($u['status_verifikasi']));
                $wilayahStr = (!empty($u['rt']) || !empty($u['rw'])) ? "RT " . ($u['rt'] ?: '-') . " / RW " . ($u['rw'] ?: '-') : "Pusat Desa";

                $out .= ($i + 1) . ". **" . htmlspecialchars($u['name']) . "**\n" .
                        "   • **Role**: $rLabel\n" .
                        "   • **Status Verifikasi**: $stVerif\n" .
                        "   • **Wilayah**: $wilayahStr\n" .
                        "   • **Email**: {$u['email']}\n" .
                        "   • **Status Akun**: Belum Aktif (Menunggu Verifikasi)\n\n";
            }

            if ($verif['count'] > count($verif['list'])) {
                $out .= "*... dan " . ($verif['count'] - count($verif['list'])) . " akun lainnya.*\n\n";
            }

            $out .= $penjelasanStatus . "\n\n💡 *Silakan buka menu **Verifikasi Warga** atau **Manajemen User** untuk memproses persetujuannya.*";
            return $out;
        }

        $roleSpecStr = $targetRole ? " untuk role **" . ($roleNames[$targetRole] ?? $targetRole) . "**" : " dari seluruh role";

        if ($verif['count'] === 0) {
            $out = "📋 **Jumlah Akun Membutuhkan Verifikasi**:\n\n" .
                   "Berdasarkan data aktual sistem di wilayah **{$verif['scope']}**, saat ini terdapat **0 akun** yang membutuhkan verifikasi (tidak ada akun yang berstatus **Pending**)$roleSpecStr.\n\n" .
                   "📊 **Rincian Status Akun Terdaftar** (Total **{$verif['total_users']} akun**):\n" .
                   "• ✅ **Terverifikasi (Aktif)**: **{$verif['active_count']} akun**\n" .
                   "• ⏳ **Belum Diverifikasi (Pending)**: **{$verif['pending_count']} akun**\n" .
                   "• ❌ **Ditolak (Rejected)**: **{$verif['rejected_count']} akun**\n\n" .
                   "👥 **Status Verifikasi per Role**:\n";

            foreach ($verif['role_counts'] as $rKey => $cnt) {
                $rLabel = $roleNames[$rKey] ?? ucfirst($rKey);
                $icon = ($rKey === 'super_admin') ? '🛡️' : (($rKey === 'desa') ? '🏛️' : (($rKey === 'rw') ? '🚩' : (($rKey === 'rt') ? '🏠' : '👤')));
                $out .= "• $icon **$rLabel**: $cnt akun (Semua Aktif / Terverifikasi)\n";
            }

            $out .= "\n" . $penjelasanStatus;
            return $out;
        }

        $out = "📋 **Jumlah Akun Membutuhkan Verifikasi**:\n\n" .
               "Berdasarkan data aktual sistem di wilayah **{$verif['scope']}**, saat ini terdapat **{$verif['count']} akun** yang membutuhkan verifikasi (berstatus **Pending**)$roleSpecStr.\n\n" .
               "👥 **Rincian Akun Belum Diverifikasi per Role**:\n";

        foreach ($verif['pending_roles'] as $rKey => $cnt) {
            $rLabel = $roleNames[$rKey] ?? ucfirst($rKey);
            $icon = ($rKey === 'super_admin') ? '🛡️' : (($rKey === 'desa') ? '🏛️' : (($rKey === 'rw') ? '🚩' : (($rKey === 'rt') ? '🏠' : '👤')));
            $out .= "• $icon **$rLabel**: **$cnt akun** (Status: ⏳ Pending)\n";
        }

        $out .= "\n📊 **Ringkasan Status Akun Seluruhnya** (Total **{$verif['total_users']} akun**):\n" .
                "• ✅ **Terverifikasi (Aktif)**: **{$verif['active_count']} akun**\n" .
                "• ⏳ **Belum Diverifikasi (Pending)**: **{$verif['pending_count']} akun**\n" .
                "• ❌ **Ditolak (Rejected)**: **{$verif['rejected_count']} akun**\n\n" .
                $penjelasanStatus . "\n\n" .
                "💡 *Silakan buka menu **Verifikasi Warga** atau **Manajemen User** untuk memproses persetujuan akun tersebut.*";
        return $out;
    }

    // ------------------------------------------------------------
    // 6B. STATISTIK USER, AKUN & ROLE SISTEM
    // ------------------------------------------------------------
    $isTanyaRole = (
        preg_match('/\b(ada\s+berapa|berapa|daftar|list|sebutkan|apa\s+saja)\s+(?:jenis\s+)?(?:role|peran)\b/i', $q) ||
        preg_match('/\b(?:role|peran)\s+(?:apa\s+saja|ada\s+berapa)\b/i', $q) ||
        preg_match('/\bada\s+berapa\s+(?:role|peran)\b/i', $q) ||
        (preg_match('/\b(?:role|peran)\b/i', $q) && preg_match('/\b(tersedia|ada\s+di\s+sistem|di\s+siwarga)\b/i', $q) && !preg_match('/\b(setiap|tiap|per)\b/i', $q))
    );

    if ($isTanyaRole) {
        if ($role !== 'super_admin') {
            return RoleCapabilities::rejectMessage($role, 'role_info', $user);
        }
        $statUser = AIDataHelper::getStatistikUser($pdo);
        $out = "Di sistem SIWARGA saat ini terdapat **{$statUser['total_roles']} role (peran pengguna)** yang aktif:\n\n" .
               "1. 🛡️ **Super Admin** ({$statUser['role_counts']['super_admin']} akun) — Otoritas tertinggi sistem, pengelola seluruh database dan menu **Manajemen User**.\n" .
               "2. 🏛️ **Admin Desa** ({$statUser['role_counts']['desa']} akun) — Pengelola administrasi desa, verifikasi surat, pengaduan, dan data warga.\n" .
               "3. 🚩 **Ketua RW** ({$statUser['role_counts']['rw']} akun) — Koordinator wilayah RW, pemantauan warga & pengaduan tingkat RW (RW 01 s/d RW 06).\n" .
               "4. 🏠 **Ketua RT** ({$statUser['role_counts']['rt']} akun) — Pelayanan warga tingkat RT, verifikasi dokumen, dan penanganan aduan (RT 01 s/d RT 06).\n" .
               "5. 👤 **Warga** ({$statUser['role_counts']['warga']} akun) — Pengguna layanan mandiri desa (pengajuan surat & pelaporan aduan).\n\n" .
               "📊 Total seluruh akun pengguna dari ke-5 role tersebut adalah **{$statUser['total_users']} akun**.";
        return $out;
    }

    $isTanyaUserPerRole = (
        preg_match('/\b(setiap|tiap|per|masing[- ]masing)\s+(?:role|peran)\b/i', $q) ||
        (preg_match('/\b(rincian|detail|breakdown|sebaran|distribusi)\b/i', $q) && preg_match('/\b(user|akun|pengguna|role|peran)\b/i', $q)) ||
        (preg_match('/\b(user|akun|pengguna)\b/i', $q) && preg_match('/\b(per|tiap|setiap)\s+(?:role|peran)\b/i', $q))
    );

    if ($isTanyaUserPerRole) {
        if ($role !== 'super_admin') {
            return RoleCapabilities::rejectMessage($role, 'role_info', $user);
        }
        $statUser = AIDataHelper::getStatistikUser($pdo);
        $out = "👥 **Rincian Jumlah Akun Pengguna per Role di SIWARGA**:\n\n" .
               "Berdasarkan data aktual sistem (total **{$statUser['total_users']} akun** terdaftar dari **{$statUser['total_roles']} role**):\n\n" .
               "• 🛡️ **Super Admin**: **{$statUser['role_counts']['super_admin']} akun**\n" .
               "• 🏛️ **Admin Desa**: **{$statUser['role_counts']['desa']} akun**\n" .
               "• 🚩 **Ketua RW**: **{$statUser['role_counts']['rw']} akun**\n" .
               "• 🏠 **Ketua RT**: **{$statUser['role_counts']['rt']} akun**\n" .
               "• 👤 **Warga**: **{$statUser['role_counts']['warga']} akun**\n\n" .
               "📌 **Perbedaan Istilah**:\n" .
               "• **Total User/Akun ({$statUser['total_users']} akun)**: Akun seluruh tingkatan pengguna untuk login ke aplikasi SIWARGA.\n" .
               "• **Jumlah Warga ({$statUser['role_counts']['warga']} warga)**: Khusus akun masyarakat pemohon layanan mandiri.\n" .
               "• **Statistik Kependudukan**: Data agregat kependudukan desa (jenis kelamin, domisili RT/RW).";
        return $out;
    }

    $isTanyaAkunRole = (
        preg_match('/\b(berapa|jumlah|total|ada\s+berapa|hitung)\b/i', $q) &&
        preg_match('/\b(akun|user|pengguna)\b/i', $q) &&
        !$isTanyaVerifikasi &&
        !preg_match('/\b(layanan|pelayanan|fitur|menu)\b/i', $q)
    );

    if ($isTanyaAkunRole) {
        if ($role !== 'super_admin') {
            return RoleCapabilities::rejectMessage($role, 'role_info', $user);
        }
        $statUser = AIDataHelper::getStatistikUser($pdo);
        if (preg_match('/\b(super\s*admin|sa)\b/i', $q)) {
            return "Saat ini di sistem SIWARGA terdapat **{$statUser['role_counts']['super_admin']} akun Super Admin** yang memegang otoritas penuh pengelolaan sistem dan menu **Manajemen User**.";
        }
        if (preg_match('/\b(admin\s*desa|desa|perangkat\s*desa)\b/i', $q) && !preg_match('/\b(rw|rt|warga)\b/i', $q)) {
            return "Saat ini di sistem SIWARGA terdapat **{$statUser['role_counts']['desa']} akun Admin Desa** yang bertanggung jawab atas pengelolaan administrasi desa, surat, dan pengaduan warga.";
        }
        if (preg_match('/\b(ketua\s*rw|rw)\b/i', $q) && !preg_match('/\brt\b/i', $q)) {
            return "Saat ini di sistem SIWARGA terdapat **{$statUser['role_counts']['rw']} akun Ketua RW** (masing-masing untuk wilayah RW 01 sampai RW 06).\n\n💡 *Catatan: Akun Ketua RW memiliki akses untuk memantau data kependudukan dan pengaduan di wilayah RW masing-masing.*";
        }
        if (preg_match('/\b(ketua\s*rt|rt)\b/i', $q)) {
            return "Saat ini di sistem SIWARGA terdapat **{$statUser['role_counts']['rt']} akun Ketua RT** (masing-masing untuk wilayah RT 01 sampai RT 06).\n\n💡 *Catatan: Akun Ketua RT bertugas melayani dan memverifikasi warga serta menindaklanjuti pengaduan di tingkat RT.*";
        }
        if (preg_match('/\bwarga\b/i', $q)) {
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            return "Saat ini di sistem SIWARGA terdapat **{$statUser['role_counts']['warga']} akun Warga** yang terdaftar aktif untuk layanan mandiri (seperti pengaduan dan permohonan surat).\n\n" .
                   "📌 **Perbedaan Istilah**:\n" .
                   "• **Akun Warga**: {$statUser['role_counts']['warga']} akun (bagian dari total {$statUser['total_users']} total user/akun sistem).\n" .
                   "• **Statistik Kependudukan**: {$kependudukan['total_warga']} warga tercatat di database kependudukan (Laki-laki: {$kependudukan['total_laki']}, Perempuan: {$kependudukan['total_perempuan']}).";
        }
    }

    $isTanyaTotalUser = (
        ((preg_match('/\b(berapa|jumlah|total|ada\s+berapa|hitung)\b/i', $q) && preg_match('/\b(user|akun|pengguna)\b/i', $q)) ||
        preg_match('/\b(total\s+user|total\s+akun|jumlah\s+user|jumlah\s+akun)\b/i', $q)) &&
        !$isTanyaVerifikasi &&
        !preg_match('/\b(layanan|pelayanan|fitur|menu)\b/i', $q)
    );

    if ($isTanyaTotalUser) {
        if ($role !== 'super_admin') {
            return RoleCapabilities::rejectMessage($role, 'user_stats', $user);
        }
        $statUser = AIDataHelper::getStatistikUser($pdo);
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        $sidebarNote = "💡 *Sebagai **Super Admin**, Anda dapat melihat dan mengelola seluruh {$statUser['total_users']} akun ini melalui menu **Manajemen User** di sidebar.*";

        $out = "Saat ini di sistem SIWARGA terdapat total **{$statUser['total_users']} akun user** yang terdaftar dari **{$statUser['total_roles']} role** berbeda (sesuai data aktual pada halaman **Manajemen User**):\n\n" .
               "👥 **Rincian Jumlah per Role**:\n" .
               "• 🛡️ **Super Admin**: **{$statUser['role_counts']['super_admin']} akun**\n" .
               "• 🏛️ **Admin Desa**: **{$statUser['role_counts']['desa']} akun**\n" .
               "• 🚩 **Ketua RW**: **{$statUser['role_counts']['rw']} akun**\n" .
               "• 🏠 **Ketua RT**: **{$statUser['role_counts']['rt']} akun**\n" .
               "• 👤 **Warga**: **{$statUser['role_counts']['warga']} akun**\n\n" .
               "📌 **Perbedaan Istilah Penting**:\n" .
               "• **Total User / Akun ({$statUser['total_users']} akun)**: Jumlah seluruh akun login terdaftar di sistem dari semua tingkatan peran (Super Admin, Desa, RW, RT, Warga).\n" .
               "• **Jumlah Warga ({$statUser['role_counts']['warga']} orang)**: Akun khusus dengan peran Warga untuk pelayanan surat dan aduan.\n" .
               "• **Statistik Kependudukan**: Data agregat kependudukan desa (saat ini terdata {$kependudukan['total_warga']} jiwa: {$kependudukan['total_laki']} laki-laki, {$kependudukan['total_perempuan']} perempuan).\n\n" .
               $sidebarNote;
        return $out;
    }

    // ------------------------------------------------------------
    // 6C. LAYANAN & PELAYANAN DESA
    // ------------------------------------------------------------
    $isTanyaLayanan = (
        preg_match('/\b(layanan|pelayanan)\b/i', $q) &&
        !preg_match('/\b(jam\s+layanan|jam\s+pelayanan|jam\s+buka|jam\s+kerja|jam\s+operasional)\b/i', $q)
    );

    if ($isTanyaLayanan) {
        $pelayananData = AIDataHelper::getPelayananData($pdo);
        $totalLayanan = $pelayananData['total_layanan'];
        $listLayanan = $pelayananData['list'];
        $menuListRole = AIDataHelper::getMenuSidebarByRole($role);
        $totalMenuRole = count($menuListRole);

        $isLayananWarga = (
            preg_match('/\b(layanan\s+warga|pelayanan\s+warga|layanan\s+untuk\s+warga|pelayanan\s+untuk\s+warga|layanan\s+masyarakat|pelayanan\s+masyarakat)\b/i', $q) ||
            (preg_match('/\b(layanan|pelayanan)\b/i', $q) && preg_match('/\b(warga|masyarakat)\b/i', $q) && !preg_match('/\b(menu|fitur|sidebar)\b/i', $q))
        );

        if ($isLayananWarga) {
            $statUser = AIDataHelper::getStatistikUser($pdo);
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            $out = "📋 **Layanan yang Tersedia untuk Warga ({$wilayah['desa_nama']})**:\n\n" .
                   "Berdasarkan data aktual pada katalog **Pelayanan**, saat ini tersedia **{$totalLayanan} jenis layanan administrasi warga** yang dapat diurus oleh masyarakat:\n\n";

            $icons = ['📄', '💼', '🎓', '🪪', '🛡️', '👨‍👩‍👧‍👦', '👶', '🕊️'];
            foreach ($listLayanan as $i => $l) {
                $icon = $icons[$i % count($icons)];
                $out .= ($i + 1) . ". $icon **" . htmlspecialchars($l['nama']) . "**\n";
                if (!empty($l['deskripsi'])) {
                    $out .= "   • " . htmlspecialchars($l['deskripsi']) . "\n";
                }
            }

            $out .= "\nSelain {$totalLayanan} layanan administrasi di atas, warga juga dapat memanfaatkan **2 fasilitas layanan mandiri online** di aplikasi:\n" .
                    "• 📮 **Layanan Buat Surat Online** (menu **Buat Surat**)\n" .
                    "• 📢 **Layanan Pengaduan & Aspirasi Warga** (menu **Pengaduan**)\n\n" .
                    "📌 **Perbedaan Istilah Penting**:\n" .
                    "• **Layanan Warga ({$totalLayanan} layanan)**: Ragam urusan administrasi dan persuratan desa yang disediakan untuk masyarakat pada menu Pelayanan.\n" .
                    "• **Jumlah Warga ({$kependudukan['total_warga']} orang)**: Jumlah penduduk terdaftar di database kependudukan desa (Laki-laki: {$kependudukan['total_laki']}, Perempuan: {$kependudukan['total_perempuan']}).\n" .
                    "• **Akun Warga ({$statUser['role_counts']['warga']} akun)**: Jumlah akun masyarakat login yang terdaftar di aplikasi SIWARGA.\n" .
                    "• **Menu Sidebar ({$totalMenuRole} menu)**: Menu navigasi aplikasi yang dapat diakses oleh role " . RoleCapabilities::label($role) . ".\n\n" .
                    "💡 *Warga dapat mengajukan permohonan surat secara mandiri melalui menu **Buat Surat** atau dibantu langsung oleh SiWarga AI!*";
            return $out;
        }

        $isDaftarLayanan = preg_match('/\b(apa saja|apa aja|daftar|list|sebutkan|rincian|katalog|detail|syarat|tampilkan|tersedia|bisa urus|bisa bikin|ada apa saja)\b/i', $q);

        if ($isDaftarLayanan) {
            $out = "📋 **Katalog Lengkap Layanan Administrasi Desa ({$wilayah['desa_nama']})**:\n\n" .
                   "Berdasarkan data aktual pada menu **Pelayanan**, saat ini tersedia **{$totalLayanan} jenis layanan administrasi** beserta persyaratan dokumennya:\n\n";

            foreach ($listLayanan as $i => $l) {
                $out .= ($i + 1) . ". **" . htmlspecialchars($l['nama']) . "**\n";
                if (!empty($l['deskripsi'])) {
                    $out .= "   • **Deskripsi**: " . htmlspecialchars($l['deskripsi']) . "\n";
                }
                if (!empty($l['syarat'])) {
                    $out .= "   • **Syarat Dokumen**: " . htmlspecialchars($l['syarat']) . "\n";
                }
                $out .= "\n";
            }

            $out .= "💡 **Cara Pengajuan**:\n" .
                    "Warga dapat mengajukan surat secara online melalui menu **Buat Surat**, atau ketik langsung di chat AI: *\"Buatkan surat [nama layanan]\"*! 😊";
            return $out;
        }

        $isJumlahLayanan = (
            preg_match('/\b(berapa|jumlah|total|hitung|ada berapa)\b/i', $q) ||
            preg_match('/\b(jumlah\s+layanan|total\s+layanan|berapa\s+layanan)\b/i', $q)
        );

        if ($isJumlahLayanan) {
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            $out = "📋 **Jumlah Layanan pada Menu Pelayanan**:\n\n" .
                   "Berdasarkan data aktual sistem di menu **Pelayanan** (tabel database `pelayanan`), saat ini tercatat ada **{$totalLayanan} jenis layanan administrasi desa** yang siap melayani warga:\n\n";

            foreach ($listLayanan as $i => $l) {
                $out .= ($i + 1) . ". **" . htmlspecialchars($l['nama']) . "**\n";
            }

            $out .= "\n📌 **Perbedaan Istilah**:\n" .
                    "• **Layanan ({$totalLayanan} layanan)**: Seluruh urusan administrasi pada menu Pelayanan (atau Info Layanan).\n" .
                    "• **Fitur Aplikasi (12 fitur)**: Modul dan kapabilitas fungsional platform aplikasi SIWARGA.\n" .
                    "• **Menu Sidebar ({$totalMenuRole} menu)**: Menu navigasi antarmuka pada sidebar untuk role " . RoleCapabilities::label($role) . ".\n" .
                    "• **Statistik Kependudukan ({$kependudukan['total_warga']} warga)**: Data jumlah masyarakat desa terdaftar.\n\n" .
                    "💡 *Untuk melihat persyaratan masing-masing layanan, ketik: 'Apa saja layanan yang tersedia?'*";
            return $out;
        }
    }

    // ------------------------------------------------------------
    // 6D. FITUR APLIKASI SIWARGA
    // ------------------------------------------------------------
    $isTanyaFitur = (
        preg_match('/\b(fitur|capabilities|kemampuan\s+aplikasi)\b/i', $q) &&
        !preg_match('/\b(menu|sidebar|layanan|pelayanan)\b/i', $q)
    ) || preg_match('/\b(berapa|apa saja|daftar|list)\s+fitur\b/i', $q) || preg_match('/\bfitur\s+(apa saja|ada berapa)\b/i', $q);

    if ($isTanyaFitur) {
        $fiturList = AIDataHelper::getFiturAplikasiList();
        $totalFitur = count($fiturList);
        $menuListRole = AIDataHelper::getMenuSidebarByRole($role);
        $totalMenuRole = count($menuListRole);
        $pelayananData = AIDataHelper::getPelayananData($pdo);
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);

        $out = "⚡ **Daftar & Jumlah Fitur Aplikasi SIWARGA**:\n\n" .
               "Aplikasi SIWARGA dilengkapi dengan **{$totalFitur} fitur utama** untuk mendukung digitalisasi tata kelola dan pelayanan desa:\n\n";

        $i = 1;
        foreach ($fiturList as $namaFitur => $descFitur) {
            $out .= "$i. **$namaFitur** — $descFitur\n";
            $i++;
        }

        $out .= "\n💡 *Sebagai **" . RoleCapabilities::label($role) . "**, Anda dapat mengakses fitur-fitur tersebut sesuai wewenang peran melalui **{$totalMenuRole} menu sidebar**.*\n\n" .
                "📌 **Perbedaan Istilah**:\n" .
                "• **Fitur Aplikasi ({$totalFitur} fitur)**: Seluruh kapabilitas fungsional platform aplikasi SIWARGA.\n" .
                "• **Menu Sidebar ({$totalMenuRole} menu)**: Tautan navigasi antarmuka pada sidebar aplikasi sesuai role.\n" .
                "• **Layanan Desa ({$pelayananData['total_layanan']} layanan)**: Katalog urusan administrasi pada menu Pelayanan.\n" .
                "• **Statistik Kependudukan ({$kependudukan['total_warga']} warga)**: Data jumlah masyarakat desa terdaftar.";
        return $out;
    }

    // ------------------------------------------------------------
    // 6E. MENU SIDEBAR SESUAI ROLE
    // ------------------------------------------------------------
    $isTanyaMenu = (
        preg_match('/\b(menu|sidebar)\b/i', $q) &&
        !preg_match('/\b(layanan|pelayanan|fitur)\b/i', $q)
    ) || preg_match('/\b(berapa|apa saja|daftar|list)\s+menu\b/i', $q) || preg_match('/\bmenu\s+(apa saja|ada berapa)\b/i', $q);

    if ($isTanyaMenu) {
        $allMenuCounts = AIDataHelper::getAllMenuCounts();

        $isTanyaMenuSemuaRole = preg_match('/\b(setiap\s+role|tiap\s+role|semua\s+role|masing[- ]masing\s+role|per\s+role)\b/i', $q);

        if ($isTanyaMenuSemuaRole) {
            $out = "📌 **Jumlah Menu Sidebar SIWARGA Berdasarkan Role**:\n\n" .
                   "Setiap role memiliki navigasi menu sidebar yang disesuaikan dengan wewenang masing-masing (sesuai navigasi aplikasi):\n\n" .
                   "• 🛡️ **Super Admin**: **{$allMenuCounts['super_admin']} menu** (Akses penuh sistem, manajemen user, konfigurasi AI, wilayah & master data)\n" .
                   "• 🏛️ **Admin Desa**: **{$allMenuCounts['desa']} menu** (Pengelolaan administrasi desa, verifikasi surat, pengaduan, dan data warga)\n" .
                   "• 🚩 **Ketua RW**: **{$allMenuCounts['rw']} menu** (Monitoring warga RW, info RT, pengaduan RW, info layanan, dan persuratan)\n" .
                   "• 🏠 **Ketua RT**: **{$allMenuCounts['rt']} menu** (Pelayanan warga RT, penanganan aduan RT, info layanan, dan surat)\n" .
                   "• 👤 **Warga**: **{$allMenuCounts['warga']} menu** (Dashboard, Pengumuman, Pengaduan, Info Layanan, Buat Surat, Pengaturan)\n\n" .
                   "💡 *Sebagai **" . RoleCapabilities::label($role) . "**, saat ini kamu memiliki akses ke **{$allMenuCounts[$role]} menu sidebar**.*";
            return $out;
        }

        $menuListRole = AIDataHelper::getMenuSidebarByRole($role);
        $totalMenuRole = count($menuListRole);
        $pelayananData = AIDataHelper::getPelayananData($pdo);
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);

        $out = "📌 **Menu Sidebar SIWARGA untuk " . RoleCapabilities::label($role) . "**:\n\n" .
               "Sebagai **" . RoleCapabilities::label($role) . "**, kamu memiliki akses ke **{$totalMenuRole} menu sidebar** pada navigasi aplikasi SIWARGA:\n\n";

        foreach ($menuListRole as $i => $mItem) {
            $out .= ($i + 1) . ". **{$mItem}**\n";
        }

        $out .= "\n📌 **Perbedaan Istilah**:\n" .
                "• **Menu Sidebar ({$totalMenuRole} menu)**: Tautan navigasi antarmuka pada sidebar kiri aplikasi sesuai hak akses role kamu.\n" .
                "• **Fitur Aplikasi (12 fitur)**: Modul dan kapabilitas fungsional platform aplikasi SIWARGA.\n" .
                "• **Layanan Desa ({$pelayananData['total_layanan']} layanan)**: Katalog urusan administrasi pada menu Pelayanan.\n" .
                "• **Statistik Kependudukan ({$kependudukan['total_warga']} warga)**: Data agregat kependudukan desa.\n\n" .
                "Mau mengetahui cara kerja salah satu menu? Tanyakan saja! 😊";
        return $out;
    }

    // ------------------------------------------------------------
    // 7. TOTAL WARGA / JUMLAH WARGA / GENDER
    // ------------------------------------------------------------
    if (preg_match('/\b(berapa|jumlah|total|hitung)\b/i', $q) && preg_match('/\b(warga|penduduk|jiwa|orang)(?:nya)?\b/i', $q) && !preg_match('/\b(user|akun|pengguna|role|peran)\b/i', $q) && !preg_match('/\b(daftar|list|nama|siapa saja|tampilkan)\b/i', $q) && !preg_match('/\b(belum\s+lengkap|kurang\s+lengkap|belum\s+melengkapi|masih\s+kurang|data\s+kurang|kurang)\b/i', $q) && !preg_match('/\b(layanan|pelayanan|fitur|menu)\b/i', $q)) {
        if (preg_match('/\brw\s*(\d+)\b/i', $q, $mRw)) {
            $rwNomor = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = ?");
            $stmt->execute([$rwNomor]);
            $count = (int)$stmt->fetchColumn();

            $stmtK = $pdo->prepare("SELECT ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ?");
            $stmtK->execute([$rwNomor]);
            $ketua = $stmtK->fetchColumn();
            $infoKetua = $ketua ? " (Ketua RW: **{$ketua}**)" : "";

            return "Di **RW {$rwNomor}**, saat ini terdapat **{$count} warga** yang terdaftar{$infoKetua}.";
        }

        if (preg_match('/\brt\s*(\d+)\b/i', $q, $mRt)) {
            $rtNomor = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt),2,'0') = ?");
            $stmt->execute([$rtNomor]);
            $count = (int)$stmt->fetchColumn();

            $stmtK = $pdo->prepare("SELECT ketua FROM rt WHERE LPAD(TRIM(nomor),2,'0') = ?");
            $stmtK->execute([$rtNomor]);
            $ketua = $stmtK->fetchColumn();
            $infoKetua = $ketua ? " (Ketua RT: **{$ketua}**)" : "";

            return "Di **RT {$rtNomor}**, saat ini terdapat **{$count} warga** yang terdaftar{$infoKetua}.";
        }

        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        $isLaki = preg_match('/\b(laki-laki|laki|pria|cowok)\b/i', $q);
        $isPerempuan = preg_match('/\b(perempuan|wanita|cewek)\b/i', $q);

        if ($isLaki && !$isPerempuan) {
            return "Untuk wilayah **{$kependudukan['scope']}**, warga **laki-laki** saat ini berjumlah **{$kependudukan['total_laki']} orang**.";
        }
        if ($isPerempuan && !$isLaki) {
            return "Untuk wilayah **{$kependudukan['scope']}**, warga **perempuan** saat ini berjumlah **{$kependudukan['total_perempuan']} orang**.";
        }
        return "Total warga di wilayah **{$kependudukan['scope']}** saat ini tercatat sebanyak **{$kependudukan['total_warga']} orang** (Laki-laki: {$kependudukan['total_laki']}, Perempuan: {$kependudukan['total_perempuan']}).";
    }

    // ------------------------------------------------------------
    // 8. JUMLAH RT / RW DI DESA
    // ------------------------------------------------------------
    if (preg_match('/\b(berapa|jumlah|total|ada berapa)\b/i', $q) && preg_match('/\b(rt|rw)\b/i', $q) && !preg_match('/\b(warga|pengaduan|surat|akun|user|data)\b/i', $q)) {
        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        $sebutRt = preg_match('/\brt\b/i', $q);
        $sebutRw = preg_match('/\brw\b/i', $q);
        if ($sebutRt && $sebutRw) {
            return "Untuk Desa {$wilayah['desa_nama']}, saat ini terdata ada **{$kependudukan['total_rt']} RT** dan **{$kependudukan['total_rw']} RW**.";
        }
        if ($sebutRw) {
            return "Untuk Desa {$wilayah['desa_nama']}, saat ini terdata ada **{$kependudukan['total_rw']} wilayah RW**.";
        }
        if ($sebutRt) {
            return "Untuk Desa {$wilayah['desa_nama']}, saat ini terdata ada **{$kependudukan['total_rt']} wilayah RT**.";
        }
    }

    // ------------------------------------------------------------
    // 9. KATEGORI PENGADUAN
    // ------------------------------------------------------------
    if (preg_match('/\b(kategori|jenis)\b/i', $q) && preg_match('/\b(pengaduan|aduan|laporan|keluhan)\b/i', $q)) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if (!empty($pengaduan['per_kategori'])) {
            $out = "📢 **Rekap Kategori Pengaduan Masuk ({$pengaduan['scope']})**:\n\n";
            foreach ($pengaduan['per_kategori'] as $kat => $jml) {
                $out .= "• **" . ucfirst($kat) . "**: {$jml} laporan\n";
            }
            if ($pengaduan['kategori_terbanyak']) {
                $out .= "\n💡 *Kategori paling sering dilaporkan: **" . ucfirst($pengaduan['kategori_terbanyak']['kategori']) . "** ({$pengaduan['kategori_terbanyak']['jumlah']} laporan).*";
            }
            return $out;
        }
        return "Belum ada kategori pengaduan yang tercatat di wilayah akses kamu. 🙏";
    }

    // ------------------------------------------------------------
    // 10. REKAP / JUMLAH PENGADUAN
    // ------------------------------------------------------------
    if (preg_match('/\b(berapa|jumlah|total|rekap|statistik)\b/i', $q) && preg_match('/\b(pengaduan|aduan|laporan)\b/i', $q) && !preg_match('/\b(kategori|jenis|cara|langkah)\b/i', $q)) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if (preg_match('/\bpending\b/i', $q)) {
            return "Untuk wilayah **{$pengaduan['scope']}**, saat ini terdapat pengaduan berstatus Pending: **{$pengaduan['pending']}** laporan.";
        }
        if (preg_match('/\b(proses|diproses)\b/i', $q)) {
            return "Untuk wilayah **{$pengaduan['scope']}**, saat ini terdapat pengaduan berstatus Diproses: **{$pengaduan['proses']}** laporan.";
        }
        if (preg_match('/\b(selesai)\b/i', $q)) {
            return "Untuk wilayah **{$pengaduan['scope']}**, pengaduan yang sudah Selesai: **{$pengaduan['selesai']}** laporan.";
        }
        return "📊 **Statistik Pengaduan ({$pengaduan['scope']})**:\n\n• Total Laporan: **{$pengaduan['total']}**\n• Pending: **{$pengaduan['pending']}**\n• Diproses: **{$pengaduan['proses']}**\n• Selesai: **{$pengaduan['selesai']}**\n• Ditolak: **{$pengaduan['ditolak']}**";
    }

    // ------------------------------------------------------------
    // 11. STATUS PENGADUAN SAYA (WARGA)
    // ------------------------------------------------------------
    if ($role === 'warga' && preg_match('/\b(pengaduan|aduan|laporan|keluhan)\b/i', $q) && !preg_match('/\b(surat|pengajuan)\b/i', $q) && preg_match('/\b(status|saya|aku|ku|gw|gue|sy|pengaduanku|aduanku)\b/i', $q)) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if (empty($pengaduan['pengaduan_saya'])) {
            return "Kamu belum pernah mengirim pengaduan nih. Kalau mau melapor sesuatu, langsung buka menu **Pengaduan** ya! 😊";
        }
        $out = "⚠️ **Status Pengaduan Kamu**:\n\n";
        foreach ($pengaduan['pengaduan_saya'] as $i => $p) {
            $badge = ($p['status'] === 'pending') ? '⏳ Pending' : (($p['status'] === 'selesai') ? '✅ Selesai' : (($p['status'] === 'proses') ? '🔄 Diproses' : '❌ Ditolak'));
            $tgl = date('d M Y', strtotime($p['created_at']));
            $out .= ($i + 1) . ". **{$p['judul']}** (" . ucfirst($p['kategori']) . ")\n   • Status: $badge\n   • Tanggal: $tgl\n\n";
        }
        return $out . "*Pantau selengkapnya di menu **Pengaduan** ya!*";
    }

    // ------------------------------------------------------------
    // 12. REKAP PENGADUAN TERBANYAK PER RT/RW
    // ------------------------------------------------------------
    if (preg_match('/\b(rt|rw)\b/i', $q) && preg_match('/\b(pengaduan|aduan|laporan|keluhan)\b/i', $q) && preg_match('/\b(terbanyak|paling|tertinggi|rank|ranking|banyak|mana)\b/i', $q)) {
        if (!in_array($role, ['super_admin', 'desa', 'rw'])) {
            return "Maaf, data perbandingan pengaduan antar wilayah hanya bisa diakses oleh Admin Desa atau Ketua RW. 🙏";
        }
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        $isRwQuery = preg_match('/\brw\b/i', $q) && !preg_match('/\brt\b/i', $q);
        if ($isRwQuery && !empty($pengaduan['ranking_rw'])) {
            $out = "🚨 **Ranking Pengaduan Terbanyak per RW**:\n\n";
            foreach ($pengaduan['ranking_rw'] as $i => $r) {
                $out .= ($i + 1) . ". **RW {$r['rw']}**: {$r['jml']} pengaduan\n";
            }
            return $out;
        } elseif (!empty($pengaduan['ranking_rt'])) {
            $out = "🚨 **Ranking Pengaduan Terbanyak per RT**:\n\n";
            foreach ($pengaduan['ranking_rt'] as $i => $r) {
                $label = ($role === 'rw') ? "RT {$r['rt']}" : "RT {$r['rt']} / RW {$r['rw']}";
                $out .= ($i + 1) . ". **$label**: {$r['jml']} pengaduan\n";
            }
            return $out;
        }
        return "Belum ada data pengaduan masuk di wilayah akses kamu. 🙏";
    }

    // ------------------------------------------------------------
    // 13. PRIORITAS TINDAK LANJUT PENGADUAN
    // ------------------------------------------------------------
    if (preg_match('/\b(pengaduan|aduan|laporan)\b/i', $q) && preg_match('/\b(lama|terlama|pending|belum selesai|tindak lanjut|prioritas|urgent)\b/i', $q) && !preg_match('/\b(berapa|jumlah|hitung|cara|bagaimana|langkah)\b/i', $q) && in_array($role, ['super_admin', 'desa', 'rw', 'rt'])) {
        $pengaduan = AIDataHelper::getStatistikPengaduan($pdo, $user);
        if (!empty($pengaduan['prioritas_tindak_lanjut'])) {
            $out = "🚨 **Prioritas Tindak Lanjut Pengaduan** (Aduan Terlama Belum Selesai):\n\n";
            foreach ($pengaduan['prioritas_tindak_lanjut'] as $i => $r) {
                $tgl = date('d M Y', strtotime($r['created_at']));
                $hari = floor((time() - strtotime($r['created_at'])) / 86400);
                $out .= ($i + 1) . ". **{$r['judul']}** (" . ucfirst($r['prioritas']) . ")\n   • Pelapor: {$r['name']} (RT {$r['rt']} / RW {$r['rw']})\n   • Status: {$r['status']}\n   • Umur Laporan: {$hari} hari ({$tgl})\n\n";
            }
            return $out . "*Segera tindak lanjuti di menu **Pengaduan** ya!* 🙏";
        }
        return "Kabar baik! Semua pengaduan di wilayah kamu sudah selesai ditindaklanjuti. Kerja bagus! 🎉";
    }

    // ------------------------------------------------------------
    // 14A. DAFTAR PENGAJUAN SURAT BELUM SELESAI (PENDING)
    // ------------------------------------------------------------
    $isSuratPendingList = (
        (preg_match('/\b(siapa|siapa saja|daftar|list|tampilkan|mana saja|sebutkan)\b/i', $q) &&
         preg_match('/\b(surat|pengajuan)\b/i', $q) &&
         preg_match('/\b(belum selesai|belum kelar|belum beres|belum diproses|belum disetujui|pending|menunggu persetujuan|masih pending|tunggu)\b/i', $q))
        || preg_match('/\b(surat|pengajuan)\b.*\b(belum selesai|belum kelar|belum beres|belum diproses|masih pending)\b/i', $q)
        || preg_match('/\b(daftar|list)\s+surat\s+pending\b/i', $q)
    );

    if ($isSuratPendingList) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if ($role === 'warga') {
            if (!empty($surat['pending_list'])) {
                $out = "Sebagai **Warga**, demi perlindungan privasi data warga lain, kamu hanya dapat melihat status pengajuan surat milik kamu sendiri. 🙏\n\n";
                $out .= "📄 **Pengajuan Surat Kamu yang Belum Selesai (Pending)**:\n\n";
                foreach ($surat['pending_list'] as $i => $p) {
                    $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($p['email_sent_at'])) . ')' : '📭 Belum dikirim';
                    $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . "\n";
                    $out .= "   • **Nomor Surat**: `" . htmlspecialchars($p['nomor_surat']) . "`\n";
                    $out .= "   • **Jenis Surat**: " . htmlspecialchars($p['judul']) . "\n";
                    $out .= "   • **Status Surat**: ⏳ **PENDING** (Menunggu Persetujuan)\n";
                    $out .= "   • **Status Email**: " . $statusEmail . "\n\n";
                }
                $out .= "💡 *Catatan:* **Status Surat** menunjukkan proses persetujuan berkas (Pending / Disetujui / Ditolak), sedangkan **Status Email** menunjukkan apakah file PDF surat sudah dikirimkan ke email kamu. Surat yang masih pending belum dapat dikirimkan ke email hingga disetujui.";
                return $out;
            } else {
                return "Sebagai **Warga**, demi perlindungan privasi data warga lain, kamu hanya dapat melihat status pengajuan surat milik kamu sendiri. 🙏\n\nKabar baik! Saat ini kamu **tidak memiliki pengajuan surat yang belum selesai (Pending)**. Seluruh pengajuan surat kamu sudah diproses! 😊\n\nKetik *\"Status surat saya\"* untuk melihat riwayat surat kamu.";
            }
        }

        if (empty($surat['pending_list'])) {
            return "Kabar baik! Di wilayah **{$surat['scope']}**, saat ini **tidak ada pengajuan surat yang belum selesai (Pending)**. Semua pengajuan surat telah selesai diproses! 🎉";
        }

        $out = "Berikut daftar pengajuan surat warga yang **belum selesai (Pending)** di wilayah **{$surat['scope']}** (sesuai data aktual di tabel Manajemen Surat):\n\n";
        foreach ($surat['pending_list'] as $i => $p) {
            $wilayahStr = '';
            if (!empty($p['rt']) || !empty($p['rw'])) {
                $wilayahStr = " (RT " . ($p['rt'] ?: '-') . " / RW " . ($p['rw'] ?: '-') . ")";
            }
            $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($p['email_sent_at'])) . ')' : '📭 Belum dikirim';
            
            $out .= ($i + 1) . ". **Pemohon**: " . htmlspecialchars($p['pemohon']) . $wilayahStr . "\n";
            $out .= "   • **Nomor Surat**: `" . htmlspecialchars($p['nomor_surat']) . "`\n";
            $out .= "   • **Jenis Surat**: " . htmlspecialchars($p['judul']) . "\n";
            $out .= "   • **Status Surat**: ⏳ **PENDING** (Menunggu Persetujuan)\n";
            $out .= "   • **Status Email**: " . $statusEmail . "\n\n";
        }

        $out .= "💡 **Perbedaan Status**:\n";
        $out .= "• **Status Surat**: Menunjukkan verifikasi/persetujuan administratif (Pending / Disetujui / Ditolak).\n";
        $out .= "• **Status Email**: Menunjukkan pengiriman file fisik PDF ke email pemohon. Surat pending belum dapat dikirim ke email hingga disetujui.\n\n";
        $out .= "*Silakan buka menu **Surat** untuk menindaklanjuti persetujuan permohonan di atas.* 📄";
        return $out;
    }

    // ------------------------------------------------------------
    // 14B. PERBEDAAN STATUS SURAT DENGAN STATUS EMAIL
    // ------------------------------------------------------------
    if ((preg_match('/\b(beda|bedanya|perbedaan|bedakan|apa itu)\b/i', $q) && preg_match('/\b(status surat|surat)\b/i', $q) && preg_match('/\b(status email|email)\b/i', $q))
        || preg_match('/\b(status surat)\b.*\b(status email)\b/i', $q)) {
        return "📌 **Perbedaan Status Surat dan Status Email di SIWARGA**:\n\n" .
               "1. **Status Surat** (Proses Administrasi & Verifikasi Dokumen):\n" .
               "   • Menunjukkan tahap pengesahan/persetujuan berkas oleh Ketua RT, Ketua RW, atau Admin Desa.\n" .
               "   • Nilai status pada sistem:\n" .
               "     - ⏳ **PENDING**: Pengajuan baru masuk dari warga, sedang menunggu peninjauan dan persetujuan petugas.\n" .
               "     - ✅ **DISETUJUI**: Pengajuan telah disetujui dan disahkan secara resmi oleh petugas. Surat resmi terbit dan siap dicetak/diunduh.\n" .
               "     - ❌ **DITOLAK**: Pengajuan ditolak oleh petugas karena data tidak valid atau persyaratan belum terpenuhi.\n\n" .
               "2. **Status Email** (Proses Pengiriman Dokumen Elektronik):\n" .
               "   • Menunjukkan apakah file PDF surat resmi yang telah disetujui sudah dikirimkan secara elektronik ke alamat email pemohon.\n" .
               "   • Nilai status pada sistem:\n" .
               "     - ✉️ **Terkirim**: File PDF surat sudah berhasil dikirim ke email warga (disertai tanggal & jam pengiriman).\n" .
               "     - 📭 **Belum dikirim**: File PDF surat belum dikirim ke email pemohon.\n\n" .
               "💡 **Catatan Alur Sistem**:\n" .
               "Surat baru dapat dikirim ke email pemohon jika **Status Surat = DISETUJUI**. Surat yang masih berstatus **PENDING** atau **DITOLAK** tidak bisa dikirim ke email.";
    }

    // ------------------------------------------------------------
    // 14C. DAFTAR SURAT DISETUJUI
    // ------------------------------------------------------------
    $isSuratDisetujuiList = (
        preg_match('/\b(siapa|siapa saja|daftar|list|tampilkan)\b/i', $q) &&
        preg_match('/\b(surat|pengajuan)\b/i', $q) &&
        preg_match('/\b(disetujui|acc|sudah disetujui|diterima|selesai diproses)\b/i', $q) &&
        !preg_match('/\b(berapa|jumlah|total)\b/i', $q)
    );

    if ($isSuratDisetujuiList) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if ($role === 'warga') {
            if (!empty($surat['disetujui_list'])) {
                $out = "📄 **Daftar Surat Kamu yang Telah Disetujui**:\n\n";
                foreach ($surat['disetujui_list'] as $i => $s) {
                    $statusEmail = !empty($s['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($s['email_sent_at'])) . ')' : '📭 Belum dikirim';
                    $out .= ($i + 1) . ". **{$s['judul']}** (No: `{$s['nomor_surat']}`)\n";
                    $out .= "   • Status Surat: ✅ **DISETUJUI**\n";
                    $out .= "   • Status Email: $statusEmail\n\n";
                }
                return $out . "*Kamu bisa mengunduh atau mencetak surat di menu **Buat Surat**.*";
            }
            return "Saat ini belum ada pengajuan surat kamu yang berstatus disetujui. 😊";
        }

        if (empty($surat['disetujui_list'])) {
            return "Belum ada pengajuan surat yang disetujui di wilayah **{$surat['scope']}**.";
        }

        $out = "📄 **Daftar Surat yang Telah Disetujui ({$surat['scope']})**:\n\n";
        foreach ($surat['disetujui_list'] as $i => $p) {
            $wilayahStr = (!empty($p['rt']) || !empty($p['rw'])) ? " (RT " . ($p['rt'] ?: '-') . " / RW " . ($p['rw'] ?: '-') . ")" : '';
            $statusEmail = !empty($p['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($p['email_sent_at'])) . ')' : '📭 Belum dikirim';
            $out .= ($i + 1) . ". **Pemohon**: {$p['pemohon']}{$wilayahStr}\n";
            $out .= "   • Nomor Surat: `{$p['nomor_surat']}`\n";
            $out .= "   • Jenis Surat: {$p['judul']}\n";
            $out .= "   • Status Surat: ✅ **DISETUJUI**\n";
            $out .= "   • Status Email: $statusEmail\n\n";
        }
        return $out;
    }

    // ------------------------------------------------------------
    // 14D. DAFTAR SURAT DITOLAK
    // ------------------------------------------------------------
    $isSuratDitolakList = (
        preg_match('/\b(siapa|siapa saja|daftar|list|tampilkan)\b/i', $q) &&
        preg_match('/\b(surat|pengajuan)\b/i', $q) &&
        preg_match('/\b(ditolak|reject|gagal)\b/i', $q) &&
        !preg_match('/\b(berapa|jumlah|total)\b/i', $q)
    );

    if ($isSuratDitolakList) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if ($role === 'warga') {
            if (!empty($surat['ditolak_list'])) {
                $out = "📄 **Daftar Surat Kamu yang Ditolak**:\n\n";
                foreach ($surat['ditolak_list'] as $i => $s) {
                    $out .= ($i + 1) . ". **{$s['judul']}** (No: `{$s['nomor_surat']}`)\n";
                    $out .= "   • Status Surat: ❌ **DITOLAK**\n";
                    $out .= "   • Status Email: 📭 Belum dikirim\n\n";
                }
                return $out . "*Silakan periksa alasan penolakan dan lengkapi berkas di menu **Buat Surat**.*";
            }
            return "Kabar baik! Tidak ada pengajuan surat kamu yang ditolak. 😊";
        }

        if (empty($surat['ditolak_list'])) {
            return "Kabar baik! Tidak ada pengajuan surat yang berstatus ditolak di wilayah **{$surat['scope']}**. 🎉";
        }

        $out = "📄 **Daftar Surat yang Ditolak ({$surat['scope']})**:\n\n";
        foreach ($surat['ditolak_list'] as $i => $p) {
            $wilayahStr = (!empty($p['rt']) || !empty($p['rw'])) ? " (RT " . ($p['rt'] ?: '-') . " / RW " . ($p['rw'] ?: '-') . ")" : '';
            $out .= ($i + 1) . ". **Pemohon**: {$p['pemohon']}{$wilayahStr}\n";
            $out .= "   • Nomor Surat: `{$p['nomor_surat']}`\n";
            $out .= "   • Jenis Surat: {$p['judul']}\n";
            $out .= "   • Status Surat: ❌ **DITOLAK**\n";
            $out .= "   • Status Email: 📭 Belum dikirim\n\n";
        }
        return $out;
    }

    // ------------------------------------------------------------
    // 14E. REKAP / JUMLAH SURAT
    // ------------------------------------------------------------
    if (preg_match('/\b(berapa|jumlah|total|rekap|statistik|ada berapa)\b/i', $q) && preg_match('/\b(surat|pengajuan)\b/i', $q) && !preg_match('/\b(pengaduan|aduan|laporan)\b/i', $q)) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if ($role === 'warga') {
            if (preg_match('/\b(pending|menunggu|belum selesai|belum diproses)\b/i', $q)) {
                return "Kamu memiliki **{$surat['pengajuan_pending']} pengajuan surat** yang masih berstatus **Pending** (menunggu persetujuan) dari total **{$surat['total_pengajuan']} surat** yang pernah kamu ajukan. Ketik *\"Status surat saya\"* untuk melihat rinciannya! 📄";
            }
            if (preg_match('/\b(disetujui|acc|selesai)\b/i', $q)) {
                return "Dari total **{$surat['total_pengajuan']} pengajuan surat** kamu, sebanyak **{$surat['pengajuan_disetujui']} surat** telah **Disetujui**! 🎉";
            }
            if (preg_match('/\b(ditolak|reject)\b/i', $q)) {
                return "Pengajuan surat kamu yang berstatus ditolak berjumlah **{$surat['pengajuan_ditolak']} surat**.";
            }
            return "Kamu tercatat telah mengajukan total **{$surat['total_pengajuan']} surat** di SIWARGA:\n• ⏳ **Pending**: {$surat['pengajuan_pending']} surat\n• ✅ **Disetujui**: {$surat['pengajuan_disetujui']} surat\n• ❌ **Ditolak**: {$surat['pengajuan_ditolak']} surat\n\nKetik *\"Status surat saya\"* untuk detail lengkapnya ya! 😊";
        }

        if (preg_match('/\b(pending|menunggu|belum disetujui|tunggu|persetujuan|belum selesai|belum diproses)\b/i', $q)) {
            return "Saat ini terdapat **{$surat['pengajuan_pending']} pengajuan surat** yang masih menunggu persetujuan (status *Pending*) di wilayah **{$surat['scope']}** (dari total {$surat['total_pengajuan']} pengajuan masuk di sistem).\n\n📊 *Rincian Status Surat ({$surat['scope']}):*\n• ⏳ **Pending**: {$surat['pengajuan_pending']} surat\n• ✅ **Disetujui**: {$surat['pengajuan_disetujui']} surat\n• ❌ **Ditolak**: {$surat['pengajuan_ditolak']} surat\n\n📧 *Rincian Status Email:*\n• ✉️ **Sudah Terkirim Email**: {$surat['email_terkirim']} surat\n• 📭 **Belum Dikirim Email**: {$surat['email_belum_terkirim']} surat\n\nKetik *\"Siapa saja yang pengajuan suratnya belum selesai?\"* untuk melihat daftar pemohonnya! 📄";
        }
        if (preg_match('/\b(disetujui|acc|selesai)\b/i', $q)) {
            return "Untuk wilayah **{$surat['scope']}**, tercatat sebanyak **{$surat['pengajuan_disetujui']} pengajuan surat** telah **Disetujui** (dari total {$surat['total_pengajuan']} pengajuan). Sebanyak {$surat['email_terkirim']} surat di antaranya telah berhasil dikirimkan ke email pemohon.";
        }
        if (preg_match('/\b(ditolak|reject)\b/i', $q)) {
            return "Untuk wilayah **{$surat['scope']}**, tercatat sebanyak **{$surat['pengajuan_ditolak']} pengajuan surat** berstatus **Ditolak** (dari total {$surat['total_pengajuan']} pengajuan masuk).";
        }
        return "📄 **Statistik Pengajuan Surat ({$surat['scope']})**:\n\n• **Total Pengajuan Surat**: **{$surat['total_pengajuan']}**\n• ⏳ **Pending (Menunggu Persetujuan)**: {$surat['pengajuan_pending']} surat\n• ✅ **Disetujui**: {$surat['pengajuan_disetujui']} surat\n• ❌ **Ditolak**: {$surat['pengajuan_ditolak']} surat\n\n📧 **Status Pengiriman Email**:\n• ✉️ **Sudah Dikirim Email**: {$surat['email_terkirim']} surat\n• 📭 **Belum Dikirim Email**: {$surat['email_belum_terkirim']} surat\n\n*(Data akurat & sinkron dengan tabel Manajemen Surat)*";
    }

    // ------------------------------------------------------------
    // 15. STATUS SURAT SAYA (WARGA)
    // ------------------------------------------------------------
    if ($role === 'warga' && preg_match('/\b(surat|pengajuan)\b/i', $q) && !preg_match('/\b(pengaduan|aduan|laporan|keluhan)\b/i', $q) && preg_match('/\b(status|saya|aku|ku|gw|gue|sy|suratku)\b/i', $q)) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if (empty($surat['surat_saya'])) {
            return "Kamu belum pernah mengajukan surat nih. Mau bikin surat sekarang? Ketik aja *\"Buat Surat Domisili\"* 😊";
        }
        $out = "📄 **Status Pengajuan Surat Kamu**:\n\n";
        foreach ($surat['surat_saya'] as $i => $s) {
            $st = strtolower(trim($s['status'] ?? 'pending'));
            $badgeSurat = ($st === 'pending') ? '⏳ PENDING (Menunggu Persetujuan)' : (($st === 'disetujui') ? '✅ DISETUJUI' : '❌ DITOLAK');
            $statusEmail = !empty($s['email_sent_at']) ? '✉️ Terkirim (' . date('d/m/Y H:i', strtotime($s['email_sent_at'])) . ')' : '📭 Belum dikirim';
            $tgl = date('d M Y', strtotime($s['created_at']));
            $out .= ($i + 1) . ". **{$s['judul']}**\n";
            $out .= "   • Nomor Surat: `{$s['nomor_surat']}`\n";
            $out .= "   • Status Surat: $badgeSurat\n";
            $out .= "   • Status Email: $statusEmail\n";
            $out .= "   • Tanggal Pengajuan: $tgl\n\n";
        }
        $out .= "💡 *Catatan:* File surat PDF dapat diunduh atau dikirim ke email kamu setelah status surat **DISETUJUI** oleh petugas.\n";
        return $out . "*Pantau selengkapnya di menu **Buat Surat** ya!*";
    }

    // ------------------------------------------------------------
    // 14. SURAT PALING BANYAK DIAJUKAN
    // ------------------------------------------------------------
    if (preg_match('/\b(surat)\b/i', $q) && preg_match('/\b(terbanyak|paling banyak|sering|populer|banyak diajukan)\b/i', $q)) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if (!empty($surat['per_jenis'])) {
            $out = "📄 **Rekapitulasi Surat Masuk per Jenis ({$surat['scope']})**:\n\n";
            foreach ($surat['per_jenis'] as $j => $jml) {
                $out .= "• **Surat {$j}**: {$jml} pengajuan\n";
            }
            if ($surat['jenis_terbanyak']) {
                $out .= "\n💡 *Surat paling banyak diajukan warga: **Surat {$surat['jenis_terbanyak']['jenis']}** ({$surat['jenis_terbanyak']['jumlah']} pengajuan).*";
            }
            return $out;
        }
        return "Belum ada pengajuan surat yang tercatat di sistem. 🙏";
    }

    // ------------------------------------------------------------
    // 15. SURAT DITOLAK BULAN INI
    // ------------------------------------------------------------
    if (preg_match('/\b(surat|pengajuan)\b.*\b(ditolak|reject|gagal)\b/i', $q) && in_array($role, ['super_admin', 'desa', 'rw', 'rt'])) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if (!empty($surat['ditolak_bulan_ini'])) {
            $out = "📄 **Surat Ditolak Bulan " . date('F Y') . " ({$surat['scope']})**:\n\n";
            foreach ($surat['ditolak_bulan_ini'] as $i => $r) {
                $out .= ($i + 1) . ". **{$r['jenis_surat']}** - Pemohon: {$r['name']} (RT {$r['rt']}/RW {$r['rw']})\n";
            }
            return $out;
        }
        return "Kabar baik! Tidak ada surat yang ditolak di wilayah akses kamu pada bulan " . date('F Y') . ". ✅";
    }

    // ------------------------------------------------------------
    // 16. SYARAT PEMBUATAN SURAT
    // ------------------------------------------------------------
    if (preg_match('/\b(syarat|butuh|perlu)\b.*\b(buat|bikin|ajukan)\b.*\b(domisili|usaha|sktm|skck|ktp|kk)\b/i', $q) || preg_match('/surat\s+apa\s+saja\s+yang\s+bisa\s+diajukan/i', $q)) {
        $syarat_map = [
            'domisili' => "1. Fotokopi KTP Pemohon\n2. Fotokopi Kartu Keluarga (KK)\n3. Surat Pengantar RT/RW\n4. Mengisi formulir permohonan",
            'usaha'    => "1. Fotokopi KTP Pemohon\n2. Fotokopi Kartu Keluarga (KK)\n3. Surat Pengantar RT/RW\n4. Foto tempat/kegiatan usaha\n5. Mengisi formulir permohonan",
            'sktm'     => "1. Fotokopi KTP Pemohon\n2. Fotokopi Kartu Keluarga (KK)\n3. Surat Pengantar RT/RW\n4. Surat pernyataan tidak mampu\n5. Mengisi formulir permohonan",
            'skck'     => "1. Fotokopi KTP & KK\n2. Surat Pengantar RT/RW\n3. Fotokopi akta kelahiran\n4. Pas foto 4x6 latar merah (3 lembar)",
            'ktp'      => "1. Fotokopi Kartu Keluarga (KK)\n2. Surat Pengantar RT/RW\n3. Fotokopi akta kelahiran / ijazah\n4. Datang langsung ke kantor desa/kecamatan untuk rekam biometrik",
            'kk'       => "1. Surat Pengantar RT/RW\n2. KK lama (asli)\n3. Fotokopi buku nikah/akta perkawinan\n4. Fotokopi akta kelahiran anggota baru",
        ];
        $matched = null;
        foreach ($syarat_map as $key => $sy) {
            if (preg_match('/\b' . $key . '\b/i', $q)) {
                $matched = $key;
                break;
            }
        }
        if ($matched) {
            return "📋 **Syarat Membuat Surat " . strtoupper($matched) . "**:\n\n" . $syarat_map[$matched] . "\n\nSetelah dokumen siap, kamu bisa ajukan langsung via AI di sini atau buka menu **Buat Surat** ya! 😊";
        }
        return "📋 **Surat yang Dapat Diajukan Online di SIWARGA**:\n\n1. Surat Keterangan Domisili\n2. Surat Keterangan Usaha\n3. Surat Keterangan Tidak Mampu (SKTM)\n4. Surat Pengantar KTP\n5. Surat Pengantar SKCK\n\nKetik misalnya: *\"Syarat buat surat domisili\"*";
    }

    // ------------------------------------------------------------
    // 17. TEMPLATE SURAT
    // ------------------------------------------------------------
    if (preg_match('/\b(template|format)\s+surat\b/i', $q)) {
        $surat = AIDataHelper::getStatistikSurat($pdo, $user);
        if (!empty($surat['templates'])) {
            $out = "📋 **Template Surat yang Tersedia di SIWARGA**:\n\n";
            foreach ($surat['templates'] as $i => $t) {
                $out .= ($i + 1) . ". **{$t['nama']}** ({$t['judul']})\n";
            }
            return $out . "\nKamu bisa mengajukan langsung lewat menu **Buat Surat** atau ketik *\"Buat Surat [Jenis]\"* di sini!";
        }
    }

    // ------------------------------------------------------------
    // 18. FORM PEMBUATAN SURAT VIA AI
    // ------------------------------------------------------------
    $formSuratRes = prosesAIFormSurat($pertanyaan, $user, $pdo, $sessionId);
    if ($formSuratRes !== null) return $formSuratRes;

    // ------------------------------------------------------------
    // 19. PENGUMUMAN
    // ------------------------------------------------------------
    if (preg_match('/\b(pengumuman|info terbaru|informasi desa)\b/i', $q) && !preg_match('/\b(warga|surat|pengaduan|rt|rw)\b/i', $q)) {
        $p = AIDataHelper::getPengumuman($pdo, 5);
        if (preg_match('/\b(ada\s+berapa|berapa|jumlah|total)\s+(?:pengumuman|info)\b/i', $q)) {
            return "Saat ini terdapat **{$p['count']} pengumuman** resmi di sistem desa. 📢\n\nKamu bisa membaca detailnya di menu **Pengumuman**!";
        }
        if (!empty($p['list'])) {
            $out = "📢 **Pengumuman Resmi Terbaru ({$wilayah['desa_nama']})**:\n\n";
            foreach ($p['list'] as $i => $item) {
                $tgl = date('d M Y', strtotime($item['created_at']));
                $out .= ($i + 1) . ". **{$item['judul']}** ({$tgl})\n";
            }
            return $out . "\nBuka menu **Pengumuman** untuk membaca isi selengkapnya ya! 😊";
        }
        return "Belum ada pengumuman terbaru di desa saat ini. 📢";
    }

    // ------------------------------------------------------------
    // 20. VERIFIKASI AKUN SISTEM (FALLBACK)
    // ------------------------------------------------------------
    if (preg_match('/\b(verifikasi|pending|belum di?verifikasi|butuh verifikasi|membutuhkan verifikasi|perlu verifikasi|warga baru|nunggu verifikasi|menunggu verifikasi|akun pending)\b/i', $q) && !preg_match('/\b(surat|pengaduan|laporan|email|otp|api)\b/i', $q) && ($ctx['topic'] ?? '') !== 'surat' && !preg_match('/\b(surat|pengajuan|permohonan|pemohon)\b/i', $lastQ . ' ' . $lastAns)) {
        if ($role === 'warga') {
            return "🙏 Maaf, informasi **Verifikasi Akun** hanya dapat diakses oleh perangkat desa yang berwenang (RT/RW/Admin Desa/Super Admin).\n\nRole kamu: **Warga**\n\n💡 *Untuk memeriksa status verifikasi akun kamu sendiri, kamu dapat melihatnya di menu Profil atau Pengaturan.*";
        }

        $verif = AIDataHelper::getVerifikasiPending($pdo, $user);
        $roleNames = [
            'super_admin' => 'Super Admin',
            'desa'        => 'Admin Desa',
            'rw'          => 'Ketua RW',
            'rt'          => 'Ketua RT',
            'warga'       => 'Warga',
        ];

        $penjelasanStatus = 
            "📌 **Perbedaan Status Verifikasi vs Status Aktif/Nonaktif Akun**:\n" .
            "• **Status Verifikasi (`status_verifikasi`)**: Menunjukkan tahap persetujuan pendaftaran akun (`Pending`, `Aktif`, `Ditolak`). Akun membutuhkan verifikasi hanya jika berstatus **Pending**.\n" .
            "• **Status Akun (Aktif/Nonaktif)**: Akun yang telah diverifikasi berstatus **Aktif**, sedangkan akun yang belum diverifikasi berstatus **Pending** (belum aktif) atau **Ditolak**.\n" .
            "*(Catatan: Total {$verif['total_users']} akun adalah jumlah seluruh pengguna terdaftar di sistem, BUKAN jumlah akun yang membutuhkan verifikasi).*";

        if ($verif['count'] === 0) {
            return "📋 **Verifikasi Akun**:\n\n" .
                   "Saat ini **tidak ada akun yang membutuhkan verifikasi (0 akun berstatus Pending)** di wilayah **{$verif['scope']}**. Seluruh {$verif['total_users']} akun terdaftar dari semua role telah berstatus **Aktif (Terverifikasi)**! 🎉\n\n" .
                   $penjelasanStatus;
        }

        $out = "📋 **Daftar Akun Belum Diverifikasi**:\n\n" .
               "Di wilayah **{$verif['scope']}**, terdapat **{$verif['count']} akun** yang belum diverifikasi:\n\n";
        foreach ($verif['list'] as $i => $u) {
            $rLabel = $roleNames[$u['role']] ?? ucfirst($u['role']);
            $stVerif = ($u['status_verifikasi'] === 'pending') ? '⏳ Pending (Belum Diverifikasi)' : ucfirst($u['status_verifikasi']);
            $out .= ($i + 1) . ". **" . htmlspecialchars($u['name']) . "**\n" .
                    "   • **Role**: $rLabel\n" .
                    "   • **Status Verifikasi**: $stVerif\n" .
                    "   • **Wilayah**: RT " . ($u['rt'] ?: '-') . " / RW " . ($u['rw'] ?: '-') . "\n\n";
        }
        return $out . $penjelasanStatus . "\n\n💡 *Silakan buka menu **Verifikasi Warga** atau **Manajemen User** untuk memproses persetujuannya ya.*";
    }

    // ------------------------------------------------------------
    // 20B. AKUN / PROFIL BELUM LENGKAP
    // ------------------------------------------------------------
    $isAkunBelumLengkapQuery = preg_match('/\b(belum\s+lengkap|kurang\s+lengkap|belum\s+melengkapi|masih\s+kurang|data\s+kurang|kurang|kelengkapan)\b/i', $q)
        && preg_match('/\b(akun|profil|warga|data|pengguna|user|orang|diri)(?:nya)?\b/i', $q);

    if ($isAkunBelumLengkapQuery) {
        $incomplete = AIDataHelper::getAkunBelumLengkap($pdo, $user);
        $wargaKurang = $incomplete['warga_kurang_list'];
        $semuaKurang = $incomplete['semua_kurang_list'];

        $isTanyaDaftar = preg_match('/\b(siapa|siapa saja|tampilkan|daftar|list|sebutkan|mana|rincian)\b/i', $q);
        $isKhususWarga = preg_match('/\b(warga|penduduk)\b/i', $q) && !preg_match('/\b(semua akun|seluruh akun|pengguna|user|pengurus)\b/i', $q);

        if ($role === 'warga') {
            if ($isTanyaDaftar) {
                return "Demi menjaga kerahasiaan dan privasi data kependudukan, akun Warga tidak diizinkan melihat daftar profil akun pengguna lain. 🙏\n\nKamu dapat memeriksa dan memperbarui kelengkapan profil pribadi kamu sendiri kapan saja di menu **Pengaturan**! 😊";
            }
            if ($isKhususWarga) {
                $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
                return "Berdasarkan data kependudukan saat ini, seluruh **{$kependudukan['total_warga']} warga** terdaftar telah melengkapi data profil mereka (Nama, Kontak, Wilayah RT/RW, dan Jenis Kelamin). Tidak ada profil warga yang belum lengkap! 🎉";
            }
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            return "Untuk kategori **warga**, seluruh **{$kependudukan['total_warga']} warga terdaftar** telah melengkapi data profilnya. Namun secara keseluruhan sistem, terdapat **" . count($semuaKurang) . " akun** (terutama akun perangkat desa/RT/RW) yang belum melengkapi nomor telepon atau jenis kelamin di profilnya.";
        }

        if ($isTanyaDaftar) {
            if ($isKhususWarga) {
                if (count($wargaKurang) === 0) {
                    $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
                    return "Kabar baik! Di wilayah **{$kependudukan['scope']}**, seluruh **{$kependudukan['total_warga']} warga terdaftar** telah melengkapi data profil mereka (Nama, No. Telepon/HP, RT, RW, dan Jenis Kelamin). Tidak ada data warga yang masih kurang saat ini. 🎉";
                }
                $out = "📋 **Daftar Warga dengan Data Belum Lengkap ({$kependudukan['scope']})**:\nTotal: **" . count($wargaKurang) . " warga**:\n\n";
                foreach ($wargaKurang as $i => $w) {
                    $out .= ($i + 1) . ". **{$w['name']}** (RT {$w['rt']} / RW {$w['rw']})\n   • Data kurang: {$w['missing_str']}\n";
                }
                return $out;
            }

            if (count($semuaKurang) === 0) {
                $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
                return "Kabar baik! Seluruh akun pengguna di wilayah **{$kependudukan['scope']}** sudah melengkapi data profil mereka. 🎉";
            }
            $out = "📋 **Daftar Akun Pengguna dengan Data Belum Lengkap ({$kependudukan['scope']})**:\nTotal: **" . count($semuaKurang) . " akun**:\n\n";
            foreach ($semuaKurang as $i => $u) {
                $roleBadge = strtoupper($u['role']);
                $wilayahStr = (!empty($u['rt']) && !empty($u['rw'])) ? " (RT {$u['rt']} / RW {$u['rw']})" : ((!empty($u['rw'])) ? " (RW {$u['rw']})" : "");
                $out .= ($i + 1) . ". **{$u['name']}** - Role: `{$roleBadge}`{$wilayahStr}\n   • Data kurang: {$u['missing_str']}\n";
            }
            $out .= "\n💡 *Pengguna dapat melengkapi profil secara mandiri melalui menu **Pengaturan**.*";
            return $out;
        }

        if ($isKhususWarga) {
            if (count($wargaKurang) === 0) {
                $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
                return "Kabar baik! Untuk wilayah **{$kependudukan['scope']}**, seluruh **{$kependudukan['total_warga']} warga terdaftar** telah melengkapi profil data pokoknya (Nama, Kontak Telepon/HP, Wilayah RT/RW, dan Jenis Kelamin). Saat ini **tidak ada (0) warga** yang belum melengkapi profil! 🎉";
            }
            $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
            return "Di wilayah **{$kependudukan['scope']}**, saat ini terdapat **" . count($wargaKurang) . " warga** yang belum melengkapi data profilnya dari total {$kependudukan['total_warga']} warga terdaftar.";
        }

        $kependudukan = AIDataHelper::getStatistikKependudukan($pdo, $user);
        return "📊 **Status Kelengkapan Profil Pengguna ({$kependudukan['scope']})**:\n\n• **Kategori Warga**: Seluruh **{$kependudukan['total_warga']} warga terdaftar** telah lengkap datanya.\n• **Akun Belum Lengkap**: Terdapat **" . count($semuaKurang) . " akun pengguna** (terutama pengurus RT/RW/Desa) yang belum melengkapi nomor telepon/HP atau jenis kelamin.\n\nKetik *\"Tampilkan akun dengan profil belum lengkap\"* untuk melihat rincian daftarnya! 📋";
    }

    // ------------------------------------------------------------
    // 21. DAFTAR WARGA (DENGAN PROTEKSI PRIVASI WARGA LAIN)
    // ------------------------------------------------------------
    $isDaftarWargaQuery = preg_match('/\b(daftar|list|sebutkan|nama|siapa saja|lihat|tampilkan)\b/i', $q) && preg_match('/\b(warga|penduduk)\b/i', $q);
    if ($isDaftarWargaQuery) {
        if ($role === 'warga') {
            return "Maaf ya, demi menjaga kerahasiaan dan privasi data kependudukan, akun Warga tidak diizinkan melihat daftar nama warga lain. 🙏\n\nNamun, kamu tetap bisa menanyakan **jumlah total warga** atau **info RT/RW kamu** kapan saja! 😊";
        }
        $filterRt = null; $filterRw = null; $filterJk = null;
        if (preg_match('/\brt\s*(\d+)\b/i', $q, $m)) $filterRt = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        if (preg_match('/\brw\s*(\d+)\b/i', $q, $m)) $filterRw = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        if (preg_match('/\b(rt saya|rt aku|rt ku)\b/i', $q)) { $filterRt = $wilayah['rt_nomor']; $filterRw = $wilayah['rw_nomor']; }
        if (preg_match('/\b(rw saya|rw aku|rw ku)\b/i', $q)) $filterRw = $wilayah['rw_nomor'];
        if ($role === 'rt') {
            if ($filterRt !== null && $filterRt !== $wilayah['rt_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_rt', $user);
            }
            if ($filterRw !== null && $filterRw !== $wilayah['rw_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_rt', $user);
            }
        }
        if ($role === 'rw') {
            if ($filterRw !== null && $filterRw !== $wilayah['rw_nomor']) {
                return RoleCapabilities::rejectMessage($role, 'wilayah_luar_rw', $user);
            }
        }
        if ($role === 'rw' && $filterRw === null && $wilayah['rw_nomor'] !== '-') $filterRw = $wilayah['rw_nomor'];
        if ($role === 'rt' && $filterRt === null && $wilayah['rt_nomor'] !== '-') { $filterRt = $wilayah['rt_nomor']; $filterRw = $wilayah['rw_nomor']; }

        if (preg_match('/\b(perempuan|wanita|cewek)\b/i', $q)) $filterJk = 'P';
        if (preg_match('/\b(laki[\s\-]?laki|pria|cowok)\b/i', $q)) $filterJk = 'L';

        $warga_list = getDaftarWarga($pdo, ['rt' => $filterRt, 'rw' => $filterRw, 'jk' => $filterJk, 'limit' => 50]);
        if (empty($warga_list)) {
            $scope = "wilayah tersebut";
            if ($filterRt) $scope = "RT $filterRt";
            if ($filterRw && !$filterRt) $scope = "RW $filterRw";
            $jkStr = ($filterJk === 'P') ? ' perempuan' : (($filterJk === 'L') ? ' laki-laki' : '');
            return "Sepertinya belum ada warga{$jkStr} yang terdaftar di $scope nih.";
        }
        $jkLabel = ($filterJk === 'P') ? ' Perempuan' : (($filterJk === 'L') ? ' Laki-Laki' : '');
        $judul = ($filterRt && $filterRw) ? "Daftar Warga{$jkLabel} RT $filterRt / RW $filterRw" : (($filterRw) ? "Daftar Warga{$jkLabel} RW $filterRw" : "Daftar Warga{$jkLabel} {$wilayah['desa_nama']}");

        $out = "Oke, ini **{$judul}** yang terdata di sistem:\n\nTotal: **" . count($warga_list) . " warga** *(NIK & kata sandi disembunyikan demi privasi)*:\n\n";
        foreach ($warga_list as $i => $w) {
            $out .= ($i + 1) . ". **" . htmlspecialchars($w['name']) . "** (RT " . htmlspecialchars($w['rt'] ?? '-') . " / RW " . htmlspecialchars($w['rw'] ?? '-') . ")\n";
        }
        return $out;
    }

    // ------------------------------------------------------------
    // 22. PENCARIAN IDENTITAS / PENGGUNA / WARGA
    // ------------------------------------------------------------
    $isFollowUpQuery = preg_match('/\b(siapa saja|siapa orangnya|siapa mereka|orangnya|pemohon|pemohonnya|ketuanya|warganya|bagaimana|gimana|kalau|kalo|belum lengkap|kurang lengkap|belum melengkapi|masih kurang|kurang|data diri|profil)\b/i', $q);
    if (!$isFollowUpQuery && preg_match('/(?:cari|carikan|info|detail|data|siapa|tampilkan|akun|user)\s+(.+)/i', $q, $m) && !preg_match('/(?:fitur|menu|layanan|pengumuman|surat|pengaduan|aduan|rt|rw|saya|kamu|desa|kantor)/i', $m[1])) {
        $kata_kunci = trim($m[1]);
        $stopwords = ['data', 'nama', 'warga', 'user', 'akun', 'pengguna', 'yang', 'bernama', 'dengan', 'itu', 'dari', 'untuk', 'milik', 'punya', 'orang', 'tentang', 'saja', 'aja', 'orangnya', 'mereka', 'pemohon', 'pemohonnya', 'lengkap', 'belum', 'kurang', 'profil', 'diri'];
        foreach ($stopwords as $word) {
            $kata_kunci = preg_replace('/\b' . $word . '\b/i', '', $kata_kunci);
        }
        $kata_kunci = trim(preg_replace('/\s+/', ' ', $kata_kunci));

        $nonNames = ['saja', 'aja', 'orangnya', 'orang', 'pemohon', 'pemohonnya', 'mereka', 'ketua', 'ketuanya', 'pengaju', 'pengajunya', 'terdaftar', 'semuanya', 'semua', 'lengkap', 'belum', 'kurang', 'profil', 'diri'];
        if (!in_array(strtolower($kata_kunci), $nonNames) && strlen($kata_kunci) >= 2) {
            return AIDataHelper::cariIdentitas($pdo, $user, $kata_kunci);
        }
    }

    // ------------------------------------------------------------
    // 23. INFO RT / RW SPESIFIK & DAFTAR RT
    // ------------------------------------------------------------
    if (preg_match('/\b(?:info|ketua|warga|jumlah|siapa)(?:\s+ketua)?\s+(?:di\s+)?rt\s*(\d+)(?:\s+rw\s*(\d+))?/i', $q, $m) && !preg_match('/\b(daftar|list|pengaduan|surat|statistik|cari|verifikasi)\b/i', $q)) {
        $rt_target = (isset($m[1]) && $m[1] !== '') ? str_pad($m[1], 2, '0', STR_PAD_LEFT) : null;
        
        $rw_target = (isset($m[2]) && $m[2] !== '') ? str_pad($m[2], 2, '0', STR_PAD_LEFT) : (in_array($role, ['super_admin', 'desa']) ? null : $wilayah['rw_nomor']);
        
        if ($role === 'rw' && $rt_target === null) {
            $stmt = $pdo->prepare("SELECT rt.nomor, rt.ketua, u.no_telp, u.email, (SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt),2,'0') = LPAD(TRIM(rt.nomor),2,'0') AND LPAD(TRIM(rw),2,'0') = LPAD(TRIM(?),2,'0')) AS jumlah_warga FROM rt JOIN rw ON rt.rw_id = rw.id LEFT JOIN users u ON u.rt = rt.nomor AND u.rw = rw.nomor AND u.role = 'rt' WHERE LPAD(TRIM(rw.nomor),2,'0') = ? ORDER BY rt.nomor");
            $stmt->execute([$rw_target, $rw_target]);
            $rt_list_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($rt_list_data) > 0) {
                $out = "📋 **Daftar RT di Wilayah RW {$rw_target}**:\n\n";
                foreach ($rt_list_data as $rt_data) {
                    $out .= "🏠 **RT {$rt_data['nomor']}**\n   • Ketua RT: **{$rt_data['ketua']}**\n   • No. Telp: **" . ($rt_data['no_telp'] ?: '-') . "**\n   • Jumlah Warga: **{$rt_data['jumlah_warga']} orang**\n\n";
                }
                return $out;
            }
        }

        if ($rt_target === null) $rt_target = $wilayah['rt_nomor'];
        if ($role === 'warga' && $rt_target !== $wilayah['rt_nomor']) return "Maaf, sebagai Warga, kamu hanya dapat melihat info RT tempat kamu terdaftar (RT {$wilayah['rt_nomor']} / RW {$wilayah['rw_nomor']}). 🙏";
        if ($role === 'rt' && $rt_target !== $wilayah['rt_nomor']) return "Maaf, kamu hanya dapat mengakses info RT {$wilayah['rt_nomor']}. 🙏";
        if ($role === 'rw' && $rw_target !== $wilayah['rw_nomor']) return "Maaf, kamu hanya dapat mengakses info di wilayah RW {$wilayah['rw_nomor']}. 🙏";

        $sql = "SELECT rt.nomor, rw.nomor AS rw_nomor, rt.ketua, u.no_telp, u.email, (SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rt),2,'0') = LPAD(TRIM(rt.nomor),2,'0')) AS jumlah_warga FROM rt LEFT JOIN rw ON rt.rw_id = rw.id LEFT JOIN users u ON u.rt = rt.nomor AND u.rw = rw.nomor AND u.role = 'rt' WHERE LPAD(TRIM(rt.nomor),2,'0') = ?";
        $params = [$rt_target];
        if ($rw_target) { $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?"; $params[] = $rw_target; }
        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rt_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rt_data) {
            $rwLabel = $rt_data['rw_nomor'] ? " / RW {$rt_data['rw_nomor']}" : "";
            return "📋 **Info RT {$rt_data['nomor']}{$rwLabel}**:\n\n• Ketua RT: **{$rt_data['ketua']}**\n• Kontak: **" . ($rt_data['no_telp'] ?: '-') . "**\n• Email: **" . ($rt_data['email'] ?: '-') . "**\n• Jumlah Warga: **{$rt_data['jumlah_warga']} orang**";
        }
        return "Data RT {$rt_target} tidak ditemukan di database. 🙏";
    }

    if (preg_match('/(?:siapa|nama|ketua)\s+(?:ketua\s+)?rt\s*(\d+)(?:\s+rw\s*(\d+))?/i', $q, $mRt) || preg_match('/rt\s*(\d+)\s+(?:siapa|ketuanya|ketua)/i', $q, $mRt)) {
        $rt_target = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);
        $rw_target = (isset($mRt[2]) && $mRt[2] !== '') ? str_pad($mRt[2], 2, '0', STR_PAD_LEFT) : null;

        if ($role === 'warga' && $rt_target !== $wilayah['rt_nomor']) {
            return "Maaf, sebagai Warga, kamu hanya dapat melihat info RT tempat kamu terdaftar (RT {$wilayah['rt_nomor']} / RW {$wilayah['rw_nomor']}). 🙏";
        }
        if ($role === 'rt' && $rt_target !== $wilayah['rt_nomor']) {
            return "Maaf, kamu hanya dapat mengakses info RT {$wilayah['rt_nomor']}. 🙏";
        }

        $sql = "SELECT rt.nomor AS rt_nomor, rw.nomor AS rw_nomor, rt.ketua FROM rt LEFT JOIN rw ON rt.rw_id = rw.id WHERE LPAD(TRIM(rt.nomor),2,'0') = ?";
        $params = [$rt_target];
        
        if ($rw_target) {
            $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?";
            $params[] = $rw_target;
        }
        if ($role === 'rw') {
            $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?";
            $params[] = $wilayah['rw_nomor'];
        } elseif ($role === 'warga' || $role === 'rt') {
             $sql .= " AND LPAD(TRIM(rw.nomor),2,'0') = ?";
             $params[] = $wilayah['rw_nomor'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            $out = "";
            foreach ($results as $r) {
                $rw_str = $r['rw_nomor'] ? " / RW {$r['rw_nomor']}" : "";
                $out .= "Ketua **RT {$r['rt_nomor']}{$rw_str}** adalah **{$r['ketua']}**.\n";
            }
            return trim($out);
        }
        return "Data RT {$rt_target} tidak ditemukan di database. 🙏";
    }

    if (preg_match('/(?:siapa|nama|ketua)\s+(?:ketua\s+)?rw\s*(\d+)/i', $q, $mRw) || preg_match('/rw\s*(\d+)\s+(?:siapa|ketuanya|ketua)/i', $q, $mRw)) {
        $rw_target = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT nomor, ketua FROM rw WHERE LPAD(TRIM(nomor),2,'0') = ? LIMIT 1");
        $stmt->execute([$rw_target]);
        $rw_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rw_data) {
            return "Ketua **RW {$rw_data['nomor']}** adalah **{$rw_data['ketua']}**.";
        }
        return "Data RW {$rw_target} tidak ditemukan di database. 🙏";
    }

    if (preg_match('/\b(?:info|detail|warga|jumlah|siapa)(?:\s+ketua)?\s+(?:di\s+)?rw\s*(\d+)/i', $q, $m) && !preg_match('/\b(daftar|list|pengaduan|surat|statistik|cari|verifikasi)\b/i', $q)) {
        $rw_target = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        if (in_array($role, ['warga', 'rt']) && $rw_target !== $wilayah['rw_nomor']) {
            return "Maaf, info detail ketua dan kontak RW lain hanya dapat diakses oleh Admin Desa atau Ketua RW yang bersangkutan. 🙏\n\nRW kamu terdaftar di: **RW {$wilayah['rw_nomor']}**.";
        }
        if ($role === 'rw' && $rw_target !== $wilayah['rw_nomor']) return "Maaf, kamu hanya memiliki akses untuk wilayah RW {$wilayah['rw_nomor']}. 🙏";

        $stmt = $pdo->prepare("SELECT rw.nomor, rw.ketua, u.no_telp, u.email, (SELECT COUNT(*) FROM users WHERE role='warga' AND LPAD(TRIM(rw),2,'0') = LPAD(TRIM(rw.nomor),2,'0')) AS jumlah_warga, (SELECT COUNT(DISTINCT nomor) FROM rt WHERE rw_id = rw.id) AS jumlah_rt FROM rw LEFT JOIN users u ON u.rw = rw.nomor AND u.role = 'rw' WHERE LPAD(TRIM(rw.nomor),2,'0') = ? LIMIT 1");
        $stmt->execute([$rw_target]);
        $rw_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rw_data) {
            return "📋 **Info RW {$rw_data['nomor']}**:\n\n• Ketua RW: **{$rw_data['ketua']}**\n• Kontak: **" . ($rw_data['no_telp'] ?: '-') . "**\n• Email: **" . ($rw_data['email'] ?: '-') . "**\n• Jumlah RT: **{$rw_data['jumlah_rt']} RT**\n• Jumlah Warga: **{$rw_data['jumlah_warga']} orang**";
        }
        return "Data RW {$rw_target} tidak ditemukan di database. 🙏";
    }

    // ------------------------------------------------------------
    // 24. INFO PROFIL SAYA
    // ------------------------------------------------------------
    if (preg_match('/\b(siapa saya|nama saya|saya siapa|aku siapa|siapa aku|info saya|profil saya|data saya|akun saya|akunku|profile|profilku)\b/i', $q) && !preg_match('/\b(ketua|kepala)\b/i', $q)) {
        $roleLabel = RoleCapabilities::label($role);
        $out = "Berikut profil kamu yang tercatat di sistem:\n\n• **Nama**: " . htmlspecialchars($user['name']) . "\n• **Email**: " . htmlspecialchars($user['email']) . "\n• **Jabatan/Role**: **$roleLabel**\n";
        if ($wilayah['rt_nomor'] !== '-') $out .= "• **RT**: " . htmlspecialchars($wilayah['rt_nomor']) . "\n";
        if ($wilayah['rw_nomor'] !== '-') $out .= "• **RW**: " . htmlspecialchars($wilayah['rw_nomor']) . "\n";
        if ($wilayah['rt_ketua'] !== '-') $out .= "• **Ketua RT**: " . htmlspecialchars($wilayah['rt_ketua']) . "\n";
        if ($wilayah['rw_ketua'] !== '-') $out .= "• **Ketua RW**: " . htmlspecialchars($wilayah['rw_ketua']) . "\n";
        return $out;
    }

    // ------------------------------------------------------------
    // 25. MENU SIDEBAR (FALLBACK)
    // ------------------------------------------------------------
    if (preg_match('/\b(menu|sidebar)\b/i', $q)) {
        $menuListRole = AIDataHelper::getMenuSidebarByRole($role);
        $totalMenuRole = count($menuListRole);
        $out = "Sebagai **" . RoleCapabilities::label($role) . "**, kamu memiliki akses ke **{$totalMenuRole} menu sidebar** pada antarmuka SIWARGA:\n\n";
        foreach ($menuListRole as $i => $fitur) {
            $out .= ($i + 1) . ". **{$fitur}**\n";
        }
        return $out . "\n📌 *Catatan: Menu sidebar adalah navigasi antarmuka, berbeda dengan Fitur Aplikasi (12 fitur) dan Layanan Desa.*";
    }

    // ------------------------------------------------------------
    // 26. PELAYANAN DESA (FALLBACK)
    // ------------------------------------------------------------
    if (preg_match('/\b(layanan|pelayanan)\b/i', $q) && !preg_match('/\b(jam|buka)\b/i', $q)) {
        $pel = AIDataHelper::getPelayananDesa($pdo);
        if (!empty($pel)) {
            $out = "📋 **Katalog Layanan Administrasi Desa ({$wilayah['desa_nama']})** (Total: **" . count($pel) . " layanan**):\n\n";
            foreach ($pel as $i => $l) {
                $out .= ($i + 1) . ". **{$l['nama']}**\n";
                if (!empty($l['deskripsi'])) {
                    $out .= "   • " . $l['deskripsi'] . "\n";
                }
            }
            return $out . "\nKamu bisa mengajukan pembuatan surat secara online melalui menu **Buat Surat** atau ketik *\"Syarat buat surat [nama layanan]\"* di sini!";
        }
    }

    // ------------------------------------------------------------
    // 27. SUPER ADMIN QUERIES
    // ------------------------------------------------------------
    if ($role === 'super_admin') {
        if (preg_match('/\b(struktur|tabel)\s+(database|db)\b/i', $q) || preg_match('/\b(daftar\s+tabel|list\s+tabel|daftar\s+table|list\s+table|semua\s+tabel)\b/i', $q)) {
            return DatabaseInspector::listTables($pdo);
        }
        if (preg_match('/\bkolom\s+tabel\s+(\w+)\b/i', $q, $m)) {
            return DatabaseInspector::describeTable($pdo, $m[1]);
        }
        if (preg_match('/\b(semua|daftar|list)\s+(user|pengguna|akun)\b/i', $q)) {
            $recent_users = $pdo->query("SELECT name, role, created_at FROM users ORDER BY created_at DESC LIMIT 15")->fetchAll();
            $out = "📋 **15 Pengguna Terakhir Terdaftar di Sistem**:\n\n";
            foreach ($recent_users as $i => $u) {
                $out .= ($i + 1) . ". **{$u['name']}** (" . ucfirst($u['role']) . ") - " . date('d M Y', strtotime($u['created_at'])) . "\n";
            }
            return $out;
        }
        if (preg_match('/\b(error|log|kendala|masalah|bug)\b.*\b(sistem|ai|database|aplikasi)\b/i', $q)) {
            $logFile = __DIR__ . '/../../storage/logs/error.log';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $recent_logs = array_slice($logs, -10);
                $out = "🚨 **10 Log Error Terakhir**:\n\n";
                foreach ($recent_logs as $log) {
                    $out .= "• `" . substr(trim($log), 0, 100) . "...`\n";
                }
                return $out;
            }
            return "✅ Tidak ada catatan file error.log saat ini. Sistem berjalan stabil.";
        }
    }

    // ------------------------------------------------------------
    // 28. INFO KANTOR DESA, JAM BUKA, KONTAK
    // ------------------------------------------------------------
    if (preg_match('/\b(jam buka|jam kerja|jam operasional|kantor buka|kapan buka|buka jam|jam kantor|jam pelayanan)\b/i', $q)) {
        return "📅 **Jam Operasional Kantor {$wilayah['desa_nama']}**:\n\n• Senin - Jumat: **08.00 - 15.00 WIB**\n• Sabtu: **08.00 - 12.00 WIB**\n• Minggu & Hari Libur Nasional: **Tutup** (Layanan AI tetap standby 24 jam) 😊";
    }
    if (preg_match('/\b(alamat kantor|alamat desa|lokasi kantor|letak kantor|kantor dimana|dimana kantor)\b/i', $q)) {
        return "📍 **Lokasi Kantor Desa**:\n\n🏛️ **{$wilayah['desa_nama']}**\n📌 Alamat: {$wilayah['desa_alamat']}\n📞 Telepon: {$wilayah['desa_telp']}\n📧 Email: {$wilayah['desa_email']}";
    }
    if (preg_match('/\b(kepala desa|kades|lurah|ketua desa|pemimpin desa|pimpinan desa|siapa pemimpin|siapa pimpinan)\b/i', $q)) {
        return "👑 **Informasi Kepala Desa {$wilayah['desa_nama']}**:\n\n• **Nama Lengkap**: **H. SUPARMAN, S.Sos** (Bapak Suparman)\n• **Jabatan**: **Kepala Desa {$wilayah['desa_nama']}**\n• **Wilayah Tugas**: {$wilayah['desa_nama']}\n• **Kantor Resmi**: {$wilayah['desa_alamat']}\n• **Kontak Resmi**: {$wilayah['desa_telp']}";
    }
    if (preg_match('/\b(kontak desa|telepon desa|telp desa|nomor desa|hubungi desa|wa desa|email desa)\b/i', $q)) {
        return "📞 **Kontak Resmi Kantor {$wilayah['desa_nama']}**:\n\n☎️ Telepon: {$wilayah['desa_telp']}\n📧 Email: {$wilayah['desa_email']}\n🏛️ Alamat: {$wilayah['desa_alamat']}";
    }
    if (preg_match('/\b(cara|langkah|panduan|bagaimana|gimana|mau|ingin)\b.*\b(lapor|mengadu|pengaduan|keluhan|aduan)\b/i', $q) || preg_match('/\bcara\s+lapor\b/i', $q)) {
        return "📌 **Langkah Menyampaikan Pengaduan di SIWARGA**:\n\n1. Buka menu **Pengaduan** di sidebar.\n2. Klik tombol **+ Buat Pengaduan**.\n3. Masukkan judul laporan, kategori, prioritas, serta lokasi kejadian.\n4. Unggah foto bukti (jika ada) dan pilih apakah ingin anonim.\n5. Klik **Kirim Pengaduan**.\n\nLaporanmu akan langsung diteruskan ke Ketua RT, Ketua RW, dan Admin Desa untuk ditindaklanjuti! 😊";
    }

    // ------------------------------------------------------------
    // 29. SAPAAN RAMAH & TERIMA KASIH
    // ------------------------------------------------------------
    if (preg_match('/\b(halo|hai|hello|hi|hei|assalam|salam|pagi|siang|sore|malam|permisi)\b/i', $q) && strlen($q) < 30) {
        if (preg_match('/\bpagi\b/i', $q)) $sapaan = "Selamat pagi";
        elseif (preg_match('/\bsiang\b/i', $q)) $sapaan = "Selamat siang";
        elseif (preg_match('/\bsore\b/i', $q)) $sapaan = "Selamat sore";
        elseif (preg_match('/\bmalam\b/i', $q)) $sapaan = "Selamat malam";
        else {
            $jam = (int)date('H');
            $sapaan = ($jam < 11) ? "Selamat pagi" : (($jam < 15) ? "Selamat siang" : (($jam < 19) ? "Selamat sore" : "Selamat malam"));
        }
        return "$sapaan, **$nama_depan**! 👋\n\nAku SIWARGA AI siap membantu kebutuhan informasi dan administrasi desa kamu. Ada yang bisa aku bantu hari ini? 😊";
    }
    if (preg_match('/\b(terima kasih|makasih|thanks|thank you|tq|thx|syukron|matur nuwun)\b/i', $q) && strlen($q) < 30) {
        return "Sama-sama, senang bisa membantu! Jika ada hal lain seputar administrasi desa yang ingin ditanyakan, jangan ragu untuk bertanya lagi ya. 😊";
    }
    if (preg_match('/\b(bantu|help|bisa apa|kemampuan|panduan|tolong bantu)\b/i', $q)) {
        return RoleCapabilities::intro($role);
    }

    return null;
}

// ============================================================
// LAPIS 2A: GEMINI AI (MULTI-KEY & AUTO-FALLBACK MODEL)
// ============================================================
function tanyaGeminiFailover($pertanyaan, $user, $keyChains = [], $temperature = 0.5, $customPrompt = '', $fallbackMsg = '', $sessionId = null) {
    if (empty($keyChains)) {
        return "🚨 API Key AI belum dikonfigurasi. Hubungi Super Admin untuk menyetel API Key di menu Konfigurasi AI.";
    }

    global $pdo;
    $nama_depan = explode(' ', $user['name'])[0];
    $geminiPrompt = AIDataHelper::buildGeminiPromptContext($pdo, $user, $customPrompt, $sessionId);
    $system_context = $geminiPrompt . "\n\nPertanyaan terkini dari {$nama_depan}: " . $pertanyaan;

    $payload = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => $system_context]]]
        ],
        "generationConfig" => [
            "temperature" => max(0.0, min(1.0, (float)$temperature))
        ]
    ];
    $body = json_encode($payload);

    $lastErrorCode = 0;
    $lastErrorMsg = '';
    $triedKeys = [];

    // Prioritaskan key yang sehat (tidak sedang masa cooldown karena 429/500/503)
    if (class_exists('AIKeyCooldown')) {
        $keyChains = AIKeyCooldown::prioritizeKeyChains($keyChains);
    }

    // Iterasi seluruh key yang terdaftar (Key Utama, lalu berurutan ke seluruh Key Cadangan)
    foreach ($keyChains as $chainIndex => $chain) {
        $apiKey = trim($chain['key'] ?? '');
        $keyLabel = $chain['type'] ?? ('Key #' . ($chainIndex + 1));

        if (empty($apiKey) || AIConfig::isMasked($apiKey)) continue;

        // Model yang akan dicoba untuk key ini: model prioritas key + 1 fallback model aman
        $primaryModelForKey = trim($chain['model'] ?? ($chain['models'][0] ?? 'gemini-3.6-flash'));
        $safeFallbackList = ['gemini-3.6-flash', 'gemini-3.5-flash', 'gemini-3-flash-preview', 'gemini-flash-lite-latest', 'gemini-3.1-flash-lite', 'gemini-flash-latest'];
        
        $candidateModels = [$primaryModelForKey];
        foreach ($safeFallbackList as $fallbackModel) {
            if ($fallbackModel !== $primaryModelForKey) {
                $candidateModels[] = $fallbackModel;
                break; // Maksimal 1 fallback model per key agar proses failover ke key berikutnya tetap cepat
            }
        }

        foreach ($candidateModels as $model) {
            if (strpos($apiKey, 'SIMULATE_429') !== false) {
                $http_code = 429;
                $resData = [
                    'error' => [
                        'code'    => 429,
                        'status'  => 'RESOURCE_EXHAUSTED',
                        'message' => 'Quota exceeded for quota metric \'Queries\' and limit \'Queries per day\' of service \'generativelanguage.googleapis.com\''
                    ]
                ];
                $response = json_encode($resData);
                $curl_error = '';
            } elseif (strpos($apiKey, 'SIMULATE_SUCCESS') !== false) {
                $http_code = 200;
                $resData = [
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => "Halo! Ini adalah respons sukses asisten SIWARGA AI melalui model {$model} setelah failover key."]
                                ]
                            ]
                        ]
                    ]
                ];
                $response = json_encode($resData);
                $curl_error = '';
            } else {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
                curl_setopt($ch, CURLOPT_TIMEOUT, 35);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                $resData = !empty($response) ? json_decode($response, true) : null;
            }

            if ($http_code === 200 && $resData) {
                if (isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
                    if (class_exists('AIKeyCooldown')) {
                        AIKeyCooldown::markSuccess($apiKey);
                    }
                    if ($chainIndex > 0) {
                        log_activity('ai_failover_success', "Berhasil menjawab menggunakan $keyLabel (model: $model) setelah failover.");
                    }
                    return trim($resData['candidates'][0]['content']['parts'][0]['text']);
                }
            }

            if ($curl_error) {
                $lastErrorMsg = "Jaringan: " . $curl_error;
            } else {
                $lastErrorMsg = $resData['error']['message'] ?? "HTTP $http_code";
            }

            // Deteksi komprehensif apakah kendala disebabkan kuota habis (429 / RESOURCE_EXHAUSTED)
            $isQuotaExceeded = ($http_code === 429) ||
                (isset($resData['error']['code']) && (int)$resData['error']['code'] === 429) ||
                (isset($resData['error']['status']) && $resData['error']['status'] === 'RESOURCE_EXHAUSTED') ||
                (isset($resData['error']['message']) && (stripos($resData['error']['message'], 'quota') !== false || stripos($resData['error']['message'], 'rate limit') !== false));

            // Deteksi apakah key tidak valid / auth error (400, 401, 403)
            $isAuthError = in_array($http_code, [400, 401, 403]) ||
                (isset($resData['error']['code']) && in_array((int)$resData['error']['code'], [400, 401, 403])) ||
                (isset($resData['error']['status']) && in_array($resData['error']['status'], ['PERMISSION_DENIED', 'UNAUTHENTICATED', 'INVALID_ARGUMENT']));

            $effectiveCode = $isQuotaExceeded ? 429 : $http_code;
            $triedKeys[$keyLabel] = $effectiveCode;
            $lastErrorCode = $effectiveCode;

            if (class_exists('AIKeyCooldown')) {
                AIKeyCooldown::markError($apiKey, $effectiveCode, $lastErrorMsg);
            }

            log_error('Gemini Failover', [
                'key_type' => $keyLabel,
                'model'    => $model,
                'code'     => $effectiveCode,
                'msg'      => substr($lastErrorMsg, 0, 100)
            ]);

            // Jika error adalah masalah kuota atau autentikasi key,
            // LANGSUNG hentikan percobaan model lain pada key ini dan BERALIH KE KEY CADANGAN BERIKUTNYA!
            if ($isQuotaExceeded || $isAuthError) {
                log_activity('ai_failover_switch', "Key $keyLabel mengalami kendala (HTTP $effectiveCode), otomatis mencoba key berikutnya.");
                break;
            }
        }
    }

    $totalKeysTried = count($triedKeys);
    $quotaCount = count(array_filter($triedKeys, fn($c) => $c === 429));

    if ($totalKeysTried > 0 && $quotaCount === $totalKeysTried) {
        return "Maaf, kuota AI (Gemini) saat ini sedang habis di seluruh API Key ($totalKeysTried key telah dicoba, semua terkena limit kuota 429). Sistem sedang menunggu reset kuota atau silakan hubungi Super Admin untuk menambah API Key cadangan. 🙏";
    }
    if ($quotaCount > 0) {
        return "Maaf, kuota AI (Gemini) saat ini sedang habis ($quotaCount dari $totalKeysTried key terkena limit 429). Sistem sedang menunggu reset kuota atau silakan hubungi Super Admin untuk menambah API Key cadangan. 🙏";
    }
    if ($lastErrorCode === 503) {
        return "Maaf, server AI sedang mengalami antrean beban tinggi (High Demand). Coba kirim pertanyaanmu lagi dalam beberapa saat ya! 🙏";
    }
    if (!empty($fallbackMsg)) {
        return $fallbackMsg;
    }
    return "🚨 Maaf, asisten AI sedang tidak dapat terhubung ($lastErrorCode). Coba beberapa saat lagi atau hubungi petugas desa.";
}

// ============================================================
// LAPIS 3: GEMINI VISION
// ============================================================
function analisisGambarGemini($pertanyaan, $base64_image, $mime_type, $user) {
    $aiCfg = AIConfig::get();
    $apiKey = $aiCfg['api_key'] ?: (getenv('GEMINI_API_KEY') ?: '');
    
    $models = array_values(array_unique(array_merge([$aiCfg['model'] ?: 'gemini-1.5-flash'], ['gemini-1.5-flash', 'gemini-1.5-pro'])));

    if (empty($apiKey)) return null;

    global $pdo;
    $nama_depan = explode(' ', $user['name'])[0];
    $role = $user['role'] ?? 'warga';
    $wilayah = getWilayahUser($user, $pdo);

    $system_context = "Kamu SIWARGA AI dari {$wilayah['desa_nama']}. User: $nama_depan (role: $role)\nTUGAS: Baca gambar terkait LAYANAN DESA.\nKalau gambar di luar konteks desa, tolak dengan sopan dan santai.\n\nPertanyaan: " . $pertanyaan;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $system_context],
                    ["inline_data" => ["mime_type" => $mime_type, "data" => $base64_image]]
                ]
            ]
        ]
    ];
    $body = json_encode($payload);

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
        }
    }
    return null;
}