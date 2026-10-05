<?php
require_once 'config.php';
requireLogin();

$booking_id = (int)($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'ID pembesanan tidak valid.'];
    header('Location: index.php?page=my_bookings');
    exit;
}

$db = getDB();

$sql = "
SELECT 
  b.*, 
  s.show_date, s.show_time,
  s.studio_id,
  m.title AS movie_title,
  m.poster AS movie_poster,
  m.genre,
  m.duration,
  st.name AS studio_name,
  u.name AS user_name
FROM bookings b
JOIN schedules s ON b.schedule_id = s.id
JOIN movies m ON s.movie_id = m.id
LEFT JOIN studios st ON s.studio_id = st.id
LEFT JOIN users u ON b.user_id = u.id
WHERE b.id = ? AND b.user_id = ?
LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Bukti pembelian tidak ditemukan.'];
    header('Location: index.php?page=my_bookings');
    exit;
}

$seats = json_decode($booking['seats'] ?? '[]', true) ?? [];
$studio = $booking['studio_name'] ?? ($booking['studio_id'] ? ('Studio #' . $booking['studio_id']) : '-');

function seats_to_line(array $seats): string {
    if (empty($seats)) return '-';
    $seats = array_values(array_filter($seats));
    sort($seats);
    return implode(', ', $seats);
}

$cinemaName = setting('cinema_name', 'CineMax');
$cinemaAddress = (string)setting('cinema_address', '');
$cinemaPhone = (string)setting('cinema_phone', '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bukti Pembelian <?= e($booking['booking_code'] ?? $booking_id) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500;display=swap" rel="stylesheet">
<style>
:root{
  --ink:#0b0b12;--ink2:#131320;--text:#eeeef8;--muted:#8888aa;--surface:#18182a;
  --surf2:#1f1f33;--surf3:#26263d;--border:rgba(255,255,255,.06);--border2:rgba(255,255,255,.11);
  --radius:10px;--radius2:18px;--accent:#e63946;--gold:#f5c542;--green:#2ec96b;--danger:#e74c3c;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Syne',sans-serif;background:var(--ink);color:var(--text);min-height:100vh}
.container{max-width:980px;margin:0 auto;padding:1.5rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius2);overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.35)}
.header{padding:1.5rem 1.5rem 1rem;border-bottom:1px solid var(--border);background:linear-gradient(135deg,#1a0a12,rgba(230,57,70,.12))}
.header h1{font-family:'Bebas Neue',sans-serif;letter-spacing:2px;font-size:2.4rem}
.badge{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:100px;border:1px solid rgba(245,197,66,.35);background:rgba(245,197,66,.12);font-weight:800;color:var(--gold);font-size:.82rem}
.content{padding:1.5rem}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:800px){.grid{grid-template-columns:1fr}}
.row{display:flex;justify-content:space-between;gap:1rem;padding:10px 0;border-bottom:1px dashed var(--border)}
.row:last-child{border-bottom:none}
.label{color:var(--muted);font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px}
.value{font-size:.95rem;font-weight:700}
.mono{font-family:'DM Mono',monospace}
.posterWrap{display:flex;gap:1rem;align-items:center}
.poster{width:84px;height:120px;border-radius:14px;object-fit:cover;border:1px solid var(--border2);background:var(--surf2)}
.seatBox{background:var(--surf2);border:1px solid var(--border2);border-radius:var(--radius);padding:12px 14px;margin-top:1rem}
.seatTitle{font-weight:900;color:var(--text);font-size:.85rem;margin-bottom:8px;text-transform:uppercase;letter-spacing:.6px}
.small{font-size:.85rem;color:var(--muted);line-height:1.6}
.footer{padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:var(--radius);text-decoration:none;border:1px solid var(--border2);background:transparent;color:var(--text);font-weight:800}
.btn:hover{border-color:rgba(230,57,70,.6);background:rgba(230,57,70,.08)}
.btnGold{border:none;background:linear-gradient(135deg,var(--gold),#ffd166);color:#1a0f00}
.btnGold:hover{box-shadow:0 8px 24px rgba(245,197,66,.25)}
</style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="header">
        <div class="mono" style="color:rgba(255,255,255,.85);font-weight:900;font-size:.78rem;">BUKTI PEMBELIAN</div>
<h1 style="margin-top:.35rem;">Terima Kasih</h1>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-top:0.6rem">
          <div>
            <div class="badge">🎟️ <?= e($cinemaName) ?></div>
            <div class="small" style="margin-top:.5rem">Kode Pembelian: <span class="mono" style="color:var(--gold);font-weight:900"><?= e($booking['booking_code'] ?? $booking_id) ?></span></div>
          </div>
          <div class="small" style="text-align:right">
            <div>Metode: <span style="color:var(--text);font-weight:900"><?= e(ucfirst((string)$booking['payment_method'])) ?></span></div>
            <div>Status: <span style="color:var(--text);font-weight:900"><?= e($booking['status'] ?? 'confirmed') ?></span></div>
          </div>
        </div>
      </div>

      <div class="content">
        <div class="posterWrap">
          <img class="poster" src="<?= htmlspecialchars((string)($booking['movie_poster'] ?? '')) ?>" alt="Poster" onerror="this.style.display='none'" />
          <div>
            <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:1px;line-height:1.1"><?= e($booking['movie_title'] ?? '-') ?></div>
            <div class="small" style="margin-top:0.35rem">
              📅 <?= date('d M Y', strtotime($booking['show_date'])) ?> • 🕐 <?= date('H:i', strtotime($booking['show_time'])) ?>
              <br>📍 <?= e($studio) ?>
            </div>
          </div>
        </div>

        <div class="grid" style="margin-top:1.2rem">
          <div>
            <div class="row"><div class="label">Pembeli</div><div class="value"><?= e($booking['user_name'] ?? ($_SESSION['user_id'] ?? '-')) ?></div></div>
            <div class="row"><div class="label">Jumlah Tiket</div><div class="value"><?= count($seats) ?> kursi</div></div>
            <div class="row"><div class="label">Kursi</div><div class="value mono"><?= e(seats_to_line($seats)) ?></div></div>
          </div>
          <div>
            <div class="row"><div class="label">Total Harga</div><div class="value" style="color:var(--gold)"><?= formatPrice($booking['total_price'] ?? 0) ?></div></div>
            <div class="row"><div class="label">Tanggal Dibeli</div><div class="value"><?= !empty($booking['created_at']) ? date('d M Y, H:i', strtotime($booking['created_at'])) : '-' ?></div></div>
          </div>
        </div>

        <div class="seatBox">
          <div class="seatTitle">Rincian Kursi</div>
          <div class="mono" style="font-weight:900;color:var(--text);"><?= e(seats_to_line($seats)) ?></div>
          <div class="small" style="margin-top:8px">Sesuai pemesanan pada jadwal di atas. Simpan kode pembelian untuk kebutuhan konfirmasi.</div>
        </div>
      </div>

      <div class="footer">
        <div class="small">
          <?= $cinemaAddress ? e($cinemaAddress) . '<br>' : '' ?>
          <?= $cinemaPhone ? '☎ ' . e($cinemaPhone) : '' ?>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <a class="btn" href="index.php?page=my_bookings">← Kembali</a>
          <button class="btn btnGold" onclick="window.print()">🖨️ Print</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

