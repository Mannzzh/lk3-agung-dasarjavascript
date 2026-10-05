<?php
require_once 'config.php';

// pastikan schema status sesuai (now_showing / coming_soon)
$pdo = getDB();


$stmtNow = $pdo->query("SELECT * FROM movies WHERE status = 'now_showing' ORDER BY id LIMIT 6");
$now_showing = $stmtNow ? $stmtNow->fetchAll() : [];

$stmtComing = $pdo->query("SELECT * FROM movies WHERE status = 'coming_showing' ORDER BY id LIMIT 4");
$coming_soon = $stmtComing ? $stmtComing->fetchAll() : [];

?>
<style>
.hero {
    position: relative; min-height: 520px;
    background: linear-gradient(135deg, #0a0a0f 0%, #220d18 50%, #0a0a1f 100%);
    display: flex; align-items: center; overflow: hidden;
    padding: 4rem 0;
}
.hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(230,57,70,0.12) 0%, transparent 70%),
                radial-gradient(ellipse at 30% 80%, rgba(52,73,235,0.08) 0%, transparent 60%);
}
.hero-grid {
    position: absolute; inset: 0; opacity: 0.04;
    background-image: linear-gradient(var(--border2) 1px, transparent 1px),
                      linear-gradient(90deg, var(--border2) 1px, transparent 1px);
    background-size: 60px 60px;
}
.hero-content { position: relative; z-index: 1; }
.hero-tag {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(230,57,70,0.15); border: 1px solid rgba(230,57,70,0.3);
    color: var(--accent); padding: 6px 14px; border-radius: 100px;
    font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 1.5rem;
}
.hero h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(3rem, 8vw, 6rem); line-height: 0.95;
    margin-bottom: 1.5rem; letter-spacing: 2px;
}
.hero h1 .line2 { color: var(--accent); }
.hero p { font-size: 1.1rem; color: var(--text2); max-width: 520px; line-height: 1.6; margin-bottom: 2rem; }
.hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
.hero-stats {
    display: flex; gap: 2.5rem; margin-top: 3rem;
    padding-top: 2rem; border-top: 1px solid var(--border);
}
.stat-item { display: flex; flex-direction: column; }
.stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: var(--accent); line-height: 1; }
.stat-lbl { font-size: 0.8rem; color: var(--text2); text-transform: uppercase; letter-spacing: 1px; }

.section { padding: 4rem 0; }
.section-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 2rem; }
.section-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 1px; }
.section-title span { color: var(--accent); }

.movie-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1.25rem; }
.movie-card {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2);
    overflow: hidden; transition: all 0.3s; cursor: pointer; text-decoration: none; color: inherit;
    display: block;
}
.movie-card:hover { transform: translateY(-8px); border-color: var(--accent); box-shadow: 0 20px 40px rgba(230,57,70,0.2); }
.movie-poster {
    aspect-ratio: 2/3; display: flex; align-items: center; justify-content: center;
    font-size: 4rem; position: relative; overflow: hidden;
    background: linear-gradient(135deg, var(--bg3), var(--card2));
}
.movie-poster::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 60%;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
}
.movie-badge-rating {
    position: absolute; top: 10px; left: 10px; z-index: 1;
    background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
    color: var(--text); padding: 3px 8px; border-radius: 6px;
    font-size: 0.7rem; font-weight: 700; border: 1px solid var(--border2);
}
.movie-now-badge {
    position: absolute; top: 10px; right: 10px; z-index: 1;
    background: var(--accent); color: #fff;
    padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 700;
}
.movie-info { padding: 1rem; }
.movie-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 4px; line-height: 1.3; }
.movie-genre { font-size: 0.75rem; color: var(--text2); margin-bottom: 8px; }
.movie-meta { display: flex; justify-content: space-between; align-items: center; }
.movie-duration { font-size: 0.75rem; color: var(--text2); font-family: 'Space Mono', monospace; }

.coming-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.25rem; }
.coming-card {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2);
    overflow: hidden; transition: all 0.3s; display: flex; align-items: center; gap: 1.25rem;
    padding: 1.25rem; text-decoration: none; color: inherit;
}
.coming-card:hover { border-color: var(--gold); box-shadow: 0 8px 30px rgba(255,215,0,0.1); }
.coming-emoji { font-size: 3rem; flex-shrink: 0; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; background: var(--bg3); border-radius: 12px; }
.coming-info h4 { font-weight: 700; margin-bottom: 4px; font-size: 0.95rem; }
.coming-info p { font-size: 0.8rem; color: var(--text2); }

.promo-banner {
    background: linear-gradient(135deg, rgba(230,57,70,0.1), rgba(255,107,107,0.05));
    border: 1px solid rgba(230,57,70,0.2); border-radius: var(--radius2);
    padding: 2.5rem; text-align: center; position: relative; overflow: hidden;
}
.promo-banner::before {
    content: '🎟️'; position: absolute; right: 2rem; top: 50%; transform: translateY(-50%);
    font-size: 8rem; opacity: 0.06;
}
</style>

<section class="hero">
    <div class="hero-grid"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-tag">🎬 Pengalaman Sinema Terbaik</div>
            <h1>
                NIKMATI<br>
                <span class="line2">FILM</span><br>
                FAVORITMU
            </h1>
            <p>Pesan tiket bioskop dengan mudah, cepat, dan aman. Pilih kursi terbaik untuk pengalaman sinema yang tak terlupakan.</p>
            <div class="hero-actions">
                <a href="index.php?page=movies" class="btn btn-primary btn-lg">🎥 Lihat Jadwal Film</a>
                <?php if (!isLoggedIn()): ?>
                    <a href="index.php?page=register" class="btn btn-secondary btn-lg">Daftar Gratis</a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="stat-item"><span class="stat-num">50+</span><span class="stat-lbl">Film Tayang</span></div>
                <div class="stat-item"><span class="stat-num">8</span><span class="stat-lbl">Studio Premium</span></div>
                <div class="stat-item"><span class="stat-num">10K+</span><span class="stat-lbl">Penonton Puas</span></div>
            </div>
        </div>
    </div>
</section>

<section class="container" style="padding-top:2rem;">
<div class="promo-banner">
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;margin-bottom:0.5rem;">Pesan Tiket Sekarang</h3>
        <p style="color:var(--text2);margin-bottom:1.25rem;">Pilih jadwal favorit Anda dan nikmati pengalaman sinema terbaik.</p>
        <a href="index.php?page=movies" class="btn btn-gold">Pesan Sekarang →</a>
    </div>
</section>

<!-- NOW SHOWING -->
<section class="section container">
    <div class="section-header">
        <h2 class="section-title">SEDANG <span>TAYANG</span></h2>
        <a href="index.php?page=movies" class="btn btn-secondary btn-sm">Lihat Semua →</a>
    </div>
    <div class="movie-grid">
        <?php foreach ($now_showing as $movie): ?>
        <a href="index.php?page=movie_detail&id=<?= $movie['id'] ?>" class="movie-card">
            <div class="movie-poster">
                <?php
                    $poster = trim((string)($movie['poster'] ?? ''));
                    $posterUrl = '';

                    if ($poster !== '' && preg_match('/^https?:\/\//i', $poster)) {
                        // URL eksternal dari web (langsung pakai sebagai src)
                        $posterUrl = $poster;
                    } elseif ($poster !== '' && preg_match('/\.(jpg|jpeg|png|gif)(\?.*)?$/i', $poster)) {
                        // File lokal (atau path relatif) - validasi berdasarkan ekstensi
                        $posterUrl = $poster;

                        // jika path lokal/nama file saja, pastikan ada di uploads
                        if (!preg_match('/^https?:\/\//i', $posterUrl)) {
                            $localCandidate = __DIR__ . '/uploads/' . basename($posterUrl);
                            if (is_file($localCandidate)) {
                                $posterUrl = 'uploads/' . basename($posterUrl);
                            } else {
                                $localCandidate2 = __DIR__ . '/uploads/' . $poster;
                                if (is_file($localCandidate2)) {
                                    $posterUrl = 'uploads/' . $poster;
                                } else {
                                    $posterUrl = '';
                                }
                            }
                        }
                    }
                ?>

                <?php if ($posterUrl): ?>
                    <img src="<?= htmlspecialchars($posterUrl) ?>" alt="Poster <?= htmlspecialchars($movie['title'] ?? '') ?>" style="width:100%;height:100%;object-fit:cover;display:block;" />
                <?php else: ?>
                    <?= htmlspecialchars($movie['poster']) ?>
                <?php endif; ?>

                <span class="movie-badge-rating"><?= htmlspecialchars($movie['rating']) ?></span>
                <span class="movie-now-badge">TAYANG</span>
            </div>
            <div class="movie-info">
                <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                <div class="movie-genre"><?= htmlspecialchars($movie['genre']) ?></div>
                <div class="movie-meta">
                    <span class="movie-duration">⏱ <?= $movie['duration'] ?> min</span>
                    <span class="badge badge-red"><?= htmlspecialchars($movie['language']) ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container" style="padding-top:0;">
    <div class="section-header">
        <h2 class="section-title">SEGERA <span>HADIR</span></h2>
    </div>
    <div class="coming-grid">
        <?php foreach ($coming_soon as $movie): ?>
        <a href="index.php?page=movie_detail&id=<?= $movie['id'] ?>" class="coming-card">
            <?php
                $poster = trim((string)($movie['poster'] ?? ''));
                $posterUrl = '';
                if ($poster !== '' && preg_match('/^https?:\/\//i', $poster)) {
                    $posterUrl = $poster;
                } elseif ($poster !== '' && preg_match('/\.(jpg|jpeg|png|gif)(\?.*)?$/i', $poster)) {
                    $posterUrl = $poster;
                    if (!preg_match('/^https?:\/\//i', $posterUrl)) {
                        if (is_file(__DIR__ . '/uploads/' . basename($posterUrl))) {
                            $posterUrl = 'uploads/' . basename($posterUrl);
                        } else {
                            if (is_file(__DIR__ . '/uploads/' . $poster)) {
                                $posterUrl = 'uploads/' . $poster;
                            } else {
                                $posterUrl = '';
                            }
                        }
                    }
                }
            ?>
            <div class="coming-emoji">
                <?php if ($posterUrl): ?>
                    <img src="<?= htmlspecialchars($posterUrl) ?>" alt="Poster <?= htmlspecialchars($movie['title'] ?? '') ?>" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:12px;" />
                <?php else: ?>
                    <?= htmlspecialchars($movie['poster']) ?>
                <?php endif; ?>
            </div>
            <div class="coming-info">
                <h4><?= htmlspecialchars($movie['title']) ?></h4>
                <p><?= htmlspecialchars($movie['genre']) ?> • <?= $movie['duration'] ?> min</p>
                <span class="badge badge-gold mt-1">Coming Soon</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>