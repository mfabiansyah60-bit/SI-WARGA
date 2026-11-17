<?php
require_once __DIR__ . '/../config/database.php';
$rows = $pdo->query("SELECT id, nama, deskripsi, syarat FROM pelayanan")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

