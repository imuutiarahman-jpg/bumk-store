<?php require 'config.php';
// Hapus hanya via POST (cegah kehapus gara-gara klik link / crawler)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: sampel.php'); exit; }
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $st = $conn->prepare("SELECT foto FROM samples WHERE id=?");
    $st->bind_param('i', $id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $del = $conn->prepare("DELETE FROM samples WHERE id=?");
    $del->bind_param('i', $id);
    $del->execute();
    if ($r && !empty($r['foto']) && file_exists(__DIR__.'/'.$r['foto'])) @unlink(__DIR__.'/'.$r['foto']);
}
header('Location: sampel.php');
