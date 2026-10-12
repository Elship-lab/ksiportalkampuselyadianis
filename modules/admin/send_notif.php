<?php
/**
 * NEXUS CAMPUS PORTAL
 * Admin — Kirim Notifikasi Broadcast / Personal
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('admin');

$db = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notif'])) {
    $target = $_POST['target'];
    $judul = trim($_POST['judul']);
    $pesan = trim($_POST['pesan']);
    $type = $_POST['type'];

    if (!$judul || !$pesan) {
        $err = 'Judul dan pesan tidak boleh kosong.';
    } else {
        if ($target === 'all') {
            $users = $db->query("SELECT id FROM users WHERE status = 'aktif'")->fetchAll();
            $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan, type) VALUES (?, ?, ?, ?)");
            foreach ($users as $u) {
                $stmt->execute([$u['id'], $judul, $pesan, $type]);
            }
            $msg = 'Notifikasi berhasil di-broadcast ke seluruh pengguna! 📢';
        } elseif ($target === 'mahasiswa' || $target === 'dosen') {
            $users = $db->prepare("SELECT id FROM users WHERE role = ? AND status = 'aktif'");
            $users->execute([$target]);
            $rows = $users->fetchAll();
            $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan, type) VALUES (?, ?, ?, ?)");
            foreach ($rows as $u) {
                $stmt->execute([$u['id'], $judul, $pesan, $type]);
            }
            $msg = "Notifikasi berhasil dikirim ke seluruh $target! 🎯";
        } else {
            $userId = (int)$target;
            $stmt = $db->prepare("INSERT INTO notifikasi (user_id, judul, pesan, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $judul, $pesan, $type]);
            $msg = 'Notifikasi personal berhasil dikirim! ✉️';
        }
    }
}

$userList = $db->query("SELECT id, nama_lengkap, role FROM users WHERE status = 'aktif' ORDER BY nama_lengkap ASC")->fetchAll();

$pageTitle = 'Kirim Notifikasi';
$pageSection = 'send_notif';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Kirim Notifikasi</span>
  </div>
  <h1 class="page-title">📢 Broadcast Notifikasi</h1>
  <p class="page-subtitle">Kirim pengumuman penting kepada mahasiswa, dosen, atau individu</p>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="4000"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<div class="card animate-fade-in-up" style="max-width:700px">
  <form method="POST">
    <input type="hidden" name="send_notif" value="1">
    
    <div class="form-group">
      <label class="form-label">Sasaran Penerima</label>
      <select name="target" class="form-control" required>
        <option value="all">🌐 Semua Pengguna (Broadcast Global)</option>
        <option value="mahasiswa">🎓 Seluruh Mahasiswa</option>
        <option value="dosen">👨‍🏫 Seluruh Dosen</option>
        <optgroup label="Pengguna Tertentu">
          <?php foreach ($userList as $u): ?>
          <option value="<?= $u['id'] ?>"><?= e($u['nama_lengkap']) ?> (<?= ucfirst($u['role']) ?>)</option>
          <?php endforeach; ?>
        </optgroup>
      </select>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Tipe Pesan</label>
        <select name="type" class="form-control" required>
          <option value="info">ℹ️ Informasi (Info)</option>
          <option value="success">✅ Sukses (Success)</option>
          <option value="warning">⚠️ Peringatan (Warning)</option>
          <option value="danger">🚨 Kritis (Danger)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Judul Pengumuman</label>
        <input type="text" name="judul" class="form-control" placeholder="Contoh: Jadwal UTS Diumumkan" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Isi Pesan Notifikasi</label>
      <textarea name="pesan" class="form-control" rows="4" placeholder="Tulis rincian pesan di sini..." required></textarea>
    </div>

    <button type="submit" class="btn btn-cyan">
      <i class="fas fa-paper-plane"></i> Kirim Notifikasi Sekarang
    </button>
  </form>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
