<?php
require 'config.php';

$pesan = '';
$tipe  = 'danger';   // jenis pesan: danger / success

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    // hanya dua peran yang diizinkan
    $role = in_array($_POST['role'], ['mahasiswa', 'penjual']) ? $_POST['role'] : 'mahasiswa';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Nama dan email wajib diisi dengan benar";
    } elseif (strlen($password) < 6) {
        $pesan = "Kata sandi minimal 6 karakter";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 1. Cek apakah email sudah terdaftar
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $pesan = "Email sudah terdaftar";
        } else {
            // 2. Simpan akun baru (INSERT hanya dijalankan di dalam else)
            $query = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)"
            );
            mysqli_stmt_bind_param($query, "ssss", $name, $email, $hashedPassword, $role);

            if (mysqli_stmt_execute($query)) {
                $pesan = "Register berhasil, silakan login";
                $tipe  = 'success';
            } else {
                $pesan = "Register gagal";
            }
        }
    }
}
$head = '<style>
  /* gaya khusus halaman register */
  html[data-theme=light] body { background: linear-gradient(135deg, #fff4e6 0%, #ffe8cc 100%); min-height: 100vh; }
  .brand-title { font-weight: 800; color: var(--ac); text-align: center; margin: 0 0 4px; font-size: 26px; }
  .subtitle { text-align: center; color: var(--mu); font-size: 14px; margin-bottom: 20px; }
  .auth-box .card { border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(217, 72, 15, .15); }
</style>';
pageStart('Daftar', $head);
?>
<div class="auth-box">
  <div class="card">
    <h1 class="brand-title">🍽️ KantinKu</h1>
    <p class="subtitle">Buat akun untuk mulai memesan atau berjualan</p>

    <?php if ($pesan): ?>
      <div class="notice <?= $tipe ?>"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <form method="post" data-validate>
      <div class="field"><label class="lbl">Nama</label><input type="text" name="name" class="input" required></div>
      <div class="field"><label class="lbl">Email</label><input type="email" name="email" class="input" required></div>
      <div class="field"><label class="lbl">Kata sandi</label><input type="password" name="password" class="input" minlength="6" required></div>
      <div class="field"><label class="lbl">Daftar sebagai</label>
        <select name="role" class="input"><option value="mahasiswa">Mahasiswa</option><option value="penjual">Penjual</option></select></div>
      <button type="submit" name="register" class="btn btn-main btn-block">Daftar</button>
    </form>
    <hr>
    <a href="login.php">Sudah punya akun? Masuk</a>
  </div>
</div>
<?php pageEnd(); ?>
