<?php
/**
 * NEXUS CAMPUS PORTAL
 * Mahasiswa — Notifikasi
 */

require_once '../../includes/auth.php';
requireLogin();
requireRole('mahasiswa');

$db     = getDB();
$userId = getCurrentUser()['id'];

// Mark all read
if (isset($_GET['markall'])) {
    $db->prepare("UPDATE notifikasi SET is_read=1 WHERE user_id=?")->execute([$userId]);
    redirect(APP_URL . '/modules/mahasiswa/notifikasi.php');
}

// Mark single read (AJAX)
if (isset($_GET['action']) && $_GET['action']==='read' && isset($_GET['id'])) {
    $db->prepare("UPDATE notifikasi SET is_read=1 WHERE id=? AND user_id=?")
       ->execute([(int)$_GET['id'], $userId]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

$notifs = $db->prepare("SELECT * FROM notifikasi WHERE user_id=? ORDER BY created_at DESC");
$notifs->execute([$userId]);
$notifList = $notifs->fetchAll();

$unread = array_filter($notifList, fn($n) => !$n['is_read']);

$pageTitle   = 'Notifikasi';
$pageSection = 'notif';
require_once '../../includes/layout_header.php';
?>

<div class="page-header animate-fade-in-up">
  <div class="flex-between">
    <div>
      <h1 class="page-title">🔔 Notifikasi</h1>
      <p class="page-subtitle"><?= count($unread) ?> belum dibaca dari <?= count($notifList) ?> total</p>
    </div>
    <?php if ($unread): ?>
    <a href="?markall=1" class="btn btn-outline btn-sm">
      <i class="fas fa-check-double"></i> Tandai Semua Dibaca
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="card animate-fade-in-up">
  <?php if (empty($notifList)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">🔔</div>
    <div class="empty-state-text">Belum ada notifikasi</div>
  </div>
  <?php else: ?>
  <?php foreach ($notifList as $n): ?>
  <?php
    $typeConfig = [
      'success' => ['icon'=>'fa-circle-check',    'color'=>'var(--accent-green)'],
      'info'    => ['icon'=>'fa-circle-info',     'color'=>'var(--accent-blue)'],
      'warning' => ['icon'=>'fa-triangle-exclamation', 'color'=>'var(--accent-amber)'],
      'danger'  => ['icon'=>'fa-circle-xmark',    'color'=>'var(--accent-red)'],
    ];
    $tc = $typeConfig[$n['type']] ?? $typeConfig['info'];
  ?>
  <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>"
       onclick="markNotifRead(<?= $n['id'] ?>, this)">
    <?php if (!$n['is_read']): ?>
    <div class="notif-dot-indicator"></div>
    <?php else: ?>
    <div style="width:8px;flex-shrink:0"></div>
    <?php endif; ?>
    <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;
                justify-content:center;flex-shrink:0;
                background:<?= $tc['color'] ?>22;color:<?= $tc['color'] ?>">
      <i class="fas <?= $tc['icon'] ?>"></i>
    </div>
    <div style="flex:1;min-width:0">
      <div style="font-weight:<?= !$n['is_read']?'700':'500' ?>;font-size:0.88rem;margin-bottom:2px">
        <?= e($n['judul']) ?>
      </div>
      <div class="text-sm text-muted"><?= e($n['pesan']) ?></div>
      <div class="text-xs text-muted mt-1"><i class="fas fa-clock"></i> <?= timeAgo($n['created_at']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once '../../includes/layout_header.php' === '../../includes/layout_header.php' ? require_once '../../includes/layout_footer.php' : null; ?>
