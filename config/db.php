<?php
require_once __DIR__ . '/app.php';

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = (int) (getenv('MYSQLPORT') ?: 3306);
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'seleno_lelang';

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');