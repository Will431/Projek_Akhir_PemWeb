-- =====================================================
-- Database KantinKu untuk MariaDB (10.4+ / XAMPP)
-- Cara pakai: phpMyAdmin > Import, atau di terminal:
--   mysql -u root -p < kantin_mariadb.sql
-- =====================================================

DROP DATABASE IF EXISTS kantin;
CREATE DATABASE kantin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE kantin;

-- 1. Akun mahasiswa dan penjual
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80)  NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('mahasiswa','penjual') NOT NULL DEFAULT 'mahasiswa',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Katalog menu dan stok
CREATE TABLE menu (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  name     VARCHAR(100) NOT NULL,
  price    INT NOT NULL,
  category VARCHAR(30)  NOT NULL,
  stock    INT NOT NULL DEFAULT 0,
  photo    VARCHAR(255) NULL,
  INDEX idx_kategori (category),
  INDEX idx_nama (name)
) ENGINE=InnoDB;

-- 3. Pesanan
CREATE TABLE orders (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  total       INT NOT NULL,
  pickup_time VARCHAR(5)  NOT NULL,
  pay_method  VARCHAR(20) NOT NULL,
  pay_status  ENUM('Belum','Lunas') NOT NULL DEFAULT 'Belum',
  status      ENUM('Menunggu','Diproses','Siap Diambil','Selesai') NOT NULL DEFAULT 'Menunggu',
  rated       TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_tanggal (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Rincian item pesanan
CREATE TABLE order_items (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_id  INT NULL,
  name     VARCHAR(100) NOT NULL,
  price    INT NOT NULL,
  qty      INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id)  REFERENCES menu(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Ulasan dan rating
CREATE TABLE reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  menu_id    INT NOT NULL,
  rating     TINYINT NOT NULL,
  comment    VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menu(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Notifikasi
CREATE TABLE notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  message    VARCHAR(255) NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_baca (user_id, is_read),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data awal menu
INSERT INTO menu (name, price, category, stock) VALUES
('Nasi Goreng',   15000, 'Makanan', 20),
('Ayam Geprek',   17000, 'Makanan', 12),
('Mie Ayam',      13000, 'Makanan', 15),
('Es Teh',         4000, 'Minuman', 40),
('Kopi Susu',     10000, 'Minuman', 25),
('Pisang Goreng',  6000, 'Snack',   30);
