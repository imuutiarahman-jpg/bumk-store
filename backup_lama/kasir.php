<?php require 'config.php';
$samples = $conn->query("SELECT s.*, COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) total FROM samples s ORDER BY s.kategori, s.nama");
$data = [];
while($r=$samples->fetch_assoc()){
  $sid=(int)$r['id'];
  $stp=$conn->prepare("SELECT ukuran,jumlah,harga FROM stock WHERE sample_id=?");
  $stp->bind_param('i',$sid); $stp->execute();
  $rs=$stp->get_result();
  $r['stok']=[]; $r['harga_map']=[]; $r['ukuran_all']=[];
  while($s=$rs->fetch_assoc()) {
    $r['stok'][$s['ukuran']]=(int)$s['jumlah'];
    $r['harga_map'][$s['ukuran']]=(int)$s['harga'] > 0 ? (int)$s['harga'] : (int)$r['harga'];
    $r['ukuran_all'][]=$s['ukuran'];
  }
  foreach (array_filter(array_map('normal_ukuran', explode(',', ($r['ukuran_list'] ?? '')))) as $u) {
    if (!in_array($u, $r['ukuran_all'], true)) $r['ukuran_all'][] = $u;
  }
  $data[]=$r;
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kasir</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="nav"><b>BUMK Store</b><a href="index.php">Dashboard</a><a href="sampel.php">Sampel</a><a href="scan.php">Scan + Stok</a><a href="kasir.php" class="active">Kasir</a><a href="laporan.php">Laporan</a></div>
<div class="wrap"><div class="row">
<div class="card" style="flex:2"><h3>Pilih Barang</h3>
<div class="row"><div><select id="fkat"><option value="">Semua kategori</option>
<?php foreach (kategori_list() as $k): ?><option value="<?= $k ?>"><?= ucfirst($k) ?></option><?php endforeach; ?>
</select></div>
<div><input id="cari" placeholder="Ketik nama / warna..."></div></div>
<div class="grid" id="list" style="margin-top:10px"></div></div>
<div class="card" style="flex:1"><h3>Keranjang</h3>
<div id="cart"></div>
<h3 id="tot">Total: Rp 0</h3><br>
<button class="btn green" id="bayar">Bayar / Checkout</button>
<div id="msg"></div></div>
</div></div>
<script>
const BARANG = <?= json_encode($data) ?>;
const cart = []; // {id,nama,ukuran,harga,qty}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function rupiah(n){return 'Rp '+n.toLocaleString('id-ID');}
function render(){
  const fk=document.getElementById('fkat').value;
  const filter=document.getElementById('cari').value.toLowerCase();
  const L=document.getElementById('list'); L.innerHTML='';
  BARANG.filter(b=>(!fk||b.kategori===fk)&&(b.nama+' '+b.warna_nama).toLowerCase().includes(filter)).forEach(b=>{
    const priceOf=u=>+(b.harga_map[u]||b.harga);
    const stockOf=u=>(b.stok[u]||0);
    const uks=(b.ukuran_all||Object.keys(b.stok)).filter(u=>stockOf(u)>0);
    const d=document.createElement('div'); d.className='item';
    d.innerHTML=`<img src="${esc(b.foto)}"><div class="p"><span class="badge">${esc(String(b.kategori||'').toUpperCase())}</span><br><b>${esc(b.nama)}</b><br>${esc(b.warna_nama)}<br>
      <small class="pr">${uks.length?rupiah(priceOf(uks[0])):rupiah(+b.harga)}</small><br>
      <small>${uks.map(u=>esc(u)+':'+stockOf(u)+' @'+priceOf(u).toLocaleString('id-ID')).join(' | ')||'stok kosong'}</small><br><br>
      <select>${uks.map(u=>`<option value="${esc(u)}">${esc(u)} - ${rupiah(priceOf(u))} (sisa ${stockOf(u)})</option>`).join('')}</select>
      <div style="display:flex;gap:6px;margin-top:6px;align-items:center">
      <input type="number" value="1" min="1" style="width:64px;margin:0" title="Jumlah">
      <button class="btn" style="padding:6px 10px">+ Keranjang</button></div></div>`;
    const sel=d.querySelector('select'), qin=d.querySelector('input');
    const syncMax=()=>{ qin.max=stockOf(sel.value); if(+qin.value>+qin.max) qin.value=qin.max; d.querySelector('.pr').textContent=rupiah(priceOf(sel.value)); };
    sel.onchange=syncMax; syncMax();
    d.querySelector('button').onclick=()=>{
      const u=sel.value; if(!u){alert('Stok kosong');return;}
      let q=Math.max(1,parseInt(qin.value||'1',10));
      const sisa=stockOf(u);
      const f=cart.find(c=>c.id==b.id&&c.ukuran==u);
      const sudah=f?f.qty:0;
      if(sudah+q>sisa){ alert('Melebihi stok! Sisa '+sisa+', sudah di keranjang '+sudah+'.'); q=sisa-sudah; }
      if(q<=0) return;
      const hrg=priceOf(u);
      if(f) f.qty+=q; else cart.push({id:b.id,nama:b.nama+' ['+b.kategori+']',ukuran:u,harga:hrg,qty:q});
      renderCart();
    };
    L.appendChild(d);
  });
}
function renderCart(){
  const C=document.getElementById('cart'); let t=0; C.innerHTML='';
  cart.forEach((c,i)=>{t+=c.harga*c.qty;
    C.innerHTML+=`<p>${esc(c.nama)} (${esc(c.ukuran)})<br>@${rupiah(c.harga)} x <input type="number" data-i="${i}" value="${c.qty}" min="1" style="width:56px;margin:0;padding:4px"> = ${rupiah(c.harga*c.qty)} <a href="#" data-i="${i}">hapus</a></p>`;});
  document.getElementById('tot').textContent='Total: '+rupiah(t);
  C.querySelectorAll('input').forEach(inp=>inp.onchange=()=>{
    const i=+inp.dataset.i;
    const b=BARANG.find(x=>x.id==cart[i].id);
    const sisa=b?(b.stok[cart[i].ukuran]||0):99;
    let v=Math.max(1,parseInt(inp.value||'1',10));
    if(v>sisa){ alert('Melebihi stok! Sisa '+sisa+'.'); v=sisa; }
    cart[i].qty=v; renderCart();
  });
  C.querySelectorAll('a').forEach(a=>a.onclick=e=>{e.preventDefault();cart.splice(+a.dataset.i,1);renderCart();});
}
document.getElementById('cari').oninput=render;
document.getElementById('fkat').onchange=render;
document.getElementById('bayar').onclick=async()=>{
  if(!cart.length){alert('Keranjang kosong');return;}
  let j;
  try { j=await fetch('api_checkout.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({items:cart})}).then(r=>r.json()); }
  catch(e){ document.getElementById('msg').innerHTML='<div class="alert">Server error.</div>'; return; }
  document.getElementById('msg').innerHTML=j.ok?'<div class="alert">Transaksi #'+j.id+' sukses: '+rupiah(j.total)+'. Halaman dimuat ulang.</div>':'<div class="alert">Gagal: '+esc(j.msg||'')+'</div>';
  if(j.ok){cart.length=0;setTimeout(()=>location.reload(),1200);}
};
render();renderCart();
</script></body></html>
