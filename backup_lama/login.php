<?php require 'config.php';
if (current_user()) { header('Location: index.php'); exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $us = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';
    $st = $conn->prepare("SELECT * FROM users WHERE username=?");
    $st->bind_param('s', $us);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();
    if ($u && password_verify($pw, $u['password'])) {
        $_SESSION['user'] = ['id'=>$u['id'],'username'=>$u['username'],'nama'=>$u['nama']];
        header('Location: index.php'); exit;
    }
    $msg = 'Username / password salah. Default: admin / admin123';
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - BUMK Store</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="wrap" style="max-width:420px;margin-top:60px"><div class="card">
<h3>BUMK Store — Login</h3>
<?php if($msg): ?><div class="alert"><?= h($msg) ?></div><?php endif; ?>
<form method="post">
<label>Username</label><input name="username" required autofocus>
<label>Password</label><input name="password" type="password" required>
<button class="btn" style="width:100%">Masuk</button>
</form>
<p style="font-size:12px;color:#666;margin-top:8px">Default: admin / admin123 — segera ganti setelah login via tabel users.</p>
</div></div></body></html>
