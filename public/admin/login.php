<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\Csrf;

if (Auth::isLoggedIn()) {
    header('Location: index.php', true, 303);
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::verify(is_string($csrfToken) ? $csrfToken : null)) {
        $error = 'Your session expired — please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } elseif (Auth::attemptLogin($username, $password)) {
        header('Location: index.php', true, 303);
        exit;
    } else {
        $error = 'Invalid username or password, or too many attempts — try again shortly.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in — Consent Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../assets/css/tokens.css">
  <link rel="stylesheet" href="../assets/css/site.css">
</head>
<body class="smg-admin-login-page">
  <main class="smg-admin-login">
    <div class="smg-admin-login__card">
      <div class="smg-admin-login__brand">
        <span class="smg-admin-login__brand-mark" aria-hidden="true">★</span>
        <span class="smg-admin-login__brand-text">Consent Admin</span>
      </div>
      <h2>Sign in</h2>

      <?php if ($error !== null): ?>
        <p class="smg-form-banner smg-form-banner--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <form method="post" action="login.php" class="smg-form">
        <?php echo Csrf::field(); ?>
        <div class="smg-field smg-field--dark">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="smg-field smg-field--dark">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="smg-btn smg-btn--primary smg-btn--block">Sign in</button>
      </form>

      <p class="smg-admin-login__notes">
        password_verify() + session regenerate on login · CSRF token on this form · rate limited to 5 attempts/min
      </p>
    </div>
  </main>
</body>
</html>
