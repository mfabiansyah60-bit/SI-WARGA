<?php
require_once __DIR__ . '/../config/database.php';

try {
    $colsRt = $pdo->query("DESCRIBE rt")->fetchAll(PDO::FETCH_ASSOC);
    echo "=== TABLE rt ===" . PHP_EOL;
    foreach ($colsRt as $c) {
        echo "{$c['Field']} ({$c['Type']})" . PHP_EOL;
    }
    
    $colsRw = $pdo->query("DESCRIBE rw")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== TABLE rw ===" . PHP_EOL;
    foreach ($colsRw as $c) {
        echo "{$c['Field']} ({$c['Type']})" . PHP_EOL;
    }

    $sampleRw = $pdo->query("SELECT * FROM rw LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== SAMPLE rw ===" . PHP_EOL;
    print_r($sampleRw);

    $sampleRt = $pdo->query("SELECT * FROM rt LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== SAMPLE rt ===" . PHP_EOL;
    print_r($sampleRt);

    $sampleWarga = $pdo->query("SELECT id, name, role, rt, rw FROM users WHERE role = 'warga' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== SAMPLE warga ===" . PHP_EOL;
    print_r($sampleWarga);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

