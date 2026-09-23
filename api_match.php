<?php require 'config.php'; // api_match.php?hex=%23A52A2A&kategori=peci -> JSON kandidat (filter kategori biar tak tertukar)
header('Content-Type: application/json');
$hex = strtoupper(trim($_GET['hex'] ?? ''));
$kat = strtolower(trim($_GET['kategori'] ?? ''));
if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) { echo json_encode(['ok'=>false,'msg'=>'hex invalid']); exit; }
[$r1,$g1,$b1] = hex_to_rgb($hex);
$sql = "SELECT s.id,s.nama,s.foto,s.warna_hex,s.warna_nama,s.harga,s.kategori,s.ukuran_list,COALESCE((SELECT SUM(jumlah) FROM stock WHERE sample_id=s.id),0) total FROM samples s";
if ($kat !== '' && in_array($kat, kategori_list(), true)) {
    $sql .= " WHERE s.kategori='" . $conn->real_escape_string($kat) . "'";
}
$q = $conn->query($sql);
$out = [];
while ($r = $q->fetch_assoc()) {
    [$r2,$g2,$b2] = hex_to_rgb($r['warna_hex']);
    $dist = sqrt(($r1-$r2)**2 + ($g1-$g2)**2 + ($b1-$b2)**2); // 0..441.67
    $r['similarity'] = round(100 - ($dist/441.67*100), 1);
    // sertakan peta stok per ukuran utk dropdown dinamis
    $st = $conn->prepare("SELECT ukuran,jumlah,harga FROM stock WHERE sample_id=?");
    $st->bind_param('i', $r['id']);
    $st->execute();
    $rs = $st->get_result();
    $map = [];
    while ($x = $rs->fetch_assoc()) $map[$x['ukuran']] = ['jumlah'=>(int)$x['jumlah'],'harga'=>(int)$x['harga']];
    $r['stokmap'] = $map;
    $out[] = $r;
}
usort($out, fn($a,$b)=>$b['similarity']<=>$a['similarity']);
echo json_encode(['ok'=>true,'query'=>$hex,'data'=>array_slice($out,0,5)]);
