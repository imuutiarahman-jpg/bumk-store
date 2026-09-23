<?php require 'config.php';
if (current_user()) { header('Location: index.php'); exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $us = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';
    try {
        $st = $conn->prepare("SELECT * FROM users WHERE username=?");
        $st->bind_param('s', $us);
        $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($pw, $u['password'])) {
            $_SESSION['user'] = ['id'=>$u['id'],'username'=>$u['username'],'nama'=>$u['nama']];
            header('Location: index.php'); exit;
        }
        $msg = 'Username / password salah. Default: admin / admin123';
    } catch (Throwable $e) { $msg = 'DB error: '.$e->getMessage(); }
}
?>
<!DOCTYPE html><html lang="id" class="h-full bg-slate-50"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - BUMK Store</title>
<link rel="icon" href="assets/logo.jpg">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#f0f9ff',100:'#e0f2fe',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985'}}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:'Inter',sans-serif}</style>
</head><body class="h-full flex min-h-screen">
<div class="hidden lg:flex w-1/2 bg-slate-900 text-white flex-col justify-between p-10 relative overflow-hidden">
<div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-500/20 rounded-full blur-3xl"></div>
<div class="absolute -bottom-24 -left-24 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl"></div>
<div class="relative flex items-center gap-3"><img src="assets/logo.jpg" class="w-11 h-11 rounded-2xl object-contain bg-white p-1 shadow-lg" alt="BUMK"><div><h1 class="font-bold text-xl">BUMK Store</h1><p class="text-xs text-slate-400">POS & Penjualan Modern</p></div></div>
<div class="relative"><h2 class="text-3xl font-bold leading-tight">Kelola toko<br>lebih cepat & rapi.</h2><p class="text-slate-400 text-sm mt-3">Dashboard, Kasir POS, Katalog foto database, Stok per ukuran, Laporan omset — tanpa PPN.</p>
<div class="flex gap-3 mt-6 text-xs"><span class="px-3 py-1.5 bg-white/10 rounded-lg"><i class="fa-solid fa-box mr-1 text-brand-400"></i>18+ Model</span><span class="px-3 py-1.5 bg-white/10 rounded-lg"><i class="fa-solid fa-calculator mr-1 text-emerald-400"></i>Kasir Cepat</span><span class="px-3 py-1.5 bg-white/10 rounded-lg"><i class="fa-solid fa-chart-pie mr-1 text-amber-400"></i>Laporan</span></div></div>
<div class="relative text-xs text-slate-500">© 2026 BUMK Store • Data aman di MySQL</div>
</div>
<div class="flex-1 flex items-center justify-center p-6 bg-slate-50">
<div class="w-full max-w-md bg-white rounded-3xl border shadow-xl p-8">
<div class="lg:hidden flex items-center gap-3 mb-6"><img src="assets/logo.jpg" class="w-10 h-10 rounded-xl object-contain bg-white p-1" alt="BUMK"><b>BUMK Store</b></div>
<h3 class="text-2xl font-bold">Selamat datang 👋</h3><p class="text-sm text-slate-500 mb-6">Masuk untuk buka Dashboard modern.</p>
<?php if($msg): ?><div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= h($msg) ?></div><?php endif; ?>
<form method="post" class="space-y-4">
<div><label class="text-xs font-semibold text-slate-600">Username</label><div class="relative mt-1"><i class="fa-solid fa-user absolute left-3.5 top-3.5 text-slate-400 text-sm"></i><input name="username" required autofocus placeholder="admin" class="w-full pl-10 pr-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></div></div>
<div><label class="text-xs font-semibold text-slate-600">Password</label><div class="relative mt-1"><i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-slate-400 text-sm"></i><input id="pw" name="password" type="password" required placeholder="••••••••" class="w-full pl-10 pr-11 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"><button type="button" onclick="const i=document.getElementById('pw');i.type=i.type==='password'?'text':'password'" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"><i class="fa-solid fa-eye"></i></button></div></div>
<button class="w-full py-3 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl shadow-md shadow-brand-500/20 transition"><i class="fa-solid fa-right-to-bracket mr-1"></i>Masuk Dashboard</button>
</form>
<p class="text-[11px] text-slate-400 mt-4 text-center">Default: <b>admin / admin123</b> — ganti via tabel users.</p>
</div>
</div></body></html>
