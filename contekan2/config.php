<?php
// Koneksi MySQL (PDO), session, dan fungsi bantu
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=kantin;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
// Koneksi mysqli (dipakai register.php)
$conn = mysqli_connect('localhost', 'root', '', 'kantin');
if (!$conn) die('Koneksi gagal: ' . mysqli_connect_error());
mysqli_set_charset($conn, 'utf8mb4');

// Endpoint AJAX: tandai notifikasi dibaca (dipanggil app.js)
if (!empty($_POST['notif_read']) && !empty($_SESSION['user'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user']['id']]);
    exit;
}

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
    $color = $status === 'Selesai' ? 'ok' : ($status === 'Siap Diambil' ? 'info' : 'warn');
    return "<div class='track'><div class='fill fill-$color' style='width:$pct%'></div></div>";
}
function orderCard($pdo, $o, $extra = '') {
    $items = array_map(fn($i) => $i['qty'] . '× ' . e($i['name']), itemsOf($pdo, $o['id']));
    return "<div class='card'><div class='flex between'><strong>#{$o['id']}</strong><span class='badge'>" . e($o['status']) . "</span></div>
      <div class='muted small'>" . implode(', ', $items) . "</div>" . statusBar($o['status']) . "
      <div class='flex between small'><span>Ambil " . e($o['pickup_time']) . " · " . e($o['pay_method']) . " (" . e($o['pay_status']) . ")</span>
      <strong class='price'>" . rp($o['total']) . "</strong></div>$extra</div>";
}

// Arahkan pengguna ke halaman sesuai peran
function home() { redirect($_SESSION['user']['role'] === 'penjual' ? 'seller_dashboard.php' : 'menu.php'); }

// Kerangka halaman: bagian atas (header + navigasi)
function pageStart($title = 'KantinKu', $head = '') {
    global $pdo;
    $u = $_SESSION['user'] ?? null;
    $theme = ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';   // cookie tema
    $nots = []; $unread = 0; $links = [];
    if ($u) {
        $st = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6"); $st->execute([$u['id']]);
        $nots = $st->fetchAll();
        $c = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $c->execute([$u['id']]); $unread = $c->fetchColumn();
        $links = $u['role'] === 'penjual'
            ? ['seller_dashboard.php' => 'Dashboard', 'seller_menu.php' => 'Menu & Stok', 'seller_orders.php' => 'Pesanan', 'seller_dashboard.php#laporan' => 'Laporan']
            : ['menu.php' => 'Katalog', 'cart.php' => 'Keranjang (' . count($_SESSION['cart'] ?? []) . ')', 'orders.php' => 'Status Pesanan', 'orders.php?view=riwayat' => 'Riwayat'];
    }
    ?><!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - KantinKu</title>
<link rel="stylesheet" href="assets/style.css"><?= $head ?></head><body>
<header class="topbar"><div class="wrap flex items-center gap wrapf">
  <a class="brand" href="login.php">🍽️ KantinKu</a>
  <nav class="nav-links flex wrapf"><?php foreach ($links as $href => $label): ?><a href="<?= $href ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
  <span class="grow"></span>
  <button class="btn btn-small" id="themeBtn" type="button">🌓</button>
  <?php if ($u): ?>
  <div class="notif"><button class="btn btn-small" id="notifBtn" type="button">🔔<?php if ($unread): ?><span id="notifBadge" class="notif-badge"><?= $unread ?></span><?php endif; ?></button>
    <div class="notif-panel" id="notifPanel">
      <?php foreach ($nots as $n): ?><div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>"><?= e($n['message']) ?></div><?php endforeach; ?>
      <?php if (!$nots): ?><div class="notif-item">Belum ada notifikasi</div><?php endif; ?></div></div>
  <span class="small muted"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</span>
  <a class="btn btn-small btn-del" href="login.php?logout=1">Keluar</a><?php endif; ?>
</div></header>
<main class="wrap page">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="notice <?= e($t) ?>"><span><?= e($m) ?></span><button type="button" class="notice-close">×</button></div><?php endif; ?>
<?php
}

// Kerangka halaman: bagian bawah
function pageEnd() {
    echo '</main><script src="assets/app.js"></script></body></html>';
}
