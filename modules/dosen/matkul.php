<?php
/**
 * NEXUS CAMPUS PORTAL
 * Dosen — Mata Kuliah Yang Diampu
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('dosen');

$db = getDB();
$user = getCurrentUser();
$dosenId = $user['profile_id'];

$stmt = $db->prepare("
    SELECT mk.*, 
           (SELECT COUNT(*) FROM enrollment WHERE matkul_id = mk.id) as total_mhs,
           (SELECT COUNT(*) FROM tugas WHERE matkul_id = mk.id) as total_tugas
    FROM mata_kuliah mk
    WHERE mk.dosen_id = ?
    ORDER BY mk.semester ASC, mk.nama ASC
");
$stmt->execute([$dosenId]);
$matkulList = $stmt->fetchAll();

$pageTitle = 'Mata Kuliah Diampu';
$pageSection = 'matkul';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Mata Kuliah</span>
  </div>
  <h1 class="page-title">📖 Mata Kuliah yang Diampu</h1>
  <p class="page-subtitle">Daftar kelas pengajaran dan mahasiswa aktif semester ini</p>
</div>

<div class="grid-3">
  <?php foreach ($matkulList as $mk): ?>
  <div class="card animate-fade-in-up" style="display:flex;flex-direction:column;justify-content:space-between">
    <div>
      <div class="flex-between mb-2">
        <span class="badge badge-purple font-mono"><?= e($mk['kode']) ?></span>
        <span class="badge badge-cyan"><?= $mk['sks'] ?> SKS</span>
      </div>
      <h3 style="font-size:1.1rem;margin-bottom:8px"><?= e($mk['nama']) ?></h3>
      <p class="text-sm text-muted mb-3"><?= e($mk['deskripsi'] ?: 'Kurikulum pengajaran aktif.') ?></p>
    </div>
    <div>
      <div class="grid-2 mb-3" style="gap:8px">
        <div style="background:var(--bg-base);padding:8px;border-radius:6px;text-align:center">
          <div style="font-weight:700;font-size:1.1rem;color:var(--accent-cyan)"><?= $mk['total_mhs'] ?></div>
          <div class="text-xs text-muted">Mahasiswa</div>
        </div>
        <div style="background:var(--bg-base);padding:8px;border-radius:6px;text-align:center">
          <div style="font-weight:700;font-size:1.1rem;color:var(--accent-purple)"><?= $mk['total_tugas'] ?></div>
          <div class="text-xs text-muted">Tugas Aktif</div>
        </div>
      </div>
      <div class="flex-between">
        <a href="<?= APP_URL ?>/modules/dosen/nilai.php?matkul=<?= $mk['id'] ?>" class="btn btn-primary btn-sm">
          <i class="fas fa-pen-to-square"></i> Input Nilai
        </a>
        <a href="<?= APP_URL ?>/modules/dosen/forum.php?matkul=<?= $mk['id'] ?>" class="btn btn-outline btn-sm">
          <i class="fas fa-comments"></i> Forum
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
