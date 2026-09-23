<?php require 'config.php';
require_login();
$msg = '';
// tambah kategori
if (($_POST['aksi'] ?? '') === 'tambah') {
    $nama = strtolower(trim($_POST['nama'] ?? ''));
    $nama = substr(preg_replace('/[^a-z ]/', '', $nama), 0, 20);
    $def = [];
    foreach (explode(',', ($_POST['ukuran_default'] ?? '')) as $u) {
        $u = normal_ukuran($u);
        if ($u !== '' && valid_ukuran($u) && !in_array($u, $def, true)) $def[] = $u;
        if (count($def) >= 15) break;
    }
    if ($nama === '' || !$def) $msg = 'Nama + minimal 1 ukuran wajib (huruf kecil, maks 20).';
    else {
        try {
        $st = $conn->prepare("INSERT INTO kategoris (nama,ukuran_default) VALUES (?,?)");
        $ulk = implode(',', $def);
        $st->bind_param('ss', $nama, $ulk);
        if ($st->execute()) $msg = "Kategori $nama ditambah. Langsung muncul di semua dropdown.";
        else $msg = 'Gagal (mungkin nama sudah ada).';
        } catch (Throwable $e) { $msg = 'Gagal: '.$e->getMessage(); }
    }
}
// edit ukuran bawaan
if (($_POST['aksi'] ?? '') === 'edit') {
    $nama = strtolower(trim($_POST['nama'] ?? ''));
    $def = [];
    foreach (explode(',', ($_POST['ukuran_default'] ?? '')) as $u) {
        $u = normal_ukuran($u);
        if ($u !== '' && valid_ukuran($u) && !in_array($u, $def, true)) $def[] = $u;
        if (count($def) >= 15) break;
    }
    if ($nama === '' || !$def) $msg = 'Data edit tidak valid.';
    else {
        try {
        $ulk = implode(',', $def);
        $st = $conn->prepare("UPDATE kategoris SET ukuran_default=? WHERE nama=?");
        $st->bind_param('ss', $ulk, $nama);
        $st->execute();
        $msg = "Ukuran bawaan $nama diperbarui (sampel lama tidak berubah).";
        } catch (Throwable $e) { $msg = 'Gagal: '.$e->getMessage(); }
    }
}
// hapus (hanya bila tidak dipakai sampel)
if (($_POST['aksi'] ?? '') === 'hapus') {
    $nama = strtolower(trim($_POST['nama'] ?? ''));
    try {
    $c = $conn->prepare("SELECT COUNT(*) c FROM samples WHERE kategori=?");
    $c->bind_param('s', $nama);
    $c->execute();
    $pakai = (int)$c->get_result()->fetch_assoc()['c'];
    if ($pakai > 0) $msg = "Tidak bisa hapus: $nama dipakai $pakai sampel.";
    else {
        $d = $conn->prepare("DELETE FROM kategoris WHERE nama=?");
        $d->bind_param('s', $nama);
        $d->execute();
        $msg = "Kategori $nama dihapus.";
    }
    } catch (Throwable $e) { $msg = 'Gagal: '.$e->getMessage(); }
}
$kat = $conn->query("SELECT k.nama,k.ukuran_default,(SELECT COUNT(*) FROM samples s WHERE s.kategori=k.nama) jml FROM kategoris k ORDER BY k.nama");
$u = current_user();
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kategori - BUMK Store</title>
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
<a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-warehouse w-5 text-center"></i>Kelola Stok</a>
<a href="laporan.php#lap" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>Laporan Penjualan</a>
<a href="scan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-camera w-5 text-center"></i>Scan + Stok</a></nav>
<div class="p-4 border-t border-slate-800 text-sm"><?= h($u['username']??'Admin') ?> | <a href="logout.php" class="text-rose-400">Keluar</a></div>
</aside>
<div id="navOverlay" onclick="toggleNav(false)" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
<div class="flex-1 flex flex-col h-full overflow-hidden">
<header class="bg-white border-b px-4 sm:px-6 py-3.5 flex items-center gap-2"><button onclick="toggleNav()" class="lg:hidden p-2 -ml-2 text-slate-600" aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button><h2 class="text-xl font-bold">Kelola Kategori</h2><a href="sampel.php" class="text-xs text-brand-600 font-semibold">← Kembali ke Katalog</a></header>
<main class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-6">
<div class="bg-white p-5 rounded-2xl border shadow-sm">
<h3 class="font-bold mb-3">Tambah Kategori Baru</h3>
<?php if($msg): ?><div class="mb-3 px-4 py-3 rounded-xl <?= str_starts_with($msg,'Gagal')||str_starts_with($msg,'Tidak')||str_starts_with($msg,'Nama')||str_starts_with($msg,'Data')?'bg-rose-50 border border-rose-200 text-rose-700':'bg-emerald-50 border border-emerald-200 text-emerald-700' ?> text-sm"><?= h($msg) ?></div><?php endif; ?>
<form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3">
<input type="hidden" name="aksi" value="tambah">
<div><label class="text-xs font-semibold text-slate-600">Nama (huruf kecil)</label><input name="nama" required maxlength="20" placeholder="cth: sarung" class="w-full px-3 py-2 border rounded-xl text-sm mt-1"></div>
<div><label class="text-xs font-semibold text-slate-600">Ukuran bawaan (pisah koma)</label><input name="ukuran_default" required placeholder="cth: ALL SIZE,JUMBO" class="w-full px-3 py-2 border rounded-xl text-sm mt-1"></div>
<div class="flex items-end"><button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold w-full"><i class="fa-solid fa-plus mr-1"></i>Tambah</button></div>
</form></div>
<div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
<div class="p-4 border-b"><h3 class="font-bold">Daftar Kategori</h3></div>
<div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b"><tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Ukuran bawaan</th><th class="px-5 py-3">Dipakai</th><th class="px-5 py-3">Aksi</th></tr></thead>
<tbody class="divide-y">
<?php while($r=$kat->fetch_assoc()): ?>
<tr class="hover:bg-slate-50"><td class="px-5 py-3 font-bold"><?= h(strtoupper($r['nama'])) ?></td>
<td class="px-5 py-3 text-xs text-slate-500"><?= h($r['ukuran_default']) ?></td>
<td class="px-5 py-3"><?= (int)$r['jml'] ?> sampel</td>
<td class="px-5 py-3"><div class="flex gap-2 flex-wrap">
<form method="post" class="flex gap-1">
<input type="hidden" name="aksi" value="edit">
<input type="hidden" name="nama" value="<?= h($r['nama']) ?>">
<input name="ukuran_default" value="<?= h($r['ukuran_default']) ?>" class="border rounded-lg px-2 py-1.5 text-xs w-48" required>
<button class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-bold">Simpan</button>
</form>
<form method="post" onsubmit="return confirm('Hapus kategori <?= h($r['nama']) ?>?')">
<input type="hidden" name="aksi" value="hapus">
<input type="hidden" name="nama" value="<?= h($r['nama']) ?>">
<button class="px-3 py-1.5 text-rose-500 hover:bg-rose-50 rounded-lg text-xs"><i class="fa-solid fa-trash-can"></i></button>
</form></div></td></tr>
<?php endwhile; ?>
</tbody></table></div>
<p class="p-4 text-[11px] text-slate-400">Edit ukuran bawaan hanya untuk sampel BARU. Sampel lama tidak berubah.</p>
</div>
</main></div><script>function toggleNav(force){const sb=document.getElementById('sidebar'),ov=document.getElementById('navOverlay');const show=typeof force==='boolean'?force:sb.classList.contains('-translate-x-full');sb.classList.toggle('-translate-x-full',!show);ov.classList.toggle('hidden',!show);}</script></body></html>
