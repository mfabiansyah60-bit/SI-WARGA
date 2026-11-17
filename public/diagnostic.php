<?php
$apiKey = "";

$tests = [
    'A. gemini-3.8-flash :generateContent' => [
        'url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=' . urlencode($apiKey),
        'body' => json_encode(['contents' => [['parts' => [['text' => 'Halo']]]]]),
        'headers' => ['Content-Type: application/json']
    ],
    'B. gemini-flash-latest :generateContent' => [
        'url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . urlencode($apiKey),
        'body' => json_encode(['contents' => [['parts' => [['text' => 'Halo']]]]]),
        'headers' => ['Content-Type: application/json']
    ],
    'C. gemini-2.0-flash :generateContent' => [
        'url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey),
        'body' => json_encode(['contents' => [['parts' => [['text' => 'Halo']]]]]),
        'headers' => ['Content-Type: application/json']
    ],
    'D. interactions (x-goog-api-key)' => [
        'url' => 'https://generativelanguage.googleapis.com/v1beta/interactions',
        'body' => json_encode(['model' => 'gemini-3.8-flash', 'input' => 'Halo']),
        'headers' => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey]
    ],
    'E. interactions (?key=)' => [
        'url' => 'https://generativelanguage.googleapis.com/v1beta/interactions?key=' . urlencode($apiKey),
        'body' => json_encode(['model' => 'gemini-3.8-flash', 'input' => 'Halo']),
        'headers' => ['Content-Type: application/json']
    ],
];

foreach ($tests as $name => $t) {
    echo "<hr><h3>$name</h3>";
    $ch = curl_init($t['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $t['body']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $t['headers']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    $start = microtime(true);
    $resp = curl_exec($ch);
    $dur = round(microtime(true) - $start, 2);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $status = ($code == 200) ? "<b style='color:green'>✅ BERHASIL</b>" : "<b style='color:red'>❌ GAGAL</b>";
    echo "<p>Durasi: <b>{$dur}s</b> | HTTP: <b>{$code}</b> | Error: " . ($err ?: '-') . " | Status: $status</p>";
    echo "<pre style='background:#f0f0f0;padding:10px;max-height:200px;overflow:auto'>" . htmlspecialchars(substr($resp ?: '(empty)', 0, 800)) . "</pre>";
}