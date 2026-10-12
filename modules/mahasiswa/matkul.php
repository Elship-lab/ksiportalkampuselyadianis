<?php
/**
 * NEXUS CAMPUS PORTAL
 * Mahasiswa — Mata Kuliah
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('mahasiswa');

$db = getDB();
$user = getCurrentUser();
$profileId = $user['profile_id'];

$stmt = $db->prepare("
    SELECT mk.*, u.nama_lengkap as dosen_nama, d.nidn, e.status as enroll_status, e.tahun_ajaran
    FROM enrollment e
    JOIN mata_kuliah mk ON e.matkul_id = mk.id
    JOIN dosen d ON mk.dosen_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE e.mahasiswa_id = ?
    ORDER BY mk.semester ASC, mk.nama ASC
");
$stmt->execute([$profileId]);
$matkulList = $stmt->fetchAll();

$pageTitle = 'Mata Kuliah Saya';
$pageSection = 'matkul';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Mata Kuliah</span>
  </div>
  <h1 class="page-title">📚 Mata Kuliah Terdaftar</h1>
  <p class="page-subtitle">Daftar kelas dan kurikulum yang sedang Anda tempuh</p>
</div>

<div class="grid-3">
  <?php foreach ($matkulList as $mk): ?>
  <div class="card animate-fade-in-up" style="display:flex;flex-direction:column;justify-content:space-between">
    <div>
      <div class="flex-between mb-2">
        <span class="badge badge-cyan font-mono"><?= e($mk['kode']) ?></span>
        <span class="badge badge-purple"><?= $mk['sks'] ?> SKS</span>
      </div>
      <h3 style="font-size:1.1rem;margin-bottom:8px"><?= e($mk['nama']) ?></h3>
      <p class="text-sm text-muted mb-3"><?= e($mk['deskripsi'] ?? 'Mata kuliah wajib program studi.') ?></p>
    </div>
    <div>
      <div style="border-top:1px solid var(--border);padding-top:12px;margin-bottom:12px">
        <div class="text-xs text-muted mb-1"><i class="fas fa-chalkboard-user"></i> Pengampu</div>
        <div style="font-weight:600;font-size:0.85rem"><?= e($mk['dosen_nama']) ?></div>
        <div class="text-xs text-muted font-mono">NIDN: <?= e($mk['nidn']) ?></div>
      </div>
      <div class="flex-between">
        <span class="badge badge-success"><i class="fas fa-circle-check"></i> <?= ucfirst($mk['enroll_status']) ?></span>
        <a href="<?= APP_URL ?>/modules/mahasiswa/forum.php?matkul=<?= $mk['id'] ?>" class="btn btn-outline btn-sm">
          <i class="fas fa-comments"></i> Forum
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
