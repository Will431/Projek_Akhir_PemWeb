<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'kantin';


$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);


$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die('Koneksi gagal: ' . mysqli_connect_error());
mysqli_set_charset($conn, 'utf8mb4');
