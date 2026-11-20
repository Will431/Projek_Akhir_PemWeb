<?php require 'config.php'; need('penjual');
$today = $pdo->query("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN status='Selesai' THEN total END),0) omzet FROM orders WHERE DATE(created_at)=CURDATE()")->fetch();
$active = $pdo->query("SELECT COUNT(*) FROM orders WHERE status<>'Selesai'")->fetchColumn();
$low = $pdo->query("SELECT COUNT(*) FROM menu WHERE stock<=5")->fetchColumn();
$top = $pdo->query("SELECT oi.name, SUM(oi.qty) qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status='Selesai' GROUP BY oi.name ORDER BY qty DESC LIMIT 5")->fetchAll();
$max = $top ? max(array_column($top, 'qty')) : 1;
$kpi = ['Pesanan hari ini' => $today['n'], 'Omzet hari ini' => rp($today['omzet']), 'Perlu diproses' => $active, 'Stok menipis' => $low];
$title = 'Dashboard'; require 'includes_header.php';
?>
<h4 class="mb-3">Dashboard Penjual</h4>
<div class="row g-3 mb-4"><?php foreach ($kpi as $label => $val): ?>
  <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="small text-body-secondary"><?= $label ?></div><div class="fs-4 fw-bold"><?= $val ?></div></div></div></div><?php endforeach; ?></div>
<div class="card"><div class="card-body"><h5>Menu Terlaris</h5>
<?php foreach ($top as $t): ?><div class="d-flex align-items-center gap-2 my-2"><span style="width:130px"><?= e($t['name']) ?></span>
  <div class="progress flex-grow-1"><div class="progress-bar" style="width:<?= $t['qty'] / $max * 100 ?>%"></div></div><strong><?= $t['qty'] ?></strong></div><?php endforeach;
if (!$top) echo '<p class="text-body-secondary">Belum ada data penjualan.</p>'; ?></div></div>
<?php require 'includes_footer.php'; ?>
