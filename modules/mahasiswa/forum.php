<?php
/**
 * NEXUS CAMPUS PORTAL
 * Mahasiswa — Forum Diskusi
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('mahasiswa');

$db     = getDB();
$user   = getCurrentUser();
$userId = $user['id'];
$profileId = $user['profile_id'];

$msg = $err = '';

// Handle new thread or reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_thread'])) {
        $matkulId = (int)$_POST['matkul_id'];
        $judul    = trim($_POST['judul']);
        $konten   = trim($_POST['konten']);

        if (!$judul || !$konten) {
            $err = 'Judul dan isi diskusi wajib diisi.';
        } else {
            $db->prepare("INSERT INTO forum (matkul_id, user_id, judul, konten) VALUES (?,?,?,?)")
               ->execute([$matkulId, $userId, $judul, $konten]);
            $msg = 'Thread diskusi berhasil dibuat! 🎉';
        }
    }

    if (isset($_POST['reply'])) {
        $forumId = (int)$_POST['forum_id'];
        $konten  = trim($_POST['konten']);
        if (!$konten) {
            $err = 'Balasan tidak boleh kosong.';
        } else {
            $db->prepare("INSERT INTO forum_balasan (forum_id, user_id, konten) VALUES (?,?,?)")
               ->execute([$forumId, $userId, $konten]);
            $msg = 'Balasan berhasil dikirim! ✅';
        }
    }
}

// Get enrolled matkul for dropdown
$stmt = $db->prepare("
    SELECT mk.id, mk.nama, mk.kode FROM enrollment e
    JOIN mata_kuliah mk ON e.matkul_id = mk.id
    WHERE e.mahasiswa_id = ? ORDER BY mk.nama
");
$stmt->execute([$profileId]);
$matkulList = $stmt->fetchAll();

// View single thread
$viewThread = null;
$replies = [];
if (isset($_GET['thread'])) {
    $threadId = (int)$_GET['thread'];
    $stmt = $db->prepare("
        SELECT f.*, mk.nama as matkul_nama, mk.kode as matkul_kode,
               u.nama_lengkap as author_nama, u.role as author_role
        FROM forum f
        JOIN mata_kuliah mk ON f.matkul_id = mk.id
        JOIN users u ON f.user_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$threadId]);
    $viewThread = $stmt->fetch();

    if ($viewThread) {
        // Increment view
        $db->prepare("UPDATE forum SET views = views + 1 WHERE id = ?")->execute([$threadId]);

        $stmt = $db->prepare("
            SELECT fb.*, u.nama_lengkap as author_nama, u.role as author_role
            FROM forum_balasan fb
            JOIN users u ON fb.user_id = u.id
            WHERE fb.forum_id = ?
            ORDER BY fb.is_solution DESC, fb.created_at ASC
        ");
        $stmt->execute([$threadId]);
        $replies = $stmt->fetchAll();
    }
}

// List threads for selected matkul or all enrolled
$selectedMatkul = (int)($_GET['matkul'] ?? 0);
if ($viewThread) {
    $threads = [];
} elseif ($selectedMatkul) {
    $stmt = $db->prepare("
        SELECT f.*, mk.nama as matkul_nama, mk.kode as matkul_kode,
               u.nama_lengkap as author_nama,
               (SELECT COUNT(*) FROM forum_balasan WHERE forum_id = f.id) as reply_count
        FROM forum f
        JOIN mata_kuliah mk ON f.matkul_id = mk.id
        JOIN users u ON f.user_id = u.id
        JOIN enrollment e ON mk.id = e.matkul_id AND e.mahasiswa_id = ?
        WHERE f.matkul_id = ?
        ORDER BY f.pinned DESC, f.created_at DESC
    ");
    $stmt->execute([$profileId, $selectedMatkul]);
    $threads = $stmt->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT f.*, mk.nama as matkul_nama, mk.kode as matkul_kode,
               u.nama_lengkap as author_nama,
               (SELECT COUNT(*) FROM forum_balasan WHERE forum_id = f.id) as reply_count
        FROM forum f
        JOIN mata_kuliah mk ON f.matkul_id = mk.id
        JOIN users u ON f.user_id = u.id
        JOIN enrollment e ON mk.id = e.matkul_id AND e.mahasiswa_id = ?
        ORDER BY f.pinned DESC, f.created_at DESC LIMIT 30
    ");
    $stmt->execute([$profileId]);
    $threads = $stmt->fetchAll();
}

$pageTitle   = 'Forum Diskusi';
$pageSection = 'forum';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/dashboard.php"><i class="fas fa-house"></i></a>
    <span class="breadcrumb-sep">›</span>
    <?php if ($viewThread): ?>
    <a href="<?= APP_URL ?>/modules/mahasiswa/forum.php">Forum</a>
    <span class="breadcrumb-sep">›</span>
    <span><?= e(substr($viewThread['judul'],0,40)) ?>...</span>
    <?php else: ?>
    <span>Forum Diskusi</span>
    <?php endif; ?>
  </div>
  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <h1 class="page-title">💬 Forum Diskusi</h1>
      <p class="page-subtitle">Diskusi dan tanya jawab seputar mata kuliah</p>
    </div>
    <button class="btn btn-primary" onclick="NexusModal.open('modalNewThread')">
      <i class="fas fa-plus"></i> Thread Baru
    </button>
  </div>
</div>

<?php if ($msg): ?><div class="alert alert-success" data-autohide="4000"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= e($err) ?></div><?php endif; ?>

<?php if ($viewThread): ?>
<!-- ── SINGLE THREAD VIEW ──────────────────────────────────── -->
<div class="card animate-fade-in-up mb-3">
  <div style="display:flex;align-items:flex-start;gap:14px">
    <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent-blue),var(--accent-purple))">
      <?= strtoupper(substr($viewThread['author_nama'],0,1)) ?>
    </div>
    <div style="flex:1">
      <?php if ($viewThread['pinned']): ?>
      <span class="badge badge-warning mb-2"><i class="fas fa-thumbtack"></i> Pinned</span>
      <?php endif; ?>
      <h2 style="font-size:1.15rem;margin-bottom:8px"><?= e($viewThread['judul']) ?></h2>
      <div class="flex-center gap-md text-sm text-muted mb-3" style="flex-wrap:wrap">
        <span><i class="fas fa-user"></i> <?= e($viewThread['author_nama']) ?>
          <span class="badge <?= $viewThread['author_role']==='dosen'?'badge-purple':'badge-blue' ?>">
            <?= ucfirst($viewThread['author_role']) ?>
          </span>
        </span>
        <span><i class="fas fa-book"></i> <?= e($viewThread['matkul_nama']) ?></span>
        <span><i class="fas fa-clock"></i> <?= timeAgo($viewThread['created_at']) ?></span>
        <span><i class="fas fa-eye"></i> <?= $viewThread['views'] ?> views</span>
      </div>
      <!-- VULNERABILITY: Stored XSS - konten tidak di-escape dengan htmlspecialchars (raw output) -->
      <div style="color:var(--text-secondary);line-height:1.7;white-space:pre-wrap"><?= $viewThread['konten'] ?></div>
    </div>
  </div>
</div>

<!-- Replies -->
<h3 class="mb-2" style="font-size:0.9rem;color:var(--text-muted)">
  <i class="fas fa-comments"></i> <?= count($replies) ?> Balasan
</h3>

<?php foreach ($replies as $r): ?>
<div class="card mb-2 <?= $r['is_solution'] ? 'animate-pulse-glow' : '' ?>"
     style="<?= $r['is_solution'] ? 'border-color:rgba(0,212,170,0.4)' : '' ?>">
  <?php if ($r['is_solution']): ?>
  <div style="background:rgba(0,212,170,0.1);border:1px solid rgba(0,212,170,0.2);
              border-radius:6px;padding:6px 12px;margin-bottom:12px;font-size:0.78rem;
              color:var(--accent-cyan);font-weight:700">
    <i class="fas fa-circle-check"></i> Jawaban Terpilih (Best Answer)
  </div>
  <?php endif; ?>
  <div style="display:flex;align-items:flex-start;gap:12px">
    <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem;
         background:linear-gradient(135deg,<?= $r['author_role']==='dosen'?'var(--accent-purple),var(--accent-pink)':'var(--accent-blue),var(--accent-cyan)' ?>)">
      <?= strtoupper(substr($r['author_nama'],0,1)) ?>
    </div>
    <div style="flex:1">
      <div class="flex-center gap-sm mb-1">
        <strong class="text-sm"><?= e($r['author_nama']) ?></strong>
        <span class="badge <?= $r['author_role']==='dosen'?'badge-purple':'badge-blue' ?>">
          <?= ucfirst($r['author_role']) ?>
        </span>
        <span class="text-xs text-muted"><?= timeAgo($r['created_at']) ?></span>
      </div>
      <!-- VULNERABILITY: Stored XSS in reply -->
      <div style="color:var(--text-secondary);font-size:0.88rem;line-height:1.7;white-space:pre-wrap"><?= $r['konten'] ?></div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Reply form -->
<div class="card mt-3 animate-fade-in-up">
  <h4 class="mb-3"><i class="fas fa-reply" style="color:var(--accent-cyan)"></i> Tulis Balasan</h4>
  <form method="POST">
    <input type="hidden" name="reply" value="1">
    <input type="hidden" name="forum_id" value="<?= $viewThread['id'] ?>">
    <div class="form-group">
      <textarea name="konten" class="form-control" rows="4"
        placeholder="Tulis balasan kamu di sini... Gunakan penjelasan yang jelas dan sopan." required></textarea>
    </div>
    <button type="submit" class="btn btn-cyan">
      <i class="fas fa-paper-plane"></i> Kirim Balasan
    </button>
  </form>
</div>

<?php else: ?>
<!-- ── THREAD LIST ──────────────────────────────────────────── -->

<!-- Filter by matkul -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="?" class="btn <?= !$selectedMatkul?'btn-primary':'btn-outline' ?> btn-sm">Semua</a>
  <?php foreach ($matkulList as $mk): ?>
  <a href="?matkul=<?= $mk['id'] ?>"
     class="btn <?= $selectedMatkul===$mk['id']?'btn-primary':'btn-outline' ?> btn-sm">
    <?= e($mk['kode']) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($threads)): ?>
<div class="empty-state card">
  <div class="empty-state-icon">💬</div>
  <div class="empty-state-text">Belum ada thread diskusi. Jadilah yang pertama!</div>
</div>
<?php endif; ?>

<?php foreach ($threads as $thread): ?>
<a href="?thread=<?= $thread['id'] ?>" style="text-decoration:none;display:block;margin-bottom:10px">
  <div class="forum-item <?= $thread['pinned'] ? 'pinned' : '' ?> animate-fade-in-up">
    <div class="forum-icon"
         style="background:<?= $thread['pinned'] ? 'rgba(245,158,11,0.15)' : 'rgba(67,97,238,0.15)' ?>;
                color:<?= $thread['pinned'] ? 'var(--accent-amber)' : 'var(--accent-blue)' ?>">
      <i class="fas fa-<?= $thread['pinned'] ? 'thumbtack' : 'message' ?>"></i>
    </div>
    <div style="flex:1;min-width:0">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
        <?php if ($thread['pinned']): ?>
        <span class="badge badge-warning"><i class="fas fa-thumbtack"></i> Pinned</span>
        <?php endif; ?>
        <div style="font-weight:700;font-size:0.9rem;color:var(--text-primary)">
          <?= e($thread['judul']) ?>
        </div>
      </div>
      <div class="forum-meta">
        <span><i class="fas fa-user"></i> <?= e($thread['author_nama']) ?></span>
        <span><i class="fas fa-book"></i> <?= e($thread['matkul_kode']) ?></span>
        <span><i class="fas fa-comments"></i> <?= $thread['reply_count'] ?> balasan</span>
        <span><i class="fas fa-eye"></i> <?= $thread['views'] ?></span>
        <span><i class="fas fa-clock"></i> <?= timeAgo($thread['created_at']) ?></span>
      </div>
    </div>
    <i class="fas fa-chevron-right" style="color:var(--text-muted);flex-shrink:0"></i>
  </div>
</a>
<?php endforeach; ?>
<?php endif; ?>

<!-- Modal: New Thread -->
<div class="modal-overlay" id="modalNewThread">
  <div class="modal">
    <div class="modal-header">
      <h3 class="card-title"><i class="fas fa-plus-circle" style="color:var(--accent-cyan)"></i> Buat Thread Baru</h3>
      <button class="modal-close" onclick="NexusModal.close('modalNewThread')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="new_thread" value="1">
      <div class="form-group">
        <label class="form-label">Mata Kuliah</label>
        <select name="matkul_id" class="form-control" required>
          <option value="">-- Pilih Mata Kuliah --</option>
          <?php foreach ($matkulList as $mk): ?>
          <option value="<?= $mk['id'] ?>"
            <?= $selectedMatkul===$mk['id']?'selected':'' ?>>
            <?= e($mk['kode']) ?> — <?= e($mk['nama']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Judul Diskusi</label>
        <input type="text" name="judul" class="form-control" required
          placeholder="Tuliskan pertanyaan atau topik diskusi...">
      </div>
      <div class="form-group">
        <label class="form-label">Isi Diskusi</label>
        <textarea name="konten" class="form-control" rows="5" required
          placeholder="Jelaskan pertanyaan atau topik kamu secara detail..."></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="NexusModal.close('modalNewThread')">Batal</button>
        <button type="submit" class="btn btn-cyan"><i class="fas fa-paper-plane"></i> Buat Thread</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../../includes/layout_footer.php'; ?>
