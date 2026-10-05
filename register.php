<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($name) < 3) { $error = 'Nama minimal 3 karakter.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid.'; }
    elseif (strlen($password) < 6) { $error = 'Password minimal 6 karakter.'; }
    elseif ($password !== $confirm) { $error = 'Konfirmasi password tidak cocok.'; }
    else {
        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) { $error = 'Email sudah terdaftar.'; }
        else {
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = $db->lastInsertId();
            addNotification($userId, 'Selamat Datang! 🎬', "Halo {$name}! Akun CineMax kamu sudah aktif. Silakan pesan tiket di menu Film.", 'success');
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Akun berhasil dibuat! Silakan masuk.'];
            header('Location: index.php?page=login');
            exit;
        }
    }
}
?>
<style>
.auth-page { min-height: calc(100vh - 64px); display: flex; align-items: center; justify-content: center; padding: 2rem; background: radial-gradient(ellipse at 70% 50%, rgba(230,57,70,0.06) 0%, transparent 60%); }
.auth-box { width: 100%; max-width: 440px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); overflow: hidden; box-shadow: var(--shadow); }
.auth-header { background: linear-gradient(135deg, rgba(46,204,113,0.12), rgba(46,204,113,0.04)); border-bottom: 1px solid var(--border); padding: 2rem; text-align: center; }
.auth-icon { font-size: 3rem; margin-bottom: 0.5rem; }
.auth-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 2px; }
.auth-header p { color: var(--text2); font-size: 0.9rem; margin-top: 4px; }
.auth-body { padding: 2rem; }
.auth-footer { padding: 1.25rem 2rem; border-top: 1px solid var(--border); text-align: center; font-size: 0.85rem; color: var(--text2); }
.auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
.error-msg { background: rgba(231,76,60,0.12); border: 1px solid rgba(231,76,60,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius); font-size: 0.875rem; margin-bottom: 1.25rem; }
.perks { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 1.25rem; }
.perk { background: var(--bg3); border-radius: var(--radius); padding: 0.75rem; font-size: 0.8rem; color: var(--text2); text-align: center; }
.perk .perk-icon { font-size: 1.5rem; display: block; margin-bottom: 4px; }
</style>

<div class="auth-page">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-icon">🎟️</div>
            <h2>DAFTAR AKUN</h2>
            <p>Bergabung dengan CineMax Indonesia</p>
        </div>
        <div class="auth-body">
            <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" placeholder="Nama Anda" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="tel" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Buat Akun 🎬</button>
            </form>
            <div class="perks">
                <div class="perk"><span class="perk-icon">🎫</span>Pesan mudah</div>
                <div class="perk"><span class="perk-icon">💰</span>Promo eksklusif</div>
                <div class="perk"><span class="perk-icon">🔔</span>Notifikasi tiket</div>
                <div class="perk"><span class="perk-icon">🌟</span>Kursi premium</div>
            </div>
        </div>
        <div class="auth-footer">
            Sudah punya akun? <a href="login.php?page=login">Masuk di sini</a>
        </div>
    </div>
</div>