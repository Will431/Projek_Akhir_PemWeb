<?php require 'config.php'; need('penjual');
// --- Ringkasan hari ini ---
$today = $pdo->query("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN status='Selesai' THEN total END),0) omzet FROM orders WHERE DATE(created_at)=CURDATE()")->fetch();
$active = $pdo->query("SELECT COUNT(*) FROM orders WHERE status<>'Selesai'")->fetchColumn();
$low = $pdo->query("SELECT COUNT(*) FROM menu WHERE stock<=5")->fetchColumn();
$top = $pdo->query("SELECT oi.name, SUM(oi.qty) qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status='Selesai' GROUP BY oi.name ORDER BY qty DESC LIMIT 5")->fetchAll();
$max = $top ? max(array_column($top, 'qty')) : 1;
$kpi = ['Pesanan hari ini' => $today['n'], 'Omzet hari ini' => rp($today['omzet']), 'Perlu diproses' => $active, 'Stok menipis' => $low];

// --- Laporan penjualan harian / bulanan ---
$mode = ($_GET['mode'] ?? 'harian') === 'bulanan' ? 'bulanan' : 'harian';
$fmt = $mode === 'harian' ? '%Y-%m-%d' : '%Y-%m';                     // whitelist, aman dari injection
$rows = $pdo->query("SELECT DATE_FORMAT(created_at,'$fmt') periode, COUNT(*) n, SUM(total) omzet FROM orders WHERE status='Selesai' GROUP BY periode ORDER BY periode DESC LIMIT 14")->fetchAll();
$chart = array_reverse(array_slice($rows, 0, 7)); $maxC = $chart ? max(array_column($chart, 'omzet')) : 1;
$sum = array_sum(array_column($rows, 'omzet'));

$title = 'Dashboard';
$css = <<<'CSS'
.tabs{display:flex;gap:8px;margin-bottom:14px}.tab{padding:6px 16px;border-radius:99px;border:1px solid var(--bd);text-decoration:none;color:var(--tx)}.tab.active{background:var(--ac);color:var(--ac-tx);border-color:var(--ac)}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:18px}.kpi .num{font-size:24px;font-weight:700}
.bar-chart{display:flex;align-items:flex-end;gap:6px;height:140px}.bar-chart div{flex:1;text-align:center;font-size:.7rem}.bar-chart span{display:block;background:var(--ac);border-radius:4px 4px 0 0}
CSS;
$head = "<style>$css</style>";
pageStart($title, $head);
?>
<h3>Dashboard Penjual</h3>
<div class="kpi-grid"><?php foreach ($kpi as $label => $val): ?>
  <div class="card kpi"><div class="small muted"><?= $label ?></div><div class="num"><?= $val ?></div></div><?php endforeach; ?></div>
<div class="card"><h4>Menu Terlaris</h4>
<?php foreach ($top as $t): ?><div class="flex items-center gap"><span style="width:130px"><?= e($t['name']) ?></span>
  <div class="track grow"><div class="fill" style="width:<?= $t['qty'] / $max * 100 ?>%"></div></div><strong><?= $t['qty'] ?></strong></div><?php endforeach;
if (!$top) echo '<p class="muted">Belum ada data penjualan.</p>'; ?></div>

<h3 id="laporan" style="margin-top:28px">Laporan Penjualan</h3>
<div class="tabs"><a class="tab <?= $mode === 'harian' ? 'active' : '' ?>" href="?mode=harian#laporan">Harian</a>
<a class="tab <?= $mode === 'bulanan' ? 'active' : '' ?>" href="?mode=bulanan#laporan">Bulanan</a></div>
<div class="card"><div class="bar-chart">
<?php foreach ($chart as $r): ?><div><span style="height:<?= $r['omzet'] / $maxC * 100 ?>px"></span><?= e(substr($r['periode'], $mode === 'harian' ? 5 : 2)) ?></div><?php endforeach; ?></div></div>
<div class="table-wrap"><table class="table"><thead><tr><th><?= $mode === 'harian' ? 'Tanggal' : 'Bulan' ?></th><th>Pesanan</th><th>Omzet</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['periode']) ?></td><td><?= $r['n'] ?></td><td><?= rp($r['omzet']) ?></td></tr><?php endforeach; ?>
<tr class="bold"><td colspan="2">Total ditampilkan</td><td><?= rp($sum) ?></td></tr></tbody></table></div>
<?php pageEnd(); ?>
