<?php require 'config.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$st0 = $conn->prepare("SELECT * FROM samples WHERE id=?");
$st0->bind_param('i', $id);
$st0->execute();
$s = $st0->get_result()->fetch_assoc();
if (!$s) { header('Location: sampel.php'); exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    $U = $_POST['ukuran'] ?? []; $St = $_POST['stok'] ?? []; $Hx = $_POST['hargax'] ?? [];
    $seen = [];
    $n = min(count($U), 15);
    for ($i = 0; $i < $n; $i++) {
        $u = normal_ukuran($U[$i] ?? '');
        if ($u === '' || !valid_ukuran($u) || isset($seen[$u])) continue;
        $seen[$u] = true;
        $j = max(0, (int)($St[$i] ?? 0));
        $harga = max(0, (int)($Hx[$i] ?? 0));
        $st = $conn->prepare("INSERT INTO stock (sample_id,ukuran,jumlah,harga) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), harga=VALUES(harga)");
        $st->bind_param('isii', $id, $u, $j, $harga);
        $st->execute();
    }
    if ($seen) {
        $ulk = implode(',', array_keys($seen));
        $up = $conn->prepare("UPDATE samples SET ukuran_list=? WHERE id=?");
        $up->bind_param('si', $ulk, $id);
        $up->execute();
    }
    $min = $conn->query("SELECT MIN(harga) m FROM stock WHERE sample_id=$id AND harga>0")->fetch_assoc()['m'] ?? 0;
    if ($min) $conn->query("UPDATE samples SET harga=".(int)$min." WHERE id=$id");
    $msg = 'Tersimpan.';
    } catch (Throwable $e) { $msg = 'Gagal: '.$e->getMessage(); }
    $st0->execute();
    $s = $st0->get_result()->fetch_assoc();
}
$list = array_filter(array_map('normal_ukuran', explode(',', ($s['ukuran_list'] ?? ''))));
$q = $conn->prepare("SELECT ukuran,jumlah,harga FROM stock WHERE sample_id=?");
$q->bind_param('i', $id);
$q->execute();
$rs = $q->get_result();
$rows = [];
while ($r = $rs->fetch_assoc()) { $rows[$r['ukuran']] = $r; if (!in_array($r['ukuran'], $list, true)) $list[] = $r['ukuran']; }
if (!$list) $list = ukuran_default($s['kategori'] ?? 'kemeja');
$u = current_user();
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Stok+Harga - BUMK Store</title>
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
<style>body{font-family:'Inter',sans-serif}</style>
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
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Kelola: <?= h($s['nama']) ?></h2><a href="sampel.php" class="text-xs text-brand-600 font-semibold">← Kembali</a></header>
<main class="flex-1 overflow-y-auto p-6"><div class="bg-white p-6 rounded-2xl border shadow-sm max-w-3xl">
<div class="flex gap-4 items-center mb-4"><img src="<?= h($s['foto']) ?>" width="80" height="80" loading="lazy" decoding="async" class="w-20 h-20 rounded-xl object-cover bg-slate-100" onerror="this.src='https://placehold.co/100/e2e8f0/64748b?text=Foto'"><div><b>[<?= h(strtoupper($s['kategori'] ?? '')) ?>]</b> <?= h($s['warna_nama']) ?><br><span class="text-xs text-slate-500"><?= h($s['kode'] ?? '') ?></span></div></div>
<?php if($msg): ?><div class="mb-3 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= h($msg) ?></div><?php endif; ?>
<form method="post">
<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-xs text-slate-500"><th class="text-left py-2">Ukuran</th><th class="text-left">Stok</th><th class="text-left">Harga (Rp)</th><th></th></tr></thead>
<tbody id="tb">
<?php foreach ($list as $uu):
  $j = (int)($rows[$uu]['jumlah'] ?? 0); $hg = (int)($rows[$uu]['harga'] ?? $s['harga']);
?>
<tr><td class="py-1 pr-2"><input name="ukuran[]" value="<?= h($uu) ?>" maxlength="20" required class="w-full px-2 py-1.5 border rounded-lg text-sm"></td>
<td class="py-1 pr-2"><input type="number" name="stok[]" value="<?= $j ?>" min="0" class="w-24 px-2 py-1.5 border rounded-lg text-sm"></td>
<td class="py-1 pr-2"><input type="number" name="hargax[]" value="<?= $hg ?>" min="0" class="w-32 px-2 py-1.5 border rounded-lg text-sm"></td>
<td class="py-1"><button type="button" class="del px-2 py-1.5 text-rose-500">x</button></td></tr>
<?php endforeach; ?>
</tbody></table></div><br>
<div class="flex gap-2"><button type="button" id="add" class="px-4 py-2 border rounded-xl text-sm font-bold text-slate-600">+ Ukuran</button>
<button class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold">Simpan</button>
<a class="px-4 py-2 border rounded-xl text-sm font-bold text-slate-600" href="sampel.php">Kembali</a></div>
</form>
<p class="text-[11px] text-slate-400 mt-2">Hapus baris ukuran = stok ukuran itu tidak diubah (tidak terhapus dari DB). Untuk menghapus ukuran, kosongkan stok jadi 0.</p>
</div></main></div>
<script>
document.getElementById('add').onclick = () => {
  const tb = document.getElementById('tb');
  if (tb.rows.length >= 15) return;
  const tr = document.createElement('tr');
  tr.innerHTML = `<td class="py-1 pr-2"><input name="ukuran[]" maxlength="20" required class="w-full px-2 py-1.5 border rounded-lg text-sm"></td><td class="py-1 pr-2"><input type="number" name="stok[]" value="0" min="0" class="w-24 px-2 py-1.5 border rounded-lg text-sm"></td><td class="py-1 pr-2"><input type="number" name="hargax[]" value="0" min="0" class="w-32 px-2 py-1.5 border rounded-lg text-sm"></td><td class="py-1"><button type="button" class="del px-2 py-1.5 text-rose-500">x</button></td>`;
  tr.querySelector('.del').onclick = () => tr.remove();
  tb.appendChild(tr);
};
document.querySelectorAll('.del').forEach(b => b.onclick = () => b.closest('tr').remove());
</script><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
