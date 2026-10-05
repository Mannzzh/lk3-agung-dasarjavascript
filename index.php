<?php
ob_start();
require_once 'config.php';

$flash        = getFlash();
$notifCount   = unreadNotifCount();
$cinemaName   = setting('cinema_name', 'CineMax');
$cinemaTagline= setting('cinema_tagline', 'Pengalaman Sinema Tak Terlupakan');
$user         = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($cinemaName) ?> — <?= e($cinemaTagline) ?></title>
<link rel="stylesheet" href="style.css"></link>

<!-- Google Fonts: Bebas Neue + Syne + DM Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style> 

</style>
</head>
<body>

<nav id="navbar">
  <div class="nav-inner">
    <a href="<?= BASE_URL ?>/index.php?page=home" class="nav-logo">
      🎬&nbsp;<span>CINE<span class="dot">MAX</span></span>
    </a>

    <div class="nav-links" id="navLinks">
      <a href="<?= BASE_URL ?>index.php?page=home"     class="nav-link <?= $page==='home'?'active':'' ?>">Beranda</a>
      <a href="<?= BASE_URL ?>index.php?page=movies"   class="nav-link <?= $page==='movies'?'active':'' ?>">Film</a>
      <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>index.php?page=my_bookings" class="nav-link <?= $page==='my_bookings'?'active':'' ?>">Tiket Saya</a>
        <?php if (isAdmin()): ?>
          <a href="<?= BASE_URL ?>index.php?page=admin" class="nav-link <?= $page==='admin'?'active':'' ?>">
            ⚙&nbsp;Admin
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="nav-actions">
      <?php if (isLoggedIn()): ?>
        <!-- Notifications -->
        <a href="<?= BASE_URL ?>index.php?page=notifications" class="notif-btn">
          🔔
          <?php if ($notifCount > 0): ?>
            <span class="notif-dot"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
          <?php endif; ?>
        </a>

        
        <div class="user-chip" tabindex="0" onclick="this.classList.toggle('open')">
          <div class="user-avatar"><?= e($user['avatar'] ?? '?') ?></div>
          <span><?= e(explode(' ', $user['name'])[0]) ?></span>
          <span style="font-size:.6rem;color:var(--muted)">▾</span>
          <div class="user-dropdown">
            <a href="<?= BASE_URL ?>index.php?page=profile"  class="dd-item">👤&nbsp;Profil Saya</a>
            <a href="<?= BASE_URL ?>index.php?page=my_bookings" class="dd-item">🎟&nbsp;Tiket Saya</a>
            <a href="<?= BASE_URL ?>index.php?page=notifications" class="dd-item">🔔&nbsp;Notifikasi</a>
            <a href="<?= BASE_URL ?>index.php?page=settings" class="dd-item">⚙&nbsp;Pengaturan</a>
            <?php if (isAdmin()): ?>
            <div class="dd-sep"></div>
            <a href="<?= BASE_URL ?>index.php?page=admin" class="dd-item">🛡&nbsp;Panel Admin</a>
            <?php endif; ?>
            <div class="dd-sep"></div>
            <a href="<?= BASE_URL ?>logout.php?page=logout" class="dd-item danger">🚪&nbsp;Keluar</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>index.php?page=login" class="btn btn-ghost btn-sm">Masuk</a>
        <a href="<?= BASE_URL ?>index.php?page=register" class="btn btn-primary btn-sm">Daftar</a>
      <?php endif; ?>

      <button class="ham-btn" onclick="document.getElementById('navLinks').classList.toggle('nav-open')">☰</button>
    </div>
  </div>
</nav>

<main id="main">
<?php

echo '<style>
@media(max-width:900px){
  #navLinks.nav-open{
    display:flex;flex-direction:column;align-items:stretch;
    position:fixed;top:62px;left:0;right:0;
    background:rgba(11,11,18,.97);backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);
    padding:1rem;gap:.25rem;z-index:999;
  }
}
</style>';
?>

<?php

$pageFile = __DIR__ . "/pages/{$page}.php";
if (file_exists($pageFile)) {
    require $pageFile;
} else {
    
    $rootFile = __DIR__ . "/{$page}.php";
    if (file_exists($rootFile)) {
        require $rootFile;
    } else {
        require 'home.php';
    }
}
?>
</main>

<footer>
  <div class="container">
    <div class="logo">🎬 <?= e(setting('cinema_name','CineMax')) ?></div>
    <p class="mt-1"><?= e(setting('cinema_address')) ?> &nbsp;|&nbsp; ☎ <?= e(setting('cinema_phone')) ?></p>
    <p class="mt-1" style="color:var(--faint)">© <?= date('Y') ?> <?= e(setting('cinema_name','CineMax')) ?>. All rights reserved.</p>
  </div>
</footer>

<div id="toast">
<?php if ($flash): ?>
  <div class="toast-item toast-<?= e($flash['type']) ?>">
    <?php $icons=['success'=>'✅','error'=>'❌','info'=>'ℹ️','warning'=>'⚠️'];
          echo $icons[$flash['type']] ?? 'ℹ️'; ?>
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>
</div>

<script>

setTimeout(()=>{
  document.querySelectorAll('.toast-item').forEach(el=>{
    el.style.transition='opacity .4s';
    el.style.opacity='0';
    setTimeout(()=>el.remove(),400);
  });
},4500);


document.addEventListener('click',e=>{
  document.querySelectorAll('.user-chip.open').forEach(el=>{
    if(!el.contains(e.target)) el.classList.remove('open');
  });
});
</script>
</body>
</html>