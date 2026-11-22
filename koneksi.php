<?php
$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "Kantin"
);

if(!$conn){
    die("koneksi gagal: ". mysqli_connect_error());
}

$pdo = new PDO("mysql:host=localhost;dbname=kantin;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

?>