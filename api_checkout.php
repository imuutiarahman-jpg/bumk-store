<?php require 'config.php'; // POST JSON {items:[{id,ukuran,qty}]} — harga diambil dari DB per ukuran (ukuran bebas)
header('Content-Type: application/json');
$in = json_decode(file_get_contents('php://input'), true);
$items = $in['items'] ?? [];
if (!$items) { echo json_encode(['ok'=>false,'msg'=>'kosong']); exit; }
if (count($items) > 50) { echo json_encode(['ok'=>false,'msg'=>'maks 50 baris per transaksi']); exit; }

$conn->begin_transaction();
try {
    $total = 0; $rows = [];
    $qSample = $conn->prepare("SELECT id,nama,harga FROM samples WHERE id=?");
    $qStock = $conn->prepare("SELECT jumlah,harga FROM stock WHERE sample_id=? AND ukuran=? FOR UPDATE");
    foreach ($items as $it) {
        $sid = (int)($it['id'] ?? 0);
        $uk = normal_ukuran($it['ukuran'] ?? '');
        $qty = (int)($it['qty'] ?? 1);
        if (!$sid || !valid_ukuran($uk) || $qty < 1 || $qty > 100) {
            throw new Exception('item tidak valid');
        }
        $qSample->bind_param('i', $sid);
        $qSample->execute();
        $s = $qSample->get_result()->fetch_assoc();
        if (!$s) throw new Exception('barang tidak ada');
        $qStock->bind_param('is', $sid, $uk);
        $qStock->execute();
        $cek = $qStock->get_result()->fetch_assoc();
        if (!$cek || (int)$cek['jumlah'] < $qty) throw new Exception("stok kurang: {$s['nama']} ($uk)");
        $hrg = (int)$cek['harga'] > 0 ? (int)$cek['harga'] : (int)$s['harga'];
        $sub = $hrg * $qty;
        $total += $sub;
        $rows[] = [$sid, $uk, $qty, $hrg, $sub];
    }
    if ($total <= 0) throw new Exception('total tidak valid');
    $kasir = current_user()['username'] ?? 'admin';
    $insT = $conn->prepare("INSERT INTO transactions (total,kasir) VALUES (?,?)");
    $insT->bind_param('is', $total, $kasir);
    $insT->execute();
    $tid = $conn->insert_id;
    $insI = $conn->prepare("INSERT INTO transaction_items (transaction_id,sample_id,ukuran,qty,harga,subtotal) VALUES (?,?,?,?,?,?)");
    $updS = $conn->prepare("UPDATE stock SET jumlah=jumlah-? WHERE sample_id=? AND ukuran=?");
    foreach ($rows as [$sid,$uk,$qty,$hrg,$sub]) {
        $insI->bind_param('iisiii', $tid, $sid, $uk, $qty, $hrg, $sub);
        $insI->execute();
        $updS->bind_param('iis', $qty, $sid, $uk);
        $updS->execute();
        if (function_exists('log_stok')) {
            $qs = $conn->prepare("SELECT jumlah FROM stock WHERE sample_id=? AND ukuran=?");
            $qs->bind_param('is', $sid, $uk);
            $qs->execute();
            $ss = $qs->get_result()->fetch_assoc();
            log_stok($sid, $uk, -$qty, (int)($ss['jumlah'] ?? 0), "jual #$tid");
        }
    }
    $conn->commit();
    echo json_encode(['ok'=>true,'id'=>$tid,'total'=>$total]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
