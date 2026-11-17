<?php
// app/Helpers/AIKeyCooldown.php
// Manajemen Cooldown & Retry Otomatis API Key AI SiWarga

class AIKeyCooldown
{
    private static $storageFile = __DIR__ . '/../../storage/ai_key_cooldown.json';

    // Durasi cooldown default berdasarkan status / kode error (dalam detik)
    const COOLDOWN_429  = 180; // 3 menit untuk limit kuota / rate limit (429)
    const COOLDOWN_5XX  = 30;  // 30 detik untuk server temporary overload (500, 503)
    const COOLDOWN_AUTH = 600; // 10 menit untuk key invalid / permission denied (400, 401, 403)

    private static function getKeyHash($apiKey)
    {
        return 'key_' . substr(hash('sha256', trim($apiKey)), 0, 16);
    }

    public static function readData()
    {
        if (!file_exists(self::$storageFile)) {
            return [];
        }
        $raw = @file_get_contents(self::$storageFile);
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private static function writeData(array $data)
    {
        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        @file_put_contents(self::$storageFile, $json, LOCK_EX);
    }

    /**
     * Catat error dan terapkan cooldown pada API Key
     */
    public static function markError($apiKey, $httpCode, $errorMsg = '')
    {
        if (empty($apiKey) || AIConfig::isMasked($apiKey)) return;

        $now = time();
        $cooldownSeconds = self::COOLDOWN_429;
        $reason = 'quota_limit_429';

        if ($httpCode === 429 || stripos($errorMsg, 'quota') !== false || stripos($errorMsg, 'rate limit') !== false) {
            $cooldownSeconds = self::COOLDOWN_429;
            $reason = 'quota_limit_429';
        } elseif (in_array((int)$httpCode, [500, 502, 503, 504])) {
            $cooldownSeconds = self::COOLDOWN_5XX;
            $reason = 'server_overload_' . $httpCode;
        } elseif (in_array((int)$httpCode, [400, 401, 403])) {
            $cooldownSeconds = self::COOLDOWN_AUTH;
            $reason = 'auth_error_' . $httpCode;
        }

        $hash = self::getKeyHash($apiKey);
        $data = self::readData();
        $data[$hash] = [
            'cooldown_until' => $now + $cooldownSeconds,
            'reason'         => $reason,
            'http_code'      => $httpCode,
            'error_msg'      => substr($errorMsg, 0, 150),
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        self::writeData($data);
    }

    /**
     * Bersihkan cooldown jika key berhasil digunakan (HTTP 200)
     */
    public static function markSuccess($apiKey)
    {
        if (empty($apiKey)) return;
        $hash = self::getKeyHash($apiKey);
        $data = self::readData();
        if (isset($data[$hash])) {
            unset($data[$hash]);
            self::writeData($data);
        }
    }

    /**
     * Cek apakah key sedang dalam masa cooldown
     */
    public static function isCoolingDown($apiKey)
    {
        if (empty($apiKey)) return false;
        $hash = self::getKeyHash($apiKey);
        $data = self::readData();
        if (isset($data[$hash])) {
            $until = $data[$hash]['cooldown_until'] ?? 0;
            if (time() < $until) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ambil sisa waktu cooldown dalam detik (0 jika tidak cooldown)
     */
    public static function getRemainingCooldown($apiKey)
    {
        if (empty($apiKey)) return 0;
        $hash = self::getKeyHash($apiKey);
        $data = self::readData();
        if (isset($data[$hash])) {
            $remaining = ($data[$hash]['cooldown_until'] ?? 0) - time();
            return max(0, $remaining);
        }
        return 0;
    }

    /**
     * Mengurutkan keyChains agar key yang SEHAT (tidak sedang cooldown) berada di urutan depan,
     * dan key yang sedang cooldown berada di urutan belakang.
     */
    public static function prioritizeKeyChains(array $keyChains)
    {
        $healthy = [];
        $cooldown = [];

        foreach ($keyChains as $chain) {
            $key = $chain['key'] ?? '';
            if (self::isCoolingDown($key)) {
                $chain['remaining_cooldown'] = self::getRemainingCooldown($key);
                $cooldown[] = $chain;
            } else {
                $chain['remaining_cooldown'] = 0;
                $healthy[] = $chain;
            }
        }

        // Urutkan key yang cooldown berdasarkan sisa waktu cooldown terkecil
        usort($cooldown, function($a, $b) {
            return ($a['remaining_cooldown'] ?? 0) <=> ($b['remaining_cooldown'] ?? 0);
        });

        // Gabungkan: yang sehat terlebih dahulu, diikuti yang sedang cooldown
        return array_merge($healthy, $cooldown);
    }

    /**
     * Reset semua status cooldown (utility)
     */
    public static function resetAll()
    {
        self::writeData([]);
    }
}

