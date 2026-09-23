<?php require 'config.php'; // POST: sample_id, ukuran, qty, [harga] -> tambah stok (ukuran bebas per item)
header('Content-Type: application/json');
$sid = (int)($_POST['sample_id'] ?? 0);
$uk = normal_ukuran($_POST['ukuran'] ?? '');
$qty = max(1,(int)($_POST['qty'] ?? 1));
$harga = max(0,(int)($_POST['harga'] ?? 0));
if (!$sid || !valid_ukuran($uk) || $qty > 1000) { echo json_encode(['ok'=>false,'msg'=>'data tidak valid']); exit; }
$cekS = $conn->prepare("SELECT id,harga,ukuran_list FROM samples WHERE id=?");
$cekS->bind_param('i', $sid);
$cekS->execute();
$sm = $cekS->get_result()->fetch_assoc();
if (!$sm) { echo json_encode(['ok'=>false,'msg'=>'sampel tidak ditemukan (id='.$sid.')']); exit; }
if ($harga > 0) {
    $st = $conn->prepare("INSERT INTO stock (sample_id,ukuran,jumlah,harga) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE jumlah=jumlah+VALUES(jumlah), harga=VALUES(harga)");
    if (!$st) { echo json_encode(['ok'=>false,'msg'=>'db error: '.$conn->error]); exit; }
    $st->bind_param('isii',$sid,$uk,$qty,$harga);
} else {
    $def = (int)$sm['harga'];
    $st = $conn->prepare("INSERT INTO stock (sample_id,ukuran,jumlah,harga) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE jumlah=jumlah+VALUES(jumlah)");
    if (!$st) { echo json_encode(['ok'=>false,'msg'=>'db error: '.$conn->error]); exit; }
    $st->bind_param('isii',$sid,$uk,$qty,$def);
}
if (!$st->execute()) { echo json_encode(['ok'=>false,'msg'=>'gagal simpan: '.$st->error]); exit; }
// kalau ukuran baru belum ada di ukuran_list sampel, tambahkan
$list = array_filter(array_map('normal_ukuran', explode(',', ($sm['ukuran_list'] ?? ''))));
if (!in_array($uk, $list, true)) {
    $list[] = $uk;
    $ulk = implode(',', array_slice($list, 0, 15));
    $up = $conn->prepare("UPDATE samples SET ukuran_list=? WHERE id=?");
    $up->bind_param('si', $ulk, $sid);
    $up->execute();
}
$q2 = $conn->prepare("SELECT jumlah,harga FROM stock WHERE sample_id=? AND ukuran=?");
$q2->bind_param('is', $sid, $uk);
$q2->execute();
$sisa = $q2->get_result()->fetch_assoc();
if (function_exists('log_stok')) log_stok($sid, $uk, $qty, (int)($sisa['jumlah'] ?? 0), 'tambah via scan/manual');
echo json_encode(['ok'=>true,'sisa'=>(int)($sisa['jumlah'] ?? 0),'harga'=>(int)($sisa['harga'] ?? 0)]);
