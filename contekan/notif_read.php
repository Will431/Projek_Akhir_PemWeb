<?php require 'config.php'; need();
$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user']['id']]);
