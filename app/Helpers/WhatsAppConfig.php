<?php
// app/Helpers/WhatsAppConfig.php
// Manajemen Konfigurasi WhatsApp Gateway Fonnte SiWarga (Khusus Super Admin)

class WhatsAppConfig
{
    private static $configFile = __DIR__ . '/../../storage/whatsapp_config.json';
    private static $idempotencyFile = __DIR__ . '/../../storage/logs/whatsapp_idempotency.json';

    /**
     * Ambil konfigurasi WhatsApp saat ini
     */
    public static function get()
    {
        $default = [
            'is_active'            => false,
            'provider'             => 'fonnte',
            'api_key'              => getenv('FONNTE_API_KEY') ?: '',
            'sender_number'        => getenv('FONNTE_SENDER') ?: '',
            'country_code'         => '62',
            'queue_delay_seconds'  => 60, // Default jeda antrean 60 detik (1 menit) antar pesan
            'last_status'          => null,
            'last_checked_at'      => null,
        ];

        if (file_exists(self::$configFile)) {
            $raw = @file_get_contents(self::$configFile);
            $saved = json_decode($raw, true);
            if (is_array($saved)) {
                foreach ($saved as $k => $v) {
                    $default[$k] = $v;
                }
            }
        }

        // Fallback ke .env jika api_key atau sender_number belum terisi di JSON
        if (empty($default['api_key'])) {
            $default['api_key'] = getenv('FONNTE_API_KEY') ?: '';
        }
        if (empty($default['sender_number'])) {
            $default['sender_number'] = getenv('FONNTE_SENDER') ?: '';
        }

        return $default;
    }

    /**
     * Simpan konfigurasi ke storage/whatsapp_config.json
     */
    public static function save(array $data)
    {
        $current = self::get();

        // 1. Status Aktif / Nonaktif
        if (isset($data['is_active'])) {
            $current['is_active'] = (bool)$data['is_active'];
        }

        // 2. Provider (saat ini Fonnte)
        $current['provider'] = 'fonnte';

        // 3. Update API Key jika diisi dan bukan nilai sensor (masked)
        if (!empty($data['api_key']) && !self::isMasked($data['api_key'])) {
            $current['api_key'] = trim($data['api_key']);
        }

        // 4. Update Nomor WhatsApp Pengirim (bisa diganti tanpa ubah kode)
        if (isset($data['sender_number'])) {
            $current['sender_number'] = self::formatPhoneNumber(trim($data['sender_number']));
        }

        // 5. Country Code default Indonesia
        if (isset($data['country_code'])) {
            $current['country_code'] = trim($data['country_code']) ?: '62';
        }

        // 6. Jeda Pengiriman Antrean (Queue Delay) dalam detik (default 60 detik / 1 menit)
        if (isset($data['queue_delay_seconds'])) {
            $current['queue_delay_seconds'] = max(1, (int)$data['queue_delay_seconds']);
        }

        // 7. Update cache status jika ada
        if (isset($data['last_status'])) {
            $current['last_status'] = $data['last_status'];
        }
        if (isset($data['last_checked_at'])) {
            $current['last_checked_at'] = $data['last_checked_at'];
        }

        $dir = dirname(self::$configFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return @file_put_contents(self::$configFile, $json, LOCK_EX) !== false;
    }

    /**
     * Reset konfigurasi WhatsApp ke setelan awal (.env)
     */
    public static function resetToDefault()
    {
        $default = [
            'is_active'            => false,
            'provider'             => 'fonnte',
            'api_key'              => getenv('FONNTE_API_KEY') ?: '',
            'sender_number'        => getenv('FONNTE_SENDER') ?: '',
            'country_code'         => '62',
            'queue_delay_seconds'  => 60, // Default jeda antrean 60 detik (1 menit) antar pesan
            'last_status'          => null,
            'last_checked_at'      => null,
        ];

        $json = json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return @file_put_contents(self::$configFile, $json, LOCK_EX) !== false;
    }

    /**
     * Sensor / masking API key untuk keamanan tampilan antarmuka
     * Contoh: "FONNTE_xyz123456789abc" -> "FONN••••••••••••••••9abc"
     */
    public static function maskKey($key)
    {
        if (empty($key)) return '';
        $len = strlen($key);
        if ($len <= 8) return str_repeat('•', 16);
        $start = substr($key, 0, 4);
        $end = substr($key, -4);
        return $start . str_repeat('•', 20) . $end;
    }

    /**
     * Cek apakah string input merupakan nilai yang disensor (masked)
     */
    public static function isMasked($val)
    {
        if (empty($val)) return false;
        return strpos($val, '•') !== false || strpos($val, '****') !== false;
    }

    /**
     * Format nomor telepon Indonesia ke standar internasional (62xxx)
     */
    public static function formatPhoneNumber($phone, $countryCode = '62')
    {
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($phone)) return '';

        if (str_starts_with($phone, '0')) {
            return $countryCode . substr($phone, 1);
        }
        if (str_starts_with($phone, '62')) {
            return $phone;
        }
        if (str_starts_with($phone, '8')) {
            return $countryCode . $phone;
        }

        return $phone;
    }

    /**
     * Validasi nomor telepon seluler Indonesia (format WhatsApp) dan deteksi nomor dummy/palsu
     * 
     * Kriteria valid:
     * - Merupakan nomor ponsel Indonesia (diawali 08 / 628 / +628)
     * - Panjang 10 s.d. 15 digit angka setelah dinormalisasi
     * - Bukan nomor fiktif/dummy berulang (cth: 0000000000, 1111111111)
     * - Bukan deret angka berurutan dummy (cth: 08123456789, 9876543210)
     * - Bukan nomor landline kantor/rumah (021, 022, 0231, dll)
     */
    public static function isValidPhoneNumber($phone)
    {
        if (empty($phone)) return false;

        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($digits)) return false;

        $normalized = self::formatPhoneNumber($digits);
        // Harus diawali 628 (ponsel seluler Indonesia untuk WhatsApp)
        if (!str_starts_with($normalized, '628')) {
            return false;
        }

        $len = strlen($normalized);
        // Panjang standar nomor seluler Indonesia: 10 s.d. 15 digit
        if ($len < 10 || $len > 15) {
            return false;
        }

        // 1. Periksa nomor dengan seluruh digit sama (misal 88888888888, 0000000000)
        if (preg_match('/^(\d)\1+$/', $digits)) {
            return false;
        }

        // 2. Dummy urutan angka seperti 123456789, 987654321
        if (str_contains($digits, '123456789') || str_contains($digits, '987654321')) {
            return false;
        }

        // 3. Nomor dengan 6 atau lebih angka 0 berturut-turut di akhir (cth: 0812000000, 0800000000)
        if (preg_match('/0{6,}$/', $digits)) {
            return false;
        }

        return true;
    }

    /**
     * Cek status koneksi perangkat dan validasi API Key via endpoint Fonnte
     * Endpoint: POST https://api.fonnte.com/device
     */
    public static function checkStatus($apiKey = null)
    {
        if (empty($apiKey) || self::isMasked($apiKey)) {
            $cfg = self::get();
            $apiKey = $cfg['api_key'];
        }

        if (empty($apiKey)) {
            return [
                'success'       => false,
                'connected'     => false,
                'device_status' => 'not_configured',
                'message'       => 'API Key Fonnte belum dikonfigurasi.',
                'device'        => '-',
                'name'          => '-',
                'quota'         => 0,
                'expired'       => '-',
                'messages'      => 0,
            ];
        }

        $url = 'https://api.fonnte.com/device';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . trim($apiKey)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            return [
                'success'       => false,
                'connected'     => false,
                'device_status' => 'network_error',
                'message'       => 'Koneksi ke server Fonnte gagal: ' . $curl_err,
                'device'        => '-',
                'name'          => '-',
                'quota'         => 0,
                'expired'       => '-',
                'messages'      => 0,
            ];
        }

        $data = json_decode($response, true);
        if ($http_code === 200 && is_array($data)) {
            $status = !empty($data['status']);
            $deviceStatus = strtolower($data['device_status'] ?? 'disconnect');
            $isConnected = ($status && $deviceStatus === 'connect');

            $res = [
                'success'       => $status,
                'connected'     => $isConnected,
                'device_status' => $deviceStatus,
                'device'        => $data['device'] ?? '-',
                'name'          => $data['name'] ?? '-',
                'quota'         => $data['quota'] ?? '0',
                'expired'       => $data['expired'] ?? '-',
                'messages'      => $data['messages'] ?? 0,
                'package'       => $data['package'] ?? 'Standard',
                'message'       => $isConnected 
                    ? 'Perangkat WhatsApp terhubung aktif dengan Fonnte.' 
                    : ($status ? 'API Key valid, namun perangkat WhatsApp sedang terputus (disconnect).' : ($data['reason'] ?? 'API Key tidak valid.')),
            ];

            // Update status ke konfigurasi jika query menggunakan key tersimpan
            $current = self::get();
            if ($apiKey === $current['api_key']) {
                $current['last_status'] = $res;
                $current['last_checked_at'] = date('Y-m-d H:i:s');
                // Jika nomor pengirim di config belum diisi, otomatis isi dari data device
                if (empty($current['sender_number']) && !empty($res['device']) && $res['device'] !== '-') {
                    $current['sender_number'] = self::formatPhoneNumber($res['device']);
                }
                self::save($current);
            }

            return $res;
        }

        $errMsg = is_array($data) ? ($data['reason'] ?? "HTTP $http_code") : "HTTP $http_code";
        return [
            'success'       => false,
            'connected'     => false,
            'device_status' => 'invalid_key',
            'message'       => 'Validasi API Key Fonnte gagal: ' . $errMsg,
            'device'        => '-',
            'name'          => '-',
            'quota'         => 0,
            'expired'       => '-',
            'messages'      => 0,
        ];
    }

    /**
     * Kirim pesan uji coba WhatsApp ke nomor penerima tertentu
     * Endpoint: POST https://api.fonnte.com/send
     * Mendukung jeda pengiriman (delay) sesuai rekomendasi Fonnte
     */
    public static function sendTestMessage($targetNumber, $customMessage = null, $apiKey = null, $delaySeconds = 2)
    {
        if (empty($apiKey) || self::isMasked($apiKey)) {
            $cfg = self::get();
            $apiKey = $cfg['api_key'];
        }

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gagal: API Key Fonnte belum dikonfigurasi.',
                'is_circuit_break' => true,
            ];
        }

        $formattedTarget = self::formatPhoneNumber($targetNumber);
        if (empty($formattedTarget)) {
            return [
                'success' => false,
                'message' => 'Gagal: Nomor WhatsApp tujuan tidak valid.',
                'is_circuit_break' => false,
            ];
        }

        $cfg = self::get();
        $sender = !empty($cfg['sender_number']) ? $cfg['sender_number'] : '-';
        $timestamp = date('d-m-Y H:i:s');

        $message = !empty($customMessage) ? trim($customMessage) : 
            "🔔 *TES PENGIRIMAN WHATSAPP SIWARGA*\n\n" .
            "Halo, ini adalah pesan uji coba dari sistem *SIWARGA (Sistem Informasi Warga & Pelayanan Desa)*.\n\n" .
            "• Pengirim: {$sender}\n" .
            "• Waktu Tes: {$timestamp} WIB\n" .
            "• Gateway: Fonnte WhatsApp API\n" .
            "• Status: Berhasil Terkirim ✅\n\n" .
            "Pesan ini menandakan bahwa integrasi WhatsApp pada sistem SiWarga telah berfungsi dengan baik.";

        $postFields = [
            'target'      => $formattedTarget,
            'message'     => $message,
            'countryCode' => '62',
            'delay'       => (string)max(1, (int)$delaySeconds),
        ];

        $url = 'https://api.fonnte.com/send';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . trim($apiKey)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway Fonnte gagal: ' . $curl_err,
                'is_circuit_break' => false,
            ];
        }

        $data = json_decode($response, true);
        $isCircuitBreak = self::isDisconnectOrRateLimit($data, $http_code);

        if ($http_code === 200 && is_array($data)) {
            if (!empty($data['status'])) {
                return [
                    'success' => true,
                    'message' => 'Pesan WhatsApp berhasil dikirim ke ' . $formattedTarget . '!',
                    'target'  => $formattedTarget,
                    'id'      => $data['id'] ?? null,
                    'process' => $data['process'] ?? 'sent',
                    'is_circuit_break' => false,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim pesan: ' . ($data['reason'] ?? 'Ditolak oleh gateway Fonnte.'),
                    'reason'  => $data['reason'] ?? null,
                    'is_circuit_break' => $isCircuitBreak,
                ];
            }
        }

        $reason = is_array($data) ? ($data['reason'] ?? "HTTP $http_code") : "HTTP $http_code";
        return [
            'success' => false,
            'message' => 'Server Fonnte merespons error: ' . $reason,
            'is_circuit_break' => $isCircuitBreak,
        ];
    }

    /**
     * Kirim pesan WhatsApp umum (bisa dipanggil oleh modul notifikasi/surat/pengaduan)
     * Hanya mengirim jika is_active bernilai true
     */
    public static function sendMessage($targetNumber, $message)
    {
        $cfg = self::get();
        if (empty($cfg['is_active'])) {
            return [
                'success' => false,
                'message' => 'Integrasi WhatsApp sedang dinonaktifkan di pengaturan.',
            ];
        }

        return self::sendTestMessage($targetNumber, $message, $cfg['api_key']);
    }

    /**
     * Kirim dokumen file PDF via WhatsApp menggunakan Fonnte API
     * Mendukung pengiriman berkas lokal (CURLFile) maupun tautan berkas online (URL)
     * Mengatur jeda pengiriman (delay) sesuai kebijakan Fonnte
     */
    public static function sendDocument($targetNumber, $filePathOrUrl, $caption = '', $customFilename = null, $apiKey = null, $delaySeconds = 2)
    {
        if (empty($apiKey) || self::isMasked($apiKey)) {
            $cfg = self::get();
            $apiKey = $cfg['api_key'];
        }

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gagal: API Key Fonnte belum dikonfigurasi.',
                'is_circuit_break' => true,
            ];
        }

        $formattedTarget = self::formatPhoneNumber($targetNumber);
        if (empty($formattedTarget)) {
            return [
                'success' => false,
                'message' => 'Gagal: Nomor WhatsApp tujuan tidak valid.',
                'is_circuit_break' => false,
            ];
        }

        $postFields = [
            'target'      => $formattedTarget,
            'message'     => $caption,
            'countryCode' => '62',
            'delay'       => (string)max(1, (int)$delaySeconds),
        ];

        // Penanganan berkas: CURLFile lokal (upload biner langsung tanpa butuh URL publik) atau URL publik
        if (is_string($filePathOrUrl) && file_exists($filePathOrUrl)) {
            $filename = $customFilename ?: basename($filePathOrUrl);
            // Pastikan ekstensi .pdf terpasang pada nama dokumen
            if (!str_ends_with(strtolower($filename), '.pdf')) {
                $filename .= '.pdf';
            }
            $postFields['file'] = new CURLFile(realpath($filePathOrUrl), 'application/pdf', $filename);
            $postFields['filename'] = $filename;
        } elseif (is_string($filePathOrUrl) && filter_var($filePathOrUrl, FILTER_VALIDATE_URL)) {
            // Cegah pengiriman URL localhost ke server Fonnte
            $host = parse_url($filePathOrUrl, PHP_URL_HOST);
            if (in_array($host, ['localhost', '127.0.0.1', '::1']) || str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
                return [
                    'success' => false,
                    'message' => 'Gagal: Server Fonnte tidak dapat mengakses berkas via URL localhost (' . $filePathOrUrl . '). Gunakan berkas fisik lokal (CURLFile) agar diunggah langsung ke Fonnte.',
                    'is_circuit_break' => false,
                ];
            }
            $postFields['url'] = $filePathOrUrl;
            if ($customFilename) {
                $postFields['filename'] = $customFilename;
            }
        } else {
            return [
                'success' => false,
                'message' => 'Gagal: Berkas dokumen tidak ditemukan di server.',
                'is_circuit_break' => false,
            ];
        }

        $url = 'https://api.fonnte.com/send';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . trim($apiKey)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway Fonnte gagal: ' . $curl_err,
                'is_circuit_break' => false,
            ];
        }

        $data = json_decode($response, true);
        $isCircuitBreak = self::isDisconnectOrRateLimit($data, $http_code);

        if ($http_code === 200 && is_array($data)) {
            if (!empty($data['status'])) {
                return [
                    'success' => true,
                    'message' => 'Dokumen PDF WhatsApp berhasil dikirim ke ' . $formattedTarget . '!',
                    'target'  => $formattedTarget,
                    'id'      => $data['id'] ?? null,
                    'process' => $data['process'] ?? 'sent',
                    'is_circuit_break' => false,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim dokumen: ' . ($data['reason'] ?? 'Ditolak oleh gateway Fonnte.'),
                    'reason'  => $data['reason'] ?? null,
                    'is_circuit_break' => $isCircuitBreak,
                ];
            }
        }

        $reason = is_array($data) ? ($data['reason'] ?? "HTTP $http_code") : "HTTP $http_code";
        return [
            'success' => false,
            'message' => 'Server Fonnte merespons error: ' . $reason,
            'is_circuit_break' => $isCircuitBreak,
        ];
    }

    /**
     * Cek apakah notifikasi dengan kunci tertentu sudah pernah dikirim (Cegah Ganda / Idempotensi)
     */
    public static function isDuplicate($key, $ttlSeconds = 3600)
    {
        if (empty($key)) return false;
        if (!file_exists(self::$idempotencyFile)) return false;

        $raw = @file_get_contents(self::$idempotencyFile);
        $data = json_decode($raw, true);
        if (!is_array($data)) return false;

        if (isset($data[$key])) {
            $timestamp = (int)$data[$key];
            if ((time() - $timestamp) < $ttlSeconds) {
                return true;
            }
        }

        return false;
    }

    /**
     * Catat kunci notifikasi yang telah dikirim ke berkas idempotensi
     */
    public static function recordIdempotency($key)
    {
        if (empty($key)) return;
        $dir = dirname(self::$idempotencyFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $data = [];
        if (file_exists(self::$idempotencyFile)) {
            $raw = @file_get_contents(self::$idempotencyFile);
            $data = json_decode($raw, true) ?: [];
        }

        // Bersihkan kunci yang sudah lewat 24 jam
        $now = time();
        foreach ($data as $k => $ts) {
            if (($now - (int)$ts) > 86400) {
                unset($data[$k]);
            }
        }

        $data[$key] = $now;
        @file_put_contents(self::$idempotencyFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Catat log pengiriman WhatsApp ke file dan tabel database
     */
    public static function logDelivery($userId, $targetNumber, $recipientName, $type, $message, $documentPath = null, $status = 'sent', $idempotencyKey = null, $responsePayload = null)
    {
        // 1. Log berkas terisolasi: storage/logs/whatsapp_delivery.log
        $logFile = __DIR__ . '/../../storage/logs/whatsapp_delivery.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

        $logEntry = [
            'time'             => date('Y-m-d H:i:s'),
            'user_id'          => $userId,
            'recipient_phone'  => $targetNumber,
            'recipient_name'   => $recipientName,
            'type'             => $type,
            'document_path'    => $documentPath,
            'status'           => $status,
            'idempotency_key'  => $idempotencyKey,
            'message_preview'  => mb_strimwidth($message ?? '', 0, 100, '...'),
            'response'         => is_array($responsePayload) ? json_encode($responsePayload) : $responsePayload,
        ];

        @file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);

        // 2. Log database: tabel whatsapp_logs
        global $pdo;
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO whatsapp_logs (user_id, recipient_phone, recipient_name, notification_type, message, document_path, status, idempotency_key, response_payload, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $respStr = is_array($responsePayload) ? json_encode($responsePayload) : (string)$responsePayload;
                $stmt->execute([
                    $userId,
                    $targetNumber,
                    $recipientName,
                    $type,
                    $message,
                    $documentPath,
                    $status,
                    $idempotencyKey,
                    $respStr
                ]);
                return true;
            } catch (Exception $e) {
                // Jangan gagalkan alur utama bila terjadi kendala pencatatan ke DB
            }
        }
        return false;
    }

    /**
     * Cek apakah respons gateway mengindikasikan pemutusan perangkat (Disconnect) atau pembatasan (Rate Limit/Quota)
     */
    public static function isDisconnectOrRateLimit($responseData, $httpCode = 200)
    {
        if ($httpCode === 429) return true;

        $reason = '';
        if (is_array($responseData)) {
            $reason = strtolower($responseData['reason'] ?? ($responseData['message'] ?? ''));
            $deviceStatus = strtolower($responseData['device_status'] ?? '');
            if ($deviceStatus === 'disconnect') return true;
        } elseif (is_string($responseData)) {
            $reason = strtolower($responseData);
        }

        if (empty($reason)) return false;

        $triggers = [
            'disconnect',
            'rate limit',
            'terlalu banyak',
            'too many',
            'quota',
            'over quota',
            'kuota habis',
            'limit reached',
            'banned',
            'blocked',
            'invalid device',
            'device not ready'
        ];

        foreach ($triggers as $t) {
            if (str_contains($reason, $t)) return true;
        }

        return false;
    }

    /**
     * Cek kesiapan perangkat WhatsApp (apakah aktif dan terhubung)
     */
    public static function isDeviceReady($apiKey = null)
    {
        $status = self::checkStatus($apiKey);
        return !empty($status['connected']);
    }

    /**
     * Cek apakah penerima menyetujui pengiriman notifikasi WhatsApp (Consent / Opt-In)
     */
    public static function isUserOptedIn($userId, $phone = null)
    {
        global $pdo;

        // 1. Cek dari database jika userId tersedia (Single Source of Truth)
        if (!empty($userId) && isset($pdo)) {
            try {
                $stmt = $pdo->prepare("SELECT wa_opt_in FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $val = $stmt->fetchColumn();
                // Jika data ditemukan di database, gunakan status aktual database
                if ($val !== false && $val !== null) {
                    return ((int)$val === 1);
                }
            } catch (Exception $e) {
                // Abaikan kesalahan pembacaan DB, fallback ke registry file
            }
        }

        // 2. Cek dari registry opt-out persisten (storage/logs/whatsapp_opt_out.json)
        $optOutFile = __DIR__ . '/../../storage/logs/whatsapp_opt_out.json';
        if (file_exists($optOutFile)) {
            $raw = @file_get_contents($optOutFile);
            $list = json_decode($raw, true);
            if (is_array($list)) {
                if (!empty($userId) && in_array((string)$userId, $list['users'] ?? [])) {
                    return false;
                }
                $formattedPhone = self::formatPhoneNumber($phone);
                if (!empty($formattedPhone) && in_array($formattedPhone, $list['phones'] ?? [])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Set status persetujuan notifikasi WhatsApp user (Opt-in / Opt-out)
     * Jika warga opt-out, otomatis batalkan seluruh antrean pesan pending miliknya
     */
    public static function setOptIn($userId, $status = true, $phone = null)
    {
        global $pdo;
        $optVal = $status ? 1 : 0;
        $formattedPhone = self::formatPhoneNumber($phone);
        $userIdStr = (string)$userId;

        // 1. Simpan ke database aktual beserta waktu persetujuan
        if (!empty($userId) && isset($pdo)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET wa_opt_in = ?, wa_opt_in_at = NOW() WHERE id = ?");
                $stmt->execute([$optVal, $userId]);
            } catch (Exception $e) {
                // Fail silently
            }
        }

        // 2. Jika mencabut persetujuan (Opt-Out), hentikan & batalkan pengiriman pending di antrean
        if (!$status && isset($pdo)) {
            try {
                $stmtCancel = $pdo->prepare("
                    UPDATE whatsapp_queue 
                    SET status = 'cancelled', 
                        last_error = 'Dibatalkan: Warga mencabut persetujuan notifikasi WhatsApp (Opt-Out)' 
                    WHERE (user_id = ? OR recipient_phone = ?) AND status = 'pending'
                ");
                $stmtCancel->execute([$userId, $formattedPhone ?: '-']);
            } catch (Exception $e) {
                // Fail silently
            }
        }

        // 3. Simpan juga ke registry file untuk fallback
        $optOutFile = __DIR__ . '/../../storage/logs/whatsapp_opt_out.json';
        $dir = dirname($optOutFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $list = ['users' => [], 'phones' => []];
        if (file_exists($optOutFile)) {
            $raw = @file_get_contents($optOutFile);
            $parsed = json_decode($raw, true);
            if (is_array($parsed)) $list = $parsed;
        }

        if (!$status) {
            // Tambahkan ke opt-out
            if (!empty($userId) && !in_array($userIdStr, $list['users'])) {
                $list['users'][] = $userIdStr;
            }
            if (!empty($formattedPhone) && !in_array($formattedPhone, $list['phones'])) {
                $list['phones'][] = $formattedPhone;
            }
        } else {
            // Hapus dari opt-out
            $list['users'] = array_values(array_diff($list['users'], [$userIdStr]));
            if (!empty($formattedPhone)) {
                $list['phones'] = array_values(array_diff($list['phones'], [$formattedPhone]));
            }
        }

        @file_put_contents($optOutFile, json_encode($list, JSON_PRETTY_PRINT), LOCK_EX);
        return true;
    }

    /**
     * Dapatkan detail persetujuan notifikasi WhatsApp user dari database aktual
     */
    public static function getOptInDetails($userId)
    {
        global $pdo;
        if (empty($userId) || !isset($pdo)) {
            return ['opted_in' => true, 'opted_at' => null];
        }

        try {
            $stmt = $pdo->prepare("SELECT wa_opt_in, wa_opt_in_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $optedIn = ($row['wa_opt_in'] === null || (int)$row['wa_opt_in'] === 1);
                return [
                    'opted_in' => $optedIn,
                    'opted_at' => $row['wa_opt_in_at']
                ];
            }
        } catch (Exception $e) {}

        return ['opted_in' => true, 'opted_at' => null];
    }

    /**
     * Cek apakah pesan yang sama persis telah dikirim ke nomor yang sama baru-baru ini (Anti-Spam / Anti-Pesan Berulang)
     */
    public static function isRepeatedMessage($phone, $message, $ttlSeconds = 1800)
    {
        $formattedPhone = self::formatPhoneNumber($phone);
        if (empty($formattedPhone) || empty($message)) return false;

        $msgHash = md5($formattedPhone . '|' . trim((string)$message));
        $key = "msg_hash_" . $msgHash;
        return self::isDuplicate($key, $ttlSeconds);
    }

    /**
     * Catat hash pesan untuk mencegah pengiriman pesan berulang
     */
    public static function recordMessageSent($phone, $message)
    {
        $formattedPhone = self::formatPhoneNumber($phone);
        if (empty($formattedPhone) || empty($message)) return;

        $msgHash = md5($formattedPhone . '|' . trim((string)$message));
        $key = "msg_hash_" . $msgHash;
        self::recordIdempotency($key);
    }
}
