<?php
/**
 * NEXUS CAMPUS PORTAL
 * Admin — Laporan Akademik
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('admin');

$db = getDB();

// Statistics
$totalMhs = $db->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();
$totalDosen = $db->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
$totalMatkul = $db->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn();
$totalSubmissions = $db->query("SELECT COUNT(*) FROM pengumpulan_tugas")->fetchColumn();

// Grade distribution
$gradeDist = $db->query("
    SELECT grade, COUNT(*) as count 
    FROM nilai 
    WHERE grade IS NOT NULL 
    GROUP BY grade 
    ORDER BY grade ASC
")->fetchAll();

// Top students
$topMhs = $db->query("
    SELECT u.nama_lengkap, m.nim, m.prodi, AVG(n.nilai_akhir) as rerata
    FROM nilai n
    JOIN enrollment e ON n.enrollment_id = e.id
    JOIN mahasiswa m ON e.mahasiswa_id = m.id
    JOIN users u ON m.user_id = u.id
    GROUP BY m.id
    ORDER BY rerata DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Laporan Akademik';
$pageSection = 'laporan';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Laporan</span>
  </div>
  <h1 class="page-title">📈 Laporan & Statistik Kampus</h1>
  <p class="page-subtitle">Metrik performa akademik, partisipasi, dan statistik nilai keseluruhan</p>
</div>

<div class="stats-grid mb-3">
  <div class="stat-card cyan">
    <div class="stat-icon cyan"><i class="fas fa-graduation-cap"></i></div>
    <div>
      <div class="stat-value"><?= $totalMhs ?></div>
      <div class="stat-label">Total Mahasiswa</div>
    </div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon purple"><i class="fas fa-chalkboard-user"></i></div>
    <div>
      <div class="stat-value"><?= $totalDosen ?></div>
      <div class="stat-label">Total Dosen</div>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fas fa-book-bookmark"></i></div>
    <div>
      <div class="stat-value"><?= $totalMatkul ?></div>
      <div class="stat-label">Mata Kuliah Aktif</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fas fa-file-circle-check"></i></div>
    <div>
      <div class="stat-value"><?= $totalSubmissions ?></div>
      <div class="stat-label">Tugas Terkumpul</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Grade Distribution -->
  <div class="card animate-fade-in-up">
    <h3 class="card-title mb-3"><i class="fas fa-chart-column" style="color:var(--accent-cyan)"></i> Distribusi Grade Mahasiswa</h3>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Grade</th>
            <th>Jumlah Entri</th>
            <th>Visualisasi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($gradeDist as $row): ?>
          <tr>
            <td><span class="grade-pill grade-<?= e($row['grade']) ?>"><?= e($row['grade']) ?></span></td>
            <td><strong><?= $row['count'] ?></strong> nilai</td>
            <td>
              <div class="progress-bar-wrapper" style="width:140px">
                <div class="progress-bar-fill" style="width:<?= min(100, $row['count'] * 15) ?>%"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Performers -->
  <div class="card animate-fade-in-up">
    <h3 class="card-title mb-3"><i class="fas fa-award" style="color:var(--accent-amber)"></i> Mahasiswa Prestasi Tertinggi</h3>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Mahasiswa</th>
            <th>Prodi</th>
            <th>Rata-rata</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topMhs as $idx => $tm): ?>
          <tr>
            <td>
              <div style="font-weight:600"><?= ($idx+1) ?>. <?= e($tm['nama_lengkap']) ?></div>
              <div class="font-mono text-xs text-muted"><?= e($tm['nim']) ?></div>
            </td>
            <td><span class="badge badge-blue"><?= e($tm['prodi']) ?></span></td>
            <td><strong class="font-mono text-success" style="color:var(--accent-cyan)"><?= number_format($tm['rerata'], 2) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
