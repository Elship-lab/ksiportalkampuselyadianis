<?php
/**
 * NEXUS CAMPUS PORTAL
 * Admin — Kelola Akun Pengguna
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('admin');

$db  = getDB();
$msg = $err = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $nama     = trim($_POST['nama_lengkap']);
    $role     = $_POST['role'];
    $password = $_POST['password'];
    $nim_nidn = trim($_POST['nim_nidn'] ?? '');

    if (!$username || !$email || !$nama || !$role || !$password) {
        $err = 'Semua field wajib diisi.';
    } elseif (!in_array($role, ['mahasiswa','dosen','admin'])) {
        $err = 'Role tidak valid.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (username,email,nama_lengkap,role,password) VALUES (?,?,?,?,?)")
               ->execute([$username,$email,$nama,$role,$hash]);
            $newId = $db->lastInsertId();

            if ($role === 'mahasiswa' && $nim_nidn) {
                $db->prepare("INSERT INTO mahasiswa (user_id,nim,prodi,fakultas,angkatan,semester) VALUES (?,?,'Teknik Informatika','Fakultas Teknik',YEAR(NOW()),1)")
                   ->execute([$newId, $nim_nidn]);
            } elseif ($role === 'dosen' && $nim_nidn) {
                $db->prepare("INSERT INTO dosen (user_id,nidn,prodi,jabatan) VALUES (?,?,'Teknik Informatika','Dosen Pengajar')")
                   ->execute([$newId, $nim_nidn]);
            }
            $msg = "✅ Akun '$username' berhasil dibuat!";
        } catch (PDOException $e) {
            $err = 'Gagal: Username atau email sudah digunakan.';
        }
    }
}

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $db->prepare("UPDATE users SET status = IF(status='aktif','nonaktif','aktif') WHERE id=?")->execute([$uid]);
    redirect(APP_URL . '/modules/admin/users.php?msg=status_updated');
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid != 1) { // Protect admin ID 1
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        redirect(APP_URL . '/modules/admin/users.php?msg=deleted');
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'status_updated') $msg = 'Status akun berhasil diubah.';
    if ($_GET['msg'] === 'deleted')        $msg = 'Akun berhasil dihapus.';
}

// Get all users with profile
$filterRole = $_GET['role'] ?? '';
$search     = trim($_GET['q'] ?? '');

$where = 'WHERE 1=1';
$params = [];
if ($filterRole && in_array($filterRole, ['mahasiswa','dosen','admin'])) {
    $where .= ' AND u.role = ?';
    $params[] = $filterRole;
}
if ($search) {
    $where .= ' AND (u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR m.nim LIKE ? OR d.nidn LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}

$users = $db->prepare("
    SELECT u.*, m.nim, m.prodi as m_prodi, m.semester, m.angkatan,
           d.nidn, d.jabatan, d.prodi as d_prodi
    FROM users u
    LEFT JOIN mahasiswa m ON u.id = m.user_id
    LEFT JOIN dosen d ON u.id = d.user_id
    $where
    ORDER BY u.created_at DESC
");
$users->execute($params);
$userList = $users->fetchAll();

$pageTitle   = 'Kelola Akun';
$pageSection = 'users';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <span>Kelola Akun</span>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
    <div>
      <h1 class="page-title">👥 Manajemen Pengguna</h1>
      <p class="page-subtitle">Kelola semua akun mahasiswa, dosen, dan administrator</p>
    </div>
    <button class="btn btn-primary" onclick="NexusModal.open('modalAddUser')">
      <i class="fas fa-user-plus"></i> Tambah Akun
    </button>
  </div>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="4000"><i class="fas fa-check"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<!-- Filter & Search Bar -->
<div class="card mb-3 animate-fade-in-up">
  <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div class="input-group" style="flex:1;min-width:200px">
      <i class="input-icon fas fa-search"></i>
      <input type="text" name="q" class="form-control" placeholder="Cari nama, username, NIM..." value="<?= e($search) ?>">
    </div>
    <select name="role" class="form-control" style="width:160px">
      <option value="">Semua Role</option>
      <option value="mahasiswa" <?= $filterRole==='mahasiswa'?'selected':'' ?>>Mahasiswa</option>
      <option value="dosen" <?= $filterRole==='dosen'?'selected':'' ?>>Dosen</option>
      <option value="admin" <?= $filterRole==='admin'?'selected':'' ?>>Admin</option>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    <a href="?" class="btn btn-outline"><i class="fas fa-rotate"></i> Reset</a>
  </form>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <?php
  $counts = ['all'=>count($userList),'mahasiswa'=>0,'dosen'=>0,'admin'=>0];
  foreach ($userList as $u) $counts[$u['role']]++;
  ?>
  <div class="stat-card cyan"><div class="stat-icon cyan"><i class="fas fa-users"></i></div>
    <div><div class="stat-value"><?= $counts['all'] ?></div><div class="stat-label">Ditampilkan</div></div></div>
  <div class="stat-card blue"><div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div>
    <div><div class="stat-value"><?= $counts['mahasiswa'] ?></div><div class="stat-label">Mahasiswa</div></div></div>
  <div class="stat-card purple"><div class="stat-icon purple"><i class="fas fa-chalkboard-teacher"></i></div>
    <div><div class="stat-value"><?= $counts['dosen'] ?></div><div class="stat-label">Dosen</div></div></div>
  <div class="stat-card red"><div class="stat-icon red"><i class="fas fa-shield-halved"></i></div>
    <div><div class="stat-value"><?= $counts['admin'] ?></div><div class="stat-label">Admin</div></div></div>
</div>

<!-- User Table -->
<div class="card animate-fade-in-up">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-table" style="color:var(--accent-cyan)"></i> Daftar Pengguna</h3>
    <span class="badge badge-cyan"><?= count($userList) ?> akun</span>
  </div>
  <div class="table-container">
    <table class="table">
      <thead>
        <tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>ID/NIM/NIDN</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($userList as $u): ?>
        <tr class="searchable-row">
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem;
                   background:linear-gradient(135deg,
                   <?= $u['role']==='admin'?'#ef4444,#dc2626':($u['role']==='dosen'?'#7c3aed,#ec4899':'#4361ee,#06b6d4') ?>)">
                <?= strtoupper(substr($u['nama_lengkap'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600;font-size:0.85rem"><?= e($u['nama_lengkap']) ?></div>
                <?php if ($u['role']==='mahasiswa'): ?>
                <div class="text-xs text-muted"><?= e($u['m_prodi']??'') ?> · Sem <?= $u['semester']??'' ?></div>
                <?php elseif ($u['role']==='dosen'): ?>
                <div class="text-xs text-muted"><?= e($u['jabatan']??'') ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><code class="text-xs"><?= e($u['username']) ?></code></td>
          <td class="text-sm text-muted"><?= e($u['email']) ?></td>
          <td>
            <span class="badge <?= $u['role']==='admin'?'badge-danger':($u['role']==='dosen'?'badge-purple':'badge-blue') ?>">
              <i class="fas fa-<?= $u['role']==='admin'?'shield-halved':($u['role']==='dosen'?'chalkboard-teacher':'graduation-cap') ?>"></i>
              <?= ucfirst($u['role']) ?>
            </span>
          </td>
          <td>
            <code class="text-xs"><?= e($u['nim'] ?? $u['nidn'] ?? '—') ?></code>
          </td>
          <td>
            <span class="badge <?= $u['status']==='aktif'?'badge-success':'badge-danger' ?>">
              <i class="fas fa-<?= $u['status']==='aktif'?'circle':'circle-xmark' ?>"></i>
              <?= $u['status'] ?>
            </span>
          </td>
          <td class="text-xs text-muted">
            <?= $u['last_login'] ? timeAgo($u['last_login']) : 'Belum pernah' ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="?toggle=<?= $u['id'] ?>"
                 class="btn btn-sm <?= $u['status']==='aktif'?'btn-danger':'btn-success' ?>"
                 title="<?= $u['status']==='aktif'?'Nonaktifkan':'Aktifkan' ?>"
                 onclick="return confirm('Ubah status akun <?= e($u['username']) ?>?')">
                <i class="fas fa-<?= $u['status']==='aktif'?'ban':'check' ?>"></i>
              </a>
              <?php if ($u['id'] != 1): ?>
              <a href="?delete=<?= $u['id'] ?>"
                 class="btn btn-sm btn-danger"
                 title="Hapus"
                 onclick="return confirm('HAPUS akun <?= e($u['username']) ?>? Ini tidak bisa dibatalkan!')">
                <i class="fas fa-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Add User -->
<div class="modal-overlay" id="modalAddUser">
  <div class="modal">
    <div class="modal-header">
      <h3 class="card-title"><i class="fas fa-user-plus" style="color:var(--accent-cyan)"></i> Tambah Akun Baru</h3>
      <button class="modal-close" onclick="NexusModal.close('modalAddUser')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="add_user" value="1">
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" class="form-control" required placeholder="Dr. Budi Santoso">
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required placeholder="budi2024">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required placeholder="budi@nexuscampus.id">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" class="form-control" required>
            <option value="mahasiswa">Mahasiswa</option>
            <option value="dosen">Dosen</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">NIM / NIDN (opsional)</label>
          <input type="text" name="nim_nidn" class="form-control" placeholder="24TIF001 / 0312079001">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="Min. 8 karakter">
      </div>
      <div class="alert alert-warning" style="font-size:0.8rem">
        <i class="fas fa-info-circle"></i>
        Password akan di-hash dengan bcrypt. Informasikan password awal ke pengguna!
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="NexusModal.close('modalAddUser')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Buat Akun</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
