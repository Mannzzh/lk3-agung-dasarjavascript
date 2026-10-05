<?php
require_once 'config.php';
requireLogin();
$db = getDB();

if (isset($_POST['mark_all_read'])) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    header('Location: index.php?page=notifications');
    exit;
}

if (isset($_GET['read'])) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([(int)$_GET['read'], $_SESSION['user_id']]);
}

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$notifs = $stmt->fetchAll();

$unread = array_filter($notifs, fn($n) => !$n['is_read']);
?>

<style>
.notif-page { padding: 2rem 0 4rem; }
.page-header { background: var(--bg2); border-bottom: 1px solid var(--border); padding: 1.5rem 0; margin-bottom: 2rem; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 2px; }
.notif-header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
.notif-card {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2);
    padding: 1.25rem; margin-bottom: 0.75rem; display: grid; grid-template-columns: auto 1fr auto;
    gap: 1rem; align-items: start; transition: all 0.2s; cursor: pointer; text-decoration: none; color: inherit;
}
.notif-card:hover { border-color: var(--border2); }
.notif-card.unread { border-left: 3px solid var(--accent); background: rgba(230,57,70,0.04); }
.notif-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.notif-icon.success { background: rgba(46,204,113,0.12); }
.notif-icon.info { background: rgba(52,152,219,0.12); }
.notif-icon.warning { background: rgba(243,156,18,0.12); }
.notif-icon.error { background: rgba(231,76,60,0.12); }
.notif-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 4px; }
.notif-msg { font-size: 0.82rem; color: var(--text2); line-height: 1.5; }
.notif-time { font-size: 0.75rem; color: var(--text2); white-space: nowrap; font-family: 'Space Mono', monospace; }
.notif-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); margin-top: 4px; }
.empty-state { text-align: center; padding: 5rem; color: var(--text2); }
</style>

<?php
$type_icons = ['success'=>'✅','info'=>'🔔','warning'=>'⚠️','error'=>'❌'];
$type_map = ['success'=>'success','info'=>'info','warning'=>'warning','error'=>'error'];
?>

<div class="notif-page">
    <div class="page-header">
        <div class="container">
            <div class="page-title">🔔 NOTIFIKASI</div>
            <p style="color:var(--text2);margin-top:4px;font-size:0.9rem;">
                <?= count($unread) ?> notifikasi belum dibaca dari <?= count($notifs) ?> total
            </p>
        </div>
    </div>

    <div class="container">
        <div class="notif-header-bar">
            <div>
                <?php if (count($unread) > 0): ?>
                    <span class="badge badge-red"><?= count($unread) ?> Belum Dibaca</span>
                <?php else: ?>
                    <span class="badge badge-green">Semua Sudah Dibaca</span>
                <?php endif; ?>
            </div>

            <?php if (count($unread) > 0): ?>
                <form method="POST">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="btn btn-secondary btn-sm">✓ Tandai Semua Dibaca</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($notifs)): ?>
            <div class="empty-state">
                <div style="font-size:4rem;margin-bottom:1rem;">🔔</div>
                <h3 style="margin-bottom:0.5rem;">Tidak Ada Notifikasi</h3>
                <p>Notifikasi tentang pemesanan tiket Anda akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifs as $n):
                $icon = $type_icons[$n['type']] ?? '🔔';
                $type_cls = $type_map[$n['type']] ?? 'info';
                $is_unread = !$n['is_read'];

                $time_diff = time() - strtotime($n['created_at']);
                if ($time_diff < 60) {
                    $time_str = 'Baru saja';
                } elseif ($time_diff < 3600) {
                    $time_str = floor($time_diff / 60) . ' menit lalu';
                } elseif ($time_diff < 86400) {
                    $time_str = floor($time_diff / 3600) . ' jam lalu';
                } else {
                    $time_str = date('d M Y', strtotime($n['created_at']));
                }
            ?>
                <a href="index.php?page=notifications&read=<?= $n['id'] ?>" class="notif-card <?= $is_unread ? 'unread' : '' ?>">
                    <div class="notif-icon <?= $type_cls ?>"><?= $icon ?></div>
                    <div>
                        <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                        <div class="notif-time"><?= $time_str ?></div>
                        <?php if ($is_unread): ?><div class="notif-dot"></div><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

