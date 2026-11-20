KantinKu - Sistem Manajemen Kantin
Materi: HTML, CSS (murni, tanpa framework), JavaScript, PHP, MySQL/MariaDB, Cookie, Session, Autentikasi

CARA MENJALANKAN
1. Jalankan Apache dan MySQL di XAMPP.
2. Salin folder "kantin" ke C:\xampp\htdocs\
3. Buka phpMyAdmin -> Import -> pilih database.sql -> Go
4. Jika MySQL memakai password, ubah di config.php (dua tempat: $pdo dan $conn)
5. Buka http://localhost/kantin/register.php
   Buat 1 akun Penjual dan 1 akun Mahasiswa, lalu login di login.php

DAFTAR FILE (13)
config.php          koneksi database (PDO + mysqli), session, fungsi bantu,
                    kerangka halaman (pageStart/pageEnd), AJAX notifikasi
login.php           login, logout (?logout=1), pengarah halaman sesuai peran
register.php        registrasi akun (mysqli)
menu.php            katalog, pencarian, filter          (mahasiswa)
cart.php            keranjang, checkout, pembayaran      (mahasiswa)
orders.php          status pesanan, riwayat, ulasan      (mahasiswa)
seller_dashboard.php  dashboard + laporan harian/bulanan (penjual)
seller_menu.php     CRUD menu dan stok                   (penjual)
seller_orders.php   proses pesanan masuk                 (penjual)
assets/style.css    seluruh CSS
assets/app.js       seluruh JavaScript
database.sql        skema dan data awal
