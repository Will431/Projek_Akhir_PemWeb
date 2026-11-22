<?php
    session_start();
    include 'koneksi.php';

    if (!empty($_SESSION['user'])) redirect('index.php');
    $error = '';

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        
        $query = mysqli_prepare(
            $conn,
            "SELECT * FROM USERS WHERE email = ?"
        );

        mysqli_stmt_bind_param($query, ,'s', $email);
        mysqli_stmt_execute($query);

        $result = mysqli_stmt_get_result($query);
        $user = mysqli_fetch_assoc($query);

        $st->execute([$email]); $user = $st->fetch();

        if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        //set cookie tolong ya pot aku gtw soale
        }
        
    }


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