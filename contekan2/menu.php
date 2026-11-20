<?php require 'config.php'; need('mahasiswa');
// Pencarian + filter: kondisi dikumpulkan dalam array
$where = ['1=1']; $params = [];
$q = trim($_GET['q'] ?? ''); $kat = $_GET['kategori'] ?? ''; $max = (int)($_GET['max'] ?? 0);
if ($q !== '') { $where[] = 'm.name LIKE ?'; $params[] = "%$q%"; }
if (in_array($kat, KATEGORI)) { $where[] = 'm.category=?'; $params[] = $kat; }
if ($max > 0) { $where[] = 'm.price<=?'; $params[] = $max; }
$st = $pdo->prepare("SELECT m.*, (SELECT ROUND(AVG(rating),1) FROM reviews r WHERE r.menu_id=m.id) AS rating
                     FROM menu m WHERE " . implode(' AND ', $where) . " ORDER BY m.category, m.name");
$st->execute($params); $menus = $st->fetchAll();
$title = 'Katalog Menu'; pageStart($title);
?>
<form method="get" class="filters">
  <input name="q" class="input" style="flex:2" placeholder="🔍 Cari menu..." value="<?= e($q) ?>" data-live>
  <select name="kategori" class="input" data-live><option value="">Semua kategori</option>
    <?php foreach (KATEGORI as $k): ?><option <?= $kat === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?></select>
  <select name="max" class="input" data-live><option value="0">Semua harga</option>
    <?php foreach ([5000, 10000, 15000] as $h): ?><option value="<?= $h ?>" <?= $max === $h ? 'selected' : '' ?>>≤ <?= rp($h) ?></option><?php endforeach; ?></select>
</form>
<div class="product-grid">
<?php foreach ($menus as $m): ?>
  <div class="product">
    <?php if ($m['photo']): ?><img src="<?= e($m['photo']) ?>" class="photo" alt=""><?php else: ?><div class="photo">🍽️</div><?php endif; ?>
    <div class="body">
      <div class="flex between"><strong><?= e($m['name']) ?></strong><span class="badge"><?= e($m['category']) ?></span></div>
      <div class="price"><?= rp($m['price']) ?></div>
      <div class="small muted"><?= $m['rating'] ? "⭐ {$m['rating']}" : 'Belum ada ulasan' ?> · <?= $m['stock'] > 0 ? "Stok {$m['stock']}" : 'Stok habis' ?></div>
      <form method="post" action="cart.php"><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= $m['id'] ?>">
        <button class="btn btn-main btn-small btn-block" <?= $m['stock'] > 0 ? '' : 'disabled' ?>>+ Keranjang</button></form>
    </div></div>
<?php endforeach; ?></div>
<?php if (!$menus): ?><p class="muted">Menu tidak ditemukan.</p><?php endif; ?>
<?php pageEnd(); ?>
