<?php 
require 'config.php'; 
need('mahasiswa');

$uid = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $st = $pdo->prepare(
        "SELECT * FROM orders WHERE id=? AND user_id=? AND status='Selesai'"
        ); 
    $st->execute([$oid, $uid]); $o = $st->fetch();
    
    if ($o && ($_POST['action'] ?? '') === 'reorder') {            
        foreach (itemsOf($pdo, $oid) as $it) {
            if (!$it['menu_id']) continue;
            $s = $pdo->prepare(
                "SELECT stock FROM menu WHERE id=?"
                ); 
            $s->execute([$it['menu_id']]); 
            $stock = (int)$s->fetchColumn();
            if ($stock > 0) 
                $_SESSION['cart'][$it['menu_id']] = min($it['qty'], $stock);
        }
        redirect('cart.php');
    }

    if ($o && !$o['rated'] && ($_POST['action'] ?? '') === 'review') { 
        $rating = max(1, min(5, (int)$_POST['rating'])); 
        $comment = trim($_POST['comment'] ?? '');
        foreach (itemsOf($pdo, $oid) as $it) 
            if ($it['menu_id'])
            $pdo->prepare("INSERT INTO reviews(user_id,menu_id,rating,comment) VALUES(?,?,?,?)")->execute([$uid, $it['menu_id'], $rating, $comment]);
        $pdo->prepare("UPDATE orders SET rated=1 WHERE id=?")->execute([$oid]);
        flash('Terima kasih atas ulasan Anda!');
    }
    redirect('orders.php?view=riwayat');
}
$view = ($_GET['view'] ?? '') === 'riwayat' ? 'riwayat' : 'aktif';
$cond = $view === 'riwayat' ? "status='Selesai'" : "status<>'Selesai'";
$st = $pdo->prepare(
    "SELECT * FROM orders WHERE user_id=? AND $cond ORDER BY id DESC"
    ); 
$st->execute([$uid]); $orders = $st->fetchAll();
$title = 'Pesanan'; pageStart($title);
?>

<h3><?= $view === 'riwayat' ? 'Riwayat Pemesanan' : 'Status Pesanan' ?></h3>
<?php foreach ($orders as $o):
    $extra = '';

    if ($view === 'riwayat') {
        $extra = "<div class='flex gap mt'><form method='post'><input type='hidden' name='order_id' value='{$o['id']}'><button name='action' value='reorder' class='btn btn-small btn-edit'>🔁 Pesan lagi</button></form>";
        $extra .= $o['rated'] ? "<span class='small muted'>Sudah diulas</span>"
            : "<form method='post' class='flex gap'><input type='hidden' name='order_id' value='{$o['id']}'><input type='hidden' name='action' value='review'>
               <select name='rating' class='input' style='width:auto'>" . implode('', array_map(fn($n) => "<option value='$n'>" . str_repeat('⭐', $n) . "</option>", [5, 4, 3, 2, 1])) . "</select>
               <input name='comment' class='input' placeholder='Komentar'><button class='btn btn-small btn-ok'>Kirim</button></form>";
        $extra .= '</div>';
    }
    echo orderCard($pdo, $o, $extra);
endforeach; 
if (!$orders): ?><div class="notice">Belum ada data.</div><?php endif;
pageEnd(); ?>
