<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\Consent;
use Smg\Db;

Auth::requireLogin();

$mstTz = new DateTimeZone('Asia/Kuala_Lumpur');
$utcTz = new DateTimeZone('UTC');

$pdo = Db::connection();

$q = trim((string) ($_GET['q'] ?? ''));
$escapedQ = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
$likePattern = '%' . $escapedQ . '%';

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM consent_log WHERE guid LIKE :q ESCAPE '\\\\'");
$countStmt->execute([':q' => $likePattern]);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT guid, action, consent_version, accepted_at, expires_at FROM consent_log
     WHERE guid LIKE :q ESCAPE '\\\\' ORDER BY accepted_at DESC LIMIT :limit OFFSET :offset"
);
$listStmt->bindValue(':q', $likePattern);
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

$acceptedTotal = (int) $pdo->query("SELECT COUNT(*) AS c FROM consent_log WHERE action = 'accepted'")->fetch()['c'];

$todayStartMst = new DateTimeImmutable('today', $mstTz);
$todayEndMst = $todayStartMst->modify('+1 day');
$todayStmt = $pdo->prepare(
    "SELECT COUNT(*) AS c FROM consent_log WHERE action = 'accepted' AND accepted_at >= :start AND accepted_at < :end"
);
$todayStmt->execute([
    ':start' => $todayStartMst->setTimezone($utcTz)->format('Y-m-d H:i:s'),
    ':end' => $todayEndMst->setTimezone($utcTz)->format('Y-m-d H:i:s'),
]);
$todayCount = (int) $todayStmt->fetch()['c'];

function smg_row_status(array $row, DateTimeZone $utcTz): string
{
    if ($row['action'] === 'declined') {
        return 'declined';
    }
    $expiresAt = new DateTimeImmutable($row['expires_at'], $utcTz);
    $now = new DateTimeImmutable('now', $utcTz);

    return $expiresAt < $now ? 'expired' : 'accepted';
}

function smg_format_mst(string $utcDatetime, DateTimeZone $utcTz, DateTimeZone $mstTz): string
{
    return (new DateTimeImmutable($utcDatetime, $utcTz))->setTimezone($mstTz)->format('j M Y, g:i A');
}

$statusLabels = ['accepted' => 'ACCEPTED', 'declined' => 'DECLINED', 'expired' => 'EXPIRED'];
$rangeStart = $total === 0 ? 0 : $offset + 1;
$rangeEnd = min($offset + $perPage, $total);

function smg_page_url(int $page, string $q): string
{
    $params = ['page' => $page];
    if ($q !== '') {
        $params['q'] = $q;
    }
    return 'index.php?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consent acceptances — Consent Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../assets/css/tokens.css">
  <link rel="stylesheet" href="../assets/css/site.css">
</head>
<body class="smg-admin-page">
  <header class="smg-admin-topbar">
    <div class="smg-admin-topbar__inner">
      <div class="smg-admin-login__brand">
        <span class="smg-admin-login__brand-mark" aria-hidden="true">★</span>
        <span class="smg-admin-login__brand-text smg-admin-login__brand-text--dark">Consent Admin</span>
      </div>
      <div class="smg-admin-topbar__actions">
        <span class="smg-admin-topbar__user"><?php echo htmlspecialchars((string) Auth::currentUsername(), ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="logout.php" class="smg-btn smg-btn--ghost smg-btn--sm">Log out</a>
      </div>
    </div>
  </header>

  <main class="smg-admin-main">
    <div class="smg-admin-stats">
      <div class="smg-stat-card">
        <p class="smg-eyebrow smg-eyebrow--muted">Accepted</p>
        <p class="smg-stat-card__value"><?php echo number_format($acceptedTotal); ?></p>
      </div>
      <div class="smg-stat-card">
        <p class="smg-eyebrow smg-eyebrow--muted">Today</p>
        <p class="smg-stat-card__value"><?php echo number_format($todayCount); ?></p>
      </div>
      <div class="smg-stat-card">
        <p class="smg-eyebrow smg-eyebrow--muted">Notice version</p>
        <p class="smg-stat-card__value"><?php echo (int) Consent::CONSENT_VERSION; ?></p>
      </div>
    </div>

    <div class="smg-admin-table-card">
      <div class="smg-admin-table-card__head">
        <h2>Consent acceptances</h2>
        <form method="get" action="index.php" class="smg-admin-table-card__controls">
          <input type="search" name="q" placeholder="Search GUID" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
          <button type="submit" class="smg-btn smg-btn--ghost smg-btn--sm">Search</button>
          <a class="smg-btn smg-btn--ghost smg-btn--sm" href="export.php<?php echo $q !== '' ? '?' . http_build_query(['q' => $q]) : ''; ?>">Export CSV</a>
        </form>
      </div>

      <div class="smg-admin-table-scroll">
        <table>
          <thead>
            <tr>
              <th>GUID</th>
              <th>Accepted at</th>
              <th>Ver</th>
              <th>Expires</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="5" class="smg-admin-table-empty">No consent records match this search.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
              <?php $status = smg_row_status($row, $utcTz); ?>
              <tr>
                <td class="smg-admin-table-guid"><?php echo htmlspecialchars($row['guid'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(smg_format_mst($row['accepted_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $row['consent_version']; ?></td>
                <td><?php echo htmlspecialchars(smg_format_mst($row['expires_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="smg-pill smg-pill--<?php echo $status; ?>"><?php echo $statusLabels[$status]; ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="smg-admin-table-card__footer">
        <span>Showing <?php echo $rangeStart; ?>–<?php echo $rangeEnd; ?> of <?php echo number_format($total); ?></span>
        <nav class="smg-pager" aria-label="Pagination">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?php echo htmlspecialchars(smg_page_url($p, $q), ENT_QUOTES, 'UTF-8'); ?>"
               class="smg-pager__item<?php echo $p === $page ? ' is-current' : ''; ?>"><?php echo $p; ?></a>
          <?php endfor; ?>
        </nav>
      </div>
    </div>
  </main>
</body>
</html>
