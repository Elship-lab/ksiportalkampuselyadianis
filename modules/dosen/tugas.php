<?php
/**
 * NEXUS CAMPUS PORTAL
 * Dosen — Kelola Tugas & Penilaian Tugas
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('dosen');

$db = getDB();
$user = getCurrentUser();
$dosenId = $user['profile_id'];

$msg = $err = '';

// Handle Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tugas'])) {
    $matkulId = (int)$_POST['matkul_id'];
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $deadline = trim($_POST['deadline']);
    $maxScore = (float)($_POST['max_score'] ?? 100);

    if (!$matkulId || !$judul || !$deadline) {
        $err = 'Mata kuliah, judul, dan deadline wajib diisi.';
    } else {
        $stmt = $db->prepare("INSERT INTO tugas (matkul_id, judul, deskripsi, deadline, max_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$matkulId, $judul, $deskripsi, $deadline, $maxScore]);
        $msg = 'Tugas baru berhasil dibuat dan dipublikasikan ke mahasiswa! 🎯';
    }
}

// Handle Grade Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nilai_submission'])) {
    $subId = (int)$_POST['submission_id'];
    $nilai = min(100, max(0, (float)$_POST['nilai']));
    $feedback = trim($_POST['feedback'] ?? '');

    $stmt = $db->prepare("UPDATE pengumpulan_tugas SET nilai = ?, feedback = ?, status = 'graded', graded_at = NOW() WHERE id = ?");
    $stmt->execute([$nilai, $feedback, $subId]);
    $msg = 'Nilai pengumpulan tugas berhasil disimpan! ⭐';
}

// Get Courses of this Dosen
$stmt = $db->prepare("SELECT id, nama, kode FROM mata_kuliah WHERE dosen_id = ? ORDER BY nama ASC");
$stmt->execute([$dosenId]);
$matkulList = $stmt->fetchAll();

// Get Assignments
$stmt = $db->prepare("
    SELECT t.*, mk.nama as matkul_nama, mk.kode as matkul_kode,
           (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id) as total_submit,
           (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id AND status = 'submitted') as pending_grade
    FROM tugas t
    JOIN mata_kuliah mk ON t.matkul_id = mk.id
    WHERE mk.dosen_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$dosenId]);
$tugasList = $stmt->fetchAll();

// Selected task submissions to review
$selectedTugasId = (int)($_GET['review'] ?? 0);
$reviewSubmissions = [];
$selectedTugasInfo = null;

if ($selectedTugasId) {
    $stmt = $db->prepare("SELECT t.*, mk.nama as matkul_nama FROM tugas t JOIN mata_kuliah mk ON t.matkul_id = mk.id WHERE t.id = ? AND mk.dosen_id = ?");
    $stmt->execute([$selectedTugasId, $dosenId]);
    $selectedTugasInfo = $stmt->fetch();

    if ($selectedTugasInfo) {
        $stmt = $db->prepare("
            SELECT pt.*, u.nama_lengkap, m.nim
            FROM pengumpulan_tugas pt
            JOIN mahasiswa m ON pt.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            WHERE pt.tugas_id = ?
            ORDER BY pt.submitted_at DESC
        ");
        $stmt->execute([$selectedTugasId]);
        $reviewSubmissions = $stmt->fetchAll();
    }
}

$pageTitle = 'Kelola Tugas';
$pageSection = 'tugas';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Kelola Tugas</span>
  </div>
  <div class="flex-between">
    <div>
      <h1 class="page-title">📝 Kelola Tugas Mahasiswa</h1>
      <p class="page-subtitle">Buat penugasan baru dan nilai pengumpulan tugas mahasiswa</p>
    </div>
    <button class="btn btn-primary" onclick="NexusModal.open('modalTambahTugas')">
      <i class="fas fa-plus"></i> Tambah Tugas Baru
    </button>
  </div>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="4000"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<?php if ($selectedTugasInfo): ?>
<!-- Review Submissions View -->
<div class="card animate-fade-in-up mb-3">
  <div class="flex-between mb-3">
    <div>
      <h3 style="font-size:1.2rem;margin-bottom:4px"><?= e($selectedTugasInfo['judul']) ?></h3>
      <span class="badge badge-blue"><?= e($selectedTugasInfo['matkul_nama']) ?></span>
      <span class="text-sm text-muted ml-2">Deadline: <?= date('d M Y, H:i', strtotime($selectedTugasInfo['deadline'])) ?></span>
    </div>
    <a href="tugas.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Tugas</a>
  </div>

  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>NIM & Mahasiswa</th>
          <th>Waktu Kirim</th>
          <th>File Pengumpulan</th>
          <th>Catatan Mahasiswa</th>
          <th>Nilai & Feedback</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reviewSubmissions)): ?>
        <tr><td colspan="5" class="text-center text-muted">Belum ada mahasiswa yang mengumpulkan tugas ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($reviewSubmissions as $sub): ?>
        <tr>
          <td>
            <strong><?= e($sub['nama_lengkap']) ?></strong>
            <div class="font-mono text-xs text-muted"><?= e($sub['nim']) ?></div>
          </td>
          <td>
            <div class="text-xs"><?= date('d/m/Y H:i', strtotime($sub['submitted_at'])) ?></div>
            <span class="badge badge-<?= $sub['status'] === 'late' ? 'warning' : 'success' ?>">
              <?= ucfirst($sub['status']) ?>
            </span>
          </td>
          <td>
            <?php if ($sub['file_path']): ?>
            <a href="<?= APP_URL . '/' . e($sub['file_path']) ?>" download class="btn btn-outline btn-sm font-mono">
              <i class="fas fa-download"></i> Unduh File
            </a>
            <?php else: ?>
            <span class="text-muted text-xs">Tanpa lampiran</span>
            <?php endif; ?>
          </td>
          <td><p class="text-xs text-muted mb-0"><?= e($sub['catatan'] ?: '-') ?></p></td>
          <td>
            <form method="POST" style="display:flex;gap:8px;align-items:center">
              <input type="hidden" name="nilai_submission" value="1">
              <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
              <input type="number" name="nilai" class="form-control" style="width:75px"
                     value="<?= $sub['nilai'] !== null ? $sub['nilai'] : '' ?>" placeholder="0-100" min="0" max="100" step="0.5" required>
              <input type="text" name="feedback" class="form-control" style="width:160px"
                     value="<?= e($sub['feedback'] ?? '') ?>" placeholder="Catatan/feedback">
              <button type="submit" class="btn btn-cyan btn-sm"><i class="fas fa-check"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>

<!-- Assignment List Table -->
<div class="card animate-fade-in-up">
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Judul Tugas</th>
          <th>Mata Kuliah</th>
          <th>Deadline</th>
          <th>Pengumpulan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tugasList)): ?>
        <tr><td colspan="5" class="text-center text-muted">Belum ada tugas dibuat. Klik "Tambah Tugas Baru".</td></tr>
        <?php endif; ?>
        <?php foreach ($tugasList as $t): ?>
        <tr>
          <td>
            <strong><?= e($t['judul']) ?></strong>
            <div class="text-xs text-muted"><?= e(substr($t['deskripsi'], 0, 70)) ?>...</div>
          </td>
          <td>
            <div><?= e($t['matkul_nama']) ?></div>
            <code class="text-xs"><?= e($t['matkul_kode']) ?></code>
          </td>
          <td class="text-xs font-mono">
            <?= date('d M Y, H:i', strtotime($t['deadline'])) ?>
          </td>
          <td>
            <span class="badge badge-cyan"><?= $t['total_submit'] ?> Terkumpul</span>
            <?php if ($t['pending_grade'] > 0): ?>
            <span class="badge badge-warning"><?= $t['pending_grade'] ?> Perlu Dinilai</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="?review=<?= $t['id'] ?>" class="btn btn-outline btn-sm">
              <i class="fas fa-list-check"></i> Periksa & Nilai
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Modal Add Task -->
<div class="modal-overlay" id="modalTambahTugas">
  <div class="modal">
    <div class="modal-header">
      <h3 class="card-title"><i class="fas fa-plus-circle" style="color:var(--accent-cyan)"></i> Tambah Tugas Baru</h3>
      <button class="modal-close" onclick="NexusModal.close('modalTambahTugas')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="tambah_tugas" value="1">
      <div class="form-group">
        <label class="form-label">Mata Kuliah</label>
        <select name="matkul_id" class="form-control" required>
          <?php foreach ($matkulList as $mk): ?>
          <option value="<?= $mk['id'] ?>"><?= e($mk['kode']) ?> - <?= e($mk['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Judul Tugas</label>
        <input type="text" name="judul" class="form-control" placeholder="Contoh: Implementasi REST API" required>
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi & Instruksi</label>
        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Detail instruksi pengerjaan..."></textarea>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Deadline</label>
          <input type="datetime-local" name="deadline" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Skor Maksimal</label>
          <input type="number" name="max_score" class="form-control" value="100" required>
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="NexusModal.close('modalTambahTugas')">Batal</button>
        <button type="submit" class="btn btn-cyan"><i class="fas fa-floppy-disk"></i> Simpan Tugas</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
