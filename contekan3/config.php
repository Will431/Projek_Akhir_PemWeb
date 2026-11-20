<?php
// Koneksi MySQL (PDO), session, dan fungsi bantu
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=kantin;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
// Koneksi mysqli (dipakai register.php)
$conn = mysqli_connect('localhost', 'root', '', 'kantin');
if (!$conn) die('Koneksi gagal: ' . mysqli_connect_error());
mysqli_set_charset($conn, 'utf8mb4');

// Endpoint AJAX: tandai notifikasi dibaca (dipanggil app.js)
if (!empty($_POST['notif_read']) && !empty($_SESSION['user'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user']['id']]);
    exit;
}

const STATUS = ['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'];   // array urutan status
const KATEGORI = ['Makanan', 'Minuman', 'Snack'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp' . number_format((int)$n, 0, ',', '.'); }
function flash($msg, $type = 'success') { $_SESSION['flash'] = [$msg, $type]; }
function redirect($url) { header("Location: $url"); exit; }

// Hak akses berdasarkan session
function need($role = null) {
    if (empty($_SESSION['user'])) redirect('login.php');
    if ($role && $_SESSION['user']['role'] !== $role) { http_response_code(403); exit('Akses ditolak.'); }
}
function notify($pdo, $userId, $msg) {
    $pdo->prepare("INSERT INTO notifications(user_id,message) VALUES(?,?)")->execute([$userId, $msg]);
}
function notifySellers($pdo, $msg) {
    $pdo->prepare("INSERT INTO notifications(user_id,message) SELECT id,? FROM users WHERE role='penjual'")->execute([$msg]);
}
function itemsOf($pdo, $orderId) {
    $s = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $s->execute([$orderId]); return $s->fetchAll();
}
function statusBar($status) {
    $step = array_search($status, STATUS) + 1;
    $pct = $step / count(STATUS) * 100;
    $color = $status === 'Selesai' ? 'ok' : ($status === 'Siap Diambil' ? 'info' : 'warn');
    return "<div class='track'><div class='fill fill-$color' style='width:$pct%'></div></div>";
}
function orderCard($pdo, $o, $extra = '') {
    $items = array_map(fn($i) => $i['qty'] . '× ' . e($i['name']), itemsOf($pdo, $o['id']));
    return "<div class='card'><div class='flex between'><strong>#{$o['id']}</strong><span class='badge'>" . e($o['status']) . "</span></div>
      <div class='muted small'>" . implode(', ', $items) . "</div>" . statusBar($o['status']) . "
      <div class='flex between small'><span>Ambil " . e($o['pickup_time']) . " · " . e($o['pay_method']) . " (" . e($o['pay_status']) . ")</span>
      <strong class='price'>" . rp($o['total']) . "</strong></div>$extra</div>";
}

// Arahkan pengguna ke halaman sesuai peran
function home() { redirect($_SESSION['user']['role'] === 'penjual' ? 'seller_dashboard.php' : 'menu.php'); }

// Kerangka halaman: bagian atas (header + navigasi)
function pageStart($title = 'KantinKu', $head = '') {
    global $pdo;
    $u = $_SESSION['user'] ?? null;
    $theme = ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';   // cookie tema
    $nots = []; $unread = 0; $links = [];
    if ($u) {
        $st = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6"); $st->execute([$u['id']]);
        $nots = $st->fetchAll();
        $c = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $c->execute([$u['id']]); $unread = $c->fetchColumn();
        $links = $u['role'] === 'penjual'
            ? ['seller_dashboard.php' => 'Dashboard', 'seller_menu.php' => 'Menu & Stok', 'seller_orders.php' => 'Pesanan', 'seller_dashboard.php#laporan' => 'Laporan']
            : ['menu.php' => 'Katalog', 'cart.php' => 'Keranjang (' . count($_SESSION['cart'] ?? []) . ')', 'orders.php' => 'Status Pesanan', 'orders.php?view=riwayat' => 'Riwayat'];
    }
    ?><!DOCTYPE html>
<html lang="id" data-theme="<?= $theme ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - KantinKu</title>
<style>
/* ===== KantinKu - CSS murni (tanpa framework) ===== */
:root{--bg:#faf6f0;--card:#fff;--tx:#2b2118;--mu:#7a6b5d;--bd:#e8dfd3;--ac:#d9480f;--ac-tx:#fff;--soft:#f1e9dd;--ok:#2f9e44;--info:#1c7ed6;--warn:#f08c00;--bad:#e03131}
html[data-theme=dark]{--bg:#1b1612;--card:#26201a;--tx:#f3ebe0;--mu:#a8998a;--bd:#3a3128;--ac:#ff8c4a;--ac-tx:#1b1612;--soft:#322a22}
*{box-sizing:border-box}
body{margin:0;font:15px/1.5 "Segoe UI",system-ui,sans-serif;background:var(--bg);color:var(--tx)}
a{color:var(--ac)} h2,h3,h4{margin:0 0 12px}
.wrap{max-width:1000px;margin:0 auto;padding:0 16px}.page{padding-top:20px;padding-bottom:40px}
/* utilitas */
.flex{display:flex}.between{justify-content:space-between}.items-center{align-items:center}.wrapf{flex-wrap:wrap}
.gap{gap:8px}.grow{flex:1}.mt{margin-top:10px}.mb{margin-bottom:14px}.right{text-align:right}
.muted{color:var(--mu)}.small{font-size:13px}.bold{font-weight:700}.price{color:var(--ac);font-weight:700}.danger-text{color:var(--bad);font-weight:700}
/* header */
.topbar{background:var(--card);border-bottom:1px solid var(--bd);position:sticky;top:0;z-index:10;padding:10px 0}
.brand{font-weight:800;font-size:18px;text-decoration:none;margin-right:10px}
.nav-links a{padding:6px 10px;border-radius:8px;text-decoration:none;color:var(--tx)}.nav-links a:hover{background:var(--soft)}
.notif{position:relative}.notif-badge{background:var(--bad);color:#fff;border-radius:99px;font-size:11px;padding:0 6px;margin-left:4px}
.notif-panel{display:none;position:absolute;right:0;top:42px;width:290px;background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:8px;box-shadow:0 8px 24px rgba(0,0,0,.15);z-index:20}
.notif-panel.open{display:block}.notif-item{font-size:13px;padding:6px 4px;border-bottom:1px solid var(--bd);color:var(--mu)}.notif-item.unread{color:var(--tx);font-weight:600}
/* komponen */
.card{background:var(--card);border:1px solid var(--bd);border-radius:14px;padding:16px;margin-bottom:12px}
.btn{display:inline-block;border:1px solid var(--bd);background:var(--card);color:var(--tx);padding:8px 14px;border-radius:10px;cursor:pointer;font:inherit;text-decoration:none;line-height:1.3}
.btn:hover{background:var(--soft)}.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-main{background:var(--ac);border-color:var(--ac);color:var(--ac-tx);font-weight:600}.btn-main:hover{background:var(--ac);opacity:.9}
.btn-ok{background:var(--ok);border-color:var(--ok);color:#fff}.btn-edit{color:var(--ac);border-color:var(--ac)}.btn-del{color:var(--bad);border-color:var(--bad)}
.btn-small{padding:4px 10px;font-size:13px}.btn-block{display:block;width:100%;text-align:center}
.input{width:100%;padding:9px 12px;border:1px solid var(--bd);border-radius:10px;background:var(--card);color:var(--tx);font:inherit}
.input:focus{outline:2px solid var(--ac);border-color:transparent}.field{margin-bottom:14px}.lbl{display:block;font-size:14px;margin-bottom:4px}
.notice{display:flex;justify-content:space-between;padding:10px 14px;border-radius:10px;margin-bottom:14px;border:1px solid var(--bd)}
.notice.success{background:rgba(47,158,68,.12);border-color:var(--ok)}.notice.danger{background:rgba(224,49,49,.12);border-color:var(--bad)}.notice.warning{background:rgba(240,140,0,.14);border-color:var(--warn)}
.notice-close{background:none;border:0;color:inherit;font-size:18px;cursor:pointer}
.badge{display:inline-block;font-size:12px;padding:2px 9px;border-radius:99px;background:var(--soft);border:1px solid var(--bd)}
.track{background:var(--soft);border-radius:99px;height:8px;overflow:hidden;margin:8px 0}.fill{height:100%;background:var(--ac)}
.fill-ok{background:var(--ok)}.fill-info{background:var(--info)}.fill-warn{background:var(--warn)}
.table-wrap{overflow-x:auto}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:9px 8px;border-bottom:1px solid var(--bd);text-align:left}
.auth-box{max-width:420px;margin:30px auto}
@media(max-width:760px){.notif-panel{right:-40px}}
</style><?= $head ?></head><body>
<header class="topbar"><div class="wrap flex items-center gap wrapf">
  <a class="brand" href="login.php">🍽️ KantinKu</a>
  <nav class="nav-links flex wrapf"><?php foreach ($links as $href => $label): ?><a href="<?= $href ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
  <span class="grow"></span>
  <button class="btn btn-small" id="themeBtn" type="button">🌓</button>
  <?php if ($u): ?>
  <div class="notif"><button class="btn btn-small" id="notifBtn" type="button">🔔<?php if ($unread): ?><span id="notifBadge" class="notif-badge"><?= $unread ?></span><?php endif; ?></button>
    <div class="notif-panel" id="notifPanel">
      <?php foreach ($nots as $n): ?><div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>"><?= e($n['message']) ?></div><?php endforeach; ?>
      <?php if (!$nots): ?><div class="notif-item">Belum ada notifikasi</div><?php endif; ?></div></div>
  <span class="small muted"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</span>
  <a class="btn btn-small btn-del" href="login.php?logout=1">Keluar</a><?php endif; ?>
</div></header>
<main class="wrap page">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="notice <?= e($t) ?>"><span><?= e($m) ?></span><button type="button" class="notice-close">×</button></div><?php endif; ?>
<?php
}

// Kerangka halaman: bagian bawah
function pageEnd($script = '') {
    ?></main>
<script>
const $ = (s, r = document) => r.querySelector(s);

// 1. Tema gelap/terang disimpan di cookie
const setTheme = t => { document.documentElement.setAttribute('data-theme', t); document.cookie = 'theme=' + t + ';path=/;max-age=2592000'; };
$('#themeBtn')?.addEventListener('click', () => setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'));
// 3. Panel notifikasi + tandai dibaca (fetch/AJAX)
const nb = $('#notifBtn'), np = $('#notifPanel');
nb?.addEventListener('click', e => {
  e.stopPropagation(); np.classList.toggle('open');
  if (np.classList.contains('open')) fetch(location.pathname, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'notif_read=1' }).then(() => $('#notifBadge')?.remove());
});
document.addEventListener('click', e => { if (np && !np.contains(e.target)) np.classList.remove('open'); });
// 4. Tutup pesan notice
document.querySelectorAll('.notice-close').forEach(b => b.addEventListener('click', () => b.parentElement.remove()));
// 6. Validasi form sebelum dikirim
document.querySelectorAll('form[data-validate]').forEach(f => f.addEventListener('submit', ev => {
  if (!f.checkValidity()) { ev.preventDefault(); f.reportValidity(); }
}));
<?= $script ?>
</script></body></html>
<?php
}
