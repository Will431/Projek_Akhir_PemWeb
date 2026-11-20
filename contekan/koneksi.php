<?php
// Koneksi MySQL/MariaDB dengan mysqli
$conn = mysqli_connect('localhost', 'root', '', 'kantin');
if (!$conn) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
