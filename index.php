<?php require 'config.php';
require_login();
// Data asli BUMK — read-only, tanpa PPN
$today = $conn->query("SELECT COALESCE(SUM(total),0) t, COUNT(*) c FROM transactions WHERE DATE(tanggal)=CURDATE()")->fetch_assoc();
$totalProduk = (int)$conn->query("SELECT COUNT(*) c FROM samples")->fetch_assoc()['c'];
// total stok per sampel
$perSampel = [];
$q = $conn->query("SELECT s.id,s.nama,s.kode,s.kategori,s.foto,COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) AS total FROM samples s ORDER BY total ASC");
while($r=$q->fetch_assoc()) $perSampel[]=$r;
$low = array_values(array_filter($perSampel, fn($x)=>(int)$x['total']<=5));
$nLow = count($low);
$low5 = array_slice($low, 0, 5);
// grafik 7 hari
$labels=[]; $vals=[];
for($i=6;$i>=0;$i--){
  $d = date('Y-m-d', strtotime("-$i days"));
  $labels[] = strftime('%a', strtotime($d));
  $row = $conn->query("SELECT COALESCE(SUM(total),0) t FROM transactions WHERE DATE(tanggal)='$d'")->fetch_assoc();
  $vals[] = (int)$row['t'];
}
// transaksi terakhir 5
$recent=[]; $qr=$conn->query("SELECT id,tanggal,total,kasir FROM transactions ORDER BY id DESC LIMIT 5");
while($r=$qr->fetch_assoc()) $recent[]=$r;
$u = current_user();
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BUMK Store - Dashboard Modern</title>
<link rel="icon" href="assets/logo.jpg">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#f0f9ff',100:'#e0f2fe',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985'}}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>body{font-family:'Inter',sans-serif}.custom-scrollbar::-webkit-scrollbar{width:6px;height:6px}.custom-scrollbar::-webkit-scrollbar-track{background:#f1f5f9}.custom-scrollbar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}</style>
</head>
<body class="h-full flex overflow-hidden text-slate-800">
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 z-40 transform -translate-x-full lg:static lg:translate-x-0 transition-transform duration-200">
  <div class="p-5 flex items-center gap-3 border-b border-slate-800">
    <img src="assets/logo.jpg" class="w-10 h-10 rounded-xl object-contain bg-white p-1" alt="BUMK">
    <div><h1 class="font-bold text-lg leading-none">BUMK Store</h1><span class="text-xs text-slate-400">POS & Penjualan</span></div>
  </div>
  <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto custom-scrollbar">
    <div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Menu Utama</div>
    <a href="index.php" class="flex items-center w-full gap-3 px-3.5 py-3 rounded-xl font-medium text-sm bg-brand-600 text-white"><i class="fa-solid fa-chart-pie w-5 text-center"></i><span>Dashboard</span></a>
    <a href="kasir.php" class="flex items-center w-full gap-3 px-3.5 py-3 rounded-xl font-medium text-sm text-slate-300 hover:bg-slate-800 hover:text-white"><i class="fa-solid fa-calculator w-5 text-center"></i><span>Kasir (POS)</span></a>
    <a href="sampel.php" class="flex items-center w-full gap-3 px-3.5 py-3 rounded-xl font-medium text-sm text-slate-300 hover:bg-slate-800 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i><span>Katalog Produk</span></a>
    <a href="laporan.php" class="flex items-center w-full gap-3 px-3.5 py-3 rounded-xl font-medium text-sm text-slate-300 hover:bg-slate-800 hover:text-white"><i class="fa-solid fa-warehouse w-5 text-center"></i><span>Kelola Stok</span></a>
    <a href="laporan.php#lap" class="flex items-center w-full gap-3 px-3.5 py-3 rounded-xl font-medium text-sm text-slate-300 hover:bg-slate-800 hover:text-white"><i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i><span>Laporan Penjualan</span></a>
    <a href="scan.php" class="flex items-center w-full gap-3 px-3.5 py-3 rounded-xl font-medium text-sm text-slate-300 hover:bg-slate-800 hover:text-white"><i class="fa-solid fa-camera w-5 text-center"></i><span>Scan + Stok</span></a>
  </nav>
  <div class="p-4 border-t border-slate-800 flex items-center gap-3">
    <div class="w-9 h-9 rounded-full bg-brand-600 flex items-center justify-center font-bold text-sm"><?= h(strtoupper(substr($u['username']??'A',0,2))) ?></div>
    <div class="flex-1 overflow-hidden"><p class="text-sm font-medium truncate"><?= h($u['username']??'Admin') ?></p><p class="text-xs text-emerald-400">● Online</p></div>
    <a href="logout.php" class="text-xs text-rose-400">Keluar</a>
  </div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden bg-slate-50 min-w-0">
<header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 flex items-center gap-2 z-10">
<button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button>
  <h2 class="text-lg sm:text-xl font-bold truncate">Dashboard Overview</h2>
  <div class="flex items-center gap-4">
    <div class="text-right hidden sm:block"><div id="current-date" class="text-sm font-semibold"></div><div id="current-time" class="text-xs text-slate-500"></div></div>
    <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
    <a href="modern_kasir.php" class="flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium"><i class="fa-solid fa-cart-plus"></i><span>Buka Kasir</span></a>
  </div>
</header>
<main class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-6">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Penjualan Hari Ini</p><h3 class="text-2xl font-bold mt-1"><?= h(rupiah($today['t'])) ?></h3><p class="text-xs text-emerald-600 font-medium mt-1"><?= (int)$today['c'] ?> transaksi hari ini</p></div><div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 text-xl"><i class="fa-solid fa-money-bill-wave"></i></div></div>
    <div class="bg-white p-5 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Total Transaksi</p><h3 class="text-2xl font-bold mt-1"><?= (int)$today['c'] ?></h3><p class="text-xs text-brand-600 font-medium mt-1">Hari ini</p></div><div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-brand-600 text-xl"><i class="fa-solid fa-receipt"></i></div></div>
    <div class="bg-white p-5 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Total Produk Active</p><h3 class="text-2xl font-bold mt-1"><?= (int)$totalProduk ?></h3><p class="text-xs text-slate-500 mt-1">Model dalam katalog</p></div><div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 text-xl"><i class="fa-solid fa-box"></i></div></div>
    <div class="bg-white p-5 rounded-2xl border shadow-sm flex items-center justify-between"><div><p class="text-xs font-medium text-slate-500 uppercase">Stok Menipis / Habis</p><h3 class="text-2xl font-bold text-amber-600 mt-1"><?= (int)$nLow ?></h3><p class="text-xs text-amber-600 font-medium mt-1">Stok ≤ 5</p></div><div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 text-xl"><i class="fa-solid fa-boxes-packing"></i></div></div>
  </div>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white p-5 rounded-2xl border shadow-sm"><h3 class="font-bold">Grafik Penjualan Mingguan</h3><p class="text-xs text-slate-500 mb-4">Ringkasan pendapatan 7 hari terakhir (tanpa PPN)</p><div class="h-64 relative"><canvas id="salesChart"></canvas></div></div>
    <div class="bg-white p-5 rounded-2xl border shadow-sm"><div class="flex items-center justify-between mb-4"><h3 class="font-bold">Perlu Restok</h3><a href="modern_laporan.php" class="text-xs text-brand-600 font-semibold">Lihat Semua</a></div>
      <div class="space-y-3"><?php foreach($low5 as $p): ?><div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border"><div class="flex items-center gap-2.5"><img src="<?= h($p['foto']) ?>" class="w-9 h-9 rounded-lg object-cover bg-slate-200" onerror="this.src='https://placehold.co/100/e2e8f0/64748b?text=Foto'"><div><p class="text-xs font-bold"><?= h($p['nama']) ?></p><p class="text-[10px] text-slate-400"><?= h(strtoupper($p['kategori'])) ?> • Sisa: <b><?= (int)$p['total'] ?></b></p></div></div><a href="modern_laporan.php" class="text-xs text-brand-600">Restok</a></div><?php endforeach; ?><?php if(!$low5): ?><p class="text-xs text-slate-400 italic">Semua stok mencukupi.</p><?php endif; ?></div>
    </div>
  </div>
  <div class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="p-5 border-b flex items-center justify-between"><h3 class="font-bold">Transaksi Terakhir</h3><a href="modern_laporan.php" class="text-xs text-brand-600 font-semibold">Lihat Semua</a></div>
    <table class="w-full text-left text-sm text-slate-600"><thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b"><tr><th class="px-5 py-3">ID</th><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Kasir</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Status</th></tr></thead>
    <tbody class="divide-y"><?php foreach($recent as $t): ?><tr class="hover:bg-slate-50"><td class="px-5 py-3 font-mono font-bold text-xs">#<?= (int)$t['id'] ?></td><td class="px-5 py-3 text-xs"><?= h($t['tanggal']) ?></td><td class="px-5 py-3 text-xs"><?= h($t['kasir']??'admin') ?></td><td class="px-5 py-3 text-xs font-bold"><?= h(rupiah($t['total'])) ?></td><td class="px-5 py-3"><span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-bold">Selesai</span></td></tr><?php endforeach; ?><?php if(!$recent): ?><tr><td colspan="5" class="text-center py-4 text-xs text-slate-400">Belum ada transaksi.</td></tr><?php endif; ?></tbody></table>
  </div>
</main></div>
<script>
function updateClock(){const n=new Date();document.getElementById('current-date').innerText=n.toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});document.getElementById('current-time').innerText=n.toLocaleTimeString('id-ID')+' WIB';}
updateClock(); setInterval(updateClock,1000);
new Chart(document.getElementById('salesChart'),{type:'line',data:{labels:<?= json_encode($labels) ?>,datasets:[{data:<?= json_encode($vals) ?>,borderColor:'#0ea5e9',backgroundColor:'rgba(14,165,233,0.1)',fill:true,tension:0.3,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>'Rp '+(v/1000)+'k',font:{size:10}}},x:{grid:{display:false}}}}});
function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}
</script></body></html>
