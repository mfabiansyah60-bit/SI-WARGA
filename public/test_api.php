<?php
$apiKey = "";
$url = 'https://generativelanguage.googleapis.com/v1beta/interactions';
$body = json_encode(["model" => "gemini-3.8-flash", "input" => "Halo"]);
$headers = ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 120 detik!
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // Paksa HTTP 1.1

$start = microtime(true);
$response = curl_exec($ch);
$duration = round(microtime(true) - $start, 2);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h2>Hasil Test (Durasi: {$duration} detik)</h2>";
echo "<b>HTTP Code:</b> $http_code<br>";
echo "<b>cURL Error:</b> " . ($curl_error ?: 'Tidak ada') . "<br><br>";
echo "<b>Response:</b><pre>" . htmlspecialchars(substr($response ?? '', 0, 1000)) . "</pre>";