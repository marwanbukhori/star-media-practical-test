<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\Csrf;

if (Auth::isLoggedIn()) {
    header('Location: index.php', true, 303);
    exit;
}

$error = null;
$fieldError = false;
$rateLimited = false;
$retryAfter = 0;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::verify(is_string($csrfToken) ? $csrfToken : null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
        $fieldError = true;
    } elseif (Auth::isRateLimited()) {
        $retryAfter = Auth::retryAfterSeconds();
        $error = 'Too many attempts. Try again in ' . $retryAfter . 's.';
        $rateLimited = true;
    } elseif (Auth::attemptLogin($username, $password)) {
        header('Location: index.php', true, 303);
        exit;
    } else {
        $error = 'Invalid username or password.';
        $fieldError = true;
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
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="smg-admin-login-page">
  <main class="smg-admin-login">
    <div class="smg-admin-login__card">
      <div class="smg-admin-login__brand">
        <img src="../assets/images/smg-logo-on-ink.png" alt="Star Media Group" class="smg-admin-login__brand-logo">
        <span class="smg-admin-login__brand-text">Consent Admin</span>
      </div>
      <h2>Sign in</h2>

      <?php if ($error !== null): ?>
        <p
          class="smg-form-banner smg-form-banner--error"
          <?php echo $rateLimited ? 'data-smg-retry-after="' . (int) $retryAfter . '"' : ''; ?>
        ><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <form method="post" action="login.php" class="smg-form" data-smg-login-form>
        <?php echo Csrf::field(); ?>
        <div class="smg-field smg-field--dark<?php echo $fieldError ? ' has-error' : ''; ?>">
          <label for="username">Username</label>
          <input
            type="text" id="username" name="username" autocomplete="username"
            value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
            required <?php echo $username === '' ? 'autofocus' : ''; ?>
          >
        </div>
        <div class="smg-field smg-field--dark<?php echo $fieldError ? ' has-error' : ''; ?>">
          <label for="password">Password</label>
          <div class="smg-password-field">
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="smg-password-toggle" data-smg-password-toggle aria-label="Show password" aria-pressed="false">
              <svg data-smg-eye-icon width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
              </svg>
              <svg data-smg-eye-off-icon width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                <path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.5 5.2A10.8 10.8 0 0 1 12 5c7 0 11 7 11 7a13.3 13.3 0 0 1-3.1 3.8M6.4 6.6A13.6 13.6 0 0 0 1 12s4 7 11 7a10.4 10.4 0 0 0 4.2-.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="smg-btn smg-btn--primary smg-btn--block" data-smg-login-submit>
          <span data-smg-btn-label>Sign in</span>
        </button>
      </form>

      <p class="smg-admin-login__notes">
        password_verify() + session regenerate on login · CSRF token on this form · rate limited to 5 attempts/min
      </p>
    </div>
  </main>
  <script src="../assets/js/admin.js" defer></script>
</body>
</html>
