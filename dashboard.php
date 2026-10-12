<?php
/**
 * NEXUS CAMPUS PORTAL
 * Dashboard — Immersive Campus Theme with Interactive Widgets (TG Portal Style)
 */

require_once 'includes/auth.php';
requireLogin();

$db   = getDB();
$user = getCurrentUser();
$role = $user['role'];

// Fetch stats per role
$stats = [];
if ($role === 'mahasiswa') {
    $profileId = $user['profile_id'];
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_matkul,
               AVG(n.nilai_akhir) as avg_nilai,
               SUM(mk.sks) as total_sks
        FROM enrollment e
        JOIN mata_kuliah mk ON e.matkul_id = mk.id
        LEFT JOIN nilai n ON e.id = n.enrollment_id
        WHERE e.mahasiswa_id = ?
    ");
    $stmt->execute([$profileId]);
    $mhsStats = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT t.id) as pending
        FROM tugas t
        JOIN mata_kuliah mk ON t.matkul_id = mk.id
        JOIN enrollment e ON mk.id = e.matkul_id AND e.mahasiswa_id = ?
        LEFT JOIN pengumpulan_tugas pt ON t.id = pt.tugas_id AND pt.mahasiswa_id = ?
        WHERE pt.id IS NULL AND t.deadline > NOW()
    ");
    $stmt->execute([$profileId, $profileId]);
    $pendingTugas = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM mahasiswa WHERE id = ?");
    $stmt->execute([$profileId]);
    $mhsData = $stmt->fetch();

    $stats = [
        'matkul'  => $mhsStats['total_matkul'] ?? 0,
        'avg'     => round($mhsStats['avg_nilai'] ?? 0, 1),
        'sks'     => $mhsStats['total_sks'] ?? 0,
        'pending' => $pendingTugas,
    ];

    $stmt = $db->prepare("
        SELECT mk.nama, mk.kode, n.nilai_akhir, n.grade, n.updated_at
        FROM enrollment e
        JOIN mata_kuliah mk ON e.matkul_id = mk.id
        JOIN nilai n ON e.id = n.enrollment_id
        WHERE e.mahasiswa_id = ?
        ORDER BY n.updated_at DESC LIMIT 5
    ");
    $stmt->execute([$profileId]);
    $recentNilai = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT t.judul, t.deadline, mk.nama as matkul,
               CASE WHEN pt.id IS NOT NULL THEN 1 ELSE 0 END as submitted
        FROM tugas t
        JOIN mata_kuliah mk ON t.matkul_id = mk.id
        JOIN enrollment e ON mk.id = e.matkul_id AND e.mahasiswa_id = ?
        LEFT JOIN pengumpulan_tugas pt ON t.id = pt.tugas_id AND pt.mahasiswa_id = ?
        WHERE t.deadline > NOW()
        ORDER BY t.deadline ASC LIMIT 5
    ");
    $stmt->execute([$profileId, $profileId]);
    $upcomingTugas = $stmt->fetchAll();

} elseif ($role === 'dosen') {
    $dosenId = $user['profile_id'];
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_matkul,
               (SELECT COUNT(*) FROM enrollment e JOIN mata_kuliah mk ON e.matkul_id = mk.id WHERE mk.dosen_id = ?) as total_mahasiswa
        FROM mata_kuliah WHERE dosen_id = ?
    ");
    $stmt->execute([$dosenId, $dosenId]);
    $dosenStats = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT COUNT(*) as pending
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.tugas_id = t.id
        JOIN mata_kuliah mk ON t.matkul_id = mk.id
        WHERE mk.dosen_id = ? AND pt.status = 'submitted'
    ");
    $stmt->execute([$dosenId]);
    $pendingGrading = $stmt->fetchColumn();

    $stats = [
        'matkul'    => $dosenStats['total_matkul'] ?? 0,
        'mahasiswa' => $dosenStats['total_mahasiswa'] ?? 0,
        'pending'   => $pendingGrading,
    ];

    $stmt = $db->prepare("
        SELECT mk.*, COUNT(e.id) as enrolled
        FROM mata_kuliah mk
        LEFT JOIN enrollment e ON mk.id = e.matkul_id
        WHERE mk.dosen_id = ?
        GROUP BY mk.id
        ORDER BY mk.created_at DESC
    ");
    $stmt->execute([$dosenId]);
    $dosenMatkul = $stmt->fetchAll();

} elseif ($role === 'admin') {
    $stats['total_users']     = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_mahasiswa'] = $db->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();
    $stats['total_dosen']     = $db->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
    $stats['total_matkul']    = $db->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn();
    $stats['total_forum']     = $db->query("SELECT COUNT(*) FROM forum")->fetchColumn();
    $stats['total_tugas']     = $db->query("SELECT COUNT(*) FROM pengumpulan_tugas")->fetchColumn();

    $recentUsers = $db->query("
        SELECT u.*, m.nim, d.nidn
        FROM users u
        LEFT JOIN mahasiswa m ON u.id = m.user_id
        LEFT JOIN dosen d ON u.id = d.user_id
        ORDER BY u.created_at DESC LIMIT 6
    ")->fetchAll();
}

$pageTitle   = 'Portal Dashboard';
$pageSection = 'dashboard';
require_once 'includes/layout_header.php';
?>

<!-- ── HERO BANNER & SEARCHBAR (TG PORTAL STYLE) ────────────────── -->
<div class="portal-hero">
  <div class="hero-content">
    <div class="hero-text">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
        <span class="badge badge-cyan font-mono"><i class="fas fa-university"></i> CAMPUS HUB</span>
        <span class="text-xs text-muted"><?= date('l, d F Y') ?></span>
      </div>
      <h1>
        Selamat Datang, <?= e(explode(' ', $user['nama'])[0]) ?> 👋
      </h1>
      <p>
        Sistem Informasi Akademik Terpadu & Laboratorium Digital Generasi 4.0
        <?php if ($role === 'mahasiswa' && isset($mhsData)): ?>
        — NIM: <span class="font-mono text-cyan" style="color:var(--accent-cyan);font-weight:700"><?= e($mhsData['nim']) ?></span>
        <?php endif; ?>
      </p>
    </div>

    <!-- Interactive Search Bar -->
    <div class="hero-searchbar">
      <i class="fas fa-search" style="color:var(--accent-cyan);font-size:1.1rem;margin-right:12px"></i>
      <input type="text" id="portalSearch" placeholder="Cari mata kuliah, tugas, dosen, atau materi...">
      <a href="<?= APP_URL ?>/modules/<?= $role === 'admin' ? 'admin/users.php' : ($role === 'dosen' ? 'dosen/nilai.php' : 'mahasiswa/nilai.php') ?>" class="hero-gpt-btn">
        <i class="fas fa-bolt"></i> Quick Hub
      </a>
    </div>
  </div>
</div>

<!-- ── WIDGETS SECTION (CAMPUS ROOMS & HIGHLIGHTS) ───────────────── -->
<div class="widgets-grid">

  <!-- Widget 1: Campus Highlights (Foto Ruangan Laboratorium / Kampus) -->
  <div class="widget-card room-card-1">
    <div class="widget-title-bar">
      <span class="widget-title"><i class="fas fa-newspaper"></i> Campus Highlights</span>
      <span class="badge badge-cyan">Terbaru</span>
    </div>
    <div style="margin-top:40px">
      <h3 style="font-size:1.2rem;font-weight:800;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,0.8)">
        Inovasi AI Lab & Cyber Security Campus Center 2026
      </h3>
      <p class="text-xs text-muted mt-1" style="color:#e2e8f0;text-shadow:0 1px 4px rgba(0,0,0,0.8)">
        Fasilitas komputasi awan dan workstation pemrograman kini dapat diakses secara daring oleh seluruh civitas akademika.
      </p>
      <div class="mt-2">
        <a href="<?= APP_URL ?>/modules/<?= $role ?>/forum.php" class="btn btn-cyan btn-sm">
          <i class="fas fa-comments"></i> Ikuti Diskusi
        </a>
      </div>
    </div>
  </div>

  <!-- Widget 2: Interactive Quick Apps Grid -->
  <div class="widget-card room-card-2">
    <div class="widget-title-bar">
      <span class="widget-title"><i class="fas fa-grid-2"></i> Aplikasi Kampus</span>
      <span class="text-xs text-muted font-mono"><i class="fas fa-wifi" style="color:var(--accent-green)"></i> Online</span>
    </div>
    <div class="app-tiles-grid">
      <a href="<?= APP_URL ?>/modules/<?= $role ?>/forum.php" class="app-tile">
        <i class="fas fa-comments"></i>
        <span>Forum Diskusi</span>
      </a>
      <a href="<?= APP_URL ?>/modules/<?= $role === 'dosen' ? 'dosen/tugas.php' : ($role === 'admin' ? 'admin/matkul.php' : 'mahasiswa/tugas.php') ?>" class="app-tile">
        <i class="fas fa-file-code"></i>
        <span><?= $role === 'dosen' ? 'Review Tugas' : ($role === 'admin' ? 'Matkul' : 'Kumpul Tugas') ?></span>
      </a>
      <a href="<?= APP_URL ?>/modules/<?= $role === 'admin' ? 'admin/laporan.php' : ($role === 'dosen' ? 'dosen/nilai.php' : 'mahasiswa/nilai.php') ?>" class="app-tile">
        <i class="fas fa-chart-line"></i>
        <span><?= $role === 'admin' ? 'Laporan' : 'Akademik' ?></span>
      </a>
      <a href="<?= APP_URL ?>/modules/<?= $role ?>/profil.php" class="app-tile">
        <i class="fas fa-user-shield"></i>
        <span>Profil Saya</span>
      </a>
    </div>
  </div>

  <!-- Widget 3: Live Academic Index Widget (Stock / Metrics style seperti di gambar) -->
  <div class="widget-card room-card-3">
    <div class="widget-title-bar">
      <span class="widget-title"><i class="fas fa-gauge-high"></i> Indeks Performa</span>
      <span class="text-xs font-mono" style="color:var(--accent-green)"><i class="fas fa-arrow-trend-up"></i> +12.4%</span>
    </div>
    <div style="margin: 12px 0">
      <div style="display:flex;align-items:baseline;gap:8px">
        <span style="font-size:2.4rem;font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--accent-cyan)">
          <?= $role === 'mahasiswa' ? number_format($stats['avg'], 1) : ($role === 'dosen' ? $stats['mahasiswa'] : $stats['total_users']) ?>
        </span>
        <span class="text-xs text-muted">
          <?= $role === 'mahasiswa' ? 'Rata-rata Nilai' : ($role === 'dosen' ? 'Total Mahasiswa Binaan' : 'Total Civitas') ?>
        </span>
      </div>
      <div class="progress-bar-wrapper mt-1">
        <div class="progress-bar-fill" style="width: 82%"></div>
      </div>
    </div>
    <div style="background:rgba(0,0,0,0.3);padding:10px 14px;border-radius:var(--radius-sm);border:1px solid var(--glass-border)">
      <div class="flex-between text-xs">
        <span class="text-muted"><i class="fas fa-server"></i> Server Kampus</span>
        <span class="font-mono" style="color:var(--accent-cyan)">Active (Port 8080/8000)</span>
      </div>
      <div class="flex-between text-xs mt-1">
        <span class="text-muted"><i class="fas fa-database"></i> Database MySQL</span>
        <span class="font-mono text-muted">nexus_campus</span>
      </div>
    </div>
  </div>

</div>

<!-- ── DETAIL DATA PER ROLE ────────────────────────────────────── -->
<?php if ($role === 'mahasiswa'): ?>

<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
    <div>
      <div class="stat-value"><?= $stats['matkul'] ?></div>
      <div class="stat-label">Mata Kuliah</div>
      <div class="stat-sub"><?= $stats['sks'] ?> SKS Aktif</div>
    </div>
  </div>
  <div class="stat-card cyan">
    <div class="stat-icon cyan"><i class="fas fa-chart-line"></i></div>
    <div>
      <div class="stat-value"><?= $stats['avg'] ?></div>
      <div class="stat-label">Rata-rata Nilai</div>
      <div class="stat-sub">Grade <?= $stats['avg'] >= 85 ? 'A' : ($stats['avg'] >= 75 ? 'B' : 'C') ?></div>
    </div>
  </div>
  <div class="stat-card <?= $stats['pending'] > 0 ? 'amber' : 'green' ?>">
    <div class="stat-icon <?= $stats['pending'] > 0 ? 'amber' : 'green' ?>">
      <i class="fas fa-<?= $stats['pending'] > 0 ? 'hourglass-half' : 'check-double' ?>"></i>
    </div>
    <div>
      <div class="stat-value"><?= $stats['pending'] ?></div>
      <div class="stat-label">Tugas Pending</div>
      <div class="stat-sub"><?= $stats['pending'] > 0 ? 'Segera kumpulkan' : 'Semua beres 🎉' ?></div>
    </div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon purple"><i class="fas fa-graduation-cap"></i></div>
    <div>
      <div class="stat-value"><?= $mhsData['semester'] ?? '7' ?></div>
      <div class="stat-label">Semester</div>
      <div class="stat-sub"><?= e($mhsData['prodi'] ?? 'Teknik Informatika') ?></div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Nilai Terkini -->
  <div class="card animate-fade-in-up">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-simple" style="color:var(--accent-cyan)"></i> Nilai Akademik Terkini</h3>
      <a href="<?= APP_URL ?>/modules/mahasiswa/nilai.php" class="btn btn-outline btn-sm">Lihat Transkrip</a>
    </div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr><th>Mata Kuliah</th><th>Nilai Akhir</th><th>Grade</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentNilai as $n): ?>
          <tr>
            <td>
              <strong><?= e($n['nama']) ?></strong>
              <div class="font-mono text-xs text-muted"><?= e($n['kode']) ?></div>
            </td>
            <td>
              <span class="font-mono" style="font-weight:700;font-size:1.05rem"><?= number_format($n['nilai_akhir'],1) ?></span>
              <div class="progress-bar-wrapper mt-1" style="width:70px">
                <div class="progress-bar-fill" style="width:<?= $n['nilai_akhir'] ?>%"></div>
              </div>
            </td>
            <td><span class="grade-pill grade-<?= e($n['grade']) ?>"><?= e($n['grade']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Upcoming Deadlines -->
  <div class="card animate-fade-in-up">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-clock" style="color:var(--accent-amber)"></i> Tugas & Tenggat Waktu</h3>
      <a href="<?= APP_URL ?>/modules/mahasiswa/tugas.php" class="btn btn-outline btn-sm">Daftar Tugas</a>
    </div>
    <?php foreach ($upcomingTugas as $t): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--glass-border)">
      <div>
        <div style="font-weight:700"><?= e($t['judul']) ?></div>
        <div class="text-xs text-muted mt-1"><i class="fas fa-book"></i> <?= e($t['matkul']) ?></div>
      </div>
      <div style="text-align:right">
        <?php if ($t['submitted']): ?>
        <span class="badge badge-success"><i class="fas fa-check"></i> Terkumpul</span>
        <?php else: ?>
        <span class="badge badge-warning"><i class="fas fa-hourglass"></i> Pending</span>
        <?php endif; ?>
        <div class="text-xs text-muted mt-1 font-mono"><?= date('d M, H:i', strtotime($t['deadline'])) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif ($role === 'dosen'): ?>

<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
    <div>
      <div class="stat-value"><?= $stats['matkul'] ?></div>
      <div class="stat-label">Mata Kuliah Diampu</div>
    </div>
  </div>
  <div class="stat-card cyan">
    <div class="stat-icon cyan"><i class="fas fa-users"></i></div>
    <div>
      <div class="stat-value"><?= $stats['mahasiswa'] ?></div>
      <div class="stat-label">Total Mahasiswa</div>
    </div>
  </div>
  <div class="stat-card <?= $stats['pending'] > 0 ? 'amber' : 'green' ?>">
    <div class="stat-icon <?= $stats['pending'] > 0 ? 'amber' : 'green' ?>">
      <i class="fas fa-clipboard-check"></i>
    </div>
    <div>
      <div class="stat-value"><?= $stats['pending'] ?></div>
      <div class="stat-label">Perlu Dinilai</div>
      <div class="stat-sub"><?= $stats['pending'] > 0 ? 'Tugas menunggu review' : 'Semua sudah dinilai' ?></div>
    </div>
  </div>
</div>

<div class="card animate-fade-in-up">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-chalkboard-user" style="color:var(--accent-blue)"></i> Kelas Pengajaran Aktif</h3>
    <a href="<?= APP_URL ?>/modules/dosen/matkul.php" class="btn btn-outline btn-sm">Lihat Semua</a>
  </div>
  <div class="grid-2">
    <?php foreach ($dosenMatkul as $mk): ?>
    <div class="stat-card blue" style="background:rgba(255,255,255,0.04)">
      <div class="stat-icon blue"><i class="fas fa-laptop-code"></i></div>
      <div style="flex:1">
        <h4 style="font-size:1.05rem;margin-bottom:4px"><?= e($mk['nama']) ?></h4>
        <div class="text-xs text-muted font-mono"><?= e($mk['kode']) ?> · <?= $mk['sks'] ?> SKS · Semester <?= $mk['semester'] ?></div>
        <div class="mt-2 flex-between">
          <span class="badge badge-cyan"><?= $mk['enrolled'] ?> Mahasiswa</span>
          <a href="<?= APP_URL ?>/modules/dosen/nilai.php?matkul=<?= $mk['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-pen-to-square"></i> Input Nilai
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php elseif ($role === 'admin'): ?>

<div class="stats-grid">
  <div class="stat-card cyan">
    <div class="stat-icon cyan"><i class="fas fa-users-gear"></i></div>
    <div>
      <div class="stat-value"><?= $stats['total_users'] ?></div>
      <div class="stat-label">Pengguna Sistem</div>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div>
    <div>
      <div class="stat-value"><?= $stats['total_mahasiswa'] ?></div>
      <div class="stat-label">Mahasiswa</div>
    </div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon purple"><i class="fas fa-chalkboard-user"></i></div>
    <div>
      <div class="stat-value"><?= $stats['total_dosen'] ?></div>
      <div class="stat-label">Dosen Pengajar</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fas fa-book"></i></div>
    <div>
      <div class="stat-value"><?= $stats['total_matkul'] ?></div>
      <div class="stat-label">Mata Kuliah</div>
    </div>
  </div>
</div>

<div class="card animate-fade-in-up">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-user-shield" style="color:var(--accent-cyan)"></i> Akun Pengguna Terbaru</h3>
    <a href="<?= APP_URL ?>/modules/admin/users.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Kelola Akun</a>
  </div>
  <div class="table-container">
    <table class="table">
      <thead>
        <tr><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td><strong><?= e($u['nama_lengkap']) ?></strong></td>
          <td><code class="font-mono text-xs"><?= e($u['username']) ?></code></td>
          <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'dosen' ? 'purple' : 'blue') ?>"><?= ucfirst($u['role']) ?></span></td>
          <td><span class="badge badge-<?= $u['status'] === 'aktif' ? 'success' : 'danger' ?>"><?= $u['status'] ?></span></td>
          <td><a href="<?= APP_URL ?>/modules/admin/users.php" class="btn btn-outline btn-sm">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?php require_once 'includes/layout_footer.php'; ?>
