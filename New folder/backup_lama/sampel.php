<?php require 'config.php';
$kat = strtolower(trim($_GET['kat'] ?? ''));
$where = ($kat !== '' && in_array($kat, kategori_list(), true)) ? "WHERE s.kategori='".$conn->real_escape_string($kat)."'" : '';
$q = $conn->query("SELECT s.*, COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) AS total FROM samples s $where ORDER BY s.id DESC");
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sampel - BUMK Store</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="nav"><b>BUMK Store</b>
<a href="index.php">Dashboard</a><a href="sampel.php" class="active">Sampel</a><a href="scan.php">Scan + Stok</a><a href="kasir.php">Kasir</a><a href="laporan.php">Laporan</a>
</div>
<div class="wrap">
<div class="card"><h3>Master Sampel Barang</h3><p>Input sekali per item, dipakai untuk pencocokan warna saat scan.</p>
<form method="get" class="row" style="margin-top:8px">
<div><label>Filter kategori</label><select name="kat" onchange="this.form.submit()">
<option value="">Semua</option>
<?php foreach (kategori_list() as $k): ?>
<option value="<?= $k ?>" <?= $kat===$k?'selected':'' ?>><?= ucfirst($k) ?></option>
<?php endforeach; ?></select></div>
<div><label>&nbsp;</label><a class="btn" href="sampel_add.php">+ Tambah Sampel</a> <a class="btn grey" href="kategori.php">Kelola Kategori</a></div>
</form></div>
<div class="grid">
<?php while($r=$q->fetch_assoc()):
  $sid=(int)$r['id'];
  $stp=$conn->prepare("SELECT ukuran,jumlah,harga FROM stock WHERE sample_id=? ORDER BY ukuran");
  $stp->bind_param('i',$sid); $stp->execute();
  $rs=$stp->get_result();
  $det=[]; $prices=[];
  while($s=$rs->fetch_assoc()){ $det[]=$s['ukuran'].':'.$s['jumlah'].' @'.number_format($s['harga'],0,',','.'); $prices[]=(int)$s['harga']; }
  $range = $prices ? 'Rp '.number_format(min($prices),0,',','.') . ' – Rp '.number_format(max($prices),0,',','.') : rupiah($r['harga']);
?>
<div class="item">
<img src="<?= h($r['foto']) ?>" alt="">
<div class="p">
<span class="badge"><?= h(strtoupper($r['kategori'] ?? 'kemeja')) ?></span> <span style="font-size:11px;color:#888"><?= h($r['kode'] ?? '') ?></span><br>
<b><?= h($r['nama']) ?></b><br>
<span class="dot" style="background:<?= h($r['warna_hex']) ?>"></span> <?= h($r['warna_nama']) ?> (<?= h($r['warna_hex']) ?>)<br>
<?= h($range) ?> &bull; <span class="badge">stok <?= (int)$r['total'] ?></span><br>
<span style="font-size:12px;color:#555"><?= h(implode(' | ', $det) ?: 'belum ada stok') ?></span><br><br>
<a class="btn" style="padding:6px 10px" href="sampel_kelola.php?id=<?= (int)$r['id'] ?>">Stok+Harga</a>
<form method="post" action="sampel_hapus.php" style="display:inline" onsubmit="return confirm('Hapus sampel + stoknya? Riwayat transaksi tetap disimpan.')">
<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button class="btn red" style="padding:6px 10px">Hapus</button>
</form>
</div></div>
<?php endwhile; ?>
</div></div></body></html>
