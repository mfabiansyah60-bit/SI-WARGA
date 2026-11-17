<?php
// public/tes_email.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Helpers/Mailer.php';

// GANTI PAKE EMAIL LU
$email_tujuan = "fabiansyah272@gmail.com"; 

echo "<h2>Mencoba mengirim OTP...</h2>";

// Panggil fungsi sendOTP dari Mailer.php
$result = Mailer::sendOTP($email_tujuan, "Test User", "123456");

echo "<pre>";
print_r($result);
echo "</pre>";