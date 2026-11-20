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
$title = 'Katalog Menu'; require 'includes_header.php';
?>
<form method="get" class="row g-2 mb-4">
  <div class="col-md-5"><input name="q" class="form-control" placeholder="🔍 Cari menu..." value="<?= e($q) ?>" data-live></div>
  <div class="col-md-4"><select name="kategori" class="form-select" data-live><option value="">Semua kategori</option>
    <?php foreach (KATEGORI as $k): ?><option <?= $kat === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select name="max" class="form-select" data-live><option value="0">Semua harga</option>
    <?php foreach ([5000, 10000, 15000] as $h): ?><option value="<?= $h ?>" <?= $max === $h ? 'selected' : '' ?>>≤ <?= rp($h) ?></option><?php endforeach; ?></select></div>
</form>
<div class="row g-3">
<?php foreach ($menus as $m): ?>
  <div class="col-6 col-md-4 col-lg-3"><div class="card h-100 menu-card">
    <?php if ($m['photo']): ?><img src="<?= e($m['photo']) ?>" class="food-photo w-100" alt=""><?php else: ?><div class="food-photo">🍽️</div><?php endif; ?>
    <div class="card-body d-flex flex-column">
      <div class="d-flex justify-content-between"><strong><?= e($m['name']) ?></strong><span class="badge text-bg-light border"><?= e($m['category']) ?></span></div>
      <div class="text-primary fw-bold"><?= rp($m['price']) ?></div>
      <div class="small text-body-secondary mb-2"><?= $m['rating'] ? "⭐ {$m['rating']}" : 'Belum ada ulasan' ?> · <?= $m['stock'] > 0 ? "Stok {$m['stock']}" : 'Stok habis' ?></div>
      <form method="post" action="cart.php" class="mt-auto"><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= $m['id'] ?>">
        <button class="btn btn-primary btn-sm w-100" <?= $m['stock'] > 0 ? '' : 'disabled' ?>>+ Keranjang</button></form>
    </div></div></div>
<?php endforeach; if (!$menus): ?><p class="text-body-secondary">Menu tidak ditemukan.</p><?php endif; ?>
</div>
<?php require 'includes_footer.php'; ?>
