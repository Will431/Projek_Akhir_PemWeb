<?php require 'config.php'; need('mahasiswa');
$_SESSION['cart'] = $_SESSION['cart'] ?? [];          // array: [menu_id => qty]
$cart = &$_SESSION['cart'];
function getMenu($pdo, $id) { $s = $pdo->prepare("SELECT * FROM menu WHERE id=?"); $s->execute([$id]); return $s->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if (in_array($act, ['add', 'plus'])) {
        $m = getMenu($pdo, $id);
        if ($m && ($cart[$id] ?? 0) < $m['stock']) $cart[$id] = ($cart[$id] ?? 0) + 1;
        else flash('Stok tidak mencukupi.', 'warning');
        if ($act === 'add') flash($m['name'] . ' ditambahkan ke keranjang.');
    } elseif ($act === 'minus') { if (isset($cart[$id]) && --$cart[$id] <= 0) unset($cart[$id]); }
    elseif ($act === 'del') unset($cart[$id]);
    elseif ($act === 'checkout' && $cart) {
        $pickup = $_POST['pickup'] ?? ''; $pay = $_POST['pay'] ?? 'Tunai';
        if (!preg_match('/^\d{2}:\d{2}$/', $pickup) || !in_array($pay, ['Tunai', 'QRIS', 'GoPay', 'OVO'])) { flash('Data checkout tidak valid.', 'danger'); redirect('cart.php'); }
        try {
            $pdo->beginTransaction(); $total = 0; $rows = [];
            foreach ($cart as $mid => $qty) {                       // cek stok dengan lock baris
                $s = $pdo->prepare("SELECT * FROM menu WHERE id=? FOR UPDATE"); $s->execute([$mid]); $m = $s->fetch();
                if (!$m || $m['stock'] < $qty) throw new Exception('Stok tidak cukup untuk ' . ($m['name'] ?? 'salah satu menu'));
                $rows[] = [$m, $qty]; $total += $m['price'] * $qty;
            }
            $status = $pay === 'Tunai' ? 'Belum' : 'Lunas';
            $pdo->prepare("INSERT INTO orders(user_id,total,pickup_time,pay_method,pay_status) VALUES(?,?,?,?,?)")
                ->execute([$_SESSION['user']['id'], $total, $pickup, $pay, $status]);
            $oid = $pdo->lastInsertId();
            foreach ($rows as [$m, $qty]) {
                $pdo->prepare("INSERT INTO order_items(order_id,menu_id,name,price,qty) VALUES(?,?,?,?,?)")->execute([$oid, $m['id'], $m['name'], $m['price'], $qty]);
                $pdo->prepare("UPDATE menu SET stock=stock-? WHERE id=?")->execute([$qty, $m['id']]);
            }
            $pdo->commit(); $cart = [];
            notify($pdo, $_SESSION['user']['id'], "Pesanan #$oid dibuat, menunggu konfirmasi penjual.");
            notifySellers($pdo, "Pesanan baru #$oid senilai " . rp($total) . '.');
            flash("Pesanan #$oid berhasil dibuat!"); redirect('orders.php');
        } catch (Exception $ex) { $pdo->rollBack(); flash($ex->getMessage(), 'danger'); }
    }
    redirect('cart.php');
}
// Ambil detail item keranjang
$items = []; $total = 0;
foreach ($cart as $id => $qty) { if ($m = getMenu($pdo, $id)) { $m['qty'] = $qty; $items[] = $m; $total += $m['price'] * $qty; } }
$title = 'Keranjang'; require 'includes_header.php';
?>
<h4>Keranjang</h4>
<?php if (!$items): ?><div class="alert alert-secondary">Keranjang masih kosong. <a href="menu.php">Lihat katalog</a></div>
<?php else: ?>
<div class="row g-3"><div class="col-lg-7"><ul class="list-group">
<?php foreach ($items as $i): ?>
  <li class="list-group-item d-flex justify-content-between align-items-center">
    <div><?= e($i['name']) ?><br><small class="text-body-secondary"><?= rp($i['price']) ?></small></div>
    <form method="post" class="d-flex align-items-center gap-1"><input type="hidden" name="id" value="<?= $i['id'] ?>">
      <button name="action" value="minus" class="btn btn-sm btn-outline-secondary">−</button><span class="px-2"><?= $i['qty'] ?></span>
      <button name="action" value="plus" class="btn btn-sm btn-outline-secondary">+</button>
      <button name="action" value="del" class="btn btn-sm btn-outline-danger">🗑</button></form></li>
<?php endforeach; ?>
  <li class="list-group-item d-flex justify-content-between fw-bold"><span>Total</span><span class="text-primary"><?= rp($total) ?></span></li></ul></div>
<div class="col-lg-5"><div class="card"><div class="card-body"><h5>Checkout</h5>
  <form method="post"><input type="hidden" name="action" value="checkout">
    <label class="form-label">Waktu pengambilan</label>
    <select name="pickup" class="form-select mb-3"><?php foreach (['10:00', '11:00', '12:00', '12:30', '13:00', '14:00', '15:00'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
    <label class="form-label">Pembayaran</label>
    <select name="pay" class="form-select mb-3"><option value="Tunai">Tunai (bayar di kasir)</option><option>QRIS</option><option>GoPay</option><option>OVO</option></select>
    <button class="btn btn-primary w-100">Konfirmasi Pesanan · <?= rp($total) ?></button></form></div></div></div></div>
<?php endif; require 'includes_footer.php'; ?>
