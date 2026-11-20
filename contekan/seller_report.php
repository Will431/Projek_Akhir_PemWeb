<?php require 'config.php'; need('penjual');
$mode = ($_GET['mode'] ?? 'harian') === 'bulanan' ? 'bulanan' : 'harian';
$fmt = $mode === 'harian' ? '%Y-%m-%d' : '%Y-%m';                     // whitelist, aman dari injection
$rows = $pdo->query("SELECT DATE_FORMAT(created_at,'$fmt') periode, COUNT(*) n, SUM(total) omzet FROM orders WHERE status='Selesai' GROUP BY periode ORDER BY periode DESC LIMIT 14")->fetchAll();
$chart = array_reverse(array_slice($rows, 0, 7)); $max = $chart ? max(array_column($chart, 'omzet')) : 1;
$sum = array_sum(array_column($rows, 'omzet'));
$title = 'Laporan Penjualan'; require 'includes_header.php';
?>
<h4>Laporan Penjualan</h4>
<ul class="nav nav-pills mb-3"><li class="nav-item"><a class="nav-link <?= $mode === 'harian' ? 'active' : '' ?>" href="?mode=harian">Harian</a></li>
<li class="nav-item"><a class="nav-link <?= $mode === 'bulanan' ? 'active' : '' ?>" href="?mode=bulanan">Bulanan</a></li></ul>
<div class="card mb-3"><div class="card-body"><div class="bar-chart">
<?php foreach ($chart as $r): ?><div><span style="height:<?= $r['omzet'] / $max * 100 ?>px"></span><?= e(substr($r['periode'], $mode === 'harian' ? 5 : 2)) ?></div><?php endforeach; ?></div></div></div>
<table class="table table-striped"><thead><tr><th><?= $mode === 'harian' ? 'Tanggal' : 'Bulan' ?></th><th>Pesanan</th><th>Omzet</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['periode']) ?></td><td><?= $r['n'] ?></td><td><?= rp($r['omzet']) ?></td></tr><?php endforeach; ?>
<tr class="fw-bold"><td colspan="2">Total ditampilkan</td><td><?= rp($sum) ?></td></tr></tbody></table>
<?php require 'includes_footer.php'; ?>
