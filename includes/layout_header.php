<?php
/**
 * NEXUS CAMPUS PORTAL
 * Shared Layout — Sidebar + Topbar (included by all pages)
 *
 * Required vars:
 *   $pageTitle  — page title
 *   $pageSection — current section for nav highlight
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$user    = getCurrentUser();
$role    = $user['role'];
$notifCount = getUnreadNotifCount();

$initial = strtoupper(substr($user['nama'], 0, 1));

// Navigation per role
$nav = [];

if ($role === 'mahasiswa') {
    $nav = [
        'Akademik' => [
            ['label' => 'Dashboard',    'icon' => 'fa-house',           'href' => 'dashboard.php',                        'section' => 'dashboard'],
            ['label' => 'Nilai Saya',   'icon' => 'fa-chart-bar',       'href' => 'modules/mahasiswa/nilai.php',           'section' => 'nilai'],
            ['label' => 'Tugas',        'icon' => 'fa-file-arrow-up',   'href' => 'modules/mahasiswa/tugas.php',           'section' => 'tugas'],
            ['label' => 'Mata Kuliah',  'icon' => 'fa-book-open',       'href' => 'modules/mahasiswa/matkul.php',          'section' => 'matkul'],
        ],
        'Komunitas' => [
            ['label' => 'Forum Diskusi', 'icon' => 'fa-comments',       'href' => 'modules/mahasiswa/forum.php',           'section' => 'forum'],
            ['label' => 'Notifikasi',    'icon' => 'fa-bell',           'href' => 'modules/mahasiswa/notifikasi.php',      'section' => 'notif', 'badge' => $notifCount],
        ],
        'Akun' => [
            ['label' => 'Profil',       'icon' => 'fa-user-circle',     'href' => 'modules/mahasiswa/profil.php',          'section' => 'profil'],
        ],
    ];
} elseif ($role === 'dosen') {
    $nav = [
        'Mengajar' => [
            ['label' => 'Dashboard',    'icon' => 'fa-house',           'href' => 'dashboard.php',                        'section' => 'dashboard'],
            ['label' => 'Mata Kuliah',  'icon' => 'fa-book-open',       'href' => 'modules/dosen/matkul.php',             'section' => 'matkul'],
            ['label' => 'Input Nilai',  'icon' => 'fa-pen-to-square',   'href' => 'modules/dosen/nilai.php',              'section' => 'nilai'],
            ['label' => 'Kelola Tugas', 'icon' => 'fa-clipboard-list',  'href' => 'modules/dosen/tugas.php',              'section' => 'tugas'],
        ],
        'Interaksi' => [
            ['label' => 'Forum Diskusi','icon' => 'fa-comments',        'href' => 'modules/dosen/forum.php',              'section' => 'forum'],
            ['label' => 'Notifikasi',  'icon' => 'fa-bell',            'href' => 'modules/dosen/notifikasi.php',         'section' => 'notif', 'badge' => $notifCount],
        ],
        'Akun' => [
            ['label' => 'Profil',       'icon' => 'fa-user-circle',     'href' => 'modules/dosen/profil.php',             'section' => 'profil'],
        ],
    ];
} elseif ($role === 'admin') {
    $nav = [
        'Manajemen' => [
            ['label' => 'Dashboard',    'icon' => 'fa-house',           'href' => 'dashboard.php',                        'section' => 'dashboard'],
            ['label' => 'Kelola Akun',  'icon' => 'fa-users-gear',      'href' => 'modules/admin/users.php',              'section' => 'users'],
            ['label' => 'Mata Kuliah',  'icon' => 'fa-book',            'href' => 'modules/admin/matkul.php',             'section' => 'matkul'],
            ['label' => 'Laporan',      'icon' => 'fa-chart-line',      'href' => 'modules/admin/laporan.php',            'section' => 'laporan'],
        ],
        'Sistem' => [
            ['label' => 'Notifikasi',   'icon' => 'fa-bell',            'href' => 'modules/admin/notifikasi.php',         'section' => 'notif', 'badge' => $notifCount],
            ['label' => 'Kirim Notif',  'icon' => 'fa-paper-plane',     'href' => 'modules/admin/send_notif.php',         'section' => 'send_notif'],
        ],
        'Akun' => [
            ['label' => 'Profil',       'icon' => 'fa-user-circle',     'href' => 'modules/admin/profil.php',             'section' => 'profil'],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Sora:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
<?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
<div class="app-layout">

  <!-- ── SIDEBAR ──────────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-logo">
        <div class="brand-icon">🎓</div>
        <div class="brand-text">
          <span class="brand-name"><?= APP_NAME ?></span>
          <span class="brand-sub">Academic Portal v<?= APP_VERSION ?></span>
        </div>
      </div>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar"><?= $initial ?></div>
      <div class="user-info">
        <div class="user-name"><?= e($user['nama']) ?></div>
        <span class="user-role role-<?= $role ?>"><?= ucfirst($role) ?></span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($nav as $section => $items): ?>
      <div class="nav-section">
        <div class="nav-section-label"><?= $section ?></div>
        <?php foreach ($items as $item): ?>
        <?php
          $isActive = ($pageSection ?? '') === $item['section'];
          $absHref  = APP_URL . '/' . $item['href'];
        ?>
        <a href="<?= $absHref ?>"
           class="nav-item <?= $isActive ? 'active' : '' ?>">
          <span class="nav-icon"><i class="fas <?= $item['icon'] ?>"></i></span>
          <?= e($item['label']) ?>
          <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
          <span class="nav-badge"><?= $item['badge'] ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <a href="<?= APP_URL ?>/logout.php"
         class="nav-item"
         style="color:var(--accent-red)"
         onclick="return confirm('Yakin ingin keluar?')">
        <span class="nav-icon"><i class="fas fa-right-from-bracket"></i></span>
        Keluar
      </a>
    </div>
  </aside>

  <!-- Mobile sidebar overlay -->
  <div id="sidebarOverlay" style="
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.5); z-index:99;
  "></div>

  <!-- ── TOPBAR ────────────────────────────────────────────────── -->
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="hamburger" id="hamburger">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-title">
        <?= APP_NAME ?> / <span><?= e($pageTitle ?? 'Dashboard') ?></span>
      </div>
    </div>

    <div class="topbar-actions">
      <!-- Notifications -->
      <a href="<?= APP_URL ?>/modules/<?= $role ?>/notifikasi.php"
         class="topbar-btn" title="Notifikasi">
        <i class="fas fa-bell"></i>
        <?php if ($notifCount > 0): ?>
        <span class="notif-dot"></span>
        <?php endif; ?>
      </a>

      <!-- Time display -->
      <div class="topbar-btn" style="width:auto;padding:0 10px;font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:var(--text-muted);" id="clock">
        --:--:--
      </div>

      <!-- User dropdown trigger -->
      <a href="<?= APP_URL ?>/modules/<?= $role ?>/profil.php"
         class="topbar-btn" title="Profil" style="background:linear-gradient(135deg,var(--accent-purple),var(--accent-pink));color:#fff;font-weight:700;">
        <?= $initial ?>
      </a>
    </div>
  </header>

  <!-- ── MAIN CONTENT ──────────────────────────────────────────── -->
  <main class="main-content">
    <!-- Flash message toast hook -->
    <?php
    $flashMsg  = getFlash('success') ?: getFlash('error') ?: getFlash('warning');
    $flashType = 'info';
    if (getFlash('success')) $flashType = 'success';
    if (getFlash('error'))   $flashType = 'danger';
    if ($flashMsg): ?>
    <div id="__toast__" data-msg="<?= e($flashMsg) ?>" data-type="<?= $flashType ?>"></div>
    <?php endif; ?>
