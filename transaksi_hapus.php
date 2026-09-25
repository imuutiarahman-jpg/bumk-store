<?php
// transaksi_hapus.php — hapus transaksi dobel/kelebihan + kembalikan stok (opsi A)
require 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: laporan.php#lap'); exit; }
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: laporan.php?hapus=gagal#lap'); exit; }
try {
    $cek = $conn->prepare("SELECT id FROM transactions WHERE id=?");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) { header('Location: laporan.php?hapus=tidak-ada#lap'); exit; }
    $it = $conn->prepare("SELECT sample_id,ukuran,qty FROM transaction_items WHERE transaction_id=?");
    $it->bind_param('i', $id);
    $it->execute();
    $rs = $it->get_result();
    $items = [];
    while ($r = $rs->fetch_assoc()) $items[] = $r;
    $conn->begin_transaction();
    foreach ($items as $x) {
        $sid = (int)$x['sample_id'];
        $uk = $x['ukuran'];
        $qty = max(0, (int)$x['qty']);
        if ($qty <= 0) continue;
        $hg = 0;
        $qh = $conn->prepare("SELECT harga FROM samples WHERE id=?");
        if ($qh) {
            $qh->bind_param('i', $sid);
            $qh->execute();
            $rh = $qh->get_result()->fetch_assoc();
            $hg = (int)($rh['harga'] ?? 0);
        }
        // tambah kembali qty; harga TIDAK diubah (baris baru pakai harga dasar sbg fallback)
        $up = $conn->prepare("INSERT INTO stock (sample_id,ukuran,jumlah,harga) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE jumlah=jumlah+VALUES(jumlah)");
        $up->bind_param('isii', $sid, $uk, $qty, $hg);
        $up->execute();
        $qs = $conn->prepare("SELECT jumlah FROM stock WHERE sample_id=? AND ukuran=?");
        $qs->bind_param('is', $sid, $uk);
        $qs->execute();
        $sisa = (int)($qs->get_result()->fetch_assoc()['jumlah'] ?? 0);
        log_stok($sid, $uk, $qty, $sisa, 'Batal transaksi #' . $id);
    }
    $del = $conn->prepare("DELETE FROM transactions WHERE id=?");
    $del->bind_param('i', $id);
    $del->execute();
    $conn->commit();
    header('Location: laporan.php?hapus=ok&id=' . $id . '#lap');
    exit;
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $e2) {}
    header('Location: laporan.php?hapus=gagal#lap');
    exit;
}
