<?php
/**
 * NEXUS CAMPUS PORTAL
 * Landing Page / Dashboard Awal (Mirip TG Portal)
 * Versi: Background Gedung Kampus Asli + Widget Ekstra Gede & Lega + Warna Putih & Biru
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

$isUserLoggedIn = isLoggedIn();
$currentUser    = $isUserLoggedIn ? getCurrentUser() : null;

// Fetch live metrics from database
$db = getDB();
$totalMahasiswa = $db->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn() ?: 8420;
$totalDosen     = $db->query("SELECT COUNT(*) FROM dosen")->fetchColumn() ?: 312;
$totalMatkul    = $db->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn() ?: 148;
$avgIpk         = $db->query("SELECT ROUND(AVG(nilai_akhir)/25, 2) FROM nilai WHERE nilai_akhir IS NOT NULL")->fetchColumn() ?: 3.68;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NexusPortal — Portal Akademik Digital Terpadu</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --primary-blue:   #0284c7;
    --primary-dark:   #0369a1;
    --primary-deep:   #0f172a;
    --accent-cyan:    #06b6d4;
    --accent-red:     #e11d48;
    --accent-green:   #10b981;
    --glass-bg:       rgba(255, 255, 255, 0.94);
    --glass-border:   rgba(255, 255, 255, 0.85);
    --text-primary:   #0f172a;
    --text-muted:     #64748b;
  }

  body {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    color: var(--text-primary);
    min-height: 100vh;
    overflow-x: hidden;
    position: relative;
    background-color: #f0f7ff;
  }

  /* ── REAL CAMPUS BUILDING HD BACKGROUND ────────────────────────── */
  .hero-bg {
    position: fixed;
    inset: 0;
    z-index: -2;
    background-image: 
      linear-gradient(180deg, rgba(240, 247, 255, 0.65) 0%, rgba(224, 242, 254, 0.75) 60%, rgba(240, 247, 255, 0.92) 100%),
      url('assets/img/gedung-kampus.png');
    background-size: cover;
    background-position: center 25%;
    background-repeat: no-repeat;
    transform: scale(1.01);
  }

  /* ── TOPBAR TRANSPARAN ─────────────────────────────────────────── */
  .landing-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 48px;
    position: relative;
    z-index: 10;
  }

  .topbar-left-btn {
    width: 48px; height: 48px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--primary-deep); font-size: 1.15rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease;
  }
  .topbar-left-btn:hover {
    background: #e0f2fe;
    color: var(--primary-blue);
    transform: scale(1.05);
  }

  .login-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    color: var(--primary-deep);
    padding: 12px 28px;
    border-radius: 99px;
    font-size: 0.95rem;
    font-weight: 800;
    text-decoration: none;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 6px 20px rgba(2, 132, 199, 0.12);
    transition: all 0.25s ease;
  }
  .login-pill-btn:hover {
    background: #e0f2fe;
    border-color: var(--primary-blue);
    color: var(--primary-blue);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(2, 132, 199, 0.25);
  }
  .login-pill-btn i { color: var(--primary-blue); font-size: 1.1rem; }

  /* ── CENTER BRANDING & BIG SEARCHBAR (TG PORTAL STYLE) ─────────── */
  .center-portal-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 20px 24px 32px;
    position: relative;
    z-index: 10;
  }

  .portal-big-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    user-select: none;
  }

  .logo-emblem {
    font-family: 'JetBrains Mono', monospace;
    font-size: 3rem;
    font-weight: 900;
    color: var(--primary-deep);
    letter-spacing: -2px;
  }
  .logo-emblem span {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-style: italic;
    font-size: 3.4rem;
    font-weight: 800;
    color: var(--primary-blue);
    letter-spacing: -1px;
    text-shadow: 0 4px 20px rgba(2, 132, 199, 0.2);
  }

  /* Big Curved Search Bar with Integrated NexusGPT Button */
  .searchbar-container {
    width: 100%;
    max-width: 720px;
    position: relative;
    margin-bottom: 24px;
  }

  .searchbar-pill {
    display: flex;
    align-items: center;
    background: #ffffff;
    border-radius: 99px;
    padding: 8px 12px 8px 26px;
    box-shadow: 0 16px 45px rgba(2, 132, 199, 0.15), 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
  }
  .searchbar-pill:focus-within {
    border-color: var(--primary-blue);
    box-shadow: 0 20px 50px rgba(2, 132, 199, 0.25);
  }

  .searchbar-input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    color: #0f172a;
    font-size: 1rem;
    font-family: inherit;
    font-weight: 600;
  }
  .searchbar-input::placeholder { color: #94a3b8; }

  .gpt-trigger-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border-left: 1.5px solid #e2e8f0;
    padding: 8px 16px 8px 20px;
    border-radius: 99px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
  }
  .gpt-trigger-btn:hover { background: #e0f2fe; }
  .gpt-label { font-size: 0.85rem; color: #475569; font-weight: 600; }
  .gpt-brand {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 800;
    color: var(--primary-deep);
    font-size: 1rem;
  }
  .gpt-brand i.fa-robot { color: var(--primary-blue); font-size: 1.2rem; }
  .gpt-arrow {
    width: 26px; height: 26px;
    background: var(--primary-blue);
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem;
  }

  /* Hide / Show Widgets Toggle Pill */
  .toggle-widgets-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 7px 22px;
    border-radius: 99px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease;
  }
  .toggle-widgets-pill:hover {
    background: #e0f2fe;
    color: var(--primary-blue);
    border-color: var(--primary-blue);
  }

  /* ── BOTTOM WIDGETS ROW (LEBIH BESAR & LEGA - HEIGHT 300px) ───── */
  .portal-widgets-row {
    display: grid;
    grid-template-columns: 1.3fr 1.3fr 1.15fr 1.45fr;
    gap: 22px;
    padding: 0 48px 48px;
    position: relative;
    z-index: 10;
    align-items: stretch;
    transition: all 0.4s ease;
  }

  .widget-section-header {
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--primary-deep);
    margin-bottom: 12px;
  }

  /* Base Glass Card (Ukuran Lebih Gede & Nyaman Dilihat) */
  .widget-glass-box {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    padding: 22px;
    position: relative;
    overflow: hidden;
    height: 300px;  /* UKURAN WIDGET LEBIH GEDE DARI SEBELUMNYA (250px -> 300px) */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 20px 45px rgba(2, 132, 199, 0.12), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
  }
  .widget-glass-box:hover {
    border-color: #bae6fd;
    transform: translateY(-5px);
    box-shadow: 0 25px 55px rgba(2, 132, 199, 0.2);
  }

  /* Widget 1 & 2: News & Highlights Slide */
  .news-thumb-wrap {
    position: absolute;
    inset: 0;
    border-radius: 24px;
    overflow: hidden;
  }
  .news-thumb-img {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: brightness(0.85);
    transition: transform 0.6s ease;
  }
  .widget-glass-box:hover .news-thumb-img { transform: scale(1.05); }

  .news-overlay-content {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 24px 22px 18px;
    background: linear-gradient(0deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.6) 60%, transparent 100%);
    z-index: 2;
    color: #ffffff;
  }
  .news-date {
    font-size: 0.72rem;
    color: #cbd5e1;
    margin-bottom: 6px;
    font-weight: 700;
  }
  .news-headline {
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.4;
    color: #ffffff;
    margin-bottom: 12px;
  }
  .slide-dots { display: flex; gap: 8px; }
  .dot {
    width: 7px; height: 7px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 50%;
  }
  .dot.active {
    width: 22px;
    border-radius: 99px;
    background: #38bdf8;
  }

  /* Widget 3: Quick Apps Grid 2x2 */
  .apps-grid-2x2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 12px;
    height: 100%;
  }
  .app-icon-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: var(--primary-deep);
    transition: all 0.25s ease;
    padding: 14px 10px;
    text-align: center;
  }
  .app-icon-card:hover {
    background: #e0f2fe;
    border-color: var(--primary-blue);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(2, 132, 199, 0.15);
  }
  .app-icon-card i {
    font-size: 1.9rem;
    margin-bottom: 8px;
    color: var(--primary-blue);
  }
  .app-icon-card span {
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  /* Widget 4: Campus Performance Index (Line Chart) */
  .stock-box {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
  }
  .stock-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .stock-company {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--primary-deep);
  }
  .stock-ticker {
    font-size: 0.75rem;
    color: #64748b;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
  }
  .stock-refresh {
    color: #64748b;
    font-size: 1rem;
    cursor: pointer;
    background: none; border: none;
  }
  .stock-refresh:hover { color: var(--primary-blue); }

  .stock-mid {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin: 12px 0 4px;
  }
  .stock-price {
    font-size: 2.2rem;
    font-weight: 900;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: -0.5px;
    color: var(--primary-deep);
  }
  .stock-price span {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--primary-blue);
    margin-left: 4px;
  }
  .stock-change {
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--accent-green);
    font-family: 'JetBrains Mono', monospace;
  }
  .daily-pill {
    font-size: 0.75rem;
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    padding: 3px 10px;
    border-radius: 99px;
    color: var(--primary-blue);
    font-weight: 700;
    margin-left: auto;
  }

  /* SVG Line Chart */
  .stock-chart-svg {
    width: 100%;
    height: 95px;
    margin-top: 6px;
    overflow: visible;
  }

  /* Live Date / Time Top Right of Widgets */
  .clock-floating {
    position: absolute;
    top: -55px;
    right: 48px;
    display: flex;
    align-items: baseline;
    gap: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: var(--primary-deep);
    user-select: none;
  }
  .clock-date {
    font-size: 1.9rem;
    font-weight: 800;
    letter-spacing: -0.5px;
  }
  .clock-time {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary-blue);
    font-family: 'JetBrains Mono', monospace;
  }

  @media (max-width: 1200px) {
    .portal-widgets-row { grid-template-columns: repeat(2, 1fr); }
    .clock-floating { position: static; margin-bottom: 20px; justify-content: flex-end; padding-right: 48px; }
  }
  @media (max-width: 768px) {
    .portal-widgets-row { grid-template-columns: 1fr; padding: 0 20px 30px; }
    .landing-topbar { padding: 18px 20px; }
    .searchbar-pill { flex-direction: column; padding: 12px; border-radius: 20px; }
    .gpt-trigger-btn { width: 100%; justify-content: center; border-left: none; border-top: 1.5px solid #e2e8f0; margin-top: 8px; }
  }
</style>
</head>
<body>

  <!-- ── 1. REAL HD CAMPUS BUILDING BACKGROUND ─────────────────── -->
  <div class="hero-bg" id="heroBg"></div>

  <!-- ── 2. TOPBAR TRANSPARAN ────────────────────────────────────── -->
  <header class="landing-topbar">
    <div class="topbar-left-btn" title="Informasi Kampus">
      <i class="fas fa-landmark"></i>
    </div>

    <div>
      <?php if ($isUserLoggedIn): ?>
      <a href="<?= APP_URL ?>/dashboard.php" class="login-pill-btn">
        <i class="fas fa-user-circle"></i> Dashboard (<?= e(explode(' ', $currentUser['nama'])[0]) ?>)
      </a>
      <a href="<?= APP_URL ?>/logout.php" class="login-pill-btn" style="background:#fee2e2;color:#b91c1c;border-color:#fca5a5;margin-left:8px">
        <i class="fas fa-right-from-bracket" style="color:#dc2626"></i> Logout
      </a>
      <?php else: ?>
      <a href="<?= APP_URL ?>/login.php" class="login-pill-btn">
        <i class="fas fa-right-to-bracket"></i> Login
      </a>
      <?php endif; ?>
    </div>
  </header>

  <!-- ── 3. CENTER SEARCHBAR & BRANDING ──────────────────────────── -->
  <section class="center-portal-area">
    <div class="portal-big-logo">
      <div class="logo-emblem">
        <i class="fas fa-graduation-cap" style="color:var(--primary-blue);font-size:2.8rem;margin-right:6px"></i><span>Portal</span>
      </div>
    </div>

    <!-- Curved Searchbar with NexusGPT button -->
    <div class="searchbar-container">
      <div class="searchbar-pill">
        <i class="fas fa-search" style="color:var(--primary-blue);font-size:1.15rem;margin-right:14px"></i>
        <input type="text" class="searchbar-input" id="portalSearchInput" placeholder="Cari informasi jadwal kuliah, nilai, materi, atau ruangan kampus...">
        
        <a href="<?= $isUserLoggedIn ? APP_URL . '/dashboard.php' : APP_URL . '/login.php' ?>" class="gpt-trigger-btn">
          <span class="gpt-label">atau coba</span>
          <div class="gpt-brand">
            <i class="fas fa-robot"></i>
            <span>NexusGPT</span>
          </div>
          <div class="gpt-arrow"><i class="fas fa-chevron-right"></i></div>
        </a>
      </div>
    </div>

    <!-- Hide / Show Widgets Toggle Button -->
    <button class="toggle-widgets-pill" id="btnToggleWidgets">
      <i class="fas fa-chevron-down" id="toggleIcon"></i>
      <span id="toggleText">Hide Widgets</span>
    </button>
  </section>

  <!-- ── 4. LIVE FLOATING CLOCK ──────────────────────────────────── -->
  <div class="clock-floating" id="clockWidget">
    <div class="clock-date" id="liveDateStr">12 Okt 2026</div>
    <div class="clock-time" id="liveTimeStr">19:07</div>
  </div>

  <!-- ── 5. BOTTOM WIDGETS ROW (LEBIH GEDE & LEGA) ──────────────── -->
  <main class="portal-widgets-row" id="widgetsRow">

    <!-- WIDGET 1: NEWS UPDATE -->
    <div>
      <div class="widget-section-header">NEWS UPDATE</div>
      <div class="widget-glass-box">
        <div class="news-thumb-wrap">
          <img class="news-thumb-img" src="assets/img/gedung-kampus.png" alt="News">
        </div>
        <div></div>
        <div class="news-overlay-content">
          <div class="news-date">Senin, 12 Okt 2026 09:30 WIB</div>
          <div class="news-headline">Peresmian Gedung Riset Sains & Teknologi Komputasi Cerdas 2026</div>
          <div class="slide-dots">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- WIDGET 2: HIGHLIGHTS -->
    <div>
      <div class="widget-section-header">HIGHLIGHTS</div>
      <div class="widget-glass-box">
        <div class="news-thumb-wrap">
          <img class="news-thumb-img" src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80" alt="Highlights">
        </div>
        <div></div>
        <div class="news-overlay-content">
          <div class="news-date">PENGUMUMAN AKADEMIK</div>
          <div class="news-headline">Jadwal Ujian Tengah Semester & Pengumpulan Proyek Akhir Praktikum</div>
          <div class="slide-dots">
            <div class="dot"></div>
            <div class="dot active"></div>
            <div class="dot"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- WIDGET 3: QUICK APPS (2x2 GRID) -->
    <div>
      <div class="widget-section-header">&nbsp;</div>
      <div class="widget-glass-box" style="padding:14px">
        <div class="apps-grid-2x2">
          <a href="<?= $isUserLoggedIn ? APP_URL . '/dashboard.php' : APP_URL . '/login.php' ?>" class="app-icon-card">
            <i class="fas fa-atom" style="color:var(--primary-blue)"></i>
            <span>NEXUS GPT</span>
          </a>
          <a href="<?= $isUserLoggedIn ? APP_URL . '/modules/mahasiswa/matkul.php' : APP_URL . '/login.php' ?>" class="app-icon-card">
            <i class="fas fa-book-bookmark" style="color:#0284c7"></i>
            <span>MATKUL</span>
          </a>
          <a href="<?= $isUserLoggedIn ? APP_URL . '/modules/mahasiswa/tugas.php' : APP_URL . '/login.php' ?>" class="app-icon-card">
            <i class="fas fa-file-arrow-up" style="color:#f59e0b"></i>
            <span>TUGAS</span>
          </a>
          <a href="<?= $isUserLoggedIn ? APP_URL . '/modules/mahasiswa/profil.php' : APP_URL . '/login.php' ?>" class="app-icon-card">
            <i class="fas fa-id-card-clip" style="color:#10b981"></i>
            <span>STUDENT ID</span>
          </a>
        </div>
      </div>
    </div>

    <!-- WIDGET 4: CAMPUS METRIC & ACADEMIC INDEX -->
    <div>
      <div class="widget-section-header">&nbsp;</div>
      <div class="widget-glass-box">
        <div class="stock-box">
          <div>
            <div class="stock-top">
              <div>
                <div class="stock-company">Indeks Prestasi Kumulatif (IPK)</div>
                <div class="stock-ticker">NEXUS ACADEMIC METRIC: NAM</div>
              </div>
              <button class="stock-refresh" title="Refresh Live Data" onclick="location.reload()"><i class="fas fa-arrows-rotate"></i></button>
            </div>

            <div class="stock-mid">
              <div class="stock-price">3.74 <span>IPK</span></div>
              <div class="daily-pill">Daily</div>
            </div>
            <div class="stock-change"><i class="fas fa-arrow-trend-up"></i> +0.08 (+2.14%) semester ini</div>
          </div>

          <!-- SVG Chart Line Blue Accent -->
          <svg class="stock-chart-svg" viewBox="0 0 300 70">
            <polyline
              fill="none"
              stroke="#0284c7"
              stroke-width="3.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              points="10,48 50,42 90,32 130,46 170,58 210,38 250,22 290,44"
            />
            <circle cx="10"  cy="48" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
            <circle cx="50"  cy="42" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
            <circle cx="90"  cy="32" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
            <circle cx="130" cy="46" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
            <circle cx="170" cy="58" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
            <circle cx="210" cy="38" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
            <circle cx="250" cy="22" r="5" fill="#38bdf8" stroke="#0369a1" stroke-width="2.5" />
            <circle cx="290" cy="44" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2" />
          </svg>

          <!-- Timeline Labels -->
          <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:#64748b;font-family:'JetBrains Mono',monospace;font-weight:700;margin-top:2px">
            <span>6 Okt</span>
            <span>7 Okt</span>
            <span>8 Okt</span>
            <span>9 Okt</span>
            <span>12 Okt</span>
          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- ── 6. JAVASCRIPT LIVE CLOCK & TOGGLE ───────────────────────── -->
  <script>
    function updateLiveClock() {
      const now = new Date();
      const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agt", "Sep", "Okt", "Nov", "Des"];
      const d = now.getDate();
      const m = months[now.getMonth()];
      const y = now.getFullYear();
      const hh = String(now.getHours()).padStart(2, '0');
      const mm = String(now.getMinutes()).padStart(2, '0');

      document.getElementById('liveDateStr').textContent = `${d} ${m} ${y}`;
      document.getElementById('liveTimeStr').textContent = `${hh}:${mm}`;
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    const btnToggle = document.getElementById('btnToggleWidgets');
    const widgetsRow = document.getElementById('widgetsRow');
    const clockWidget = document.getElementById('clockWidget');
    const toggleIcon = document.getElementById('toggleIcon');
    const toggleText = document.getElementById('toggleText');
    let isHidden = false;

    btnToggle.addEventListener('click', () => {
      isHidden = !isHidden;
      if (isHidden) {
        widgetsRow.style.opacity = '0';
        widgetsRow.style.transform = 'translateY(40px)';
        widgetsRow.style.pointerEvents = 'none';
        clockWidget.style.opacity = '0';
        toggleIcon.className = 'fas fa-chevron-up';
        toggleText.textContent = 'Show Widgets';
      } else {
        widgetsRow.style.opacity = '1';
        widgetsRow.style.transform = 'translateY(0)';
        widgetsRow.style.pointerEvents = 'auto';
        clockWidget.style.opacity = '1';
        toggleIcon.className = 'fas fa-chevron-down';
        toggleText.textContent = 'Hide Widgets';
      }
    });

    document.getElementById('portalSearchInput').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const q = e.target.value.trim();
        window.location.href = '<?= $isUserLoggedIn ? APP_URL . "/dashboard.php?q=" : APP_URL . "/login.php?q=" ?>' + encodeURIComponent(q);
      }
    });
  </script>
</body>
</html>
