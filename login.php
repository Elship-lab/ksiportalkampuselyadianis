<?php
/**
 * NEXUS CAMPUS PORTAL
 * Dedicated Login Page
 * Tema: Cerah Bersih Putih & Biru dengan Background Gedung Kampus Asli
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

$error   = '';
$success = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logged_out')   $success = 'Berhasil keluar. Sampai jumpa! 👋';
    if ($_GET['msg'] === 'please_login') $error   = 'Silakan login untuk mengakses portal.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $result = login($username, $password);
        if ($result['success']) {
            redirect(APP_URL . '/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — NexusPortal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --primary-blue: #0284c7;
    --primary-dark: #0369a1;
    --primary-deep: #0f172a;
  }

  body {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    color: #0f172a;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f7ff;
    position: relative;
    padding: 24px;
    overflow-x: hidden;
  }

  /* ── BRIGHT SUNNY CAMPUS BUILDING BACKGROUND ─────────────────── */
  .login-bg {
    position: fixed; inset: 0; z-index: -2;
    background-image: 
      linear-gradient(135deg, rgba(240, 247, 255, 0.78) 0%, rgba(224, 242, 254, 0.85) 60%, rgba(2, 132, 199, 0.15) 100%),
      url('assets/img/gedung-kampus.png');
    background-size: cover;
    background-position: center;
  }

  .back-to-home {
    position: absolute;
    top: 28px; left: 32px;
    display: inline-flex; align-items: center; gap: 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: var(--primary-deep);
    padding: 10px 22px;
    border-radius: 99px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
    transition: all 0.25s ease;
  }
  .back-to-home:hover {
    background: #e0f2fe;
    color: var(--primary-blue);
    border-color: var(--primary-blue);
    transform: translateX(-3px);
  }

  /* ── BRIGHT WHITE & BLUE CARD (FROSTED GLASS) ─────────────────── */
  .login-card-bright {
    width: 100%;
    max-width: 460px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    border-radius: 28px;
    padding: 44px 38px;
    box-shadow: 0 25px 60px -15px rgba(2, 132, 199, 0.22), 0 0 30px rgba(0, 0, 0, 0.04);
  }

  .brand-logo {
    text-align: center;
    margin-bottom: 28px;
  }
  .brand-logo-icon {
    width: 58px; height: 58px;
    margin: 0 auto 12px;
    background: linear-gradient(135deg, #0284c7, #38bdf8);
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; color: #fff;
    box-shadow: 0 8px 25px rgba(2, 132, 199, 0.35);
  }
  .brand-logo h1 {
    font-size: 1.9rem;
    font-weight: 800;
    color: var(--primary-deep);
    letter-spacing: -0.5px;
  }
  .brand-logo p {
    font-size: 0.88rem;
    color: #64748b;
    margin-top: 4px;
    font-weight: 500;
  }

  .form-group { margin-bottom: 20px; }
  .form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #334155;
    margin-bottom: 8px;
  }
  .input-wrap {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    padding: 13px 18px;
    transition: all 0.2s ease;
  }
  .input-wrap:focus-within {
    border-color: var(--primary-blue);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
  }
  .input-wrap i { color: #94a3b8; margin-right: 14px; font-size: 1rem; }
  .input-wrap input {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    color: #0f172a;
    font-size: 0.95rem;
    font-family: inherit;
    font-weight: 600;
  }

  .btn-submit {
    width: 100%;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    color: #fff;
    border: none;
    border-radius: 14px;
    padding: 15px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(2, 132, 199, 0.35);
    transition: all 0.25s ease;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    margin-top: 10px;
  }
  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(2, 132, 199, 0.5);
  }

  .alert {
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 0.88rem;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
    font-weight: 600;
  }
  .alert-danger  { background: #ffe4e6; border: 1px solid #fecdd3; color: #e11d48; }
  .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #16a34a; }

  /* Demo Fast Cards */
  .demo-box {
    margin-top: 26px;
    border-top: 1px solid #e2e8f0;
    padding-top: 20px;
  }
  .demo-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #64748b;
    font-weight: 800;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
  }
  .demo-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 16px;
    margin-bottom: 8px;
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .demo-item:hover {
    background: #e0f2fe;
    border-color: #0284c7;
    transform: translateX(3px);
  }
  .demo-role { font-size: 0.82rem; font-weight: 800; color: #0284c7; }
  .demo-user { font-size: 0.75rem; color: #64748b; font-family: 'JetBrains Mono', monospace; }
</style>
</head>
<body>

  <div class="login-bg"></div>

  <a href="<?= APP_URL ?>/index.php" class="back-to-home">
    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
  </a>

  <div class="login-card-bright">
    <div class="brand-logo">
      <div class="brand-logo-icon"><i class="fas fa-graduation-cap"></i></div>
      <h1>NexusPortal</h1>
      <p>Portal Akademik Digital — Generasi 4.0</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username / NIM / NIDN</label>
        <div class="input-wrap">
          <i class="fas fa-user"></i>
          <input type="text" id="usernameInput" name="username" placeholder="Masukkan username..." required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" id="passwordInput" name="password" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <i class="fas fa-right-to-bracket"></i> Masuk ke Akun
      </button>
    </form>

    <div class="demo-box">
      <div class="demo-title"><i class="fas fa-bolt" style="color:#0284c7"></i> Klik untuk Auto-Fill Akun Demo</div>
      <div class="demo-item" onclick="fillCreds('admin', 'password')">
        <div>
          <div class="demo-role" style="color:#e11d48">Administrator</div>
          <div class="demo-user">admin</div>
        </div>
        <i class="fas fa-chevron-right" style="color:#94a3b8;font-size:0.8rem"></i>
      </div>
      <div class="demo-item" onclick="fillCreds('dr.andi', 'password')">
        <div>
          <div class="demo-role" style="color:#7e22ce">Dosen — Dr. Andi Pratama</div>
          <div class="demo-user">dr.andi</div>
        </div>
        <i class="fas fa-chevron-right" style="color:#94a3b8;font-size:0.8rem"></i>
      </div>
      <div class="demo-item" onclick="fillCreds('budi2022', 'password')">
        <div>
          <div class="demo-role">Mahasiswa — Budi Santoso</div>
          <div class="demo-user">budi2022 (NIM: 22TIF001)</div>
        </div>
        <i class="fas fa-chevron-right" style="color:#94a3b8;font-size:0.8rem"></i>
      </div>
    </div>
  </div>

  <script>
    function fillCreds(u, p) {
      document.getElementById('usernameInput').value = u;
      document.getElementById('passwordInput').value = p;
    }
  </script>
</body>
</html>
