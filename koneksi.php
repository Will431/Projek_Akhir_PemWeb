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

?>