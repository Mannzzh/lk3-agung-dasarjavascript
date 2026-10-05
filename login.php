<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Selamat datang, {$user['name']}! 🎬"];
        header('Location: index.php?page=home');
        exit;
    } else {
        $error = 'Email atau password salah.';
    }
}
?>
<style>
.auth-page {
    min-height: calc(100vh - 64px);
    display: flex; align-items: center; justify-content: center;
    padding: 2rem;
    background: radial-gradient(ellipse at 30% 50%, rgba(230,57,70,0.06) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 20%, rgba(52,73,235,0.04) 0%, transparent 60%);
}
.auth-box {
    width: 100%; max-width: 420px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius2); overflow: hidden;
    box-shadow: var(--shadow);
}
.auth-header {
    background: linear-gradient(135deg, rgba(230,57,70,0.15), rgba(230,57,70,0.05));
    border-bottom: 1px solid var(--border); padding: 2rem;
    text-align: center;
}
.auth-icon { font-size: 3rem; margin-bottom: 0.5rem; }
.auth-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 2px; color: var(--text); }
.auth-header p { color: var(--text2); font-size: 0.9rem; margin-top: 4px; }
.auth-body { padding: 2rem; }
.auth-footer { padding: 1.25rem 2rem; border-top: 1px solid var(--border); text-align: center; font-size: 0.85rem; color: var(--text2); }
.auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
.error-msg { background: rgba(231,76,60,0.12); border: 1px solid rgba(231,76,60,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius); font-size: 0.875rem; margin-bottom: 1.25rem; }
.demo-hint {
    background: rgba(52,152,219,0.08); border: 1px solid rgba(52,152,219,0.2);
    border-radius: var(--radius); padding: 0.75rem 1rem; margin-top: 1rem;
    font-size: 0.8rem; color: var(--text2);
}
.demo-hint strong { color: var(--info); }
</style>

<div class="auth-page">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-icon">🔐</div>
            <h2>MASUK</h2>
            <p>Akses akun CineMax Anda</p>
        </div>
        <div class="auth-body">
            <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk →</button>
            </form>
            <div class="demo-hint">
                <strong>Demo:</strong> admin@cinema.com / admin123<br>
                <strong>User:</strong> andi@email.com / andi112
            </div>
        </div>
        <div class="auth-footer">
            Belum punya akun? <a href="index.php?page=register">Daftar sekarang</a>
        </div>
    </div>
</div>