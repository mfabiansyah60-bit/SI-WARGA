<?php
// app/Controllers/PengumumanController.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0); // <-- TAMBAHAN: Biar PHP gak mati di tengah jalan (Timeout)

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';
require_once __DIR__ . '/../Models/Notification.php'; 
require_once __DIR__ . '/../Helpers/Mailer.php';
require_once __DIR__ . '/../Services/WhatsAppNotificationService.php';

if (!isLoggedIn()) { header('Location: /login'); exit; }
$user = auth();
$role = $user['role'];
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

$isAdmin = in_array($role, ['super_admin', 'desa', 'rw', 'rt']);

// ============================================
// BUAT PENGUMUMAN
// ============================================
if ($action === 'buat' && $isAdmin) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $judul    = trim($_POST['judul'] ?? '');
        $isi      = trim($_POST['isi'] ?? '');
        $saluran  = trim($_POST['saluran'] ?? 'both');
        $lokasi   = trim($_POST['lokasi'] ?? '');
        $jadwal   = trim($_POST['jadwal'] ?? '');
        $imbauan  = trim($_POST['imbauan'] ?? '');
        $extraNotif = array_filter([
            'lokasi'  => $lokasi,
            'jadwal'  => $jadwal,
            'imbauan' => $imbauan,
        ]);
        if (!in_array($saluran, ['both', 'whatsapp', 'email'])) {
            $saluran = 'both';
        }

        if (empty($judul) || empty($isi)) {
            $_SESSION['flash'] = ['message' => '⚠️ Judul dan isi wajib diisi!', 'type' => 'error'];
        } else {
            try {
                // 1. Simpan Pengumuman dengan Pilihan Saluran Pengiriman
                $stmt = $pdo->prepare("INSERT INTO pengumuman (user_id, judul, isi, saluran) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $judul, $isi, $saluran]);
                $newPengumumanId = $pdo->lastInsertId();
                
                // 2. Notifikasi In-App untuk seluruh warga
                Notification::notifyByRole(
                    ['warga'],
                    'pengumuman_baru',
                    '📢 Pengumuman Baru',
                    'Info terbaru: ' . $judul,
                    '/pengumuman',
                    $user['id']
                );

                // 3. Ambil Data Kop Surat & Pengirim
                $nama_desa = 'Sukamaju'; 
                if (!empty($user['desa_id'])) {
                    $stmtDesa = $pdo->prepare("SELECT nama FROM desa WHERE id = ?");
                    $stmtDesa->execute([$user['desa_id']]);
                    $nama_desa = $stmtDesa->fetchColumn() ?: 'Sukamaju';
                }
                $nama_ketua = $user['name'] ?? 'Admin';
                if ($role === 'rt' && !empty($user['rt_id'])) {
                    $stmtKetua = $pdo->prepare("SELECT ketua FROM rt WHERE id = ?");
                    $stmtKetua->execute([$user['rt_id']]);
                    $nama_ketua = $stmtKetua->fetchColumn() ?: $user['name'];
                } elseif ($role === 'rw' && !empty($user['rw_id'])) {
                    $stmtKetua = $pdo->prepare("SELECT ketua FROM rw WHERE id = ?");
                    $stmtKetua->execute([$user['rw_id']]);
                    $nama_ketua = $stmtKetua->fetchColumn() ?: $user['name'];
                }
                $no_rt = $user['rt'] ?? '00';
                $no_rw = $user['rw'] ?? '00';

                $waStatus = 'Tidak Dipilih';
                $waSentAt = null;
                $emailStatus = 'Tidak Dipilih';
                $emailSentAt = null;

                // 4. Saluran Email (Gmail / Mailer SMTP)
                if ($saluran === 'email' || $saluran === 'both') {
                    $userRtNorm = str_pad(trim((string)$no_rt), 2, '0', STR_PAD_LEFT);
                    $userRwNorm = str_pad(trim((string)$no_rw), 2, '0', STR_PAD_LEFT);

                    // Filter penerima email warga sesuai wilayah kewenangan & status aktif
                    if ($role === 'rt') {
                        $stmtEmail = $pdo->prepare("
                            SELECT email, name 
                            FROM users 
                            WHERE role = 'warga' 
                              AND (status_verifikasi = 'active' OR status_verifikasi IS NULL)
                              AND LPAD(TRIM(rt), 2, '0') = ? 
                              AND LPAD(TRIM(rw), 2, '0') = ? 
                              AND email IS NOT NULL 
                              AND TRIM(email) != '' 
                              AND email NOT LIKE '%@siwarga.local'
                        ");
                        $stmtEmail->execute([$userRtNorm, $userRwNorm]);
                    } elseif ($role === 'rw') {
                        $stmtEmail = $pdo->prepare("
                            SELECT email, name 
                            FROM users 
                            WHERE role = 'warga' 
                              AND (status_verifikasi = 'active' OR status_verifikasi IS NULL)
                              AND LPAD(TRIM(rw), 2, '0') = ? 
                              AND email IS NOT NULL 
                              AND TRIM(email) != '' 
                              AND email NOT LIKE '%@siwarga.local'
                        ");
                        $stmtEmail->execute([$userRwNorm]);
                    } else {
                        // Super Admin / Desa: broadcast hanya ke seluruh warga desa aktif
                        $stmtEmail = $pdo->query("
                            SELECT email, name 
                            FROM users 
                            WHERE role = 'warga' 
                              AND (status_verifikasi = 'active' OR status_verifikasi IS NULL)
                              AND email IS NOT NULL 
                              AND TRIM(email) != '' 
                              AND email NOT LIKE '%@siwarga.local'
                        ");
                    }
                    $penerima_email = $stmtEmail->fetchAll(PDO::FETCH_ASSOC);

                    // Deduplikasi email unik (case-insensitive) dan validasi format sebelum pengiriman
                    $uniquePenerima = [];
                    $seenEmails = [];
                    foreach ($penerima_email as $p) {
                        $em = strtolower(trim($p['email'] ?? ''));
                        if (!empty($em) && filter_var($em, FILTER_VALIDATE_EMAIL) && !isset($seenEmails[$em])) {
                            $seenEmails[$em] = true;
                            $uniquePenerima[] = [
                                'email' => $em,
                                'name'  => $p['name'] ?? ''
                            ];
                        }
                    }

                    if (count($uniquePenerima) > 0) {
                        $hasilMail = Mailer::sendPengumumanBatch(
                            $uniquePenerima, $judul, $isi, $role, $nama_desa, $nama_ketua, $no_rt, $no_rw
                        );
                        if (!empty($hasilMail['berhasil']) && $hasilMail['berhasil'] > 0) {
                            $emailStatus = "Terkirim BCC ({$hasilMail['berhasil']} email)";
                            $emailSentAt = date('Y-m-d H:i:s');
                        } else {
                            $emailStatus = "Gagal kirim (" . ($hasilMail['error'] ?? 'Gagal antrean') . ")";
                        }
                    } else {
                        $emailStatus = "Tidak ada warga ber-email valid";
                    }
                }

                // 5. Saluran WhatsApp (Fonnte API Gateway)
                if ($saluran === 'whatsapp' || $saluran === 'both') {
                    $waCfg = WhatsAppConfig::get();
                    if (!empty($waCfg['is_active'])) {
                        try {
                            $waRes = WhatsAppNotificationService::sendPengumuman(
                                $newPengumumanId, $judul, $isi, $role, $user['rt'] ?? '', $user['rw'] ?? '', $nama_desa, $nama_ketua, $extraNotif
                            );
                            if (!empty($waRes['sent']) && $waRes['sent'] > 0) {
                                $waStatus = "Terkirim ({$waRes['sent']} warga)";
                                $waSentAt = date('Y-m-d H:i:s');
                            } else {
                                $failReason = !empty($waRes['message']) ? $waRes['message'] : 'Gagal mengirim ke gateway';
                                $waStatus = "Gagal ($failReason)";
                            }
                        } catch (Exception $eWa) {
                            error_log('WhatsApp sendPengumuman error: ' . $eWa->getMessage());
                            $waStatus = "Error: " . $eWa->getMessage();
                        }
                    } else {
                        $waStatus = "Integrasi WhatsApp nonaktif";
                    }
                }

                // 6. Catat status pengiriman terpisah ke database
                $stmtUpStatus = $pdo->prepare("UPDATE pengumuman SET whatsapp_status = ?, email_status = ?, whatsapp_sent_at = ?, email_sent_at = ? WHERE id = ?");
                $stmtUpStatus->execute([$waStatus, $emailStatus, $waSentAt, $emailSentAt, $newPengumumanId]);

                // 7. Pesan Notifikasi Terperinci
                $msgParts = ["✅ Pengumuman berhasil diterbitkan!"];
                if ($saluran === 'both') {
                    $msgParts[] = "• WhatsApp: {$waStatus}";
                    $msgParts[] = "• Email: {$emailStatus}";
                } elseif ($saluran === 'whatsapp') {
                    $msgParts[] = "• Saluran: WhatsApp Saja ({$waStatus})";
                } elseif ($saluran === 'email') {
                    $msgParts[] = "• Saluran: Gmail / Email Saja ({$emailStatus})";
                }

                $_SESSION['flash'] = ['message' => implode('<br>', $msgParts), 'type' => 'success'];
                header('Location: /pengumuman'); exit;

            } catch (Exception $e) {
                $_SESSION['flash'] = ['message' => '❌ Terjadi kesalahan: ' . $e->getMessage(), 'type' => 'error'];
            }
        }
    }
    require_once __DIR__ . '/../../resources/views/pengumuman/buat.php';
    exit;
}

// ============================================
// HAPUS PENGUMUMAN
// ============================================
if ($action === 'hapus' && $id && $isAdmin) {
    $stmt = $pdo->prepare("DELETE FROM pengumuman WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['message' => '✅ Pengumuman dihapus!', 'type' => 'success'];
    header('Location: /pengumuman'); exit;
}

// ============================================
// LIST PENGUMUMAN
// ============================================
$stmt = $pdo->query("
    SELECT p.*, u.name AS author_name, u.role AS author_role 
    FROM pengumuman p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$pengumuman_list = $stmt->fetchAll();

require_once __DIR__ . '/../../resources/views/pengumuman/index.php';