<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>🧪 Test Gemini AI</h2>";

// 1. Cek API key
if (GEMINI_API_KEY === 'PASTE_API_KEY_LO_DI_SINI') {
    die("❌ <b>API key BELUM diisi!</b>");
}

echo "✅ API Key: " . substr(GEMINI_API_KEY, 0, 15) . "...<br><br>";

// 2. Test request ke Gemini
$url = GEMINI_ENDPOINT . '?key=' . GEMINI_API_KEY;

$body = json_encode([
    'contents' => [
        ['parts' => [['text' => 'Siapa presiden Indonesia? Jawab singkat dalam 1 kalimat.']]]
    ]
]);

echo "📡 Mengirim request ke Gemini...<br><br>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "📊 HTTP Code: <b style='font-size:20px;color:" . ($http_code == 200 ? "green" : "red") . ";'>$http_code</b><br><br>";

if ($curl_error) {
    echo "❌ <b>Curl Error:</b> $curl_error<br>";
}

if ($http_code == 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "<div style='background:#d1fae5;padding:20px;border-radius:10px;'>";
        echo "<h3 style='color:#059669;'>✅ GEMINI BERFUNGSI!</h3>";
        echo "<p><b>Jawaban Gemini:</b></p>";
        echo "<p style='font-size:18px;'>" . htmlspecialchars($data['candidates'][0]['content']['parts'][0]['text']) . "</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background:#fee2e2;padding:20px;border-radius:10px;'>";
    echo "<h3 style='color:#dc2626;'>❌ GEMINI ERROR</h3>";
    echo "<p><b>Response:</b></p>";
    echo "<pre style='background:white;padding:10px;border-radius:5px;'>";
    print_r(json_decode($response, true));
    echo "</pre>";
    echo "</div>";
}