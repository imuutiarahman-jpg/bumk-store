<?php require 'config.php'; require_login(); $u = current_user(); ?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scan + Stok - BUMK Store</title>
<link rel="icon" href="assets/logo.jpg">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#f0f9ff',100:'#e0f2fe',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985'}}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:'Inter',sans-serif}</style>
</head><body class="h-full flex overflow-hidden text-slate-800">
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 z-40 transform -translate-x-full lg:static lg:translate-x-0 transition-transform duration-200">
<div class="p-5 flex items-center gap-3 border-b border-slate-800"><img src="assets/logo.jpg" class="w-10 h-10 rounded-xl object-contain bg-white p-1" alt="BUMK"><div><h1 class="font-bold text-lg leading-none">BUMK Store</h1><span class="text-xs text-slate-400">POS & Penjualan</span></div></div>
<nav class="flex-1 p-4 space-y-1.5 text-sm"><div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase">Menu Utama</div>
<a href="index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-chart-pie w-5 text-center"></i>Dashboard</a>
<a href="kasir.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-calculator w-5 text-center"></i>Kasir (POS)</a>
<a href="sampel.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i>Katalog Produk</a>
<a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-warehouse w-5 text-center"></i>Kelola Stok</a>
<a href="scan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-brand-600 text-white font-medium"><i class="fa-solid fa-camera w-5 text-center"></i>Scan + Stok</a></nav>
<div class="p-4 border-t border-slate-800 text-sm"><?= h($u['username']??'Admin') ?> | <a href="logout.php" class="text-rose-400">Keluar</a></div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden">
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Scan + Stok</h2><span class="text-xs text-slate-500">Foto → warna → stok +1</span></header>
<main class="flex-1 overflow-y-auto p-6 space-y-6">
<div class="bg-white p-6 rounded-2xl border shadow-sm">
<h3 class="font-bold">Scan Foto → Tambah Stok (per item)</h3>
<p class="text-xs text-slate-500 mb-4">1) Pilih kategori dulu &nbsp; 2) Foto &nbsp; 3) Pilih kandidat &nbsp; 4) Pilih ukuran → stok +1</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-3">
<div><label class="text-xs font-semibold text-slate-600">Kategori yg di-scan</label>
<select id="katScan" class="w-full px-3 py-2 border rounded-xl text-sm mt-1 bg-white">
<?php foreach (kategori_list() as $k): ?>
<option value="<?= h($k) ?>"><?= h(ucfirst($k)) ?><?= $k==='kain' ? ' (per pcs)' : '' ?></option>
<?php endforeach; ?>
</select></div>
<div><label class="text-xs font-semibold text-slate-600">Ambil dari kamera (HP/laptop)</label><input type="file" id="cam" accept="image/*" capture="environment" class="w-full text-sm mt-1"></div>
<div><label class="text-xs font-semibold text-slate-600">atau upload file</label><input type="file" id="up" accept="image/*" class="w-full text-sm mt-1"></div>
<img id="prev" class="rounded-xl max-w-[260px] hidden" alt="">
<p class="text-sm">Warna terdeteksi: <span id="sw" class="inline-block w-4 h-4 rounded-full border align-middle"></span> <b id="hx">-</b> (<span id="nm">-</span>)</p>
<button id="btnCari" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold">Cari Sampel Mirip</button>
</div>
<div>
<label class="text-xs font-semibold text-slate-600">Hasil pencocokan warna (sesama kategori)</label>
<div id="hasil" class="mt-2 space-y-2"><p class="text-xs text-slate-400">Belum ada. Foto dulu lalu klik Cari.</p></div>
</div>
</div>
</div>
<div class="bg-white p-6 rounded-2xl border shadow-sm">
<h3 class="font-bold mb-3">Tambah stok manual (tanpa foto)</h3>
<form id="fManual" class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
<div><label class="text-xs font-semibold">Kategori</label><select id="mKat" class="w-full px-2 py-2 border rounded-xl text-sm mt-1 bg-white">
<?php foreach (kategori_list() as $k): ?>
<option value="<?= h($k) ?>"><?= h(ucfirst($k)) ?></option>
<?php endforeach; ?></select></div>
<div><label class="text-xs font-semibold">Barang</label><select name="sample_id" id="mBrg" required class="w-full px-2 py-2 border rounded-xl text-sm mt-1 bg-white"></select></div>
<div><label class="text-xs font-semibold">Ukuran</label><select name="ukuran" id="mUk" class="w-full px-2 py-2 border rounded-xl text-sm mt-1 bg-white"></select></div>
<div><label class="text-xs font-semibold">Qty</label><input type="number" name="qty" value="1" min="1" class="w-full px-2 py-2 border rounded-xl text-sm mt-1"></div>
<div><label class="text-xs font-semibold">Harga baru</label><input type="number" name="harga" min="0" placeholder="tetap" class="w-full px-2 py-2 border rounded-xl text-sm mt-1"></div>
<div><button class="w-full px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold">+ Tambah</button></div>
</form><div id="msgM" class="mt-2 text-sm"></div>
</div>
</main></div>
<script src="assets/app.js"></script>
<script>
const SAMPLES = <?= json_encode($conn->query("SELECT id,nama,warna_nama,kategori,ukuran_list FROM samples ORDER BY nama")->fetch_all(MYSQLI_ASSOC)) ?>;
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
let curHex='#888888';
const prev=document.getElementById('prev');
function loadFile(f){ if(!f)return; prev.classList.remove('hidden'); prev.src=URL.createObjectURL(f);
  prev.onload=()=>{curHex=dominantColorOf(prev);
    document.getElementById('hx').textContent=curHex;
    document.getElementById('nm').textContent=nearestColorName(curHex);
    document.getElementById('sw').style.background=curHex;};}
document.getElementById('cam').onchange=e=>loadFile(e.target.files[0]);
document.getElementById('up').onchange=e=>loadFile(e.target.files[0]);
document.getElementById('btnCari').onclick=async()=>{
  if(!prev.src){alert('Foto dulu');return;}
  const kat=document.getElementById('katScan').value;
  let r;
  try { r=await fetch('api_match.php?hex='+encodeURIComponent(curHex)+'&kategori='+encodeURIComponent(kat)).then(r=>r.json()); }
  catch(e){ alert('Server error. Cek koneksi DB.'); return; }
  const box=document.getElementById('hasil'); box.innerHTML='';
  if(!r.data.length){ box.innerHTML='<p class="text-xs">Belum ada sampel kategori '+esc(kat)+'.</p>'; return; }
  r.data.forEach(o=>{
    const sizes=Object.keys(o.stokmap||{});
    const opts=sizes.length?sizes:(o.ukuran_list||'').split(',').filter(Boolean);
    const d=document.createElement('div'); d.className='flex gap-3 items-center border rounded-xl p-3';
    d.innerHTML=`<img src="${esc(o.foto)}" class="w-16 h-16 rounded-lg object-cover bg-slate-100" onerror="this.src='https://placehold.co/100/e2e8f0/64748b?text=P'"><div class="flex-1 text-sm"><b>${esc(o.nama)}</b> <span class="text-[10px] font-bold text-brand-600">${esc((o.kategori||'').toUpperCase())}</span><br>
      <span class="text-xs text-slate-500">${esc(o.warna_nama)} • ${esc(o.similarity)}% mirip • <span class="stokinfo">stok ${esc(o.total)}</span></span><br>
      <div class="flex gap-2 mt-1"><select class="border rounded-lg px-2 py-1 text-xs">${opts.map(u=>`<option>${esc(u)}</option>`).join('')}</select>
      <button class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold">+ Stok</button></div></div>`;
    const btn=d.querySelector('button'), sel=d.querySelector('select');
    const info=d.querySelector('.stokinfo');
    btn.onclick=async()=>{
      const fd=new FormData(); fd.append('sample_id',o.id); fd.append('ukuran',sel.value); fd.append('qty',1);
      let j;
      try { j=await fetch('api_stok.php',{method:'POST',body:fd}).then(r=>r.json()); }
      catch(e){ alert('Server error.'); return; }
      if(j.ok){ alert(`Stok ${o.nama} ukuran ${sel.value} +1. Sisa: ${j.sisa}`); info.textContent='stok '+j.sisa; }
      else alert('Gagal: '+(j.msg||'unknown'));
    };
    box.appendChild(d);
  });
};
const mKat=document.getElementById('mKat'), mBrg=document.getElementById('mBrg'), mUk=document.getElementById('mUk');
function fillBrg(){
  mBrg.innerHTML='';
  SAMPLES.filter(s=>s.kategori===mKat.value).forEach(s=>{
    const o=document.createElement('option'); o.value=s.id; o.textContent=s.nama+' - '+s.warna_nama; mBrg.appendChild(o);
  });
  if(!mBrg.options.length) mBrg.innerHTML='<option value="">(belum ada)</option>';
  fillUk();
}
function fillUk(){
  const s=SAMPLES.find(x=>x.id==mBrg.value);
  const list=s?String(s.ukuran_list||'').split(',').filter(Boolean):[];
  mUk.innerHTML=list.map(u=>`<option>${esc(u)}</option>`).join('')||'<option>ALL SIZE</option>';
}
mKat.onchange=fillBrg; mBrg.onchange=fillUk; fillBrg();
document.getElementById('fManual').onsubmit=async e=>{
  e.preventDefault();
  let j;
  try { j=await fetch('api_stok.php',{method:'POST',body:new FormData(e.target)}).then(r=>r.json()); }
  catch(err){ document.getElementById('msgM').innerHTML='<div class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 text-xs">Server error.</div>'; return; }
  document.getElementById('msgM').innerHTML=j.ok?`<div class="px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs">Stok bertambah. Sisa: ${j.sisa}.</div>`:`<div class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 text-xs">Gagal: ${esc(j.msg||'unknown')}</div>`;
};
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
