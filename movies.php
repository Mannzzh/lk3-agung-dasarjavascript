<?php
require_once 'config.php';
$db = getDB();
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$genre = $_GET['genre'] ?? '';

$where = [];
$params = [];

if ($filter === 'now_showing') {
    $where[] = "status = 'now_showing'";
} elseif ($filter === 'coming_soon') {
    $where[] = "status = 'coming_showing'";
}

if ($search) {
    $where[] = "title LIKE ?";
    $params[] = "%$search%";
}
if ($genre) {
    $where[] = "genre LIKE ?";
    $params[] = "%$genre%";
}

$sql = "SELECT * FROM movies";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY status DESC, id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();

$genres_raw = $db->query("SELECT DISTINCT genre FROM movies ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
.movies-page { padding: 2rem 0 4rem; }
.page-hero {
    background: linear-gradient(135deg, var(--bg2), var(--bg));
    border-bottom: 1px solid var(--border);
    padding: 2.5rem 0;
    margin-bottom: 2rem;
}
.page-hero h1 { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; letter-spacing: 2px; }
.filter-bar {
    display: flex; gap: 0.75rem; flex-wrap: wrap;
    margin-bottom: 2rem; align-items: center;
}
.filter-btn {
    padding: 8px 18px; border-radius: 100px;
    background: var(--bg3); border: 1px solid var(--border2);
    color: var(--text2); cursor: pointer; font-size: 0.85rem; font-weight: 500;
    text-decoration: none; transition: all 0.2s;
}
.filter-btn.active, .filter-btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; }
.search-box {
    display: flex; align-items: center; gap: 0; margin-left: auto;
    background: var(--bg3); border: 1px solid var(--border2); border-radius: 100px;
    overflow: hidden;
}
.search-box input {
    background: none; border: none; outline: none; padding: 8px 16px;
    color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 0.875rem; width: 200px;
}
.search-box button {
    background: var(--accent); border: none; color: #fff;
    padding: 8px 16px; cursor: pointer; font-size: 0.875rem;
}
.movie-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1.25rem; }
.movie-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); overflow: hidden; transition: all 0.3s; text-decoration: none; color: inherit; display: block; }
.movie-card:hover { transform: translateY(-6px); border-color: var(--accent); box-shadow: 0 16px 40px rgba(230,57,70,0.2); }
.movie-poster { aspect-ratio: 2/3; display: flex; align-items: center; justify-content: center; font-size: 5rem; background: linear-gradient(135deg, var(--bg3), var(--card2)); position: relative; }
.movie-status-now { position: absolute; top: 10px; right: 10px; background: var(--accent); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }
.movie-status-soon { position: absolute; top: 10px; right: 10px; background: var(--gold2); color: #1a0f00; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }
.movie-rating-badge { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: var(--text); padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid var(--border2); }
.movie-info { padding: 1rem; }
.movie-title { font-weight: 700; font-size: 0.9rem; line-height: 1.3; margin-bottom: 4px; }
.movie-genre { font-size: 0.75rem; color: var(--text2); margin-bottom: 8px; }
.movie-footer { display: flex; justify-content: space-between; align-items: center; }
.movie-dur { font-size: 0.75rem; color: var(--text2); font-family: 'Space Mono', monospace; }
.empty-state { text-align: center; padding: 4rem 2rem; color: var(--text2); }
.empty-state .icon { font-size: 4rem; margin-bottom: 1rem; }
.genre-pills { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.genre-pill { padding: 5px 14px; border-radius: 100px; font-size: 0.8rem; font-weight: 500; text-decoration: none; transition: all 0.2s; background: var(--bg3); border: 1px solid var(--border); color: var(--text2); }
.genre-pill:hover, .genre-pill.active { background: var(--bg2); color: var(--text); border-color: var(--accent); }
</style>

<div class="movies-page">
    <div class="page-hero">
        <div class="container">
            <h1>🎬 JADWAL <span style="color:var(--accent)">FILM</span></h1>
            <p style="color:var(--text2);margin-top:0.5rem;">Temukan film favorit Anda dan pesan tiket sekarang</p>
        </div>
    </div>

    <div class="container">
        <div class="filter-bar">
            <a href="index.php?page=movies" class="filter-btn <?= $filter==='all'?'active':'' ?>">🎬 Semua</a>
            <a href="index.php?page=movies&filter=now_showing" class="filter-btn <?= $filter==='now_showing'?'active':'' ?>">🔴 Sedang Tayang</a>
            <a href="index.php?page=movies&filter=coming_soon" class="filter-btn <?= $filter==='coming_soon'?'active':'' ?>">🌟 Segera Hadir</a>
            <form class="search-box" method="GET" action="index.php">
                <input type="hidden" name="page" value="movies">
                <input type="text" name="q" placeholder="Cari film..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">🔍</button>
            </form>
        </div>

        <div class="genre-pills">
            <span style="font-size:0.8rem;color:var(--text2);align-self:center;">Genre:</span>
            <a href="index.php?page=movies&filter=<?= $filter ?>" class="genre-pill <?= !$genre?'active':'' ?>">Semua</a>
            <?php foreach ($genres_raw as $g): ?>
                <?php $parts = explode('/', $g); foreach($parts as $gp): $gp = trim($gp); ?>
                    <a href="index.php?page=movies&genre=<?= urlencode($gp) ?>&filter=<?= $filter ?>" class="genre-pill <?= $genre===$gp?'active':'' ?>"><?= htmlspecialchars($gp) ?></a>
                <?php endforeach; endforeach; ?>
        </div>

        <?php if (empty($movies)): ?>
            <div class="empty-state">
                <div class="icon">🎭</div>
                <h3>Film tidak ditemukan</h3>
                <p>Coba ubah filter atau kata kunci pencarian Anda</p>
                <a href="index.php?page=movies" class="btn btn-secondary mt-2">Reset Filter</a>
            </div>
        <?php else: ?>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                    <?php
                        $poster = trim((string)($movie['poster'] ?? ''));
                        $posterUrl = '';

                        // Jika poster adalah URL eksternal (http/https), langsung pakai.
                        // Selain itu, validasi berdasarkan ekstensi untuk path lokal.
                        if ($poster !== '' && preg_match('/^https?:\/\//i', $poster)) {
                            $posterUrl = $poster;
                        } elseif ($poster !== '' && preg_match('/\.(jpg|jpeg|png|gif)(\?.*)?$/i', $poster)) {
                            // dukung 2 kemungkinan: path lokal/url eksternal
                            $posterUrl = $poster;

                            // jika ternyata hanya nama file, cek dulu apakah file ada di uploads
                            if (!preg_match('/^https?:\/\//i', $posterUrl)) {
                                $localPath = __DIR__ . '/uploads/' . basename($posterUrl);
                                if (!is_file($localPath)) {
                                    // coba kalau DB menyimpan nama file saja
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

                    <a href="index.php?page=movie_detail&id=<?= $movie['id'] ?>" class="movie-card">
                        <div class="movie-poster">
                            <?php if ($posterUrl): ?>
                                <img src="<?= htmlspecialchars($posterUrl) ?>" alt="Poster <?= htmlspecialchars($movie['title']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;" />
                            <?php else: ?>
                                <span style="font-size:1.8rem;opacity:.9;">&mdash; <?= htmlspecialchars($movie['poster']) ?></span>
                            <?php endif; ?>

                            <span class="movie-rating-badge"><?= htmlspecialchars($movie['rating']) ?></span>
                            <?php if ($movie['status'] === 'now_showing'): ?>
                                <span class="movie-status-now">TAYANG</span>
                            <?php else: ?>
                                <span class="movie-status-soon">SOON</span>
                            <?php endif; ?>
                        </div>
                        <div class="movie-info">
                            <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                            <div class="movie-genre"><?= htmlspecialchars($movie['genre']) ?></div>
                            <div class="movie-footer">
                                <span class="movie-dur">⏱ <?= $movie['duration'] ?>m</span>
                                <span class="badge <?= $movie['status']==='now_showing'?'badge-red':'badge-gold' ?>">
                                    <?= htmlspecialchars($movie['language']) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="mt-3" style="color:var(--text2);font-size:0.85rem;">Menampilkan <?= count($movies) ?> film</p>
        <?php endif; ?>
    </div>
</div>

