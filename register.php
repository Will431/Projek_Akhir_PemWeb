
<?php 
    session_start();  
    include 'koneksi.php';

    $pesan = '';
    $tipe = 'danger';

    if(isset($_POST['register'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = in_array($_POST['role'], ['mahasiswa','penjual']) ?
        $_POST['role'] : 'mahasiswa';

        if($name == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { //unique
        $pesan = "Nama dan Email wajib diisi dengan benar";
        } elseif (strlen($password) < 8) { //unique
        $pesan = "Kata Sandi minimal 8 karakter";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($check, 's', $email);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);

            if(mysqli_num_rows($result) > 0){
                $pesan = "Email telah terdaftar";
            } else {
                $query = mysqli_prepare(
                    $conn,
                    "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)"
                );
                mysqli_stmt_bind_param($query, "ssss", $name, $email, $hashedPassword, $role);

                if (mysqli_stmt_execute($query)) {
                    $pesan = "Register berhasil, silakan login";
                    $tipe = 'success';
                } else {
                    $pesan = "Register Gagal";    
                }
            }
        }
    }

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register - KantinKu</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
        body { background: linear-gradient(135deg, #fff4e6 0%, #ffe8cc 100%); min-height: 100vh; }
        .brand { font-weight: 800; color: #d9480f; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #7a6b5d; font-size: 14px; margin-bottom: 20px; }
        .card { border: 0; border-radius: 16px; box-shadow: 0 8px 24px rgba(217, 72, 15, .15); }
        .form-control, .form-select { border-radius: 10px; padding: 10px 12px; }
        .form-control:focus, .form-select:focus { border-color: #d9480f; box-shadow: 0 0 0 .2rem rgba(217, 72, 15, .2); }
        .btn-daftar { background: #d9480f; border: 0; border-radius: 10px; padding: 10px; font-weight: 600; color: #fff; }
        .btn-daftar:hover { background: #bf3f0c; color: #fff; }
        a { color: #d9480f; }
    </style>
    </head>
    <body>
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-5">
          <div class="card">
            <div class="card-body">
              <h3 class="brand">🍽️ KantinKu</h3>
              <p class="subtitle">Buat akun untuk mulai memesan atau berjualan</p>
              <?php if ($pesan): ?>
                <div class="alert alert-<?= $tipe ?>"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
                <form method="post">
                <div class="mb-3">          
                <label class="form-label">Nama</label>          
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="mb-3">
              <label class="form-label">Email</label>          
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
            <label class="form-label">Kata sandi</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
          </div>
          <div class="mb-3">
          <label class="form-label">Daftar sebagai</label>
          <select name="role" class="form-select">
          <option value="mahasiswa">Mahasiswa</option>
                      <option value="penjual">Penjual</option>
                    </select>
                  </div>
                  <button type="submit" name="register" class="btn btn-daftar w-100">Daftar</button>
                </form>
                <hr>
                <a href="login.php">Sudah punya akun? Masuk</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </body>
    </html>

