<?php
require_once 'config.php';
requireLogin();
if (empty($_SESSION['uid']) && !empty($_SESSION['user_id'])) {
    $_SESSION['uid'] = $_SESSION['user_id'];
}
$db = getDB();

$status_filter = $_GET['status'] ?? 'all';
$sql = "SELECT b.*, s.show_date, s.show_time, m.title, m.poster, m.genre FROM bookings b JOIN schedules s ON b.schedule_id = s.id JOIN movies m ON s.movie_id = m.id WHERE b.user_id = ?";
$params = [$_SESSION['user_id']];
if ($status_filter !== 'all') { $sql .= " AND b.status = ?"; $params[] = $status_filter; }
$sql .= " ORDER BY b.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    $db->prepare("UPDATE bookings SET status = ? WHERE id = ? AND user_id = ?")->execute(['cancelled', $cancel_id, $_SESSION['user_id']]);
    addNotification($_SESSION['user_id'], '❌ Pemesanan Dibatalkan', "Pemesanan tiket telah dibatalkan. Refund akan diproses dalam 3-5 hari kerja.", 'warning');
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Pemesanan berhasil dibatalkan.'];
    header('Location: index.php?page=my_bookings');
    exit;
}
?>
<style>
.my-bookings { padding: 2rem 0 4rem; }
.page-header { background: var(--bg2); border-bottom: 1px solid var(--border); padding: 1.5rem 0; margin-bottom: 2rem; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 2px; }
.status-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.status-tab { padding: 8px 16px; border-radius: 100px; font-size: 0.85rem; font-weight: 500; text-decoration: none; color: var(--text2); background: var(--bg3); border: 1px solid var(--border2); transition: all 0.2s; }
.status-tab.active, .status-tab:hover { background: var(--accent); border-color: var(--accent); color: #fff; }
.booking-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); overflow: hidden; margin-bottom: 1rem; transition: all 0.2s; }
.booking-card:hover { border-color: var(--border2); }
.booking-inner { display: grid; grid-template-columns: auto 1fr auto; gap: 1.25rem; align-items: center; padding: 1.25rem; }
.booking-emoji { font-size: 3rem; background: var(--bg3); border-radius: 12px; width: 72px; height: 72px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.booking-title { font-weight: 700; font-size: 1rem; margin-bottom: 4px; }
.booking-meta { font-size: 0.82rem; color: var(--text2); line-height: 1.6; }
.booking-code { font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--accent); background: rgba(230,57,70,0.1); padding: 3px 10px; border-radius: 6px; display: inline-block; margin-top: 4px; }
.booking-right { text-align: right; flex-shrink: 0; }
.booking-total { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; color: var(--gold); line-height: 1; margin-bottom: 6px; }
.booking-seats-bar { background: var(--bg3); border-top: 1px solid var(--border); padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; }
.seats-pills { display: flex; gap: 4px; flex-wrap: wrap; }
.seat-pill { background: var(--bg2); border: 1px solid var(--border2); border-radius: 6px; padding: 3px 8px; font-family: 'Space Mono', monospace; font-size: 0.75rem; }
.empty-state { text-align: center; padding: 5rem 2rem; color: var(--text2); }
.status-confirmed { color: var(--success); }
.status-cancelled { color: var(--danger); }
.status-pending { color: var(--warning); }
.cancel-btn { background: none; border: 1px solid rgba(231,76,60,0.3); color: var(--danger); padding: 6px 14px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; margin-top: 8px; transition: all 0.2s; }
.cancel-btn:hover { background: rgba(231,76,60,0.12); }
@media(max-width:600px) { .booking-inner { grid-template-columns: 1fr; } .booking-right { text-align: left; } .booking-emoji { width: 56px; height: 56px; font-size: 2rem; } }
</style>

<div class="my-bookings">
    <div class="page-header">
        <div class="container">
            <div class="page-title">🎟️ TIKET <span style="color:var(--accent)">SAYA</span></div>
            <p style="color:var(--text2);margin-top:4px;font-size:0.9rem;">Riwayat dan status pemesanan tiket Anda</p>
        </div>
    </div>
    <div class="container">
        <div class="status-tabs">
            <a href="index.php?page=my_bookings" class="status-tab <?= $status_filter==='all'?'active':'' ?>">📋 Semua</a>
            <a href="index.php?page=my_bookings&status=confirmed" class="status-tab <?= $status_filter==='confirmed'?'active':'' ?>">✅ Dikonfirmasi</a>
            <a href="index.php?page=my_bookings&status=pending" class="status-tab <?= $status_filter==='pending'?'active':'' ?>">⏳ Menunggu</a>
            <a href="index.php?page=my_bookings&status=cancelled" class="status-tab <?= $status_filter==='cancelled'?'active':'' ?>">❌ Dibatalkan</a>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <div style="font-size:4rem;margin-bottom:1rem;">🎭</div>
                <h3 style="margin-bottom:0.5rem;">Belum Ada Tiket</h3>
                <p style="margin-bottom:1.5rem;">Anda belum memiliki riwayat pemesanan tiket.</p>
                <a href="index.php?page=movies" class="btn btn-primary">🎬 Pesan Tiket Sekarang</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $b):
                $seats = json_decode($b['seats'], true) ?? [];
                $is_future = strtotime($b['show_date']) >= strtotime('today');
$status_class = "status-{$b['status']}";
                $status_icons = ['confirmed'=>'✅','cancelled'=>'❌','pending'=>'⏳'];
                $status_labels = ['confirmed'=>'Dikonfirmasi','cancelled'=>'Dibatalkan','pending'=>'Menunggu Pembayaran'];
                // Sistem status "pending" diimplementasikan seperti memasukkan rating:
                // nilai pending ditampilkan sebagai bintang, bukan ikon status biasa.
                $pending_rating = isset($b['rating']) ? (float)$b['rating'] : null;
            ?>
            <div class="booking-card">
                <div class="booking-inner">
                    <div class="booking-emoji">
                        <img
                            src="<?= htmlspecialchars($b['poster']) ?>"
                            alt="Poster <?= htmlspecialchars($b['title']) ?>"
                            style="width:60px;height:60px;object-fit:cover;border-radius:12px;display:block;"
                        />
                    </div>

                    <div>
                        <div class="booking-title"><?= htmlspecialchars($b['title']) ?></div>
                        <div class="booking-meta">
                            📅 <?= date('d M Y', strtotime($b['show_date'])) ?> &nbsp;•&nbsp;
                            🕐 <?= date('H:i', strtotime($b['show_time'])) ?> &nbsp;•&nbsp;
                            <?php $studio = $b['studio'] ?? ($b['cinema_studio'] ?? ($b['studio_name'] ?? '-')); ?>
                            📍 <?= htmlspecialchars($studio) ?>

                            <?php if ($b['payment_method']): ?>
                                <br>💳 <?= ucfirst(htmlspecialchars($b['payment_method'])) ?>
                            <?php endif; ?>
                        </div>
                        <span class="booking-code"><?= htmlspecialchars($b['booking_code']) ?></span>
                    </div>
                    <div class="booking-right">
                        <div class="booking-total"><?= formatPrice($b['total_price']) ?></div>
                        <span class="<?= $status_class ?>">
                            <?php if (($b['status'] ?? '') === 'pending'): ?>
                                ⭐ <?= htmlspecialchars($b['rating'] ?? '0') ?>
                            <?php else: ?>
                                <?= ($status_icons[$b['status']] ?? '❓') . ' ' . ($status_labels[$b['status']] ?? $b['status']) ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($b['discount'] > 0): ?>
                            <div style="font-size:0.75rem;color:var(--success);margin-top:4px;">💰 Hemat <?= formatPrice($b['discount']) ?></div>
                        <?php endif; ?>
                        <?php if ($b['status'] === 'confirmed' && $is_future): ?>
                            <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                <input type="hidden" name="cancel_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="cancel-btn">❌ Batalkan</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="booking-seats-bar">
                    <div class="seats-pills">
                        <span style="color:var(--text2);margin-right:4px;">💺 Kursi:</span>
                        <?php foreach (array_slice($seats, 0, 10) as $seat): ?>
                            <span class="seat-pill"><?= htmlspecialchars($seat) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($seats) > 10): ?><span style="color:var(--text2);font-size:0.75rem;">+<?= count($seats)-10 ?> lagi</span><?php endif; ?>
                    </div>
                    <span style="font-size:0.75rem;color:var(--text2);">
                        Dibeli: <?= date('d M Y, H:i', strtotime($b['created_at'])) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>