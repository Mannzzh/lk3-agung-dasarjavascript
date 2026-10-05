<?php
require_once 'config.php';
requireLogin();
$db = getDB();


$tab = $_GET['tab'] ?? 'profile';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'profile') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (strlen($name) < 3) {
        $error = 'Nama minimal 3 karakter.';
    } else {
        $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")
            ->execute([$name, $phone, $_SESSION['user_id']]);
        $_SESSION['name'] = $name;
        $success = 'Profil berhasil diperbarui!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'password') {
    $curr = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    $us = $db->prepare("SELECT password FROM users WHERE id = ?");
    $us->execute([$_SESSION['user_id']]);
    $userRow = $us->fetch();

    if (!$userRow || !password_verify($curr, $userRow['password'])) {
        $error = 'Password saat ini salah.';
    } elseif (strlen($new) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($new !== $conf) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        $success = 'Password berhasil diubah!';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'preferences') {
    $success = 'Preferensi berhasil disimpan!';
}

$ustmt = $db->prepare("SELECT * FROM users WHERE id = ?"); $ustmt->execute([$_SESSION['user_id']]); $user = $ustmt->fetch();
$booking_count = $db->prepare("SELECT COUNT(*) as c FROM bookings WHERE user_id = ? AND status='confirmed'")->execute([$_SESSION['user_id']]) ? 0 : 0;
$bc = $db->prepare("SELECT COUNT(*) as c FROM bookings WHERE user_id = ? AND status='confirmed'"); $bc->execute([$_SESSION['user_id']]); $booking_count = $bc->fetch()['c'];
?>
<style>
.settings-page { padding: 2rem 0 4rem; }
.page-header { background: var(--bg2); border-bottom: 1px solid var(--border); padding: 1.5rem 0; margin-bottom: 2rem; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 2px; }
.settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 2rem; }
.settings-nav { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); padding: 1rem; height: fit-content; position: sticky; top: 80px; }
.settings-nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: var(--radius); cursor: pointer; text-decoration: none; color: var(--text2); font-size: 0.9rem; transition: all 0.2s; margin-bottom: 2px; }
.settings-nav-item:hover, .settings-nav-item.active { color: var(--text); background: var(--bg3); }
.settings-nav-item.active { border-left: 3px solid var(--accent); padding-left: 11px; color: var(--accent); }
.user-avatar { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent2)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 auto 1rem; }
.settings-panel { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); overflow: hidden; }
.panel-header { padding: 1.5rem; border-bottom: 1px solid var(--border); }
.panel-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; }
.panel-body { padding: 1.5rem; }
.stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-box { background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; text-align: center; }
.stat-box .val { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: var(--accent); }
.stat-box .lbl { font-size: 0.75rem; color: var(--text2); }
.pref-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border); }
.pref-item:last-child { border-bottom: none; }
.pref-left h4 { font-size: 0.9rem; font-weight: 600; margin-bottom: 4px; }
.pref-left p { font-size: 0.8rem; color: var(--text2); }
.toggle { width: 48px; height: 26px; background: var(--bg3); border-radius: 13px; position: relative; cursor: pointer; transition: all 0.2s; border: 1px solid var(--border2); }
.toggle.on { background: var(--success); border-color: var(--success); }
.toggle::after { content: ''; position: absolute; width: 20px; height: 20px; background: #fff; border-radius: 50%; top: 2px; left: 2px; transition: all 0.2s; }
.toggle.on::after { left: 24px; }
.success-msg { background: rgba(46,204,113,0.12); border: 1px solid rgba(46,204,113,0.3); color: var(--success); padding: 10px 14px; border-radius: var(--radius); font-size: 0.875rem; margin-bottom: 1.25rem; }
.error-msg { background: rgba(231,76,60,0.12); border: 1px solid rgba(231,76,60,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius); font-size: 0.875rem; margin-bottom: 1.25rem; }
@media(max-width:768px) { .settings-layout { grid-template-columns: 1fr; } .settings-nav { position: static; } }
</style>

<div class="settings-page">
    <div class="page-header">
        <div class="container">
            <div class="page-title">⚙️ PENGATURAN</div>
            <p style="color:var(--text2);margin-top:4px;font-size:0.9rem;">Kelola profil dan preferensi akun Anda</p>
        </div>
    </div>
    <div class="container">
        <div class="settings-layout">
            <!-- Sidebar Nav -->
            <div class="settings-nav">
                <div style="text-align:center;padding:1rem 0;border-bottom:1px solid var(--border);margin-bottom:1rem;">
                    <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                    <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--text2);"><?= htmlspecialchars($user['email']) ?></div>
                    <?php if ($user['role']==='admin'): ?><span class="badge badge-gold mt-1">Admin</span><?php endif; ?>
                </div>
                <a href="?page=settings&tab=profile" class="settings-nav-item <?= $tab==='profile'?'active':'' ?>">👤 Profil Saya</a>
                <a href="?page=settings&tab=password" class="settings-nav-item <?= $tab==='password'?'active':'' ?>">🔒 Ubah Password</a>
                <a href="?page=settings&tab=preferences" class="settings-nav-item <?= $tab==='preferences'?'active':'' ?>">🎨 Preferensi</a>
                <div style="border-top:1px solid var(--border);margin-top:1rem;padding-top:1rem;">
                    <a href="?page=my_bookings" class="settings-nav-item">🎟️ Tiket Saya</a>
                    <a href="?page=notifications" class="settings-nav-item">🔔 Notifikasi</a>
                    <a href="?page=logout" class="settings-nav-item" style="color:var(--danger);">🚪 Keluar</a>
                </div>
            </div>

            <div>
                <?php if ($success): ?><div class="success-msg">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

                <?php if ($tab === 'profile'): ?>
                <div class="settings-panel">
                    <div class="panel-header">
                        <div class="panel-title">PROFIL SAYA</div>
                        <p style="color:var(--text2);font-size:0.85rem;margin-top:4px;">Perbarui informasi profil Anda</p>
                    </div>
                    <div class="panel-body">
                        <div class="stat-row">
                            <div class="stat-box"><div class="val"><?= $booking_count ?></div><div class="lbl">Total Tiket</div></div>
                            <div class="stat-box"><div class="val"><?= date('Y') - date('Y', strtotime($user['created_at'])) ?: '<1' ?></div><div class="lbl">Tahun Bergabung</div></div>
                            <div class="stat-box"><div class="val"><?= $user['role']==='admin'?'⭐':'👤' ?></div><div class="lbl"><?= ucfirst($user['role']) ?></div></div>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;">
                                <small style="color:var(--text2);font-size:0.75rem;">Email tidak dapat diubah</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Telepon</label>
                                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tanggal Bergabung</label>
                                <input type="text" class="form-control" value="<?= date('d M Y', strtotime($user['created_at'])) ?>" disabled style="opacity:0.6;">
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <?php elseif ($tab === 'password'): ?>
                <div class="settings-panel">
                    <div class="panel-header">
                        <div class="panel-title">UBAH PASSWORD</div>
                        <p style="color:var(--text2);font-size:0.85rem;margin-top:4px;">Pastikan password baru Anda kuat dan unik</p>
                    </div>
                    <div class="panel-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Password Saat Ini</label>
                                <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 karakter" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Ubah Password 🔒</button>
                        </form>
                    </div>
                </div>

                <?php elseif ($tab === 'preferences'): ?>
                <div class="settings-panel">
                    <div class="panel-header">
                        <div class="panel-title">PREFERENSI</div>
                        <p style="color:var(--text2);font-size:0.85rem;margin-top:4px;">Sesuaikan pengalaman CineMax Anda</p>
                    </div>
                    <div class="panel-body">
                        <form method="POST">
                            <div class="pref-item">
                                <div class="pref-left">
                                    <h4>🔔 Notifikasi Email</h4>
                                    <p>Terima konfirmasi pemesanan tiket via email</p>
                                </div>
                                <div class="toggle on" onclick="this.classList.toggle('on')"></div>
                            </div>
                            <div class="pref-item">
                                <div class="pref-left">
                                    <h4>🎬 Rekomendasi Film</h4>
                                    <p>Dapatkan saran film berdasarkan histori Anda</p>
                                </div>
                                <div class="toggle on" onclick="this.classList.toggle('on')"></div>
                            </div>
                            <div class="pref-item">
                                <div class="pref-left">
                                    <h4>💰 Promo & Penawaran</h4>
                                    <p>Terima info promo eksklusif via notifikasi</p>
                                </div>
                                <div class="toggle on" onclick="this.classList.toggle('on')"></div>
                            </div>
                            <div class="pref-item">
                                <div class="pref-left">
                                    <h4>⏰ Pengingat Jadwal</h4>
                                    <p>Ingatkan 2 jam sebelum jadwal tayang</p>
                                </div>
                                <div class="toggle" onclick="this.classList.toggle('on')"></div>
                            </div>
                            <div class="divider"></div>
                            <button type="submit" class="btn btn-primary mt-2">Simpan Preferensi</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>