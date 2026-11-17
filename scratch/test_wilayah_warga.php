<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/AIDataHelper.php';

$warga12 = $pdo->query("SELECT * FROM users WHERE id = 12")->fetch(PDO::FETCH_ASSOC);
$wilayah12 = AIDataHelper::getWilayahUser($pdo, $warga12);

echo "=== Warga 12 (Arjuna) ===\n";
print_r($wilayah12);

$warga11 = $pdo->query("SELECT * FROM users WHERE id = 11")->fetch(PDO::FETCH_ASSOC);
$wilayah11 = AIDataHelper::getWilayahUser($pdo, $warga11);

echo "\n=== Warga 11 (Agung) ===\n";
print_r($wilayah11);

