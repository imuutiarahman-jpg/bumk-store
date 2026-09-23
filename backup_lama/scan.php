<?php require 'config.php'; ?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Scan + Stok</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="nav"><b>BUMK Store</b><a href="index.php">Dashboard</a><a href="sampel.php">Sampel</a><a href="scan.php" class="active">Scan + Stok</a><a href="kasir.php">Kasir</a><a href="laporan.php">Laporan</a></div>
<div class="wrap">
<div class="card">
<h3>Scan Foto → Tambah Stok (per item)</h3>
<p style="font-size:13px">1) Pilih kategori dulu (biar peci tidak tertukar tas) &nbsp; 2) Foto &nbsp; 3) Pilih kandidat &nbsp; 4) Pilih ukuran khas item itu → stok +1</p><br>
<div class="row">
<div>
<label>Kategori yg di-scan</label>
<select id="katScan">
<?php foreach (kategori_list() as $k): ?>
<option value="<?= $k ?>"><?= ucfirst($k) ?><?= $k==='kain' ? ' (per pcs)' : '' ?></option>
<?php endforeach; ?>
</select>
<label>Ambil dari kamera (HP/laptop)</label>
<input type="file" id="cam" accept="image/*" capture="environment">
<label>atau upload file</label>
<input type="file" id="up" accept="image/*">
<img id="prev" class="preview" style="display:none">
<p>Warna terdeteksi: <span id="sw" class="dot"></span> <b id="hx">-</b> (<span id="nm">-</span>)</p>
<button class="btn" id="btnCari">Cari Sampel Mirip</button>
</div>
<div>
<label>Hasil pencocokan warna (sesama kategori)</label>
<div id="hasil"><p style="font-size:13px;color:#666">Belum ada. Foto dulu lalu klik Cari.</p></div>
</div>
</div>
</div>
<div class="card">
<h3>Tambah stok manual (tanpa foto)</h3>
<form id="fManual" class="row">
<div><label>Kategori</label><select id="mKat">
<?php foreach (kategori_list() as $k): ?>
<option value="<?= $k ?>"><?= ucfirst($k) ?></option>
<?php endforeach; ?></select></div>
<div><label>Barang</label><select name="sample_id" id="mBrg" required></select></div>
<div><label>Ukuran</label><select name="ukuran" id="mUk"></select></div>
<div><label>Qty</label><input type="number" name="qty" value="1" min="1"></div>
<div><label>Harga baru (opsional)</label><input type="number" name="harga" min="0" placeholder="kosong = tetap"></div>
<div><label>&nbsp;</label><button class="btn green">+ Tambah</button></div>
</form><div id="msgM"></div>
</div>
</div>
<script src="assets/app.js"></script>
<script>
const SAMPLES = <?= json_encode($conn->query("SELECT id,nama,warna_nama,kategori,ukuran_list FROM samples ORDER BY nama")->fetch_all(MYSQLI_ASSOC)) ?>;
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
let curHex='#888888';
const prev=document.getElementById('prev');
function loadFile(f){ if(!f)return; prev.style.display='block'; prev.src=URL.createObjectURL(f);
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
  if(!r.data.length){ box.innerHTML='<p style="font-size:13px">Belum ada sampel kategori '+esc(kat)+'. Tambah dulu di menu Sampel.</p>'; return; }
  r.data.forEach(o=>{
    const sizes=Object.keys(o.stokmap||{});
    const opts=sizes.length?sizes:(o.ukuran_list||'').split(',').filter(Boolean);
    const d=document.createElement('div'); d.className='kandidat';
    d.innerHTML=`<img src="${esc(o.foto)}"><div style="flex:1"><b>${esc(o.nama)}</b> <span class="badge">${esc((o.kategori||'').toUpperCase())}</span><br>
      <span class="dot" style="background:${esc(o.warna_hex)}"></span> ${esc(o.warna_nama)} • ${esc(o.similarity)}% mirip${o.similarity<65?' • <b style="color:#c62828">rendah, cek manual</b>':''} • <span class="stokinfo">stok ${esc(o.total)}</span><br>
      <select>${opts.map(u=>`<option>${esc(u)}</option>`).join('')}</select>
      <button class="btn green" style="padding:6px 10px">+ Stok</button></div>`;
    const btn=d.querySelector('button'), sel=d.querySelector('select');
    const info=d.querySelector('.stokinfo');
    btn.onclick=async()=>{
      const fd=new FormData(); fd.append('sample_id',o.id); fd.append('ukuran',sel.value); fd.append('qty',1);
      let j;
      try { j=await fetch('api_stok.php',{method:'POST',body:fd}).then(r=>r.json()); }
      catch(e){ alert('Server error / bukan JSON. Cek config DB hosting.'); return; }
      if(j.ok){ alert(`Stok ${o.nama} ukuran ${sel.value} +1. Sisa sekarang: ${j.sisa}`); info.textContent='stok '+j.sisa; }
      else alert('Gagal: '+(j.msg||'unknown'));
    };
    box.appendChild(d);
  });
};
// manual: dropdown barang mengikuti kategori
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
  catch(err){ document.getElementById('msgM').innerHTML='<div class="alert">Server error. Cek koneksi DB.</div>'; return; }
  document.getElementById('msgM').innerHTML=j.ok?`<div class="alert">Stok bertambah. Sisa sekarang: ${j.sisa}.</div>`:`<div class="alert">Gagal: ${esc(j.msg||'unknown')}</div>`;
};
</script></body></html>
