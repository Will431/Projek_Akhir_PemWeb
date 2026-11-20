<?php require 'config.php'; need();
redirect($_SESSION['user']['role'] === 'penjual' ? 'seller_dashboard.php' : 'menu.php');
