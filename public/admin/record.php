<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\AuditLog;
use Smg\Db;

Auth::requireLogin();

$mstTz = new DateTimeZone('Asia/Kuala_Lumpur');
$utcTz = new DateTimeZone('UTC');

$guid = trim((string) ($_GET['guid'] ?? ''));
$record = null;

if ($guid !== '') {
    AuditLog::record('view_record', Auth::currentUserId(), Auth::currentUsername(), $guid);

    $stmt = Db::connection()->prepare(
        'SELECT guid, action, consent_version, accepted_at, expires_at, ip_address, user_agent, created_at
         FROM consent_log WHERE guid = :guid'
    );
    $stmt->bindValue(':guid', $guid);
    $stmt->execute();
    $record = $stmt->fetch() ?: null;
}

function smg_format_mst_full(string $utcDatetime, DateTimeZone $utcTz, DateTimeZone $mstTz): string
{
    return (new DateTimeImmutable($utcDatetime, $utcTz))->setTimezone($mstTz)->format('j M Y, g:i:s A \M\S\T');
}

$status = null;
if ($record !== null) {
    if ($record['action'] === 'declined') {
        $status = 'declined';
    } else {
        $expiresAt = new DateTimeImmutable($record['expires_at'], $utcTz);
        $status = $expiresAt < new DateTimeImmutable('now', $utcTz) ? 'expired' : 'accepted';
    }
}
$statusLabels = ['accepted' => 'ACCEPTED', 'declined' => 'DECLINED', 'expired' => 'EXPIRED'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consent record — Consent Admin</title>
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

    <?php if ($record === null): ?>
      <div class="smg-admin-table-card smg-admin-record-card">
        <p class="smg-admin-table-empty">No record found for that GUID.</p>
      </div>
    <?php else: ?>
      <div class="smg-admin-table-card smg-admin-record-card">
        <div class="smg-admin-table-card__head">
          <h2>Consent record</h2>
          <span class="smg-pill smg-pill--<?php echo $status; ?>"><?php echo $statusLabels[$status]; ?></span>
        </div>
        <dl class="smg-admin-record-detail">
          <dt>GUID</dt>
          <dd class="smg-admin-table-guid"><?php echo htmlspecialchars($record['guid'], ENT_QUOTES, 'UTF-8'); ?></dd>

          <dt>Action</dt>
          <dd><?php echo htmlspecialchars(ucfirst($record['action']), ENT_QUOTES, 'UTF-8'); ?></dd>

          <dt>Notice version</dt>
          <dd><?php echo (int) $record['consent_version']; ?></dd>

          <dt>Accepted / declined at</dt>
          <dd><?php echo htmlspecialchars(smg_format_mst_full($record['accepted_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></dd>

          <dt>Expires at</dt>
          <dd><?php echo htmlspecialchars(smg_format_mst_full($record['expires_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></dd>

          <dt>IP address</dt>
          <dd><?php echo $record['ip_address'] !== null ? htmlspecialchars((string) inet_ntop($record['ip_address']), ENT_QUOTES, 'UTF-8') : '—'; ?></dd>

          <dt>User agent</dt>
          <dd class="smg-admin-record-detail__wrap"><?php echo $record['user_agent'] !== null ? htmlspecialchars($record['user_agent'], ENT_QUOTES, 'UTF-8') : '—'; ?></dd>

          <dt>Logged at</dt>
          <dd><?php echo htmlspecialchars(smg_format_mst_full($record['created_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></dd>
        </dl>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
