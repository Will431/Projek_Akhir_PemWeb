<?php 
include 'config.php';
if (isset($_GET['logout'])) { 
  $_SESSION = []; session_destroy(); 
  header('Location: login.php'); exit; 
  }
  
  if (!empty($_SESSION['user'])) home();
  $error = '';
  
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? ''); 
    $pass = $_POST['password'] ?? '';
    $st = $pdo->prepare(
      "SELECT * FROM users WHERE email=?"
      );
       
      $st->execute([$email]); 
      $user = $st->fetch();

      if ($user && password_verify($pass, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']];

        if (!empty($_POST['remember'])) setcookie('remember_email', $email, time() + 86400 * 30, '/', '', false, true);
        else setcookie('remember_email', '', time() - 3600, '/');
        home();
    }
    $error = 'Email atau kata sandi salah.';
}
$title = 'Masuk';
pageStart($title); $saved = $_COOKIE['remember_email'] ?? '';

?> 

<div class="auth-box"><div class="card">
  <h3>Masuk</h3>
  <?php if ($error): ?><div class="notice danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" data-validate>
    <div class="field"><label class="lbl">Email</label><input type="email" name="email" class="input" value="<?= e($saved) ?>" required></div>
    <div class="field"><label class="lbl">Kata sandi</label><input type="password" name="password" class="input" required></div>
    <div class="field"><label><input type="checkbox" name="remember" <?= $saved ? 'checked' : '' ?>> Ingat email saya</label></div>
    <button class="btn btn-main btn-block">Masuk</button>
  </form><hr><a href="register.php">Belum punya akun? Daftar</a>
</div></div>
<?php pageEnd(); ?>