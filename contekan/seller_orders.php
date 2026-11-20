<?php require 'config.php'; need('penjual');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)$_POST['order_id'];
    $s = $pdo->prepare("SELECT * FROM orders WHERE id=?"); $s->execute([$oid]); $o = $s->fetch();
    $idx = $o ? array_search($o['status'], STATUS) : false;
    if ($o && $idx !== false && $idx < count(STATUS) - 1) {
        $new = STATUS[$idx + 1];
        $payNow = $new === 'Selesai' ? ", pay_status='Lunas'" : '';
        $pdo->prepare("UPDATE orders SET status=? $payNow WHERE id=?")->execute([$new, $oid]);
        $msg = "Pesanan #$oid: $new" . ($new === 'Siap Diambil' ? ' - silakan ambil pukul ' . $o['pickup_time'] : '') . '.';
        notify($pdo, $o['user_id'], $msg);                       // notifikasi ke mahasiswa
    }
    redirect('seller_orders.php');
}
$orders = $pdo->query("SELECT o.*, u.name uname FROM orders o JOIN users u ON u.id=o.user_id WHERE o.status<>'Selesai' ORDER BY o.pickup_time, o.id")->fetchAll();
$title = 'Pesanan Masuk'; require 'includes_header.php';
?>
<h4>Pesanan Masuk</h4>
<?php foreach ($orders as $o):
    $next = STATUS[array_search($o['status'], STATUS) + 1];
    echo orderCard($pdo, $o, "<div class='d-flex justify-content-between align-items-center mt-2'><small>Pemesan: " . e($o['uname']) . "</small>
      <form method='post'><input type='hidden' name='order_id' value='{$o['id']}'><button class='btn btn-sm btn-primary'>Ubah ke: $next</button></form></div>");
endforeach; if (!$orders): ?><div class="alert alert-secondary">Tidak ada pesanan aktif.</div><?php endif;
require 'includes_footer.php'; ?>
