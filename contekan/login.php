  <?php require 'config.php';
if (!empty($_SESSION['user'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
    $st = $pdo->prepare("SELECT * FROM users WHERE email=?"); $st->execute([$email]); $user = $st->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']];
        // Cookie "ingat saya": simpan email 30 hari
        if (!empty($_POST['remember'])) setcookie('remember_email', $email, time() + 86400 * 30, '/', '', false, true);
        else setcookie('remember_email', '', time() - 3600, '/');
        redirect('index.php');
    }
    $error = 'Email atau kata sandi salah.';
}
$title = 'Masuk'; $links = []; $u = null; $theme = $_COOKIE['theme'] ?? 'light'; $nots = []; $unread = 0;
require 'includes_header.php'; $saved = $_COOKIE['remember_email'] ?? '';
?>
<div class="row justify-content-center"><div class="col-md-5"><div class="card"><div class="card-body">
  <h4 class="mb-3">Masuk</h4>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" data-validate novalidate>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($saved) ?>" required></div>
    <div class="mb-3"><label class="form-label">Kata sandi</label><input type="password" name="password" class="form-control" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="rm" <?= $saved ? 'checked' : '' ?>><label class="form-check-label" for="rm">Ingat email saya</label></div>
    <button class="btn btn-primary w-100">Masuk</button>
  </form><hr><a href="register.php">Belum punya akun? Daftar</a>
</div></div></div></div>
<?php require 'includes_footer.php'; ?>
