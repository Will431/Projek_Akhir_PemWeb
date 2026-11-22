<?php 
require 'config.php'; 
need('mahasiswa');

$_SESSION['cart'] = $_SESSION['cart'] ?? [];          
$cart = &$_SESSION['cart'];
function getMenu($pdo, $id) { $s = $pdo->prepare(
    "SELECT * FROM menu WHERE id=?"
    ); 
    $s->execute([$id]); 
    return $s->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; 
    $id = (int)($_POST['id'] ?? 0);
    if (in_array($act, ['add', 'plus'])) {
        $m = getMenu($pdo, $id);
        
        if ($m && ($cart[$id] ?? 0) < $m['stock']) $cart[$id] = ($cart[$id] ?? 0) + 1;
        else flash('Stok tidak mencukupi.', 'warning');
        
        if ($act === 'add') flash($m['name'] . ' ditambahkan ke keranjang.');
    } elseif ($act === 'minus') { 
        if (isset($cart[$id]) && --$cart[$id] <= 0) unset($cart[$id]); 
        }
    elseif ($act === 'del') unset($cart[$id]);
    elseif ($act === 'checkout' && $cart) {
        $pickup = $_POST['pickup'] ?? ''; $pay = $_POST['pay'] ?? 'Tunai';
        if (!preg_match('/^\d{2}:\d{2}$/', $pickup) || !in_array($pay, ['Tunai', 'QRIS', 'GoPay', 'OVO'])) { 
            flash('Data checkout tidak valid.', 'danger'); redirect('cart.php'); 
            }
        try {
            $pdo->beginTransaction(); $total = 0; $rows = [];
            foreach ($cart as $mid => $qty) {                       // cek stok dengan lock baris
                $s = $pdo->prepare(
                    "SELECT * FROM menu WHERE id=? FOR UPDATE"
                    ); 
                $s->execute([$mid]); $m = $s->fetch();
                if (!$m || $m['stock'] < $qty) throw new Exception('Stok tidak cukup untuk ' . ($m['name'] ?? 'salah satu menu'));
                $rows[] = [$m, $qty]; $total += $m['price'] * $qty;
            }

            $status = $pay === 'Tunai' ? 'Belum' : 'Lunas';
            $pdo->prepare(
                "INSERT INTO orders(user_id,total,pickup_time,pay_method,pay_status) VALUES(?,?,?,?,?)"
                )
                ->execute([$_SESSION['user']['id'], $total, $pickup, $pay, $status]);
            $oid = $pdo->lastInsertId();

            foreach ($rows as [$m, $qty]) {
                $pdo->prepare(
                    "INSERT INTO order_items(order_id,menu_id,name,price,qty) VALUES(?,?,?,?,?)"
                    )->execute([$oid, $m['id'], $m['name'], $m['price'], $qty]);
                $pdo->prepare(
                    "UPDATE menu SET stock=stock-? WHERE id=?"
                    )->execute([$qty, $m['id']]);
            }
            $pdo->commit(); $cart = [];
            notify($pdo, $_SESSION['user']['id'], "Pesanan #$oid dibuat, menunggu konfirmasi penjual.");
            notifySellers($pdo, "Pesanan baru #$oid senilai " . rp($total) . '.');
            flash("Pesanan #$oid berhasil dibuat!"); redirect('orders.php');
        } catch (Exception $ex) { $pdo->rollBack(); flash($ex->getMessage(), 'danger'); }
    }
    redirect('cart.php');
}

$items = []; $total = 0;
foreach ($cart as $id => $qty) { 
    if ($m = getMenu($pdo, $id)) { $m['qty'] = $qty; $items[] = $m; $total += $m['price'] * $qty; } 
}

$title = 'Keranjang';
$css = <<<'CSS'
.list{list-style:none;margin:0;padding:0;border:1px solid var(--bd);border-radius:12px;background:var(--card)}.list li{padding:12px 14px;border-bottom:1px solid var(--bd)}.list li:last-child{border:0}
.cart-layout{display:grid;grid-template-columns:7fr 5fr;gap:16px;align-items:start}
@media(max-width:760px){.cart-layout{grid-template-columns:1fr}}
CSS;
$head = "<style>$css</style>";
pageStart($title, $head);
?>
 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang</title>
 </head>
 <body>
    <h3>Keranjang</h3>
    <?php if (!$items): ?><div class="notice">Keranjang masih kosong.&nbsp;<a href="menu.php">Lihat katalog</a></div>
    <?php else: ?>
    <div class="cart-layout"><div><ul class="list">
    <?php foreach ($items as $i): ?>
    <li class="flex between items-center">
    <div><?= e($i['name']) ?><br><small class="muted"><?= rp($i['price']) ?></small></div>
    <form method="post" class="flex items-center gap"><input type="hidden" name="id" value="<?= $i['id'] ?>">
      <button name="action" value="minus" class="btn btn-small">−</button><span><?= $i['qty'] ?></span>
      <button name="action" value="plus" class="btn btn-small">+</button>
      <button name="action" value="del" class="btn btn-small btn-del">🗑</button></form></li>
    <?php endforeach; ?>
    <li class="flex between bold"><span>Total</span><span class="price"><?= rp($total) ?></span></li></ul></div>
    <div class="card"><h4>Checkout</h4>
    <form method="post"><input type="hidden" name="action" value="checkout">
    <div class="field"><label class="lbl">Waktu pengambilan</label>
    <select name="pickup" class="input"><?php foreach (['10:00', '11:00', '12:00', '12:30', '13:00', '14:00', '15:00'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="field"><label class="lbl">Pembayaran</label>
    <select name="pay" class="input"><option value="Tunai">Tunai (bayar di kasir)</option><option>QRIS</option><option>GoPay</option><option>OVO</option></select></div>
    <button class="btn btn-main btn-block">Konfirmasi Pesanan · <?= rp($total) ?></button></form></div></div>
<?php endif; pageEnd(); ?>
 </body>
 </html>
