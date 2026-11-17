<?php
session_start();

echo "<h2>🔍 CEK SESSION</h2>";

echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>User dari localStorage:</h3>";
echo "<pre>";
print_r(json_decode($_SESSION['siwarga_user'] ?? '{}', true));
echo "</pre>";

echo "<br><a href='/Rpl/login'>Kembali ke Login</a>";