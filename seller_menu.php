<?php
include 'config.php';
need('penjual');

// ---------- Proses form (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id  = (int)($_POST['id'] ?? 0);

    // Hapus menu
    if ($act === 'delete') {
        $pdo->prepare("DELETE FROM menu WHERE id=?")->execute([$id]);
        flash('Menu dihapus.');

    // Tambah / kurangi stok
    } elseif ($act === 'stock') {
        $pdo->prepare("UPDATE menu SET stock=GREATEST(0,stock+?) WHERE id=?")
            ->execute([(int)$_POST['d'], $id]);

    // Simpan menu (baru / ubah)
    } elseif ($act === 'save') {
        $name  = trim($_POST['name']);
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $cat   = in_array($_POST['category'], KATEGORI) ? $_POST['category'] : 'Makanan';

        if ($name === '' || $price <= 0) {
            flash('Nama dan harga wajib diisi.', 'danger');
            redirect('seller_menu.php');
        }

        // Upload foto (opsional)
        $foto = null;
        if (!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                @mkdir('uploads');
                $foto = 'uploads/' . uniqid() . ".$ext";
                move_uploaded_file($_FILES['photo']['tmp_name'], $foto);
            }
        }

        if ($id) {
            $pdo->prepare("UPDATE menu SET name=?,price=?,category=?,stock=?,photo=COALESCE(?,photo) WHERE id=?")
                ->execute([$name, $price, $cat, $stock, $foto, $id]);
        } else {
            $pdo->prepare("INSERT INTO menu(name,price,category,stock,photo) VALUES(?,?,?,?,?)")
                ->execute([$name, $price, $cat, $stock, $foto]);
        }
        flash('Menu disimpan.');
    }

    redirect('seller_menu.php');
}

// ---------- Data untuk form & tabel ----------
$edit = ['id' => 0, 'name' => '', 'price' => '', 'category' => 'Makanan', 'stock' => ''];

if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM menu WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch() ?: $edit;
}

$menus = $pdo->query("SELECT * FROM menu ORDER BY category,name")->fetchAll();

// ---------- Tampilan ----------
$title = 'Kelola Menu';

$css = <<<'CSS'
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:50}
.modal-bg.open{display:flex}
.modal-box{background:var(--card);padding:20px;border-radius:14px;width:min(380px,92vw)}
CSS;

$js = <<<'JS'
// 5. Modal konfirmasi hapus menu
document.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => {
  $('#delId').value = b.dataset.del;
  $('#delName').textContent = b.dataset.name;
  $('#delModal').classList.add('open');
}));
document.querySelectorAll('[data-close]').forEach(b =>
  b.addEventListener('click', () => $('#delModal').classList.remove('open'))
);
JS;

$head = "<style>$css</style>";
pageStart($title, $head);
?>

<!-- Form tambah / ubah menu -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="card">
    <h4><?= $edit['id'] ? 'Ubah' : 'Tambah' ?> Menu</h4>

    <form method="post" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">

        <div class="form-grid">
            <input name="name" class="input" placeholder="Nama" value="<?= e($edit['name']) ?>" required>
            <input name="price" type="number" min="1" class="input" placeholder="Harga" value="<?= e($edit['price']) ?>" required>
            <input name="stock" type="number" min="0" class="input" placeholder="Stok" value="<?= e($edit['stock']) ?>" required>

            <select name="category" class="input">
                <?php foreach (KATEGORI as $k): ?>
                    <option <?= $edit['category'] === $k ? 'selected' : '' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>

            <input type="file" name="photo" accept="image/*" class="input">
        </div>

        <div class="mt">
            <button class="btn btn-main">Simpan</button>
            <?php if ($edit['id']): ?>
                <a href="seller_menu.php" class="btn">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabel menu -->
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Menu</th>
                <th>Harga</th>
                <th>Stok</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menus as $m): ?>
                <tr>
                    <td>
                        <?= e($m['name']) ?>
                        <span class="badge"><?= e($m['category']) ?></span>
                    </td>
                    <td><?= rp($m['price']) ?></td>
                    <td>
                        <form method="post" class="flex items-center gap">
                            <input type="hidden" name="action" value="stock">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button name="d" value="-1" class="btn btn-small">−</button>
                            <span class="<?= $m['stock'] <= 5 ? 'danger-text' : '' ?>"><?= $m['stock'] ?></span>
                            <button name="d" value="1" class="btn btn-small">+</button>
                        </form>
                    </td>
                    <td class="right">
                        <a href="?edit=<?= $m['id'] ?>" class="btn btn-small btn-edit">Ubah</a>
                        <button type="button" class="btn btn-small btn-del"
                                data-del="<?= $m['id'] ?>"
                                data-name="<?= e($m['name']) ?>">Hapus</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal konfirmasi hapus -->
<div class="modal-bg" id="delModal">
    <form method="post" class="modal-box">
        <p>Hapus menu <strong id="delName"></strong>?</p>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delId">
        <div class="flex gap" style="justify-content:flex-end">
            <button type="button" class="btn" data-close>Batal</button>
            <button class="btn btn-main btn-del">Hapus</button>
        </div>
    </form>
</div>

<?php pageEnd($js); ?>
</body>
</html>