<?php
$u = $_SESSION['user'] ?? null;
$theme = ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';   // cookie tema
$nots = []; $unread = 0;
if ($u) {
    $st = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6"); $st->execute([$u['id']]);
    $nots = $st->fetchAll();
    $c = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $c->execute([$u['id']]); $unread = $c->fetchColumn();
    $links = $u['role'] === 'penjual'
        ? ['seller_dashboard.php' => 'Dashboard', 'seller_menu.php' => 'Menu & Stok', 'seller_orders.php' => 'Pesanan', 'seller_report.php' => 'Laporan']
        : ['menu.php' => 'Katalog', 'cart.php' => 'Keranjang (' . count($_SESSION['cart'] ?? []) . ')', 'orders.php' => 'Status Pesanan', 'orders.php?view=riwayat' => 'Riwayat'];
}
?><!DOCTYPE html>
<html lang="id" data-bs-theme="<?= $theme ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'KantinKu') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet"></head><body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top"><div class="container">
  <a class="navbar-brand fw-bold text-primary" href="index.php">🍽️ KantinKu</a>
  <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nv"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="nv">
    <ul class="navbar-nav me-auto"><?php foreach ($links ?? [] as $href => $label): ?>
      <li class="nav-item"><a class="nav-link" href="<?= $href ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
    <button class="btn btn-sm btn-outline-secondary me-2" id="themeBtn" type="button">🌓</button>
    <?php if ($u): ?>
    <div class="dropdown me-2"><button class="btn btn-sm btn-outline-secondary position-relative" id="notifBtn" data-bs-toggle="dropdown">🔔
      <?php if ($unread): ?><span id="notifBadge" class="badge text-bg-danger"><?= $unread ?></span><?php endif; ?></button>
      <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:290px">
      <?php foreach ($nots as $n): ?><li class="small border-bottom py-1 <?= $n['is_read'] ? 'text-body-secondary' : 'fw-semibold' ?>"><?= e($n['message']) ?></li><?php endforeach; ?>
      <?php if (!$nots): ?><li class="small text-body-secondary">Belum ada notifikasi</li><?php endif; ?></ul></div>
    <span class="small me-2"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</span>
    <a class="btn btn-sm btn-outline-danger" href="logout.php">Keluar</a><?php endif; ?>
  </div></div></nav>
<main class="container py-4">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= $t ?> alert-dismissible"><?= e($m) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
