<?php require 'config.php';
require_login();
$kat = strtolower(trim($_GET['kat'] ?? ''));
$where = ($kat !== '' && in_array($kat, kategori_list(), true)) ? "WHERE s.kategori='".$conn->real_escape_string($kat)."'" : '';
$q = $conn->query("SELECT s.*, COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) AS total FROM samples s $where ORDER BY s.id DESC");
$rows = [];
$ids = [];
while($r=$q->fetch_assoc()){ $r['det']=[]; $rows[(int)$r['id']]=$r; $ids[]=(int)$r['id']; }
// 1 query stok untuk semua produk (sebelumnya N+1: 1 prepare per produk)
if($ids){
  $in = implode(',', $ids);
  $qs = $conn->query("SELECT sample_id,ukuran,jumlah,harga FROM stock WHERE sample_id IN ($in) ORDER BY ukuran");
  if($qs) while($s=$qs->fetch_assoc()){ $sid=(int)$s['sample_id']; if(isset($rows[$sid])) $rows[$sid]['det'][]=$s; }
}
foreach($rows as &$r){
  $prices = array_map(fn($s)=>(int)$s['harga'], $r['det']);
  $r['range']=$prices ? 'Rp '.number_format(min($prices),0,',','.') . ' – Rp '.number_format(max($prices),0,',','.') : rupiah($r['harga']);
}
unset($r); $rows=array_values($rows);
// Pagination ringan gaya sama (12/halaman) — hanya batasi gambar yg dimuat, desain kartu tidak berubah
$perPage=12; $totalRows=count($rows); $totalPages=max(1,(int)ceil($totalRows/$perPage));
$page=max(1,min($totalPages,(int)($_GET['page']??1)));
$pageRows=array_slice($rows,($page-1)*$perPage,$perPage);
$base='modern_katalog.php'.($kat!==''?'?kat='.urlencode($kat).'&':'?');
$u = current_user();
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BUMK Store - Katalog Modern</title>
<link rel="icon" href="assets/logo.jpg">
<link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="https://placehold.co">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#f0f9ff',100:'#e0f2fe',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985'}}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:'Inter',sans-serif}.custom-scrollbar::-webkit-scrollbar{width:6px;height:6px}.custom-scrollbar::-webkit-scrollbar-track{background:#f1f5f9}.custom-scrollbar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}</style>
</head><body class="h-full flex overflow-hidden text-slate-800">
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 z-40 transform -translate-x-full lg:static lg:translate-x-0 transition-transform duration-200">
<div class="p-5 flex items-center gap-3 border-b border-slate-800"><img src="assets/logo.jpg" class="w-10 h-10 rounded-xl object-contain bg-white p-1" alt="BUMK"><div><h1 class="font-bold text-lg leading-none">BUMK Store</h1><span class="text-xs text-slate-400">POS & Penjualan</span></div></div>
<nav class="flex-1 p-4 space-y-1.5 text-sm"><div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase">Menu Utama</div>
<a href="index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-chart-pie w-5 text-center"></i>Dashboard</a>
<a href="kasir.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-calculator w-5 text-center"></i>Kasir (POS)</a>
<a href="sampel.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-brand-600 text-white font-medium"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i>Katalog Produk</a>
<a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-warehouse w-5 text-center"></i>Kelola Stok</a>
<a href="laporan.php#lap" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>Laporan Penjualan</a>
<a href="scan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-camera w-5 text-center"></i>Scan + Stok</a></nav>
<div class="p-4 border-t border-slate-800 text-sm"><?= h($u['username']??'Admin') ?> | <a href="logout.php" class="text-rose-400">Keluar</a></div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden">
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Katalog Produk</h2><span class="text-xs text-slate-500">Foto dari database</span></header>
<main class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-6">
<div class="bg-white p-4 rounded-2xl border shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
<div class="flex flex-1 items-center gap-3"><div class="relative flex-1 max-w-xs"><i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i><input id="cari" placeholder="Cari nama / kode / warna..." class="w-full pl-10 pr-4 py-2 border rounded-xl text-sm"></div>
<select id="fkat" class="border rounded-xl px-3 py-2 text-sm"><option value="">Semua Kategori</option><?php foreach(kategori_list() as $k): ?><option value="<?= h($k) ?>" <?= $kat===$k?'selected':'' ?>><?= h(strtoupper($k)) ?></option><?php endforeach; ?></select></div>
<div class="flex gap-2"><a href="sampel_add.php" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Tambah Produk Baru</a><a href="kategori.php" class="border px-4 py-2 rounded-xl text-sm text-slate-600">Kelola Kategori</a></div>
</div>
<div id="grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
<?php foreach($pageRows as $r): ?>
<div class="kard bg-white rounded-2xl border shadow-sm overflow-hidden flex flex-col" data-cari="<?= h(strtolower($r['nama'].' '.$r['kode'].' '.$r['warna_nama'].' '.$r['kategori'])) ?>">
<div class="h-40 overflow-hidden relative bg-slate-100"><img src="<?= h($r['foto']) ?>" width="400" height="160" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/300x200/e2e8f0/64748b?text=Foto'"><span class="absolute top-3 right-3 px-2.5 py-1 rounded-lg text-xs font-bold <?= (int)$r['total']<=5?'bg-amber-500 text-white':'bg-slate-900/80 text-white' ?>">Stok: <?= (int)$r['total'] ?></span></div>
<div class="p-4 flex-1 flex flex-col"><span class="text-xs font-semibold text-brand-600 uppercase"><?= h($r['kategori']) ?> • <?= h($r['kode']) ?></span><h4 class="font-bold text-sm mt-0.5"><?= h($r['nama']) ?></h4><span class="text-xs text-slate-500"><span class="inline-block w-3 h-3 rounded-full border align-middle" style="background:<?= h($r['warna_hex']) ?>"></span> <?= h($r['warna_nama']) ?></span><p class="text-sm font-bold mt-1"><?= h($r['range']) ?></p><p class="text-[11px] text-slate-400 mt-1"><?= h(implode(' | ', array_map(fn($s)=>$s['ukuran'].':'.$s['jumlah'], $r['det'])) ?: 'belum ada stok') ?></p>
<div class="mt-3 pt-3 border-t flex gap-1"><a href="sampel_kelola.php?id=<?= (int)$r['id'] ?>" class="flex-1 text-center p-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-bold"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a><form method="post" action="sampel_hapus.php" class="flex-1" onsubmit="return confirm('Hapus?')"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="w-full p-2 hover:bg-rose-50 text-slate-500 hover:text-rose-600 rounded-lg text-xs"><i class="fa-solid fa-trash-can"></i></button></form></div></div>
</div>
<?php endforeach; ?>
</div>
<?php if(!$rows): ?><p class="text-sm text-slate-400">Tidak ada produk.</p><?php endif; ?>
<?php if($totalPages>1): ?><div class="flex flex-wrap items-center justify-center gap-2 pt-2">
<span class="text-xs text-slate-400 w-full text-center">Menampilkan <?= count($pageRows) ?> dari <?= (int)$totalRows ?> produk • Halaman <?= (int)$page ?> dari <?= (int)$totalPages ?></span>
<?php if($page>1): ?><a href="<?= $base.'page='.($page-1) ?>" class="px-3 py-2 border rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100">← Sebelumnya</a><?php endif; ?>
<?php for($p=1;$p<=$totalPages;$p++): ?><?php if($p===$page): ?><span class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white"><?= $p ?></span><?php else: ?><a href="<?= $base.'page='.$p ?>" class="px-3 py-2 border rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100"><?= $p ?></a><?php endif; ?><?php endfor; ?>
<?php if($page<$totalPages): ?><a href="<?= $base.'page='.($page+1) ?>" class="px-3 py-2 border rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100">Berikutnya →</a><?php endif; ?>
</div><?php endif; ?>
</main></div>
<script>
document.getElementById('fkat').onchange=e=>{const v=e.target.value;location.href='modern_katalog.php'+(v?'?kat='+encodeURIComponent(v):'');};
document.getElementById('cari').oninput=e=>{const f=e.target.value.toLowerCase();document.querySelectorAll('.kard').forEach(k=>k.style.display=k.dataset.cari.includes(f)?'':'none');};
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
