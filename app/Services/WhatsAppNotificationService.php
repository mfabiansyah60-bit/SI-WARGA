<?php
// app/Services/WhatsAppNotificationService.php
// Layanan Integrasi Notifikasi & Pengiriman Surat PDF Otomatis via WhatsApp Fonnte

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/WhatsAppConfig.php';

class WhatsAppNotificationService
{
    /**
     * Dapatkan base URL aplikasi untuk link pada pesan
     */
    public static function getAppUrl()
    {
        $envUrl = getenv('APP_URL');
        if (!empty($envUrl)) {
            return rtrim($envUrl, '/');
        }
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . '/Rpl';
    }

    /**
     * Cek apakah URL merupakan alamat lokal (localhost / 127.0.0.1 / IP privat)
     */
    public static function isLocalhostUrl($url)
    {
        if (empty($url)) return true;
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) return true;
        $hostLower = strtolower($host);
        if (in_array($hostLower, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) return true;
        if (str_ends_with($hostLower, '.local') || str_ends_with($hostLower, '.test') || str_ends_with($hostLower, '.example')) return true;
        return false;
    }

    /**
     * Ekstrak bagian lokasi, jadwal, dan imbauan secara cerdas dari teks isi pengumuman jika tersedia
     */
    public static function extractPengumumanSections($isi)
    {
        $lokasi = null;
        $jadwal = null;
        $imbauan = null;

        $lines = explode("\n", str_replace("\r", "", (string)$isi));
        $remainingLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^(?:[\*\-\•]\s*)?(?:lokasi|tempat|bertempat\s*di)\s*[:=]\s*(.+)$/i', $trimmed, $m)) {
                $lokasi = trim($m[1], " *_\t\n\r\0\x0B");
            } elseif (preg_match('/^(?:[\*\-\•]\s*)?(?:jadwal|waktu|hari\s*[\/\&]?\s*tanggal|tanggal\s*pelaksanaan)\s*[:=]\s*(.+)$/i', $trimmed, $m)) {
                $val = trim($m[1], " *_\t\n\r\0\x0B");
                $jadwal = $jadwal ? ($jadwal . ', ' . $val) : $val;
            } elseif (preg_match('/^(?:[\*\-\•]\s*)?(?:imbauan|himbauan|catatan|perhatian|instruksi)\s*[:=]\s*(.+)$/i', $trimmed, $m)) {
                $imbauan = trim($m[1], " *_\t\n\r\0\x0B");
            } else {
                $remainingLines[] = $line;
            }
        }

        $cleanIsi = trim(implode("\n", $remainingLines));
        if (empty($cleanIsi)) {
            $cleanIsi = trim((string)$isi);
        }

        return [
            'isi'     => $cleanIsi,
            'lokasi'  => $lokasi,
            'jadwal'  => $jadwal,
            'imbauan' => $imbauan,
        ];
    }

    /**
     * Bersihkan teks dari URL localhost dan tautan internal sebelum dikirimkan ke warga
     */
    public static function cleanMessageForWarga($text)
    {
        if (empty($text)) return '';
        
        // 1. Hapus link action internal seperti ?action=hapus atau /pengumuman?action=hapus
        $text = preg_replace('/(?:\/[a-zA-Z0-9_\-]+)?\?[^\s\n\r]*action=(?:hapus|delete)[^\s\n\r]*/i', '', $text);
        
        // 2. Hapus URL localhost / IP lokal
        $text = preg_replace('/https?:\/\/(?:localhost|127\.0\.0\.1|0\.0\.0\.0)(?::\d+)?(?:\/[^\s\n\r]*)?/i', '', $text);
        
        // 3. Rapikan whitespace berlebih akibat pemotongan URL
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Susun template pesan pengumuman WhatsApp yang profesional, menarik, dan berstandar resmi
     * Berisi: Judul, Penerbit, Tanggal, Isi Pengumuman, Lokasi, Jadwal, Imbauan jika tersedia, serta Penutup Resmi SIWARGA.
     * Tidak menyertakan tautan localhost.
     */
    public static function buildPengumumanMessage($judul, $isi, $pengurusLabel, $desaNama, $tanggal, $appUrl = null, $extra = [])
    {
        $judul = self::cleanMessageForWarga($judul);
        $isi = self::cleanMessageForWarga($isi);

        $lokasi = !empty($extra['lokasi']) ? trim(self::cleanMessageForWarga($extra['lokasi'])) : null;
        $jadwal = !empty($extra['jadwal']) ? trim(self::cleanMessageForWarga($extra['jadwal'])) : null;
        $imbauan = !empty($extra['imbauan']) ? trim(self::cleanMessageForWarga($extra['imbauan'])) : null;

        // Jika belum disediakan di $extra, ekstrak otomatis dari isi pengumuman
        if (empty($lokasi) && empty($jadwal) && empty($imbauan)) {
            $parsed = self::extractPengumumanSections($isi);
            $lokasi = $parsed['lokasi'];
            $jadwal = $parsed['jadwal'];
            $imbauan = $parsed['imbauan'];
            if (!empty($parsed['isi'])) {
                $isi = $parsed['isi'];
            }
        }

        $desaNamaUpper = strtoupper($desaNama);

        $msg = "📢 *PENGUMUMAN RESMI PEMERINTAH {$desaNamaUpper}*\n" .
               "*Sistem Informasi & Pelayanan Warga (SIWARGA)*\n" .
               "═══════════════════════════════════════\n\n" .
               "📌 *Judul:* *{$judul}*\n" .
               "🏛️ *Penerbit:* {$pengurusLabel}\n" .
               "📅 *Tanggal Terbit:* {$tanggal} WIB\n\n" .
               "───────────────────────────────────────\n" .
               "📝 *DETAIL PENGUMUMAN:*\n" .
               "{$isi}\n";

        if (!empty($lokasi)) {
            $msg .= "\n📍 *Lokasi:* {$lokasi}";
        }

        if (!empty($jadwal)) {
            $msg .= "\n⏰ *Jadwal / Waktu:* {$jadwal}";
        }

        if (!empty($imbauan)) {
            $msg .= "\n⚠️ *Imbauan & Catatan:* {$imbauan}";
        }

        $msg .= "\n───────────────────────────────────────\n\n" .
                "🏛️ *PENUTUP RESMI SIWARGA*\n" .
                "Pemberitahuan ini disampaikan secara resmi oleh {$pengurusLabel} melalui sistem layanan SIWARGA. " .
                "Atas perhatian, kerja sama, dan partisipasi aktif seluruh warga, kami ucapkan terima kasih.\n\n" .
                "Salam hormat,\n" .
                "*Pemerintah {$desaNama}*";

        // Tampilkan tautan portal hanya jika sudah di-hosting publik resmi (bukan localhost)
        if (!empty($appUrl) && !self::isLocalhostUrl($appUrl)) {
            $msg .= "\n\n🌐 *Portal Resmi:* {$appUrl}/pengumuman";
        }

        return $msg;
    }

    /**
     * Daftarkan pesan ke tabel antrean whatsapp_queue
     * Memfilter nomor dummy, penolakan izin (opt-out), dan duplikasi dalam satu batch
     */
    public static function enqueueMessage($batchId, $userId, $phone, $name, $type, $message, $documentPath = null, $maxAttempts = 2)
    {
        global $pdo;
        if (!isset($pdo)) return false;

        // 1. Cegah nomor dummy atau tidak valid masuk antrean
        if (!WhatsAppConfig::isValidPhoneNumber($phone)) {
            return false;
        }

        // 2. Cegah warga yang telah opt-out masuk antrean
        if (!WhatsAppConfig::isUserOptedIn($userId, $phone)) {
            return false;
        }

        // 3. Cegah nomor duplikat dalam batch yang sama
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM whatsapp_queue WHERE batch_id = ? AND recipient_phone = ?");
            $checkStmt->execute([$batchId, $phone]);
            if ($checkStmt->fetch()) {
                return false;
            }
        } catch (Exception $e) {}

        try {
            $stmt = $pdo->prepare("
                INSERT INTO whatsapp_queue (batch_id, user_id, recipient_phone, recipient_name, notification_type, message, document_path, status, attempts, max_attempts, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, ?, NOW())
            ");
            $stmt->execute([
                $batchId,
                $userId,
                $phone,
                $name,
                $type,
                $message,
                $documentPath,
                $maxAttempts
            ]);
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log('enqueueMessage error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Proses antrean pesan per batch dengan jeda pengiriman satu per satu dan sirkuit penghenti (Circuit Breaker)
     * Mengatur jeda pengiriman sesuai konfigurasi Super Admin (default 1 menit / 60 detik)
     */
    public static function processQueueBatch($batchId, $pacingMicroseconds = null, $maxBatchSize = 50)
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'aborted' => true, 'message' => 'Integrasi WhatsApp nonaktif'];
        }

        // 1. Pre-check: Hentikan pengiriman jika perangkat Disconnect atau API key tidak valid
        $devStatus = WhatsAppConfig::checkStatus($cfg['api_key']);
        $deviceState = strtolower($devStatus['device_status'] ?? 'disconnect');
        if (in_array($deviceState, ['disconnect', 'invalid_key', 'not_configured']) || empty($devStatus['connected'])) {
            $reasonMsg = $devStatus['message'] ?? 'Perangkat WhatsApp sedang terputus (Disconnect).';
            return [
                'success' => false,
                'sent'    => 0,
                'failed'  => 0,
                'aborted' => true,
                'message' => 'Pengiriman antrean dihentikan: ' . $reasonMsg
            ];
        }

        // 2. Tentukan jeda pengiriman antar pesan (default 60 detik / 1 menit dari setelan Super Admin)
        if ($pacingMicroseconds === null) {
            $delaySec = isset($cfg['queue_delay_seconds']) ? max(1, (int)$cfg['queue_delay_seconds']) : 60;
            $pacingMicroseconds = $delaySec * 1000000;
        }

        // 3. Ambil antrean pending pada batch ini
        $stmt = $pdo->prepare("
            SELECT * FROM whatsapp_queue 
            WHERE batch_id = ? AND status = 'pending' AND attempts < max_attempts 
            ORDER BY id ASC 
            LIMIT " . (int)$maxBatchSize . "
        ");
        $stmt->execute([$batchId]);
        $queueItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($queueItems)) {
            return ['success' => true, 'sent' => 0, 'failed' => 0, 'message' => 'Tidak ada antrean pesan yang perlu dikirim.'];
        }

        $sentCount = 0;
        $failCount = 0;
        $circuitBreakerTripped = false;
        $circuitReason = '';

        foreach ($queueItems as $index => $item) {
            // Jika circuit breaker telah terpicu pada iterasi sebelumnya, hentikan segera!
            if ($circuitBreakerTripped) {
                break;
            }

            // Validasi apakah warga telah mencabut persetujuan notifikasi saat berada di antrean
            if (!WhatsAppConfig::isUserOptedIn($item['user_id'] ?? null, $item['recipient_phone'])) {
                $pdo->prepare("UPDATE whatsapp_queue SET status = 'cancelled', last_error = 'Dibatalkan: Warga telah mencabut persetujuan notifikasi WhatsApp (Opt-Out)' WHERE id = ?")->execute([$item['id']]);
                continue;
            }

            // Validasi apakah nomor penerima valid dan bukan nomor dummy
            if (!WhatsAppConfig::isValidPhoneNumber($item['recipient_phone'])) {
                $pdo->prepare("UPDATE whatsapp_queue SET status = 'failed', attempts = max_attempts, last_error = 'Gagal: Nomor telepon tidak valid atau dummy' WHERE id = ?")->execute([$item['id']]);
                $failCount++;
                continue;
            }

            // Jeda pengiriman satu per satu antar pesan sesuai pengaturan Super Admin
            if ($index > 0 && $pacingMicroseconds > 0) {
                usleep($pacingMicroseconds);
            }

            // Tandai antrean sedang diproses
            $pdo->prepare("UPDATE whatsapp_queue SET status = 'processing', attempts = attempts + 1 WHERE id = ?")->execute([$item['id']]);

            // Eksekusi pengiriman dengan parameter delay
            $apiDelaySec = isset($cfg['queue_delay_seconds']) ? max(1, min(60, (int)$cfg['queue_delay_seconds'])) : 2;
            if (!empty($item['document_path'])) {
                $res = WhatsAppConfig::sendDocument($item['recipient_phone'], $item['document_path'], $item['message'], null, $cfg['api_key'], $apiDelaySec);
            } else {
                $res = WhatsAppConfig::sendTestMessage($item['recipient_phone'], $item['message'], $cfg['api_key'], $apiDelaySec);
            }

            $isSuccess = !empty($res['success']);
            $idempotencyKey = $item['notification_type'] . '_' . $item['batch_id'] . '_' . $item['recipient_phone'];

            if ($isSuccess) {
                // Catat keberhasilan ke antrean dan log berdasarkan respons aktual Fonnte
                $pdo->prepare("UPDATE whatsapp_queue SET status = 'sent', last_error = NULL, sent_at = NOW() WHERE id = ?")->execute([$item['id']]);
                WhatsAppConfig::recordIdempotency($idempotencyKey);
                WhatsAppConfig::recordMessageSent($item['recipient_phone'], $item['message']);
                WhatsAppConfig::logDelivery($item['user_id'], $item['recipient_phone'], $item['recipient_name'], $item['notification_type'], $item['message'], $item['document_path'], 'sent', $idempotencyKey, $res);
                $sentCount++;
            } else {
                $failReason = $res['reason'] ?? ($res['message'] ?? 'Gateway menolak pengiriman');
                $isCircuitBreak = !empty($res['is_circuit_break']) || WhatsAppConfig::isDisconnectOrRateLimit($res);

                // Periksa batas percobaan ulang (max_attempts) & cegah retry berulang untuk error permanen
                $isPermanentError = $isCircuitBreak || !WhatsAppConfig::isValidPhoneNumber($item['recipient_phone']) || str_contains(strtolower($failReason), 'invalid') || str_contains(strtolower($failReason), 'tidak valid');

                $newAttempts = (int)$item['attempts'] + 1;
                $newStatus = ($isPermanentError || $newAttempts >= (int)$item['max_attempts']) ? 'failed' : 'pending';

                $pdo->prepare("UPDATE whatsapp_queue SET status = ?, attempts = ?, last_error = ? WHERE id = ?")->execute([$newStatus, $newAttempts, $failReason, $item['id']]);
                WhatsAppConfig::logDelivery($item['user_id'], $item['recipient_phone'], $item['recipient_name'], $item['notification_type'], $item['message'], $item['document_path'], 'failed', $idempotencyKey, $res);
                $failCount++;

                // Sirkuit Penghenti: Jika perangkat disconnect atau batas pengiriman tercapai, hentikan batch segera!
                if ($isCircuitBreak) {
                    $circuitBreakerTripped = true;
                    $circuitReason = $failReason;
                    break;
                }
            }
        }

        $msgResult = $circuitBreakerTripped
            ? "Pengiriman otomatis dihentikan karena gangguan gateway/perangkat ({$circuitReason}). Berhasil: {$sentCount}, Gagal: {$failCount}."
            : "Antrean pengiriman berhasil diproses. Berhasil: {$sentCount}, Gagal: {$failCount}.";

        return [
            'success' => ($sentCount > 0 && !$circuitBreakerTripped),
            'sent'    => $sentCount,
            'failed'  => $failCount,
            'aborted' => $circuitBreakerTripped,
            'message' => $msgResult
        ];
    }

    /**
     * 1. NOTIFIKASI PENGUMUMAN DESA
     * Hak akses:
     * - RT: warga di RT & RW sendiri yang menyetujui notifikasi
     * - RW: warga di RW sendiri yang menyetujui notifikasi
     * - Desa / Super Admin: seluruh warga desa aktif yang menyetujui notifikasi
     * Menggunakan sistem antrean, jeda aman, anti-duplikasi nomor/pesan, dan circuit breaker.
     */
    public static function sendPengumuman($pengumumanId, $judul, $isi, $senderRole, $senderRt = null, $senderRw = null, $desaNama = 'Desa Sukamaju', $creatorName = 'Pemerintah Desa', $extra = [])
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'sent' => 0];
        }

        try {
            $senderRtNorm = str_pad(trim((string)$senderRt), 2, '0', STR_PAD_LEFT);
            $senderRwNorm = str_pad(trim((string)$senderRw), 2, '0', STR_PAD_LEFT);

            // Filter penerima aktual dari database sesuai role warga, wilayah kewenangan, dan persetujuan notifikasi (wa_opt_in)
            // Wajib berstatus verifikasi aktif (bukan pending/rejected) dan menyetujui notifikasi (wa_opt_in = 1)
            if ($senderRole === 'rt') {
                $stmt = $pdo->prepare("
                    SELECT id, name, no_hp, no_telp, rt, rw, wa_opt_in 
                    FROM users 
                    WHERE role = 'warga' 
                      AND status_verifikasi = 'active'
                      AND (wa_opt_in = 1 OR wa_opt_in IS NULL)
                      AND LPAD(TRIM(rt), 2, '0') = ? 
                      AND LPAD(TRIM(rw), 2, '0') = ?
                      AND ((no_hp IS NOT NULL AND TRIM(no_hp) != '') OR (no_telp IS NOT NULL AND TRIM(no_telp) != ''))
                ");
                $stmt->execute([$senderRtNorm, $senderRwNorm]);
                $pengurusLabel = "Ketua RT {$senderRtNorm} / RW {$senderRwNorm} ({$creatorName})";
            } elseif ($senderRole === 'rw') {
                $stmt = $pdo->prepare("
                    SELECT id, name, no_hp, no_telp, rt, rw, wa_opt_in 
                    FROM users 
                    WHERE role = 'warga' 
                      AND status_verifikasi = 'active'
                      AND (wa_opt_in = 1 OR wa_opt_in IS NULL)
                      AND LPAD(TRIM(rw), 2, '0') = ?
                      AND ((no_hp IS NOT NULL AND TRIM(no_hp) != '') OR (no_telp IS NOT NULL AND TRIM(no_telp) != ''))
                ");
                $stmt->execute([$senderRwNorm]);
                $pengurusLabel = "Ketua RW {$senderRwNorm} ({$creatorName})";
            } else {
                $stmt = $pdo->query("
                    SELECT id, name, no_hp, no_telp, rt, rw, wa_opt_in 
                    FROM users 
                    WHERE role = 'warga' 
                      AND status_verifikasi = 'active'
                      AND (wa_opt_in = 1 OR wa_opt_in IS NULL)
                      AND ((no_hp IS NOT NULL AND TRIM(no_hp) != '') OR (no_telp IS NOT NULL AND TRIM(no_telp) != ''))
                ");
                $pengurusLabel = "Pemerintah {$desaNama} ({$creatorName})";
            }

            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $appUrl = self::getAppUrl();
            $tanggal = date('d-m-Y H:i');

            // Susun pesan profesional resmi SIWARGA (tanpa tautan localhost)
            $message = self::buildPengumumanMessage(
                $judul, 
                $isi, 
                $pengurusLabel, 
                $desaNama, 
                $tanggal, 
                $appUrl, 
                $extra
            );

            // 1. Masukkan ke Antrean (Queue) terlebih dahulu, bukan pengiriman massal sekaligus
            $batchId = "pengumuman_{$pengumumanId}_" . time();
            $enqueuedCount = 0;
            $processedPhones = [];

            foreach ($recipients as $u) {
                $rawPhone = !empty($u['no_hp']) ? $u['no_hp'] : $u['no_telp'];
                $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
                // Validasi format nomor seluler Indonesia dan deteksi nomor dummy
                if (empty($phone) || !WhatsAppConfig::isValidPhoneNumber($phone)) {
                    WhatsAppConfig::logDelivery($u['id'], $phone ?: ($rawPhone ?: '-'), $u['name'], 'pengumuman', $judul, null, 'skipped', null, 'Dilewati: Nomor telepon tidak valid atau dummy');
                    continue;
                }

                // Pencegahan kirim ganda ke nomor yang sama dalam satu batch
                if (in_array($phone, $processedPhones)) {
                    continue;
                }
                $processedPhones[] = $phone;

                // Cek persetujuan notifikasi warga (Consent / Opt-in)
                if (!WhatsAppConfig::isUserOptedIn($u['id'], $phone)) {
                    WhatsAppConfig::logDelivery($u['id'], $phone, $u['name'], 'pengumuman', $judul, null, 'skipped', null, 'Dilewati: Warga tidak mengaktifkan notifikasi WhatsApp (Opt-Out)');
                    continue;
                }

                // Pencegahan pesan berulang (Anti-Pesan Berulang)
                if (WhatsAppConfig::isRepeatedMessage($phone, $message)) {
                    WhatsAppConfig::logDelivery($u['id'], $phone, $u['name'], 'pengumuman', $judul, null, 'skipped', null, 'Dilewati: Pesan serupa baru saja dikirim');
                    continue;
                }

                // Pencegahan kirim ganda via idempotency
                $idempotencyKey = "pengumuman_{$pengumumanId}_{$phone}";
                if (WhatsAppConfig::isDuplicate($idempotencyKey)) {
                    WhatsAppConfig::logDelivery($u['id'], $phone, $u['name'], 'pengumuman', $judul, null, 'skipped', $idempotencyKey, 'Duplicate skipped');
                    continue;
                }

                self::enqueueMessage($batchId, $u['id'], $phone, $u['name'], 'pengumuman', $message, null, 3);
                $enqueuedCount++;
            }

            if ($enqueuedCount === 0) {
                return ['success' => true, 'sent' => 0, 'failed' => 0, 'message' => 'Tidak ada penerima baru yang memenuhi syarat atau menyetujui notifikasi.', 'message_preview' => $message];
            }

            // 2. Proses antrean secara aman dengan jeda dan sirkuit penghenti
            $queueResult = self::processQueueBatch($batchId);
            $queueResult['message_preview'] = $message;

            return $queueResult;

        } catch (Exception $e) {
            error_log('WhatsApp sendPengumuman error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'sent' => 0];
        }
    }

    /**
     * 2. NOTIFIKASI PENGADUAN WARGA BARU (KE PENGURUS RT/RW/DESA/SUPER ADMIN)
     */
    public static function sendPengaduanBaru($pengaduanId, $judul, $kategori, $prioritas, $pelaporUser, $isAnonymous = false)
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped', 'sent' => 0];
        }

        try {
            $userRt = str_pad(trim((string)($pelaporUser['rt'] ?? '')), 2, '0', STR_PAD_LEFT);
            $userRw = str_pad(trim((string)($pelaporUser['rw'] ?? '')), 2, '0', STR_PAD_LEFT);
            $pelaporId = $pelaporUser['id'] ?? 0;
            $pelaporNama = $isAnonymous ? 'Warga (Anonim)' : ($pelaporUser['name'] ?? 'Warga');

            // Petugas yang dituju: Super Admin, Desa, RT setempat, RW setempat
            $targets = [];

            // a. Super Admin & Desa
            $stmtGlobal = $pdo->query("SELECT id, name, no_hp, no_telp, role FROM users WHERE role IN ('super_admin', 'desa')");
            foreach ($stmtGlobal->fetchAll(PDO::FETCH_ASSOC) as $adm) $targets[] = $adm;

            // b. RT setempat
            if ($userRt !== '00') {
                $stmtRt = $pdo->prepare("SELECT id, name, no_hp, no_telp, role FROM users WHERE role = 'rt' AND LPAD(TRIM(rt), 2, '0') = ? AND LPAD(TRIM(rw), 2, '0') = ?");
                $stmtRt->execute([$userRt, $userRw]);
                foreach ($stmtRt->fetchAll(PDO::FETCH_ASSOC) as $rt) $targets[] = $rt;
            }

            // c. RW setempat
            if ($userRw !== '00') {
                $stmtRw = $pdo->prepare("SELECT id, name, no_hp, no_telp, role FROM users WHERE role = 'rw' AND LPAD(TRIM(rw), 2, '0') = ?");
                $stmtRw->execute([$userRw]);
                foreach ($stmtRw->fetchAll(PDO::FETCH_ASSOC) as $rw) $targets[] = $rw;
            }

            $appUrl = self::getAppUrl();
            $waktu = date('d-m-Y H:i');

            foreach ($targets as $t) {
                if ($t['id'] == $pelaporId) continue;
                $rawPhone = !empty($t['no_hp']) ? $t['no_hp'] : $t['no_telp'];
                $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
                if (empty($phone)) continue;

                $idempotencyKey = "pengaduan_baru_{$pengaduanId}_{$phone}";
                if (WhatsAppConfig::isDuplicate($idempotencyKey)) continue;

                $message = "🔔 *PENGADUAN WARGA BARU MASUK*\n\n" .
                           "Terdapat laporan pengaduan baru di sistem SIWARGA:\n\n" .
                           "• *Judul:* {$judul}\n" .
                           "• *Kategori:* " . ucfirst($kategori) . "\n" .
                           "• *Prioritas:* " . strtoupper($prioritas) . "\n" .
                           "• *Pelapor:* {$pelaporNama}\n" .
                           "• *Wilayah:* RT {$userRt} / RW {$userRw}\n" .
                           "• *Waktu:* {$waktu} WIB\n\n" .
                           "Silakan tindak lanjuti laporan melalui tautan:\n" .
                           "{$appUrl}/pengaduan?action=detail&id={$pengaduanId}";

                $res = WhatsAppConfig::sendTestMessage($phone, $message, $cfg['api_key']);
                $status = !empty($res['success']) ? 'sent' : 'failed';

                WhatsAppConfig::logDelivery($t['id'], $phone, $t['name'], 'pengaduan_baru', $message, null, $status, $idempotencyKey, $res);
                if (!empty($res['success'])) WhatsAppConfig::recordIdempotency($idempotencyKey);
            }

            return true;
        } catch (Exception $e) {
            error_log('WhatsApp sendPengaduanBaru error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 3. NOTIFIKASI UPDATE TANGGAPAN PENGADUAN (BALIK KE WARGA PELAPOR)
     */
    public static function sendPengaduanUpdate($pengaduanId, $status, $tanggapan = '')
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped', 'sent' => 0];
        }

        try {
            $stmt = $pdo->prepare("SELECT p.*, u.name as pelapor_name, u.no_hp, u.no_telp, u.status_verifikasi FROM pengaduan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
            $stmt->execute([$pengaduanId]);
            $pengaduan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pengaduan) return false;

            // Cegah pengiriman jika akun pelapor belum diverifikasi aktif
            if (($pengaduan['status_verifikasi'] ?? 'active') !== 'active') {
                return ['success' => false, 'message' => 'Akun pelapor belum diverifikasi aktif.', 'status' => 'skipped'];
            }

            $rawPhone = !empty($pengaduan['no_hp']) ? $pengaduan['no_hp'] : $pengaduan['no_telp'];
            $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
            if (empty($phone) || !WhatsAppConfig::isValidPhoneNumber($phone)) {
                return ['success' => false, 'message' => 'Nomor WhatsApp pelapor tidak valid atau dummy.', 'status' => 'skipped'];
            }

            // Cek persetujuan notifikasi warga (Opt-In / Opt-Out)
            if (!WhatsAppConfig::isUserOptedIn($pengaduan['user_id'], $phone)) {
                WhatsAppConfig::logDelivery($pengaduan['user_id'], $phone, $pengaduan['pelapor_name'], 'pengaduan_update', 'Warga opt-out', null, 'skipped', null, 'Opt-out skipped');
                return ['success' => false, 'message' => 'Pelapor tidak mengaktifkan notifikasi WhatsApp.', 'status' => 'skipped'];
            }

            $statusLabel = ['pending'=>'Menunggu', 'proses'=>'Sedang Diproses', 'selesai'=>'Selesai', 'ditolak'=>'Ditolak'][$status] ?? ucfirst($status);
            $statusEmoji = ['pending'=>'⏳', 'proses'=>'🔄', 'selesai'=>'✅', 'ditolak'=>'❌'][$status] ?? '🔔';

            $idempotencyKey = "pengaduan_update_{$pengaduanId}_{$status}_{$phone}";
            if (WhatsAppConfig::isDuplicate($idempotencyKey)) return true;

            $appUrl = self::getAppUrl();
            $waktu = date('d-m-Y H:i');

            $message = "{$statusEmoji} *UPDATE STATUS PENGADUAN SIWARGA*\n\n" .
                       "Halo Bapak/Ibu *{$pengaduan['pelapor_name']}*,\n" .
                       "Pengaduan Anda telah ditindaklanjuti oleh pengurus/admin desa:\n\n" .
                       "• *Judul Pengaduan:* {$pengaduan['judul']}\n" .
                       "• *Status Terbaru:* {$statusLabel}\n" .
                       "• *Waktu Update:* {$waktu} WIB\n";

            if (!empty($tanggapan)) {
                $message .= "\n*Tanggapan Petugas:*\n\"{$tanggapan}\"\n";
            }

            if (!empty($appUrl) && !self::isLocalhostUrl($appUrl)) {
                $message .= "\nLihat detail lengkap pengaduan Anda:\n" .
                            "{$appUrl}/pengaduan?action=detail&id={$pengaduanId}";
            } else {
                $message .= "\nSilakan cek tindak lanjut pengaduan Anda melalui menu Pengaduan di aplikasi SIWARGA.";
            }

            $res = WhatsAppConfig::sendTestMessage($phone, $message, $cfg['api_key']);
            $resStatus = !empty($res['success']) ? 'sent' : 'failed';

            WhatsAppConfig::logDelivery($pengaduan['user_id'], $phone, $pengaduan['pelapor_name'], 'pengaduan_update', $message, null, $resStatus, $idempotencyKey, $res);
            if (!empty($res['success'])) WhatsAppConfig::recordIdempotency($idempotencyKey);

            return ['success' => true, 'message' => 'Notifikasi tanggapan berhasil dikirim.', 'status' => $resStatus, 'target' => $phone];
        } catch (Exception $e) {
            error_log('WhatsApp sendPengaduanUpdate error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'status' => 'failed'];
        }
    }

    /**
     * 4. NOTIFIKASI PENGAJUAN SURAT BARU (KE PENGURUS RT/RW/DESA)
     */
    public static function sendPengajuanSuratBaru($suratId, $tipe, $jenisSurat, $nomorSurat, $pemohonUser)
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped', 'sent' => 0];
        }

        try {
            $userRt = str_pad(trim((string)($pemohonUser['rt'] ?? '')), 2, '0', STR_PAD_LEFT);
            $userRw = str_pad(trim((string)($pemohonUser['rw'] ?? '')), 2, '0', STR_PAD_LEFT);
            $pemohonNama = $pemohonUser['name'] ?? 'Warga';

            $targets = [];

            // a. Super Admin & Desa
            $stmtGlobal = $pdo->query("SELECT id, name, no_hp, no_telp, role FROM users WHERE role IN ('super_admin', 'desa')");
            foreach ($stmtGlobal->fetchAll(PDO::FETCH_ASSOC) as $adm) $targets[] = $adm;

            // b. RT setempat
            if ($userRt !== '00') {
                $stmtRt = $pdo->prepare("SELECT id, name, no_hp, no_telp, role FROM users WHERE role = 'rt' AND LPAD(TRIM(rt), 2, '0') = ? AND LPAD(TRIM(rw), 2, '0') = ?");
                $stmtRt->execute([$userRt, $userRw]);
                foreach ($stmtRt->fetchAll(PDO::FETCH_ASSOC) as $rt) $targets[] = $rt;
            }

            // c. RW setempat
            if ($userRw !== '00') {
                $stmtRw = $pdo->prepare("SELECT id, name, no_hp, no_telp, role FROM users WHERE role = 'rw' AND LPAD(TRIM(rw), 2, '0') = ?");
                $stmtRw->execute([$userRw]);
                foreach ($stmtRw->fetchAll(PDO::FETCH_ASSOC) as $rw) $targets[] = $rw;
            }

            $appUrl = self::getAppUrl();
            $waktu = date('d-m-Y H:i');

            foreach ($targets as $t) {
                if ($t['id'] == ($pemohonUser['id'] ?? 0)) continue;
                $rawPhone = !empty($t['no_hp']) ? $t['no_hp'] : $t['no_telp'];
                $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
                if (empty($phone)) continue;

                $idempotencyKey = "surat_baru_{$tipe}_{$suratId}_{$phone}";
                if (WhatsAppConfig::isDuplicate($idempotencyKey)) continue;

                $message = "📨 *PERMOHONAN SURAT WARGA BARU*\n\n" .
                           "Terdapat permohonan surat administrasi baru:\n\n" .
                           "• *Pemohon:* {$pemohonNama}\n" .
                           "• *Jenis Surat:* {$jenisSurat}\n" .
                           "• *Nomor Registrasi:* {$nomorSurat}\n" .
                           "• *Wilayah:* RT {$userRt} / RW {$userRw}\n" .
                           "• *Waktu Pengajuan:* {$waktu} WIB\n\n" .
                           "Silakan periksa dan berikan persetujuan pada menu Surat:\n" .
                           "{$appUrl}/surat";

                $res = WhatsAppConfig::sendTestMessage($phone, $message, $cfg['api_key']);
                $status = !empty($res['success']) ? 'sent' : 'failed';

                WhatsAppConfig::logDelivery($t['id'], $phone, $t['name'], 'surat_pengajuan', $message, null, $status, $idempotencyKey, $res);
                if (!empty($res['success'])) WhatsAppConfig::recordIdempotency($idempotencyKey);
            }

            return true;
        } catch (Exception $e) {
            error_log('WhatsApp sendPengajuanSuratBaru error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 5. GENERATE FILE PDF SURAT RESMI
     * Menghasilkan berkas PDF fisik di server untuk dikirim via WhatsApp
     */
    public static function generateSuratPDF($suratId, $tipe = 'ai')
    {
        global $pdo;
        try {
            if ($tipe === 'ai') {
                $stmt = $pdo->prepare("SELECT s.*, u.name, u.email, u.no_hp, u.no_telp FROM surat_pengajuan s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
                $stmt->execute([$suratId]);
                $surat = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$surat) throw new Exception("Data surat pengajuan AI #$suratId tidak ditemukan.");

                $jenis = $surat['jenis_surat'];
                $nomor = $surat['nomor_surat'] ?: sprintf("%03d/SK/AI/%s", $surat['id'], date('Y'));
                $data_surat = json_decode($surat['data_json'], true) ?: [];

                $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $ts = strtotime($surat['created_at']);
                $tanggal_indo = date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);

            } else {
                $stmt = $pdo->prepare("SELECT s.*, t.nama as template_nama, t.isi_template, u.name, u.email, u.no_hp, u.no_telp FROM surat_terbit s JOIN surat_template t ON s.template_id = t.id JOIN users u ON s.user_id = u.id WHERE s.id = ?");
                $stmt->execute([$suratId]);
                $surat = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$surat) throw new Exception("Data surat terbit manual #$suratId tidak ditemukan.");

                $jenis = $surat['template_nama'] ?? 'Surat Resmi';
                $nomor = $surat['nomor_surat'] ?: sprintf("%03d/SK/%s", $surat['id'], date('Y'));

                $replacements = json_decode($surat['data_isi'], true) ?: [];
                $isi_surat = str_replace(array_keys($replacements), array_values($replacements), $surat['isi_template']);
                $tanggal_indo = date('d F Y');
            }

            // Render HTML template PDF
            ob_start();
            require __DIR__ . '/../../resources/views/surat/pdf_template.php';
            $html_pdf = ob_get_clean();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html_pdf);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf_binary = $dompdf->output();

            // Direktori penyimpanan file PDF
            $pdfDir = __DIR__ . '/../../public/uploads/surat_pdf';
            if (!is_dir($pdfDir)) {
                @mkdir($pdfDir, 0755, true);
            }

            $nomorSafe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $nomor);
            $fileName = "Surat_{$tipe}_{$suratId}_{$nomorSafe}.pdf";
            $filePath = $pdfDir . '/' . $fileName;

            file_put_contents($filePath, $pdf_binary);

            $downloadUrl = self::getAppUrl() . '/public/uploads/surat_pdf/' . $fileName;

            return [
                'success'      => true,
                'file_path'    => $filePath,
                'download_url' => $downloadUrl,
                'filename'     => $fileName,
                'jenis'        => $jenis,
                'nomor'        => $nomor,
                'user'         => $surat,
            ];

        } catch (Exception $e) {
            error_log('generateSuratPDF error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 6. PENGIRIMAN SURAT RESMI FINAL BERBENTUK PDF KE WHATSAPP PEMOHON
     * Dikirim otomatis setelah surat disetujui
     */
    /**
     * Kirim pesan notifikasi WhatsApp bahwa surat telah disetujui & dikirimkan ke email pemohon
     * Format: Nama pemohon, jenis surat, nomor surat, tanggal, dan email tujuan
     * (PENTING: Tidak lagi mengirimkan file PDF melalui WhatsApp)
     */
    public static function sendSuratEmailNotificationToWhatsApp($suratId, $tipe = 'ai', $emailTujuan = null, $forceResend = false)
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped'];
        }

        try {
            $table = ($tipe === 'ai') ? 'surat_pengajuan' : 'surat_terbit';
            if ($tipe === 'ai') {
                $stmt = $pdo->prepare("SELECT s.*, u.name, u.email, u.no_hp, u.no_telp, u.status_verifikasi FROM surat_pengajuan s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
                $stmt->execute([$suratId]);
                $surat = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$surat) throw new Exception("Data surat pengajuan #$suratId tidak ditemukan.");
                $jenisSurat = $surat['jenis_surat'];
                $nomorSurat = $surat['nomor_surat'] ?: sprintf("%03d/SK/AI/%s", $surat['id'], date('Y'));
            } else {
                $stmt = $pdo->prepare("SELECT s.*, t.nama as template_nama, u.name, u.email, u.no_hp, u.no_telp, u.status_verifikasi FROM surat_terbit s JOIN surat_template t ON s.template_id = t.id JOIN users u ON s.user_id = u.id WHERE s.id = ?");
                $stmt->execute([$suratId]);
                $surat = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$surat) throw new Exception("Data surat terbit #$suratId tidak ditemukan.");
                $jenisSurat = $surat['template_nama'] ?? 'Surat Resmi';
                $nomorSurat = $surat['nomor_surat'] ?: sprintf("%03d/SK/%s", $surat['id'], date('Y'));
            }

            // Cegah pengiriman jika akun pemohon belum diverifikasi aktif
            if (($surat['status_verifikasi'] ?? 'active') !== 'active') {
                WhatsAppConfig::logDelivery($surat['user_id'], '-', $surat['name'], 'surat_email_notif', 'Akun pemohon belum diverifikasi aktif', null, 'skipped', null, 'Unverified user');
                return ['success' => false, 'message' => 'Akun pemohon belum diverifikasi aktif.', 'status' => 'skipped'];
            }

            $rawPhone = !empty($surat['no_hp']) ? $surat['no_hp'] : $surat['no_telp'];
            $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
            if (empty($phone) || !WhatsAppConfig::isValidPhoneNumber($phone)) {
                WhatsAppConfig::logDelivery($surat['user_id'], $phone ?: '-', $surat['name'], 'surat_email_notif', 'Nomor WhatsApp pemohon tidak valid atau dummy', null, 'skipped', null, 'Invalid/dummy phone');
                return ['success' => false, 'message' => 'Pemohon tidak memiliki nomor WhatsApp yang valid.', 'status' => 'skipped'];
            }

            // Cek persetujuan notifikasi warga (Opt-In / Opt-Out)
            if (!WhatsAppConfig::isUserOptedIn($surat['user_id'], $phone)) {
                WhatsAppConfig::logDelivery($surat['user_id'], $phone, $surat['name'], 'surat_email_notif', 'Warga menolak notifikasi WhatsApp (Opt-out)', null, 'skipped', null, 'Opt-out skipped');
                return ['success' => false, 'message' => 'Pemohon tidak mengaktifkan notifikasi WhatsApp.', 'status' => 'skipped'];
            }

            $emailTujuan = $emailTujuan ?: trim($surat['email'] ?? '');
            if (empty($emailTujuan) || !filter_var($emailTujuan, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Alamat email pemohon tidak valid.', 'status' => 'skipped'];
            }

            $idempotencyKey = "surat_email_notif_{$tipe}_{$suratId}_{$phone}";
            if (!$forceResend && WhatsAppConfig::isDuplicate($idempotencyKey)) {
                WhatsAppConfig::logDelivery($surat['user_id'], $phone, $surat['name'], 'surat_email_notif', $nomorSurat, null, 'skipped', $idempotencyKey, 'Duplicate skipped');
                return ['success' => true, 'message' => 'Notifikasi WhatsApp sudah pernah dikirim sebelumnya.', 'status' => 'skipped'];
            }

            $tanggal = date('d-m-Y');

            // Format Pesan Notifikasi Resmi: Memuat Nama Pemohon, Jenis Surat, Nomor Surat, Tanggal, dan Email Tujuan
            $message = "📄 *NOTIFIKASI PENGIRIMAN SURAT RESMI*\n" .
                       "*Sistem Informasi & Pelayanan Warga (SIWARGA)*\n" .
                       "═══════════════════════════════════════\n\n" .
                       "Yth. Bapak/Ibu *{$surat['name']}*,\n\n" .
                       "Pemberitahuan bahwa permohonan surat administrasi kependudukan Anda telah *DISETUJUI* dan berkas resmi telah dikirimkan melalui surel (email):\n\n" .
                       "• *Pemohon:* {$surat['name']}\n" .
                       "• *Jenis Surat:* {$jenisSurat}\n" .
                       "• *Nomor Surat:* {$nomorSurat}\n" .
                       "• *Tanggal Terbit:* {$tanggal} WIB\n" .
                       "• *Email Tujuan:* {$emailTujuan}\n" .
                       "• *Status:* Berhasil Terkirim ke Email ✅\n\n" .
                       "📧 *Informasi Pengiriman:*\n" .
                       "Berkas surat resmi dalam format PDF berstempel dan bertanda tangan digital telah dikirimkan ke alamat email Anda (*{$emailTujuan}*).\n\n" .
                       "Silakan periksa folder Kotak Masuk (Inbox) atau Spam pada email Anda untuk mengunduh dan mencetak berkas tersebut.\n\n" .
                       "Terima kasih telah menggunakan layanan digital SIWARGA.\n" .
                       "───────────────────────────────────────\n" .
                       "🏛️ *Pemerintah Desa Sukamaju*\n" .
                       "Layanan Administrasi Kependudukan";

            // Kirim notifikasi WhatsApp berbasis teks (TIDAK LAGI mengirimkan file PDF via WhatsApp)
            $res = WhatsAppConfig::sendTestMessage($phone, $message, $cfg['api_key']);
            $isFonnteSuccess = !empty($res['success']) && !empty($res['id']);

            if ($isFonnteSuccess) {
                $stmtUp = $pdo->prepare("UPDATE $table SET whatsapp_sent_at = NOW() WHERE id = ?");
                $stmtUp->execute([$suratId]);

                WhatsAppConfig::recordIdempotency($idempotencyKey);
                WhatsAppConfig::logDelivery($surat['user_id'], $phone, $surat['name'], 'surat_email_notif', $message, null, 'sent', $idempotencyKey, $res);

                return [
                    'success'      => true,
                    'status'       => 'sent',
                    'message'      => 'Notifikasi WhatsApp berhasil dikirim ke pemohon!',
                    'target'       => $phone,
                    'email_target' => $emailTujuan,
                    'fonnte_id'    => $res['id'] ?? null,
                ];
            } else {
                $errorReason = $res['reason'] ?? ($res['message'] ?? 'Gateway Fonnte menolak pengiriman pesan notifikasi.');
                WhatsAppConfig::logDelivery($surat['user_id'], $phone, $surat['name'], 'surat_email_notif', $message, null, 'failed', $idempotencyKey, $res);

                return [
                    'success'      => false,
                    'status'       => 'failed',
                    'message'      => 'Gagal mengirim notifikasi WhatsApp: ' . $errorReason,
                    'reason'       => $errorReason,
                    'target'       => $phone,
                ];
            }

        } catch (Exception $e) {
            error_log('sendSuratEmailNotificationToWhatsApp error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'status' => 'failed'];
        }
    }

    /**
     * 6. ALUR PENGIRIMAN SURAT RESMI FINAL
     * 1) PDF surat final dikirim langsung ke email pemohon melalui Gmail.
     * 2) Setelah Gmail berhasil mengirim, Fonnte mengirim notifikasi WhatsApp bahwa surat sudah dikirim melalui email.
     * 3) Notifikasi berisi nama pemohon, jenis surat, nomor surat, tanggal, dan email tujuan.
     * 4) Jika Gmail gagal, jangan kirim notifikasi WhatsApp yang menyatakan surat berhasil dikirim.
     */
    public static function sendSuratApprovedWithPDF($suratId, $tipe = 'ai', $forceResend = false)
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped'];
        }

        try {
            // 1. Generate berkas PDF fisik surat resmi
            $pdfData = self::generateSuratPDF($suratId, $tipe);
            if (empty($pdfData['success'])) {
                throw new Exception("Gagal membuat berkas PDF: " . ($pdfData['message'] ?? 'Unknown error'));
            }

            $surat = $pdfData['user'];
            $emailWarga = trim($surat['email'] ?? '');
            $table = ($tipe === 'ai') ? 'surat_pengajuan' : 'surat_terbit';

            // 2. Kirim PDF surat final langsung ke email pemohon melalui Gmail
            require_once __DIR__ . '/../Helpers/Mailer.php';

            if (empty($emailWarga) || !filter_var($emailWarga, FILTER_VALIDATE_EMAIL) || str_contains($emailWarga, '@siwarga.local')) {
                // "Jika email gagal, jangan kirim notifikasi berhasil."
                $failMsg = "Pemohon tidak memiliki alamat email valid ($emailWarga). Notifikasi WhatsApp berhasil tidak dikirim.";
                WhatsAppConfig::logDelivery($surat['user_id'], '-', $surat['name'], 'surat_disetujui_email', $failMsg, $pdfData['file_path'], 'failed', null, 'Invalid/empty email');

                return [
                    'success'      => false,
                    'status'       => 'failed',
                    'email_status' => 'failed',
                    'message'      => $failMsg,
                    'download_url' => $pdfData['download_url'],
                    'file_path'    => $pdfData['file_path'],
                ];
            }

            $pdfBinary = file_get_contents($pdfData['file_path']);
            $emailResult = Mailer::sendSurat(
                $emailWarga,
                $surat['name'],
                $pdfData['jenis'],
                $pdfData['nomor'],
                $pdfData['jenis'],
                "Sukamaju",
                '',
                $pdfBinary
            );

            // 3. Evaluasi hasil pengiriman Gmail
            if ($emailResult !== true) {
                // "Jika Gmail gagal, jangan kirim notifikasi berhasil."
                $errorReason = is_string($emailResult) ? $emailResult : 'SMTP Connection Error';
                $failMsg = "Pengiriman surat ke email pemohon ($emailWarga) gagal: $errorReason. Notifikasi WhatsApp berhasil tidak dikirim.";
                
                WhatsAppConfig::logDelivery($surat['user_id'], '-', $surat['name'], 'surat_disetujui_email', $failMsg, $pdfData['file_path'], 'failed', null, $errorReason);

                return [
                    'success'      => false,
                    'status'       => 'failed',
                    'email_status' => 'failed',
                    'message'      => $failMsg,
                    'reason'       => $errorReason,
                    'download_url' => $pdfData['download_url'],
                    'file_path'    => $pdfData['file_path'],
                ];
            }

            // Gmail berhasil! Catat tanggal terkirim email di database
            $stmtUpEmail = $pdo->prepare("UPDATE $table SET email_sent_at = NOW() WHERE id = ?");
            $stmtUpEmail->execute([$suratId]);

            // 4. Setelah Gmail berhasil mengirim, Fonnte mengirim notifikasi WhatsApp bahwa surat sudah dikirim melalui email
            // (PENTING: Jangan lagi mengirim PDF melalui WhatsApp)
            $waSent = false;
            $waMsg = 'Notifikasi WhatsApp dilewati.';

            if (!empty($cfg['is_active'])) {
                $waNotif = self::sendSuratEmailNotificationToWhatsApp($suratId, $tipe, $emailWarga, $forceResend);
                $waSent = !empty($waNotif['success']);
                $waMsg = $waNotif['message'] ?? '';
            }

            return [
                'success'      => true,
                'status'       => 'sent',
                'email_status' => 'sent',
                'wa_status'    => $waSent ? 'sent' : 'pending',
                'message'      => "Surat PDF resmi berhasil dikirim ke email pemohon ($emailWarga)" . ($waSent ? " dan notifikasi WhatsApp telah dikirimkan!" : "."),
                'download_url' => $pdfData['download_url'],
                'file_path'    => $pdfData['file_path'],
                'email_target' => $emailWarga,
                'target'       => $surat['no_hp'] ?: ($surat['no_telp'] ?? '-'),
                'fonnte_id'    => $waNotif['fonnte_id'] ?? null,
            ];

        } catch (Exception $e) {
            error_log('sendSuratApprovedWithPDF error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'status' => 'failed'];
        }
    }

    /**
     * 7. NOTIFIKASI SURAT DITOLAK
     */
    public static function sendSuratRejected($suratId, $tipe = 'ai', $alasan = '')
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped', 'sent' => 0];
        }

        try {
            $table = ($tipe === 'ai') ? 'surat_pengajuan' : 'surat_terbit';
            $stmt = $pdo->prepare("SELECT s.*, u.name, u.no_hp, u.no_telp, u.status_verifikasi FROM $table s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
            $stmt->execute([$suratId]);
            $surat = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$surat) return false;

            // Cegah pengiriman jika akun pemohon belum diverifikasi aktif
            if (($surat['status_verifikasi'] ?? 'active') !== 'active') {
                return ['success' => false, 'message' => 'Akun pemohon belum diverifikasi aktif.', 'status' => 'skipped'];
            }

            $rawPhone = !empty($surat['no_hp']) ? $surat['no_hp'] : $surat['no_telp'];
            $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
            if (empty($phone) || !WhatsAppConfig::isValidPhoneNumber($phone)) {
                return ['success' => false, 'message' => 'Nomor WhatsApp pemohon tidak valid atau dummy.', 'status' => 'skipped'];
            }

            // Cek persetujuan notifikasi warga (Opt-In / Opt-Out)
            if (!WhatsAppConfig::isUserOptedIn($surat['user_id'], $phone)) {
                WhatsAppConfig::logDelivery($surat['user_id'], $phone, $surat['name'], 'surat_ditolak', 'Warga opt-out', null, 'skipped', null, 'Opt-out skipped');
                return ['success' => false, 'message' => 'Pemohon tidak mengaktifkan notifikasi WhatsApp.', 'status' => 'skipped'];
            }

            $idempotencyKey = "surat_ditolak_{$tipe}_{$suratId}_{$phone}";
            if (WhatsAppConfig::isDuplicate($idempotencyKey)) return true;

            $jenis = $surat['jenis_surat'] ?? 'Surat Administrasi';
            $nomor = $surat['nomor_surat'] ?? '-';
            $appUrl = self::getAppUrl();

            $message = "❌ *STATUS PENGAJUAN SURAT: DITOLAK*\n\n" .
                       "Halo Bapak/Ibu *{$surat['name']}*,\n" .
                       "Mohon maaf, permohonan surat administrasi Anda belum dapat disetujui:\n\n" .
                       "• *Jenis Surat:* {$jenis}\n" .
                       "• *Nomor Surat:* {$nomor}\n";

            if (!empty($alasan)) {
                $message .= "• *Catatan / Alasan:* \"{$alasan}\"\n";
            }

            if (!empty($appUrl) && !self::isLocalhostUrl($appUrl)) {
                $message .= "\nSilakan lengkapi data atau persyaratan yang kurang, atau hubungi pengurus RT/RW setempat.\n" .
                            "Akses portal SIWARGA: {$appUrl}/surat";
            } else {
                $message .= "\nSilakan lengkapi data atau persyaratan yang kurang pada menu Pengajuan Surat di aplikasi SIWARGA, atau hubungi pengurus RT/RW setempat.";
            }

            $res = WhatsAppConfig::sendTestMessage($phone, $message, $cfg['api_key']);
            $status = !empty($res['success']) ? 'sent' : 'failed';

            WhatsAppConfig::logDelivery($surat['user_id'], $phone, $surat['name'], 'surat_ditolak', $message, null, $status, $idempotencyKey, $res);
            if (!empty($res['success'])) WhatsAppConfig::recordIdempotency($idempotencyKey);

            return ['success' => true, 'message' => 'Notifikasi penolakan surat berhasil dikirim.', 'status' => $status, 'target' => $phone];
        } catch (Exception $e) {
            error_log('sendSuratRejected error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'status' => 'failed'];
        }
    }

    /**
     * 8. NOTIFIKASI VERIFIKASI AKUN WARGA (APPROVE / REJECT)
     */
    public static function sendVerifikasiAkun($userId, $statusVerifikasi, $catatan = '')
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped', 'sent' => 0];
        }

        try {
            $stmt = $pdo->prepare("SELECT id, name, email, no_hp, no_telp, rt, rw FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$u) return false;

            $rawPhone = !empty($u['no_hp']) ? $u['no_hp'] : $u['no_telp'];
            $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
            if (empty($phone)) return false;

            // Cek persetujuan notifikasi warga (Opt-In / Opt-Out)
            if (!WhatsAppConfig::isUserOptedIn($userId, $phone)) {
                WhatsAppConfig::logDelivery($userId, $phone, $u['name'], 'verifikasi_' . $statusVerifikasi, 'Warga opt-out', null, 'skipped', null, 'Opt-out skipped');
                return ['success' => false, 'message' => 'Warga tidak mengaktifkan notifikasi WhatsApp.', 'status' => 'skipped'];
            }

            $idempotencyKey = "verifikasi_{$userId}_{$statusVerifikasi}_{$phone}";
            if (WhatsAppConfig::isDuplicate($idempotencyKey)) return true;

            $appUrl = self::getAppUrl();
            $rtNorm = str_pad(trim((string)$u['rt']), 2, '0', STR_PAD_LEFT);
            $rwNorm = str_pad(trim((string)$u['rw']), 2, '0', STR_PAD_LEFT);

            if ($statusVerifikasi === 'active') {
                $message = "🎉 *AKUN SIWARGA TELAH DIAKTIFKAN*\n\n" .
                           "Selamat Bapak/Ibu *{$u['name']}*!\n" .
                           "Pendaftaran akun Anda di sistem *SIWARGA* telah berhasil diverifikasi dan disetujui oleh pengurus RT {$rtNorm} / RW {$rwNorm}.\n\n" .
                           "Sekarang akun Anda telah *AKTIF* dan siap digunakan untuk:\n" .
                           "✅ Permohonan surat administrasi desa online\n" .
                           "✅ Penyampaian pengaduan dan aspirasi warga\n" .
                           "✅ Konsultasi dengan Asisten Digital SiWarga AI\n\n";

                if (!empty($appUrl) && !self::isLocalhostUrl($appUrl)) {
                    $message .= "Silakan masuk ke aplikasi:\n{$appUrl}/login";
                } else {
                    $message .= "Silakan masuk ke aplikasi SIWARGA untuk mulai menggunakan layanan digital desa.";
                }
            } else {
                $message = "⚠️ *STATUS PENDAFTARAN AKUN SIWARGA*\n\n" .
                           "Halo Bapak/Ibu *{$u['name']}*,\n" .
                           "Mohon maaf, pendaftaran akun Anda di sistem SIWARGA belum dapat disetujui oleh pengurus RT {$rtNorm} / RW {$rwNorm}.\n";

                if (!empty($catatan)) {
                    $message .= "\n*Catatan Pengurus:*\n\"{$catatan}\"\n";
                }

                $message .= "\nSilakan hubungi pengurus RT/RW setempat untuk konfirmasi dan perbaikan data kependudukan Anda.";
            }

            $res = WhatsAppConfig::sendTestMessage($phone, $message, $cfg['api_key']);
            $status = !empty($res['success']) ? 'sent' : 'failed';

            WhatsAppConfig::logDelivery($userId, $phone, $u['name'], 'verifikasi_' . $statusVerifikasi, $message, null, $status, $idempotencyKey, $res);
            if (!empty($res['success'])) WhatsAppConfig::recordIdempotency($idempotencyKey);

            return ['success' => true, 'message' => 'Notifikasi status verifikasi akun berhasil dikirim.', 'status' => $status, 'target' => $phone];
        } catch (Exception $e) {
            error_log('sendVerifikasiAkun error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'status' => 'failed'];
        }
    }

    /**
     * 9. NOTIFIKASI INFORMASI PELAYANAN DESA BARU / UPDATE
     */
    public static function sendPelayananUpdate($pelayananId, $namaPelayanan, $deskripsi, $syarat, $actionType = 'tambah')
    {
        global $pdo;
        $cfg = WhatsAppConfig::get();
        if (empty($cfg['is_active'])) {
            return ['success' => false, 'message' => 'Integrasi WhatsApp sedang dinonaktifkan.', 'status' => 'skipped', 'sent' => 0];
        }

        try {
            $stmt = $pdo->query("
                SELECT id, name, no_hp, no_telp, wa_opt_in 
                FROM users 
                WHERE role = 'warga' 
                  AND status_verifikasi = 'active'
                  AND (wa_opt_in = 1 OR wa_opt_in IS NULL)
                  AND ((no_hp IS NOT NULL AND no_hp != '') OR (no_telp IS NOT NULL AND no_telp != ''))
            ");
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $appUrl = self::getAppUrl();
            $batchId = "pelayanan_{$pelayananId}_{$actionType}_" . time();
            $processedPhones = [];
            $enqueuedCount = 0;

            $actionWord = ($actionType === 'tambah') ? 'Layanan Administrasi Baru' : 'Pembaruan Layanan';

            $message = "📋 *INFORMASI PELAYANAN DESA*\n\n" .
                       "Telah tersedia {$actionWord} di SIWARGA:\n\n" .
                       "• *Nama Layanan:* {$namaPelayanan}\n" .
                       "• *Deskripsi:* {$deskripsi}\n" .
                       "• *Syarat Dokumen:* {$syarat}\n\n";

            if (!empty($appUrl) && !self::isLocalhostUrl($appUrl)) {
                $message .= "Warga dapat membaca panduan dan mengurus berkas melalui portal:\n{$appUrl}/pelayanan";
            } else {
                $message .= "Warga dapat membaca panduan dan mengurus berkas melalui menu Pelayanan di aplikasi SIWARGA.";
            }

            foreach ($recipients as $u) {
                $rawPhone = !empty($u['no_hp']) ? $u['no_hp'] : $u['no_telp'];
                $phone = WhatsAppConfig::formatPhoneNumber($rawPhone);
                if (empty($phone) || !WhatsAppConfig::isValidPhoneNumber($phone)) continue;

                // Pencegahan kirim ganda ke nomor yang sama dalam satu batch
                if (in_array($phone, $processedPhones)) continue;
                $processedPhones[] = $phone;

                // Cek persetujuan notifikasi warga (Consent / Opt-in)
                if (!WhatsAppConfig::isUserOptedIn($u['id'], $phone)) continue;

                // Pencegahan pesan berulang
                if (WhatsAppConfig::isRepeatedMessage($phone, $message)) continue;

                $idempotencyKey = "pelayanan_{$pelayananId}_{$actionType}_{$phone}";
                if (WhatsAppConfig::isDuplicate($idempotencyKey)) continue;

                self::enqueueMessage($batchId, $u['id'], $phone, $u['name'], 'pelayanan_info', $message, null, 3);
                $enqueuedCount++;
            }

            if ($enqueuedCount === 0) {
                return ['success' => true, 'message' => 'Tidak ada penerima baru yang memenuhi syarat atau menyetujui notifikasi.', 'status' => 'sent'];
            }

            $queueResult = self::processQueueBatch($batchId);
            return [
                'success' => $queueResult['success'],
                'message' => $queueResult['message'],
                'status'  => $queueResult['success'] ? 'sent' : 'failed',
                'sent'    => $queueResult['sent'],
                'failed'  => $queueResult['failed'],
            ];
        } catch (Exception $e) {
            error_log('sendPelayananUpdate error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'status' => 'failed'];
        }
    }
}
