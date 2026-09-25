<?php require 'config.php';
require_login();
$msg = '';
$defMap = [];
foreach (kategori_list() as $k) $defMap[$k] = ukuran_default($k);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $kategori = strtolower(trim($_POST['kategori'] ?? 'kemeja'));
    if (!in_array($kategori, kategori_list(), true)) $kategori = 'kemeja';
    $harga = (int)($_POST['harga'] ?? 0);
    $warna_hex = strtoupper(trim($_POST['warna_hex'] ?? '#888888'));
    $warna_nama = trim($_POST['warna_nama'] ?? '-');
    $sizes = [];
    foreach (($_POST['ukuran'] ?? []) as $u) {
        $u = normal_ukuran($u);
        if ($u !== '' && valid_ukuran($u) && !in_array($u, $sizes, true)) $sizes[] = $u;
        if (count($sizes) >= 15) break;
    }
    if (!$sizes) $sizes = ukuran_default($kategori);
    if ($nama === '' || empty($_FILES['foto']['name'])) {
        $msg = 'Nama + foto wajib diisi.';
    } elseif (!$sizes) {
        $msg = 'Isi minimal 1 ukuran.';
    } else {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) $msg = 'Foto harus jpg/png/webp.';
        elseif (($_FILES['foto']['size'] ?? 0) > 5*1024*1024) $msg = 'Foto maksimal 5MB.';
        elseif (!@getimagesize($_FILES['foto']['tmp_name'])) $msg = 'File bukan gambar valid.';
        else {
            try {
            $fname = 'uploads/s_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0777, true);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/' . $fname)) {
                $ins = $conn->prepare("INSERT INTO samples (kode,nama,foto,warna_hex,warna_nama,harga,kategori,ukuran_list) VALUES ('',?,?,?,?,?,?,?)");
                $ulk = implode(',', $sizes);
                $ins->bind_param('ssssiss', $nama, $fname, $warna_hex, $warna_nama, $harga, $kategori, $ulk);
                $ins->execute();
                $sid = $ins->insert_id;
                $kode = 'BRG-' . str_pad($sid, 4, '0', STR_PAD_LEFT);
                $up = $conn->prepare("UPDATE samples SET kode=? WHERE id=?");
                $up->bind_param('si', $kode, $sid);
                $up->execute();
                $stok = $_POST['stok'] ?? []; $hrgA = $_POST['hargax'] ?? [];
                foreach ($sizes as $i => $u) {
                    $j = max(0, (int)($stok[$i] ?? 0));
                    $hrgU = (int)($hrgA[$i] ?? 0);
                    if ($hrgU <= 0) $hrgU = $harga;
                    if ($j > 0 || $hrgU > 0) {
                        $s2 = $conn->prepare("INSERT INTO stock (sample_id,ukuran,jumlah,harga) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE jumlah=jumlah+VALUES(jumlah), harga=VALUES(harga)");
                        $s2->bind_param('isii', $sid, $u, $j, $hrgU);
                        $s2->execute();
                    }
                }
                header('Location: sampel.php'); exit;
            } else $msg = 'Upload gagal. Pastikan folder uploads bisa ditulis (755).';
            } catch (Throwable $e) { $msg = 'Gagal simpan DB: ' . $e->getMessage(); }
        }
    }
}
$u = current_user();
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Sampel - BUMK Store</title>
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
<a href="sampel.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-brand-600 text-white font-medium"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i>Katalog Produk</a>
<a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-warehouse w-5 text-center"></i>Kelola Stok</a>
<a href="scan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-camera w-5 text-center"></i>Scan + Stok</a></nav>
<div class="p-4 border-t border-slate-800 text-sm"><?= h($u['username']??'Admin') ?> | <a href="logout.php" class="text-rose-400">Keluar</a></div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden">
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Tambah Sampel Baru</h2><a href="sampel.php" class="text-xs text-brand-600 font-semibold">← Kembali ke Katalog</a></header>
<main class="flex-1 overflow-y-auto p-6 custom-scrollbar">
<div class="bg-white p-6 rounded-2xl border shadow-sm max-w-3xl">
<?php if($msg): ?><div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm"><?= h($msg) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="space-y-4" id="formSampel">
<div><label class="text-xs font-semibold text-slate-600">Nama barang</label><input name="nama" required placeholder="cth: Peci Hitam Polos" class="w-full px-3 py-2 border rounded-xl text-sm mt-1"></div>
<div><label class="text-xs font-semibold text-slate-600">Kategori barang</label>
<select name="kategori" id="kategori" required class="w-full px-3 py-2 border rounded-xl text-sm mt-1 bg-white">
<?php foreach (kategori_list() as $k): ?>
<option value="<?= h($k) ?>"><?= h(ucfirst($k)) ?><?= $k==='kain' ? ' (per pcs)' : '' ?></option>
<?php endforeach; ?>
</select></div>
<div><label class="text-xs font-semibold text-slate-600">Harga dasar (Rp)</label><input name="harga" type="number" min="0" value="50000" required class="w-full px-3 py-2 border rounded-xl text-sm mt-1"></div>
<div><label class="text-xs font-semibold text-slate-600">Foto sampel</label><input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp" required class="w-full text-sm mt-1">
<p id="fotoInfo" class="text-[11px] text-slate-400 mt-1">Foto dari HP otomatis dikompres (maks 1280px, ~200-300KB) agar katalog/kasir tetap cepat. Tampilan tidak berubah.</p>
<img id="prev" class="rounded-xl mt-2 max-w-[260px] hidden" alt=""></div>
<div><label class="text-xs font-semibold text-slate-600">Warna (auto dari foto, bisa dikoreksi)</label>
<div class="flex gap-2 mt-1 items-center">
<input name="warna_hex" id="warna_hex" value="#888888" required class="flex-1 px-3 py-2 border rounded-xl text-sm">
<input name="warna_nama" id="warna_nama" value="-" required class="flex-1 px-3 py-2 border rounded-xl text-sm">
<span id="swatch" class="w-10 h-10 rounded-xl border inline-block" style="background:#888888"></span>
</div>
<button type="button" id="btnDetect" class="mt-2 px-3 py-2 border rounded-xl text-xs font-bold text-slate-600">Deteksi Ulang Warna</button></div>
<div><label class="text-xs font-semibold text-slate-600">Ukuran per kategori (bisa edit / tambah baris)</label>
<div class="overflow-x-auto mt-1"><table class="w-full text-sm"><thead><tr class="text-xs text-slate-500"><th class="text-left py-2">Ukuran</th><th class="text-left">Stok</th><th class="text-left">Harga (Rp)</th><th></th></tr></thead>
<tbody id="tblUk"></tbody></table></div>
<button type="button" id="addRow" class="mt-2 px-3 py-2 border rounded-xl text-xs font-bold text-slate-600">+ Tambah ukuran</button>
<p class="text-[11px] text-slate-400 mt-1">Kain dihitung per PCS. Kosongkan harga = ikut harga dasar.</p></div>
<button class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl">Simpan Sampel</button>
</form></div></main></div>
<script src="assets/app.js"></script>
<script>
const DEF = <?= json_encode($defMap) ?>;
const katSel = document.getElementById('kategori'), tbl = document.getElementById('tblUk');
function row(u='', s=0, hx=''){
  const tr = document.createElement('tr');
  tr.innerHTML = `<td class="py-1 pr-2"><input name="ukuran[]" value="${u}" maxlength="20" required class="w-full px-2 py-1.5 border rounded-lg text-sm"></td>
    <td class="py-1 pr-2"><input type="number" name="stok[]" value="${s}" min="0" class="w-24 px-2 py-1.5 border rounded-lg text-sm"></td>
    <td class="py-1 pr-2"><input type="number" name="hargax[]" value="${hx}" min="0" placeholder="ikut dasar" class="w-32 px-2 py-1.5 border rounded-lg text-sm"></td>
    <td class="py-1"><button type="button" class="px-2 py-1.5 text-rose-500">x</button></td>`;
  tr.querySelector('button').onclick = () => tr.remove();
  tbl.appendChild(tr);
}
function loadKat(){ tbl.innerHTML=''; (DEF[katSel.value]||['ALL SIZE']).forEach(u=>row(u,0,'')); }
katSel.onchange = loadKat; loadKat();
document.getElementById('addRow').onclick = () => { if(tbl.rows.length<15) row('',0,''); };
const fi=document.getElementById('foto'),pv=document.getElementById('prev');
const hx=document.getElementById('warna_hex'),nm=document.getElementById('warna_nama'),sw=document.getElementById('swatch');
const fotoInfo=document.getElementById('fotoInfo');
// Kompres sisi-client (tanpa GD server): max 1280px, JPEG q0.78. Tampilan kartu (h-40) tidak berubah.
function kompresFoto(file){return new Promise(res=>{if(!file||!/^image\//.test(file.type)||file.size<400*1024)return res(file);const img=new Image();img.onload=()=>{const maxS=1280;let w=img.width,h=img.height;const sc=Math.min(1,maxS/Math.max(w,h));w=Math.round(w*sc);h=Math.round(h*sc);const c=document.createElement('canvas');c.width=w;c.height=h;const ctx=c.getContext('2d');ctx.fillStyle='#ffffff';ctx.fillRect(0,0,w,h);ctx.drawImage(img,0,0,w,h);URL.revokeObjectURL(img.src);c.toBlob(b=>res(b?new File([b],'foto.jpg',{type:'image/jpeg'}):file),'image/jpeg',0.78);};img.onerror=()=>res(file);img.src=URL.createObjectURL(file);});}
document.getElementById('formSampel').addEventListener('submit',async e=>{const f=fi.files[0];if(!f)return;e.preventDefault();if(fotoInfo)fotoInfo.textContent='Mengompres foto...';try{const kecil=await kompresFoto(f);const dt=new DataTransfer();dt.items.add(kecil);fi.files=dt.files;if(fotoInfo&&kecil.size<f.size)fotoInfo.textContent='Foto dikompres: '+(f.size/1048576).toFixed(1)+'MB → '+(kecil.size/1024).toFixed(0)+'KB.';}catch(err){}e.target.submit();});
function apply(c){hx.value=c;nm.value=nearestColorName(c);sw.style.background=c;}
fi.onchange=()=>{const f=fi.files[0];if(!f)return;pv.classList.remove('hidden');pv.src=URL.createObjectURL(f);
pv.onload=()=>apply(dominantColorOf(pv));};
document.getElementById('btnDetect').onclick=()=>{if(pv.src)apply(dominantColorOf(pv));};
hx.oninput=()=>{sw.style.background=hx.value;try{nm.value=nearestColorName(hx.value);}catch(e){}};
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
