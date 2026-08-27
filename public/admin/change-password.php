<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\AuditLog;
use Smg\Csrf;
use Smg\Db;

Auth::requireLogin();

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!Csrf::verify(is_string($csrfToken) ? $csrfToken : null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $pdo = Db::connection();
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
        $stmt->execute([':id' => Auth::currentUserId()]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } else {
            $update = $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id');
            $update->execute([
                ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id' => Auth::currentUserId(),
            ]);
            AuditLog::record('change_password', Auth::currentUserId(), Auth::currentUsername());
            $success = true;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Change password — Consent Admin</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png">
  <link rel="apple-touch-icon" href="../assets/images/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../assets/css/tokens.css">
  <link rel="stylesheet" href="../assets/css/site.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="smg-admin-page">
  <header class="smg-admin-topbar">
    <div class="smg-admin-topbar__inner">
      <div class="smg-admin-login__brand">
        <img src="../assets/images/smg-logo-on-ink.png" alt="Star Media Group" class="smg-admin-login__brand-logo">
        <span class="smg-admin-login__brand-text">Consent Admin</span>
      </div>
      <div class="smg-admin-topbar__actions">
        <span class="smg-admin-topbar__user"><?php echo htmlspecialchars((string) Auth::currentUsername(), ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="logout.php" class="smg-btn smg-btn--ghost smg-btn--sm">Log out</a>
      </div>
    </div>
  </header>

  <main class="smg-admin-main">
    <a href="index.php" class="smg-admin-back-link">&larr; Back to consent acceptances</a>

    <div class="smg-admin-table-card smg-admin-record-card smg-admin-narrow-card">
      <div class="smg-admin-table-card__head">
        <h2>Change password</h2>
      </div>

      <?php if ($success): ?>
        <p class="smg-form-banner smg-form-banner--success">Password updated.</p>
      <?php endif; ?>
      <?php if ($error !== null): ?>
        <p class="smg-form-banner smg-form-banner--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <form method="post" action="change-password.php" class="smg-form">
        <?php echo Csrf::field(); ?>
        <div class="smg-field">
          <label for="current_password">Current password</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="smg-field">
          <label for="new_password">New password</label>
          <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="smg-field">
          <label for="confirm_password">Confirm new password</label>
          <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
        </div>
        <button type="submit" class="smg-btn smg-btn--primary">Update password</button>
      </form>
    </div>
  </main>
</body>
</html>
