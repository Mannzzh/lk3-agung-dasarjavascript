<?php
require_once 'config.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Film tidak ditemukan.'];
    header('Location: ' . BASE_URL . '/index.php?page=movies');
    exit;
}


$sel_date = $_GET['date'] ?? date('Y-m-d');
$stmt2 = $db->prepare("SELECT s.*, 
    COALESCE(
        (
            SELECT SUM(COALESCE(JSON_LENGTH(b.seats),0))
            FROM bookings b 
            WHERE b.schedule_id = s.id AND b.status != 'cancelled'
        ),
        0
    ) AS booked_count
FROM schedules s 
WHERE s.movie_id = ? AND s.show_date = ? 
ORDER BY s.show_time");
$stmt2->execute([$id, $sel_date]);
$schedules = $stmt2->fetchAll();

$dates = [];
for ($i = 0; $i < 7; $i++) {
    $dates[] = date('Y-m-d', strtotime("+$i days"));
}
?>
<style>
.detail-page { padding-bottom: 4rem; }
.movie-hero {
    background: linear-gradient(to bottom, var(--bg2) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border); padding: 3rem 0 2rem;
}
.movie-hero-inner { display: grid; grid-template-columns: auto 1fr; gap: 2.5rem; align-items: start; }
.movie-poster-lg {
    width: 180px; aspect-ratio: 2/3;
    background: linear-gradient(135deg, var(--bg3), var(--card2));
    border-radius: var(--radius2); display: flex; align-items: center; justify-content: center;
    font-size: 6rem; border: 1px solid var(--border); flex-shrink: 0;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}
.movie-detail-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; letter-spacing: 2px; line-height: 1; margin-bottom: 1rem; }
.movie-tags { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
.movie-synopsis { color: var(--text2); line-height: 1.7; font-size: 0.95rem; max-width: 600px; margin-bottom: 1.25rem; }
.meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; margin-top: 1.25rem; }
.meta-item { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 0.75rem 1rem; }
.meta-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text2); margin-bottom: 4px; }
.meta-value { font-weight: 600; font-size: 0.9rem; }

.schedule-section { padding: 2rem 0; }
.date-tabs { display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
.date-tab {
    flex-shrink: 0; padding: 10px 18px; border-radius: var(--radius);
    background: var(--bg3); border: 1px solid var(--border2);
    text-decoration: none; color: var(--text2); text-align: center;
    transition: all 0.2s; min-width: 80px;
}
.date-tab:hover, .date-tab.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.date-day { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
.date-num { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; line-height: 1; display: block; }
.date-mon { font-size: 0.7rem; display: block; }
.schedule-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.sched-card {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2);
    padding: 1.25rem; transition: all 0.2s; cursor: pointer; text-decoration: none; color: inherit;
    display: block;
}
.sched-card:hover { border-color: var(--accent); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(230,57,70,0.15); }
.sched-card.disabled { opacity: 0.5; pointer-events: none; }
.sched-time { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--accent); line-height: 1; margin-bottom: 0.5rem; }
.sched-studio { font-size: 0.8rem; color: var(--text2); margin-bottom: 0.75rem; }
.sched-info { display: flex; justify-content: space-between; align-items: center; }
.sched-seats { font-size: 0.8rem; color: var(--text2); }
.sched-price { font-weight: 700; color: var(--gold); font-size: 0.9rem; }
.no-schedule { text-align: center; padding: 3rem; color: var(--text2); }
@media(max-width:768px) { .movie-hero-inner { grid-template-columns: 1fr; } .movie-poster-lg { width: 120px; } .movie-detail-title { font-size: 2rem; } }
</style>

<div class="detail-page">
    <div class="movie-hero">
        <div class="container">
            <div class="movie-hero-inner">
                <div class="movie-poster-lg">
                    <?php
                        $poster = trim((string)($movie['poster'] ?? ''));
                        $posterUrl = '';
                        if ($poster !== '' && preg_match('/^https?:\/\//i', $poster)) {
                            $posterUrl = $poster;
                        } elseif ($poster !== '' && preg_match('/\.(jpg|jpeg|png|gif)(\?.*)?$/i', $poster)) {
                            $posterUrl = $poster;

                            // jika hanya nama file lokal (tanpa path), cek di uploads
                            if (!preg_match('/^https?:\/\//i', $posterUrl)) {
                                $localPath = __DIR__ . '/uploads/' . basename($posterUrl);
                                if (!is_file($localPath)) {
                                    if (is_file(__DIR__ . '/uploads/' . $poster)) {
                                        $posterUrl = 'uploads/' . $poster;
                                    } else {
                                        $posterUrl = '';
                                    }
                                } else {
                                    $posterUrl = 'uploads/' . basename($posterUrl);
                                }
                            }
                        }
                    ?>
                    <?php if ($posterUrl): ?>
                        <img src="<?= htmlspecialchars($posterUrl) ?>" alt="Poster <?= htmlspecialchars($movie['title']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:var(--radius2);" onerror="this.style.display='none';document.getElementById('poster-fallback').style.display='flex';" />
                        <div id="poster-fallback" style="display:none;justify-content:center;align-items:center;width:100%;height:100%;font-size:1rem;color:var(--text2);opacity:.95;">
                            Poster URL tidak bisa dimuat.
                        </div>
                    <?php else: ?>
                        <span style="font-size:4.5rem;opacity:.95;">
                    <?php endif; ?>
                </div>
                <div>
                    <div class="movie-tags">
                        <span class="badge badge-red"><?= htmlspecialchars($movie['rating']) ?></span>
                        <span class="badge badge-blue"><?= htmlspecialchars($movie['language']) ?></span>
                        <?php if($movie['status']==='now_showing'): ?>
                            <span class="badge badge-green">Sedang Tayang</span>
                        <?php else: ?>
                            <span class="badge badge-gold">Segera Hadir</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="movie-detail-title"><?= htmlspecialchars($movie['title']) ?></h1>
                    <p class="movie-synopsis"><?= htmlspecialchars($movie['synopsis']) ?></p>
                    <div class="movie-tags">
                        <?php foreach(explode('/', $movie['genre']) as $g): ?>
                            <span class="badge badge-gray"><?= htmlspecialchars(trim($g)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="meta-grid">
                        <div class="meta-item"><div class="meta-label">Sutradara</div><div class="meta-value"><?= htmlspecialchars($movie['director']) ?></div></div>
                        <div class="meta-item"><div class="meta-label">Durasi</div><div class="meta-value">⏱ <?= $movie['duration'] ?> menit</div></div>
                        <div class="meta-item"><div class="meta-label">Rating</div><div class="meta-value">⭐ <?= htmlspecialchars($movie['rating']) ?></div></div>
                        <div class="meta-item"><div class="meta-label">Bahasa</div><div class="meta-value"><?= htmlspecialchars($movie['language']) ?></div></div>
                    </div>
                    <?php $movieCast = $movie['cast'] ?? ''; ?>
                    <?php if (!empty($movieCast)): ?>
                    <p style="margin-top:1rem;font-size:0.85rem;color:var(--text2);">🎭 <strong>Pemeran:</strong> <?= htmlspecialchars($movieCast) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($movie['status'] === 'now_showing'): ?>
    <div class="schedule-section container">
        <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;margin-bottom:1.25rem;">PILIH <span style="color:var(--accent)">JADWAL</span></h2>
        
        <div class="date-tabs">
            <?php foreach ($dates as $d): ?>
            <a href="index.php?page=movie_detail&id=<?= $id ?>&date=<?= $d ?>" class="date-tab <?= $d===$sel_date?'active':'' ?>">
                <span class="date-day"><?= date('D', strtotime($d)) ?></span>
                <span class="date-num"><?= date('d', strtotime($d)) ?></span>
                <span class="date-mon"><?= date('M', strtotime($d)) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($schedules)): ?>
            <div class="no-schedule">
                <p style="font-size:2rem;">📅</p>
                <p>Tidak ada jadwal pada tanggal ini.</p>
            </div>
        <?php else: ?>
            <div class="schedule-grid">
                <?php foreach ($schedules as $sched):
                    // seats_total di schedules belum tentu terisi, jadi fallback ke seats, lalu default 80
                    $seatsTotal = (int)($sched['seats_total'] ?? $sched['seats'] ?? 0);
                    if ($seatsTotal <= 0) {
                        $seatsTotal = 80;
                    }
                    $available = $seatsTotal - (int)($sched['booked_count'] ?? 0);
                    $is_full = $available <= 0;
                    $link = isLoggedIn() ? "index.php?page=booking&schedule_id={$sched['id']}" : "index.php?page=login";
                ?>
                <a href="<?= $link ?>" class="sched-card <?= $is_full?'disabled':'' ?>">
                    <div class="sched-time"><?= date('H:i', strtotime($sched['show_time'])) ?></div>
                    <?php $studio = $sched['studio'] ?? ($sched['cinema_studio'] ?? ($sched['studio_name'] ?? '')); ?>
                    <div class="sched-studio">📍 <?= htmlspecialchars($studio) ?></div>
                    <?php if (isset($sched['id'])): ?>

                    <?php endif; ?>
                    <div class="sched-info">
                        <span class="sched-seats">
                            <?php if ($is_full): ?>
                                <span style="color:var(--danger);">PENUH</span>
                            <?php else: ?>
                                💺 <?= $available ?> tersisa
                            <?php endif; ?>
                        </span>
                        <span class="sched-price"><?= formatPrice($sched['price']) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (!isLoggedIn()): ?>
                <div class="mt-3 flash flash-info" style="max-width:400px;">
                    ⚠️ Silakan <a href="index.php?page=login" style="color:var(--accent);font-weight:700;">masuk</a> atau <a href="index.php?page=register" style="color:var(--accent);font-weight:700;">daftar</a> untuk memesan tiket.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <div class="container" style="padding:3rem 0;text-align:center;">
            <p style="font-size:3rem;">🌟</p>
            <h3 style="font-family:'Bebas Neue',sans-serif;font-size:2rem;margin-bottom:0.5rem;">FILM INI SEGERA HADIR</h3>
            <p style="color:var(--text2);">Pantau terus untuk informasi jadwal tayang selanjutnya.</p>
            <a href="index.php?page=movies" class="btn btn-primary mt-3">← Kembali ke Daftar Film</a>
        </div>
    <?php endif; ?>
</div>