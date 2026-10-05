 <?php

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Akses hanya untuk user yang sudah login dan role-nya admin.
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php?page=login');
    exit;
}


if (!isAdmin()) {
    header('Location: ' . BASE_URL . 'home.php?page=home');
    exit;
}

$db = getDB();


$tab = $_GET['tab'] ?? 'dashboard';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG (sementara): bantu lacak error "Invalid parameter number".
    // Cari log file ini setelah menjalankan aksi yang error: d:/xampp/htdocs/Tiket_Bioskop/admin_debug.log
    $__dbgFile = __DIR__ . '/admin_debug.log';
    $___dbg = function(string $msg) use ($__dbgFile) {
        @file_put_contents($__dbgFile, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
    };
    if (isset($_POST['save_settings'])) {
        foreach (['cinema_name','cinema_address','cinema_phone','cinema_email','booking_fee','max_seats_per_booking'] as $key) {
            if (isset($_POST[$key])) {
                $stmt = $db->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
                $stmt->execute([$key, (string)$_POST[$key]]);
            }
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Pengaturan berhasil disimpan!'];
        header('Location: index.php?page=admin&tab=settings');
        exit;
    }


    if (isset($_POST['add_movie'])) {
        $statusForm = (string)($_POST['status'] ?? '');
            // PHP 7.4 tidak mendukung match()
            $status = 'now_showing';
            if ($statusForm === 'now_showing') {
                $status = 'now_showing';
            } elseif ($statusForm === 'coming_soon' || $statusForm === 'coming_showing') {
                $status = 'coming_showing';
            }

        if ($status === '') {
            $status = 'now_showing';
        }

        $db->prepare("INSERT INTO movies (title, genre, duration, rating, synopsis, poster, director, cast, language, status) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                $_POST['title'],
                $_POST['genre'],
                (int)$_POST['duration'],
                $_POST['rating'],
                $_POST['synopsis'],
                trim((string)$_POST['poster']),
                $_POST['director'],
                $_POST['cast'],
                $_POST['language'],
                $status,
            ]);

        $_SESSION['flash'] = ['type'=>'success','msg'=>'Film berhasil ditambahkan!'];
        header('Location: index.php?page=admin&tab=movies');
        exit;
    }

    if (isset($_POST['update_movie'])) {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        if ($movie_id > 0) {
            // Jangan ubah kolom status lewat form edit agar kategori tidak jadi kosong
            $__dbgFile = __DIR__ . '/admin_debug.log';

            $statusForm = (string)($_POST['status'] ?? '');
            $status = 'now_showing';
            if ($statusForm === 'now_showing') {
                $status = 'now_showing';
            } elseif ($statusForm === 'coming_soon' || $statusForm === 'coming_showing') {
                $status = 'coming_showing';
            }

            $params = [
                $_POST['title'] ?? '',
                $_POST['genre'] ?? '',
                (int)($_POST['duration'] ?? 0),
                $_POST['rating'] ?? '',
                $_POST['synopsis'] ?? '',
                trim((string)($_POST['poster'] ?? '')),
                $_POST['director'] ?? '',
                $_POST['cast'] ?? '',
                $_POST['language'] ?? '',
                $status,
                $movie_id,
            ];

            // Debug tokens count (opsional)
            @file_put_contents(
                $__dbgFile,
                '[' . date('Y-m-d H:i:s') . '] update_movie: tokens=' . count($params) . PHP_EOL,
                FILE_APPEND
            );

            // Pastikan jumlah placeholder (?) sesuai dengan jumlah parameter ($params)
            $sql = "UPDATE movies SET title = ?, genre = ?, duration = ?, rating = ?, synopsis = ?, poster = ?, director = ?, cast = ?, language = ?, status = ? WHERE id = ?";
            // $params: [title, genre, duration, rating, synopsis, poster, director, cast, language, status, movie_id]
            $db->prepare($sql)->execute($params);

            $_SESSION['flash'] = ['type'=>'success','msg'=>'Film berhasil diupdate!'];
        } else {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'ID film tidak valid.'];
        }
        header('Location: index.php?page=admin&tab=movies');
        exit;
    }

    if (isset($_POST['delete_movie'])) {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        if ($movie_id > 0) {
            $db->prepare("DELETE FROM movies WHERE id = ?")->execute([$movie_id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Film berhasil dihapus!'];
        } else {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'ID film tidak valid.'];
        }
        header('Location: index.php?page=admin&tab=movies');
        exit;
    }


    if (isset($_POST['add_schedule'])) {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        $show_date = trim((string)($_POST['show_date'] ?? ''));
        $show_time = trim((string)($_POST['show_time'] ?? ''));

        if ($movie_id <= 0 || $show_date === '' || $show_time === '') {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'Movie, tanggal, dan jam harus diisi.'];
            header('Location: index.php?page=admin&tab=schedules');
            exit;
        }

// Kolom yang dipakai aplikasi
        // Skema kamu: schedules punya studio_id, jadi harus di-insert dari form.
        $inserted = false;
        try {
            $studio_id = (int)($_POST['studio_id'] ?? 0);
            $price = (int)($_POST['price'] ?? 0);

            if ($studio_id > 0) {
                $db->prepare("INSERT INTO schedules (movie_id, show_date, show_time, studio_id, price) VALUES (?,?,?,?,?)")
                    ->execute([$movie_id, $show_date, $show_time, $studio_id, $price]);
            } else {
                // jika admin kosongkan studio, insert tanpa studio_id (NULL)
                $db->prepare("INSERT INTO schedules (movie_id, show_date, show_time, price) VALUES (?,?,?,?)")
                    ->execute([$movie_id, $show_date, $show_time, $price]);
            }

            $inserted = true;
        } catch (Throwable $e) {
            $inserted = false;
        }

        if ($inserted) {
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Jadwal berhasil ditambahkan!'];
        } else {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'Gagal menambahkan jadwal. Periksa struktur tabel schedules.'];
        }

        header('Location: index.php?page=admin&tab=schedules');
        exit;
    }
}


// Stats
$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'bookings' => $db->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
    'revenue' => $db->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='confirmed'")->fetchColumn(),
    'movies' => $db->query("SELECT COUNT(*) FROM movies WHERE status='now_showing'")->fetchColumn(),
];

$recent_bookings = $db->query("SELECT b.*, u.name as user_name, m.title as movie_title, s.show_date, s.show_time FROM bookings b JOIN users u ON b.user_id=u.id JOIN schedules s ON b.schedule_id=s.id JOIN movies m ON s.movie_id=m.id ORDER BY b.created_at DESC LIMIT 10")->fetchAll();

$all_movies = $db->query("SELECT * FROM movies ORDER BY id DESC")->fetchAll();
$settings_data = [];
// Beberapa DB versi lama memakai kolom selain `key`/`value`, jadi ambil secara aman.
foreach ($db->query("SELECT * FROM settings" )->fetchAll() as $row) {
    // prefer kolom `key` dan `value`, fallback ke kolom alternatif.
    $k = $row['key'] ?? $row['setting_key'] ?? $row['name'] ?? null;
    $v = $row['value'] ?? $row['setting_value'] ?? $row['val'] ?? null;
    if ($k !== null) {
        $settings_data[$k] = $v;
    }
}
?>
<style>
.admin-page { padding: 2rem 0 4rem; }
.page-header { background: linear-gradient(135deg, #1a0a12, var(--bg2)); border-bottom: 1px solid var(--border); padding: 1.5rem 0; margin-bottom: 2rem; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; }
.admin-tabs { display: flex; gap: 0.25rem; background: var(--bg3); padding: 4px; border-radius: var(--radius); margin-bottom: 2rem; width: fit-content; }
.admin-tab { padding: 8px 18px; border-radius: 8px; text-decoration: none; color: var(--text2); font-size: 0.85rem; font-weight: 600; transition: all 0.2s; }
.admin-tab.active { background: var(--card); color: var(--text); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); padding: 1.25rem; }
.stat-val { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; line-height: 1; color: var(--accent); }
.stat-lbl { font-size: 0.8rem; color: var(--text2); margin-top: 4px; }
.stat-icon { font-size: 2rem; margin-bottom: 0.5rem; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: var(--bg3); padding: 10px 14px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text2); font-weight: 600; border-bottom: 1px solid var(--border); }
.data-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: 0.875rem; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: rgba(255,255,255,0.02); }
.table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); overflow: hidden; }
.panel { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); overflow: hidden; }
.panel-head { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.panel-head h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; }
.panel-body { padding: 1.5rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media(max-width:900px) { .stats-grid { grid-template-columns: 1fr 1fr; } .form-row { grid-template-columns: 1fr; } }
@media(max-width:600px) { .stats-grid { grid-template-columns: 1fr; } }
.status-badge { padding: 3px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
.sb-confirmed { background: rgba(46,204,113,0.15); color: var(--success); }
.sb-cancelled { background: rgba(231,76,60,0.15); color: var(--danger); }
.sb-pending { background: rgba(243,156,18,0.15); color: var(--warning); }
</style>

<div class="admin-page">
    <div class="page-header">
        <div class="container">
            <div class="page-title">⚙️ ADMIN <span style="color:var(--accent)">PANEL</span></div>
            <p style="color:var(--text2);font-size:0.875rem;margin-top:4px;">Kelola seluruh sistem CineMax</p>
        </div>
    </div>
    <div class="container">
        <div class="admin-tabs">
            <a href="?page=admin&tab=dashboard" class="admin-tab <?= $tab==='dashboard'?'active':'' ?>">📊 Dashboard</a>
            <a href="?page=admin&tab=bookings" class="admin-tab <?= $tab==='bookings'?'active':'' ?>">🎟️ Pemesanan</a>
            <a href="?page=admin&tab=movies" class="admin-tab <?= $tab==='movies'?'active':'' ?>">🎬 Film</a>

            <a href="?page=admin&tab=settings" class="admin-tab <?= $tab==='settings'?'active':'' ?>">⚙️ Bioskop</a>
            <a href="?page=admin&tab=schedules" class="admin-tab <?= $tab==='schedules'?'active':'' ?>">🗓️ Jadwal</a>
        </div>


        <?php if ($tab === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-val"><?= $stats['users'] ?></div><div class="stat-lbl">Total Pengguna</div></div>
            <div class="stat-card"><div class="stat-icon">🎟️</div><div class="stat-val"><?= $stats['bookings'] ?></div><div class="stat-lbl">Tiket Terjual</div></div>
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-val" style="font-size:1.8rem;"><?= formatPrice($stats['revenue']) ?></div><div class="stat-lbl">Total Pendapatan</div></div>
            <div class="stat-card"><div class="stat-icon">🎬</div><div class="stat-val"><?= $stats['movies'] ?></div><div class="stat-lbl">Film Tayang</div></div>
        </div>
        <div class="panel">
            <div class="panel-head"><h3>📋 PEMESANAN TERBARU</h3></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Kode</th><th>Pengguna</th><th>Film</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $b): ?>
                        <tr>
                            <td><code style="color:var(--accent);font-size:0.75rem;"><?= htmlspecialchars($b['booking_code']) ?></code></td>
                            <td><?= htmlspecialchars($b['user_name']) ?></td>
                            <td><?= htmlspecialchars($b['movie_title']) ?></td>
                            <td style="font-size:0.8rem;color:var(--text2);"><?= date('d M Y', strtotime($b['show_date'])) ?></td>
                            <td><?= formatPrice($b['total_price']) ?></td>
                            <td><span class="status-badge sb-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($tab === 'bookings'): ?>
        <?php
            $all_bookings = $db->query(
                "SELECT b.*, u.name as user_name, m.title as movie_title, s.show_date, s.show_time " .
                "FROM bookings b " .
                "JOIN users u ON b.user_id=u.id " .
                "JOIN schedules s ON b.schedule_id=s.id " .
                "JOIN movies m ON s.movie_id=m.id " .
                "ORDER BY b.created_at DESC LIMIT 50"
            )->fetchAll();
        ?>
        <div class="panel">
            <div class="panel-head"><h3>🎟️ SEMUA PEMESANAN</h3><span class="badge badge-blue"><?= count($all_bookings) ?>+ Records</span></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Kode</th><th>Pengguna</th><th>Film</th><th>Jadwal</th><th>Kursi</th><th>Total</th><th>Promo</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($all_bookings as $b): ?>
                            <?php $seats = json_decode($b['seats'], true) ?? []; ?>
                            <tr>
                                <td><code style="color:var(--accent);font-size:0.72rem;">#<?= htmlspecialchars($b['booking_code'] ?? '') ?></code></td>
                                <td><?= htmlspecialchars($b['user_name'] ?? '') ?></td>
                                <td style="max-width:150px;"><?= htmlspecialchars($b['movie_title'] ?? '') ?></td>
                                <td style="font-size:0.78rem;">
                                    <?= $b['show_date'] ? date('d/m/Y', strtotime($b['show_date'])) : '' ?>
                                    <?= $b['show_time'] ? date('H:i', strtotime($b['show_time'])) : '' ?>
                                </td>
                                <td style="font-size:0.78rem;">
                                    <?= implode(', ', array_slice($seats, 0, 3)) ?><?= count($seats) > 3 ? '...' : '' ?>
                                </td>
                                <td><?= formatPrice($b['total_price'] ?? 0) ?></td>
                                <td>-</td>

                                <td><span class="status-badge sb-<?= htmlspecialchars($b['status'] ?? '') ?>"><?= ucfirst($b['status'] ?? '') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <?php elseif ($tab === 'movies'): ?>
        <div class="grid-2 mb-4">
            <div class="panel">
                <div class="panel-head"><h3>➕ TAMBAH FILM</h3></div>
                <div class="panel-body">
                    <form method="POST">
                        <input type="hidden" name="add_movie" value="1">
                        <div class="form-group"><label class="form-label">Judul Film</label><input name="title" class="form-control" required placeholder="Judul film"></div>
                        <div class="form-row">
                            <div class="form-group"><label class="form-label">Genre</label><input name="genre" class="form-control" placeholder="Action/Drama"></div>
                            <div class="form-group"><label class="form-label">Durasi (menit)</label><input name="duration" type="number" class="form-control" value="120"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label class="form-label">Rating</label><select name="rating" class="form-control"><option>G</option><option>PG</option><option selected>PG-13</option><option>R</option></select></div>
                            <div class="form-group"><label class="form-label">Bahasa</label><select name="language" class="form-control"><option>Indonesia</option><option>Inggris</option><option>Jepang</option><option>Korea</option></select></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Link Poster (URL gambar)</label>
                            <input
                                name="poster"
                                class="form-control"
                                value=""
                                placeholder="Contoh: https://domain.com/poster.jpg"
                                required
                            >
                            <div style="font-size:0.75rem;color:var(--text2);margin-top:6px;opacity:.85;">
                                Harus berupa URL gambar (http:// atau https://).
                            </div>
                        </div>
                        <div class="form-group"><label class="form-label">Sutradara</label><input name="director" class="form-control" placeholder="Nama Sutradara"></div>
                        <div class="form-group"><label class="form-label">Pemeran</label><input name="cast" class="form-control" placeholder="Aktor, Aktris"></div>
                        <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="now_showing">Sedang Tayang</option><option value="coming_soon">Segera Hadir</option></select></div>
                        <div class="form-group"><label class="form-label">Sinopsis</label><textarea name="synopsis" class="form-control" rows="3" placeholder="Deskripsi film..."></textarea></div>
                        <button type="submit" class="btn btn-primary">➕ Tambah Film</button>
                    </form>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head"><h3>🎬 DAFTAR FILM (<?= count($all_movies) ?>)</h3></div>
                <div style="max-height:600px;overflow-y:auto;">
                    <table class="data-table">
                        <thead><tr><th>Film</th><th>Genre</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($all_movies as $m): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <span><?= htmlspecialchars($m['poster']) ?></span>
                                        <strong><?= htmlspecialchars($m['title']) ?></strong>
                                    </div>
                                </td>
                                <td style="font-size:0.8rem;color:var(--text2);"><?= htmlspecialchars($m['genre']) ?></td>
                                <td>
                                    <span class="badge <?= $m['status']==='now_showing'?'badge-green':'badge-gold' ?>"><?= $m['status']==='now_showing'?'Tayang':'Soon' ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="document.querySelectorAll('[id^=editMovieRow]').forEach(el=>{el.style.display='none'});document.getElementById('editMovieRow<?= (int)$m['id'] ?>').style.display='table-row';">✏️ Edit</button>


                                    <form method="POST" style="display:inline-block" onsubmit="return confirm('Hapus film ini?')">

                                        <input type="hidden" name="delete_movie" value="1">
                                        <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <tr style="display:none" id="editMovieRow<?= (int)$m['id'] ?>">
                                <td colspan="4">
                                    <div class="panel" style="border-radius:12px;">
                                        <div class="panel-body">
                                            <form method="POST" id="editMovieForm<?= (int)$m['id'] ?>" style="display:block">
                                                <input type="hidden" name="update_movie" value="1">
                                                <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                                                <div class="form-row">
                                                    <div class="form-group"><label class="form-label">Judul Film</label><input name="title" class="form-control" value="<?= htmlspecialchars($m['title'] ?? '') ?>" required></div>
                                                    <div class="form-group"><label class="form-label">Genre</label><input name="genre" class="form-control" value="<?= htmlspecialchars($m['genre'] ?? '') ?>"></div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group"><label class="form-label">Durasi (menit)</label><input name="duration" type="number" class="form-control" value="<?= (int)($m['duration'] ?? 0) ?>"></div>
                                                    <div class="form-group"><label class="form-label">Rating</label><input name="rating" class="form-control" value="<?= htmlspecialchars($m['rating'] ?? '') ?>"></div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group"><label class="form-label">Bahasa</label><input name="language" class="form-control" value="<?= htmlspecialchars($m['language'] ?? '') ?>"></div>
                                                    <input type="hidden" name="status" value="<?= htmlspecialchars($m['status'] ?? 'now_showing') ?>">
                                                </div>

                                                <div class="form-group"><label class="form-label">Link Poster (URL)</label><input name="poster" class="form-control" value="<?= htmlspecialchars($m['poster'] ?? '') ?>" required></div>
                                                <div class="form-group"><label class="form-label">Sutradara</label><input name="director" class="form-control" value="<?= htmlspecialchars($m['director'] ?? '') ?>"></div>
                                                <div class="form-group"><label class="form-label">Pemeran</label><input name="cast" class="form-control" value="<?= htmlspecialchars($m['cast'] ?? '') ?>"></div>
                                                <div class="form-group"><label class="form-label">Sinopsis</label><textarea name="synopsis" class="form-control" rows="3"><?= htmlspecialchars($m['synopsis'] ?? '') ?></textarea></div>
                                                <button type="submit" class="btn btn-primary">💾 Simpan</button>

                                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editMovieRow<?= (int)$m['id'] ?>').style.display='none'">Batal</button>

                                            </form>

                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'promos'): ?>
        <?php header('Location: index.php?page=admin&tab=dashboard'); exit; ?>

        <?php elseif ($tab === 'schedules'): ?>
        <?php
            // List schedules (join movies)
            $schedules = $db->query(
                "SELECT s.*, m.title as movie_title, m.poster as movie_poster, m.status as movie_status
                 FROM schedules s
                 JOIN movies m ON s.movie_id = m.id
                 ORDER BY s.show_date DESC, s.show_time ASC
                 LIMIT 200"
            )->fetchAll();
        ?>

        <div class="grid-2">
            <div class="panel">
                <div class="panel-head"><h3>➕ TAMBAH JADWAL</h3></div>
                <div class="panel-body">
                    <form method="POST">
                        <input type="hidden" name="add_schedule" value="1">

                        <div class="form-group"><label class="form-label">Film</label>
                            <select name="movie_id" class="form-control" required>
                                <option value="">-- Pilih Film --</option>
                                <?php foreach ($all_movies as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group"><label class="form-label">Tanggal</label><input name="show_date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                            <div class="form-group"><label class="form-label">Jam</label><input name="show_time" type="time" class="form-control" required value="<?= date('H:i') ?>"></div>
                        </div>

                        <!-- Optional fields (tanpa memaksa kolom di DB) -->
                        <div class="form-row">
                            <div class="form-group"><label class="form-label">Studio (opsional)</label>
                                <select name="studio_id" class="form-control">
                                    <option value="">-- Pilih Studio --</option>
                                    <?php
                                        // ambil data studio dari tabel studios (berbasis studio_id)
                                        try {
                                            $studioRows = $db->query("SELECT id, name FROM studios ORDER BY name ASC")->fetchAll();
                                        } catch (Throwable $e) {
                                            $studioRows = [];
                                        }
                                        foreach ($studioRows as $st):
                                    ?>
                                        <option value="<?= (int)$st['id'] ?>"><?= htmlspecialchars($st['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label">Harga (opsional)</label><input name="price" type="number" class="form-control" min="0" placeholder="50000"></div>
                        </div>


                        <button type="submit" class="btn btn-primary">➕ Tambah Jadwal</button>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h3>🗓️ DAFTAR JADWAL (<?= count($schedules) ?>)</h3></div>
                <div style="max-height:600px;overflow-y:auto;">
                    <table class="data-table">
                        <thead><tr><th>Film</th><th>Tanggal</th><th>Jam</th><th>Price</th></tr></thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                                <tr>
                                    <td style="max-width:220px;">
                                        <?= isset($s['movie_poster']) && $s['movie_poster'] ? htmlspecialchars($s['movie_poster']) . ' ' : '' ?>
                                        <?= htmlspecialchars($s['movie_title'] ?? '') ?>
                                    </td>
                                    <td><?= !empty($s['show_date']) ? date('d/m/Y', strtotime($s['show_date'])) : '-' ?></td>
                                    <td><?= !empty($s['show_time']) ? date('H:i', strtotime($s['show_time'])) : '-' ?></td>
                                    <td><?= isset($s['price']) ? formatPrice((int)$s['price']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($schedules)): ?>
                                <tr><td colspan="4" style="text-align:center;color:var(--text2);padding:24px 14px;">Belum ada jadwal.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'settings'): ?>
        <div class="panel">

            <div class="panel-head"><h3>⚙️ PENGATURAN SISTEM</h3></div>
            <div class="panel-body">
                <form method="POST">
                    <input type="hidden" name="save_settings" value="1">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Nama Bioskop</label><input name="cinema_name" class="form-control" value="<?= htmlspecialchars($settings_data['cinema_name']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Email Bioskop</label><input name="cinema_email" type="email" class="form-control" value="<?= htmlspecialchars($settings_data['cinema_email']??'') ?>"></div>
                    </div>
                    <div class="form-group"><label class="form-label">Alamat</label><input name="cinema_address" class="form-control" value="<?= htmlspecialchars($settings_data['cinema_address']??'') ?>"></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">No. Telepon</label><input name="cinema_phone" class="form-control" value="<?= htmlspecialchars($settings_data['cinema_phone']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Biaya Layanan (Rp)</label><input name="booking_fee" type="number" class="form-control" value="<?= $settings_data['booking_fee']??5000 ?>"></div>
                    </div>
                    <div class="form-group"><label class="form-label">Maks. Kursi per Pemesanan</label><input name="max_seats_per_booking" type="number" class="form-control" value="<?= $settings_data['max_seats_per_booking']??8 ?>"></div>
                    <button type="submit" class="btn btn-primary btn-lg">💾 Simpan Pengaturan</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>