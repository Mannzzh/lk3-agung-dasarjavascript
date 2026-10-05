<?php
session_start();


define('DB_HOST', 'localhost');
define('DB_NAME', 'chinemax_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', '');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function ensureSessionKeys(): void
{
    // Standarisasi: login.php menyimpan user ke $_SESSION['user_id'].
    if (empty($_SESSION['uid']) && !empty($_SESSION['user_id'])) {
        $_SESSION['uid'] = $_SESSION['user_id'];
    }

    // Versi sebaliknya (untuk kompatibilitas kode lama).
    if (empty($_SESSION['user_id']) && !empty($_SESSION['uid'])) {
        $_SESSION['user_id'] = $_SESSION['uid'];
    }

    // Pastikan role tersedia untuk isAdmin().
    if (empty($_SESSION['role']) && !empty($_SESSION['user_role'])) {
        $_SESSION['role'] = $_SESSION['user_role'];
    }
}


function isLoggedIn(): bool
{
    ensureSessionKeys();
    // Session bisa tersimpan dalam uid atau user_id tergantung halaman.
    return !empty($_SESSION['uid']) || !empty($_SESSION['user_id']);
}


function isAdmin(): bool
{
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
}

function redirect(string $page, array $params = []): never
{
    $qs = $params ? '&' . http_build_query($params) : '';
    header('Location: ' . BASE_URL . '/index.php?page=' . $page . $qs);
    exit;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) redirect('home');
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    $s = db()->prepare('SELECT * FROM users WHERE id = ?');
    $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}

function getDB(): PDO { return db(); }

$ALLOWED_PAGES = [
    'home', 'movies', 'movie_detail', 'booking', 'my_bookings',
    'receipt','notifications', 'profile', 'settings',
    'login', 'register', 'logout',
    'admin',
];

$page = $_GET['page'] ?? 'home';
if (!in_array($page, $ALLOWED_PAGES, true)) $page = 'home';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setFlash(array $flash): void
{
    $_SESSION['flash'] = [
        'type' => $flash['type'] ?? 'info',
        'msg'  => $flash['msg'] ?? '',
    ];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function setting(string $key, $default = null)
{
    $val = getSetting($key, null);
    return $val === null ? $default : $val;
}

function getSetting(string $key, $default = null)
{
    try {
        $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return $default;
        $value = $row['value'];
        return $value;
    } catch (Throwable $e) {
        return $default;
    }
}

function formatPrice($amount): string
{
    $n = (int)$amount;
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function addNotification(int $userId, string $title, string $message, string $type = 'info'): void
{
    try {
        db()->prepare('INSERT INTO notifications (user_id, type, title, message, is_read) VALUES (?, ?, ?, ?, 0)')
            ->execute([$userId, $type, $title, $message]);
    } catch (Throwable $e) {
        
    }
}

function unreadNotifCount(): int
{
    if (!isLoggedIn()) return 0;
    try {
        $stmt = db()->prepare('SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$_SESSION['uid']]);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function generateBookingCode(): string
{
    $suffix = strtoupper(bin2hex(random_bytes(2)));
    return 'BC-' . date('Ymd') . '-' . $suffix;
}

function getSettingInt(string $key, int $default = 0): int
{
    $v = getSetting($key, null);
    if ($v === null || $v === '') return $default;
    return (int)$v;
}


if ($page === 'logout') {
    
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();

    header('Location: ' . BASE_URL . '/index.php?page=home');
    exit;
}


