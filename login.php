<?php
session_start();

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Already logged in
if (!empty($_SESSION['logged_in'])) {
    header('Location: app/index.php');
    exit;
}

$error = '';

require_once __DIR__ . '/app/config.php';
$storedHash = CFG_PASSWORD_HASH;
$defaultPassword = CFG_DEFAULT_PASSWORD;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Ungültige Anfrage. Bitte Seite neu laden.';
    } else {
        $password = $_POST['password'] ?? '';
        $valid = false;

        if (!empty($storedHash)) {
            $valid = password_verify($password, $storedHash);
        } else {
            $valid = ($password === $defaultPassword);
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: app/index.php');
            exit;
        } else {
            $error = 'Falsches Passwort. Bitte erneut versuchen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>evalu-pro · Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
@font-face{font-family:'Geist Mono';src:url('https://r2.vercel-storage.com/geist-mono/GeistMono-Regular.woff2') format('woff2');font-weight:400;font-style:normal;font-display:swap}
  :root {
    --bg:#f8f7f5; --bg2:#ffffff; --bg3:#f2f1ef;
    --border:#e3e2df; --border2:#d0ceca;
    --text:#1a1917; --text2:#4a4845; --text3:#908d8a;
    --accent:#0049EC; --accent2:#263FCC;
    --accent-bg:rgba(0,73,236,.07); --accent-border:rgba(0,73,236,.18);
    --green:#15803d; --green-bg:#f0fdf4; --green-border:#bbf7d0;
    --amber:#b45309; --amber-bg:#fffbeb; --amber-border:#fde68a;
    --red:#dc2626; --red-bg:#fef2f2; --red-border:#fecaca;
    --radius:10px; --radius-lg:14px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Manrope', system-ui, sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    color: var(--text);
  }
  .login-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 40px 36px;
    width: 100%;
    max-width: 380px;
    box-shadow: 0 4px 24px rgba(26,25,23,.08), 0 1px 4px rgba(26,25,23,.06);
  }
  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
  }
  .brand-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .brand-icon svg { color: #fff; }
  .brand-name {
    font-family: 'Manrope', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.3px;
  }
  .brand-name span { color: var(--accent); }
  .tagline {
    font-size: 13px;
    color: var(--text3);
    margin-bottom: 32px;
    padding-left: 54px;
  }
  .divider {
    height: 1px;
    background: var(--border);
    margin-bottom: 24px;
  }
  label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text2);
    margin-bottom: 6px;
  }
  .input-wrap {
    position: relative;
    margin-bottom: 20px;
  }
  input[type="password"], input[type="text"] {
    width: 100%;
    height: 44px;
    padding: 0 44px 0 14px;
    border: 1px solid var(--border2);
    border-radius: var(--radius);
    background: var(--bg);
    font-family: 'Geist Mono', monospace;
    font-size: 14px;
    color: var(--text);
    outline: none;
    transition: border-color .15s, box-shadow .15s;
  }
  input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-border);
    background: var(--bg2);
  }
  .toggle-pw {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text3);
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .15s;
  }
  .toggle-pw:hover { color: var(--text2); }
  .btn-login {
    width: 100%;
    height: 46px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-family: 'Manrope', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, transform .1s, box-shadow .2s;
    box-shadow: 0 2px 8px rgba(67,56,202,.30);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .btn-login:hover {
    background: var(--accent2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67,56,202,.35);
  }
  .btn-login:active { transform: translateY(0); }
  .error-box {
    margin-top: 16px;
    padding: 10px 14px;
    background: var(--red-bg);
    border: 1px solid var(--red-border);
    border-radius: var(--radius);
    color: var(--red);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
</style>
</head>
<body>
<div class="login-card">
  <div class="brand">
    <div class="brand-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>
    </div>
    <div class="brand-name">evalu<span>-pro</span></div>
  </div>
  <div class="tagline">Internes SEO-Tool · SQEG Analyzer</div>
  <div class="divider"></div>

  <form method="POST" action="login.php" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <label for="password">Passwort</label>
    <div class="input-wrap">
      <input type="password" id="password" name="password" autofocus placeholder="••••••••••••" required>
      <button type="button" class="toggle-pw" onclick="togglePw()" title="Passwort anzeigen/verbergen">
        <svg id="eye-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>
    <button type="submit" class="btn-login">
      Anmelden
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
      </svg>
    </button>
  </form>

  <?php if ($error): ?>
  <div class="error-box">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>
</div>

<script>
function togglePw() {
  const inp = document.getElementById('password');
  const ico = document.getElementById('eye-icon');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>
</body>
</html>
