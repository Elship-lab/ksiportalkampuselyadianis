<?php
/**
 * NEXUS CAMPUS PORTAL
 * Admin — Kelola Mata Kuliah
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('admin');

$db = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_matkul'])) {
    $kode = strtoupper(trim($_POST['kode']));
    $nama = trim($_POST['nama']);
    $sks = (int)$_POST['sks'];
    $dosenId = (int)$_POST['dosen_id'];
    $semester = (int)$_POST['semester'];
    $prodi = trim($_POST['prodi']);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!$kode || !$nama || !$sks || !$dosenId || !$semester) {
        $err = 'Semua field wajib diisi.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO mata_kuliah (kode, nama, sks, dosen_id, semester, prodi, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode, $nama, $sks, $dosenId, $semester, $prodi, $deskripsi]);
            $msg = 'Mata kuliah baru berhasil ditambahkan!';
        } catch (PDOException $e) {
            $err = 'Kode mata kuliah sudah ada atau data tidak valid.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM mata_kuliah WHERE id = ?")->execute([$id]);
    redirect('matkul.php?msg=deleted');
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = 'Mata kuliah berhasil dihapus.';
}

$stmt = $db->query("
    SELECT mk.*, u.nama_lengkap as dosen_nama,
           (SELECT COUNT(*) FROM enrollment WHERE matkul_id = mk.id) as total_mhs
    FROM mata_kuliah mk
    JOIN dosen d ON mk.dosen_id = d.id
    JOIN users u ON d.user_id = u.id
    ORDER BY mk.semester ASC, mk.kode ASC
");
$matkulList = $stmt->fetchAll();

$dosens = $db->query("SELECT d.id, u.nama_lengkap FROM dosen d JOIN users u ON d.user_id = u.id ORDER BY u.nama_lengkap ASC")->fetchAll();

$pageTitle = 'Kelola Mata Kuliah';
$pageSection = 'matkul';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Mata Kuliah</span>
  </div>
  <div class="flex-between">
    <div>
      <h1 class="page-title">📚 Kurikulum & Mata Kuliah</h1>
      <p class="page-subtitle">Kelola daftar mata kuliah, dosen pengampu, dan SKS</p>
    </div>
    <button class="btn btn-primary" onclick="NexusModal.open('modalTambahMatkul')">
      <i class="fas fa-plus"></i> Tambah Matkul
    </button>
  </div>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="4000"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<div class="card animate-fade-in-up">
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama Mata Kuliah</th>
          <th>SKS</th>
          <th>Semester</th>
          <th>Dosen Pengampu</th>
          <th>Prodi</th>
          <th>Mahasiswa</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matkulList as $mk): ?>
        <tr>
          <td><code class="font-mono"><?= e($mk['kode']) ?></code></td>
          <td><strong><?= e($mk['nama']) ?></strong></td>
          <td><?= $mk['sks'] ?></td>
          <td>Semester <?= $mk['semester'] ?></td>
          <td><?= e($mk['dosen_nama']) ?></td>
          <td><span class="badge badge-purple"><?= e($mk['prodi']) ?></span></td>
          <td><span class="badge badge-cyan"><?= $mk['total_mhs'] ?> Mhs</span></td>
          <td>
            <a href="?delete=<?= $mk['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus mata kuliah ini?')">
              <i class="fas fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Add Matkul -->
<div class="modal-overlay" id="modalTambahMatkul">
  <div class="modal">
    <div class="modal-header">
      <h3 class="card-title"><i class="fas fa-book-medical" style="color:var(--accent-cyan)"></i> Tambah Mata Kuliah</h3>
      <button class="modal-close" onclick="NexusModal.close('modalTambahMatkul')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="tambah_matkul" value="1">
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Kode Matkul</label>
          <input type="text" name="kode" class="form-control" placeholder="Contoh: TIF401" required>
        </div>
        <div class="form-group">
          <label class="form-label">Jumlah SKS</label>
          <input type="number" name="sks" class="form-control" value="3" min="1" max="6" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Mata Kuliah</label>
        <input type="text" name="nama" class="form-control" placeholder="Contoh: Pemrograman Mobile" required>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Dosen Pengampu</label>
          <select name="dosen_id" class="form-control" required>
            <?php foreach ($dosens as $d): ?>
            <option value="<?= $d['id'] ?>"><?= e($d['nama_lengkap']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Semester</label>
          <input type="number" name="semester" class="form-control" value="1" min="1" max="8" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Program Studi</label>
        <input type="text" name="prodi" class="form-control" value="Teknik Informatika" required>
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Silabus singkat..."></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="NexusModal.close('modalTambahMatkul')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Simpan Matkul</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
