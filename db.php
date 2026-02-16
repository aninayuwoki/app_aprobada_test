<?php
$host = '127.0.0.1';
$db = 'sis_certifi_cpateec';
$user = 'root';
$pass = 'configBspwm13';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>