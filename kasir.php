<?php require 'config.php';
require_login();
$samples = $conn->query("SELECT s.*, COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) total FROM samples s ORDER BY s.kategori, s.nama");
$data = [];
$byId = [];
while($r=$samples->fetch_assoc()){
  $r['stok']=[]; $r['harga_map']=[]; $r['ukuran_all']=[];
  $byId[(int)$r['id']]=$r;
}
// 1 query stok untuk semua produk (sebelumnya N+1)
if($byId){
  $in = implode(',', array_map('intval', array_keys($byId)));
  $qs = $conn->query("SELECT sample_id,ukuran,jumlah,harga FROM stock WHERE sample_id IN ($in)");
  if($qs) while($s=$qs->fetch_assoc()){
    $sid=(int)$s['sample_id'];
    if(!isset($byId[$sid])) continue;
    $byId[$sid]['stok'][$s['ukuran']]=(int)$s['jumlah'];
    $byId[$sid]['harga_map'][$s['ukuran']]=(int)$s['harga']>0?(int)$s['harga']:(int)$byId[$sid]['harga'];
    $byId[$sid]['ukuran_all'][]=$s['ukuran'];
  }
}
foreach($byId as $r){
  foreach(array_filter(array_map('normal_ukuran',explode(',',($r['ukuran_list']??'')))) as $u){
    if(!in_array($u,$r['ukuran_all'],true)) $r['ukuran_all'][]=$u;
  }
  $data[]=$r;
}
$kats = kategori_list();
$u = current_user();
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BUMK Store - Kasir Modern</title>
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
<a href="kasir.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-brand-600 text-white font-medium"><i class="fa-solid fa-calculator w-5 text-center"></i>Kasir (POS)</a>
<a href="sampel.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i>Katalog Produk</a>
<a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-warehouse w-5 text-center"></i>Kelola Stok</a>
<a href="laporan.php#lap" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>Laporan Penjualan</a>
<a href="scan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-camera w-5 text-center"></i>Scan + Stok</a></nav>
<div class="p-4 border-t border-slate-800 text-sm"><?= h($u['username']??'Admin') ?> | <a href="logout.php" class="text-rose-400">Keluar</a></div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden">
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Kasir (Point of Sale)</h2><div class="text-sm text-slate-500">Foto dari database • Tanpa PPN</div></header>
<main class="flex-1 flex flex-col lg:flex-row gap-6 p-6 overflow-hidden">
<div class="flex-1 flex flex-col h-full overflow-hidden bg-white rounded-2xl border shadow-sm">
<div class="p-4 border-b space-y-3 bg-slate-50"><div class="relative"><i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400"></i><input id="cari" placeholder="Cari nama / kode / warna..." class="w-full pl-10 pr-4 py-2 rounded-xl border text-sm"></div><div id="pills" class="flex gap-2 overflow-x-auto custom-scrollbar pb-1"></div></div>
<div class="flex-1 overflow-y-auto p-4 custom-scrollbar"><div id="grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div><div id="pager" class="flex flex-wrap items-center justify-center gap-2 py-3"></div></div>
</div>
<div class="w-full lg:w-96 bg-white rounded-2xl border shadow-sm flex flex-col overflow-hidden">
<div class="p-4 border-b flex justify-between items-center bg-slate-900 text-white"><h3 class="font-bold"><i class="fa-solid fa-cart-shopping text-brand-500 mr-2"></i>Keranjang</h3><button onclick="cart=[];renderCart()" class="text-xs text-rose-400">Kosongkan</button></div>
<div id="cart" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar text-sm"></div>
<div class="p-4 border-t bg-slate-50 space-y-2"><div class="flex justify-between text-sm"><span>Subtotal</span><b id="sub">Rp 0</b></div><div class="flex justify-between font-bold text-lg border-t pt-2"><span>Total</span><span id="tot" class="text-brand-600">Rp 0</span></div>
<button id="bayar" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl"><i class="fa-solid fa-circle-check mr-1"></i>Bayar Sekarang</button><div id="msg" class="text-xs"></div></div>
</div></main></div>
<div id="nota" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center p-4 z-50"><div class="bg-white rounded-2xl max-w-sm w-full p-6"><h3 class="font-bold mb-2">Transaksi Berhasil</h3><div id="notaIsi" class="text-sm"></div><div class="flex gap-2 mt-4"><button onclick="window.print()" class="flex-1 py-2 bg-slate-800 text-white rounded-xl text-sm">Cetak</button><button onclick="location.reload()" class="flex-1 py-2 bg-brand-500 text-white rounded-xl text-sm">Selesai</button></div></div></div>
<script>
const BARANG=<?= json_encode($data) ?>; const KATS=<?= json_encode($kats) ?>;
let fkat='',cart=[],page=1;const PER=12;
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const rupiah=n=>'Rp '+(+n).toLocaleString('id-ID');
function pills(){const b=document.getElementById('pills');const a=['',...KATS];b.innerHTML=a.map(k=>`<button data-k="${esc(k)}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap ${fkat===k?'bg-slate-900 text-white':'bg-white border text-slate-600'}">${k===''?'Semua':esc(k.toUpperCase())}</button>`).join('');b.querySelectorAll('button').forEach(x=>x.onclick=()=>{fkat=x.dataset.k;page=1;pills();render();});}
function render(){const f=document.getElementById('cari').value.toLowerCase();const g=document.getElementById('grid');g.innerHTML='';
const list=BARANG.filter(b=>(!fkat||b.kategori===fkat)&&(b.nama+' '+b.warna_nama+' '+b.kode).toLowerCase().includes(f));
const tp=Math.max(1,Math.ceil(list.length/PER));if(page>tp)page=tp;
list.slice((page-1)*PER,page*PER).forEach(b=>{
const p=u=>+(b.harga_map[u]||b.harga), s=u=>(b.stok[u]||0), uks=(b.ukuran_all||[]).filter(u=>s(u)>0);
const d=document.createElement('div');d.className='bg-white rounded-xl border overflow-hidden shadow-sm flex flex-col group';
d.innerHTML=`<div class="h-28 overflow-hidden relative bg-slate-100"><img src="${esc(b.foto)}" width="300" height="112" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/300x200/e2e8f0/64748b?text=Foto'"><span class="absolute top-2 right-2 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-900/70 text-white">Stok: ${uks.reduce((a,u)=>a+s(u),0)}</span></div><div class="p-3 text-xs flex-1 flex flex-col gap-1"><span class="text-[10px] text-slate-400 font-bold">${esc((b.kategori||'').toUpperCase())} • ${esc(b.kode||'')}</span><b>${esc(b.nama)}</b><span class="text-slate-500">${esc(b.warna_nama)}</span><span class="font-bold text-brand-600">${uks.length?rupiah(p(uks[0])):rupiah(+b.harga)}</span><small class="text-slate-500">${uks.map(u=>esc(u)+':'+s(u)).join(' | ')||'stok kosong'}</small><select class="border rounded-lg px-2 py-1 mt-1">${uks.map(u=>`<option value="${esc(u)}">${esc(u)} — ${rupiah(p(u))} (sisa ${s(u)})</option>`).join('')}</select><div class="flex gap-2 mt-2"><input type="number" value="1" min="1" class="border rounded-lg w-16 px-2 py-1"><button class="flex-1 bg-slate-900 text-white rounded-lg py-1.5 font-bold">+ Keranjang</button></div></div>`;
const sel=d.querySelector('select'),qin=d.querySelector('input');
if(!uks.length){d.querySelector('button').disabled=true;d.classList.add('opacity-50');}
else d.querySelector('button').onclick=()=>{const u=sel.value;let q=Math.max(1,parseInt(qin.value||'1'));const sisa=s(u);const f=cart.find(c=>c.id==b.id&&c.ukuran==u);if((f?f.qty:0)+q>sisa){alert('Melebihi stok! Sisa '+sisa);return;}if(f)f.qty+=q;else cart.push({id:b.id,nama:b.nama+' ['+b.kategori+']',ukuran:u,harga:p(u),qty:q});renderCart();};
g.appendChild(d);});
if(!g.children.length)g.innerHTML='<p class="text-sm text-slate-400 col-span-full text-center py-10">Produk tidak ditemukan</p>';
pager(list.length,tp);}
function pager(n,tp){const pg=document.getElementById('pager');if(!pg)return;if(tp<=1||!n){pg.innerHTML=n?'':'<span class="text-xs text-slate-400"></span>';return;}let h=`<span class="text-xs text-slate-400 w-full text-center">${n} produk • Halaman ${page} dari ${tp}</span>`;if(page>1)h+=`<button data-p="${page-1}" class="px-3 py-2 border rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100">← Sebelumnya</button>`;for(let p=1;p<=tp;p++){h+=p===page?`<span class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white">${p}</span>`:`<button data-p="${p}" class="px-3 py-2 border rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100">${p}</button>`;}if(page<tp)h+=`<button data-p="${page+1}" class="px-3 py-2 border rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100">Berikutnya →</button>`;pg.innerHTML=h;pg.querySelectorAll('button').forEach(b=>b.onclick=()=>{page=+b.dataset.p;render();const g=document.getElementById('grid');if(g&&g.parentElement)g.parentElement.scrollTop=0;});}
function renderCart(){const C=document.getElementById('cart');let t=0;C.innerHTML='';cart.forEach((c,i)=>{t+=c.harga*c.qty;C.innerHTML+=`<div class="border-b pb-2"><b>${esc(c.nama)} (${esc(c.ukuran)})</b><br>@${rupiah(c.harga)} x ${c.qty} = <b>${rupiah(c.harga*c.qty)}</b> <a href="#" data-i="${i}" class="text-rose-500">hapus</a></div>`;});if(!cart.length)C.innerHTML='<p class="text-slate-400 text-center py-10 text-xs">Keranjang kosong</p>';document.getElementById('sub').textContent=rupiah(t);document.getElementById('tot').textContent=rupiah(t);C.querySelectorAll('a').forEach(a=>a.onclick=e=>{e.preventDefault();cart.splice(+a.dataset.i,1);renderCart();});}
document.getElementById('cari').oninput=()=>{page=1;render();};
document.getElementById('bayar').onclick=async()=>{if(!cart.length){alert('Keranjang kosong');return;}const r=await fetch('api_checkout.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({items:cart})}).then(r=>r.json()).catch(()=>null);if(!r||!r.ok){document.getElementById('msg').textContent='Gagal: '+((r&&r.msg)||'server error');return;}document.getElementById('notaIsi').innerHTML=`Transaksi #${r.id}<br>Total: <b>${rupiah(r.total)}</b>`;const m=document.getElementById('nota');m.classList.remove('hidden');m.classList.add('flex');cart=[];};
pills();render();renderCart();
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
