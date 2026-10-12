<?php
/**
 * NEXUS CAMPUS PORTAL
 * Mahasiswa — Nilai (Grades)
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('mahasiswa');

$db        = getDB();

// VULNERABILITY: IDOR (Insecure Direct Object Reference) / Kontrol Akses Lemah
// Mahasiswa bisa mengintip transkrip & nilai mahasiswa lain hanya dengan mengganti parameter ?mhs_id=
$targetProfileId = isset($_GET['mhs_id']) ? (int)$_GET['mhs_id'] : getCurrentUser()['profile_id'];

// Get all enrolled courses with grades
$stmt = $db->prepare("
    SELECT
        mk.kode, mk.nama, mk.sks, mk.semester,
        u.nama_lengkap as dosen_nama,
        n.nilai_tugas, n.nilai_uts, n.nilai_uas,
        n.nilai_akhir, n.grade,
        e.status as enroll_status
    FROM enrollment e
    JOIN mata_kuliah mk ON e.matkul_id = mk.id
    JOIN dosen d ON mk.dosen_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN nilai n ON e.id = n.enrollment_id
    WHERE e.mahasiswa_id = ?
    ORDER BY mk.semester DESC, mk.nama ASC
");
$stmt->execute([$targetProfileId]);
$nilaiList = $stmt->fetchAll();

// Calculate IPK
$totalBobot = 0; $totalSks = 0;
$gradePoint = ['A'=>4,'B'=>3,'C'=>2,'D'=>1,'E'=>0];
foreach ($nilaiList as $n) {
    if ($n['grade']) {
        $totalBobot += ($gradePoint[$n['grade']] ?? 0) * $n['sks'];
        $totalSks   += $n['sks'];
    }
}
$ipk = $totalSks > 0 ? round($totalBobot / $totalSks, 2) : 0;

// Grade distribution
$gradeDist = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0];
foreach ($nilaiList as $n) {
    if (isset($gradeDist[$n['grade']])) $gradeDist[$n['grade']]++;
}

$pageTitle   = 'Nilai Saya';
$pageSection = 'nilai';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Nilai Saya</span>
  </div>
  <h1 class="page-title">📊 Transkrip Nilai</h1>
  <p class="page-subtitle">Rekap nilai seluruh mata kuliah yang diambil</p>
</div>

<!-- IPK Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card cyan">
    <div class="stat-icon cyan"><i class="fas fa-star"></i></div>
    <div>
      <div class="stat-value"><?= number_format($ipk,2) ?></div>
      <div class="stat-label">IPK</div>
      <div class="stat-sub"><?= $ipk>=3.5?'Cumlaude':($ipk>=3.0?'Memuaskan':'Cukup') ?></div>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
    <div>
      <div class="stat-value"><?= count($nilaiList) ?></div>
      <div class="stat-label">Mata Kuliah</div>
    </div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon purple"><i class="fas fa-layer-group"></i></div>
    <div>
      <div class="stat-value"><?= $totalSks ?></div>
      <div class="stat-label">Total SKS</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fas fa-trophy"></i></div>
    <div>
      <div class="stat-value"><?= $gradeDist['A'] ?></div>
      <div class="stat-label">Grade A</div>
    </div>
  </div>
</div>

<!-- Grade Distribution -->
<div class="card animate-fade-in-up mb-3">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-chart-pie" style="color:var(--accent-purple)"></i> Distribusi Grade</h3>
  </div>
  <div style="display:flex;gap:24px;flex-wrap:wrap">
    <?php foreach ($gradeDist as $g => $count): ?>
    <div style="text-align:center;min-width:60px">
      <div class="grade-pill grade-<?= $g ?>" style="width:44px;height:44px;font-size:1rem;margin:0 auto 8px"><?= $g ?></div>
      <div style="font-size:1.3rem;font-weight:800"><?= $count ?></div>
      <div class="text-muted text-xs">Matkul</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Nilai Table -->
<div class="card animate-fade-in-up">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-table" style="color:var(--accent-cyan)"></i> Detail Nilai</h3>
    <div class="input-group" style="width:220px">
      <i class="input-icon fas fa-search"></i>
      <input type="text" class="form-control" id="tableSearch" placeholder="Cari mata kuliah...">
    </div>
  </div>
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Mata Kuliah</th>
          <th>SKS</th>
          <th>Dosen</th>
          <th>Tugas (30%)</th>
          <th>UTS (30%)</th>
          <th>UAS (40%)</th>
          <th>Nilai Akhir</th>
          <th>Grade</th>
          <th>Poin</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($nilaiList as $n): ?>
        <tr class="searchable-row">
          <td><code><?= e($n['kode']) ?></code></td>
          <td>
            <div style="font-weight:600"><?= e($n['nama']) ?></div>
            <div class="text-muted text-xs">Semester <?= $n['semester'] ?></div>
          </td>
          <td class="text-center"><?= $n['sks'] ?></td>
          <td class="text-sm"><?= e($n['dosen_nama']) ?></td>
          <?php if ($n['nilai_akhir'] !== null): ?>
          <td>
            <div class="flex-center gap-sm">
              <span style="font-weight:600;font-family:'JetBrains Mono',monospace"><?= number_format($n['nilai_tugas'],1) ?></span>
            </div>
            <div class="progress-bar-wrapper mt-1" style="width:60px">
              <div class="progress-bar-fill" data-width="<?= $n['nilai_tugas'] ?>"></div>
            </div>
          </td>
          <td>
            <span style="font-weight:600;font-family:'JetBrains Mono',monospace"><?= number_format($n['nilai_uts'],1) ?></span>
            <div class="progress-bar-wrapper mt-1" style="width:60px">
              <div class="progress-bar-fill" data-width="<?= $n['nilai_uts'] ?>"></div>
            </div>
          </td>
          <td>
            <span style="font-weight:600;font-family:'JetBrains Mono',monospace"><?= number_format($n['nilai_uas'],1) ?></span>
            <div class="progress-bar-wrapper mt-1" style="width:60px">
              <div class="progress-bar-fill" data-width="<?= $n['nilai_uas'] ?>"></div>
            </div>
          </td>
          <td>
            <span style="font-size:1.1rem;font-weight:800;font-family:'JetBrains Mono',monospace;
                         color:<?= gradeColor($n['grade']) ?>">
              <?= number_format($n['nilai_akhir'],2) ?>
            </span>
          </td>
          <td><span class="grade-pill grade-<?= e($n['grade']) ?>"><?= e($n['grade']) ?></span></td>
          <td class="text-center font-mono"><?= $gradePoint[$n['grade']] ?? 0 ?></td>
          <?php else: ?>
          <td colspan="6" class="text-center text-muted" style="font-style:italic">
            <i class="fas fa-clock"></i> Belum dinilai
          </td>
          <td>—</td>
          <td>—</td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:var(--bg-base)">
          <td colspan="2" style="font-weight:700;padding:12px 14px">
            <i class="fas fa-calculator" style="color:var(--accent-cyan)"></i> IPK Total
          </td>
          <td style="font-weight:700"><?= $totalSks ?></td>
          <td colspan="5"></td>
          <td colspan="2" style="font-weight:800;font-size:1.1rem;color:var(--accent-cyan);font-family:'JetBrains Mono',monospace">
            <?= number_format($ipk,2) ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
