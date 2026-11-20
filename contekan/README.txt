KantinKu - Sistem Manajemen Kantin
Materi: HTML, CSS, Bootstrap, JavaScript, PHP, MySQL/MariaDB, Cookie, Session, Autentikasi

CARA MENJALANKAN
1. Jalankan Apache dan MySQL di XAMPP.
2. Salin folder "kantin" ke C:\xampp\htdocs\
3. Buka phpMyAdmin -> Import -> pilih database.sql -> Go
4. Jika MySQL memakai password, ubah di config.php dan koneksi.php
5. Buka http://localhost/kantin/register.php
   Buat 1 akun Penjual dan 1 akun Mahasiswa, lalu login di login.php

CATATAN
- register.php memakai mysqli (koneksi.php).
- Halaman lain memakai PDO (config.php).
- Folder uploads/ dibuat otomatis saat penjual mengunggah foto menu.

DAFTAR FILE
config.php, koneksi.php          koneksi database, session, fungsi bantu
includes_header.php/footer.php   kerangka halaman (navbar, notifikasi, Bootstrap)
index.php, login.php, register.php, logout.php   autentikasi
menu.php, cart.php, orders.php   halaman mahasiswa
seller_dashboard.php, seller_menu.php, seller_orders.php, seller_report.php   halaman penjual
notif_read.php                   AJAX tandai notifikasi dibaca
assets/style.css, assets/app.js  CSS dan JavaScript
database.sql                     skema dan data awal (MariaDB/MySQL)
