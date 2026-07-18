<?php
require __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Throttle brute force: 5 attempts, then a 5-minute lockout per session.
    $now = time();
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'] ?? [],
        fn($t) => $t > $now - 300
    );
    if (count($_SESSION['login_attempts']) >= 5) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash FROM admin_users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            unset($_SESSION['login_attempts']);
            header('Location: index.php');
            exit;
        }
        $_SESSION['login_attempts'][] = $now;
        $error = 'Incorrect email or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Portal — Rentcom</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600&family=Nunito:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/site.css">
</head>
<body>
<div style="background:#111;width:100%;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;padding:24px">
  <div style="width:min(420px,90vw)">
    <div style="display:flex;align-items:flex-start;gap:6px;justify-content:center;margin-bottom:56px">
      <div style="width:11px;height:11px;border-radius:50%;background:#FF3C3C;margin-top:6px;flex-shrink:0"></div>
      <span style="font:800 32px/1 'Nunito',sans-serif;color:#FF6200;letter-spacing:-.02em">Rentcom</span>
    </div>
    <form method="post" style="background:#1A1A1A;border:1px solid #2A2A2A;padding:44px 40px">
      <h1 style="font:700 26px/1.2 'Playfair Display',serif;color:#fff;margin-bottom:8px;text-align:center">Admin Portal</h1>
      <p style="font:400 14px/1.5 'DM Sans',sans-serif;color:#777;margin-bottom:36px;text-align:center">Sign in to manage leads and campaigns</p>
      <?php if ($error): ?>
      <p style="font:400 13px/1.5 'DM Sans',sans-serif;color:#FF6E6E;margin-bottom:24px;text-align:center"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <div style="margin-bottom:20px">
        <label for="email" style="display:block;font:500 13px/1 'DM Sans',sans-serif;color:#ccc;margin-bottom:9px">Email</label>
        <input class="field field--dark" style="background:#111;padding:15px 18px;font-size:15px" type="email" id="email" name="email" placeholder="team@rentcom.com" required autocomplete="username">
      </div>
      <div style="margin-bottom:32px">
        <label for="password" style="display:block;font:500 13px/1 'DM Sans',sans-serif;color:#ccc;margin-bottom:9px">Password</label>
        <input class="field field--dark" style="background:#111;padding:15px 18px;font-size:15px" type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <button type="submit" class="btn" style="padding:16px;font:600 15px/1 'DM Sans',sans-serif">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
