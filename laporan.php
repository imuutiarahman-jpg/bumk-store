<?php require 'config.php';
require_login();
$today = $conn->query("SELECT COALESCE(SUM(total),0) t, COUNT(*) c FROM transactions WHERE DATE(tanggal)=CURDATE()")->fetch_assoc();
$trx = $conn->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 50");
$ids=[]; $rows=[];
while($r=$trx->fetch_assoc()){ $ids[]=(int)$r['id']; $rows[]=$r; }
$detail=[];
if($ids){ $in=implode(',',$ids); $q=$conn->query("SELECT i.transaction_id,COALESCE(s.nama,'[terhapus]') nama,i.ukuran,i.qty,i.harga FROM transaction_items i LEFT JOIN samples s ON s.id=i.sample_id WHERE i.transaction_id IN ($in)"); while($x=$q->fetch_assoc()) $detail[$x['transaction_id']][]=$x; }
$perItem=$conn->query("SELECT s.id,s.kode,s.nama,s.kategori,s.foto,s.warna_nama,s.harga,COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) AS stok FROM samples s ORDER BY s.kategori,s.nama");
$items=[]; while($r=$perItem->fetch_assoc()) $items[]=$r;
$stokDet=[]; $q2=$conn->query("SELECT st.sample_id,st.ukuran,st.jumlah,st.harga FROM stock st ORDER BY st.sample_id");
while($x=$q2->fetch_assoc()) $stokDet[$x['sample_id']][]=$x;
$u=current_user();
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BUMK Store - Stok & Laporan Modern</title>
<link rel="icon" href="assets/logo.jpg">
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
<a href="sampel.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i>Katalog Produk</a>
<a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-brand-600 text-white font-medium"><i class="fa-solid fa-warehouse w-5 text-center"></i>Kelola Stok</a>
<a href="#lap" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>Laporan Penjualan</a>
<a href="scan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-camera w-5 text-center"></i>Scan + Stok</a></nav>
<div class="p-4 border-t border-slate-800 text-sm"><?= h($u['username']??'Admin') ?> | <a href="logout.php" class="text-rose-400">Keluar</a></div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden">
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Kelola Stok / Laporan</h2><span class="text-xs text-slate-500">Hari ini: <?= h(rupiah($today['t'])) ?> (<?= (int)$today['c'] ?> trx)</span></header>
<main class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-6">
<div class="bg-white p-4 rounded-2xl border shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
<div class="relative w-full sm:w-72"><i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i><input id="cari" placeholder="Cari barang / kode..." class="w-full pl-10 pr-4 py-2 border rounded-xl text-sm"></div>
<div class="flex gap-2"><select id="fstat" class="border rounded-xl px-3 py-2 text-sm"><option value="">Semua Status</option><option value="aman">Tersedia</option><option value="menipis">Menipis (≤5)</option><option value="habis">Habis (0)</option></select><a href="sampel_add.php" class="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Tambah</a></div>
</div>
<div class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b"><tr><th class="px-5 py-3">Produk</th><th class="px-5 py-3">Kategori</th><th class="px-5 py-3">Harga</th><th class="px-5 py-3">Sisa</th><th class="px-5 py-3">Total Nilai</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead><tbody id="tb" class="divide-y">
<?php $grand=0; foreach($items as $r): $st=(int)$r['stok']; $nilai=(int)$r['harga']*$st; $grand+=$nilai; $badge=$st===0?'<span class="px-2.5 py-1 bg-rose-50 text-rose-600 rounded-lg text-xs font-bold">Habis</span>':($st<=5?'<span class="px-2.5 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-bold">Menipis</span>':'<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-bold">Tersedia</span>'); $rinci=implode(' | ', array_map(fn($s)=>$s['ukuran'].':'.$s['jumlah'].' @'.number_format($s['harga'],0,',','.'), $stokDet[$r['id']]??[])); ?>
<tr class="brg hover:bg-slate-50" data-stat="<?= $st===0?'habis':($st<=5?'menipis':'aman') ?>"><td class="px-5 py-3 flex items-center gap-3"><img src="<?= h($r['foto']) ?>" class="w-9 h-9 rounded-lg object-cover bg-slate-100" onerror="this.src='https://placehold.co/100/e2e8f0/64748b?text=P'"><span><b><?= h($r['nama']) ?></b><br><span class="text-[11px] text-slate-400"><?= h($r['kode']) ?> • <?= h($r['warna_nama']) ?><br><?= h($rinci?:'-') ?></span></span></td><td class="px-5 py-3 text-xs"><?= h(strtoupper($r['kategori'])) ?></td><td class="px-5 py-3 text-xs font-bold"><?= h(rupiah($r['harga'])) ?></td><td class="px-5 py-3 font-bold"><?= $st ?></td><td class="px-5 py-3 text-xs font-bold text-brand-700"><?= h(rupiah($nilai)) ?><br><span class="text-[10px] font-normal text-slate-400"><?= number_format((int)$r['harga'],0,',','.') ?> × <?= $st ?></span></td><td class="px-5 py-3"><?= $badge ?></td><td class="px-5 py-3 text-right"><a href="sampel_kelola.php?id=<?= (int)$r['id'] ?>" class="px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-bold">Edit Stok</a></td></tr>
<?php endforeach; ?>
</tbody><tfoot><tr class="bg-slate-50 border-t font-bold text-sm"><td colspan="4" class="px-5 py-3 text-right">Grand Total Nilai Stok:</td><td class="px-5 py-3 text-brand-700"><?= h(rupiah($grand)) ?></td><td colspan="2"></td></tr></tfoot></table></div></div>
<div id="lap" class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="p-4 border-b flex justify-between items-center"><h3 class="font-bold">50 Transaksi Terakhir</h3><button onclick="expCSV()" class="bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-semibold"><i class="fa-solid fa-file-excel mr-1"></i>Export CSV</button></div><div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b"><tr><th class="px-5 py-3">ID</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Item</th><th class="px-5 py-3">Kasir</th><th class="px-5 py-3">Total</th></tr></thead><tbody class="divide-y"><?php foreach($rows as $t): $dets=[]; foreach(($detail[$t['id']]??[]) as $x) $dets[]=$x['nama']." ({$x['ukuran']} x{$x['qty']})"; ?><tr class="hover:bg-slate-50"><td class="px-5 py-3 font-mono font-bold text-xs">#<?= (int)$t['id'] ?></td><td class="px-5 py-3 text-xs"><?= h($t['tanggal']) ?></td><td class="px-5 py-3 text-xs"><?= h(implode(', ',$dets)) ?></td><td class="px-5 py-3 text-xs"><?= h($t['kasir']??'admin') ?></td><td class="px-5 py-3 text-xs font-bold"><?= h(rupiah($t['total'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</main></div>
<script>
const TRX=<?= json_encode($rows) ?>;
document.getElementById('cari').oninput=e=>{const v=e.target.value.toLowerCase(),s=document.getElementById('fstat').value;document.querySelectorAll('.brg').forEach(tr=>{const okS=!s||tr.dataset.stat===s;tr.style.display=(tr.innerText.toLowerCase().includes(v)&&okS)?'':'none';});};
document.getElementById('fstat').onchange=e=>document.getElementById('cari').oninput({target:document.getElementById('cari')});
function expCSV(){let c="data:text/csv;charset=utf-8,ID,Tanggal,Kasir,Total\n";TRX.forEach(t=>{c+=`${t.id},"${t.tanggal}",${t.kasir},${t.total}\n`;});const a=document.createElement('a');a.href=encodeURI(c);a.download='laporan.csv';a.click();}
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
