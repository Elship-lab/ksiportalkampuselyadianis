<?php
/**
 * NEXUS CAMPUS PORTAL
 * Dosen — Input Nilai (Grade Entry)
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('dosen');

$db      = getDB();
$user    = getCurrentUser();
$dosenId = $user['profile_id'];

$msg = $err = '';

// Handle grade save (bulk update per matkul)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_nilai'])) {
    $matkulId = (int)$_POST['matkul_id'];

    // Verify this matkul belongs to this dosen
    $chk = $db->prepare("SELECT id FROM mata_kuliah WHERE id = ? AND dosen_id = ?");
    $chk->execute([$matkulId, $dosenId]);
    if (!$chk->fetchColumn()) {
        $err = 'Akses ditolak.';
    } else {
        $grades = $_POST['grades'] ?? [];
        $saved  = 0;
        foreach ($grades as $enrollId => $data) {
            $tugas = min(100, max(0, (float)($data['tugas'] ?? 0)));
            $uts   = min(100, max(0, (float)($data['uts']   ?? 0)));
            $uas   = min(100, max(0, (float)($data['uas']   ?? 0)));

            // Upsert nilai
            $check = $db->prepare("SELECT id FROM nilai WHERE enrollment_id = ?");
            $check->execute([$enrollId]);
            if ($check->fetchColumn()) {
                $db->prepare("
                    UPDATE nilai SET nilai_tugas=?, nilai_uts=?, nilai_uas=?, updated_by=?
                    WHERE enrollment_id=?
                ")->execute([$tugas, $uts, $uas, $user['id'], $enrollId]);
            } else {
                $db->prepare("
                    INSERT INTO nilai (enrollment_id, nilai_tugas, nilai_uts, nilai_uas, updated_by)
                    VALUES (?,?,?,?,?)
                ")->execute([$enrollId, $tugas, $uts, $uas, $user['id']]);
            }
            $saved++;
        }
        $msg = "✅ $saved nilai berhasil disimpan!";
    }
}

// Get dosen's matkul list
$stmt = $db->prepare("
    SELECT mk.*, COUNT(e.id) as enrolled
    FROM mata_kuliah mk
    LEFT JOIN enrollment e ON mk.id = e.matkul_id
    WHERE mk.dosen_id = ?
    GROUP BY mk.id
    ORDER BY mk.semester DESC, mk.nama ASC
");
$stmt->execute([$dosenId]);
$matkulList = $stmt->fetchAll();

// Selected matkul
$selectedId = (int)($_GET['matkul'] ?? ($matkulList[0]['id'] ?? 0));
$selectedMatkul = null;
$students = [];

if ($selectedId) {
    $stmt = $db->prepare("SELECT * FROM mata_kuliah WHERE id = ? AND dosen_id = ?");
    $stmt->execute([$selectedId, $dosenId]);
    $selectedMatkul = $stmt->fetch();

    if ($selectedMatkul) {
        $stmt = $db->prepare("
            SELECT
                e.id as enrollment_id,
                m.nim, u.nama_lengkap, m.semester as student_semester,
                n.nilai_tugas, n.nilai_uts, n.nilai_uas,
                n.nilai_akhir, n.grade
            FROM enrollment e
            JOIN mahasiswa m ON e.mahasiswa_id = m.id
            JOIN users u ON m.user_id = u.id
            LEFT JOIN nilai n ON e.id = n.enrollment_id
            WHERE e.matkul_id = ?
            ORDER BY u.nama_lengkap ASC
        ");
        $stmt->execute([$selectedId]);
        $students = $stmt->fetchAll();
    }
}

$pageTitle   = 'Input Nilai';
$pageSection = 'nilai';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Input Nilai</span>
  </div>
  <h1 class="page-title">✏️ Input & Kelola Nilai</h1>
  <p class="page-subtitle">Masukkan nilai tugas, UTS, dan UAS untuk mahasiswa</p>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="5000"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<div class="grid-2" style="grid-template-columns:240px 1fr;gap:20px">
  <!-- Matkul Selector -->
  <div>
    <div class="card">
      <h4 class="card-title mb-3"><i class="fas fa-book-open" style="color:var(--accent-blue)"></i> Mata Kuliah</h4>
      <?php foreach ($matkulList as $mk): ?>
      <a href="?matkul=<?= $mk['id'] ?>" style="text-decoration:none;display:block;margin-bottom:8px">
        <div class="nav-item <?= $selectedId===$mk['id']?'active':'' ?>"
             style="border:1px solid var(--border);border-radius:var(--radius-sm)">
          <span class="nav-icon"><i class="fas fa-book"></i></span>
          <div>
            <div style="font-size:0.82rem;font-weight:700"><?= e($mk['nama']) ?></div>
            <div class="text-xs text-muted"><?= e($mk['kode']) ?> · <?= $mk['enrolled'] ?> mhs</div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Grade Entry Table -->
  <div>
    <?php if ($selectedMatkul && !empty($students)): ?>
    <div class="card animate-fade-in-up">
      <div class="card-header">
        <div>
          <h3 class="card-title"><?= e($selectedMatkul['nama']) ?></h3>
          <div class="text-muted text-xs"><?= e($selectedMatkul['kode']) ?> · <?= count($students) ?> Mahasiswa terdaftar</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <span class="badge badge-blue"><?= $selectedMatkul['sks'] ?> SKS</span>
          <span class="badge badge-purple">Sem <?= $selectedMatkul['semester'] ?></span>
        </div>
      </div>

      <!-- Info bobot -->
      <div class="alert alert-info mb-3" style="font-size:0.8rem">
        <i class="fas fa-info-circle"></i>
        Formula: <strong>Nilai Akhir = (Tugas × 30%) + (UTS × 30%) + (UAS × 40%)</strong>
        &nbsp;·&nbsp; Grade: A≥85, B≥75, C≥65, D≥55, E&lt;55
      </div>

      <form method="POST">
        <input type="hidden" name="save_nilai" value="1">
        <input type="hidden" name="matkul_id" value="<?= $selectedId ?>">

        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <th style="width:110px">Tugas (30%)</th>
                <th style="width:110px">UTS (30%)</th>
                <th style="width:110px">UAS (40%)</th>
                <th style="width:100px">Nilai Akhir</th>
                <th>Grade</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <?php
                $finalScore = ($s['nilai_tugas']*0.3 + $s['nilai_uts']*0.3 + $s['nilai_uas']*0.4);
                $grade = $s['grade'] ?? '';
              ?>
              <tr>
                <td><code class="text-xs"><?= e($s['nim']) ?></code></td>
                <td style="font-weight:600"><?= e($s['nama_lengkap']) ?></td>
                <td>
                  <input type="number"
                    name="grades[<?= $s['enrollment_id'] ?>][tugas]"
                    class="form-control" data-type="grade"
                    value="<?= $s['nilai_tugas'] ?? '' ?>"
                    min="0" max="100" step="0.5"
                    placeholder="0–100">
                </td>
                <td>
                  <input type="number"
                    name="grades[<?= $s['enrollment_id'] ?>][uts]"
                    class="form-control" data-type="grade"
                    value="<?= $s['nilai_uts'] ?? '' ?>"
                    min="0" max="100" step="0.5"
                    placeholder="0–100">
                </td>
                <td>
                  <input type="number"
                    name="grades[<?= $s['enrollment_id'] ?>][uas]"
                    class="form-control" data-type="grade"
                    value="<?= $s['nilai_uas'] ?? '' ?>"
                    min="0" max="100" step="0.5"
                    placeholder="0–100">
                </td>
                <td>
                  <span style="font-weight:700;font-size:0.95rem;font-family:'JetBrains Mono',monospace;
                               color:<?= $grade ? gradeColor($grade) : 'var(--text-muted)' ?>">
                    <?= $s['nilai_akhir'] ? number_format($s['nilai_akhir'],2) : '—' ?>
                  </span>
                </td>
                <td>
                  <?php if ($grade): ?>
                  <span class="grade-pill grade-<?= $grade ?> grade-preview"><?= $grade ?></span>
                  <?php else: ?>
                  <span class="grade-pill grade-preview" style="background:var(--bg-base);color:var(--text-muted);border:1px solid var(--border)">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="display:flex;gap:12px;margin-top:16px;justify-content:flex-end">
          <button type="reset" class="btn btn-outline">
            <i class="fas fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn btn-cyan">
            <i class="fas fa-save"></i> Simpan Semua Nilai
          </button>
        </div>
      </form>
    </div>

    <?php elseif ($selectedMatkul): ?>
    <div class="card">
      <div class="empty-state">
        <div class="empty-state-icon">👨‍🎓</div>
        <div class="empty-state-text">Belum ada mahasiswa yang terdaftar di mata kuliah ini.</div>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="empty-state">
        <div class="empty-state-icon">📚</div>
        <div class="empty-state-text">Pilih mata kuliah dari daftar di sebelah kiri.</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
