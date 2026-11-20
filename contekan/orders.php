<?php require 'config.php'; need('mahasiswa');
$uid = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=? AND status='Selesai'"); $st->execute([$oid, $uid]); $o = $st->fetch();
    if ($o && ($_POST['action'] ?? '') === 'reorder') {            // pesan lagi -> isi keranjang
        foreach (itemsOf($pdo, $oid) as $it) {
            if (!$it['menu_id']) continue;
            $s = $pdo->prepare("SELECT stock FROM menu WHERE id=?"); $s->execute([$it['menu_id']]); $stock = (int)$s->fetchColumn();
            if ($stock > 0) $_SESSION['cart'][$it['menu_id']] = min($it['qty'], $stock);
        }
        redirect('cart.php');
    }
    if ($o && !$o['rated'] && ($_POST['action'] ?? '') === 'review') {   // ulasan & rating
        $rating = max(1, min(5, (int)$_POST['rating'])); $comment = trim($_POST['comment'] ?? '');
        foreach (itemsOf($pdo, $oid) as $it) if ($it['menu_id'])
            $pdo->prepare("INSERT INTO reviews(user_id,menu_id,rating,comment) VALUES(?,?,?,?)")->execute([$uid, $it['menu_id'], $rating, $comment]);
        $pdo->prepare("UPDATE orders SET rated=1 WHERE id=?")->execute([$oid]);
        flash('Terima kasih atas ulasan Anda!');
    }
    redirect('orders.php?view=riwayat');
}
$view = ($_GET['view'] ?? '') === 'riwayat' ? 'riwayat' : 'aktif';
$cond = $view === 'riwayat' ? "status='Selesai'" : "status<>'Selesai'";
$st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? AND $cond ORDER BY id DESC"); $st->execute([$uid]); $orders = $st->fetchAll();
$title = 'Pesanan'; require 'includes_header.php';
?>
<h4><?= $view === 'riwayat' ? 'Riwayat Pemesanan' : 'Status Pesanan' ?></h4>
<?php foreach ($orders as $o):
    $extra = '';
    if ($view === 'riwayat') {
        $extra = "<div class='d-flex gap-2 mt-2'><form method='post'><input type='hidden' name='order_id' value='{$o['id']}'><button name='action' value='reorder' class='btn btn-sm btn-outline-primary'>🔁 Pesan lagi</button></form>";
        $extra .= $o['rated'] ? "<span class='small text-body-secondary align-self-center'>Sudah diulas</span>"
            : "<form method='post' class='d-flex gap-1'><input type='hidden' name='order_id' value='{$o['id']}'><input type='hidden' name='action' value='review'>
               <select name='rating' class='form-select form-select-sm' style='width:auto'>" . implode('', array_map(fn($n) => "<option value='$n'>" . str_repeat('⭐', $n) . "</option>", [5, 4, 3, 2, 1])) . "</select>
               <input name='comment' class='form-control form-control-sm' placeholder='Komentar'><button class='btn btn-sm btn-success'>Kirim</button></form>";
        $extra .= '</div>';
    }
    echo orderCard($pdo, $o, $extra);
endforeach; if (!$orders): ?><div class="alert alert-secondary">Belum ada data.</div><?php endif;
require 'includes_footer.php'; ?>
