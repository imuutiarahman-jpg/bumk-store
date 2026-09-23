<?php require 'config.php';
$total_sampel = $conn->query("SELECT COUNT(*) c FROM samples")->fetch_assoc()['c'];
$total_stok = $conn->query("SELECT COALESCE(SUM(jumlah),0) c FROM stock")->fetch_assoc()['c'];
$omset = $conn->query("SELECT COALESCE(SUM(total),0) c FROM transactions")->fetch_assoc()['c'];
$terjual = $conn->query("SELECT COALESCE(SUM(qty),0) c FROM transaction_items")->fetch_assoc()['c'];
$ntrx = $conn->query("SELECT COUNT(*) c FROM transactions")->fetch_assoc()['c'];
// agregat per kategori utk etalase + grafik (baca saja)
$katData = [];
$q = $conn->query("SELECT s.id, s.foto, s.kategori,
  COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) AS stok,
  COALESCE((SELECT SUM(qty) FROM transaction_items WHERE sample_id=s.id),0) AS tj
  FROM samples s ORDER BY s.kategori, s.id");
while ($r = $q->fetch_assoc()) {
    $k = $r['kategori'] ?: 'lainnya';
    if (!isset($katData[$k])) $katData[$k] = ['stok'=>0,'terjual'=>0,'nsampel'=>0,'foto'=>$r['foto']];
    $katData[$k]['stok'] += (int)$r['stok'];
    $katData[$k]['terjual'] += (int)$r['tj'];
    $katData[$k]['nsampel']++;
}
$katWarna = [
    'kemeja'=>'linear-gradient(135deg,#1a237e,#5c6bc0)', 'peci'=>'linear-gradient(135deg,#212121,#616161)',
    'topi'=>'linear-gradient(135deg,#e65100,#fb8c00)', 'tas'=>'linear-gradient(135deg,#4e342e,#8d6e63)',
    'kain'=>'linear-gradient(135deg,#1b5e20,#43a047)', 'taplak'=>'linear-gradient(135deg,#4a148c,#8e24aa)',
    'bendera'=>'linear-gradient(135deg,#b71c1c,#e53935)', 'sarung'=>'linear-gradient(135deg,#00695c,#00acc1)',
];
$palet = ['linear-gradient(135deg,#37474f,#78909c)','linear-gradient(135deg,#0d47a1,#42a5f5)','linear-gradient(135deg,#33691e,#7cb342)'];
$pi = 0;
foreach ($katData as $k => $v) {
    if (!isset($katWarna[$k])) $katWarna[$k] = $palet[$pi++ % count($palet)];
}
$maks = 1;
foreach ($katData as $v) $maks = max($maks, $v['stok'], $v['terjual']);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BUMK Store</title><link rel="stylesheet" href="assets/style.css?v=3"></head><body>
<div class="nav"><b>BUMK Store</b>
<a href="index.php" class="active">Dashboard</a><a href="sampel.php">Sampel</a><a href="scan.php">Scan + Stok</a><a href="kasir.php">Kasir</a><a href="laporan.php">Laporan</a>
</div>
<div class="wrap">
<div class="row">
<div class="card"><h3><?= (int)$total_sampel ?> Sampel</h3><p>Master barang + warna</p><br><a class="btn" href="sampel.php">Kelola</a></div>
<div class="card"><h3><?= (int)$total_stok ?> Stok</h3><p>Total semua item + ukuran</p><br><a class="btn green" href="scan.php">Scan Tambah</a></div>
<div class="card"><h3><?= (int)$terjual ?> Terjual</h3><p>Total pcs terjual (<?= (int)$ntrx ?> transaksi)</p><br><a class="btn" href="kasir.php">Kasir</a></div>
<div class="card"><h3><?= rupiah($omset) ?></h3><p>Total omset</p><br><a class="btn grey" href="laporan.php">Laporan</a></div>
</div>
<h3 style="margin:4px 0 10px">Etalase per Kategori</h3>
<div class="kat-grid">
<?php foreach ($katData as $k => $v): ?>
<div class="kat-card" style="background:<?= h($katWarna[$k]) ?>">
<img src="<?= h($v['foto']) ?>" alt="" width="72" height="72" style="width:72px;height:72px;object-fit:cover;border-radius:10px;flex:0 0 72px">
<div class="kat-info">
<b><?= h(strtoupper($k)) ?></b>
<span><?= (int)$v['nsampel'] ?> model &bull; <b><?= (int)$v['stok'] ?></b> stok &bull; <b><?= (int)$v['terjual'] ?></b> terjual</span>
<a href="sampel.php?kat=<?= urlencode($k) ?>">Lihat &raquo;</a>
</div></div>
<?php endforeach; ?>
</div>
<div class="card"><h3>Grafik Stok vs Terjual per Kategori</h3>
<p style="font-size:12px;color:#666"><span class="dot" style="background:#43a047"></span> Stok &nbsp; <span class="dot" style="background:#1e88e5"></span> Terjual &nbsp; (detail angka di <a href="laporan.php">Laporan</a>)</p>
<?php foreach ($katData as $k => $v):
  $pS = round($v['stok']/$maks*100); $pT = round($v['terjual']/$maks*100);
?>
<div class="gbar-row"><div class="gbar-label"><?= h(strtoupper($k)) ?></div>
<div class="gbar-track"><div class="gbar-fill stok" style="width:<?= $pS ?>%"></div></div><div class="gbar-num"><?= (int)$v['stok'] ?></div></div>
<div class="gbar-row"><div class="gbar-label"></div>
<div class="gbar-track"><div class="gbar-fill jual" style="width:<?= $pT ?>%"></div></div><div class="gbar-num"><?= (int)$v['terjual'] ?></div></div>
<?php endforeach; ?>
</div>
</div></body></html>
