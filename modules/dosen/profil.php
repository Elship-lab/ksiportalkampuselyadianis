<?php
/**
 * NEXUS CAMPUS PORTAL
 * Shared Profile Page for All Roles
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$role = $user['role'];
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $newPass = trim($_POST['password'] ?? '');

    if (!$nama || !$email) {
        $err = 'Nama dan Email tidak boleh kosong.';
    } else {
        if (!empty($newPass)) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET nama_lengkap = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $hash, $user['id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $user['id']]);
        }
        $_SESSION['nama'] = $nama;
        $user['nama'] = $nama;
        $msg = 'Profil berhasil diperbarui!';
    }
}

// Fetch details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

$extraData = [];
if ($role === 'mahasiswa') {
    $stmt = $db->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $extraData = $stmt->fetch() ?: [];
} elseif ($role === 'dosen') {
    $stmt = $db->prepare("SELECT * FROM dosen WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $extraData = $stmt->fetch() ?: [];
}

$pageTitle = 'Profil Pengguna';
$pageSection = 'profil';
require_once __DIR__ . '/../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Profil</span>
  </div>
  <h1 class="page-title">⚙️ Pengaturan Profil</h1>
  <p class="page-subtitle">Informasi akun dan kredensial Anda</p>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="4000"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<div class="grid-2">
  <div class="card animate-fade-in-up">
    <div style="text-align:center;padding:20px 0">
      <div class="user-avatar" style="width:72px;height:72px;font-size:1.8rem;margin:0 auto 16px;background:linear-gradient(135deg,var(--accent-purple),var(--accent-pink))">
        <?= strtoupper(substr($userData['nama_lengkap'], 0, 1)) ?>
      </div>
      <h2><?= e($userData['nama_lengkap']) ?></h2>
      <p class="text-sm text-muted font-mono mb-2">@<?= e($userData['username']) ?></p>
      <span class="badge badge-<?= $role === 'admin' ? 'danger' : ($role === 'dosen' ? 'purple' : 'blue') ?>">
        <?= strtoupper($role) ?>
      </span>
    </div>

    <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:16px">
      <?php if ($role === 'mahasiswa'): ?>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">NIM</span>
        <span class="font-mono text-sm fw-bold"><?= e($extraData['nim'] ?? '-') ?></span>
      </div>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">Program Studi</span>
        <span class="text-sm"><?= e($extraData['prodi'] ?? '-') ?></span>
      </div>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">Fakultas</span>
        <span class="text-sm"><?= e($extraData['fakultas'] ?? '-') ?></span>
      </div>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">Angkatan</span>
        <span class="text-sm font-mono"><?= e($extraData['angkatan'] ?? '-') ?></span>
      </div>
      <?php elseif ($role === 'dosen'): ?>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">NIDN</span>
        <span class="font-mono text-sm fw-bold"><?= e($extraData['nidn'] ?? '-') ?></span>
      </div>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">Jabatan</span>
        <span class="text-sm"><?= e($extraData['jabatan'] ?? '-') ?></span>
      </div>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">Program Studi</span>
        <span class="text-sm"><?= e($extraData['prodi'] ?? '-') ?></span>
      </div>
      <?php else: ?>
      <div class="flex-between mb-2">
        <span class="text-sm text-muted">Role</span>
        <span class="text-sm">Super Administrator</span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card animate-fade-in-up">
    <h3 class="card-title mb-3"><i class="fas fa-user-pen" style="color:var(--accent-cyan)"></i> Edit Informasi</h3>
    <form method="POST">
      <input type="hidden" name="update_profile" value="1">
      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" class="form-control" value="<?= e($userData['nama_lengkap']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($userData['email']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password Baru (Kosongkan jika tidak diubah)</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-cyan">
        <i class="fas fa-floppy-disk"></i> Simpan Perubahan
      </button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
