<?php
// app/Helpers/AIConfig.php
// Manajemen Konfigurasi AI SiWarga (Multi-Key Failover, Dynamic Models, Masking)

class AIConfig
{
    private static $configFile = __DIR__ . '/../../storage/ai_config.json';

    // Default Fallback Models
    private static $defaultModels = [
        'gemini-3.6-flash',
        'gemini-3.5-flash',
        'gemini-3.7-flash',
        'gemini-3.8-flash',
        'gemini-flash-latest',
        'gemini-2.5-flash',
    ];

    /**
     * Ambil konfigurasi saat ini
     */
    public static function get()
    {
        $default = [
            'provider'         => 'gemini',
            'api_key'          => getenv('GEMINI_API_KEY') ?: '',
            'model'            => 'gemini-3.6-flash',
            'available_models' => self::$defaultModels,
            'temperature'      => 0.5,
            'backup_keys'      => [], // list of ['key' => '...', 'model' => '...']
            'rag_mode'         => 'Local Vector DB / Pinecone',
            'system_prompt'    => 'Anda adalah "SIWARGA AI", asisten digital resmi pelayanan publik desa. Tugas Anda memberikan pelayanan informasi administrasi desa yang ramah, santun, komunikatif, dan presisi. Jika warga bertanya tentang identitas Anda, perkenalkan diri Anda dengan jelas sebagai Asisten Resmi Desa.',
            'fallback_message' => 'Maaf, saya adalah SIWARGA AI (Asisten Digital Desa). Informasi tersebut belum tersedia di basis data resmi atau berada di luar layanan administrasi desa. Ada yang bisa saya bantu terkait pengajuan surat atau informasi desa lainnya?',
        ];

        if (file_exists(self::$configFile)) {
            $raw = @file_get_contents(self::$configFile);
            $saved = json_decode($raw, true);
            if (is_array($saved)) {
                // Merge dengan default
                foreach ($saved as $k => $v) {
                    $default[$k] = $v;
                }
            }
        }

        // Jika api_key kosong, ambil dari .env
        if (empty($default['api_key'])) {
            $default['api_key'] = getenv('GEMINI_API_KEY') ?: '';
        }

        // Pastikan available_models tidak kosong
        if (empty($default['available_models']) || !is_array($default['available_models'])) {
            $default['available_models'] = self::$defaultModels;
        }

        return $default;
    }

    /**
     * Simpan konfigurasi ke storage/ai_config.json
     */
    public static function save(array $data)
    {
        $current = self::get();

        // Update provider
        $current['provider'] = 'gemini';

        // Update API Key Utama jika diisi & bukan masked
        if (!empty($data['api_key']) && !self::isMasked($data['api_key'])) {
            $current['api_key'] = trim($data['api_key']);
        }

        // Update Model Utama
        if (!empty($data['model'])) {
            $current['model'] = trim($data['model']);
        }

        // Update Temperature
        if (isset($data['temperature'])) {
            $temp = (float)$data['temperature'];
            $current['temperature'] = max(0.0, min(1.0, $temp));
        }

        // Update Backup Keys (list of ['key' => '...', 'model' => '...'])
        if (isset($data['backup_keys']) && is_array($data['backup_keys'])) {
            $sanitizedBackups = [];
            foreach ($data['backup_keys'] as $idx => $bk) {
                $bKey = trim($bk['key'] ?? '');
                $bModel = trim($bk['model'] ?? $current['model']);

                // Jika key bertopeng, pertahankan nilai asli jika ada
                if (self::isMasked($bKey)) {
                    $originalKey = $current['backup_keys'][$idx]['key'] ?? '';
                    if (!empty($originalKey)) {
                        $sanitizedBackups[] = [
                            'key'   => $originalKey,
                            'model' => $bModel,
                        ];
                    }
                } elseif (!empty($bKey)) {
                    $sanitizedBackups[] = [
                        'key'   => $bKey,
                        'model' => $bModel,
                    ];
                }
            }
            $current['backup_keys'] = $sanitizedBackups;
        }

        // Update available models jika ada
        if (!empty($data['available_models']) && is_array($data['available_models'])) {
            $current['available_models'] = array_values(array_unique(array_filter($data['available_models'])));
        }

        // System prompt & Fallback message
        if (isset($data['system_prompt'])) {
            $current['system_prompt'] = trim($data['system_prompt']);
        }
        if (isset($data['fallback_message'])) {
            $current['fallback_message'] = trim($data['fallback_message']);
        }

        $dir = dirname(self::$configFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return @file_put_contents(self::$configFile, $json, LOCK_EX) !== false;
    }

    /**
     * Reset konfigurasi ke setelan awal (.env)
     */
    public static function resetToDefault()
    {
        $default = [
            'provider'         => 'gemini',
            'api_key'          => getenv('GEMINI_API_KEY') ?: '',
            'model'            => 'gemini-3.6-flash',
            'available_models' => self::$defaultModels,
            'temperature'      => 0.5,
            'backup_keys'      => [],
            'rag_mode'         => 'Local Vector DB / Pinecone',
            'system_prompt'    => 'Anda adalah "SIWARGA AI", asisten digital resmi pelayanan publik desa. Tugas Anda memberikan pelayanan informasi administrasi desa yang ramah, santun, komunikatif, dan presisi. Jika warga bertanya tentang identitas Anda, perkenalkan diri Anda dengan jelas sebagai Asisten Resmi Desa.',
            'fallback_message' => 'Maaf, saya adalah SIWARGA AI (Asisten Digital Desa). Informasi tersebut belum tersedia di basis data resmi atau berada di luar layanan administrasi desa. Ada yang bisa saya bantu terkait pengajuan surat atau informasi desa lainnya?',
        ];

        $json = json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return @file_put_contents(self::$configFile, $json, LOCK_EX) !== false;
    }

    /**
     * Sensor / masking API key untuk keamanan tampilan frontend
     */
    public static function maskKey($key)
    {
        if (empty($key)) return '';
        $len = strlen($key);
        if ($len <= 8) return str_repeat('•', 16);
        $start = substr($key, 0, 4);
        $end = substr($key, -4);
        return $start . str_repeat('•', 24) . $end;
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
     * Fetch list model yang didukung secara dinamis dari API Google Gemini
     */
    public static function fetchSupportedModels($apiKey = null)
    {
        if (empty($apiKey)) {
            $cfg = self::get();
            $apiKey = $cfg['api_key'];
        }
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API Key kosong.', 'models' => self::$defaultModels];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            return ['success' => false, 'message' => 'Koneksi gagal: ' . $curl_err, 'models' => self::$defaultModels];
        }

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['models']) && is_array($data['models'])) {
                $models = [];
                // Model rekomendasi yang kita prioritaskan di urutan atas
                $priority = ['gemini-3.6-flash', 'gemini-3.5-flash', 'gemini-3.7-flash', 'gemini-3.8-flash', 'gemini-flash-latest'];
                
                $detected = [];
                foreach ($data['models'] as $m) {
                    $methods = $m['supportedGenerationMethods'] ?? [];
                    if (in_array('generateContent', $methods)) {
                        $name = str_replace('models/', '', $m['name']);
                        // Hanya sertakan model berbasis teks/chat flash atau pro
                        if (strpos($name, 'embedding') === false && strpos($name, 'aqa') === false) {
                            $detected[] = $name;
                        }
                    }
                }

                // Susun urutan: prioritaskan model flash yang stabil
                foreach ($priority as $p) {
                    $models[] = $p;
                }
                foreach ($detected as $d) {
                    if (!in_array($d, $models)) {
                        $models[] = $d;
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Berhasil mendeteksi ' . count($models) . ' model didukung!',
                    'models'  => $models,
                ];
            }
        }

        $errData = json_decode($response, true);
        $errMsg = $errData['error']['message'] ?? "HTTP Status $http_code";

        return [
            'success' => false,
            'message' => 'Gagal verifikasi API Key: ' . $errMsg,
            'models'  => self::$defaultModels,
        ];
    }
}
