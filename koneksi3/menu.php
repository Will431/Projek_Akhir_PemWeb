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
$title = 'Katalog Menu';
$css = <<<'CSS'
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.filters .input{flex:1;min-width:140px}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.product{background:var(--card);border:1px solid var(--bd);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s}.product:hover{transform:translateY(-3px)}
.product .body{padding:12px;display:flex;flex-direction:column;flex:1;gap:4px}.product form{margin-top:auto}
.photo{height:130px;width:100%;object-fit:cover;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:3rem}
CSS;
$js = <<<'JS'
// 2. Pencarian/filter otomatis (debounce)
let timer;
document.querySelectorAll('[data-live]').forEach(el => el.addEventListener('input', () => {
  clearTimeout(timer); timer = setTimeout(() => el.form.submit(), el.tagName === 'SELECT' ? 0 : 450);
}));
const q = $('input[name=q]');
if (q && q.value) { q.focus(); q.setSelectionRange(q.value.length, q.value.length); }
JS;
$head = "<style>$css</style>";
pageStart($title, $head);
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
<?php pageEnd($js); ?>
