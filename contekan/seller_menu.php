<?php require 'config.php'; need('penjual');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($act === 'delete') { $pdo->prepare("DELETE FROM menu WHERE id=?")->execute([$id]); flash('Menu dihapus.'); }
    elseif ($act === 'stock') { $pdo->prepare("UPDATE menu SET stock=GREATEST(0,stock+?) WHERE id=?")->execute([(int)$_POST['d'], $id]); }
    elseif ($act === 'save') {
        $name = trim($_POST['name']); $price = (int)$_POST['price']; $stock = (int)$_POST['stock']; $cat = in_array($_POST['category'], KATEGORI) ? $_POST['category'] : 'Makanan';
        if ($name === '' || $price <= 0) { flash('Nama dan harga wajib diisi.', 'danger'); redirect('seller_menu.php'); }
        $foto = null;
        if (!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) { @mkdir('uploads'); $foto = 'uploads/' . uniqid() . ".$ext"; move_uploaded_file($_FILES['photo']['tmp_name'], $foto); }
        }
        if ($id) $pdo->prepare("UPDATE menu SET name=?,price=?,category=?,stock=?,photo=COALESCE(?,photo) WHERE id=?")->execute([$name, $price, $cat, $stock, $foto, $id]);
        else $pdo->prepare("INSERT INTO menu(name,price,category,stock,photo) VALUES(?,?,?,?,?)")->execute([$name, $price, $cat, $stock, $foto]);
        flash('Menu disimpan.');
    }
    redirect('seller_menu.php');
}
$edit = ['id' => 0, 'name' => '', 'price' => '', 'category' => 'Makanan', 'stock' => ''];
if (isset($_GET['edit'])) { $s = $pdo->prepare("SELECT * FROM menu WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch() ?: $edit; }
$menus = $pdo->query("SELECT * FROM menu ORDER BY category,name")->fetchAll();
$title = 'Kelola Menu'; require 'includes_header.php';
?>
<div class="card mb-4"><div class="card-body"><h5><?= $edit['id'] ? 'Ubah' : 'Tambah' ?> Menu</h5>
<form method="post" enctype="multipart/form-data" class="row g-2" data-validate novalidate>
  <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?>">
  <div class="col-md-3"><input name="name" class="form-control" placeholder="Nama" value="<?= e($edit['name']) ?>" required></div>
  <div class="col-md-2"><input name="price" type="number" min="1" class="form-control" placeholder="Harga" value="<?= e($edit['price']) ?>" required></div>
  <div class="col-md-2"><input name="stock" type="number" min="0" class="form-control" placeholder="Stok" value="<?= e($edit['stock']) ?>" required></div>
  <div class="col-md-2"><select name="category" class="form-select"><?php foreach (KATEGORI as $k): ?><option <?= $edit['category'] === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><input type="file" name="photo" accept="image/*" class="form-control"></div>
  <div class="col-12"><button class="btn btn-primary">Simpan</button> <?php if ($edit['id']): ?><a href="seller_menu.php" class="btn btn-outline-secondary">Batal</a><?php endif; ?></div>
</form></div></div>
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Menu</th><th>Harga</th><th>Stok</th><th></th></tr></thead><tbody>
<?php foreach ($menus as $m): ?><tr><td><?= e($m['name']) ?> <span class="badge text-bg-light border"><?= e($m['category']) ?></span></td><td><?= rp($m['price']) ?></td>
  <td><form method="post" class="d-flex align-items-center gap-1"><input type="hidden" name="action" value="stock"><input type="hidden" name="id" value="<?= $m['id'] ?>">
    <button name="d" value="-1" class="btn btn-sm btn-outline-secondary">−</button><span class="px-2 <?= $m['stock'] <= 5 ? 'text-danger fw-bold' : '' ?>"><?= $m['stock'] ?></span><button name="d" value="1" class="btn btn-sm btn-outline-secondary">+</button></form></td>
  <td class="text-end"><a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
    <button type="button" class="btn btn-sm btn-outline-danger" data-del="<?= $m['id'] ?>" data-name="<?= e($m['name']) ?>">Hapus</button></td></tr><?php endforeach; ?></tbody></table></div>
<div class="modal fade" id="delModal" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-body">Hapus menu <strong id="delName"></strong>?
  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delId"></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div></div>
<?php require 'includes_footer.php'; ?>
