<?php
include 'config.php';
need('mahasiswa');

// fallback kalau config.php belum punya helper ini
if (!function_exists('e'))  { function e($v)  { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('rp')) { function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); } }

// Pencarian + filter: kondisi dikumpulkan dalam array
$where = ['1=1']; $params = [];
$q   = trim($_GET['q'] ?? '');
$kat = $_GET['kategori'] ?? '';
$max = (int)($_GET['max'] ?? 0);

if ($q !== '') { $where[] = 'm.name LIKE ?'; $params[] = "%$q%"; }
if (in_array($kat, KATEGORI, true)) { $where[] = 'm.category=?'; $params[] = $kat; }
if ($max > 0) { $where[] = 'm.price<=?'; $params[] = $max; }

$st = $pdo->prepare("SELECT m.*, (SELECT ROUND(AVG(rating),1) FROM reviews r WHERE r.menu_id=m.id) AS rating
                     FROM menu m WHERE " . implode(' AND ', $where) . " ORDER BY m.category, m.name");
$st->execute($params);
$menus = $st->fetchAll();

$title = 'Katalog Menu';

$css = <<<'CSS'
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.filters .input{flex:1;min-width:140px}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.product{background:var(--card);border:1px solid var(--bd);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s}.product:hover{transform:translateY(-3px)}
.product .body{padding:12px;display:flex;flex-direction:column;flex:1;gap:4px}.product form{margin-top:auto}
.photo{height:130px;width:100%;object-fit:cover;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:3rem}
CSS;

$js = <<<'JS'
// Pencarian/filter otomatis (debounce)
let timer;
document.querySelectorAll('[data-live]').forEach(el => el.addEventListener('input', () => {
  clearTimeout(timer);
  if (el.name === 'q') sessionStorage.setItem('refocusQ', '1');
  timer = setTimeout(() => el.form.submit(), el.tagName === 'SELECT' ? 0 : 450);
}));
const q = document.querySelector('input[name=q]');
if (q && sessionStorage.getItem('refocusQ') === '1') {
  sessionStorage.removeItem('refocusQ');
  q.focus(); q.setSelectionRange(q.value.length, q.value.length);
}
JS;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style><?= $css ?></style>
</head>
<body class="bg-light">
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><?= e($title) ?></h3>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <!-- Filter -->
    <form method="get" class="row g-2 mb-4">
        <div class="col-md-6">
            <input name="q" class="form-control" placeholder="🔍 Cari menu..." value="<?= e($q) ?>" data-live>
        </div>
        <div class="col-md-3">
            <select name="kategori" class="form-select" data-live>
                <option value="">Semua kategori</option>
                <?php foreach (KATEGORI as $k): ?>
                    <option value="<?= e($k) ?>" <?= $kat === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="max" class="form-select" data-live>
                <option value="0">Semua harga</option>
                <?php foreach ([5000, 10000, 15000] as $h): ?>
                    <option value="<?= $h ?>" <?= $max === $h ? 'selected' : '' ?>>≤ <?= rp($h) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- Katalog -->
    <div class="row g-3">
        <?php if (!$menus): ?>
            <p class="text-muted">Menu tidak ditemukan.</p>
        <?php endif; ?>

        <?php foreach ($menus as $m): ?>
            <div class="col-md-4">
                <div class="card product h-100 shadow-sm">
                    <?php if ($m['photo']): ?>
                        <img src="<?= e($m['photo']) ?>" class="card-img-top photo" alt="<?= e($m['name']) ?>">
                    <?php else: ?>
                        <div class="photo">🍽️</div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h5 class="card-title mb-0"><?= e($m['name']) ?></h5>
                            <span class="badge text-bg-secondary"><?= e($m['category']) ?></span>
                        </div>
                        <p class="card-text fw-bold mb-1">Harga: <?= rp($m['price']) ?></p>
                        <p class="card-text small text-muted mb-3">
                            <?= $m['rating'] ? '⭐ ' . e($m['rating']) : 'Belum ada ulasan' ?> ·
                            <?= $m['stock'] > 0 ? 'Stok ' . (int)$m['stock'] : 'Stok habis' ?>
                        </p>

                        <form method="post" action="cart.php" class="mt-auto">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                            <button class="btn btn-primary btn-sm w-100" <?= $m['stock'] > 0 ? '' : 'disabled' ?>>+ Keranjang</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script><?= $js ?></script>
</body>
</html>