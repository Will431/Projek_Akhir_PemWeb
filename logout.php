<?php 
include 'config.php';
$_SESSION = []; session_destroy();
redirect('login.php');
?>