<?php
/**
 * NEXUS CAMPUS PORTAL
 * Session & Authentication Helper
 */

require_once __DIR__ . '/config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,      // 24 jam
        'path'     => '/',
        'secure'   => false,      // true jika HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ─── AUTH FUNCTIONS ──────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(string $redirect = '/kampus-portal/index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php?msg=please_login');
        exit;
    }
}

function requireRole(string|array $roles): void {
    requireLogin();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $allowed)) {
        header('Location: ' . APP_URL . '/dashboard.php?err=unauthorized');
        exit;
    }
}

function getCurrentUser(): array {
    if (!isLoggedIn()) return [];
    return [
        'id'          => $_SESSION['user_id'],
        'username'    => $_SESSION['username'],
        'nama'        => $_SESSION['nama'],
        'role'        => $_SESSION['role'],
        'profile_id'  => $_SESSION['profile_id'] ?? null,
    ];
}

function login(string $username, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'aktif' LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Username atau password salah.'];
    }

    // Update last_login
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    // Set session
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama']     = $user['nama_lengkap'];
    $_SESSION['role']     = $user['role'];

    // Get role-specific profile id
    if ($user['role'] === 'mahasiswa') {
        $p = $db->prepare("SELECT id FROM mahasiswa WHERE user_id = ?");
        $p->execute([$user['id']]);
        $_SESSION['profile_id'] = $p->fetchColumn();
    } elseif ($user['role'] === 'dosen') {
        $p = $db->prepare("SELECT id FROM dosen WHERE user_id = ?");
        $p->execute([$user['id']]);
        $_SESSION['profile_id'] = $p->fetchColumn();
    }

    return ['success' => true, 'role' => $user['role']];
}

function logout(): void {
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/index.php?msg=logged_out');
    exit;
}

// ─── UTILITY ─────────────────────────────────────────────────

function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function getUnreadNotifCount(): int {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

function gradeColor(string $grade): string {
    return match($grade) {
        'A'     => '#00d4aa',
        'B'     => '#4fc3f7',
        'C'     => '#ffb74d',
        'D'     => '#ff7043',
        default => '#ef5350',
    };
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);
    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

function deadlineStatus(string $deadline): array {
    $now  = new DateTime();
    $dl   = new DateTime($deadline);
    $diff = $now->diff($dl);
    if ($now > $dl) return ['label' => 'Expired', 'class' => 'badge-danger', 'icon' => 'fa-times-circle'];
    if ($diff->days == 0 && $diff->h < 24) return ['label' => 'Hari Ini!', 'class' => 'badge-warning', 'icon' => 'fa-exclamation-triangle'];
    if ($diff->days <= 3) return ['label' => $diff->days . ' hari lagi', 'class' => 'badge-warning', 'icon' => 'fa-clock'];
    return ['label' => $diff->days . ' hari lagi', 'class' => 'badge-info', 'icon' => 'fa-calendar'];
}
