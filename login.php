<?php
    include 'koneksi.php';
    if (!empty($_SESSION['user'])) redirect('index.php');
    $error = '';

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        email = $_POST['email'] ?? '';
        $pass = $_POST['password'] ?? '';
        
        $query = mysqli_prepare(
            $conn,
            "SELECT * FROM USERS WHERE email = ?"
        );
        $st->execute([$email]); $user = $st->fetch();

        
    }