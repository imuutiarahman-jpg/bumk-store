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
<link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="https://placehold.co">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#f0f9ff',100:'#e0f2fe',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985'}}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:'Inter',sans-serif}.custom-scrollbar::-webkit-scrollbar{width:6px;height:6px}.custom-scrollbar::-webkit-scrollbar-track{background:#f1f5f9}.custom-scrollbar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
table{font-variant-numeric:tabular-nums}
/* Scrollable table region: smooth touch scroll + visible focus for keyboard users */
.table-scroll{-webkit-overflow-scrolling:touch;scrollbar-width:thin}
.table-scroll:focus-visible{outline:2px solid #0284c7;outline-offset:-2px}
/* "Geser" hint: only shown via JS when the table actually overflows */
.scroll-hint{display:none}
.scroll-hint.on{display:flex}
/* ===== Mobile: data table transforms into stacked cards (best practice) ===== */
@media (max-width:639.98px){
  table.cards-sm thead{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}
  table.cards-sm,table.cards-sm tbody,table.cards-sm tfoot{display:block;width:100%}
  table.cards-sm tbody{display:flex;flex-direction:column;gap:.75rem;background:#f1f5f9;padding:.75rem}
  table.cards-sm tbody tr{display:block;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:.875rem 1rem;box-shadow:0 1px 2px rgba(15,23,42,.05)}
  table.cards-sm tbody tr:hover{background:#fff}
  table.cards-sm td{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding:.5rem 0;border:0;text-align:right;font-size:.8125rem}
  table.cards-sm td+td{border-top:1px dashed #f1f5f9}
  table.cards-sm td::before{content:attr(data-label);font-size:.6875rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;text-align:left;flex-shrink:0;padding-top:.15rem}
  table.cards-sm td>*{min-width:0;overflow-wrap:anywhere}
  table.cards-sm td.cell-main,table.cards-sm td.cell-action,table.cards-sm td.cell-empty{display:flex;padding-left:0;padding-right:0}
  table.cards-sm td.cell-main::before,table.cards-sm td.cell-action::before,table.cards-sm td.cell-empty::before{content:none}
  table.cards-sm td.cell-main{justify-content:flex-start;text-align:left;padding-bottom:.65rem}
  table.cards-sm td.cell-action{padding-top:.75rem}
  table.cards-sm td.cell-action a{display:inline-flex;width:100%;align-items:center;justify-content:center;min-height:44px;font-size:.875rem;border-radius:.75rem}
  table.cards-sm td.cell-empty{justify-content:center;text-align:center;color:#94a3b8}
  table.cards-sm tfoot tr{display:flex;flex-direction:column;align-items:stretch;gap:.25rem;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;margin:.75rem;padding:.875rem 1rem}
  table.cards-sm tfoot td{padding:0;border:0}
  table.cards-sm tfoot td::before{content:none}
  table.cards-sm tfoot td.foot-label{text-align:left;font-size:.75rem;font-weight:600;color:#64748b}
  table.cards-sm tfoot td.foot-value{text-align:left;font-size:1.125rem}
  table.cards-sm tfoot td.foot-empty{display:none}
}
@media (prefers-reduced-motion:reduce){.table-scroll{scroll-behavior:auto}}</style>
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
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-x-2 gap-y-1 flex-wrap"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600 min-w-[44px] min-h-[44px] inline-flex items-center justify-center" aria-label="Buka menu navigasi"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-lg sm:text-xl font-bold truncate">Kelola Stok / Laporan</h2><span class="text-xs text-slate-500 basis-full sm:basis-auto sm:ml-auto">Hari ini: <?= h(rupiah($today['t'])) ?> (<?= (int)$today['c'] ?> trx)</span></header>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 custom-scrollbar space-y-4 sm:space-y-6">
<div class="bg-white p-4 rounded-2xl border shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
<div class="relative w-full sm:w-72"><i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none" aria-hidden="true"></i><input id="cari" type="search" placeholder="Cari barang / kode..." aria-label="Cari barang berdasarkan nama atau kode" class="w-full pl-10 pr-4 py-2.5 border rounded-xl text-base sm:text-sm min-h-[44px]"></div>
<div class="flex flex-wrap gap-2"><select id="fstat" aria-label="Filter berdasarkan status stok" class="border rounded-xl px-3 py-2.5 text-base sm:text-sm min-h-[44px] bg-white"><option value="">Semua Status</option><option value="aman">Tersedia</option><option value="menipis">Menipis (≤5)</option><option value="habis">Habis (0)</option></select><a href="sampel_add.php" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center justify-center min-h-[44px]"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>Tambah</a></div>
</div>
<?php $sumProduk=count($items); $sumStok=0; $sumNilai=0; foreach($items as $_it){ $_st=(int)$_it['stok']; $sumStok+=$_st; $sumNilai+=(int)$_it['harga']*$_st; } ?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
<div class="bg-white p-4 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Total Produk</p><h3 class="text-2xl font-bold mt-1"><span id="sumProduk"><?= (int)$sumProduk ?></span> <span class="text-sm font-medium text-slate-400">model</span></h3><p class="text-xs text-slate-500 mt-1">Model dalam katalog</p></div><div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 text-xl"><i class="fa-solid fa-box"></i></div></div>
<div class="bg-white p-4 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Total Keseluruhan Stok</p><h3 class="text-2xl font-bold mt-1 text-brand-700"><span id="sumStok"><?= number_format((int)$sumStok,0,',','.') ?></span> <span class="text-sm font-medium text-slate-400">pcs</span></h3><p class="text-xs text-slate-500 mt-1">Semua produk & ukuran</p></div><div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-brand-600 text-xl"><i class="fa-solid fa-boxes-stacked"></i></div></div>
<div class="bg-white p-4 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Nilai Stok</p><h3 class="text-2xl font-bold mt-1"><span id="sumNilai"><?= h(rupiah($sumNilai)) ?></span></h3><p class="text-xs text-slate-500 mt-1">Harga dasar × sisa</p></div><div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 text-xl"><i class="fa-solid fa-money-bill-wave"></i></div></div>
</div>
<div class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="tbl-card"><p class="scroll-hint items-center gap-1.5 px-4 py-2 text-[11px] text-slate-400 border-b bg-slate-50/60"><i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>Geser tabel ke samping untuk melihat semua kolom</p><div class="table-scroll overflow-x-auto" tabindex="0" role="region" aria-label="Tabel stok produk"><table class="w-full sm:min-w-[860px] text-sm text-left cards-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b"><tr><th scope="col" class="px-5 py-3">Produk</th><th scope="col" class="px-5 py-3">Kategori</th><th scope="col" class="px-5 py-3">Harga</th><th scope="col" class="px-5 py-3">Sisa</th><th scope="col" class="px-5 py-3">Total Nilai</th><th scope="col" class="px-5 py-3">Status</th><th scope="col" class="px-5 py-3 text-right">Aksi</th></tr></thead><tbody id="tb" class="divide-y sm:divide-y">
<?php $grand=0; $grandStok=0; foreach($items as $r): $st=(int)$r['stok']; $nilai=(int)$r['harga']*$st; $grand+=$nilai; $grandStok+=$st; $badge=$st===0?'<span class="px-2.5 py-1 bg-rose-50 text-rose-600 rounded-lg text-xs font-bold whitespace-nowrap">Habis</span>':($st<=5?'<span class="px-2.5 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-bold whitespace-nowrap">Menipis</span>':'<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-bold whitespace-nowrap">Tersedia</span>'); $rinci=implode(' | ', array_map(fn($s)=>$s['ukuran'].':'.$s['jumlah'].' @'.number_format($s['harga'],0,',','.'), $stokDet[$r['id']]??[])); ?>
<tr class="brg hover:bg-slate-50" data-stat="<?= $st===0?'habis':($st<=5?'menipis':'aman') ?>" data-stok="<?= $st ?>" data-nilai="<?= $nilai ?>"><td class="px-5 py-3 cell-main" data-label="Produk"><div class="flex items-center gap-3 min-w-0"><img src="<?= h($r['foto']) ?>" alt="Foto <?= h($r['nama']) ?>" width="36" height="36" loading="lazy" decoding="async" class="w-9 h-9 rounded-lg object-cover bg-slate-100 flex-shrink-0" onerror="this.src='https://placehold.co/100/e2e8f0/64748b?text=P'"><span class="min-w-0"><b class="block break-words"><?= h($r['nama']) ?></b><span class="block text-[11px] text-slate-400 break-words"><?= h($r['kode']) ?> • <?= h($r['warna_nama']) ?><br><?= h($rinci?:'-') ?></span></span></div></td><td class="px-5 py-3 text-xs" data-label="Kategori"><?= h(strtoupper($r['kategori'])) ?></td><td class="px-5 py-3 text-xs font-bold whitespace-nowrap" data-label="Harga"><?= h(rupiah($r['harga'])) ?></td><td class="px-5 py-3 font-bold" data-label="Sisa"><?= $st ?></td><td class="px-5 py-3 text-xs font-bold text-brand-700" data-label="Total Nilai"><span><span class="block"><?= h(rupiah($nilai)) ?></span><span class="block text-[10px] font-normal text-slate-400"><?= number_format((int)$r['harga'],0,',','.') ?> × <?= $st ?></span></span></td><td class="px-5 py-3" data-label="Status"><?= $badge ?></td><td class="px-5 py-3 sm:text-right cell-action" data-label="Aksi"><a href="sampel_kelola.php?id=<?= (int)$r['id'] ?>" class="inline-flex items-center justify-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-bold whitespace-nowrap">Edit Stok</a></td></tr>
<?php endforeach; ?>
<?php if(!$items): ?><tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400 cell-empty" data-label="">Belum ada produk. Tambahkan lewat tombol “Tambah”.</td></tr><?php endif; ?>
</tbody><tfoot><tr class="bg-slate-50 border-t font-bold text-sm"><td colspan="4" class="px-5 py-3 sm:text-right foot-label">Grand Total Nilai Stok (<span id="grandUnit"><?= (int)$grandStok ?></span> pcs):</td><td class="px-5 py-3 text-brand-700 whitespace-nowrap foot-value"><span id="grandVal"><?= h(rupiah($grand)) ?></span></td><td colspan="2" class="foot-empty"></td></tr></tfoot></table></div></div></div>
<div id="lap" class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="p-4 border-b flex flex-wrap gap-2 justify-between items-center"><h3 class="font-bold">50 Transaksi Terakhir</h3><button onclick="expCSV()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center justify-center min-h-[44px]"><i class="fa-solid fa-file-excel mr-1" aria-hidden="true"></i>Export CSV</button></div><div class="tbl-card"><p class="scroll-hint items-center gap-1.5 px-4 py-2 text-[11px] text-slate-400 border-b bg-slate-50/60"><i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>Geser tabel ke samping untuk melihat semua kolom</p><div class="table-scroll overflow-x-auto" tabindex="0" role="region" aria-label="Tabel 50 transaksi terakhir"><table class="w-full sm:min-w-[720px] text-sm text-left cards-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b"><tr><th scope="col" class="px-5 py-3">ID</th><th scope="col" class="px-5 py-3">Tanggal</th><th scope="col" class="px-5 py-3">Item</th><th scope="col" class="px-5 py-3">Kasir</th><th scope="col" class="px-5 py-3">Total</th></tr></thead><tbody class="divide-y sm:divide-y"><?php foreach($rows as $t): $dets=[]; foreach(($detail[$t['id']]??[]) as $x) $dets[]=$x['nama']." ({$x['ukuran']} x{$x['qty']})"; ?><tr class="hover:bg-slate-50"><td class="px-5 py-3 font-mono font-bold text-xs whitespace-nowrap" data-label="ID">#<?= (int)$t['id'] ?></td><td class="px-5 py-3 text-xs whitespace-nowrap" data-label="Tanggal"><?= h($t['tanggal']) ?></td><td class="px-5 py-3 text-xs" data-label="Item"><?= h(implode(', ',$dets)) ?></td><td class="px-5 py-3 text-xs" data-label="Kasir"><?= h($t['kasir']??'admin') ?></td><td class="px-5 py-3 text-xs font-bold whitespace-nowrap" data-label="Total"><?= h(rupiah($t['total'])) ?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400 cell-empty" data-label="">Belum ada transaksi.</td></tr><?php endif; ?></tbody></table></div></div></div>
</main></div>
<script>
const TRX=<?= json_encode($rows) ?>;
document.getElementById('cari').oninput=e=>{const v=e.target.value.toLowerCase(),s=document.getElementById('fstat').value;document.querySelectorAll('.brg').forEach(tr=>{const okS=!s||tr.dataset.stat===s;tr.style.display=(tr.innerText.toLowerCase().includes(v)&&okS)?'':'none';});hitungVisible();};
document.getElementById('fstat').onchange=e=>document.getElementById('cari').oninput({target:document.getElementById('cari')});
function rupiahJS(n){return 'Rp '+(+n).toLocaleString('id-ID');}
function hitungVisible(){let ts=0,tn=0,vis=0;const rows=document.querySelectorAll('.brg');rows.forEach(tr=>{if(tr.style.display!=='none'){ts+=+(tr.dataset.stok||0);tn+=+(tr.dataset.nilai||0);vis++;}});const gu=document.getElementById('grandUnit'),gv=document.getElementById('grandVal');if(gu)gu.textContent=ts.toLocaleString('id-ID');if(gv)gv.textContent=rupiahJS(tn);const ss=document.getElementById('sumStok'),sn=document.getElementById('sumNilai'),sp=document.getElementById('sumProduk');if(ss)ss.textContent=ts.toLocaleString('id-ID');if(sn)sn.textContent=rupiahJS(tn);if(sp)sp.textContent=vis.toLocaleString('id-ID');const fl=document.querySelector('tfoot .foot-label');if(fl)fl.title=vis+' dari '+rows.length+' produk tampil (mengikuti filter/pencarian)';}
function expCSV(){let c="data:text/csv;charset=utf-8,ID,Tanggal,Kasir,Total\n";TRX.forEach(t=>{c+=`${t.id},"${t.tanggal}",${t.kasir},${t.total}\n`;});const a=document.createElement('a');a.href=encodeURI(c);a.download='laporan.csv';a.click();}
// Tampilkan petunjuk "geser" hanya jika tabel benar-benar overflow (tablet/layar kecil)
function fitHints(){document.querySelectorAll('.tbl-card').forEach(card=>{const sc=card.querySelector('.table-scroll'),h=card.querySelector('.scroll-hint');if(!sc||!h)return;h.classList.toggle('on',sc.scrollWidth>sc.clientWidth+4);});}
window.addEventListener('resize',fitHints);fitHints();
document.querySelectorAll('.table-scroll').forEach(el=>el.addEventListener('scroll',()=>{const h=el.parentElement.querySelector('.scroll-hint');if(h&&el.scrollLeft>24)h.classList.remove('on');},{passive:true}));
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
