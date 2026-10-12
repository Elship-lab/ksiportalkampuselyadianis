<?php
/**
 * NEXUS CAMPUS PORTAL
 * Mahasiswa — Tugas (Assignment Submission)
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('mahasiswa');

$db        = getDB();
$user      = getCurrentUser();
$profileId = $user['profile_id'];

$msg = $err = '';

// Handle submission upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tugas'])) {
    $tugasId = (int)$_POST['tugas_id'];
    $catatan = trim($_POST['catatan'] ?? '');

    // Validate tugas exists and not expired
    $t = $db->prepare("SELECT * FROM tugas WHERE id = ?");
    $t->execute([$tugasId]);
    $tugas = $t->fetch();

    if (!$tugas) {
        $err = 'Tugas tidak ditemukan.';
    } else {
        $isLate  = new DateTime() > new DateTime($tugas['deadline']);
        $status  = $isLate ? 'late' : 'submitted';
        $filePath = null;

        // Handle file upload
        if (!empty($_FILES['file']['name'])) {
            // VULNERABILITY: Validasi Berkas Lemah (Weak File Validation)
            // Memperbolehkan ekstensi berbahaya seperti .php, .phtml, .html, .svg
            $allowed  = ['pdf','doc','docx','zip','rar','txt','py','php','phtml','html','svg','sh'];
            $ext      = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $maxBytes = MAX_FILE_MB * 1024 * 1024;

            if (!in_array($ext, $allowed)) {
                $err = 'Tipe file tidak diizinkan. Gunakan format yang didukung.';
            } elseif ($_FILES['file']['size'] > $maxBytes) {
                $err = 'File terlalu besar. Maksimal ' . MAX_FILE_MB . 'MB.';
            } else {
                $newName  = 'tugas_' . $profileId . '_' . $tugasId . '_' . time() . '.' . $ext;
                $destPath = UPLOAD_DIR . $newName;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
                    $filePath = 'uploads/tugas/' . $newName;
                } else {
                    $err = 'Gagal menyimpan file. Pastikan folder uploads/tugas dapat ditulis.';
                }
            }
        }

        if (!$err) {
            // Upsert submission
            $check = $db->prepare("SELECT id FROM pengumpulan_tugas WHERE tugas_id=? AND mahasiswa_id=?");
            $check->execute([$tugasId, $profileId]);
            $existing = $check->fetchColumn();

            if ($existing) {
                $upd = $db->prepare("
                    UPDATE pengumpulan_tugas
                    SET catatan=?, status=?, submitted_at=NOW()
                        " . ($filePath ? ", file_path=?" : "") . "
                    WHERE id=?
                ");
                $params = [$catatan, $status];
                if ($filePath) $params[] = $filePath;
                $params[] = $existing;
                $upd->execute($params);
                $msg = 'Tugas berhasil diperbarui! ' . ($isLate ? '⚠️ Terlambat.' : '✅');
            } else {
                $ins = $db->prepare("
                    INSERT INTO pengumpulan_tugas (tugas_id, mahasiswa_id, file_path, catatan, status)
                    VALUES (?,?,?,?,?)
                ");
                $ins->execute([$tugasId, $profileId, $filePath, $catatan, $status]);
                $msg = 'Tugas berhasil dikumpulkan! ' . ($isLate ? '⚠️ Catatan: Terlambat.' : '🎉');
            }
        }
    }
}

// Fetch all assignments for enrolled courses
$stmt = $db->prepare("
    SELECT
        t.id as tugas_id, t.judul, t.deskripsi, t.deadline, t.max_score,
        mk.nama as matkul_nama, mk.kode as matkul_kode,
        u.nama_lengkap as dosen_nama,
        pt.id as submission_id, pt.status as sub_status,
        pt.nilai as sub_nilai, pt.feedback, pt.submitted_at, pt.file_path
    FROM tugas t
    JOIN mata_kuliah mk ON t.matkul_id = mk.id
    JOIN dosen d ON mk.dosen_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN enrollment e ON mk.id = e.matkul_id AND e.mahasiswa_id = ?
    LEFT JOIN pengumpulan_tugas pt ON t.id = pt.tugas_id AND pt.mahasiswa_id = ?
    ORDER BY t.deadline ASC
");
$stmt->execute([$profileId, $profileId]);
$tugasList = $stmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'semua';

$pageTitle   = 'Tugas';
$pageSection = 'tugas';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Tugas</span>
  </div>
  <h1 class="page-title">📁 Tugas & Pengumpulan</h1>
  <p class="page-subtitle">Lihat dan kumpulkan tugas dari seluruh mata kuliah</p>
</div>

<?php if ($msg): ?>
<div class="alert alert-success" data-autohide="5000"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div>
<?php endif; ?>

<!-- Stats quick bar -->
<?php
$pending = array_filter($tugasList, fn($t) => !$t['submission_id'] && new DateTime() < new DateTime($t['deadline']));
$submitted = array_filter($tugasList, fn($t) => $t['submission_id']);
$graded = array_filter($tugasList, fn($t) => $t['sub_status'] === 'graded');
?>
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fas fa-list"></i></div>
    <div><div class="stat-value"><?= count($tugasList) ?></div><div class="stat-label">Total Tugas</div></div>
  </div>
  <div class="stat-card amber">
    <div class="stat-icon amber"><i class="fas fa-hourglass-half"></i></div>
    <div><div class="stat-value"><?= count($pending) ?></div><div class="stat-label">Belum Dikumpulkan</div></div>
  </div>
  <div class="stat-card cyan">
    <div class="stat-icon cyan"><i class="fas fa-paper-plane"></i></div>
    <div><div class="stat-value"><?= count($submitted) ?></div><div class="stat-label">Dikumpulkan</div></div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fas fa-check-double"></i></div>
    <div><div class="stat-value"><?= count($graded) ?></div><div class="stat-label">Sudah Dinilai</div></div>
  </div>
</div>

<!-- Tabs -->
<div class="tabs">
  <a href="?tab=semua"    class="tab-item <?= $activeTab==='semua'?'active':'' ?>">Semua (<?= count($tugasList) ?>)</a>
  <a href="?tab=pending"  class="tab-item <?= $activeTab==='pending'?'active':'' ?>">Pending (<?= count($pending) ?>)</a>
  <a href="?tab=dinilai"  class="tab-item <?= $activeTab==='dinilai'?'active':'' ?>">Dinilai (<?= count($graded) ?>)</a>
</div>

<!-- Assignments List -->
<?php foreach ($tugasList as $tugas):
    $now     = new DateTime();
    $dl      = new DateTime($tugas['deadline']);
    $isLate  = $now > $dl;
    $dlStatus = deadlineStatus($tugas['deadline']);

    // Filter tab
    if ($activeTab === 'pending'  && ($tugas['submission_id'] || $isLate)) continue;
    if ($activeTab === 'dinilai'  && $tugas['sub_status'] !== 'graded') continue;
?>
<div class="card animate-fade-in-up mb-2">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
    <div style="flex:1">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
        <h3 style="font-size:1rem;margin:0"><?= e($tugas['judul']) ?></h3>
        <?php if ($tugas['sub_status'] === 'graded'): ?>
          <span class="badge badge-success"><i class="fas fa-star"></i> Dinilai</span>
        <?php elseif ($tugas['submission_id']): ?>
          <span class="badge badge-cyan"><i class="fas fa-check"></i> Dikumpulkan</span>
          <?php if ($tugas['sub_status'] === 'late'): ?>
          <span class="badge badge-warning"><i class="fas fa-clock"></i> Terlambat</span>
          <?php endif; ?>
        <?php elseif ($isLate): ?>
          <span class="badge badge-danger"><i class="fas fa-times"></i> Melewati Deadline</span>
        <?php else: ?>
          <span class="badge badge-warning"><i class="fas fa-hourglass"></i> Belum Dikumpulkan</span>
        <?php endif; ?>
      </div>

      <div class="flex-center gap-md mb-2" style="flex-wrap:wrap">
        <span class="text-sm text-muted"><i class="fas fa-book"></i> <?= e($tugas['matkul_nama']) ?> (<?= e($tugas['matkul_kode']) ?>)</span>
        <span class="text-sm text-muted"><i class="fas fa-user-tie"></i> <?= e($tugas['dosen_nama']) ?></span>
        <span class="text-sm <?= $isLate ? 'text-muted' : '' ?>">
          <i class="fas <?= $dlStatus['icon'] ?>"></i>
          Deadline: <?= date('d M Y, H:i', strtotime($tugas['deadline'])) ?>
          <span class="badge <?= $dlStatus['class'] ?>"><?= $dlStatus['label'] ?></span>
        </span>
      </div>

      <?php if ($tugas['deskripsi']): ?>
      <p class="text-sm" style="color:var(--text-secondary);margin-bottom:12px"><?= e($tugas['deskripsi']) ?></p>
      <?php endif; ?>

      <!-- If graded, show score -->
      <?php if ($tugas['sub_status'] === 'graded' && $tugas['sub_nilai'] !== null): ?>
      <div style="background:var(--bg-base);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:16px">
          <div style="text-align:center">
            <div style="font-size:2rem;font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--accent-cyan)">
              <?= number_format($tugas['sub_nilai'],1) ?>
            </div>
            <div class="text-xs text-muted">dari <?= number_format($tugas['max_score'],0) ?></div>
          </div>
          <?php if ($tugas['feedback']): ?>
          <div style="flex:1">
            <div class="text-xs text-muted mb-1"><i class="fas fa-comment-dots"></i> Feedback Dosen:</div>
            <div class="text-sm" style="color:var(--text-secondary);font-style:italic">"<?= e($tugas['feedback']) ?>"</div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Submission form or status -->
      <?php if (!$isLate || $tugas['submission_id']): ?>
      <details <?= !$tugas['submission_id'] ? 'open' : '' ?>>
        <summary style="cursor:pointer;font-size:0.82rem;font-weight:600;color:var(--accent-cyan);margin-bottom:8px">
          <i class="fas fa-<?= $tugas['submission_id'] ? 'edit' : 'upload' ?>"></i>
          <?= $tugas['submission_id'] ? 'Perbarui Pengumpulan' : 'Kumpulkan Tugas' ?>
        </summary>
        <form method="POST" enctype="multipart/form-data" style="margin-top:12px">
          <input type="hidden" name="submit_tugas" value="1">
          <input type="hidden" name="tugas_id" value="<?= $tugas['tugas_id'] ?>">

          <div class="form-group">
            <div class="upload-zone" style="padding:24px">
              <input type="file" name="file" style="display:none"
                accept=".pdf,.doc,.docx,.zip,.rar,.txt,.py,.php,.java,.cpp">
              <div class="upload-label" style="pointer-events:none">
                <i class="fas fa-cloud-arrow-up" style="font-size:2rem;color:var(--accent-blue);margin-bottom:8px"></i><br>
                <?php if ($tugas['file_path']): ?>
                <strong style="color:var(--accent-cyan)">File sebelumnya:</strong><br>
                <small class="text-muted"><?= e(basename($tugas['file_path'])) ?></small><br>
                <small class="text-muted">Klik untuk ganti file</small>
                <?php else: ?>
                <strong>Drag & drop atau klik untuk pilih file</strong><br>
                <small class="text-muted">PDF, DOC, ZIP, PY, PHP, JAVA, CPP · Max <?= MAX_FILE_MB ?>MB</small>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Catatan (opsional)</label>
            <textarea name="catatan" class="form-control" rows="3"
              placeholder="Tambahkan catatan untuk dosen..."><?= e($tugas['catatan'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-cyan">
            <i class="fas fa-paper-plane"></i>
            <?= $tugas['submission_id'] ? 'Perbarui' : 'Kumpulkan Sekarang' ?>
          </button>
        </form>
      </details>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($tugasList)): ?>
<div class="empty-state card">
  <div class="empty-state-icon">📋</div>
  <div class="empty-state-text">Belum ada tugas untuk mata kuliah yang kamu ikuti.</div>
</div>
<?php endif; ?>

<?php require_once '../../includes/layout_footer.php'; ?>
