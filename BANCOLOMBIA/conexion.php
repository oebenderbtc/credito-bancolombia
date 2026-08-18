<?php
$host   = getenv('DB_HOST') ?: 'srv1578.hstgr.io';
$port   = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'u423799403_eldemon777';
$user   = getenv('DB_USER') ?: 'u423799403_eldemon777';
$pass   = getenv('DB_PASS') ?: '777Eldemon';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass, [PDO::ATTR_PERSISTENT => true]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}
