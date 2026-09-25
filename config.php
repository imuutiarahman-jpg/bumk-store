<?php
// config.php - koneksi DB + auto install + auth + helper
if (session_status() === PHP_SESSION_NONE) session_start();
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);
if ($isLocal) {
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'if0_42977813_bumk';
} else {
    $DB_HOST = 'sql103.infinityfree.com';
    $DB_USER = 'if0_42968556';
    $DB_PASS = 'lDkPGwvGfoZc5';
    $DB_NAME = 'if0_42968556_bumk';
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($conn->connect_error) {
    die('Koneksi DB gagal: ' . $conn->connect_error);
}
try {
    // CREATE DATABASE hanya di lokal. Di hosting shared dilarang -> skip agar tidak 500.
    if ($isLocal) $conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    if (!$conn->select_db($DB_NAME)) {
        die('Koneksi DB gagal: database `' . $DB_NAME . '` tidak bisa dipilih. Error: ' . $conn->error);
    }
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    die('Koneksi DB gagal: ' . $e->getMessage());
}

$conn->query("CREATE TABLE IF NOT EXISTS samples (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL DEFAULT '',
  nama VARCHAR(100) NOT NULL,
  foto VARCHAR(255) NOT NULL,
  warna_hex VARCHAR(7) NOT NULL DEFAULT '#888888',
  warna_nama VARCHAR(50) NOT NULL DEFAULT '-',
  harga INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kode (kode)
) ENGINE=InnoDB");

$conn->query("CREATE TABLE IF NOT EXISTS stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sample_id INT NOT NULL,
  ukuran ENUM('S','M','L','XL','XXL') NOT NULL,
  jumlah INT NOT NULL DEFAULT 0,
  harga INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_sample_ukuran (sample_id, ukuran),
  FOREIGN KEY (sample_id) REFERENCES samples(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$cek = $conn->query("SHOW COLUMNS FROM stock LIKE 'harga'");
if ($cek && $cek->num_rows === 0) {
    $conn->query("ALTER TABLE stock ADD COLUMN harga INT NOT NULL DEFAULT 0");
}
$cek2 = $conn->query("SHOW COLUMNS FROM samples LIKE 'kode'");
if ($cek2 && $cek2->num_rows === 0) {
    $conn->query("ALTER TABLE samples ADD COLUMN kode VARCHAR(20) NOT NULL DEFAULT '' AFTER id");
}
// kategori + daftar ukuran custom per sampel (topi/tas/kain/taplak/bendera/peci)
$ck = $conn->query("SHOW COLUMNS FROM samples LIKE 'kategori'");
if ($ck && $ck->num_rows === 0) {
    $conn->query("ALTER TABLE samples ADD COLUMN kategori VARCHAR(20) NOT NULL DEFAULT 'kemeja' AFTER kode");
}
$ck2 = $conn->query("SHOW COLUMNS FROM samples LIKE 'ukuran_list'");
if ($ck2 && $ck2->num_rows === 0) {
    $conn->query("ALTER TABLE samples ADD COLUMN ukuran_list TEXT NOT NULL AFTER warna_nama");
}
// ubah ukuran dari ENUM kaku jadi teks bebas (pertahankan data S-XXL lama)
// cek tipe dulu agar tidak error di DB yg tabelnya belum ada / sudah varchar
$ct = null;
$tmpCt = $conn->query("SHOW COLUMNS FROM stock LIKE 'ukuran'");
if ($tmpCt) $ct = $tmpCt->fetch_assoc();
if ($ct && isset($ct['Type']) && stripos($ct['Type'], 'varchar') === false) {
    $conn->query("ALTER TABLE stock MODIFY ukuran VARCHAR(20) NOT NULL");
}
// backfill sampel lama (kemeja) yg belum ada kategori/ukuran
$conn->query("UPDATE samples SET kategori='kemeja' WHERE kategori=''");
$conn->query("UPDATE samples SET ukuran_list='S,M,L,XL,XXL' WHERE ukuran_list=''");
$conn->query("UPDATE stock st JOIN samples s ON s.id=st.sample_id SET st.harga=s.harga WHERE st.harga=0 AND s.harga>0");
// isi kode kosong utk data lama
$q0 = $conn->query("SELECT id FROM samples WHERE kode=''");
while ($q0 && $r0 = $q0->fetch_assoc()) {
    $kode = 'BRG-' . str_pad($r0['id'], 4, '0', STR_PAD_LEFT);
    $conn->query("UPDATE samples SET kode='$kode' WHERE id=" . (int)$r0['id']);
}

$conn->query("CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  total INT NOT NULL DEFAULT 0,
  kasir VARCHAR(50) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB");
$c3 = $conn->query("SHOW COLUMNS FROM transactions LIKE 'kasir'");
if ($c3 && $c3->num_rows === 0) $conn->query("ALTER TABLE transactions ADD COLUMN kasir VARCHAR(50) NOT NULL DEFAULT 'admin'");

$conn->query("CREATE TABLE IF NOT EXISTS transaction_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  sample_id INT NOT NULL,
  ukuran VARCHAR(20) NOT NULL,
  qty INT NOT NULL,
  harga INT NOT NULL,
  subtotal INT NOT NULL,
  FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB");
$ct2 = null;
$tmpCt2 = $conn->query("SHOW COLUMNS FROM transaction_items LIKE 'ukuran'");
if ($tmpCt2) $ct2 = $tmpCt2->fetch_assoc();
if ($ct2 && isset($ct2['Type']) && stripos($ct2['Type'], 'varchar(20)') === false) {
    $conn->query("ALTER TABLE transaction_items MODIFY ukuran VARCHAR(20) NOT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS stock_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  sample_id INT NOT NULL,
  ukuran VARCHAR(20) NOT NULL,
  perubahan INT NOT NULL,
  sisa INT NOT NULL DEFAULT 0,
  keterangan VARCHAR(100) NOT NULL DEFAULT '',
  oleh VARCHAR(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB");
$ct3 = null;
$tmpCt3 = $conn->query("SHOW COLUMNS FROM stock_history LIKE 'ukuran'");
if ($tmpCt3) $ct3 = $tmpCt3->fetch_assoc();
if ($ct3 && isset($ct3['Type']) && stripos($ct3['Type'], 'varchar(20)') === false) {
    $conn->query("ALTER TABLE stock_history MODIFY ukuran VARCHAR(20) NOT NULL");
}

$conn->query("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL DEFAULT 'Admin'
) ENGINE=InnoDB");
// admin default: admin / admin123
$cekU = $conn->query("SELECT id FROM users WHERE username='admin'");
if ($cekU && $cekU->num_rows === 0) {
    $pw = password_hash('admin123', PASSWORD_DEFAULT);
    $st = $conn->prepare("INSERT INTO users (username,password,nama) VALUES ('admin',?,'Admin')");
    $st->bind_param('s', $pw);
    $st->execute();
}

// tabel kategori (tambah item baru dari web, tanpa upload file lagi)
$conn->query("CREATE TABLE IF NOT EXISTS kategoris (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(20) NOT NULL UNIQUE,
  ukuran_default TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
// isi awal + sarung (INSERT IGNORE: data lama tidak ditimpa)
$__seed = [
    ['kemeja','S,M,L,XL,XXL'],
    ['topi','ALL SIZE,M,L,XL'],
    ['tas','ALL SIZE'],
    ['kain','PCS'],
    ['taplak','120X120,120X180,150X200,200X200'],
    ['bendera','30X45,60X90,90X135,120X180'],
    ['peci','1,2,3,4,5,6,7,8,9'],
    ['sarung','ALL SIZE,JUMBO'],
];
foreach ($__seed as [$__n, $__u]) {
    $__st = $conn->prepare("INSERT IGNORE INTO kategoris (nama,ukuran_default) VALUES (?,?)");
    $__st->bind_param('ss', $__n, $__u);
    $__st->execute();
}
// index performa — aman di-rerun (cek dulu agar tidak duplicate key error)
try {
    $ix = $conn->query("SHOW INDEX FROM transactions WHERE Key_name='ix_trans_tanggal'");
    if ($ix && $ix->num_rows === 0) $conn->query("ALTER TABLE transactions ADD INDEX ix_trans_tanggal (tanggal)");
    $ix2 = $conn->query("SHOW INDEX FROM stock WHERE Key_name='ix_stock_sample'");
    if ($ix2 && $ix2->num_rows === 0) $conn->query("ALTER TABLE stock ADD INDEX ix_stock_sample (sample_id)");
    $ix3 = $conn->query("SHOW INDEX FROM transaction_items WHERE Key_name='ix_ti_trans'");
    if ($ix3 && $ix3->num_rows === 0) $conn->query("ALTER TABLE transaction_items ADD INDEX ix_ti_trans (transaction_id)");
} catch (Throwable $e) {}

if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0777, true);
}

function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function current_user() { return $_SESSION['user'] ?? null; }
function require_login() {
    if (!current_user()) { header('Location: login.php'); exit; }
}
function log_stok($sample_id, $ukuran, $perubahan, $sisa, $ket) {
    global $conn;
    $oleh = current_user()['username'] ?? 'sistem';
    $st = $conn->prepare("INSERT INTO stock_history (sample_id,ukuran,perubahan,sisa,keterangan,oleh) VALUES (?,?,?,?,?,?)");
    $st->bind_param('isiiis', $sample_id, $ukuran, $perubahan, $sisa, $ket, $oleh);
    $st->execute();
}
function nav($active = '') {
    $u = current_user();
    $nm = $u ? h($u['username']) : '';
    $m = [
        'index' => ['index.php','Dashboard'], 'sampel' => ['sampel.php','Sampel'],
        'scan' => ['scan.php','Scan + Stok'], 'kasir' => ['kasir.php','Kasir'],
        'riwayat' => ['riwayat.php','Riwayat'], 'laporan' => ['laporan.php','Laporan'],
    ];
    echo '<div class="nav"><b>BUMK Store</b>';
    foreach ($m as $k => [$url,$label]) {
        $cls = $k === $active ? ' class="active"' : '';
        echo "<a href=\"$url\"$cls>$label</a>";
    }
    echo "<span style=\"margin-left:auto;color:#c5cae9;font-size:13px\">$nm | <a href=\"logout.php\" style=\"background:#c62828\">Keluar</a></span></div>";
}
function hex_to_rgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}
// daftar kategori + ukuran bawaan dibaca dari tabel kategoris (bisa ditambah dari web)
function kategori_list() {
    global $conn;
    static $c = null;
    if ($c !== null) return $c;
    $c = [];
    try {
        $q = $conn->query("SELECT nama FROM kategoris ORDER BY nama");
        if ($q) while ($r = $q->fetch_assoc()) $c[] = $r['nama'];
    } catch (Throwable $e) { $c = []; }
    if (!$c) $c = ['kemeja','topi','tas','kain','taplak','bendera','peci','sarung'];
    return $c;
}
function ukuran_default($kat) {
    global $conn;
    try {
    $st = $conn->prepare("SELECT ukuran_default FROM kategoris WHERE nama=?");
    if ($st) {
        $st->bind_param('s', $kat);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        if ($r && trim($r['ukuran_default'] ?? '') !== '') {
            $out = [];
            foreach (explode(',', $r['ukuran_default']) as $u) {
                $u = normal_ukuran($u);
                if ($u !== '' && valid_ukuran($u) && !in_array($u, $out, true)) $out[] = $u;
            }
            if ($out) return $out;
        }
    }
    } catch (Throwable $e) {}
    return ['ALL SIZE'];
}
function normal_ukuran($u) {
    $u = strtoupper(trim(preg_replace('/\s+/', ' ', $u)));
    return substr($u, 0, 20);
}
function valid_ukuran($u) {
    return (bool)preg_match('/^[A-Z0-9 \.X\-]{1,20}$/', $u);
}
