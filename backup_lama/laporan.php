<?php require 'config.php';
$today = $conn->query("SELECT COALESCE(SUM(total),0) t, COUNT(*) c FROM transactions WHERE DATE(tanggal)=CURDATE()")->fetch_assoc();
$trx = $conn->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 50");
$ids = [];
$rows = [];
while ($r = $trx->fetch_assoc()) { $ids[] = (int)$r['id']; $rows[] = $r; }
$detail = [];
if ($ids) {
    $in = implode(',', $ids);
    $q = $conn->query("SELECT i.transaction_id, COALESCE(s.nama,'[terhapus]') nama, i.ukuran, i.qty, i.harga, i.subtotal
      FROM transaction_items i LEFT JOIN samples s ON s.id=i.sample_id
      WHERE i.transaction_id IN ($in)");
    while ($x = $q->fetch_assoc()) $detail[$x['transaction_id']][] = $x;
}
// pindahan dari dashboard: stok + terjual per item & stok menipis
$perItem = $conn->query("SELECT s.id, s.kode, s.nama, s.kategori, s.warna_nama,
  COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) AS stok,
  COALESCE((SELECT SUM(qty) FROM transaction_items WHERE sample_id=s.id),0) AS terjual
  FROM samples s ORDER BY s.kategori, s.nama");
$items = [];
while ($r = $perItem->fetch_assoc()) $items[] = $r;
$allStok = [];
$q2 = $conn->query("SELECT sample_id,ukuran,jumlah FROM stock ORDER BY sample_id,ukuran");
while ($x = $q2->fetch_assoc()) $allStok[$x['sample_id']][] = $x['ukuran'].':'.$x['jumlah'];
$menipis = $conn->query("SELECT s.nama, s.kategori, st.ukuran, st.jumlah, st.harga, s.warna_nama FROM stock st JOIN samples s ON s.id=st.sample_id WHERE st.jumlah BETWEEN 1 AND 3 ORDER BY st.jumlah ASC LIMIT 20");
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan</title><link rel="stylesheet" href="assets/style.css?v=2"></head><body>
<div class="nav"><b>BUMK Store</b><a href="index.php">Dashboard</a><a href="sampel.php">Sampel</a><a href="scan.php">Scan + Stok</a><a href="kasir.php">Kasir</a><a href="laporan.php" class="active">Laporan</a></div>
<div class="wrap">
<div class="card"><h3>Penjualan hari ini: <?= rupiah($today['t']) ?> (<?= (int)$today['c'] ?> transaksi)</h3></div>
<div class="card"><h3>Stok + Terjual per Item</h3>
<table><tr><th>Kategori</th><th>Barang</th><th>Warna</th><th>Stok</th><th>Rincian ukuran</th><th>Terjual</th></tr>
<?php foreach ($items as $r): ?>
<tr><td><?= h(strtoupper($r['kategori'])) ?></td>
<td><?= h($r['nama']) ?><br><span style="font-size:11px;color:#888"><?= h($r['kode']) ?></span></td>
<td><?= h($r['warna_nama']) ?></td>
<td><b><?= (int)$r['stok'] ?></b></td>
<td style="font-size:12px"><?= h(implode(' | ', $allStok[$r['id']] ?? ['-'])) ?></td>
<td><b><?= (int)$r['terjual'] ?></b> pcs</td></tr>
<?php endforeach; ?>
</table></div>
<div class="card"><h3>Stok menipis (sisa 1–3)</h3>
<table><tr><th>Barang</th><th>Kategori</th><th>Warna</th><th>Ukuran</th><th>Sisa</th><th>Harga</th></tr>
<?php while($r=$menipis->fetch_assoc()): ?>
<tr><td><?= h($r['nama']) ?></td><td><?= h(strtoupper($r['kategori'])) ?></td><td><?= h($r['warna_nama']) ?></td><td><?= h($r['ukuran']) ?></td><td><?= (int)$r['jumlah'] ?></td><td><?= rupiah($r['harga']) ?></td></tr>
<?php endwhile; ?>
</table></div>
<div class="card"><h3>50 Transaksi Terakhir</h3>
<table><tr><th>ID</th><th>Tanggal</th><th>Total</th><th>Detail</th></tr>
<?php foreach ($rows as $r):
  $det = [];
  foreach (($detail[$r['id']] ?? []) as $x) $det[] = $x['nama']." ({$x['ukuran']} x{$x['qty']} @".number_format($x['harga'],0,',','.').")";
?>
<tr><td>#<?= (int)$r['id'] ?></td><td><?= h($r['tanggal']) ?></td><td><?= rupiah($r['total']) ?></td><td><?= h(implode(', ',$det)) ?></td></tr>
<?php endforeach; ?></table></div>
</div></body></html>
