<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\AuditLog;
use Smg\ErrorPage;

Auth::requireLogin();

$mstTz = new DateTimeZone('Asia/Kuala_Lumpur');
$utcTz = new DateTimeZone('UTC');

$page = max(1, (int) ($_GET['page'] ?? 1));
try {
    $result = AuditLog::recent($page, 25);
} catch (PDOException $e) {
    error_log('Admin audit log could not load: ' . $e->getMessage());
    ErrorPage::render(503);
    exit;
}

$actionLabels = [
    'login' => 'Signed in',
    'logout' => 'Signed out',
    'export' => 'Exported CSV',
    'view_record' => 'Viewed record',
    'change_password' => 'Changed password',
];

function smg_format_mst_short(string $utcDatetime, DateTimeZone $utcTz, DateTimeZone $mstTz): string
{
    return (new DateTimeImmutable($utcDatetime, $utcTz))->setTimezone($mstTz)->format('j M Y, g:i A');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Audit log — Consent Admin</title>
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

    <div class="smg-admin-table-card">
      <div class="smg-admin-table-card__head">
        <h2>Audit log</h2>
      </div>

      <div class="smg-admin-table-scroll">
        <table>
          <thead>
            <tr>
              <th>When</th>
              <th>Admin</th>
              <th>Action</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$result['rows']): ?>
              <tr><td colspan="4" class="smg-admin-table-empty">No activity logged yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($result['rows'] as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars(smg_format_mst_short($row['created_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($actionLabels[$row['action']] ?? $row['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="smg-admin-audit-detail"><?php echo htmlspecialchars($row['detail'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="smg-admin-table-card__footer">
        <span>Showing page <?php echo (int) $result['page']; ?> of <?php echo (int) $result['totalPages']; ?> (<?php echo number_format($result['total']); ?> total)</span>
        <nav class="smg-pager" aria-label="Pagination">
          <?php for ($p = 1; $p <= $result['totalPages']; $p++): ?>
            <a href="audit.php?page=<?php echo $p; ?>"
               class="smg-pager__item<?php echo $p === $result['page'] ? ' is-current' : ''; ?>"><?php echo $p; ?></a>
          <?php endfor; ?>
        </nav>
      </div>
    </div>
  </main>
</body>
</html>
