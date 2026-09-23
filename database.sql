-- database.sql : import manual bila perlu (config.php sudah auto-create)
CREATE DATABASE IF NOT EXISTS bumk_store CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bumk_store;

CREATE TABLE IF NOT EXISTS samples (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  foto VARCHAR(255) NOT NULL,
  warna_hex VARCHAR(7) NOT NULL DEFAULT '#888888',
  warna_nama VARCHAR(50) NOT NULL DEFAULT '-',
  harga INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sample_id INT NOT NULL,
  ukuran ENUM('S','M','L','XL','XXL') NOT NULL,
  jumlah INT NOT NULL DEFAULT 0,
  harga INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_sample_ukuran (sample_id, ukuran),
  FOREIGN KEY (sample_id) REFERENCES samples(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  total INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaction_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  sample_id INT NOT NULL,
  ukuran VARCHAR(5) NOT NULL,
  qty INT NOT NULL,
  harga INT NOT NULL,
  subtotal INT NOT NULL,
  FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
