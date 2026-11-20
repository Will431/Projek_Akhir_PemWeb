<?php
// Koneksi MySQL (PDO), session, dan fungsi bantu
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=kantin;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
const STATUS = ['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'];   // array urutan status
const KATEGORI = ['Makanan', 'Minuman', 'Snack'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp' . number_format((int)$n, 0, ',', '.'); }
function flash($msg, $type = 'success') { $_SESSION['flash'] = [$msg, $type]; }
function redirect($url) { header("Location: $url"); exit; }

// Hak akses berdasarkan session
function need($role = null) {
    if (empty($_SESSION['user'])) redirect('login.php');
    if ($role && $_SESSION['user']['role'] !== $role) { http_response_code(403); exit('Akses ditolak.'); }
}
function notify($pdo, $userId, $msg) {
    $pdo->prepare("INSERT INTO notifications(user_id,message) VALUES(?,?)")->execute([$userId, $msg]);
}
function notifySellers($pdo, $msg) {
    $pdo->prepare("INSERT INTO notifications(user_id,message) SELECT id,? FROM users WHERE role='penjual'")->execute([$msg]);
}
function itemsOf($pdo, $orderId) {
    $s = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $s->execute([$orderId]); return $s->fetchAll();
}
function statusBar($status) {
    $step = array_search($status, STATUS) + 1;
    $pct = $step / count(STATUS) * 100;
    $color = $status === 'Selesai' ? 'success' : ($status === 'Siap Diambil' ? 'info' : 'warning');
    return "<div class='progress my-2' style='height:8px'><div class='progress-bar bg-$color' style='width:$pct%'></div></div>";
}
function orderCard($pdo, $o, $extra = '') {
    $items = array_map(fn($i) => $i['qty'] . '× ' . e($i['name']), itemsOf($pdo, $o['id']));
    return "<div class='card mb-3'><div class='card-body'><div class='d-flex justify-content-between'>
      <strong>#{$o['id']}</strong><span class='badge text-bg-secondary'>" . e($o['status']) . "</span></div>
      <div class='text-body-secondary small'>" . implode(', ', $items) . "</div>" . statusBar($o['status']) . "
      <div class='d-flex justify-content-between small'><span>Ambil " . e($o['pickup_time']) . " · " . e($o['pay_method']) . " (" . e($o['pay_status']) . ")</span>
      <strong class='text-primary'>" . rp($o['total']) . "</strong></div>$extra</div></div>";
}
